<?php

use App\Http\Controllers\Api\DashboardController;

Route::middleware(['auth:sanctum'])->group(function () {

    // Statistiques globales (toutes fermes)
    Route::get('dashboard/global', [DashboardController::class, 'globalStats'])
        ->middleware('permission:dashboard.view');

    // Stats simplifiées pour dashboard admin (4 cartes)
    Route::get('dashboard/admin-stats', [DashboardController::class, 'adminStats'])
        ->middleware('permission:dashboard.view');

    // Tableau de bord pour une ferme (accepte farm_id en paramètre ou utilise la ferme courante)
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view');
});
