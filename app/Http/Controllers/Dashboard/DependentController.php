<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Dependent;
use App\Models\Employee;
use App\Rules\UniqueNationalId;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DependentController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('create', Dependent::class);

        $validated = $this->validateDependent($request, $employee);
        $validated['employee_id'] = $employee->id;

        $dependent = Dependent::create($validated);

        ActivityLogService::log(
            'Created',
            'Dependent',
            "تم إضافة تابع: {$dependent->full_name} للموظف {$employee->full_name}.",
            null,
            $dependent->toArray()
        );

        return redirect()->route('dashboard.employees.edit', $employee)->with('success', 'تم إضافة التابع.');
    }

    public function update(Request $request, Employee $employee, Dependent $dependent): RedirectResponse
    {
        $this->authorize('update', $dependent);

        $validated = $this->validateDependent($request, $employee, $dependent);

        $old = $dependent->toArray();
        $dependent->update($validated);

        ActivityLogService::log(
            'Updated',
            'Dependent',
            "تم تعديل بيانات التابع: {$dependent->full_name}.",
            $old,
            $dependent->getChanges()
        );

        return redirect()->route('dashboard.employees.edit', $employee)->with('success', 'تم تعديل بيانات التابع.');
    }

    public function destroy(Employee $employee, Dependent $dependent): RedirectResponse
    {
        $this->authorize('delete', $dependent);

        $old = $dependent->toArray();

        try {
            $dependent->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('dashboard.employees.edit', $employee)
                ->with('danger', 'لا يمكن حذف تابع له زيارات مسجّلة.');
        }

        ActivityLogService::log(
            'Deleted',
            'Dependent',
            "تم حذف التابع: {$old['full_name']}.",
            $old,
            null
        );

        return redirect()->route('dashboard.employees.edit', $employee)->with('success', 'تم حذف التابع.');
    }

    /** @return array<string, mixed> */
    private function validateDependent(Request $request, Employee $employee, ?Dependent $dependent = null): array
    {
        $validated = $request->validate([
            'type' => 'required|in:spouse,child,parent',
            'full_name' => 'required|string|max:255',
            'national_id' => [
                'required',
                'string',
                'size:9',
                new UniqueNationalId('dependents', $dependent?->id),
            ],
            'gender' => 'required|in:male,female',
            'parent_type' => 'nullable|in:father,mother',
        ]);

        if ($validated['type'] !== 'parent') {
            $validated['parent_type'] = null;
        }

        $this->assertDependentRules($employee, $validated, $dependent);

        return $validated;
    }

    /** @param array<string, mixed> $validated */
    private function assertDependentRules(Employee $employee, array $validated, ?Dependent $dependent): void
    {
        $existingQuery = $employee->dependents()->where('type', $validated['type']);
        if ($dependent) {
            $existingQuery->where('id', '!=', $dependent->id);
        }

        if ($validated['type'] === 'spouse') {
            if ($validated['gender'] === ($employee->gender === 'male' ? 'male' : 'female')) {
                abort(422, 'جنس الزوج/ة يجب أن يخالف جنس الموظف.');
            }

            $maxSpouses = $employee->gender === 'male' && $employee->marital_status === 'polygamous' ? 4 : 1;
            $spouseCountAfterSave = $existingQuery->count() + 1;

            if ($spouseCountAfterSave > $maxSpouses) {
                abort(422, $maxSpouses === 4
                    ? 'لا يمكن إضافة أكثر من 4 زوجات.'
                    : 'لا يمكن إضافة أكثر من زوج/ة واحدة.');
            }
        }

        if ($validated['type'] === 'parent') {
            if (empty($validated['parent_type'])) {
                abort(422, 'يجب تحديد نوع الوالد (أب/أم).');
            }

            $duplicateParentQuery = $employee->dependents()
                ->where('type', 'parent')
                ->where('parent_type', $validated['parent_type']);
            if ($dependent) {
                $duplicateParentQuery->where('id', '!=', $dependent->id);
            }

            if ($duplicateParentQuery->exists()) {
                abort(422, 'يوجد بالفعل سجل لهذا الوالد.');
            }
        }
    }
}
