<?php

use App\Http\Controllers\Api\SyncPushController;
use App\Http\Controllers\Api\SyncPullController;
use App\Http\Controllers\Api\SyncErrorsController;

// Initial sync - NO farm.context middleware (solves chicken-and-egg problem)
Route::middleware(['auth:sanctum'])
    ->prefix('sync')
    ->group(function () {
        Route::post('/initial', [SyncPullController::class, 'initial']);
    });

// Sync endpoints with farm.context (require farm_id)
Route::middleware(['auth:sanctum', 'farm.context'])
    ->prefix('sync')
    ->group(function () {
        Route::post('/push', [SyncPushController::class, 'push']);
        Route::post('/pull', [SyncPullController::class, 'pull']);
        Route::post('/verify-consistency', [SyncErrorsController::class, 'verifyConsistency']);
        Route::get('/errors', [SyncErrorsController::class, 'getSyncErrors']);
    });