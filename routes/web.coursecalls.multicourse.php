<?php

use App\Http\Controllers\Admin\CourseCallController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::middleware('permission:course_calls.view')->group(function () {
            Route::get('/course-calls', [CourseCallController::class, 'index'])
                ->name('course-calls.index');

            Route::get('/course-calls/{courseCall}', [CourseCallController::class, 'show'])
                ->name('course-calls.show');

            Route::get('/course-calls/{courseCall}/applications', [CourseCallController::class, 'applications'])
                ->name('course-calls.applications');
        });

        Route::middleware('permission:course_calls.manage')->group(function () {
            Route::post('/course-calls', [CourseCallController::class, 'store'])
                ->name('course-calls.store');

            Route::get('/course-calls/{courseCall}/edit', [CourseCallController::class, 'edit'])
                ->name('course-calls.edit');

            Route::put('/course-calls/{courseCall}', [CourseCallController::class, 'update'])
                ->name('course-calls.update');

            Route::delete('/course-calls/{courseCall}', [CourseCallController::class, 'destroy'])
                ->name('course-calls.destroy');

            Route::post('/course-calls/{courseCall}/questions', [CourseCallController::class, 'addQuestion'])
                ->name('course-calls.questions.store');
        });
    });
