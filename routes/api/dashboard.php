<?php

use App\Http\Controllers\Api\DashboardController;

Route::middleware(['auth:sanctum'])->group(function () {

    // Statistiques globales (toutes fermes) - pas besoin de farm.context
    Route::get('dashboard/global', [DashboardController::class, 'globalStats'])
        ->middleware('permission:dashboard.view');
});

Route::middleware(['auth:sanctum', 'farm.context'])->group(function () {

    // Tableau de bord global pour la ferme courante
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view');
});
