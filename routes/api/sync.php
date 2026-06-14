<?php

use App\Http\Controllers\Api\SyncController;

Route::middleware(['auth:sanctum', 'farm.context'])
    ->prefix('sync')
    ->group(function () {
        Route::post('/push', [SyncController::class, 'push']);
        Route::get('/pull', [SyncController::class, 'pull']);
    });