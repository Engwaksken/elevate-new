<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CohortController;
use App\Http\Controllers\Admin\ProgrammeController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Auth\ParticipantAuthController;
use App\Http\Controllers\Auth\StaffAuthController;
use App\Http\Controllers\Participant\DashboardController;
use App\Http\Controllers\Participant\NotificationController;
use App\Http\Controllers\Participant\ProfileController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PasswordResetController;

// Participant authentication
Route::middleware('guest')->group(function () {
    Route::get('/register', [ParticipantAuthController::class, 'create'])->name('register');
    Route::post('/register', [ParticipantAuthController::class, 'store'])->name('register.store');
    Route::get('/login', [ParticipantAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [ParticipantAuthController::class, 'login'])->name('login.attempt');

    Route::get('/admin/login', [StaffAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/admin/login', [StaffAuthController::class, 'login'])->name('admin.login.attempt');
    
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])
    ->name('password.request');

Route::post('/forgot-password', [PasswordResetController::class, 'email'])
    ->name('password.email');

Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])
    ->name('password.reset');

Route::post('/reset-password', [PasswordResetController::class, 'update'])
    ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [ParticipantAuthController::class, 'logout'])->name('logout');

    Route::get('/email/verify', fn () => view('auth.verify-email'))
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('dashboard')->with('success', 'Email verified.');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('success', 'Verification link sent.');
    })->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'staff'])
    ->group(function () {
        Route::post('/logout', [StaffAuthController::class, 'logout'])->name('logout');

        Route::resource('programmes', ProgrammeController::class)->except('show')
            ->middleware('permission:programmes.manage');

        Route::resource('projects', ProjectController::class)->except('show')
            ->middleware('permission:programmes.manage');

        Route::resource('branches', BranchController::class)->except('show')
            ->middleware('permission:programmes.manage');

        Route::resource('cohorts', CohortController::class)->except('show')
            ->middleware('permission:cohorts.manage');

        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:roles.manage')->name('roles.index');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
            ->middleware('permission:roles.manage')->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->middleware('permission:roles.manage')->name('roles.update');
    });

