<?php

namespace App\Http\Controllers;

use App\Models\Dependent;
use App\Models\Employee;
use App\Models\OrganizationUnit;
use App\Models\SurveySubmission;
use App\Services\SurveyWindowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurveySubmissionPublicController extends Controller
{
    public function __construct(private SurveyWindowService $surveyWindow)
    {
    }

    public function show(): View
    {
        if (! $this->surveyWindow->isOpen()) {
            return view('survey.closed');
        }

        $organizationUnits = OrganizationUnit::orderBy('name')->get();

        return view('survey.form', compact('organizationUnits'));
    }

    public function checkNationalId(string $nationalId): JsonResponse
    {
        return response()->json(['exists' => $this->nationalIdExists($nationalId)]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->surveyWindow->isOpen()) {
            return redirect()->route('survey.show')->with('danger', 'انتهت فترة استقبال البيانات.');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'national_id' => 'required|string|size:9',
            'gender' => 'required|in:male,female',
            'marital_status' => 'required|in:single,married,polygamous,widowed,divorced',
            'organization_unit_id' => 'required|exists:organization_units,id',
            'dependents' => 'array',
            'dependents.*.type' => 'required_with:dependents|in:spouse,child,parent',
            'dependents.*.full_name' => 'required_with:dependents|string|max:255',
            'dependents.*.national_id' => 'required_with:dependents|string|size:9',
            'dependents.*.gender' => 'required_with:dependents|in:male,female',
            'dependents.*.parent_type' => 'nullable|in:father,mother',
        ]);

        if ($this->nationalIdExists($validated['national_id'])) {
            return redirect()->route('survey.show')
                ->withInput()
                ->with('danger', 'رقم الهوية مستخدم مسبقاً. الرجاء التحقق من الرقم أو التواصل مع الإدارة.');
        }

        foreach ($validated['dependents'] ?? [] as $dependent) {
            if ($this->nationalIdExists($dependent['national_id'])) {
                return redirect()->route('survey.show')
                    ->withInput()
                    ->with('danger', "رقم الهوية {$dependent['national_id']} (أحد التابعين) مستخدم مسبقاً.");
            }
        }

        SurveySubmission::create([
            'raw_data' => $validated,
            'national_id' => $validated['national_id'],
            'status' => 'pending',
        ]);

        return redirect()->route('survey.show')->with('success', 'تم استلام بياناتك بنجاح. سيتم مراجعتها من قبل الإدارة.');
    }

    private function nationalIdExists(string $nationalId): bool
    {
        return Employee::where('national_id', $nationalId)->exists()
            || Dependent::where('national_id', $nationalId)->exists()
            || SurveySubmission::where('national_id', $nationalId)->where('status', 'pending')->exists();
    }
}
