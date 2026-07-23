<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Dependent;
use App\Models\Employee;
use App\Models\SurveySubmission;
use App\Rules\UniqueNationalId;
use App\Services\ActivityLogService;
use App\Services\SurveyWindowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SurveySubmissionController extends Controller
{
    public function __construct(private SurveyWindowService $surveyWindow)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('view', SurveySubmission::class);

        $status = $request->input('status', 'pending');

        $submissions = SurveySubmission::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('dashboard.survey_submissions.index', [
            'submissions' => $submissions,
            'status' => $status,
            'windowStart' => $this->surveyWindow->start()?->toDateString(),
            'windowEnd' => $this->surveyWindow->end()?->toDateString(),
            'windowOpen' => $this->surveyWindow->isOpen(),
        ]);
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

    public function show(SurveySubmission $surveySubmission): View
    {
        $this->authorize('view', SurveySubmission::class);

        return view('dashboard.survey_submissions.show', ['submission' => $surveySubmission]);
    }

    public function approve(SurveySubmission $surveySubmission): RedirectResponse
    {
        $this->authorize('update', SurveySubmission::class);

        if ($surveySubmission->status !== 'pending') {
            return redirect()->route('dashboard.survey-submissions.index')->with('danger', 'هذا الطلب تمت مراجعته مسبقاً.');
        }

        $data = $surveySubmission->raw_data;

        $nationalIdRule = new UniqueNationalId();
        $conflict = false;
        $nationalIdRule->validate('national_id', $data['national_id'], function () use (&$conflict) {
            $conflict = true;
        });

        if ($conflict) {
            return redirect()->route('dashboard.survey-submissions.show', $surveySubmission)
                ->with('danger', 'رقم الهوية أصبح مستخدماً من جهة أخرى — لا يمكن الموافقة.');
        }

        foreach ($data['dependents'] ?? [] as $dependent) {
            $dependentConflict = false;
            $nationalIdRule->validate('national_id', $dependent['national_id'], function () use (&$dependentConflict) {
                $dependentConflict = true;
            });

            if ($dependentConflict) {
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

        return redirect()->route('dashboard.survey-submissions.index')->with('success', 'تمت الموافقة وإنشاء سجل الموظف.');
    }

    public function reject(SurveySubmission $surveySubmission): RedirectResponse
    {
        $this->authorize('update', SurveySubmission::class);

        if ($surveySubmission->status !== 'pending') {
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

        return redirect()->route('dashboard.survey-submissions.index')->with('success', 'تم رفض الطلب.');
    }
}
