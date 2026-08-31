<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\EmployeesExport;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\OrganizationUnit;
use App\Models\SurveySubmission;
use App\Rules\UniqueNationalId;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    private const STATUS_LABELS = [
        'pending' => 'قيد الموافقة',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
    ];

    private const MARITAL_STATUS_LABELS = [
        'single' => 'أعزب/عزباء',
        'married' => 'متزوج/ة',
        'polygamous' => 'متعدد الزوجات',
        'widowed' => 'أرمل/ة',
        'divorced' => 'مطلق/ة',
    ];

    /** Columns filterable via the header dropdown checkbox-list. */
    private const FILTERABLE_COLUMNS = ['full_name', 'national_id', 'gender', 'marital_status', 'status', 'organization_unit_name', 'organization_center', 'organization_department', 'dependents_count'];

    public function index(Request $request)
    {
        $this->authorize('view', Employee::class);

        if ($request->ajax()) {
            $query = Employee::query()->with('organizationUnit.parent.parent')->withCount('dependents');
            $this->applyColumnFilters($query, $request);
            $this->applySort($query, $request);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('organization_unit_name', fn (Employee $employee) => $employee->organizationUnit?->name ?? '-')
                ->addColumn('organization_center', function (Employee $employee) {
                    return $employee->organizationUnit?->ancestryChain()['center']?->name ?? '-';
                })
                ->addColumn('organization_department', function (Employee $employee) {
                    return $employee->organizationUnit?->ancestryChain()['department']?->name ?? '-';
                })
                ->addColumn('organization_section', function (Employee $employee) {
                    return $employee->organizationUnit?->ancestryChain()['section']?->name ?? '-';
                })
                ->addColumn('status_label', fn (Employee $employee) => self::STATUS_LABELS[$employee->status] ?? $employee->status)
                ->addColumn('marital_status_label', fn (Employee $employee) => self::MARITAL_STATUS_LABELS[$employee->marital_status] ?? $employee->marital_status)
                ->addColumn('edit', fn (Employee $employee) => $employee->id)
                ->addColumn('delete', fn (Employee $employee) => $employee->id)
                ->rawColumns(['edit', 'delete'])
                ->make(true);
        }

        $organizationUnits = OrganizationUnit::orderBy('name')->get();

        return view('dashboard.employees.index', compact('organizationUnits'));
    }

    public function export()
    {
        $this->authorize('view', Employee::class);

        ActivityLogService::log('Exported', 'Employee', 'تم تصدير جدول الموظفين إلى Excel.', null, null);

        return Excel::download(new EmployeesExport(), 'employees_export_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    private function applyColumnFilters($query, Request $request): void
    {
        $filters = (array) $request->input('column_filters', []);

        foreach ($filters as $column => $values) {
            if (! in_array($column, self::FILTERABLE_COLUMNS, true) || empty($values)) {
                continue;
            }

            if ($column === 'organization_unit_name') {
                $query->whereHas('organizationUnit', fn ($q) => $q->whereIn('name', (array) $values));
                continue;
            }

            if ($column === 'organization_center') {
                $sectionIds = $this->sectionIdsUnderAncestors(1, (array) $values);
                $query->whereIn('organization_unit_id', $sectionIds);
                continue;
            }

            if ($column === 'organization_department') {
                $sectionIds = $this->sectionIdsUnderAncestors(2, (array) $values);
                $query->whereIn('organization_unit_id', $sectionIds);
                continue;
            }

            if ($column === 'dependents_count') {
                $query->havingRaw('dependents_count in ('.implode(',', array_fill(0, count((array) $values), '?')).')', (array) $values);
                continue;
            }

            $query->whereIn($column, (array) $values);
        }
    }

    private function applySort($query, Request $request): void
    {
        $sortColumn = $request->input('sort_column');
        $sortDirection = $request->input('sort_direction', 'asc');

        if (! in_array($sortColumn, ['full_name', 'national_id', 'gender', 'marital_status', 'status'], true)) {
            return;
        }

        $query->orderBy($sortColumn, $sortDirection === 'desc' ? 'desc' : 'asc');
    }

    /**
     * Resolve level-3 (section) organization unit ids that fall under any level-1/level-2
     * ancestor unit matching the given names, for filtering employees by center/department.
     *
     * @return array<int>
     */
    private function sectionIdsUnderAncestors(int $ancestorLevel, array $ancestorNames): array
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

    public function getFilterOptions(string $column): JsonResponse
    {
        $this->authorize('view', Employee::class);

        abort_unless(in_array($column, self::FILTERABLE_COLUMNS, true), 404);

        if ($column === 'organization_unit_name') {
            $values = OrganizationUnit::query()
                ->whereHas('employees')
                ->distinct()
                ->pluck('name');

            return response()->json($values);
        }

        if (in_array($column, ['organization_center', 'organization_department'], true)) {
            $level = $column === 'organization_center' ? 1 : 2;
            $sectionIds = Employee::query()->distinct()->pluck('organization_unit_id');

            $values = OrganizationUnit::where('level', $level)
                ->when($level === 1, fn ($q) => $q->whereHas('children.children', fn ($qq) => $qq->whereIn('id', $sectionIds)))
                ->when($level === 2, fn ($q) => $q->whereHas('children', fn ($qq) => $qq->whereIn('id', $sectionIds)))
                ->distinct()
                ->pluck('name');

            return response()->json($values);
        }

        if ($column === 'dependents_count') {
            $values = Employee::query()->withCount('dependents')->get()
                ->pluck('dependents_count')->unique()->sort()->values();

            return response()->json($values);
        }

        $labels = match ($column) {
            'status' => self::STATUS_LABELS,
            'marital_status' => self::MARITAL_STATUS_LABELS,
            'gender' => ['male' => 'ذكر', 'female' => 'أنثى'],
            default => [],
        };

        $values = Employee::query()->distinct()->pluck($column)->filter()->values()
            ->map(fn ($value) => $labels[$value] ?? $value);

        return response()->json($values);
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        $organizationUnits = OrganizationUnit::query()
            ->whereNull('parent_id')
            ->with([
                'children' => fn ($query) => $query->orderBy('name'),
                'children.children' => fn ($query) => $query->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();

        return view('dashboard.employees.create', compact('organizationUnits'));
    }

    public function show(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load(['organizationUnit.parent.parent', 'dependents']);

        return view('dashboard.employees.show', compact('employee'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Employee::class);

        $validated = $this->validateEmployee($request);
        $dependents = $validated['dependents'] ?? [];
        unset($validated['dependents']);

        $this->assertCreateDependentsRules($validated, $dependents);
        $this->assertCreateNationalIdsAvailable($validated, $dependents);

        $validated['source'] = 'admin';
        $validated['status'] = $validated['status'] ?? 'active';

        $employee = DB::transaction(function () use ($validated, $dependents) {
            $employee = Employee::create($validated);

            foreach ($dependents as $dependent) {
                if ($dependent['type'] !== 'parent') {
                    $dependent['parent_type'] = null;
                }

                $employee->dependents()->create($dependent);
            }

            return $employee;
        });

        ActivityLogService::log(
            'Created',
            'Employee',
            "تم إضافة موظف: {$employee->full_name}.",
            null,
            $employee->toArray()
        );

        return redirect()->route('dashboard.employees.edit', $employee)->with('success', 'تم إضافة الموظف.');
    }

    public function checkNationalId(Request $request, string $nationalId): JsonResponse
    {
        $this->authorize('create', Employee::class);

        $exists = false;
        $rule = new UniqueNationalId();
        $rule->validate('national_id', $nationalId, function () use (&$exists) {
            $exists = true;
        });

        if (! $exists) {
            $exists = SurveySubmission::where('national_id', $nationalId)->where('status', 'pending')->exists();
        }

        return response()->json(['exists' => $exists]);
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        $employee->load('dependents');
        $organizationUnits = OrganizationUnit::query()
            ->whereNull('parent_id')
            ->with([
                'children' => fn ($query) => $query->orderBy('name'),
                'children.children' => fn ($query) => $query->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();

        return view('dashboard.employees.edit', compact('employee', 'organizationUnits'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $validated = $this->validateEmployee($request, $employee);

        $old = $employee->toArray();
        $employee->update($validated);

        ActivityLogService::log(
            'Updated',
            'Employee',
            "تم تعديل بيانات الموظف: {$employee->full_name}.",
            $old,
            $employee->getChanges()
        );

        return redirect()->route('dashboard.employees.edit', $employee)->with('success', 'تم تعديل بيانات الموظف.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $old = $employee->toArray();

        try {
            $employee->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('dashboard.employees.index')
                ->with('danger', 'لا يمكن حذف موظف له زيارات مسجّلة.');
        }

        ActivityLogService::log(
            'Deleted',
            'Employee',
            "تم حذف الموظف: {$old['full_name']}.",
            $old,
            null
        );

        return redirect()->route('dashboard.employees.index')->with('success', 'تم حذف الموظف.');
    }

    /** @return array<string, mixed> */
    private function validateEmployee(Request $request, ?Employee $employee = null): array
    {
        $rules = [
            'full_name' => 'required|string|max:255',
            'national_id' => [
                'required',
                'string',
                'size:9',
                new UniqueNationalId('employees', $employee?->id),
            ],
            'gender' => 'required|in:male,female',
            'marital_status' => 'required|in:single,married,polygamous,widowed,divorced',
            'organization_unit_id' => 'required|exists:organization_units,id',
            'status' => 'nullable|in:pending,active,inactive',
        ];

        if ($employee === null) {
            $rules += [
                'dependents' => 'array',
                'dependents.*.type' => 'required_with:dependents|in:spouse,child,parent',
                'dependents.*.full_name' => 'required_with:dependents|string|max:255',
                'dependents.*.national_id' => [
                    'required_with:dependents',
                    'string',
                    'size:9',
                    'distinct',
                    new UniqueNationalId('dependents'),
                ],
                'dependents.*.gender' => 'required_with:dependents|in:male,female',
                'dependents.*.parent_type' => 'nullable|in:father,mother',
            ];
        }

        return $request->validate($rules);
    }

    /** @param array<string, mixed> $employee */
    private function assertCreateNationalIdsAvailable(array $employee, array $dependents): void
    {
        if (SurveySubmission::where('national_id', $employee['national_id'])->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'national_id' => 'رقم الهوية مستخدم مسبقاً في طلب استبيان قيد المراجعة.',
            ]);
        }

        foreach ($dependents as $index => $dependent) {
            if (SurveySubmission::where('national_id', $dependent['national_id'])->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    "dependents.{$index}.national_id" => 'رقم الهوية مستخدم مسبقاً في طلب استبيان قيد المراجعة.',
                ]);
            }
        }
    }

    /** @param array<string, mixed> $employee */
    private function assertCreateDependentsRules(array $employee, array $dependents): void
    {
        $spouseCount = 0;
        $parentTypes = [];
        $seenNationalIds = [$employee['national_id'] => true];

        if ($employee['gender'] === 'female' && $employee['marital_status'] === 'polygamous') {
            throw ValidationException::withMessages([
                'marital_status' => 'لا يمكن اختيار "متعدد الزوجات" لموظفة أنثى.',
            ]);
        }

        foreach ($dependents as $index => $dependent) {
            if (isset($seenNationalIds[$dependent['national_id']])) {
                throw ValidationException::withMessages([
                    "dependents.{$index}.national_id" => 'رقم الهوية مستخدم مسبقاً في نفس الطلب.',
                ]);
            }

            $seenNationalIds[$dependent['national_id']] = true;

            if ($dependent['type'] === 'spouse') {
                $spouseCount++;

                if ($dependent['gender'] === ($employee['gender'] === 'male' ? 'male' : 'female')) {
                    throw ValidationException::withMessages([
                        "dependents.{$index}.gender" => 'جنس الزوج/ة يجب أن يخالف جنس الموظف.',
                    ]);
                }
            }

            if ($dependent['type'] === 'parent') {
                if (empty($dependent['parent_type'])) {
                    throw ValidationException::withMessages([
                        "dependents.{$index}.parent_type" => 'يجب تحديد نوع الوالد (أب/أم).',
                    ]);
                }

                if (isset($parentTypes[$dependent['parent_type']])) {
                    throw ValidationException::withMessages([
                        "dependents.{$index}.parent_type" => 'يوجد بالفعل سجل لهذا الوالد.',
                    ]);
                }

                $parentTypes[$dependent['parent_type']] = true;
            }
        }

        $maxSpouses = $employee['gender'] === 'male' && $employee['marital_status'] === 'polygamous' ? 4 : 1;

        if ($spouseCount > $maxSpouses) {
            $message = $maxSpouses === 4
                ? 'لا يمكن إضافة أكثر من 4 زوجات.'
                : 'لا يمكن إضافة أكثر من زوج/ة واحدة.';

            throw ValidationException::withMessages([
                'dependents' => $message,
            ]);
        }
    }
}

