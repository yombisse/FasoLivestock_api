<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SyncController;

Route::prefix('auth')->middleware('throttle:60,1')->group(function () {

    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

    Route::post('/verify-2fa', [AuthController::class, 'verify2fa'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/me', [AuthController::class, 'me']);
   });
});

Route::middleware('auth:sanctum')->prefix('sync')->group(function () {
    Route::post('/push', [SyncController::class, 'push'])->middleware('farm.context');
    Route::get('/pull', [SyncController::class, 'pull'])->middleware('farm.context');
});