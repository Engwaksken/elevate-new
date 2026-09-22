<?php

use App\Http\Controllers\Admin\ME\IndicatorController;
use App\Http\Controllers\Admin\ME\MEDashboardController;
use App\Http\Controllers\Admin\ME\ResultsFrameworkController;
use App\Http\Controllers\Admin\ProgrammeManagement\ActivityController;
use App\Http\Controllers\Admin\ProgrammeManagement\MilestoneController;
use App\Http\Controllers\Admin\ProgrammeManagement\WorkplanController;
use App\Http\Controllers\Calendar\CalendarController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/calendar',[CalendarController::class,'index'])->name('calendar.index');
});

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function () {
    Route::get('/workplans',[WorkplanController::class,'index'])->middleware('permission:workplans.view')->name('workplans.index');
    Route::post('/workplans',[WorkplanController::class,'store'])->middleware('permission:workplans.create')->name('workplans.store');
    Route::post('/workplans/{workplan}/submit',[WorkplanController::class,'submit'])->middleware('permission:workplans.edit')->name('workplans.submit');
    Route::post('/workplans/{workplan}/approve',[WorkplanController::class,'approve'])->middleware('permission:workplans.approve')->name('workplans.approve');

    Route::post('/workplans/{workplan}/milestones',[MilestoneController::class,'store'])->middleware('permission:milestones.manage')->name('milestones.store');
    Route::put('/milestones/{milestone}',[MilestoneController::class,'update'])->middleware('permission:milestones.manage')->name('milestones.update');

    Route::post('/workplans/{workplan}/activities',[ActivityController::class,'store'])->middleware('permission:activities.manage')->name('activities.store');
    Route::put('/activities/{activity}/progress',[ActivityController::class,'updateProgress'])->middleware('permission:activities.manage')->name('activities.progress');

    Route::get('/indicators',[IndicatorController::class,'index'])->middleware('permission:indicators.view')->name('indicators.index');
    Route::post('/indicators',[IndicatorController::class,'store'])->middleware('permission:indicators.manage')->name('indicators.store');
    Route::post('/indicators/{indicator}/targets',[IndicatorController::class,'addTarget'])->middleware('permission:indicators.manage')->name('indicators.targets.store');
    Route::post('/indicators/{indicator}/calculate',[IndicatorController::class,'calculate'])->middleware('permission:indicators.manage')->name('indicators.calculate');
    Route::post('/indicator-results/{result}/verify',[IndicatorController::class,'verify'])->middleware('permission:indicators.verify')->name('indicator-results.verify');

    Route::get('/results-framework',[ResultsFrameworkController::class,'index'])->middleware('permission:meal.view')->name('results-framework.index');
    Route::post('/results-framework',[ResultsFrameworkController::class,'store'])->middleware('permission:meal.manage')->name('results-framework.store');
    Route::post('/results-framework/{framework}/results',[ResultsFrameworkController::class,'addResult'])->middleware('permission:meal.manage')->name('results-framework.results.store');

    Route::get('/meal',[MEDashboardController::class,'index'])->middleware('permission:meal.view')->name('meal.dashboard');
});
