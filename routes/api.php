<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\JobController;
use App\Http\Controllers\Api\V1\LibraryController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\Participant\AuthController as ParticipantAuthController;
use App\Http\Controllers\Api\V1\Participant\ParticipantController;

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

        Route::prefix('participant')->group(function () {
            Route::post('/logout',[ParticipantAuthController::class,'logout']);
            Route::get('/me',[ParticipantController::class,'me']);
            Route::get('/dashboard',[ParticipantController::class,'dashboard']);
            Route::get('/courses',[ParticipantController::class,'courses']);
            Route::get('/courses/{course}',[ParticipantController::class,'course']);
            Route::get('/assignments',[ParticipantController::class,'assignments']);
            Route::post('/assignments/{assessment}/submit',[ParticipantController::class,'submitAssignment']);
            Route::get('/announcements',[ParticipantController::class,'announcements']);
            Route::get('/sync',[ParticipantController::class,'sync']);
            Route::post('/device-token',[ParticipantController::class,'deviceToken']);
        });
    });
});
