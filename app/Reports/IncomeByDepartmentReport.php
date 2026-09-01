<?php

namespace App\Reports;

use App\Models\Clinic;
use App\Models\MedicalDepartment;
use App\Models\OrganizationUnit;
use App\Models\VisitDepartment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class IncomeByDepartmentReport
{
    public const DEPARTMENT_LABELS = [
        'clinics' => 'الكشف الطبي',
        'pharmacy' => 'الصيدلية',
        'laboratory' => 'المختبر',
        'optics' => 'البصريات',
        'dental' => 'الأسنان',
        'radiology' => 'الأشعة',
    ];

    public const FILTERABLE_COLUMNS = [
        'visit_date',
        'department_name',
        'clinic_name',
        'organization_center',
        'organization_department',
    ];

    public static function query(Request $request, ?array $columnFilters = null): Builder
    {
        return self::lineQuery($request, $columnFilters)
            ->groupBy('medical_departments.id', 'medical_departments.name')
            ->selectRaw('medical_departments.id as department_id, medical_departments.name as department_name,
                COUNT(*) as visits_count,
                SUM(visit_departments.amount_before_discount) as total_before,
                SUM(visit_departments.amount_after_discount) as total_after,
                SUM(visit_departments.amount_before_discount - visit_departments.amount_after_discount) as total_discount')
            ->orderByDesc('total_after')
            ->orderBy('department_name');
    }

    public static function rows(Request $request): Collection
    {
        return self::aggregates($request)->map(fn ($row) => self::row($row));
    }

    public static function headings(): array
    {
        return [
            'اسم القسم الطبي',
            'عدد الزيارات المحتسبة',
            'إجمالي قبل الخصم',
            'إجمالي بعد الخصم',
            'إجمالي الخصم',
            'متوسط الخصم %',
            'نسبة الخصم المُعتمدة حالياً',
            'الحد الأقصى للخصم (حالياً)',
        ];
    }

    public static function summary(Request $request): array
    {
        $rows = self::aggregates($request);
        $topDepartment = $rows->sortByDesc(fn ($row) => (float) $row->total_after)->first();

        return [
            'total_before' => number_format((float) $rows->sum(fn ($row) => (float) $row->total_before), 2),
            'total_after' => number_format((float) $rows->sum(fn ($row) => (float) $row->total_after), 2),
            'total_discount' => number_format((float) $rows->sum(fn ($row) => (float) $row->total_discount), 2),
            'top_department' => $topDepartment ? self::departmentLabel($topDepartment->department_name) : '-',
        ];
    }

    /** Raw numeric sums for the DataTable footer totals row (unformatted, JS does its own formatting). */
    public static function totals(Request $request): array
    {
        $rows = self::aggregates($request);

        return [
            'total_before' => (float) $rows->sum(fn ($row) => (float) $row->total_before),
            'total_after' => (float) $rows->sum(fn ($row) => (float) $row->total_after),
            'total_discount' => (float) $rows->sum(fn ($row) => (float) $row->total_discount),
        ];
    }

    public static function filterOptions(string $column, Request $request): Collection
    {
        $query = self::lineQuery($request, (array) $request->input('active_filters', []));

        if ($column === 'visit_date') {
            return (clone $query)
                ->pluck('visits.visit_date')
                ->filter()
                ->map(fn ($value) => Carbon::parse($value)->format('Y-m-d'))
                ->unique()
                ->sortDesc()
                ->values();
        }

        if ($column === 'department_name') {
            return (clone $query)
                ->pluck('medical_departments.name')
                ->filter()
                ->unique()
                ->values()
                ->map(fn ($name) => self::departmentLabel($name));
        }

        if ($column === 'clinic_name') {
            return Clinic::query()
                ->whereIn('id', (clone $query)->whereNotNull('visits.clinic_id')->distinct()->pluck('visits.clinic_id'))
                ->distinct()
                ->pluck('name')
                ->filter()
                ->values();
        }

        if ($column === 'organization_center' || $column === 'organization_department') {
            $level = $column === 'organization_center' ? 1 : 2;
            $sectionIds = (clone $query)->distinct()->pluck('employees.organization_unit_id')->filter()->unique();

            return OrganizationUnit::where('level', $level)
                ->when($level === 1, fn ($q) => $q->whereHas('children.children', fn ($qq) => $qq->whereIn('id', $sectionIds)))
                ->when($level === 2, fn ($q) => $q->whereHas('children', fn ($qq) => $qq->whereIn('id', $sectionIds)))
                ->distinct()
                ->pluck('name')
                ->filter()
                ->values();
        }

        return collect();
    }

    public static function filtersDescription(Request $request): string
    {
        $filters = self::filtersFromRequest($request);
        $parts = [];

        if (! empty($filters['department_name'])) {
            $parts[] = 'القسم الطبي: '.implode('، ', self::departmentLabels($filters['department_name']));
        }

        if (! empty($filters['clinic_name'])) {
            $parts[] = 'العيادة: '.implode('، ', $filters['clinic_name']);
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

    private static function aggregates(Request $request): Collection
    {
        return self::query($request)->get();
    }

    private static function row($department): array
    {
        $totalBefore = (float) $department->total_before;
        $totalDiscount = (float) $department->total_discount;
        $avgDiscount = $totalBefore > 0 ? ($totalDiscount / $totalBefore) * 100 : null;
        $config = MedicalDepartment::where('name', $department->department_name)->first();

        return [
            'department_name' => self::departmentLabel($department->department_name),
            'visits_count' => (int) $department->visits_count,
            'total_before' => number_format($totalBefore, 2),
            'total_after' => number_format((float) $department->total_after, 2),
            'total_discount' => number_format($totalDiscount, 2),
            'avg_discount_percentage' => $avgDiscount === null ? '-' : number_format($avgDiscount, 1),
            'current_discount_percentage' => $config ? rtrim(rtrim(number_format($config->discount_percentage, 2), '0'), '.').'%' : '-',
            'current_max_discount_amount' => $config?->max_discount_amount !== null ? number_format((float) $config->max_discount_amount, 2) : 'بدون حد أقصى',
        ];
    }

    private static function lineQuery(Request $request, ?array $columnFilters = null): Builder
    {
        $query = VisitDepartment::query()
            ->join('medical_departments', 'medical_departments.id', '=', 'visit_departments.medical_department_id')
            ->join('visits', 'visits.id', '=', 'visit_departments.visit_id')
            ->join('employees', 'employees.id', '=', 'visits.employee_id')
            ->whereNotNull('visit_departments.amount_before_discount');

        $filters = self::filtersFromRequest($request, $columnFilters);

        if (! empty($filters['visit_from'])) {
            $query->whereDate('visits.visit_date', '>=', $filters['visit_from']);
        }

        if (! empty($filters['visit_to'])) {
            $query->whereDate('visits.visit_date', '<=', $filters['visit_to']);
        }

        if (empty($filters['visit_from']) && empty($filters['visit_to'])) {
            $query->whereBetween('visits.visit_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);
        }

        $departmentNames = self::normalizeDepartmentValues($filters['department_name']);
        if ($departmentNames !== []) {
            $query->whereIn('medical_departments.name', $departmentNames);
        }

        if (! empty($filters['clinic_name']) && ($departmentNames === [] || in_array('clinics', $departmentNames, true))) {
            $query->whereIn('visits.clinic_id', Clinic::whereIn('name', $filters['clinic_name'])->pluck('id'));
        }

        if (! empty($filters['organization_center'])) {
            $query->whereIn('employees.organization_unit_id', self::sectionIdsUnderAncestors(1, $filters['organization_center']));
        }

        if (! empty($filters['organization_department'])) {
            $query->whereIn('employees.organization_unit_id', self::sectionIdsUnderAncestors(2, $filters['organization_department']));
        }

        return $query;
    }

    private static function filtersFromRequest(Request $request, ?array $columnFilters = null): array
    {
        $columnFilters ??= (array) $request->input('column_filters', []);

        return [
            'department_name' => self::listFilter($request, $columnFilters, 'department_name'),
            'clinic_name' => self::listFilter($request, $columnFilters, 'clinic_name'),
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

    private static function normalizeDepartmentValues(array $values): array
    {
        $labelToName = array_flip(self::DEPARTMENT_LABELS);

        return collect($values)
            ->map(fn ($value) => $labelToName[$value] ?? $value)
            ->filter()
            ->values()
            ->all();
    }

    private static function departmentLabels(array $values): array
    {
        return collect(self::normalizeDepartmentValues($values))
            ->map(fn ($name) => self::departmentLabel($name))
            ->all();
    }

    private static function departmentLabel(string $name): string
    {
        return self::DEPARTMENT_LABELS[$name] ?? $name;
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
