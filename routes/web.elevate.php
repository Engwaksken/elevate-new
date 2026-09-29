<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InstructorCourseWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {
    Route::get('/instructor/dashboard', fn () => redirect()->route('admin.dashboard'))
        ->name('instructor.dashboard.legacy');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::middleware('can:manage-assigned-courses')->group(function (): void {
            Route::get('/my-courses', [InstructorCourseWorkspaceController::class, 'index'])->name('my-courses.index');
            Route::get('/my-courses/{course}', [InstructorCourseWorkspaceController::class, 'show'])->name('my-courses.show');
        });
    });
});
