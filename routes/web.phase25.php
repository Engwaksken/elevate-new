<?php

use App\Http\Controllers\Admin\{CourseCallController,PlatformSettingsController,SurveyController};
use App\Http\Controllers\Participant\{CourseCallApplicationController,SurveyResponseController};
use App\Http\Controllers\Public\PublicSurveyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::middleware('permission:course_calls.view')->group(function () {
        Route::get('/course-calls',[CourseCallController::class,'index'])->name('course-calls.index');
        Route::get('/course-calls/{courseCall}/applications',[CourseCallController::class,'applications'])->name('course-calls.applications');
    });

    Route::middleware('permission:course_calls.manage')->group(function () {
        Route::post('/course-calls',[CourseCallController::class,'store'])->name('course-calls.store');
        Route::put('/course-calls/{courseCall}',[CourseCallController::class,'update'])->name('course-calls.update');
        Route::delete('/course-calls/{courseCall}',[CourseCallController::class,'destroy'])->name('course-calls.destroy');
        Route::post('/course-calls/{courseCall}/questions',[CourseCallController::class,'addQuestion'])->name('course-calls.questions.store');
    });

    Route::put('/course-applications/{application}/review',[CourseCallController::class,'review'])
        ->middleware('permission:applications.review')
        ->name('course-applications.review');

    Route::middleware('permission:surveys.view')->group(function () {
        Route::get('/surveys',[SurveyController::class,'index'])->name('surveys.index');
        Route::get('/surveys/{survey}/responses',[SurveyController::class,'responses'])->name('surveys.responses');
    });

    Route::middleware('permission:surveys.manage')->group(function () {
        Route::post('/surveys',[SurveyController::class,'store'])->name('surveys.store');
        Route::get('/surveys/{survey}/builder',[SurveyController::class,'builder'])->name('surveys.builder');
        Route::post('/surveys/{survey}/sections',[SurveyController::class,'addSection'])->name('surveys.sections.store');
        Route::post('/surveys/{survey}/questions',[SurveyController::class,'addQuestion'])->name('surveys.questions.store');
        Route::delete('/surveys/{survey}/questions/{question}',[SurveyController::class,'destroyQuestion'])->name('surveys.questions.destroy');
    });

    /*
     * IMPORTANT:
     * Use the controller index, not Route::view().
     * The hardened settings page requires $settings and $backups.
     */
    Route::get('/platform-settings',[PlatformSettingsController::class,'index'])
        ->middleware('permission:settings.manage')
        ->name('platform-settings.index');

    Route::put('/platform-settings/branding',[PlatformSettingsController::class,'updateBranding'])
        ->middleware('permission:settings.branding')
        ->name('platform-settings.branding');

    Route::put('/platform-settings/maintenance',[PlatformSettingsController::class,'updateMaintenance'])
        ->middleware('permission:settings.maintenance')
        ->name('platform-settings.maintenance');

    Route::post('/platform-settings/backup-now',[PlatformSettingsController::class,'backupNow'])
        ->middleware('permission:settings.backups')
        ->name('platform-settings.backup-now');
});

Route::middleware(['auth', \App\Http\Middleware\EnsureParticipantUser::class])->prefix('participant')->name('participant.')->group(function () {
    Route::get('/course-opportunities',[CourseCallApplicationController::class,'index'])->name('course-calls.index');
    Route::get('/course-opportunities/{courseCall}',[CourseCallApplicationController::class,'show'])->name('course-calls.show');
    Route::put('/course-opportunities/{courseCall}',[CourseCallApplicationController::class,'save'])->name('course-calls.save');

    Route::get('/surveys',[SurveyResponseController::class,'index'])->name('surveys.index');
    Route::get('/surveys/{survey}',[SurveyResponseController::class,'show'])->name('surveys.show');
    Route::put('/surveys/{survey}',[SurveyResponseController::class,'save'])->name('surveys.save');
});

Route::get('/surveys/{survey:slug}',[PublicSurveyController::class,'show'])->name('surveys.public.show');
Route::post('/surveys/{survey:slug}',[PublicSurveyController::class,'store'])->name('surveys.public.store');
Route::get('/surveys/{survey:slug}/qr.svg',[PublicSurveyController::class,'qr'])->name('surveys.public.qr');
