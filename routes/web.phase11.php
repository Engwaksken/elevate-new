<?php

use App\Http\Controllers\Admin\ExecutiveDashboardController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\NotificationAdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function () {
    Route::get('/executive-dashboard',[ExecutiveDashboardController::class,'index'])
        ->middleware('permission:reports.view')->name('executive-dashboard');

    Route::get('/search',GlobalSearchController::class)
        ->middleware('permission:reports.view')->name('search');

    Route::get('/settings',[SettingsController::class,'index'])
        ->middleware('permission:settings.manage')->name('settings.index');
    Route::post('/settings',[SettingsController::class,'store'])
        ->middleware('permission:settings.manage')->name('settings.store');
    Route::put('/settings/{setting}',[SettingsController::class,'update'])
        ->middleware('permission:settings.manage')->name('settings.update');
    Route::delete('/settings/{setting}',[SettingsController::class,'destroy'])
        ->middleware('permission:settings.manage')->name('settings.destroy');
    Route::get('/audit-logs',[AuditLogController::class,'index'])
        ->middleware('permission:reports.view')->name('audit-logs.index');

    Route::get('/notifications',[NotificationAdminController::class,'index'])
        ->middleware('permission:reports.view')->name('notifications.index');
});

