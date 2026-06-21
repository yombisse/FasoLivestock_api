<?php

use App\Http\Controllers\Api\AlimentController;
use App\Http\Controllers\Api\RationController;

Route::middleware(['auth:sanctum', 'farm.context'])->group(function () {

    // =========================================================
    // ALIMENTS
    // =========================================================

    // Liste des aliments
    Route::get('alimentation/aliments', [AlimentController::class, 'index'])
        ->middleware('permission:alimentation.view');

    // Créer un aliment
    Route::post('alimentation/aliments', [AlimentController::class, 'store'])
        ->middleware('permission:alimentation.create');

    // Détail d'un aliment
    Route::get('alimentation/aliments/{aliment}', [AlimentController::class, 'show'])
        ->middleware('permission:alimentation.view');

    // Modifier un aliment
    Route::put('alimentation/aliments/{aliment}', [AlimentController::class, 'update'])
        ->middleware('permission:alimentation.update');

    // Archiver un aliment
    Route::delete('alimentation/aliments/{aliment}', [AlimentController::class, 'destroy'])
        ->middleware('permission:alimentation.delete');

    // Liste des aliments archivés
    Route::get('alimentation/aliments/trashed', [AlimentController::class, 'trashed'])
        ->middleware('permission:alimentation.view');

    // Restaurer un aliment archivé
    Route::post('alimentation/aliments/{id}/restore', [AlimentController::class, 'restore'])
        ->middleware('permission:alimentation.update');

    // Approvisionner le stock d'un aliment
    Route::post('alimentation/aliments/{aliment}/approvisionner', [AlimentController::class, 'approvisionner'])
        ->middleware('permission:alimentation.update');

    // Ajuster manuellement le stock d'un aliment
    Route::post('alimentation/aliments/{aliment}/ajuster-stock', [AlimentController::class, 'ajusterStock'])
        ->middleware('permission:alimentation.update');

    // Obtenir les aliments en rupture de stock
    Route::get('alimentation/aliments/en-rupture', [AlimentController::class, 'enRupture'])
        ->middleware('permission:alimentation.view');

    // Obtenir l'historique des mouvements de stock d'un aliment
    Route::get('alimentation/aliments/{aliment}/historique-stock', [AlimentController::class, 'historiqueStock'])
        ->middleware('permission:alimentation.view');

    // Obtenir les statistiques d'utilisation d'un aliment
    Route::get('alimentation/aliments/{aliment}/statistiques', [AlimentController::class, 'statistiques'])
        ->middleware('permission:alimentation.view');

    // =========================================================
    // RATIONS
    // =========================================================

    // Liste des rations
    Route::get('alimentation/rations', [RationController::class, 'index'])
        ->middleware('permission:alimentation.view');

    // Créer une ration
    Route::post('alimentation/rations', [RationController::class, 'store'])
        ->middleware('permission:alimentation.create');

    // Détail d'une ration
    Route::get('alimentation/rations/{ration}', [RationController::class, 'show'])
        ->middleware('permission:alimentation.view');

    // Modifier une ration
    Route::put('alimentation/rations/{ration}', [RationController::class, 'update'])
        ->middleware('permission:alimentation.update');

    // Archiver une ration
    Route::delete('alimentation/rations/{ration}', [RationController::class, 'destroy'])
        ->middleware('permission:alimentation.delete');

    // Liste des rations archivées
    Route::get('alimentation/rations/trashed', [RationController::class, 'trashed'])
        ->middleware('permission:alimentation.view');

    // Restaurer une ration archivée
    Route::post('alimentation/rations/{id}/restore', [RationController::class, 'restore'])
        ->middleware('permission:alimentation.update');

    // Distribuer une ration à un lot
    Route::post('alimentation/rations/distribuer-lot', [RationController::class, 'distribuerLot'])
        ->middleware('permission:alimentation.create');

    // Distribuer une ration à plusieurs animaux
    Route::post('alimentation/rations/distribuer-animaux', [RationController::class, 'distribuerAnimaux'])
        ->middleware('permission:alimentation.create');

    // Obtenir l'historique alimentaire d'un animal
    Route::get('alimentation/animals/{animal}/historique-alimentaire', [RationController::class, 'historiqueAnimal'])
        ->middleware('permission:alimentation.view');

    // Obtenir l'historique alimentaire d'un lot
    Route::get('alimentation/lots/{lot}/historique-alimentaire', [RationController::class, 'historiqueLot'])
        ->middleware('permission:alimentation.view');

    // Obtenir la consommation totale d'un animal
    Route::get('alimentation/animals/{animal}/consommation', [RationController::class, 'consommationAnimal'])
        ->middleware('permission:alimentation.view');

    // Obtenir la consommation totale d'un lot
    Route::get('alimentation/lots/{lot}/consommation', [RationController::class, 'consommationLot'])
        ->middleware('permission:alimentation.view');

    // Obtenir les statistiques globales d'alimentation pour la ferme courante
    Route::get('alimentation/statistiques-globales', [RationController::class, 'statistiquesGlobales'])
        ->middleware('permission:alimentation.view');
});
