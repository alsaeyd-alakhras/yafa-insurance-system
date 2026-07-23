<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\OrganizationUnit;
use App\Rules\UniqueNationalId;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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
    private const FILTERABLE_COLUMNS = ['gender', 'marital_status', 'status', 'organization_unit_name'];

    public function index(Request $request)
    {
        $this->authorize('view', Employee::class);

        if ($request->ajax()) {
            $query = Employee::query()->with('organizationUnit');
            $this->applyColumnFilters($query, $request);
            $this->applySort($query, $request);

            return DataTables::of($query)
                ->addColumn('organization_unit_name', fn (Employee $employee) => $employee->organizationUnit?->name ?? '-')
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

        $organizationUnits = OrganizationUnit::orderBy('name')->get();

        return view('dashboard.employees.create', compact('organizationUnits'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Employee::class);

        $validated = $this->validateEmployee($request);
        $validated['source'] = 'admin';
        $validated['status'] = $validated['status'] ?? 'active';

        $employee = Employee::create($validated);

        ActivityLogService::log(
            'Created',
            'Employee',
            "تم إضافة موظف: {$employee->full_name}.",
            null,
            $employee->toArray()
        );

        return redirect()->route('dashboard.employees.edit', $employee)->with('success', 'تم إضافة الموظف.');
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        $employee->load('dependents');
        $organizationUnits = OrganizationUnit::orderBy('name')->get();

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
        return $request->validate([
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
        ]);
    }
}
