<?php

use App\Http\Controllers\Admin\ExecutiveDashboardController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\SettingsController;
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
});
