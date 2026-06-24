<?php

use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'farm.context'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/non-lues', [NotificationController::class, 'nonLues']);
    Route::post('/notifications/tout-lire', [NotificationController::class, 'marquerToutLu']);
    Route::post('/notifications/generer-alertes', [NotificationController::class, 'genererAlertes']);
    Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
    Route::post('/notifications/{notification}/lire', [NotificationController::class, 'marquerLu']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
});
