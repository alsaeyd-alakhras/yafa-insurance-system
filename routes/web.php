<?php

use App\Http\Controllers\SurveySubmissionPublicController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {return view('welcome');})->name('home');

Route::prefix('survey')->name('survey.')->group(function () {
    Route::get('/', [SurveySubmissionPublicController::class, 'show'])->name('show');
    Route::post('/', [SurveySubmissionPublicController::class, 'store'])->name('store');
    Route::get('check-national-id/{nationalId}', [SurveySubmissionPublicController::class, 'checkNationalId'])->name('check-national-id');
});

require __DIR__.'/dashboard.php';
