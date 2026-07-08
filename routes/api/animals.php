<?php

/**
 * LEGACY: Ces endpoints REST sont destinés à être remplacés par le canal sync push/pull.
 * Le mobile doit n'utiliser que /sync/push et /sync/pull pour la création/modification/suppression.
 * Ces endpoints sont conservés temporairement pour l'admin web panel et seront restreints
 * au rôle admin-only après une période d'observation des logs (2 semaines recommandées).
 */

use App\Http\Controllers\Api\AnimalController;

Route::middleware(['auth:sanctum', 'farm.context'])->group(function () {

    Route::get('animals', [AnimalController::class, 'index'])
        ->middleware('permission:animals.view');

    Route::post('animals/import', [AnimalController::class, 'importBatch'])
        ->middleware('permission:animals.import');

    Route::post('animals', [AnimalController::class, 'store'])
        ->middleware('permission:animals.create');

    Route::get('animals/{animal}', [AnimalController::class, 'show'])
        ->middleware('permission:animals.view');

    Route::put('animals/{animal}', [AnimalController::class, 'update'])
        ->middleware('permission:animals.update');

    Route::delete('animals/{animal}', [AnimalController::class, 'destroy'])
        ->middleware('permission:animals.delete');

    Route::get('animals/trashed', [AnimalController::class, 'trashed'])
        ->middleware('permission:animals.view');

    Route::post('animals/{id}/restore', [AnimalController::class, 'restore'])
        ->middleware('permission:animals.update');

    // =========================================================
    // ACTIONS MÉTIER (MOUVEMENTS)
    // =========================================================

    Route::post('animals/purchase', [AnimalController::class, 'purchase'])
        ->middleware('permission:animals.create');

    Route::post('animals/birth', [AnimalController::class, 'birth'])
        ->middleware('permission:animals.create');

    Route::post('animals/{animal}/sell', [AnimalController::class, 'sell'])
        ->middleware('permission:animals.update');

    Route::post('animals/{animal}/transfer', [AnimalController::class, 'transfer'])
        ->middleware('permission:animals.update');

    Route::post('animals/{animal}/declare-death', [AnimalController::class, 'declareDeath'])
        ->middleware('permission:animals.update');

    Route::post('animals/{animal}/declare-loss', [AnimalController::class, 'declareLoss'])
        ->middleware('permission:animals.update');

    Route::post('animals/{animal}/slaughter', [AnimalController::class, 'slaughter'])
        ->middleware('permission:animals.update');

    Route::post('animals/lots/{lot}/sell', [AnimalController::class, 'sellLot'])
        ->middleware('permission:animals.update');
});
