<?php

use App\Http\Controllers\Api\V1\Participant\DashboardController;
use App\Http\Controllers\Api\V1\Participant\DeviceTokenController;
use App\Http\Controllers\Api\V1\Participant\OfflineActionController;
use App\Http\Controllers\Api\V1\Participant\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/participant')->middleware(['auth:sanctum','throttle:api'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class);
    Route::get('/sync', [SyncController::class, 'pull']);
    Route::post('/sync/actions', [OfflineActionController::class, 'store']);
    Route::post('/device-token', [DeviceTokenController::class, 'store']);
    Route::delete('/device-token', [DeviceTokenController::class, 'destroy']);
});
