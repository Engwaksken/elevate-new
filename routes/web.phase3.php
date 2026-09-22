<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MigrationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\Elearning\CourseController;
use App\Http\Controllers\Admin\Elearning\CourseModuleController;
use App\Http\Controllers\Admin\Elearning\LessonController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth','staff'])
    ->group(function () {
        Route::get('/dashboard',[DashboardController::class,'index'])->name('dashboard');

        Route::resource('users',UserController::class)->except(['show','destroy'])
            ->middleware('permission:users.edit');

        Route::get('/migrations',[MigrationController::class,'index'])
            ->middleware('permission:settings.manage')->name('migrations.index');
        Route::get('/migrations/create',[MigrationController::class,'create'])
            ->middleware('permission:settings.manage')->name('migrations.create');
        Route::post('/migrations',[MigrationController::class,'store'])
            ->middleware('permission:settings.manage')->name('migrations.store');
        Route::get('/migrations/{migration}',[MigrationController::class,'show'])
            ->middleware('permission:settings.manage')->name('migrations.show');

        Route::prefix('elearning')->name('elearning.')->group(function () {
            Route::resource('courses',CourseController::class)->except(['show','destroy'])
                ->middleware('permission:courses.edit');

            Route::post('/courses/{course}/modules',[CourseModuleController::class,'store'])
                ->middleware('permission:courses.edit')->name('modules.store');
            Route::put('/courses/{course}/modules/{module}',[CourseModuleController::class,'update'])
                ->middleware('permission:courses.edit')->name('modules.update');
            Route::delete('/courses/{course}/modules/{module}',[CourseModuleController::class,'destroy'])
                ->middleware('permission:courses.delete')->name('modules.destroy');

            Route::post('/modules/{module}/lessons',[LessonController::class,'store'])
                ->middleware('permission:courses.edit')->name('lessons.store');
            Route::put('/modules/{module}/lessons/{lesson}',[LessonController::class,'update'])
                ->middleware('permission:courses.edit')->name('lessons.update');
            Route::delete('/modules/{module}/lessons/{lesson}',[LessonController::class,'destroy'])
                ->middleware('permission:courses.delete')->name('lessons.destroy');
        });
    });
