<?php

use App\Http\Controllers\Admin\Elearning\CertificateIndexController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Phase 23 integration routes
|--------------------------------------------------------------------------
|
| The project already had certificate PDF generation but no admin index route.
| This route exposes the existing certificate management Blade through a
| dedicated index controller without altering the existing generation route.
|
*/

Route::middleware(['auth'])
    ->prefix('admin/elearning')
    ->name('admin.elearning.')
    ->group(function () {
        Route::get('/certificates', [CertificateIndexController::class, 'index'])
            ->name('certificates.index');
    });
