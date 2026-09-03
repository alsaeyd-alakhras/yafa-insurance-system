<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\RadiologyExam;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RadiologyExamController extends Controller
{
    /** Columns filterable via the header dropdown checkbox-list. */
    private const FILTERABLE_COLUMNS = ['category', 'name', 'is_active_label'];

    public function index(Request $request)
    {
        $this->authorize('view', RadiologyExam::class);

        if ($request->ajax()) {
            $query = RadiologyExam::query();
            $this->applyColumnFilters($query, $request);
            $this->applySort($query, $request);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('category', fn (RadiologyExam $exam) => $exam->category ?? '-')
                ->addColumn('price', fn (RadiologyExam $exam) => number_format((float) $exam->price, 2))
                ->addColumn('discount_amount', fn (RadiologyExam $exam) => number_format((float) $exam->discount_amount, 2))
                ->addColumn('is_active_label', fn (RadiologyExam $exam) => $exam->is_active ? 'مفعّلة' : 'معطّلة')
                ->addColumn('is_active_badge', fn (RadiologyExam $exam) => $exam->is_active
                    ? '<span class="badge bg-label-success">مفعّلة</span>'
                    : '<span class="badge bg-label-secondary">معطّلة</span>')
                ->addColumn('edit', fn (RadiologyExam $exam) => $exam->id)
                ->rawColumns(['is_active_badge', 'edit'])
                ->make(true);
        }

        return view('dashboard.radiology_exams.index');
    }

    private function applyColumnFilters($query, Request $request): void
    {
        $filters = (array) $request->input('column_filters', []);

        foreach ($filters as $column => $values) {
            if (! in_array($column, self::FILTERABLE_COLUMNS, true) || empty($values)) {
                continue;
            }

            if ($column === 'is_active_label') {
                $labelToValue = ['مفعّلة' => true, 'معطّلة' => false];
                $boolValues = collect((array) $values)->map(fn ($label) => $labelToValue[$label] ?? null)->filter(fn ($v) => $v !== null);
                $query->whereIn('is_active', $boolValues);

                continue;
            }

            $query->whereIn($column, (array) $values);
        }
    }

    private function applySort($query, Request $request): void
    {
        $sortColumn = $request->input('sort_column');
        $sortDirection = $request->input('sort_direction', 'asc');

        if (! in_array($sortColumn, ['category', 'name', 'price', 'discount_amount'], true)) {
            $query->orderBy('category')->orderBy('name');

            return;
        }

        $query->orderBy($sortColumn, $sortDirection === 'desc' ? 'desc' : 'asc');
    }

    public function getFilterOptions(string $column): JsonResponse
    {
        $this->authorize('view', RadiologyExam::class);

        abort_unless(in_array($column, self::FILTERABLE_COLUMNS, true), 404);

        $values = match ($column) {
            'category' => RadiologyExam::query()->whereNotNull('category')->distinct()->pluck('category'),
            'name' => RadiologyExam::query()->distinct()->pluck('name'),
            'is_active_label' => collect(['مفعّلة', 'معطّلة']),
            default => collect(),
        };

        return response()->json($values->values());
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', RadiologyExam::class);

        $validated = $request->validate([
            'category' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);
        $validated['discount_amount'] = $validated['discount_amount'] ?? 0;
        $validated['is_active'] = true;

        $radiologyExam = RadiologyExam::create($validated);

        ActivityLogService::log(
            'Created',
            'RadiologyExam',
            "تم إضافة فحص أشعة: {$radiologyExam->name}.",
            null,
            $radiologyExam->toArray()
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'تم إضافة فحص الأشعة.']);
        }

        return redirect()->route('dashboard.radiology-exams.index')->with('success', 'تم إضافة فحص الأشعة.');
    }

    public function update(Request $request, RadiologyExam $radiologyExam): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $radiologyExam);

        $validated = $request->validate([
            'category' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);
        $validated['discount_amount'] = $validated['discount_amount'] ?? 0;

        $old = $radiologyExam->toArray();
        $radiologyExam->update($validated);

        ActivityLogService::log(
            'Updated',
            'RadiologyExam',
            "تم تعديل فحص الأشعة: {$radiologyExam->name}.",
            $old,
            $radiologyExam->getChanges()
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'تم تعديل فحص الأشعة.']);
        }

        return redirect()->route('dashboard.radiology-exams.index')->with('success', 'تم تعديل فحص الأشعة.');
    }
}
