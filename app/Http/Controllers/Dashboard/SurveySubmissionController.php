<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Dependent;
use App\Models\Employee;
use App\Models\OrganizationUnit;
use App\Models\SurveySubmission;
use App\Rules\UniqueNationalId;
use App\Services\ActivityLogService;
use App\Services\SurveyWindowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SurveySubmissionController extends Controller
{
    private const STATUS_LABELS = [
        'pending' => 'قيد المراجعة',
        'approved' => 'موافَق عليها',
        'rejected' => 'مرفوضة',
    ];

    private const GENDER_LABELS = [
        'male' => 'ذكر',
        'female' => 'أنثى',
    ];

    private const MARITAL_STATUS_LABELS = [
        'single' => 'أعزب/عزباء',
        'married' => 'متزوج/ة',
        'polygamous' => 'متعدد الزوجات',
        'widowed' => 'أرمل/ة',
        'divorced' => 'مطلق/ة',
    ];

    private const PARENT_TYPE_LABELS = [
        'father' => 'أب',
        'mother' => 'أم',
    ];

    /** Columns filterable via the header dropdown checkbox-list. */
    private const FILTERABLE_COLUMNS = ['full_name', 'national_id', 'status', 'gender', 'marital_status', 'organization_center', 'organization_department', 'organization_unit_name', 'created_at'];

    public function __construct(private SurveyWindowService $surveyWindow)
    {
    }

    public function index(Request $request): JsonResponse|View
    {
        $this->authorize('view', SurveySubmission::class);

        if ($request->ajax()) {
            $query = SurveySubmission::query();
            $this->applyColumnFilters($query, $request);
            $this->applySort($query, $request);

            if (! $request->filled('sort_column')) {
                $query->orderByDesc('created_at');
            }

            $organizationUnits = OrganizationUnit::all()->keyBy('id');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('full_name', fn (SurveySubmission $submission) => $submission->raw_data['full_name'] ?? '-')
                ->addColumn('gender', fn (SurveySubmission $submission) => $submission->raw_data['gender'] ?? null)
                ->addColumn('marital_status', fn (SurveySubmission $submission) => $submission->raw_data['marital_status'] ?? null)
                ->addColumn('organization_center', function (SurveySubmission $submission) use ($organizationUnits) {
                    $unit = $organizationUnits->get($submission->raw_data['organization_unit_id'] ?? null);

                    return $unit?->ancestryChain()['center']?->name ?? '-';
                })
                ->addColumn('organization_department', function (SurveySubmission $submission) use ($organizationUnits) {
                    $unit = $organizationUnits->get($submission->raw_data['organization_unit_id'] ?? null);

                    return $unit?->ancestryChain()['department']?->name ?? '-';
                })
                ->addColumn('organization_unit_name', function (SurveySubmission $submission) use ($organizationUnits) {
                    return $organizationUnits->get($submission->raw_data['organization_unit_id'] ?? null)?->name ?? '-';
                })
                ->addColumn('created_at_formatted', fn (SurveySubmission $submission) => $submission->created_at?->format('Y-m-d H:i') ?? '-')
                ->addColumn('view', fn (SurveySubmission $submission) => $submission->id)
                ->rawColumns(['view'])
                ->make(true);
        }

        return view('dashboard.survey_submissions.index', [
            'windowStart' => $this->surveyWindow->start()?->toDateString(),
            'windowEnd' => $this->surveyWindow->end()?->toDateString(),
            'windowOpen' => $this->surveyWindow->isOpen(),
        ]);
    }

    private function applyColumnFilters($query, Request $request): void
    {
        $filters = (array) $request->input('column_filters', []);

        foreach ($filters as $column => $values) {
            if (! in_array($column, self::FILTERABLE_COLUMNS, true) || empty($values)) {
                continue;
            }

            if ($column === 'status') {
                $values = $this->normalizeFilterValues($column, (array) $values);
                $query->whereIn('status', $values);
                continue;
            }

            if ($column === 'organization_center') {
                $query->whereIn(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.organization_unit_id'))"), $this->unitIdsUnderAncestors(1, (array) $values));
                continue;
            }

            if ($column === 'organization_department') {
                $query->whereIn(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.organization_unit_id'))"), $this->unitIdsUnderAncestors(2, (array) $values));
                continue;
            }

            if ($column === 'organization_unit_name') {
                $unitIds = OrganizationUnit::where('level', 3)->whereIn('name', (array) $values)->pluck('id');
                $query->whereIn(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.organization_unit_id'))"), $unitIds);
                continue;
            }

            if ($column === 'created_at') {
                $query->where(function ($q) use ($values) {
                    foreach ((array) $values as $value) {
                        $q->orWhereDate('created_at', $value);
                    }
                });
                continue;
            }

            $values = $this->normalizeFilterValues($column, (array) $values);
            $query->whereIn(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.{$column}'))"), $values);
        }
    }

    /**
     * Resolve level-3 (section) organization unit ids that fall under any level-1/level-2
     * ancestor unit matching the given names, for filtering survey submissions by center/department.
     *
     * @return array<int>
     */
    private function unitIdsUnderAncestors(int $ancestorLevel, array $ancestorNames): array
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

    private function applySort($query, Request $request): void
    {
        $sortColumn = $request->input('sort_column');
        $sortDirection = $request->input('sort_direction', 'asc');

        if (! in_array($sortColumn, ['national_id', 'created_at'], true)) {
            return;
        }

        $query->orderBy($sortColumn, $sortDirection === 'desc' ? 'desc' : 'asc');
    }

    public function getFilterOptions(string $column): JsonResponse
    {
        $this->authorize('view', SurveySubmission::class);

        abort_unless(in_array($column, self::FILTERABLE_COLUMNS, true), 404);

        if ($column === 'status') {
            $values = SurveySubmission::query()->distinct()->pluck('status')->filter()->values();

            return response()->json($values->map(fn ($value) => self::STATUS_LABELS[$value] ?? $value));
        }

        if (in_array($column, ['organization_center', 'organization_department', 'organization_unit_name'], true)) {
            $unitIds = SurveySubmission::query()
                ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.organization_unit_id')) as unit_id"))
                ->distinct()
                ->pluck('unit_id')
                ->filter()
                ->values();

            $level = match ($column) {
                'organization_center' => 1,
                'organization_department' => 2,
                default => 3,
            };

            $values = OrganizationUnit::where('level', 3)
                ->whereIn('id', $unitIds)
                ->get()
                ->map(fn (OrganizationUnit $unit) => $level === 3 ? $unit->name : ($unit->ancestryChain()[$level === 1 ? 'center' : 'department']?->name))
                ->filter()
                ->unique()
                ->values();

            return response()->json($values);
        }

        if ($column === 'created_at') {
            $values = SurveySubmission::query()
                ->select(DB::raw('DATE(created_at) as submission_date'))
                ->distinct()
                ->orderByDesc('submission_date')
                ->pluck('submission_date');

            return response()->json($values);
        }

        if (in_array($column, ['full_name', 'national_id'], true)) {
            $values = SurveySubmission::query()
                ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.{$column}')) as {$column}"))
                ->distinct()
                ->pluck($column)
                ->filter()
                ->values();

            return response()->json($values);
        }

        $labels = $column === 'gender' ? self::GENDER_LABELS : self::MARITAL_STATUS_LABELS;

        $values = SurveySubmission::query()
            ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.{$column}')) as {$column}"))
            ->distinct()
            ->pluck($column)
            ->filter()
            ->values()
            ->map(fn ($value) => $labels[$value] ?? $value);

        return response()->json($values);
    }

    private function normalizeFilterValues(string $column, array $values): array
    {
        $map = match ($column) {
            'status' => array_flip(self::STATUS_LABELS),
            'gender' => array_flip(self::GENDER_LABELS),
            'marital_status' => array_flip(self::MARITAL_STATUS_LABELS),
            default => [],
        };

        return collect($values)
            ->map(fn ($value) => $map[$value] ?? $value)
            ->filter()
            ->values()
            ->all();
    }

    public function updateWindow(Request $request): RedirectResponse
    {
        $this->authorize('update', SurveySubmission::class);

        $validated = $request->validate([
            'window_start' => 'required|date',
            'window_end' => 'required|date|after_or_equal:window_start',
        ]);

        $this->surveyWindow->setWindow($validated['window_start'], $validated['window_end']);

        return redirect()->route('dashboard.survey-submissions.index')->with('success', 'تم تحديث نافذة الاستبيان.');
    }

    public function show(Request $request, SurveySubmission $surveySubmission): JsonResponse|View
    {
        $this->authorize('view', SurveySubmission::class);

        if ($request->ajax() || $request->wantsJson()) {
            $data = $surveySubmission->raw_data;
            $dependents = collect($data['dependents'] ?? []);

            return response()->json([
                'id' => $surveySubmission->id,
                'employee' => [
                    'full_name' => $data['full_name'] ?? '-',
                    'national_id' => $data['national_id'] ?? '-',
                    'gender' => self::GENDER_LABELS[$data['gender'] ?? ''] ?? '-',
                    'marital_status' => self::MARITAL_STATUS_LABELS[$data['marital_status'] ?? ''] ?? '-',
                    'organization_unit_name' => OrganizationUnit::find($data['organization_unit_id'] ?? null)?->name ?? '-',
                    'status' => $surveySubmission->status,
                    'status_label' => self::STATUS_LABELS[$surveySubmission->status] ?? $surveySubmission->status,
                ],
                'dependents' => [
                    'spouses' => $this->formatDependents($dependents->where('type', 'spouse')->values()),
                    'children' => $this->formatDependents($dependents->where('type', 'child')->values()),
                    'parents' => $this->formatDependents($dependents->where('type', 'parent')->values()),
                ],
                'can_update' => auth()->user()->can('update', SurveySubmission::class),
                'urls' => [
                    'approve' => route('dashboard.survey-submissions.approve', $surveySubmission),
                    'reject' => route('dashboard.survey-submissions.reject', $surveySubmission),
                ],
            ]);
        }

        return view('dashboard.survey_submissions.show', ['submission' => $surveySubmission]);
    }

    private function formatDependents($dependents): array
    {
        return $dependents
            ->map(fn (array $dependent) => [
                'full_name' => $dependent['full_name'] ?? '-',
                'national_id' => $dependent['national_id'] ?? '-',
                'gender' => self::GENDER_LABELS[$dependent['gender'] ?? ''] ?? '-',
                'parent_type' => self::PARENT_TYPE_LABELS[$dependent['parent_type'] ?? ''] ?? null,
            ])
            ->values()
            ->all();
    }

    public function approve(Request $request, SurveySubmission $surveySubmission): JsonResponse|RedirectResponse
    {
        $this->authorize('update', SurveySubmission::class);

        if ($surveySubmission->status !== 'pending') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => 'هذا الطلب تمت مراجعته مسبقاً.'], 422);
            }

            return redirect()->route('dashboard.survey-submissions.index')->with('danger', 'هذا الطلب تمت مراجعته مسبقاً.');
        }

        $data = $surveySubmission->raw_data;

        $nationalIdRule = new UniqueNationalId();
        $conflict = false;
        $nationalIdRule->validate('national_id', $data['national_id'], function () use (&$conflict) {
            $conflict = true;
        });

        if ($conflict) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => 'رقم الهوية أصبح مستخدماً من جهة أخرى — لا يمكن الموافقة.'], 422);
            }

            return redirect()->route('dashboard.survey-submissions.show', $surveySubmission)
                ->with('danger', 'رقم الهوية أصبح مستخدماً من جهة أخرى — لا يمكن الموافقة.');
        }

        foreach ($data['dependents'] ?? [] as $dependent) {
            $dependentConflict = false;
            $nationalIdRule->validate('national_id', $dependent['national_id'], function () use (&$dependentConflict) {
                $dependentConflict = true;
            });

            if ($dependentConflict) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['message' => "رقم هوية أحد التابعين ({$dependent['national_id']}) أصبح مستخدماً من جهة أخرى — لا يمكن الموافقة."], 422);
                }

                return redirect()->route('dashboard.survey-submissions.show', $surveySubmission)
                    ->with('danger', "رقم هوية أحد التابعين ({$dependent['national_id']}) أصبح مستخدماً من جهة أخرى — لا يمكن الموافقة.");
            }
        }

        DB::transaction(function () use ($surveySubmission, $data) {
            $employee = Employee::create([
                'full_name' => $data['full_name'],
                'national_id' => $data['national_id'],
                'gender' => $data['gender'],
                'marital_status' => $data['marital_status'],
                'organization_unit_id' => $data['organization_unit_id'],
                'status' => 'active',
                'source' => 'survey',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            foreach ($data['dependents'] ?? [] as $dependent) {
                Dependent::create([
                    'employee_id' => $employee->id,
                    'type' => $dependent['type'],
                    'full_name' => $dependent['full_name'],
                    'national_id' => $dependent['national_id'],
                    'gender' => $dependent['gender'],
                    'parent_type' => $dependent['parent_type'] ?? null,
                ]);
            }

            $surveySubmission->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'created_employee_id' => $employee->id,
            ]);

            ActivityLogService::log(
                'Updated',
                'SurveySubmission',
                "تمت الموافقة على طلب استبيان وإنشاء الموظف: {$employee->full_name}.",
                null,
                $surveySubmission->fresh()->toArray()
            );
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'تمت الموافقة وإنشاء سجل الموظف.']);
        }

        return redirect()->route('dashboard.survey-submissions.index')->with('success', 'تمت الموافقة وإنشاء سجل الموظف.');
    }

    public function reject(Request $request, SurveySubmission $surveySubmission): JsonResponse|RedirectResponse
    {
        $this->authorize('update', SurveySubmission::class);

        if ($surveySubmission->status !== 'pending') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => 'هذا الطلب تمت مراجعته مسبقاً.'], 422);
            }

            return redirect()->route('dashboard.survey-submissions.index')->with('danger', 'هذا الطلب تمت مراجعته مسبقاً.');
        }

        $surveySubmission->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        ActivityLogService::log(
            'Updated',
            'SurveySubmission',
            "تم رفض طلب استبيان من: {$surveySubmission->raw_data['full_name']}.",
            null,
            $surveySubmission->toArray()
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'تم رفض الطلب.']);
        }

        return redirect()->route('dashboard.survey-submissions.index')->with('success', 'تم رفض الطلب.');
    }
}
