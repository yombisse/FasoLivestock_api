<?php

use App\Http\Controllers\Api\MouvementController;

Route::middleware(['auth:sanctum', 'farm.context'])->group(function () {

    // Liste des mouvements avec filtres
    Route::get('mouvements', [MouvementController::class, 'index'])
        ->middleware('permission:mouvements.view');

    // Créer un mouvement
    Route::post('mouvements', [MouvementController::class, 'store'])
        ->middleware('permission:mouvements.create');

    // Détail d'un mouvement
    Route::get('mouvements/{evenement}', [MouvementController::class, 'show'])
        ->middleware('permission:mouvements.view');

    // Modifier un mouvement
    Route::put('mouvements/{evenement}', [MouvementController::class, 'update'])
        ->middleware('permission:mouvements.update');

    // Archiver un mouvement
    Route::delete('mouvements/{evenement}', [MouvementController::class, 'destroy'])
        ->middleware('permission:mouvements.delete');

    // Historique des mouvements d'un animal
    Route::get('animals/{animal}/mouvements', [MouvementController::class, 'animalHistory'])
        ->middleware('permission:mouvements.view');

    // Traçabilité complète d'un animal
    Route::get('mouvements/trace/{animal}', [MouvementController::class, 'trace'])
        ->middleware('permission:mouvements.view');

    // Statistiques des mouvements
    Route::get('mouvements/statistiques', [MouvementController::class, 'statistiques'])
        ->middleware('permission:mouvements.view');
});
