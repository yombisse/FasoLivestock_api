<?php

use App\Http\Controllers\Api\SyncController;

// Initial sync - NO farm.context middleware (solves chicken-and-egg problem)
Route::middleware(['auth:sanctum'])
    ->prefix('sync')
    ->group(function () {
        Route::post('/initial', [SyncController::class, 'initial']);
    });

// Sync endpoints with farm.context (require farm_id)
Route::middleware(['auth:sanctum', 'farm.context'])
    ->prefix('sync')
    ->group(function () {
        Route::post('/push', [SyncController::class, 'push']);
        Route::post('/pull', [SyncController::class, 'pull']);
        Route::post('/verify-consistency', [SyncController::class, 'verifyConsistency']);
    });