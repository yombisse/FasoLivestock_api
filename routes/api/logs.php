<?php

use App\Http\Controllers\Api\ActivityLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:logs.view'])->group(function () {
    Route::get('/logs', [ActivityLogController::class, 'index']);
    Route::get('/logs/{modelType}', [ActivityLogController::class, 'parModele']);
});
