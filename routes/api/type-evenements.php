<?php

use App\Http\Controllers\Api\TypeEvenementController;

Route::middleware('auth:sanctum')->group(function () {

    // Lister tous les types d'événements
    Route::get('type-evenements', [TypeEvenementController::class, 'index']);

    // Créer un type d'événement (admin only)
    Route::post('type-evenements', [TypeEvenementController::class, 'store'])
        ->middleware('permission:type_evenements.create');

    // Détail d'un type d'événement
    Route::get('type-evenements/{typeEvenement}', [TypeEvenementController::class, 'show']);

    // Modifier un type d'événement (admin only)
    Route::put('type-evenements/{typeEvenement}', [TypeEvenementController::class, 'update'])
        ->middleware('permission:type_evenements.update');

    // Supprimer un type d'événement (admin only)
    Route::delete('type-evenements/{typeEvenement}', [TypeEvenementController::class, 'destroy'])
        ->middleware('permission:type_evenements.delete');
});
