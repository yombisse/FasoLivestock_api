<?php

/**
 * LEGACY: Ces endpoints REST sont destinés à être remplacés par le canal sync push/pull.
 * Le mobile doit n'utiliser que /sync/push et /sync/pull pour la création/modification/suppression.
 * Ces endpoints sont conservés temporairement pour l'admin web panel et seront restreints
 * au rôle admin-only après une période d'observation des logs (2 semaines recommandées).
 */

use App\Http\Controllers\Api\FinanceTransactionController;
use App\Http\Controllers\Api\FinanceRevenueController;
use App\Http\Controllers\Api\FinanceChargeController;
use App\Http\Controllers\Api\FinanceReportController;

Route::middleware(['auth:sanctum', 'farm.context'])->group(function () {

    // =========================================================
    // TRANSACTIONS
    // =========================================================

    // Liste des transactions
    Route::get('finance/transactions', [FinanceTransactionController::class, 'index'])
        ->middleware('permission:transactions.view');

    // Créer une transaction
    Route::post('finance/transactions', [FinanceTransactionController::class, 'store'])
        ->middleware('permission:transactions.create');

    // Détail d'une transaction
    Route::get('finance/transactions/{transaction}', [FinanceTransactionController::class, 'show'])
        ->middleware('permission:transactions.view');

    // Modifier une transaction
    Route::put('finance/transactions/{transaction}', [FinanceTransactionController::class, 'update'])
        ->middleware('permission:transactions.update');

    // Archiver une transaction
    Route::delete('finance/transactions/{transaction}', [FinanceTransactionController::class, 'destroy'])
        ->middleware('permission:transactions.delete');

    // Liste des transactions archivées
    Route::get('finance/transactions/trashed', [FinanceTransactionController::class, 'trashed'])
        ->middleware('permission:transactions.view');

    // Restaurer une transaction archivée
    Route::post('finance/transactions/{id}/restore', [FinanceTransactionController::class, 'restore'])
        ->middleware('permission:transactions.update');

    // =========================================================
    // REVENUS
    // =========================================================

    // Lister les revenus
    Route::get('finance/revenus', [FinanceRevenueController::class, 'index'])
        ->middleware('permission:transactions.view');

    // Obtenir les revenus par période
    Route::get('finance/revenus/par-periode', [FinanceRevenueController::class, 'parPeriode'])
        ->middleware('permission:transactions.view');

    // Obtenir les revenus par catégorie
    Route::get('finance/revenus/par-categorie', [FinanceRevenueController::class, 'parCategorie'])
        ->middleware('permission:transactions.view');

    // Obtenir les revenus par animal
    Route::get('finance/revenus/par-animal', [FinanceRevenueController::class, 'parAnimal'])
        ->middleware('permission:transactions.view');

    // =========================================================
    // CHARGES
    // =========================================================

    // Lister les charges
    Route::get('finance/charges', [FinanceChargeController::class, 'index'])
        ->middleware('permission:transactions.view');

    // Obtenir les charges par période
    Route::get('finance/charges/par-periode', [FinanceChargeController::class, 'parPeriode'])
        ->middleware('permission:transactions.view');

    // Obtenir les charges par catégorie
    Route::get('finance/charges/par-categorie', [FinanceChargeController::class, 'parCategorie'])
        ->middleware('permission:transactions.view');

    // =========================================================
    // BILAN FINANCIER
    // =========================================================

    // Obtenir le bilan financier
    Route::get('finance/bilan', [FinanceReportController::class, 'bilan'])
        ->middleware('permission:transactions.view');

    // Obtenir le bilan financier par période
    Route::get('finance/bilan/par-periode', [FinanceReportController::class, 'bilanParPeriode'])
        ->middleware('permission:transactions.view');

    // Obtenir le bilan financier par ferme
    Route::get('finance/bilan/par-ferme/{farmId}', [FinanceReportController::class, 'bilanParFerme'])
        ->middleware('permission:transactions.view');

    // Obtenir les statistiques globales financières
    Route::get('finance/statistiques-globales', [FinanceReportController::class, 'statistiquesGlobales'])
        ->middleware('permission:transactions.view');
});
