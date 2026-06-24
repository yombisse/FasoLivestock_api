<?php

use App\Http\Controllers\Api\StatisticsController;

Route::middleware(['auth:sanctum', 'farm.context'])->group(function () {

    // Tableau de bord graphique (tous les graphiques)
    Route::get('statistics/dashboard-charts', [StatisticsController::class, 'dashboardCharts'])
        ->middleware('permission:statistics.view');

    // =========================================================
    // GRAPHIQUES FINANCIERS
    // =========================================================

    // Évolution financière (ligne)
    Route::get('statistics/financial-evolution', [StatisticsController::class, 'financialEvolution'])
        ->middleware('permission:statistics.view');

    // Revenus par catégorie (doughnut)
    Route::get('statistics/revenue-by-category', [StatisticsController::class, 'revenueByCategory'])
        ->middleware('permission:statistics.view');

    // Charges par catégorie (doughnut)
    Route::get('statistics/expense-by-category', [StatisticsController::class, 'expenseByCategory'])
        ->middleware('permission:statistics.view');

    // =========================================================
    // GRAPHIQUES CHEPTEL
    // =========================================================

    // Cheptel par espèce (pie)
    Route::get('statistics/herd-by-species', [StatisticsController::class, 'herdBySpecies'])
        ->middleware('permission:statistics.view');

    // Cheptel par sexe (bar)
    Route::get('statistics/herd-by-sex', [StatisticsController::class, 'herdBySex'])
        ->middleware('permission:statistics.view');

    // =========================================================
    // GRAPHIQUES MOUVEMENTS
    // =========================================================

    // Statistiques des mouvements (bar)
    Route::get('statistics/movements-stats', [StatisticsController::class, 'movementsStats'])
        ->middleware('permission:statistics.view');

    // =========================================================
    // GRAPHIQUES SANTÉ
    // =========================================================

    // Évolution des événements sanitaires (ligne)
    Route::get('statistics/health-events-evolution', [StatisticsController::class, 'healthEventsEvolution'])
        ->middleware('permission:statistics.view');

    // =========================================================
    // GRAPHIQUES REPRODUCTION
    // =========================================================

    // Statistiques de reproduction (bar)
    Route::get('statistics/reproduction-stats', [StatisticsController::class, 'reproductionStats'])
        ->middleware('permission:statistics.view');

    // Naissances par mois (ligne)
    Route::get('statistics/births-by-month', [StatisticsController::class, 'birthsByMonth'])
        ->middleware('permission:statistics.view');
});
