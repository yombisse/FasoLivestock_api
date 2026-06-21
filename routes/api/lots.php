<?php

use App\Http\Controllers\Api\LotController;

Route::middleware(['auth:sanctum', 'farm.context'])->group(function () {

    // Liste des lots
    Route::get('lots', [LotController::class, 'index'])
        ->middleware('permission:lots.view');

    // Créer un lot
    Route::post('lots', [LotController::class, 'store'])
        ->middleware('permission:lots.create');

    // Détail d'un lot
    Route::get('lots/{lot}', [LotController::class, 'show'])
        ->middleware('permission:lots.view');

    // Modifier un lot
    Route::put('lots/{lot}', [LotController::class, 'update'])
        ->middleware('permission:lots.update');

    // Archiver un lot
    Route::delete('lots/{lot}', [LotController::class, 'destroy'])
        ->middleware('permission:lots.delete');

    // Liste des lots archivés
    Route::get('lots/trashed', [LotController::class, 'trashed'])
        ->middleware('permission:lots.view');

    // Restaurer un lot archivé
    Route::post('lots/{id}/restore', [LotController::class, 'restore'])
        ->middleware('permission:lots.update');

    // Liste des animaux d'un lot
    Route::get('lots/{lot}/animals', [LotController::class, 'animals'])
        ->middleware('permission:lots.view');

    // Affecter des animaux à un lot
    Route::post('lots/{lot}/animals', [LotController::class, 'assignAnimals'])
        ->middleware('permission:lots.update');

    // Retirer un animal d'un lot
    Route::delete('lots/{lot}/animals/{animalId}', [LotController::class, 'removeAnimal'])
        ->middleware('permission:lots.update');

    // Statistiques d'un lot
    Route::get('lots/{lot}/stats', [LotController::class, 'statistiques'])
        ->middleware('permission:lots.view');
});
