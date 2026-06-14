<?php

use App\Http\Controllers\Api\FarmController;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('farms/trashed', [FarmController::class, 'trashed'])
        ->middleware('permission:farms.view');

    Route::get('farms', [FarmController::class, 'index'])
        ->middleware('permission:farms.view');

    Route::post('farms', [FarmController::class, 'store'])
        ->middleware('permission:farms.create');

    Route::get('farms/{farm}', [FarmController::class, 'show'])
        ->middleware('permission:farms.view');

    Route::put('farms/{farm}', [FarmController::class, 'update'])
        ->middleware('permission:farms.update');

    Route::delete('farms/{farm}', [FarmController::class, 'destroy'])
        ->middleware('permission:farms.delete');

    Route::patch('farms/{farm}/restore', [FarmController::class, 'restore'])
        ->middleware('permission:farms.update');

    // ─── Gestion des membres ──────────────────────────────────
    Route::post('farms/{farm}/users', [FarmController::class, 'manageUsers'])
        ->middleware('permission:farms.update');

    Route::delete('farms/{farm}/users/{user}', [FarmController::class, 'removeUser'])
        ->middleware('permission:farms.update');
});