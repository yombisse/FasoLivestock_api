<?php

use App\Http\Controllers\Api\EvenementReproductionController;
use App\Http\Controllers\Api\NaissanceController;
use App\Http\Controllers\Api\ReproductionController;

Route::middleware(['auth:sanctum', 'farm.context'])->group(function () {

    // =========================================================
    // ÉVÉNEMENTS REPRODUCTION
    // =========================================================

    // Liste des événements de reproduction
    Route::get('reproduction/evenements', [EvenementReproductionController::class, 'index'])
        ->middleware('permission:reproduction.view');

    // Créer un événement de reproduction
    Route::post('reproduction/evenements', [EvenementReproductionController::class, 'store'])
        ->middleware('permission:reproduction.create');

    // Détail d'un événement de reproduction
    Route::get('reproduction/evenements/{evenement}', [EvenementReproductionController::class, 'show'])
        ->middleware('permission:reproduction.view');

    // Modifier un événement de reproduction
    Route::put('reproduction/evenements/{evenement}', [EvenementReproductionController::class, 'update'])
        ->middleware('permission:reproduction.update');

    // Archiver un événement de reproduction
    Route::delete('reproduction/evenements/{evenement}', [EvenementReproductionController::class, 'destroy'])
        ->middleware('permission:reproduction.delete');

    // Liste des événements de reproduction archivés
    Route::get('reproduction/evenements/trashed', [EvenementReproductionController::class, 'trashed'])
        ->middleware('permission:reproduction.view');

    // Restaurer un événement de reproduction archivé
    Route::post('reproduction/evenements/{id}/restore', [EvenementReproductionController::class, 'restore'])
        ->middleware('permission:reproduction.update');

    // =========================================================
    // NAISSANCES
    // =========================================================

    // Liste des naissances
    Route::get('reproduction/naissances', [NaissanceController::class, 'index'])
        ->middleware('permission:reproduction.view');

    // Créer une naissance
    Route::post('reproduction/naissances', [NaissanceController::class, 'store'])
        ->middleware('permission:reproduction.create');

    // Détail d'une naissance
    Route::get('reproduction/naissances/{naissance}', [NaissanceController::class, 'show'])
        ->middleware('permission:reproduction.view');

    // Modifier une naissance
    Route::put('reproduction/naissances/{naissance}', [NaissanceController::class, 'update'])
        ->middleware('permission:reproduction.update');

    // Archiver une naissance
    Route::delete('reproduction/naissances/{naissance}', [NaissanceController::class, 'destroy'])
        ->middleware('permission:reproduction.delete');

    // Liste des naissances archivées
    Route::get('reproduction/naissances/trashed', [NaissanceController::class, 'trashed'])
        ->middleware('permission:reproduction.view');

    // Restaurer une naissance archivée
    Route::post('reproduction/naissances/{id}/restore', [NaissanceController::class, 'restore'])
        ->middleware('permission:reproduction.update');

    // Prévisions de mises bas
    Route::get('reproduction/naissances/previsions', [NaissanceController::class, 'previsions'])
        ->middleware('permission:reproduction.view');

    // =========================================================
    // PRÉVISIONS & STATISTIQUES
    // =========================================================

    // Dashboard de reproduction
    Route::get('reproduction/dashboard', [ReproductionController::class, 'dashboard'])
        ->middleware('permission:reproduction.view');

    // Prévisions de reproduction
    Route::get('reproduction/forecast', [ReproductionController::class, 'forecast'])
        ->middleware('permission:reproduction.view');

    // Historique reproductif d'un animal
    Route::get('reproduction/animals/{animal}/historique', [ReproductionController::class, 'historiqueAnimal'])
        ->middleware('permission:reproduction.view');

    // Statistiques reproductives d'un animal
    Route::get('reproduction/animals/{animal}/stats', [ReproductionController::class, 'statistiquesAnimal'])
        ->middleware('permission:reproduction.view');
});
