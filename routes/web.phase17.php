<?php

use App\Http\Controllers\Admin\AdminProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function(){
    Route::get('/profile',[AdminProfileController::class,'edit'])->name('profile.edit');
    Route::put('/profile',[AdminProfileController::class,'update'])->name('profile.update');
    Route::put('/profile/password',[AdminProfileController::class,'password'])->name('profile.password');
});
