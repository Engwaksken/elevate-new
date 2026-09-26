<?php

use App\Http\Controllers\Admin\Jobs\EmployerAdminController;
use App\Http\Controllers\Admin\Jobs\JobAdminController;
use App\Http\Controllers\Admin\Jobs\OutcomeAdminController;
use App\Http\Controllers\Admin\Mentorship\MentorRecommendationController;
use App\Http\Controllers\Employer\ApplicantController;
use App\Http\Controllers\Employer\EmployerJobController;
use App\Http\Controllers\Employer\EmployerProfileController;
use App\Http\Controllers\Jobs\JobApplicationController;
use App\Http\Controllers\Jobs\JobBrowseController;
use App\Http\Controllers\Mentorship\MentorshipGoalController;
use Illuminate\Support\Facades\Route;

Route::get('/jobs',[JobBrowseController::class,'index'])->name('jobs.index');
Route::get('/jobs/{job}',[JobBrowseController::class,'show'])->name('jobs.show');

Route::middleware(['auth','verified'])->group(function () {
    Route::post('/jobs/{job}/apply',[JobApplicationController::class,'store'])->name('jobs.apply');
    Route::get('/my-job-applications',[JobApplicationController::class,'index'])->name('jobs.applications');
    Route::post('/job-applications/{application}/withdraw',[JobApplicationController::class,'withdraw'])->name('jobs.withdraw');

    Route::post('/mentorship/matches/{match}/goals',[MentorshipGoalController::class,'store'])->name('mentorship.goals.store');
    Route::put('/mentorship/goals/{goal}',[MentorshipGoalController::class,'update'])->name('mentorship.goals.update');

    Route::get('/employer/profile',[EmployerProfileController::class,'edit'])->name('employer.profile.edit');
    Route::put('/employer/profile',[EmployerProfileController::class,'update'])->name('employer.profile.update');
    Route::get('/employer/jobs',[EmployerJobController::class,'index'])->name('employer.jobs.index');
    Route::post('/employer/jobs',[EmployerJobController::class,'store'])->name('employer.jobs.store');
    Route::get('/employer/applicants',[ApplicantController::class,'index'])->name('employer.applicants.index');
    Route::put('/employer/applicants/{application}/status',[ApplicantController::class,'status'])->name('employer.applicants.status');
});

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function () {
    Route::get('/mentorship/mentees/{mentee}/recommendations',[MentorRecommendationController::class,'show'])
        ->middleware('permission:mentorship.match')->name('mentorship.recommendations');

    Route::get('/jobs/employers',[EmployerAdminController::class,'index'])
        ->middleware('permission:employers.approve')->name('jobs.employers.index');
    Route::post('/jobs/employers/{employer}/approve',[EmployerAdminController::class,'approve'])
        ->middleware('permission:employers.approve')->name('jobs.employers.approve');
    Route::post('/jobs/employers/{employer}/reject',[EmployerAdminController::class,'reject'])
        ->middleware('permission:employers.approve')->name('jobs.employers.reject');

    Route::get('/jobs',[JobAdminController::class,'index'])
        ->middleware('permission:jobs.manage')->name('jobs.index');
    Route::post('/jobs',[JobAdminController::class,'store'])
        ->middleware('permission:jobs.manage')->name('jobs.store');
    Route::post('/jobs/import',[JobAdminController::class,'import'])
        ->middleware('permission:jobs.manage')->name('jobs.import');
    Route::put('/jobs/{job}',[JobAdminController::class,'update'])
        ->middleware('permission:jobs.manage')->name('jobs.update');
    Route::delete('/jobs/{job}',[JobAdminController::class,'destroy'])
        ->middleware('permission:jobs.manage')->name('jobs.destroy');
    Route::post('/jobs/{job}/publish',[JobAdminController::class,'publish'])
        ->middleware('permission:jobs.manage')->name('jobs.publish');
    Route::post('/jobs/{job}/reject',[JobAdminController::class,'reject'])
        ->middleware('permission:jobs.manage')->name('jobs.reject');

    Route::get('/outcomes',[OutcomeAdminController::class,'index'])
        ->middleware('permission:meal.view')->name('jobs.outcomes.index');
    Route::post('/outcomes/{outcome}/verify',[OutcomeAdminController::class,'verify'])
        ->middleware('permission:meal.manage')->name('jobs.outcomes.verify');
    Route::post('/outcomes/{outcome}/reject',[OutcomeAdminController::class,'reject'])
        ->middleware('permission:meal.manage')->name('jobs.outcomes.reject');
});
