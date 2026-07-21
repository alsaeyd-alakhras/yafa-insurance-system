<?php


// dashboard routes

use App\Http\Controllers\Dashboard\ActivityLogController;
use App\Http\Controllers\Dashboard\ConstantController;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\UserController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => '',
    'middleware' => ['auth'],
    'as' => 'dashboard.'
], function () {
    /* ********************************************************** */

    // Dashboard ************************
    Route::get('/', [HomeController::class,'index'])->name('home');
    Route::post('dashboard/refresh-cache', [HomeController::class, 'refreshDashboardCache'])->name('home.refresh-cache');

    // Logs ************************
    Route::get('logs',[ActivityLogController::class,'index'])->name('logs.index');
    Route::get('getLogs',[ActivityLogController::class,'getLogs'])->name('logs.getLogs');

    // users ************************
    Route::get('profile/settings', [UserController::class, 'settings'])->name('profile.settings');
    Route::put('profile/settings', [UserController::class, 'updateProfile'])->name('profile.update');

    /* ********************************************************** */

    // Resources

    Route::resource('constants', ConstantController::class)->only(['index','store','destroy']);

    Route::resources([
        'users' => UserController::class,
    ]);

    /* ********************************************************** */

    // Phase 5 placeholders — remove each line once its real controller/routes land
    Route::get('visits', fn () => view('dashboard.pages.coming-soon', ['label' => 'الزيارات']))->name('visits.index');
    Route::get('employees', fn () => view('dashboard.pages.coming-soon', ['label' => 'الموظفون والتابعون']))->name('employees.index');
    Route::get('organization-units', fn () => view('dashboard.pages.coming-soon', ['label' => 'الوحدات التنظيمية']))->name('organization-units.index');
    Route::get('medical-departments', fn () => view('dashboard.pages.coming-soon', ['label' => 'الأقسام الطبية']))->name('medical-departments.index');
    Route::get('survey-submissions', fn () => view('dashboard.pages.coming-soon', ['label' => 'طلبات الاستبيان']))->name('survey-submissions.index');
});