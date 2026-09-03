<?php

// dashboard routes

use App\Http\Controllers\Dashboard\ActivityLogController;
use App\Http\Controllers\Dashboard\ClinicController;
use App\Http\Controllers\Dashboard\DependentController;
use App\Http\Controllers\Dashboard\EmployeeController;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\MedicalDepartmentController;
use App\Http\Controllers\Dashboard\OrganizationUnitController;
use App\Http\Controllers\Dashboard\RadiologyExamController;
use App\Http\Controllers\Dashboard\ReportController;
use App\Http\Controllers\Dashboard\SurveySubmissionController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\VisitController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => '',
    'middleware' => ['auth'],
    'as' => 'dashboard.',
], function () {
    /* ********************************************************** */

    // Dashboard ************************
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::post('dashboard/refresh-cache', [HomeController::class, 'refreshDashboardCache'])->name('home.refresh-cache');

    // Logs ************************
    Route::get('logs', [ActivityLogController::class, 'index'])->name('logs.index');
    Route::get('getLogs', [ActivityLogController::class, 'getLogs'])->name('logs.getLogs');

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
    Route::resource('radiology-exams', RadiologyExamController::class)->only(['index', 'store', 'update']);
    Route::get('radiology-exams-filters/{column}', [RadiologyExamController::class, 'getFilterOptions'])->name('radiology-exams.filters');

    Route::get('employees/check-national-id/{nationalId}', [EmployeeController::class, 'checkNationalId'])->name('employees.check-national-id');
    Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::resource('employees', EmployeeController::class);
    Route::get('employees-filters/{column}', [EmployeeController::class, 'getFilterOptions'])->name('employees.filters');
    Route::resource('employees.dependents', DependentController::class)->except(['index', 'show', 'create', 'edit']);

    Route::get('visits', [VisitController::class, 'index'])->name('visits.index');
    Route::get('visits-filters/{column}', [VisitController::class, 'getFilterOptions'])->name('visits.filters');
    Route::get('visits/search-patients', [VisitController::class, 'searchPatients'])->name('visits.search-patients');
    Route::get('visits/search', [VisitController::class, 'search'])->name('visits.search');
    Route::post('visits', [VisitController::class, 'store'])->name('visits.store');
    Route::get('visits/{visit}', [VisitController::class, 'show'])->name('visits.show');
    Route::get('visits/{visit}/edit', [VisitController::class, 'edit'])->name('visits.edit');
    Route::delete('visits/{visit}', [VisitController::class, 'destroy'])->name('visits.destroy');
    Route::post('visits/{visit}/departments', [VisitController::class, 'addDepartment'])->name('visits.departments.store');
    Route::put('visits/{visit}/departments/{visitDepartment}', [VisitController::class, 'updateDepartmentAmount'])->name('visits.departments.update-amount');
    Route::delete('visits/{visit}/departments/{visitDepartment}', [VisitController::class, 'removeDepartment'])->name('visits.departments.destroy');

    Route::get('survey-submissions', [SurveySubmissionController::class, 'index'])->name('survey-submissions.index');
    Route::get('survey-submissions-filters/{column}', [SurveySubmissionController::class, 'getFilterOptions'])->name('survey-submissions.filters');
    Route::put('survey-submissions-window', [SurveySubmissionController::class, 'updateWindow'])->name('survey-submissions.update-window');
    Route::get('survey-submissions/{surveySubmission}', [SurveySubmissionController::class, 'show'])->name('survey-submissions.show');
    Route::post('survey-submissions/{surveySubmission}/approve', [SurveySubmissionController::class, 'approve'])->name('survey-submissions.approve');
    Route::post('survey-submissions/{surveySubmission}/reject', [SurveySubmissionController::class, 'reject'])->name('survey-submissions.reject');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');

        Route::get('employees', [ReportController::class, 'employees'])->name('employees');
        Route::get('employees/summary', [ReportController::class, 'employeesSummary'])->name('employees.summary');
        Route::get('employees-filters/{column}', [ReportController::class, 'employeesFilterOptions'])->name('employees.filters');
        Route::get('employees/export-excel', [ReportController::class, 'employeesExportExcel'])->name('employees.export-excel');
        Route::get('employees/export-pdf', [ReportController::class, 'employeesExportPdf'])->name('employees.export-pdf');

        Route::get('visits', [ReportController::class, 'visits'])->name('visits');
        Route::get('visits/summary', [ReportController::class, 'visitsSummary'])->name('visits.summary');
        Route::get('visits-filters/{column}', [ReportController::class, 'visitsFilterOptions'])->name('visits.filters');
        Route::get('visits/export-excel', [ReportController::class, 'visitsExportExcel'])->name('visits.export-excel');
        Route::get('visits/export-pdf', [ReportController::class, 'visitsExportPdf'])->name('visits.export-pdf');

        Route::get('income', [ReportController::class, 'income'])->name('income');
        Route::get('income/summary', [ReportController::class, 'incomeSummary'])->name('income.summary');
        Route::get('income-filters/{column}', [ReportController::class, 'incomeFilterOptions'])->name('income.filters');
        Route::get('income/export-excel', [ReportController::class, 'incomeExportExcel'])->name('income.export-excel');
        Route::get('income/export-pdf', [ReportController::class, 'incomeExportPdf'])->name('income.export-pdf');
    });
});
