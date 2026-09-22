<?php

use App\Http\Controllers\Admin\Elearning\AssessmentBuilderController;
use App\Http\Controllers\Admin\Elearning\BulkEnrolmentController;
use App\Http\Controllers\Admin\Elearning\CertificateAdminController;
use App\Http\Controllers\Admin\Elearning\GradebookController;
use App\Http\Controllers\Admin\Elearning\LearningFileAdminController;
use App\Http\Controllers\Admin\Mentorship\MentorAdminController;
use App\Http\Controllers\Admin\Mentorship\MentorMatchController;
use App\Http\Controllers\Learning\LearningFileController;
use App\Http\Controllers\Mentorship\MentorProfileController;
use App\Http\Controllers\Mentorship\MentorshipDashboardController;
use App\Http\Controllers\Mentorship\MentorshipSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/learning/files/{file}/download',[LearningFileController::class,'download'])
        ->name('learning.files.download');

    Route::get('/mentorship',[MentorshipDashboardController::class,'index'])->name('mentorship.dashboard');
    Route::get('/mentorship/mentor-profile',[MentorProfileController::class,'edit'])->name('mentorship.mentor-profile.edit');
    Route::put('/mentorship/mentor-profile',[MentorProfileController::class,'update'])->name('mentorship.mentor-profile.update');
    Route::post('/mentorship/matches/{match}/sessions',[MentorshipSessionController::class,'store'])->name('mentorship.sessions.store');
    Route::put('/mentorship/sessions/{session}/complete',[MentorshipSessionController::class,'complete'])->name('mentorship.sessions.complete');
});

Route::prefix('admin/elearning')->name('admin.elearning.')->middleware(['auth','staff'])->group(function () {
    Route::get('/courses/{course}/assessments',[AssessmentBuilderController::class,'index'])
        ->middleware('permission:courses.edit')->name('assessments.index');
    Route::post('/courses/{course}/assessments',[AssessmentBuilderController::class,'store'])
        ->middleware('permission:courses.edit')->name('assessments.store');
    Route::get('/courses/{course}/assessments/{assessment}',[AssessmentBuilderController::class,'edit'])
        ->middleware('permission:courses.edit')->name('assessments.edit');
    Route::post('/courses/{course}/assessments/{assessment}/questions',[AssessmentBuilderController::class,'addQuestion'])
        ->middleware('permission:courses.edit')->name('questions.store');
    Route::delete('/courses/{course}/assessments/{assessment}/questions/{question}',[AssessmentBuilderController::class,'destroyQuestion'])
        ->middleware('permission:courses.delete')->name('questions.destroy');

    Route::get('/courses/{course}/gradebook',[GradebookController::class,'index'])
        ->middleware('permission:courses.edit')->name('gradebook.index');
    Route::get('/gradebook/{attempt}',[GradebookController::class,'edit'])
        ->middleware('permission:courses.edit')->name('gradebook.edit');
    Route::put('/gradebook/{attempt}',[GradebookController::class,'update'])
        ->middleware('permission:courses.edit')->name('gradebook.update');

    Route::get('/bulk-enrolment',[BulkEnrolmentController::class,'create'])
        ->middleware('permission:students.edit')->name('bulk-enrolment.create');
    Route::post('/bulk-enrolment',[BulkEnrolmentController::class,'store'])
        ->middleware('permission:students.edit')->name('bulk-enrolment.store');

    Route::post('/courses/{course}/files',[LearningFileAdminController::class,'store'])
        ->middleware('permission:courses.edit')->name('files.store');

    Route::post('/certificates/{certificate}/generate',[CertificateAdminController::class,'generate'])
        ->middleware('permission:courses.edit')->name('certificates.generate');
});

Route::prefix('admin/mentorship')->name('admin.mentorship.')->middleware(['auth','staff'])->group(function () {
    Route::get('/mentors',[MentorAdminController::class,'index'])
        ->middleware('permission:mentors.manage')->name('mentors.index');
    Route::post('/mentors/{mentor}/approve',[MentorAdminController::class,'approve'])
        ->middleware('permission:mentors.manage')->name('mentors.approve');
    Route::post('/mentors/{mentor}/reject',[MentorAdminController::class,'reject'])
        ->middleware('permission:mentors.manage')->name('mentors.reject');

    Route::get('/matches',[MentorMatchController::class,'index'])
        ->middleware('permission:mentorship.match')->name('matches.index');
    Route::post('/matches',[MentorMatchController::class,'store'])
        ->middleware('permission:mentorship.match')->name('matches.store');
});
