<?php

use App\Http\Controllers\Admin\EventEvaluationController;
use App\Http\Controllers\EventEngagementController;
use Illuminate\Support\Facades\Route;

Route::get('/event-certificates/verify/{code}',[EventEngagementController::class,'verify'])->name('events.certificates.verify');

Route::middleware(['auth','verified'])->group(function(){
    Route::get('/events/{event}/feedback',[EventEngagementController::class,'feedback'])->name('events.feedback');
    Route::post('/events/{event}/feedback',[EventEngagementController::class,'saveFeedback'])->name('events.feedback.store');
    Route::get('/events/{event}/certificate',[EventEngagementController::class,'certificate'])->name('events.certificate');
});

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function(){
    Route::post('/events/{event}/evaluation-settings',[EventEvaluationController::class,'settings'])->name('events.evaluation-settings');
    Route::get('/events/{event}/feedback',[EventEvaluationController::class,'feedback'])->name('events.feedback');
    Route::get('/events/{event}/reminder-logs',[EventEvaluationController::class,'reminderLogs'])->name('events.reminder-logs');

    Route::get('/events-calendar',[EventEvaluationController::class,'calendar'])->name('events.calendar');
    Route::get('/events-meal-report',[EventEvaluationController::class,'mealReport'])->name('events.meal-report');
    Route::get('/events-meal-report.csv',[EventEvaluationController::class,'mealCsv'])->name('events.meal-report.csv');
});
