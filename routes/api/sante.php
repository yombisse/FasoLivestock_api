<?php

/**
 * LEGACY: Ces endpoints REST sont destinés à être remplacés par le canal sync push/pull.
 * Le mobile doit n'utiliser que /sync/push et /sync/pull pour la création/modification/suppression.
 * Ces endpoints sont conservés temporairement pour l'admin web panel et seront restreints
 * au rôle admin-only après une période d'observation des logs (2 semaines recommandées).
 */

use App\Http\Controllers\Api\SanteRappelController;
use App\Http\Controllers\Api\SanteAnimalController;
use App\Http\Controllers\Api\SanteEvenementController;

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

    // =========================================================
    // ÉVÉNEMENTS SANITAIRES (Vaccinations, Traitements, Maladies, Consultations)
    // =========================================================

    // Liste des événements sanitaires
    Route::get('sante/evenements', [SanteEvenementController::class, 'index'])
        ->middleware('permission:evenements.view');

    // Créer un événement sanitaire
    Route::post('sante/evenements', [SanteEvenementController::class, 'store'])
        ->middleware('permission:evenements.create');

    // Détail d'un événement sanitaire
    Route::get('sante/evenements/{evenement}', [SanteEvenementController::class, 'show'])
        ->middleware('permission:evenements.view');

    // Modifier un événement sanitaire
    Route::put('sante/evenements/{evenement}', [SanteEvenementController::class, 'update'])
        ->middleware('permission:evenements.update');

    // Supprimer un événement sanitaire
    Route::delete('sante/evenements/{evenement}', [SanteEvenementController::class, 'destroy'])
        ->middleware('permission:evenements.delete');

    // =========================================================
    // ÉVÉNEMENTS PAR TYPE (PAR ANIMAL)
    // =========================================================

    // Vaccinations d'un animal
    Route::get('sante/animals/{animal}/vaccinations', [SanteEvenementController::class, 'vaccinationsAnimal'])
        ->middleware('permission:sante.view');

    // Traitements d'un animal
    Route::get('sante/animals/{animal}/traitements', [SanteEvenementController::class, 'traitementsAnimal'])
        ->middleware('permission:sante.view');

    // Maladies d'un animal
    Route::get('sante/animals/{animal}/maladies', [SanteEvenementController::class, 'maladiesAnimal'])
        ->middleware('permission:sante.view');

    // Consultations d'un animal
    Route::get('sante/animals/{animal}/consultations', [SanteEvenementController::class, 'consultationsAnimal'])
        ->middleware('permission:sante.view');

    // Statistiques sanitaires par type pour la ferme
    Route::get('sante/statistiques-par-type', [SanteEvenementController::class, 'statistiquesParType'])
        ->middleware('permission:sante.view');
});
