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

                Route::get('/courses',[ParticipantController::class,'courses']);
                Route::get('/courses/{course}',[ParticipantController::class,'course']);

                Route::get('/assignments',[ParticipantController::class,'assignments']);
                Route::post('/assignments/{assessment}/submit',[ParticipantController::class,'submitAssignment']);
                Route::post('/assignments/{assessment}/extension-requests',[ParticipantController::class,'requestExtension'])
                    ->name('api.participant.assignments.extension-requests.store');

                Route::get('/mentorship',[ParticipantController::class,'mentorship']);
                Route::post('/mentorship/sessions/{session}/attendance',[ParticipantController::class,'confirmMentorshipAttendance'])
                    ->whereNumber('session')
                    ->name('api.participant.mentorship.sessions.attendance');

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
