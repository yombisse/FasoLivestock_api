<?php

use App\Http\Controllers\Api\SanteRappelController;
use App\Http\Controllers\Api\SanteAnimalController;

Route::middleware(['auth:sanctum', 'farm.context'])->group(function () {

    // =========================================================
    // RAPPELS SANITAIRES
    // =========================================================

    // Liste des rappels sanitaires
    Route::get('sante/rappels', [SanteRappelController::class, 'index'])
        ->middleware('permission:sante.view');

    // Créer un rappel sanitaire
    Route::post('sante/rappels', [SanteRappelController::class, 'store'])
        ->middleware('permission:sante.create');

    // Détail d'un rappel sanitaire
    Route::get('sante/rappels/{rappel}', [SanteRappelController::class, 'show'])
        ->middleware('permission:sante.view');

    // Modifier un rappel sanitaire
    Route::put('sante/rappels/{rappel}', [SanteRappelController::class, 'update'])
        ->middleware('permission:sante.update');

    // Archiver un rappel sanitaire
    Route::delete('sante/rappels/{rappel}', [SanteRappelController::class, 'destroy'])
        ->middleware('permission:sante.delete');

    // Liste des rappels sanitaires archivés
    Route::get('sante/rappels/trashed', [SanteRappelController::class, 'trashed'])
        ->middleware('permission:sante.view');

    // Restaurer un rappel sanitaire archivé
    Route::post('sante/rappels/{id}/restore', [SanteRappelController::class, 'restore'])
        ->middleware('permission:sante.update');

    // Rappels sanitaires à venir
    Route::get('sante/rappels/a-venir', [SanteRappelController::class, 'aVenir'])
        ->middleware('permission:sante.view');

    // Rappels sanitaires en retard
    Route::get('sante/rappels/en-retard', [SanteRappelController::class, 'enRetard'])
        ->middleware('permission:sante.view');

    // Marquer un rappel comme réalisé
    Route::post('sante/rappels/{rappel}/marquer-realise', [SanteRappelController::class, 'marquerRealise'])
        ->middleware('permission:sante.update');

    // =========================================================
    // HISTORIQUE MÉDICAL & STATISTIQUES
    // =========================================================

    // Historique médical d'un animal
    Route::get('sante/animals/{animal}/historique-medical', [SanteAnimalController::class, 'historiqueMedical'])
        ->middleware('permission:sante.view');

    // Statistiques sanitaires d'un animal
    Route::get('sante/animals/{animal}/statistiques-sanitaires', [SanteAnimalController::class, 'statistiquesSanitaires'])
        ->middleware('permission:sante.view');

    // Résumé sanitaire de la ferme courante
    Route::get('sante/resume-ferme', [SanteAnimalController::class, 'resumeFerme'])
        ->middleware('permission:sante.view');

    // Alertes sanitaires de la ferme courante
    Route::get('sante/alertes-ferme', [SanteAnimalController::class, 'alertesFerme'])
        ->middleware('permission:sante.view');
});
