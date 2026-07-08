<?php

use App\Http\Controllers\Api\FinanceTransactionController;

Route::middleware(['auth:sanctum', 'farm.context'])->group(function () {
    // =========================================================
    // TRANSACTIONS (MVP)
    // =========================================================

    // Liste des transactions
    Route::get('transactions', [FinanceTransactionController::class, 'index'])
        ->middleware('permission:transactions.view');

    // Créer une transaction
    Route::post('transactions', [FinanceTransactionController::class, 'store'])
        ->middleware('permission:transactions.create');

    // Détail d'une transaction
    Route::get('transactions/{transaction}', [FinanceTransactionController::class, 'show'])
        ->middleware('permission:transactions.view');

    // Modifier une transaction
    Route::put('transactions/{transaction}', [FinanceTransactionController::class, 'update'])
        ->middleware('permission:transactions.update');

    // Supprimer une transaction
    Route::delete('transactions/{transaction}', [FinanceTransactionController::class, 'destroy'])
        ->middleware('permission:transactions.delete');

    // Liste des transactions supprimées
    Route::get('transactions/trashed', [FinanceTransactionController::class, 'trashed'])
        ->middleware('permission:transactions.view');

    // Restaurer une transaction supprimée
    Route::post('transactions/{id}/restore', [FinanceTransactionController::class, 'restore'])
        ->middleware('permission:transactions.update');

    // Bilan financier (revenus - dépenses)
    Route::get('transactions/bilan', [FinanceTransactionController::class, 'bilan'])
        ->middleware('permission:transactions.view');

    // Historique transactionnel d'un animal
    Route::get('transactions/animals/{animal}', [FinanceTransactionController::class, 'historiqueAnimal'])
        ->middleware('permission:transactions.view');
});

