<?php

use App\Http\Controllers\Admin\Elearning\BulkEnrolmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('admin/elearning')
    ->name('admin.elearning.')
    ->group(function () {
        Route::get(
            '/bulk-enrolment/template.csv',
            [BulkEnrolmentController::class, 'template']
        )
            ->middleware('permission:students.edit')
            ->name('bulk-enrolment.template');
    });