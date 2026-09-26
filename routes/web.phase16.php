<?php

use App\Http\Controllers\Admin\CourseAttendanceReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function(){
    Route::get('/course-attendance-report',[CourseAttendanceReportController::class,'index'])
        ->name('course-attendance-report.index');

    Route::get('/course-attendance-report.csv',[CourseAttendanceReportController::class,'csv'])
        ->name('course-attendance-report.csv');

    Route::get('/participant-attendance-summary',[CourseAttendanceReportController::class,'participant'])
        ->name('participant-attendance-summary.index');
});
