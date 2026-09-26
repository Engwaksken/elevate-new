<?php

use App\Http\Controllers\Admin\EventOperationsController;
use App\Http\Controllers\EventCheckinController;
use Illuminate\Support\Facades\Route;

Route::get('/events/{event}/calendar.ics',[EventCheckinController::class,'calendar'])->name('events.calendar');

Route::middleware(['auth','verified'])->group(function(){
    Route::get('/events/{event}/check-in/{token}',[EventCheckinController::class,'checkin'])->name('events.checkin');
});

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function(){
    Route::get('/events/{event}/view',[EventOperationsController::class,'show'])->name('events.view');
    Route::post('/events/{event}/reminders',[EventOperationsController::class,'saveReminder'])->name('events.reminders.store');
    Route::delete('/events/{event}/reminders/{reminder}',[EventOperationsController::class,'deleteReminder'])->name('events.reminders.destroy');
    Route::get('/events/{event}/attendance.csv',[EventOperationsController::class,'csv'])->name('events.attendance.csv');

    Route::get('/attendance-analytics',[EventOperationsController::class,'analytics'])->name('attendance-analytics.index');
    Route::get('/attendance-analytics.csv',[EventOperationsController::class,'analyticsCsv'])->name('attendance-analytics.csv');
});
