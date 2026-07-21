<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        return view('dashboard.index', [
            'role' => $user?->role ?? ($user?->super_admin ? 'super_admin' : 'guest'),
        ]);
    }

    public function refreshDashboardCache()
    {
        return back()->with('success', 'تم تحديث البيانات.');
    }
}
