<?php

use App\Http\Controllers\Admin\Elearning\CourseAssignmentController;
use App\Http\Controllers\Admin\Elearning\EnrolmentAdminController;
use App\Http\Controllers\Instructor\AttendanceController;
use App\Http\Controllers\Instructor\InstructorDashboardController;
use App\Http\Controllers\Learning\AssessmentController;
use App\Http\Controllers\Learning\CertificateController;
use App\Http\Controllers\Learning\CourseCatalogueController;
use App\Http\Controllers\Learning\EnrolmentController;
use App\Http\Controllers\Learning\LessonController;
use Illuminate\Support\Facades\Route;

Route::get('/learning',[CourseCatalogueController::class,'index'])->name('learning.index');
Route::get('/learning/courses/{course}',[CourseCatalogueController::class,'show'])->name('learning.course.show');
Route::get('/certificates/verify/{token}',[CertificateController::class,'verify'])->name('certificates.verify');

Route::middleware(['auth','verified'])->group(function () {
    Route::post('/learning/courses/{course}/enrol',[EnrolmentController::class,'store'])->name('learning.enrol');
    Route::get('/learning/my-courses',[EnrolmentController::class,'myCourses'])->name('learning.my-courses');
    Route::get('/learning/lessons/{lesson}',[LessonController::class,'show'])->name('learning.lesson.show');
    Route::post('/learning/lessons/{lesson}/complete',[LessonController::class,'complete'])->name('learning.lesson.complete');
    Route::get('/learning/assessments/{assessment}',[AssessmentController::class,'show'])->name('learning.assessment.show');
    Route::post('/learning/assessments/{assessment}',[AssessmentController::class,'submit'])->name('learning.assessment.submit');
});

Route::prefix('admin/elearning')->name('admin.elearning.')->middleware(['auth','staff'])->group(function () {
    Route::get('/courses/{course}/assignments',[CourseAssignmentController::class,'edit'])
        ->middleware('permission:courses.edit')->name('assignments.edit');
    Route::put('/courses/{course}/assignments',[CourseAssignmentController::class,'update'])
        ->middleware('permission:courses.edit')->name('assignments.update');
    Route::get('/enrolments',[EnrolmentAdminController::class,'index'])
        ->middleware('permission:students.view')->name('enrolments.index');
    Route::post('/enrolments',[EnrolmentAdminController::class,'store'])
        ->middleware('permission:students.edit')->name('enrolments.store');
});

Route::prefix('instructor')->name('instructor.')->middleware(['auth','staff'])->group(function () {
    Route::get('/dashboard',[InstructorDashboardController::class,'index'])->name('dashboard');
    Route::get('/courses/{course}/attendance',[AttendanceController::class,'create'])->name('attendance.create');
    Route::post('/courses/{course}/attendance',[AttendanceController::class,'store'])->name('attendance.store');
});
