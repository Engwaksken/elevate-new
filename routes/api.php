<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\JobController;
use App\Http\Controllers\Api\V1\LibraryController;
use App\Http\Controllers\Api\V1\MeController;

// ElevateHer360 consolidated API routes.

Route::prefix('v1')->group(function () {
    Route::get('/courses',[CourseController::class,'index']);
    Route::get('/courses/{course}',[CourseController::class,'show']);
    Route::get('/jobs',[JobController::class,'index']);
    Route::get('/library',[LibraryController::class,'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',MeController::class);
    });
});
