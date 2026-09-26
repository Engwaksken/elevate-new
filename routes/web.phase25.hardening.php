<?php

use App\Http\Controllers\Admin\{PlatformSettingsController,SurveyController};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::put('/surveys/{survey}/reorder',[SurveyController::class,'reorder'])
        ->middleware('permission:surveys.manage')
        ->name('surveys.reorder');

    Route::put('/surveys/{survey}/assignments',[SurveyController::class,'assignments'])
        ->middleware('permission:surveys.manage')
        ->name('surveys.assignments');

    Route::get('/surveys/{survey}/responses.csv',[SurveyController::class,'exportCsv'])
        ->middleware('permission:survey_responses.export')
        ->name('surveys.responses.csv');

    Route::put('/platform-settings/storage',[PlatformSettingsController::class,'updateStorage'])
        ->middleware('permission:settings.backups')
        ->name('platform-settings.storage');
});
