<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClinicController extends Controller
{
    public function index(): View
    {
        $this->authorize('view', Clinic::class);

        $clinics = Clinic::orderBy('name')->get();

        return view('dashboard.clinics.index', compact('clinics'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Clinic::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $validated['is_active'] = true;

        $clinic = Clinic::create($validated);

        ActivityLogService::log(
            'Created',
            'Clinic',
            "تم إضافة عيادة: {$clinic->name}.",
            null,
            $clinic->toArray()
        );

        return redirect()->route('dashboard.clinics.index')->with('success', 'تم إضافة العيادة.');
    }

    public function update(Request $request, Clinic $clinic): RedirectResponse
    {
        $this->authorize('update', $clinic);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $old = $clinic->toArray();
        $clinic->update($validated);

        ActivityLogService::log(
            'Updated',
            'Clinic',
            "تم تعديل العيادة: {$clinic->name}.",
            $old,
            $clinic->getChanges()
        );

        return redirect()->route('dashboard.clinics.index')->with('success', 'تم تعديل العيادة.');
    }
}
