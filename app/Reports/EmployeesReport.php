<?php

namespace App\Reports;

use App\Models\Employee;
use App\Models\OrganizationUnit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class EmployeesReport
{
    public const STATUS_LABELS = [
        'pending' => 'قيد الموافقة',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
    ];

    public const GENDER_LABELS = [
        'male' => 'ذكر',
        'female' => 'أنثى',
    ];

    public const MARITAL_STATUS_LABELS = [
        'single' => 'أعزب/عزباء',
        'married' => 'متزوج/ة',
        'polygamous' => 'متعدد الزوجات',
        'widowed' => 'أرمل/ة',
        'divorced' => 'مطلق/ة',
    ];

    public const SOURCE_LABELS = [
        'survey' => 'استبيان',
        'admin' => 'إضافة مباشرة',
    ];

    public const DEPENDENT_TYPE_LABELS = [
        'spouse' => 'زوج/ة',
        'child' => 'ابن/ابنة',
        'parent' => 'والد/ة',
    ];

    public const PARENT_TYPE_LABELS = [
        'father' => 'أب',
        'mother' => 'أم',
    ];

    public const FILTERABLE_COLUMNS = [
        'status',
        'gender',
        'marital_status',
        'organization_center',
        'organization_department',
        'source',
        'dependents_count',
        'created_at',
        'approved_at',
    ];

    public static function query(Request $request, ?array $columnFilters = null): Builder
    {
        $query = Employee::query()
            ->with(['organizationUnit.parent.parent', 'approvedBy', 'dependents'])
            ->withCount('dependents');

        $filters = self::filtersFromRequest($request, $columnFilters);

        foreach (['status', 'gender', 'marital_status', 'source'] as $column) {
            if (! empty($filters[$column])) {
                $query->whereIn($column, self::normalizeMappedValues($column, $filters[$column]));
            }
        }

        if (! empty($filters['organization_center'])) {
            $query->whereIn('organization_unit_id', self::sectionIdsUnderAncestors(1, $filters['organization_center']));
        }

        if (! empty($filters['organization_department'])) {
            $query->whereIn('organization_unit_id', self::sectionIdsUnderAncestors(2, $filters['organization_department']));
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        if (! empty($filters['approved_from'])) {
            $query->whereDate('approved_at', '>=', $filters['approved_from']);
        }

        if (! empty($filters['approved_to'])) {
            $query->whereDate('approved_at', '<=', $filters['approved_to']);
        }

        if (! empty($filters['dependents_count'])) {
            $counts = collect($filters['dependents_count'])
                ->filter(fn ($value) => is_numeric($value))
                ->map(fn ($value) => (int) $value)
                ->values()
                ->all();

            if ($counts !== []) {
                $query->havingRaw('dependents_count in ('.implode(',', array_fill(0, count($counts), '?')).')', $counts);
            }
        }

        return $query;
    }

    public static function rows(Request $request): Collection
    {
        return self::query($request)
            ->orderBy('id')
            ->get()
            ->map(fn (Employee $employee) => self::row($employee));
    }

    public static function headings(): array
    {
        return [
            'المعرف',
            'الاسم الكامل',
            'رقم الهوية',
            'الحالة',
            'الجنس',
            'الحالة الاجتماعية',
            'المركزية',
            'الدائرة',
            'القسم',
            'مصدر التسجيل',
            'عدد التابعين',
            'تفاصيل التابعين',
            'اعتُمد بواسطة',
            'تاريخ الاعتماد',
            'تاريخ الإنشاء',
        ];
    }

    public static function summary(Request $request): array
    {
        $employees = self::query($request)
            ->get(['id', 'status']);

        return [
            'total_employees' => $employees->count(),
            'active_count' => $employees->where('status', 'active')->count(),
            'pending_count' => $employees->where('status', 'pending')->count(),
            'total_dependents' => $employees->sum('dependents_count'),
        ];
    }

    public static function filterOptions(string $column, Request $request): Collection
    {
        $query = self::query($request, (array) $request->input('active_filters', []));

        if ($column === 'created_at' || $column === 'approved_at') {
            return (clone $query)
                ->get()
                ->pluck($column)
                ->filter()
                ->map(fn ($value) => Carbon::parse($value)->format('Y-m-d'))
                ->unique()
                ->sortDesc()
                ->values();
        }

        if ($column === 'organization_center' || $column === 'organization_department') {
            $level = $column === 'organization_center' ? 1 : 2;
            $sectionIds = (clone $query)->get()->pluck('organization_unit_id')->filter()->unique();

            return OrganizationUnit::where('level', $level)
                ->when($level === 1, fn ($q) => $q->whereHas('children.children', fn ($qq) => $qq->whereIn('id', $sectionIds)))
                ->when($level === 2, fn ($q) => $q->whereHas('children', fn ($qq) => $qq->whereIn('id', $sectionIds)))
                ->distinct()
                ->pluck('name')
                ->filter()
                ->values();
        }

        if ($column === 'dependents_count') {
            return (clone $query)
                ->get()
                ->pluck('dependents_count')
                ->unique()
                ->sort()
                ->values();
        }

        $labels = match ($column) {
            'status' => self::STATUS_LABELS,
            'gender' => self::GENDER_LABELS,
            'marital_status' => self::MARITAL_STATUS_LABELS,
            'source' => self::SOURCE_LABELS,
            default => [],
        };

        return (clone $query)
            ->get()
            ->pluck($column)
            ->filter()
            ->unique()
            ->values()
            ->map(fn ($value) => $labels[$value] ?? $value);
    }

    public static function filtersDescription(Request $request): string
    {
        $filters = self::filtersFromRequest($request);
        $parts = [];

        foreach (['status', 'gender', 'marital_status', 'source'] as $column) {
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

        if (! empty($filters['dependents_count'])) {
            $parts[] = 'عدد التابعين: '.implode('، ', $filters['dependents_count']);
        }

        if (! empty($filters['created_from']) || ! empty($filters['created_to'])) {
            $parts[] = 'تاريخ الإنشاء: من '.($filters['created_from'] ?: '-').' إلى '.($filters['created_to'] ?: '-');
        }

        if (! empty($filters['approved_from']) || ! empty($filters['approved_to'])) {
            $parts[] = 'تاريخ الاعتماد: من '.($filters['approved_from'] ?: '-').' إلى '.($filters['approved_to'] ?: '-');
        }

        return $parts === [] ? 'بدون فلاتر' : implode(' | ', $parts);
    }

    private static function row(Employee $employee): array
    {
        $chain = $employee->organizationUnit?->ancestryChain() ?? [];

        return [
            'id' => $employee->id,
            'full_name' => $employee->full_name,
            'national_id' => $employee->national_id,
            'status' => self::STATUS_LABELS[$employee->status] ?? $employee->status,
            'gender' => self::GENDER_LABELS[$employee->gender] ?? $employee->gender,
            'marital_status' => self::MARITAL_STATUS_LABELS[$employee->marital_status] ?? $employee->marital_status,
            'center' => $chain['center']->name ?? '',
            'department' => $chain['department']->name ?? '',
            'section' => $chain['section']->name ?? '',
            'source' => self::SOURCE_LABELS[$employee->source] ?? $employee->source,
            'dependents_count' => $employee->dependents_count,
            'dependents_detail' => self::dependentsDetail($employee),
            'approved_by' => $employee->approvedBy->name ?? '',
            'approved_at' => $employee->approved_at ? Carbon::parse($employee->approved_at)->format('Y-m-d H:i') : '',
            'created_at' => $employee->created_at?->format('Y-m-d H:i') ?? '',
        ];
    }

    public static function dependentsDetail(Employee $employee): string
    {
        return $employee->dependents->map(function ($dependent) {
            $typeLabel = self::DEPENDENT_TYPE_LABELS[$dependent->type] ?? $dependent->type;

            if ($dependent->type === 'parent' && $dependent->parent_type) {
                $typeLabel = self::PARENT_TYPE_LABELS[$dependent->parent_type] ?? $typeLabel;
            }

            return "{$typeLabel}: {$dependent->full_name} ({$dependent->national_id})";
        })->implode(' | ') ?: '-';
    }

    private static function filtersFromRequest(Request $request, ?array $columnFilters = null): array
    {
        $columnFilters ??= (array) $request->input('column_filters', []);

        return [
            'status' => self::listFilter($request, $columnFilters, 'status'),
            'gender' => self::listFilter($request, $columnFilters, 'gender'),
            'marital_status' => self::listFilter($request, $columnFilters, 'marital_status'),
            'organization_center' => self::listFilter($request, $columnFilters, 'organization_center'),
            'organization_department' => self::listFilter($request, $columnFilters, 'organization_department'),
            'source' => self::listFilter($request, $columnFilters, 'source'),
            'dependents_count' => self::listFilter($request, $columnFilters, 'dependents_count'),
            'created_from' => $columnFilters['created_at']['from'] ?? $request->input('created_from') ?? $request->input('from_date'),
            'created_to' => $columnFilters['created_at']['to'] ?? $request->input('created_to') ?? $request->input('to_date'),
            'approved_from' => $columnFilters['approved_at']['from'] ?? $request->input('approved_from'),
            'approved_to' => $columnFilters['approved_at']['to'] ?? $request->input('approved_to'),
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
            'status' => array_flip(self::STATUS_LABELS),
            'gender' => array_flip(self::GENDER_LABELS),
            'marital_status' => array_flip(self::MARITAL_STATUS_LABELS),
            'source' => array_flip(self::SOURCE_LABELS),
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
            'status' => self::STATUS_LABELS,
            'gender' => self::GENDER_LABELS,
            'marital_status' => self::MARITAL_STATUS_LABELS,
            'source' => self::SOURCE_LABELS,
            default => [],
        };

        return collect($normalized)
            ->map(fn ($value) => $labels[$value] ?? $value)
            ->all();
    }

    private static function filterLabel(string $column): string
    {
        return match ($column) {
            'status' => 'الحالة',
            'gender' => 'الجنس',
            'marital_status' => 'الحالة الاجتماعية',
            'source' => 'مصدر التسجيل',
            default => $column,
        };
    }

    /**
     * @return array<int>
     */
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
