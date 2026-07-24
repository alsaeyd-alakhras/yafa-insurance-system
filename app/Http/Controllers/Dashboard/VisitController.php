<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Dependent;
use App\Models\Employee;
use App\Models\MedicalDepartment;
use App\Models\Visit;
use App\Models\VisitDepartment;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class VisitController extends Controller
{
    /** Columns filterable via the header dropdown checkbox-list. */
    private const FILTERABLE_COLUMNS = ['patient_name', 'employee_name', 'clinic_name'];

    public function index(Request $request)
    {
        $this->authorize('viewAny', Visit::class);

        if ($request->ajax()) {
            $query = Visit::query()->with(['patientEmployee', 'patientDependent', 'clinic', 'employee', 'recordedBy', 'visitDepartments']);
            $hasDateFilter = $this->applyColumnFilters($query, $request);

            if (! $hasDateFilter) {
                $query->whereDate('visit_date', now()->toDateString());
            }

            $this->applySort($query, $request);

            return DataTables::of($query)
                ->addColumn('patient_name', fn (Visit $visit) => $visit->patientEmployee?->full_name ?? $visit->patientDependent?->full_name ?? '-')
                ->addColumn('employee_name', fn (Visit $visit) => $visit->employee?->full_name ?? '-')
                ->addColumn('clinic_name', fn (Visit $visit) => $visit->clinic?->name ?? '-')
                ->addColumn('departments_list', fn (Visit $visit) => $visit->visitDepartments->pluck('medicalDepartment.name')->filter()->implode('، ') ?: '-')
                ->addColumn('total_before_discount', fn (Visit $visit) => $visit->total_before_discount !== null ? number_format((float) $visit->total_before_discount, 2) : '-')
                ->addColumn('total_after_discount', fn (Visit $visit) => $visit->total_after_discount !== null ? number_format((float) $visit->total_after_discount, 2) : '-')
                ->addColumn('recorded_by_name', fn (Visit $visit) => $visit->recordedBy?->name ?? '-')
                ->addColumn('edit', fn (Visit $visit) => $visit->id)
                ->addColumn('delete', fn (Visit $visit) => auth()->user()->can('delete', $visit) ? $visit->id : null)
                ->rawColumns(['edit', 'delete'])
                ->make(true);
        }

        return view('dashboard.visits.index');
    }

    private function applyColumnFilters($query, Request $request): bool
    {
        $filters = (array) $request->input('column_filters', []);
        $hasDateFilter = false;

        foreach ($filters as $column => $values) {
            if ($column === 'visit_date' && is_array($values)) {
                if (! empty($values['from'])) {
                    $query->whereDate('visit_date', '>=', $values['from']);
                    $hasDateFilter = true;
                }
                if (! empty($values['to'])) {
                    $query->whereDate('visit_date', '<=', $values['to']);
                    $hasDateFilter = true;
                }
                continue;
            }

            if (! in_array($column, self::FILTERABLE_COLUMNS, true) || empty($values)) {
                continue;
            }

            if ($column === 'clinic_name') {
                $query->whereHas('clinic', fn ($q) => $q->whereIn('name', (array) $values));
            }

            if ($column === 'patient_name') {
                $query->where(function ($q) use ($values) {
                    $q->whereHas('patientEmployee', fn ($qq) => $qq->whereIn('full_name', (array) $values))
                        ->orWhereHas('patientDependent', fn ($qq) => $qq->whereIn('full_name', (array) $values));
                });
            }

            if ($column === 'employee_name') {
                $query->whereHas('employee', fn ($q) => $q->whereIn('full_name', (array) $values));
            }
        }

        return $hasDateFilter;
    }

    private function applySort($query, Request $request): void
    {
        $sortColumn = $request->input('sort_column');
        $sortDirection = $request->input('sort_direction', 'asc');

        if (! in_array($sortColumn, ['visit_date'], true)) {
            return;
        }

        $query->orderBy($sortColumn, $sortDirection === 'desc' ? 'desc' : 'asc');
    }

    public function getFilterOptions(string $column): JsonResponse
    {
        $this->authorize('viewAny', Visit::class);

        abort_unless(in_array($column, self::FILTERABLE_COLUMNS, true), 404);

        $values = match ($column) {
            'clinic_name' => Clinic::query()->whereHas('visits')->distinct()->pluck('name'),
            'patient_name' => Employee::query()->whereHas('visitsAsPatient')->pluck('full_name')
                ->merge(Dependent::query()->whereHas('visitsAsPatient')->pluck('full_name'))
                ->unique()
                ->values(),
            'employee_name' => Employee::query()->whereHas('visits')->distinct()->pluck('full_name'),
            default => collect(),
        };

        return response()->json($values);
    }

    public function searchPatients(Request $request): JsonResponse
    {
        $this->authorize('create', Visit::class);

        $term = trim((string) $request->input('term', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $employees = Employee::query()
            ->where(fn ($q) => $q->where('full_name', 'like', "%{$term}%")->orWhere('national_id', 'like', "%{$term}%"))
            ->limit(10)
            ->get()
            ->map(fn (Employee $employee) => [
                'type' => 'employee',
                'national_id' => $employee->national_id,
                'full_name' => $employee->full_name,
                'label' => "{$employee->full_name} — {$employee->national_id} (موظف)",
            ]);

        $dependents = Dependent::query()
            ->with('employee')
            ->where(fn ($q) => $q->where('full_name', 'like', "%{$term}%")->orWhere('national_id', 'like', "%{$term}%"))
            ->limit(10)
            ->get()
            ->map(fn (Dependent $dependent) => [
                'type' => 'dependent',
                'national_id' => $dependent->national_id,
                'full_name' => $dependent->full_name,
                'label' => "{$dependent->full_name} — {$dependent->national_id} (تابع لـ: {$dependent->employee?->full_name})",
            ]);

        return response()->json($employees->concat($dependents)->take(15)->values());
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorize('create', Visit::class);

        $validated = $request->validate([
            'national_id' => 'required|string|size:9',
            'clinic_id' => 'nullable|exists:clinics,id',
        ]);

        $employee = Employee::where('national_id', $validated['national_id'])->first();
        $dependent = $employee ? null : Dependent::where('national_id', $validated['national_id'])->first();

        if (! $employee && ! $dependent) {
            return response()->json(['message' => 'لا يوجد موظف أو تابع بهذا الرقم.'], 404);
        }

        $patientEmployeeId = $employee?->id;
        $patientDependentId = $dependent?->id;
        $quotaOwner = $employee ?? $dependent->employee;
        $clinicId = $validated['clinic_id'] ?? null;

        $existingVisit = Visit::query()
            ->when($patientEmployeeId, fn ($q) => $q->where('patient_employee_id', $patientEmployeeId))
            ->when($patientDependentId, fn ($q) => $q->where('patient_dependent_id', $patientDependentId))
            ->whereDate('visit_date', now()->toDateString())
            ->where('clinic_id', $clinicId)
            ->first();

        if ($existingVisit) {
            return response()->json([
                'existing_visit_id' => $existingVisit->id,
                'redirect' => route('dashboard.visits.edit', $existingVisit),
            ]);
        }

        return response()->json([
            'patient_type' => $employee ? 'employee' : 'dependent',
            'patient_id' => $employee?->id ?? $dependent?->id,
            'patient_name' => $employee?->full_name ?? $dependent?->full_name,
            'quota_owner_employee_id' => $quotaOwner->id,
            'remaining_quota' => $quotaOwner->remainingQuota(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Visit::class);

        $validated = $request->validate([
            'national_id' => 'required|string|size:9',
            'clinic_id' => 'nullable|exists:clinics,id',
        ]);

        $employee = Employee::where('national_id', $validated['national_id'])->first();
        $dependent = $employee ? null : Dependent::where('national_id', $validated['national_id'])->first();

        abort_unless($employee || $dependent, 404, 'لا يوجد موظف أو تابع بهذا الرقم.');

        $quotaOwner = $employee ?? $dependent->employee;
        $clinicId = $validated['clinic_id'] ?? null;

        $existingVisit = Visit::query()
            ->when($employee, fn ($q) => $q->where('patient_employee_id', $employee->id))
            ->when($dependent, fn ($q) => $q->where('patient_dependent_id', $dependent->id))
            ->whereDate('visit_date', now()->toDateString())
            ->where('clinic_id', $clinicId)
            ->first();

        if ($existingVisit) {
            return redirect()->route('dashboard.visits.edit', $existingVisit)
                ->with('info', 'توجد زيارة مسجّلة لهذا المريض اليوم — تمت إضافتك لها.');
        }

        if ($quotaOwner->remainingQuota() <= 0) {
            return redirect()->route('dashboard.visits.index')
                ->with('danger', 'انتهت زيارات هذا الشهر لهذا الموظف.');
        }

        $visit = Visit::create([
            'employee_id' => $quotaOwner->id,
            'patient_employee_id' => $employee?->id,
            'patient_dependent_id' => $dependent?->id,
            'clinic_id' => $clinicId,
            'visit_date' => now()->toDateString(),
            'recorded_by' => auth()->id(),
        ]);

        $patientName = $employee?->full_name ?? $dependent?->full_name;

        ActivityLogService::log(
            'Created',
            'Visit',
            "تم تسجيل زيارة جديدة لـ: {$patientName}.",
            null,
            $visit->toArray()
        );

        return redirect()->route('dashboard.visits.edit', $visit)->with('success', 'تم تسجيل الزيارة.');
    }

    public function edit(Visit $visit): View
    {
        $this->authorize('update', $visit);

        $visit->load(['patientEmployee', 'patientDependent', 'employee', 'clinic', 'visitDepartments.medicalDepartment']);

        $medicalDepartments = MedicalDepartment::where('is_active', true)
            ->whereNotIn('id', $visit->visitDepartments->pluck('medical_department_id'))
            ->get();

        return view('dashboard.visits.edit', compact('visit', 'medicalDepartments'));
    }

    public function addDepartment(Request $request, Visit $visit): RedirectResponse
    {
        $this->authorize('update', $visit);

        $validated = $request->validate([
            'medical_department_id' => 'required|exists:medical_departments,id',
            'amount_before_discount' => 'nullable|numeric|min:0',
        ]);

        $department = MedicalDepartment::findOrFail($validated['medical_department_id']);

        if ($visit->visitDepartments()->where('medical_department_id', $department->id)->exists()) {
            return redirect()->route('dashboard.visits.edit', $visit)
                ->with('danger', 'هذا القسم مُضاف مسبقاً لهذه الزيارة.');
        }

        $visitDepartment = VisitDepartment::create([
            'visit_id' => $visit->id,
            'medical_department_id' => $department->id,
            'applied_discount_percentage' => $department->discount_percentage,
            'applied_max_discount_amount' => $department->max_discount_amount,
            'amount_before_discount' => $validated['amount_before_discount'] ?? null,
            'amount_after_discount' => null,
            'added_at' => now(),
            'added_by' => auth()->id(),
        ]);

        if ($visitDepartment->amount_before_discount !== null) {
            $visitDepartment->update(['amount_after_discount' => $visitDepartment->calculateAmountAfterDiscount()]);
        }

        $visit->recalculateTotals();
        $visit->update(['last_updated_by' => auth()->id()]);

        ActivityLogService::log(
            'Updated',
            'Visit',
            "تم إضافة قسم {$department->name} لزيارة #{$visit->id}.",
            null,
            $visitDepartment->toArray()
        );

        return redirect()->route('dashboard.visits.edit', $visit)->with('success', 'تم إضافة القسم للزيارة.');
    }

    public function updateDepartmentAmount(Request $request, Visit $visit, VisitDepartment $visitDepartment): RedirectResponse
    {
        $this->authorize('update', $visit);
        abort_unless($visitDepartment->visit_id === $visit->id, 404);

        $validated = $request->validate([
            'amount_before_discount' => 'nullable|numeric|min:0',
        ]);

        $old = $visitDepartment->toArray();
        $visitDepartment->amount_before_discount = $validated['amount_before_discount'] ?? null;
        $visitDepartment->amount_after_discount = $visitDepartment->calculateAmountAfterDiscount();
        $visitDepartment->save();

        $visit->recalculateTotals();
        $visit->update(['last_updated_by' => auth()->id()]);

        ActivityLogService::log(
            'Updated',
            'Visit',
            "تم تعديل مبلغ قسم بزيارة #{$visit->id}.",
            $old,
            $visitDepartment->getChanges()
        );

        return redirect()->route('dashboard.visits.edit', $visit)->with('success', 'تم تعديل المبلغ.');
    }

    public function destroy(Visit $visit): RedirectResponse
    {
        $this->authorize('delete', $visit);

        $old = $visit->toArray();
        $patientName = $visit->patientEmployee?->full_name ?? $visit->patientDependent?->full_name;

        DB::transaction(function () use ($visit) {
            $visit->visitDepartments()->delete();
            $visit->delete();
        });

        ActivityLogService::log(
            'Deleted',
            'Visit',
            "تم حذف زيارة #{$old['id']} لـ: {$patientName}.",
            $old,
            null
        );

        return redirect()->route('dashboard.visits.index')->with('success', 'تم حذف الزيارة.');
    }
}
