<?php
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\EventPortalController;
use Illuminate\Support\Facades\Route;
Route::get('/events',[EventPortalController::class,'index'])->name('events.index');
Route::get('/events/{event}',[EventPortalController::class,'show'])->name('events.show');
Route::middleware(['auth','verified'])->group(function(){Route::post('/events/{event}/register',[EventPortalController::class,'register'])->name('events.register');});
Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function(){
 Route::get('/events',[EventController::class,'index'])->name('events.index');
 Route::post('/events',[EventController::class,'store'])->name('events.store');
 Route::put('/events/{event}',[EventController::class,'update'])->name('events.update');
 Route::delete('/events/{event}',[EventController::class,'destroy'])->name('events.destroy');
 Route::get('/events/{event}/attendance',[EventController::class,'attendance'])->name('events.attendance');
 Route::post('/events/{event}/attendance',[EventController::class,'saveAttendance'])->name('events.attendance.save');
});
