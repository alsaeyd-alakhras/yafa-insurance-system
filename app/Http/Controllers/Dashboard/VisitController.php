<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Dependent;
use App\Models\Employee;
use App\Models\MedicalDepartment;
use App\Models\User;
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
    private const FILTERABLE_COLUMNS = ['patient_name', 'employee_name', 'clinic_name', 'departments_list', 'recorded_by_name'];

    /** Arabic labels for the fixed medical department slugs. */
    private const DEPARTMENT_LABELS = [
        'clinics' => 'الكشف الطبي',
        'pharmacy' => 'الصيدلية',
        'laboratory' => 'المختبر',
        'optics' => 'البصريات',
        'dental' => 'الأسنان',
        'radiology' => 'الأشعة',
    ];

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
                ->addIndexColumn()
                ->addColumn('patient_name', fn (Visit $visit) => $visit->patientEmployee?->full_name ?? $visit->patientDependent?->full_name ?? '-')
                ->addColumn('employee_name', fn (Visit $visit) => $visit->employee?->full_name ?? '-')
                ->addColumn('clinic_name', fn (Visit $visit) => $visit->clinic?->name ?? '-')
                ->addColumn('departments_list', fn (Visit $visit) => $visit->visitDepartments->pluck('medicalDepartment.name')->filter()->map(fn ($name) => self::DEPARTMENT_LABELS[$name] ?? $name)->implode('، ') ?: '-')
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

            if ($column === 'departments_list') {
                $labelToName = array_flip(self::DEPARTMENT_LABELS);
                $names = collect((array) $values)->map(fn ($label) => $labelToName[$label] ?? $label);
                $query->whereHas('visitDepartments.medicalDepartment', fn ($q) => $q->whereIn('name', $names));
            }

            if ($column === 'recorded_by_name') {
                $query->whereHas('recordedBy', fn ($q) => $q->whereIn('name', (array) $values));
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
            'departments_list' => MedicalDepartment::query()
                ->whereIn('id', VisitDepartment::query()->distinct()->pluck('medical_department_id'))
                ->pluck('name')
                ->map(fn ($name) => self::DEPARTMENT_LABELS[$name] ?? $name)
                ->unique()
                ->values(),
            'recorded_by_name' => User::query()
                ->whereIn('id', Visit::query()->whereNotNull('recorded_by')->distinct()->pluck('recorded_by'))
                ->pluck('name'),
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

        $matchingEmployeeIds = Employee::query()
            ->where(fn ($q) => $q->where('full_name', 'like', "%{$term}%")
                ->orWhere('national_id', 'like', "%{$term}%"))
            ->limit(10)
            ->pluck('id');

        $matchingDependentOwnerIds = Dependent::query()
            ->where(fn ($q) => $q->where('full_name', 'like', "%{$term}%")
                ->orWhere('national_id', 'like', "%{$term}%"))
            ->limit(10)
            ->pluck('employee_id');

        $employeeIds = $matchingEmployeeIds->concat($matchingDependentOwnerIds)->unique()->take(10)->values();

        $employees = Employee::query()
            ->with(['dependents' => fn ($query) => $query->orderBy('type')->orderBy('full_name')])
            ->whereIn('id', $employeeIds)
            ->get()
            ->sortBy(fn (Employee $employee) => $employeeIds->search($employee->id))
            ->values()
            ->map(function (Employee $employee) {
                $dependents = $employee->dependents->map(fn (Dependent $dependent) => [
                    'id' => $dependent->id,
                    'type' => 'dependent',
                    'dependent_type' => $dependent->type,
                    'parent_type' => $dependent->parent_type,
                    'gender' => $dependent->gender,
                    'national_id' => $dependent->national_id,
                    'full_name' => $dependent->full_name,
                ]);

                return [
                    'id' => $employee->id,
                    'type' => 'employee',
                    'full_name' => $employee->full_name,
                    'national_id' => $employee->national_id,
                    'dependents' => [
                        'spouses' => $dependents->where('dependent_type', 'spouse')->values(),
                        'children' => $dependents->where('dependent_type', 'child')->values(),
                        'parents' => $dependents->where('dependent_type', 'parent')->values(),
                    ],
                ];
            });

        return response()->json($employees);
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorize('create', Visit::class);

        $validated = $request->validate([
            'national_id' => 'required|string|size:9',
        ]);

        $resolution = $this->resolvePatientVisit($validated['national_id']);

        if (! $resolution) {
            return response()->json(['message' => 'لا يوجد موظف أو تابع بهذا الرقم.'], 404);
        }

        if ($resolution['existing_visit']) {
            return response()->json([
                'existing_visit_id' => $resolution['existing_visit']->id,
                'redirect' => route('dashboard.visits.edit', $resolution['existing_visit']),
            ]);
        }

        return response()->json([
            'patient_type' => $resolution['employee'] ? 'employee' : 'dependent',
            'patient_id' => $resolution['employee']?->id ?? $resolution['dependent']?->id,
            'patient_name' => $resolution['employee']?->full_name ?? $resolution['dependent']?->full_name,
            'quota_owner_employee_id' => $resolution['quota_owner']->id,
            'remaining_quota' => $resolution['remaining_quota'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Visit::class);

        $validated = $request->validate([
            'national_id' => 'required|string|size:9',
            'force_new' => 'nullable|boolean',
        ]);

        $forceNew = (bool) ($validated['force_new'] ?? false);

        $resolution = $this->resolvePatientVisit($validated['national_id']);

        abort_unless($resolution, 404, 'لا يوجد موظف أو تابع بهذا الرقم.');

        $employee = $resolution['employee'];
        $dependent = $resolution['dependent'];
        $quotaOwner = $resolution['quota_owner'];

        if ($resolution['existing_visit'] && ! $forceNew) {
            return redirect()->route('dashboard.visits.edit', $resolution['existing_visit'])
                ->with('info', 'توجد زيارة مسجّلة لهذا المريض اليوم — تمت إضافتك لها.');
        }

        if ($resolution['remaining_quota'] <= 0) {
            return redirect()->route('dashboard.visits.index')
                ->with('danger', 'انتهت زيارات هذا الشهر لهذا الموظف.');
        }

        $visit = Visit::create([
            'employee_id' => $quotaOwner->id,
            'patient_employee_id' => $employee?->id,
            'patient_dependent_id' => $dependent?->id,
            'clinic_id' => null,
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

    public function show(Visit $visit): JsonResponse
    {
        $this->authorize('view', $visit);

        $visit->load(['patientEmployee', 'patientDependent', 'employee', 'clinic', 'recordedBy', 'visitDepartments.medicalDepartment']);

        return response()->json([
            'id' => $visit->id,
            'patient_name' => $visit->patientEmployee?->full_name ?? $visit->patientDependent?->full_name ?? '-',
            'patient_type' => $visit->patient_employee_id ? 'موظف' : 'تابع',
            'employee_name' => $visit->employee?->full_name ?? '-',
            'clinic_name' => $visit->clinic?->name ?? 'بدون عيادة',
            'visit_date' => $visit->visit_date,
            'recorded_by_name' => $visit->recordedBy?->name ?? '-',
            'total_before_discount' => $visit->total_before_discount !== null ? number_format((float) $visit->total_before_discount, 2) : null,
            'total_after_discount' => $visit->total_after_discount !== null ? number_format((float) $visit->total_after_discount, 2) : null,
            'departments' => $visit->visitDepartments->map(fn (VisitDepartment $vd) => [
                'name' => self::DEPARTMENT_LABELS[$vd->medicalDepartment->name] ?? $vd->medicalDepartment->name,
                'discount_percentage' => rtrim(rtrim(number_format($vd->applied_discount_percentage, 2), '0'), '.'),
                'amount_before_discount' => $vd->amount_before_discount !== null ? number_format($vd->amount_before_discount, 2) : null,
                'amount_after_discount' => $vd->amount_after_discount !== null ? number_format($vd->amount_after_discount, 2) : null,
            ])->values(),
            'can_update' => auth()->user()->can('update', $visit),
            'edit_url' => route('dashboard.visits.edit', $visit),
        ]);
    }

    public function edit(Visit $visit): View
    {
        $this->authorize('update', $visit);

        $visit->load(['patientEmployee', 'patientDependent', 'employee', 'clinic', 'visitDepartments.medicalDepartment']);

        $medicalDepartments = MedicalDepartment::where('is_active', true)
            ->whereNotIn('id', $visit->visitDepartments->pluck('medical_department_id'))
            ->get();

        $clinics = Clinic::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.visits.edit', compact('visit', 'medicalDepartments', 'clinics'));
    }

    public function addDepartment(Request $request, Visit $visit): JsonResponse
    {
        $this->authorize('update', $visit);

        $validated = $request->validate([
            'medical_department_id' => 'required|exists:medical_departments,id',
            'amount_before_discount' => 'nullable|numeric|min:0',
            'clinic_id' => 'nullable|exists:clinics,id',
        ]);

        $department = MedicalDepartment::findOrFail($validated['medical_department_id']);
        $request->validate($department->name === 'clinics'
            ? ['clinic_id' => 'required|exists:clinics,id']
            : ['clinic_id' => 'prohibited']);

        if ($visit->visitDepartments()->where('medical_department_id', $department->id)->exists()) {
            return response()->json(['message' => 'هذا القسم مُضاف مسبقاً لهذه الزيارة.'], 422);
        }

        if ($department->name === 'clinics') {
            $visit->loadMissing(['patientEmployee', 'patientDependent']);
            $patientNationalId = $visit->patientEmployee?->national_id ?? $visit->patientDependent?->national_id;
            $resolution = $this->resolvePatientVisit(
                $patientNationalId,
                (int) $validated['clinic_id'],
                $visit->visit_date,
                $visit->id
            );

            if ($resolution && $resolution['existing_visit']) {
                return response()->json([
                    'message' => 'توجد زيارة أخرى لهذا المريض في العيادة المحددة اليوم. يلزم فتح زيارة منفصلة عند اختيار عيادة ثانية، ولا يمكن إضافة الكشف لهذه الزيارة.',
                ], 422);
            }
        }

        $visitDepartment = DB::transaction(function () use ($department, $validated, $visit) {
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

            if ($department->name === 'clinics') {
                $visit->update(['clinic_id' => $validated['clinic_id']]);
            }

            $visit->recalculateTotals();
            $visit->update(['last_updated_by' => auth()->id()]);

            return $visitDepartment;
        });

        ActivityLogService::log(
            'Updated',
            'Visit',
            "تم إضافة قسم {$department->name} لزيارة #{$visit->id}.",
            null,
            $visitDepartment->toArray()
        );

        return response()->json($this->visitPayload($visit, 'تم إضافة القسم للزيارة.'));
    }

    public function updateDepartmentAmount(Request $request, Visit $visit, VisitDepartment $visitDepartment): JsonResponse
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

        return response()->json($this->visitPayload($visit, 'تم تعديل المبلغ.'));
    }

    public function removeDepartment(Visit $visit, VisitDepartment $visitDepartment): JsonResponse
    {
        $this->authorize('update', $visit);
        abort_unless($visitDepartment->visit_id === $visit->id, 404);

        $visitDepartment->load('medicalDepartment');
        $departmentName = $visitDepartment->medicalDepartment->name;
        $old = $visitDepartment->toArray();

        DB::transaction(function () use ($visit, $visitDepartment, $departmentName) {
            $visitDepartment->delete();

            if ($departmentName === 'clinics' && ! $visit->visitDepartments()
                ->whereHas('medicalDepartment', fn ($query) => $query->where('name', 'clinics'))
                ->exists()) {
                $visit->update(['clinic_id' => null]);
            }

            $visit->recalculateTotals();
            $visit->update(['last_updated_by' => auth()->id()]);
        });

        ActivityLogService::log(
            'Updated',
            'Visit',
            "تم حذف قسم {$departmentName} من زيارة #{$visit->id}.",
            $old,
            null
        );

        return response()->json($this->visitPayload($visit, 'تم حذف القسم من الزيارة.'));
    }

    private function resolvePatientVisit(
        string $nationalId,
        ?int $clinicId = null,
        mixed $visitDate = null,
        ?int $excludeVisitId = null
    ): ?array {
        $employee = Employee::where('national_id', $nationalId)->first();
        $dependent = $employee ? null : Dependent::with('employee')->where('national_id', $nationalId)->first();

        if (! $employee && ! $dependent) {
            return null;
        }

        $quotaOwner = $employee ?? $dependent->employee;
        $existingVisit = Visit::query()
            ->when($employee, fn ($query) => $query->where('patient_employee_id', $employee->id))
            ->when($dependent, fn ($query) => $query->where('patient_dependent_id', $dependent->id))
            ->whereDate('visit_date', $visitDate ?? now()->toDateString())
            ->when(
                $clinicId === null,
                fn ($query) => $query->whereNull('clinic_id'),
                fn ($query) => $query->where('clinic_id', $clinicId)
            )
            ->when($excludeVisitId, fn ($query) => $query->whereKeyNot($excludeVisitId))
            ->first();

        return [
            'employee' => $employee,
            'dependent' => $dependent,
            'quota_owner' => $quotaOwner,
            'remaining_quota' => $quotaOwner->remainingQuota(),
            'existing_visit' => $existingVisit,
        ];
    }

    private function visitPayload(Visit $visit, string $message): array
    {
        $visit->refresh()->load(['clinic', 'visitDepartments.medicalDepartment']);

        return [
            'message' => $message,
            'visit' => [
                'id' => $visit->id,
                'clinic_id' => $visit->clinic_id,
                'clinic_name' => $visit->clinic?->name ?? 'بدون عيادة',
                'total_before_discount' => $visit->total_before_discount,
                'total_after_discount' => $visit->total_after_discount,
            ],
            'departments' => $visit->visitDepartments->map(fn (VisitDepartment $visitDepartment) => [
                'id' => $visitDepartment->id,
                'name' => $visitDepartment->medicalDepartment->name,
                'applied_discount_percentage' => $visitDepartment->applied_discount_percentage,
                'amount_before_discount' => $visitDepartment->amount_before_discount,
                'amount_after_discount' => $visitDepartment->amount_after_discount,
                'update_url' => route('dashboard.visits.departments.update-amount', [$visit, $visitDepartment]),
                'delete_url' => route('dashboard.visits.departments.destroy', [$visit, $visitDepartment]),
            ])->values(),
            'available_departments' => MedicalDepartment::query()
                ->where('is_active', true)
                ->whereNotIn('id', $visit->visitDepartments->pluck('medical_department_id'))
                ->get(['id', 'name'])
                ->values(),
        ];
    }

    public function destroy(Request $request, Visit $visit): RedirectResponse|JsonResponse
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

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'تم حذف الزيارة.']);
        }

        return redirect()->route('dashboard.visits.index')->with('success', 'تم حذف الزيارة.');
    }
}
