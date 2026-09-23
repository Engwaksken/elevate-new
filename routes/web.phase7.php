<?php

use App\Http\Controllers\Admin\CareerAiController;
use App\Http\Controllers\Admin\Library\LibraryResourceController;
use App\Http\Controllers\Career\CoverLetterController;
use App\Http\Controllers\Career\CoverLetterUploadController;
use App\Http\Controllers\Career\ResumeAiController;
use App\Http\Controllers\Career\ResumeController;
use App\Http\Controllers\Career\ResumeSectionController;
use App\Http\Controllers\Career\ResumeUploadController;
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
    Route::put('/career/resumes/{resume}/template',[ResumeController::class,'updateTemplate'])->name('career.resume.template');
    Route::post('/career/resumes/{resume}/default',[ResumeController::class,'makeDefault'])->name('career.resume.default');
    Route::delete('/career/resumes/{resume}',[ResumeController::class,'destroy'])->name('career.resume.destroy');
    Route::get('/career/resumes/{resume}/download',[ResumeController::class,'download'])->name('career.resume.download');

    Route::post('/career/resumes/upload',[ResumeUploadController::class,'store'])->name('career.resume.upload.store');
    Route::get('/career/resume-uploads/{upload}',[ResumeUploadController::class,'review'])->name('career.resume.upload.review');
    Route::get('/career/resume-uploads/{upload}/status',[ResumeUploadController::class,'status'])->name('career.resume.upload.status');
    Route::post('/career/resume-uploads/{upload}/import',[ResumeUploadController::class,'import'])->name('career.resume.upload.import');
    Route::get('/career/resume-uploads/{upload}/original',[ResumeUploadController::class,'original'])->name('career.resume.upload.original');
    Route::post('/career/resume-uploads/{upload}/replace',[ResumeUploadController::class,'replace'])->name('career.resume.upload.replace');
    Route::post('/career/resume-uploads/{upload}/retry',[ResumeUploadController::class,'retry'])->name('career.resume.upload.retry');
    Route::delete('/career/resume-uploads/{upload}',[ResumeUploadController::class,'destroy'])->name('career.resume.upload.destroy');

    Route::post('/career/resumes/{resume}/ai/improve',[ResumeAiController::class,'improve'])->name('career.resume.ai.improve');
    Route::post('/career/resumes/{resume}/ai/ats',[ResumeAiController::class,'ats'])->name('career.resume.ai.ats');
    Route::post('/career/resumes/{resume}/ai/tailor',[ResumeAiController::class,'tailor'])->name('career.resume.ai.tailor');

    Route::post('/career/cover-letters',[CoverLetterController::class,'store'])->name('career.cover-letter.store');
    Route::post('/career/cover-letters/generate',[CoverLetterController::class,'generate'])->name('career.cover-letter.generate');

    Route::post('/career/cover-letters/upload',[CoverLetterUploadController::class,'store'])->name('career.cover-letter.upload.store');
    Route::get('/career/cover-letter-uploads/{upload}',[CoverLetterUploadController::class,'review'])->name('career.cover-letter.upload.review');
    Route::get('/career/cover-letter-uploads/{upload}/status',[CoverLetterUploadController::class,'status'])->name('career.cover-letter.upload.status');
    Route::post('/career/cover-letter-uploads/{upload}/import',[CoverLetterUploadController::class,'import'])->name('career.cover-letter.upload.import');
    Route::get('/career/cover-letter-uploads/{upload}/original',[CoverLetterUploadController::class,'original'])->name('career.cover-letter.upload.original');
    Route::post('/career/cover-letter-uploads/{upload}/replace',[CoverLetterUploadController::class,'replace'])->name('career.cover-letter.upload.replace');
    Route::post('/career/cover-letter-uploads/{upload}/retry',[CoverLetterUploadController::class,'retry'])->name('career.cover-letter.upload.retry');
    Route::delete('/career/cover-letter-uploads/{upload}',[CoverLetterUploadController::class,'destroy'])->name('career.cover-letter.upload.destroy');

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

Route::prefix('admin/career-ai')->name('admin.career-ai.')->middleware(['auth','staff'])->group(function () {
    Route::get('/',[CareerAiController::class,'index'])->name('index');
    Route::put('/',[CareerAiController::class,'update'])->name('update');
});
