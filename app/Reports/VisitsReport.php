<?php

namespace App\Reports;

use App\Models\Clinic;
use App\Models\Dependent;
use App\Models\Employee;
use App\Models\MedicalDepartment;
use App\Models\OrganizationUnit;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitDepartment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class VisitsReport
{
    public const DEPARTMENT_LABELS = [
        'clinics' => 'الكشف الطبي',
        'pharmacy' => 'الصيدلية',
        'laboratory' => 'المختبر',
        'optics' => 'البصريات',
        'dental' => 'الأسنان',
        'radiology' => 'الأشعة',
    ];

    public const PATIENT_TYPE_LABELS = [
        'employee' => 'موظف',
        'dependent' => 'تابع',
    ];

    public const FILTERABLE_COLUMNS = [
        'visit_date',
        'patient_name',
        'patient_type',
        'employee_name',
        'clinic_name',
        'departments_list',
        'recorded_by_name',
        'organization_center',
        'organization_department',
    ];

    public static function query(Request $request, ?array $columnFilters = null): Builder
    {
        $query = Visit::query()
            ->with([
                'patientEmployee',
                'patientDependent',
                'employee.organizationUnit.parent.parent',
                'clinic',
                'recordedBy',
                'lastUpdatedBy',
                'visitDepartments.medicalDepartment',
            ])
            ->withCount('visitDepartments');

        $filters = self::filtersFromRequest($request, $columnFilters);

        if (! empty($filters['visit_from'])) {
            $query->whereDate('visit_date', '>=', $filters['visit_from']);
        }

        if (! empty($filters['visit_to'])) {
            $query->whereDate('visit_date', '<=', $filters['visit_to']);
        }

        if (empty($filters['visit_from']) && empty($filters['visit_to'])) {
            $query->whereBetween('visit_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);
        }

        if (! empty($filters['patient_name'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('patientEmployee', fn ($qq) => $qq->whereIn('full_name', $filters['patient_name']))
                    ->orWhereHas('patientDependent', fn ($qq) => $qq->whereIn('full_name', $filters['patient_name']));
            });
        }

        if (! empty($filters['employee_name'])) {
            $query->whereHas('employee', fn ($q) => $q->whereIn('full_name', $filters['employee_name']));
        }

        if (! empty($filters['clinic_name'])) {
            $query->whereHas('clinic', fn ($q) => $q->whereIn('name', $filters['clinic_name']));
        }

        if (! empty($filters['departments_list'])) {
            $labelToName = array_flip(self::DEPARTMENT_LABELS);
            $names = collect($filters['departments_list'])->map(fn ($label) => $labelToName[$label] ?? $label);

            $query->whereHas('visitDepartments.medicalDepartment', fn ($q) => $q->whereIn('name', $names));
        }

        if (! empty($filters['recorded_by_name'])) {
            $query->whereHas('recordedBy', fn ($q) => $q->whereIn('name', $filters['recorded_by_name']));
        }

        if (! empty($filters['organization_center'])) {
            $sectionIds = self::sectionIdsUnderAncestors(1, $filters['organization_center']);
            $query->whereHas('employee', fn ($q) => $q->whereIn('organization_unit_id', $sectionIds));
        }

        if (! empty($filters['organization_department'])) {
            $sectionIds = self::sectionIdsUnderAncestors(2, $filters['organization_department']);
            $query->whereHas('employee', fn ($q) => $q->whereIn('organization_unit_id', $sectionIds));
        }

        if (! empty($filters['patient_type'])) {
            $types = self::normalizeMappedValues('patient_type', $filters['patient_type']);

            $query->where(function ($q) use ($types) {
                if (in_array('employee', $types, true)) {
                    $q->orWhereNotNull('patient_employee_id');
                }

                if (in_array('dependent', $types, true)) {
                    $q->orWhereNotNull('patient_dependent_id');
                }
            });
        }

        return $query;
    }

    public static function rows(Request $request): Collection
    {
        return self::query($request)
            ->orderBy('visit_date')
            ->orderBy('id')
            ->get()
            ->map(fn (Visit $visit) => self::row($visit));
    }

    public static function headings(): array
    {
        return [
            'التاريخ',
            'اسم المريض',
            'نوع المريض',
            'الموظف صاحب الرصيد',
            'المركزية',
            'الدائرة',
            'العيادة',
            'تفاصيل الأقسام (الخصم والمبلغ)',
            'المبلغ قبل الخصم',
            'المبلغ بعد الخصم',
            'مسجّل الزيارة',
            'آخر تعديل بواسطة',
        ];
    }

    public static function summary(Request $request): array
    {
        $query = self::query($request);

        $totalVisits = (clone $query)->count();
        $totalBeforeDiscount = (clone $query)->sum('total_before_discount');
        $totalAfterDiscount = (clone $query)->sum('total_after_discount');
        $avgDepartments = $totalVisits > 0
            ? (clone $query)->setEagerLoads([])->get()->avg('visit_departments_count')
            : 0;

        return [
            'total_visits' => $totalVisits,
            'total_before_discount' => number_format((float) $totalBeforeDiscount, 2),
            'total_after_discount' => number_format((float) $totalAfterDiscount, 2),
            'avg_departments_per_visit' => number_format(round((float) $avgDepartments, 1), 1),
        ];
    }

    /** Raw numeric sums for the DataTable footer totals row (unformatted, JS does its own formatting). */
    public static function totals(Request $request): array
    {
        $query = self::query($request);

        return [
            'total_before_discount' => (float) (clone $query)->sum('total_before_discount'),
            'total_after_discount' => (float) (clone $query)->sum('total_after_discount'),
        ];
    }

    public static function departmentsDetail(Visit $visit): string
    {
        return $visit->visitDepartments->map(function ($visitDepartment) {
            $name = self::DEPARTMENT_LABELS[$visitDepartment->medicalDepartment?->name] ?? $visitDepartment->medicalDepartment?->name ?? '-';
            $percentage = rtrim(rtrim(number_format((float) $visitDepartment->applied_discount_percentage, 2), '0'), '.');
            $before = $visitDepartment->amount_before_discount !== null ? number_format((float) $visitDepartment->amount_before_discount, 2) : '-';
            $after = $visitDepartment->amount_after_discount !== null ? number_format((float) $visitDepartment->amount_after_discount, 2) : '-';

            return "{$name} (خصم {$percentage}%: {$before} ← {$after})";
        })->implode(' | ') ?: '-';
    }

    public static function filterOptions(string $column, Request $request): Collection
    {
        $query = self::query($request, (array) $request->input('active_filters', []));

        if ($column === 'visit_date') {
            return (clone $query)
                ->setEagerLoads([])
                ->get(['visit_date'])
                ->pluck('visit_date')
                ->filter()
                ->map(fn ($value) => Carbon::parse($value)->format('Y-m-d'))
                ->unique()
                ->sortDesc()
                ->values();
        }

        if ($column === 'patient_type') {
            $values = collect();

            if ((clone $query)->setEagerLoads([])->whereNotNull('patient_employee_id')->exists()) {
                $values->push(self::PATIENT_TYPE_LABELS['employee']);
            }

            if ((clone $query)->setEagerLoads([])->whereNotNull('patient_dependent_id')->exists()) {
                $values->push(self::PATIENT_TYPE_LABELS['dependent']);
            }

            return $values;
        }

        if ($column === 'organization_center' || $column === 'organization_department') {
            $level = $column === 'organization_center' ? 1 : 2;
            $sectionIds = (clone $query)->get()->pluck('employee.organization_unit_id')->filter()->unique();

            return OrganizationUnit::where('level', $level)
                ->when($level === 1, fn ($q) => $q->whereHas('children.children', fn ($qq) => $qq->whereIn('id', $sectionIds)))
                ->when($level === 2, fn ($q) => $q->whereHas('children', fn ($qq) => $qq->whereIn('id', $sectionIds)))
                ->distinct()
                ->pluck('name')
                ->filter()
                ->values();
        }

        return match ($column) {
            'clinic_name' => Clinic::query()
                ->whereIn('id', (clone $query)->setEagerLoads([])->whereNotNull('clinic_id')->distinct()->pluck('clinic_id'))
                ->distinct()
                ->pluck('name')
                ->filter()
                ->values(),
            'patient_name' => Employee::query()
                ->whereIn('id', (clone $query)->setEagerLoads([])->whereNotNull('patient_employee_id')->distinct()->pluck('patient_employee_id'))
                ->pluck('full_name')
                ->merge(
                    Dependent::query()
                        ->whereIn('id', (clone $query)->setEagerLoads([])->whereNotNull('patient_dependent_id')->distinct()->pluck('patient_dependent_id'))
                        ->pluck('full_name')
                )
                ->filter()
                ->unique()
                ->values(),
            'employee_name' => Employee::query()
                ->whereIn('id', (clone $query)->setEagerLoads([])->distinct()->pluck('employee_id'))
                ->distinct()
                ->pluck('full_name')
                ->filter()
                ->values(),
            'departments_list' => MedicalDepartment::query()
                ->whereIn('id', VisitDepartment::query()
                    ->whereIn('visit_id', (clone $query)->setEagerLoads([])->pluck('id'))
                    ->distinct()
                    ->pluck('medical_department_id'))
                ->pluck('name')
                ->map(fn ($name) => self::DEPARTMENT_LABELS[$name] ?? $name)
                ->filter()
                ->unique()
                ->values(),
            'recorded_by_name' => User::query()
                ->whereIn('id', (clone $query)->setEagerLoads([])->whereNotNull('recorded_by')->distinct()->pluck('recorded_by'))
                ->pluck('name')
                ->filter()
                ->values(),
            default => collect(),
        };
    }

    public static function filtersDescription(Request $request): string
    {
        $filters = self::filtersFromRequest($request);
        $parts = [];

        foreach (['patient_name', 'patient_type', 'employee_name', 'clinic_name', 'departments_list', 'recorded_by_name'] as $column) {
            if (! empty($filters[$column])) {
                $parts[] = self::filterLabel($column).': '.implode('، ', self::labelValues($column, $filters[$column]));
            }
        }

        if (! empty($filters['organization_center'])) {
            $parts[] = 'المركزية: '.implode('، ', $filters['organization_center']);
        }

        if (! empty($filters['organization_department'])) {
            $parts[] = 'الدائرة: '.implode('، ', $filters['organization_department']);
        }

        if (! empty($filters['visit_from']) || ! empty($filters['visit_to'])) {
            $parts[] = 'تاريخ الزيارة: من '.($filters['visit_from'] ?: '-').' إلى '.($filters['visit_to'] ?: '-');
        }

        return $parts === [] ? 'الشهر الحالي افتراضياً' : implode(' | ', $parts);
    }

    private static function row(Visit $visit): array
    {
        $chain = $visit->employee?->organizationUnit?->ancestryChain() ?? [];

        return [
            'visit_date' => $visit->visit_date,
            'patient_name' => $visit->patientEmployee?->full_name ?? $visit->patientDependent?->full_name ?? '-',
            'patient_type' => $visit->patient_employee_id ? self::PATIENT_TYPE_LABELS['employee'] : self::PATIENT_TYPE_LABELS['dependent'],
            'employee_name' => $visit->employee?->full_name ?? '-',
            'organization_center' => $chain['center']->name ?? '-',
            'organization_department' => $chain['department']->name ?? '-',
            'clinic_name' => $visit->clinic?->name ?? '-',
            'departments_detail' => self::departmentsDetail($visit),
            'total_before_discount' => $visit->total_before_discount !== null ? number_format((float) $visit->total_before_discount, 2) : '-',
            'total_after_discount' => $visit->total_after_discount !== null ? number_format((float) $visit->total_after_discount, 2) : '-',
            'recorded_by_name' => $visit->recordedBy?->name ?? '-',
            'last_updated_by_name' => $visit->lastUpdatedBy?->name ?? '-',
        ];
    }

    private static function filtersFromRequest(Request $request, ?array $columnFilters = null): array
    {
        $columnFilters ??= (array) $request->input('column_filters', []);

        return [
            'patient_name' => self::listFilter($request, $columnFilters, 'patient_name'),
            'patient_type' => self::listFilter($request, $columnFilters, 'patient_type'),
            'employee_name' => self::listFilter($request, $columnFilters, 'employee_name'),
            'clinic_name' => self::listFilter($request, $columnFilters, 'clinic_name'),
            'departments_list' => self::listFilter($request, $columnFilters, 'departments_list'),
            'recorded_by_name' => self::listFilter($request, $columnFilters, 'recorded_by_name'),
            'organization_center' => self::listFilter($request, $columnFilters, 'organization_center'),
            'organization_department' => self::listFilter($request, $columnFilters, 'organization_department'),
            'visit_from' => $columnFilters['visit_date']['from'] ?? $request->input('visit_from') ?? $request->input('from_date'),
            'visit_to' => $columnFilters['visit_date']['to'] ?? $request->input('visit_to') ?? $request->input('to_date'),
        ];
    }

    private static function listFilter(Request $request, array $columnFilters, string $key): array
    {
        $value = $columnFilters[$key] ?? $request->input($key, []);

        if (is_string($value)) {
            $value = str_contains($value, ',') ? explode(',', $value) : [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->reject(fn ($item) => $item === null || $item === '' || $item === 'all' || $item === 'All' || $item === 'الكل')
            ->values()
            ->all();
    }

    private static function normalizeMappedValues(string $column, array $values): array
    {
        $map = match ($column) {
            'patient_type' => array_flip(self::PATIENT_TYPE_LABELS),
            default => [],
        };

        return collect($values)
            ->map(fn ($value) => $map[$value] ?? $value)
            ->filter()
            ->values()
            ->all();
    }

    private static function labelValues(string $column, array $values): array
    {
        $normalized = self::normalizeMappedValues($column, $values);
        $labels = match ($column) {
            'patient_type' => self::PATIENT_TYPE_LABELS,
            default => [],
        };

        return collect($normalized)
            ->map(fn ($value) => $labels[$value] ?? $value)
            ->all();
    }

    private static function filterLabel(string $column): string
    {
        return match ($column) {
            'patient_name' => 'اسم المريض',
            'patient_type' => 'نوع المريض',
            'employee_name' => 'الموظف صاحب الرصيد',
            'clinic_name' => 'العيادة',
            'departments_list' => 'الأقسام المضافة',
            'recorded_by_name' => 'مسجّل الزيارة',
            default => $column,
        };
    }

    private static function sectionIdsUnderAncestors(int $ancestorLevel, array $ancestorNames): array
    {
        $ancestorIds = OrganizationUnit::where('level', $ancestorLevel)
            ->whereIn('name', $ancestorNames)
            ->pluck('id');

        if ($ancestorIds->isEmpty()) {
            return [];
        }

        return match ($ancestorLevel) {
            1 => OrganizationUnit::where('level', 3)
                ->whereHas('parent', fn ($q) => $q->whereIn('parent_id', $ancestorIds))
                ->pluck('id')
                ->all(),
            2 => OrganizationUnit::where('level', 3)
                ->whereIn('parent_id', $ancestorIds)
                ->pluck('id')
                ->all(),
            default => [],
        };
    }
}
