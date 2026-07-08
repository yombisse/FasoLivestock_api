<?php

use App\Http\Controllers\Api\EvenementMouvementController;

Route::middleware(['auth:sanctum', 'farm.context'])->group(function () {

    // Liste des mouvements avec filtres
    Route::get('mouvements', [EvenementMouvementController::class, 'index'])
        ->middleware('permission:mouvements.view');

    // Détail d'un mouvement
    Route::get('mouvements/{evenement}', [EvenementMouvementController::class, 'show'])
        ->middleware('permission:mouvements.view');

    // IMPORTANT: création / modification / suppression volontairement désactivées côté API
    // Les mouvements doivent être générés par les observateurs / autres modules (traçabilité).

    // (Routes POST/PUT/DELETE laissées intentionnellement absentes)


    // Historique des mouvements d'un animal
    Route::get('animals/{animal}/mouvements', [EvenementMouvementController::class, 'animalHistory'])
        ->middleware('permission:mouvements.view');

    // Traçabilité complète d'un animal
    Route::get('mouvements/trace/{animal}', [EvenementMouvementController::class, 'trace'])
        ->middleware('permission:mouvements.view');

    // Statistiques des mouvements
    Route::get('mouvements/statistiques', [EvenementMouvementController::class, 'statistiques'])
        ->middleware('permission:mouvements.view');
});
