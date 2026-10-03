<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\JobController;
use App\Http\Controllers\Api\V1\LibraryController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\Participant\AuthController as ParticipantAuthController;
use App\Http\Controllers\Api\V1\Participant\LessonController as ParticipantLessonController;
use App\Http\Controllers\Api\V1\Participant\ParticipantController;
use App\Http\Controllers\Api\V1\Participant\ProfileController as ParticipantProfileController;
use App\Http\Controllers\Api\V1\Participant\ProgressController as ParticipantProgressController;
use App\Http\Controllers\Api\V1\Participant\SupportController as ParticipantSupportController;
use App\Http\Middleware\EnsureParticipantApi;

// ElevateHer360 consolidated API routes.

Route::prefix('v1')->group(function () {
    Route::get('/courses',[CourseController::class,'index']);
    Route::get('/courses/{course}',[CourseController::class,'show']);
    Route::get('/jobs',[JobController::class,'index']);
    Route::get('/library',[LibraryController::class,'index']);

    Route::post('/participant/login',[ParticipantAuthController::class,'login'])
        ->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',MeController::class);

        Route::prefix('participant')
            ->middleware(EnsureParticipantApi::class)
            ->group(function () {
                Route::post('/logout',[ParticipantAuthController::class,'logout']);
                Route::get('/me',[ParticipantController::class,'me']);
                Route::get('/dashboard',[ParticipantController::class,'dashboard']);
                Route::get('/support',[ParticipantSupportController::class,'show']);
                Route::get('/career/documents', [\App\Http\Controllers\Api\V1\Participant\CareerDocumentController::class, 'index']);
                Route::prefix('career/uploads')->where(['type' => 'resume|cover-letter', 'id' => '[0-9]+'])->group(function () {
                    Route::post('/{type}', [\App\Http\Controllers\Api\V1\Participant\CareerUploadController::class, 'store'])->middleware('throttle:10,1');
                    Route::get('/{type}/{id}', [\App\Http\Controllers\Api\V1\Participant\CareerUploadController::class, 'show']);
                    Route::post('/{type}/{id}/import', [\App\Http\Controllers\Api\V1\Participant\CareerUploadController::class, 'import']);
                    Route::post('/{type}/{id}/retry', [\App\Http\Controllers\Api\V1\Participant\CareerUploadController::class, 'retry'])->middleware('throttle:5,1');
                    Route::get('/{type}/{id}/original', [\App\Http\Controllers\Api\V1\Participant\CareerUploadController::class, 'original']);
                    Route::delete('/{type}/{id}', [\App\Http\Controllers\Api\V1\Participant\CareerUploadController::class, 'destroy']);
                });
                Route::post('/career/documents/{type}/{id}/ai', [\App\Http\Controllers\Api\V1\Participant\CareerAiController::class, 'assist'])
                    ->where('type', 'resume|cover-letter')->whereNumber('id')->middleware('throttle:10,1');
                Route::post('/career/resumes', [\App\Http\Controllers\Api\V1\Participant\CareerDocumentController::class, 'storeResume']);
                Route::post('/career/resumes/{resume}/portfolio-files', [\App\Http\Controllers\Career\ResumePortfolioController::class, 'store']);
                Route::get('/career/resumes/{resume}/portfolio-files/{file}', [\App\Http\Controllers\Career\ResumePortfolioController::class, 'download']);
                Route::delete('/career/resumes/{resume}/portfolio-files/{file}', [\App\Http\Controllers\Career\ResumePortfolioController::class, 'destroy']);
                Route::put('/career/resumes/{resume}', [\App\Http\Controllers\Api\V1\Participant\CareerDocumentController::class, 'updateResume']);
                Route::delete('/career/resumes/{resume}', [\App\Http\Controllers\Api\V1\Participant\CareerDocumentController::class, 'destroyResume']);
                Route::get('/career/resumes/{resume}/download', [\App\Http\Controllers\Api\V1\Participant\CareerDocumentController::class, 'downloadResume']);
                Route::post('/career/cover-letters', [\App\Http\Controllers\Api\V1\Participant\CareerDocumentController::class, 'storeLetter']);
                Route::put('/career/cover-letters/{letter}', [\App\Http\Controllers\Api\V1\Participant\CareerDocumentController::class, 'updateLetter']);
                Route::delete('/career/cover-letters/{letter}', [\App\Http\Controllers\Api\V1\Participant\CareerDocumentController::class, 'destroyLetter']);
                Route::get('/career/cover-letters/{letter}/download', [\App\Http\Controllers\Api\V1\Participant\CareerDocumentController::class, 'downloadLetter']);
                Route::post('/career/documents/{type}/{id}/share', [\App\Http\Controllers\Career\DocumentShareController::class, 'share'])->where('type', 'resume|cover-letter')->whereNumber('id');

                Route::get('/courses',[ParticipantController::class,'courses']);
                Route::get('/certificates', [\App\Http\Controllers\Learning\ParticipantCertificateController::class, 'index']);
                Route::prefix('certificates')->where(['type'=>'course|event','id'=>'[0-9]+'])->group(function () {
                    Route::get('/{type}/{id}/preview', [\App\Http\Controllers\Learning\ParticipantCertificateController::class, 'preview']);
                    Route::get('/{type}/{id}/download', [\App\Http\Controllers\Learning\ParticipantCertificateController::class, 'download']);
                    Route::post('/{type}/{id}/share', [\App\Http\Controllers\Learning\ParticipantCertificateController::class, 'share']);
                });
                Route::get('/courses/{course}',[ParticipantController::class,'course']);

                Route::get('/assignments',[ParticipantController::class,'assignments']);
                Route::post('/assignments/{assessment}/submit',[ParticipantController::class,'submitAssignment']);
                Route::post('/assignments/{assessment}/extension-requests',[ParticipantController::class,'requestExtension'])
                    ->name('api.participant.assignments.extension-requests.store');

                Route::get('/mentorship',[ParticipantController::class,'mentorship']);
                Route::post('/mentorship/sessions/{session}/attendance',[ParticipantController::class,'confirmMentorshipAttendance'])
                    ->whereNumber('session')
                    ->name('api.participant.mentorship.sessions.attendance');
                Route::post('/mentorship/assistant',[App\Http\Controllers\Api\V1\Participant\MentorshipAssistantController::class,'message'])
                    ->middleware('throttle:20,1')->name('api.participant.mentorship.assistant');

                Route::get('/goals',[App\Http\Controllers\Api\V1\Participant\GoalController::class,'index'])->name('api.participant.goals.index');
                Route::post('/goals',[App\Http\Controllers\Api\V1\Participant\GoalController::class,'store'])->name('api.participant.goals.store');
                Route::put('/goals/{goal}',[App\Http\Controllers\Api\V1\Participant\GoalController::class,'update'])->whereNumber('goal')->name('api.participant.goals.update');
                Route::put('/goals/{goal}/progress',[App\Http\Controllers\Api\V1\Participant\GoalController::class,'progress'])->whereNumber('goal')->name('api.participant.goals.progress');
                Route::delete('/goals/{goal}',[App\Http\Controllers\Api\V1\Participant\GoalController::class,'destroy'])->whereNumber('goal')->name('api.participant.goals.destroy');

                Route::get('/profile',[ParticipantProfileController::class,'show'])->name('api.participant.profile.show');
                Route::put('/profile',[ParticipantProfileController::class,'update'])->name('api.participant.profile.update');
                Route::put('/profile/password',[ParticipantProfileController::class,'updatePassword'])
                    ->middleware('throttle:10,1')
                    ->name('api.participant.profile.password');
                Route::get('/profile/photo',[ParticipantProfileController::class,'photo'])->name('api.participant.profile.photo');
                Route::post('/profile/photo',[ParticipantProfileController::class,'uploadPhoto'])->name('api.participant.profile.photo.store');
                Route::delete('/profile/photo',[ParticipantProfileController::class,'deletePhoto'])->name('api.participant.profile.photo.destroy');

                Route::get('/progress',[ParticipantProgressController::class,'show'])->name('api.participant.progress');

                Route::get('/jobs',[ParticipantController::class,'jobs']);
                Route::post('/jobs/{job}/save',[ParticipantController::class,'saveJob']);
                Route::delete('/jobs/{job}/save',[ParticipantController::class,'unsaveJob']);

                Route::get('/events',[ParticipantController::class,'events']);
                Route::get('/announcements',[ParticipantController::class,'announcements']);

                Route::get('/notifications',[ParticipantController::class,'notifications']);
                Route::put('/notifications/{notification}/read',[ParticipantController::class,'markNotificationRead']);

                Route::get('/lessons/{lesson}',[ParticipantLessonController::class,'show'])
                    ->name('api.participant.lessons.show');
                Route::get('/lessons/{lesson}/download',[ParticipantLessonController::class,'download'])
                    ->name('api.participant.lessons.download');
                Route::get('/lessons/{lesson}/files/{file}/download',[ParticipantLessonController::class,'downloadFile'])
                    ->name('api.participant.lessons.files.download');
                Route::match(['put','post'],'/lessons/{lesson}/progress',[ParticipantLessonController::class,'progress'])
                    ->name('api.participant.lessons.progress');

                Route::get('/assignments/{assessment}/attachment',[ParticipantLessonController::class,'assessmentAttachment'])
                    ->name('api.participant.assignments.attachment');

                Route::post('/offline-actions',[ParticipantController::class,'processOfflineActions']);
                Route::get('/sync',[ParticipantController::class,'sync']);

                Route::post('/device-token',[ParticipantController::class,'deviceToken']);
                Route::delete('/device-token',[ParticipantController::class,'removeDeviceToken']);
            });
    });
});
