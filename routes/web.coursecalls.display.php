<?php

use App\Http\Controllers\Admin\CourseCallDisplayController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get(
            '/course-calls/{courseCall}/qr.svg',
            [CourseCallDisplayController::class, 'qr']
        )
            ->middleware('permission:course_calls.view')
            ->name('course-calls.qr');
    });
