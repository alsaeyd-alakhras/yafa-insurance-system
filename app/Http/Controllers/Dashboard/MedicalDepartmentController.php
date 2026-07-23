<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MedicalDepartment;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicalDepartmentController extends Controller
{
    private const LABELS = [
        'clinics' => 'الكشف الطبي',
        'pharmacy' => 'الصيدلية',
        'laboratory' => 'المختبر',
        'optics' => 'البصريات',
        'dental' => 'الأسنان',
        'radiology' => 'الأشعة',
    ];

    public function index(): View
    {
        $this->authorize('view', MedicalDepartment::class);

        $departments = MedicalDepartment::where('is_active', true)->get();

        return view('dashboard.medical_departments.index', [
            'departments' => $departments,
            'labels' => self::LABELS,
        ]);
    }

    public function update(Request $request, MedicalDepartment $medicalDepartment): RedirectResponse
    {
        $this->authorize('update', $medicalDepartment);

        $validated = $request->validate([
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'max_discount_amount' => 'nullable|numeric|min:0',
        ]);

        $old = $medicalDepartment->toArray();
        $medicalDepartment->update($validated);

        $label = self::LABELS[$medicalDepartment->name] ?? $medicalDepartment->name;

        ActivityLogService::log(
            'Updated',
            'MedicalDepartment',
            "تم تعديل خصم القسم الطبي: {$label}.",
            $old,
            $medicalDepartment->getChanges()
        );

        return redirect()->route('dashboard.medical-departments.index')->with('success', 'تم تعديل القسم الطبي.');
    }
}
