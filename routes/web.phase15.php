<?php

use App\Http\Controllers\Admin\Elearning\CourseAssignmentController;
use App\Http\Controllers\Admin\Elearning\EnrolmentAdminController;
use App\Http\Controllers\Admin\Elearning\LearningFileAdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/elearning')->name('admin.elearning.')->middleware(['auth','staff'])->group(function () {
    Route::get('/assignments',[CourseAssignmentController::class,'index'])
        ->middleware('permission:courses.edit')->name('assignments.index');

    Route::put('/enrolments/{enrolment}',[EnrolmentAdminController::class,'update'])
        ->middleware('permission:students.edit')->name('enrolments.update');

    Route::delete('/enrolments/{enrolment}',[EnrolmentAdminController::class,'destroy'])
        ->middleware('permission:students.edit')->name('enrolments.destroy');

    // IMPORTANT:
    // Do not call this route files.index because the application already has a
    // per-course route with that name: /admin/elearning/courses/{course}/files.
    Route::get('/learning-files',[LearningFileAdminController::class,'index'])
        ->middleware('permission:courses.edit')->name('learning-files.index');

    Route::delete('/learning-files/{file}',[LearningFileAdminController::class,'destroy'])
        ->middleware('permission:courses.edit')->name('learning-files.destroy');
});
