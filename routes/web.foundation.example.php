<?php
use Illuminate\Support\Facades\Route;
Route::middleware(['auth'])->group(function(){
    Route::view('/dashboard','dashboard')->name('dashboard');
    Route::view('/profile','profile')->name('profile');
});
Route::prefix('admin')->middleware(['auth','staff'])->name('admin.')->group(function(){
    Route::view('/dashboard','admin.dashboard')->name('dashboard');
    Route::view('/programmes','admin.programmes.index')->middleware('permission:programmes.view')->name('programmes.index');
    Route::view('/cohorts','admin.cohorts.index')->middleware('permission:cohorts.view')->name('cohorts.index');
});
