<?php


// dashboard routes

use App\Http\Controllers\Dashboard\ActivityLogController;
use App\Http\Controllers\Dashboard\ClinicController;
use App\Http\Controllers\Dashboard\DependentController;
use App\Http\Controllers\Dashboard\EmployeeController;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\MedicalDepartmentController;
use App\Http\Controllers\Dashboard\OrganizationUnitController;
use App\Http\Controllers\Dashboard\SurveySubmissionController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\VisitController;
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

    Route::resources([
        'users' => UserController::class,
    ]);

    Route::resource('organization-units', OrganizationUnitController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('medical-departments', MedicalDepartmentController::class)->only(['index', 'store', 'update']);
    Route::resource('clinics', ClinicController::class)->only(['index', 'store', 'update']);

    Route::get('employees/check-national-id/{nationalId}', [EmployeeController::class, 'checkNationalId'])->name('employees.check-national-id');
    Route::resource('employees', EmployeeController::class);
    Route::get('employees-filters/{column}', [EmployeeController::class, 'getFilterOptions'])->name('employees.filters');
    Route::resource('employees.dependents', DependentController::class)->except(['index', 'show', 'create', 'edit']);

    Route::get('visits', [VisitController::class, 'index'])->name('visits.index');
    Route::get('visits-filters/{column}', [VisitController::class, 'getFilterOptions'])->name('visits.filters');
    Route::get('visits/search-patients', [VisitController::class, 'searchPatients'])->name('visits.search-patients');
    Route::get('visits/search', [VisitController::class, 'search'])->name('visits.search');
    Route::post('visits', [VisitController::class, 'store'])->name('visits.store');
    Route::get('visits/{visit}/edit', [VisitController::class, 'edit'])->name('visits.edit');
    Route::delete('visits/{visit}', [VisitController::class, 'destroy'])->name('visits.destroy');
    Route::post('visits/{visit}/departments', [VisitController::class, 'addDepartment'])->name('visits.departments.store');
    Route::put('visits/{visit}/departments/{visitDepartment}', [VisitController::class, 'updateDepartmentAmount'])->name('visits.departments.update-amount');

    Route::get('survey-submissions', [SurveySubmissionController::class, 'index'])->name('survey-submissions.index');
    Route::put('survey-submissions-window', [SurveySubmissionController::class, 'updateWindow'])->name('survey-submissions.update-window');
    Route::get('survey-submissions/{surveySubmission}', [SurveySubmissionController::class, 'show'])->name('survey-submissions.show');
    Route::post('survey-submissions/{surveySubmission}/approve', [SurveySubmissionController::class, 'approve'])->name('survey-submissions.approve');
    Route::post('survey-submissions/{surveySubmission}/reject', [SurveySubmissionController::class, 'reject'])->name('survey-submissions.reject');
});
