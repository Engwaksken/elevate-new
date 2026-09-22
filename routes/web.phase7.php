<?php

use App\Http\Controllers\Admin\Library\LibraryResourceController;
use App\Http\Controllers\Career\ResumeController;
use App\Http\Controllers\Career\ResumeSectionController;
use App\Http\Controllers\Employer\InterviewController;
use App\Http\Controllers\Employer\OfferController;
use App\Http\Controllers\Jobs\JobRecommendationController;
use App\Http\Controllers\Jobs\SavedJobController;
use App\Http\Controllers\Library\LibraryController;
use Illuminate\Support\Facades\Route;

Route::get('/library',[LibraryController::class,'index'])->name('library.index');
Route::get('/library/{resource}',[LibraryController::class,'show'])->name('library.show');

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/career/resumes',[ResumeController::class,'index'])->name('career.resume.index');
    Route::get('/career/resumes/create',[ResumeController::class,'create'])->name('career.resume.create');
    Route::post('/career/resumes',[ResumeController::class,'store'])->name('career.resume.store');
    Route::get('/career/resumes/{resume}/edit',[ResumeController::class,'edit'])->name('career.resume.edit');
    Route::put('/career/resumes/{resume}',[ResumeController::class,'update'])->name('career.resume.update');
    Route::post('/career/resumes/{resume}/default',[ResumeController::class,'makeDefault'])->name('career.resume.default');
    Route::get('/career/resumes/{resume}/download',[ResumeController::class,'download'])->name('career.resume.download');

    Route::post('/career/resumes/{resume}/experience',[ResumeSectionController::class,'addExperience'])->name('career.resume.experience.store');
    Route::post('/career/resumes/{resume}/education',[ResumeSectionController::class,'addEducation'])->name('career.resume.education.store');
    Route::post('/career/resumes/{resume}/skills',[ResumeSectionController::class,'addSkill'])->name('career.resume.skill.store');

    Route::get('/jobs/saved',[SavedJobController::class,'index'])->name('jobs.saved');
    Route::post('/jobs/{job}/save',[SavedJobController::class,'store'])->name('jobs.save');
    Route::delete('/jobs/{job}/save',[SavedJobController::class,'destroy'])->name('jobs.unsave');
    Route::get('/jobs/recommendations',[JobRecommendationController::class,'index'])->name('jobs.recommendations');

    Route::post('/employer/applications/{application}/interviews',[InterviewController::class,'store'])->name('employer.interviews.store');
    Route::post('/employer/applications/{application}/offers',[OfferController::class,'store'])->name('employer.offers.store');

    Route::get('/library/{resource}/download',[LibraryController::class,'download'])->name('library.download');
    Route::post('/library/{resource}/bookmark',[LibraryController::class,'bookmark'])->name('library.bookmark');
});

Route::prefix('admin/library')->name('admin.library.')->middleware(['auth','staff','permission:library.manage'])->group(function () {
    Route::get('/',[LibraryResourceController::class,'index'])->name('index');
    Route::post('/',[LibraryResourceController::class,'store'])->name('store');
});
