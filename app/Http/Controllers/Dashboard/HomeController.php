<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\SurveySubmission;
use App\Models\Visit;
use App\Services\SurveyWindowService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $data = [
            'role' => $user?->super_admin ? 'super_admin' : ($user?->role ?? 'guest'),
        ];

        if ($user?->can('view', Visit::class)) {
            $data['visitsToday'] = Visit::whereDate('visit_date', today())->count();
            $data['visitsThisMonth'] = Visit::whereYear('visit_date', now()->year)
                ->whereMonth('visit_date', now()->month)
                ->count();
        }

        if ($user?->can('view', SurveySubmission::class)) {
            $data['pendingSurveySubmissions'] = SurveySubmission::where('status', 'pending')->count();
        }

        if ($user?->can('update', SurveySubmission::class)) {
            $data['surveyWindowOpen'] = app(SurveyWindowService::class)->isOpen();
        }

        if ($user?->can('view', Employee::class)) {
            $data['pendingEmployees'] = Employee::where('status', 'pending')->count();
            $data['activeEmployeesCount'] = Employee::where('status', 'active')->count();
        }

        if ($user?->can('view', ActivityLog::class)) {
            $data['recentActivity'] = ActivityLog::latest('created_at')->take(6)->get();
        }

        return view('dashboard.index', $data);
    }

    public function refreshDashboardCache()
    {
        return back()->with('success', 'تم تحديث البيانات.');
    }
}
