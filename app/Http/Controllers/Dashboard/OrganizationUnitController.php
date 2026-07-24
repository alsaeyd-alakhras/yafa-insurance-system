<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\OrganizationUnit;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationUnitController extends Controller
{
    public function index(): View
    {
        $this->authorize('view', OrganizationUnit::class);

        $units = OrganizationUnit::with('children.children.children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $allUnits = OrganizationUnit::where('level', '<', 3)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return view('dashboard.organization_units.index', compact('units', 'allUnits'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', OrganizationUnit::class);

        $validated = $this->validated($request);

        $unit = OrganizationUnit::create($validated);

        ActivityLogService::log(
            'Created',
            'OrganizationUnit',
            "تم إضافة وحدة تنظيمية: {$unit->name}.",
            null,
            $unit->toArray()
        );

        return redirect()->route('dashboard.organization-units.index')->with('success', 'تم إضافة الوحدة التنظيمية.');
    }

    public function update(Request $request, OrganizationUnit $organizationUnit): RedirectResponse
    {
        $this->authorize('update', $organizationUnit);

        $validated = $this->validated($request, $organizationUnit);

        $old = $organizationUnit->toArray();
        $organizationUnit->update($validated);

        ActivityLogService::log(
            'Updated',
            'OrganizationUnit',
            "تم تعديل الوحدة التنظيمية: {$organizationUnit->name}.",
            $old,
            $organizationUnit->getChanges()
        );

        return redirect()->route('dashboard.organization-units.index')->with('success', 'تم تعديل الوحدة التنظيمية.');
    }

    public function destroy(OrganizationUnit $organizationUnit): RedirectResponse
    {
        $this->authorize('delete', $organizationUnit);

        if ($organizationUnit->children()->exists()) {
            return redirect()->route('dashboard.organization-units.index')
                ->with('danger', 'لا يمكن حذف وحدة تنظيمية لها وحدات فرعية.');
        }

        if ($organizationUnit->employees()->exists()) {
            return redirect()->route('dashboard.organization-units.index')
                ->with('danger', 'لا يمكن حذف وحدة تنظيمية مرتبطة بموظفين.');
        }

        $old = $organizationUnit->toArray();
        $organizationUnit->delete();

        ActivityLogService::log(
            'Deleted',
            'OrganizationUnit',
            "تم حذف الوحدة التنظيمية: {$old['name']}.",
            $old,
            null
        );

        return redirect()->route('dashboard.organization-units.index')->with('success', 'تم حذف الوحدة التنظيمية.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?OrganizationUnit $unit = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:organization_units,id',
        ]);

        $validated['parent_id'] = $validated['parent_id'] ?? null;

        if ($unit && (int) $validated['parent_id'] === $unit->id) {
            abort(422, 'لا يمكن أن تكون الوحدة أباً لنفسها.');
        }

        $validated['level'] = $validated['parent_id']
            ? (OrganizationUnit::findOrFail($validated['parent_id'])->level + 1)
            : 1;

        if ($validated['level'] > 3) {
            abort(422, 'لا يمكن إضافة وحدة فرعية لقسم (المستوى الثالث) — هذا هو أعمق مستوى مسموح به.');
        }

        return $validated;
    }
}
