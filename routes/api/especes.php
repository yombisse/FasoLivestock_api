<?php

use App\Http\Controllers\Api\EspeceController;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('especes', [EspeceController::class, 'index'])
        ->middleware('permission:especes.view');

    Route::post('especes', [EspeceController::class, 'store'])
        ->middleware('permission:especes.create');

    Route::get('especes/{espece}', [EspeceController::class, 'show'])
        ->middleware('permission:especes.view');

    Route::put('especes/{espece}', [EspeceController::class, 'update'])
        ->middleware('permission:especes.update');

    Route::delete('especes/{espece}', [EspeceController::class, 'destroy'])
        ->middleware('permission:especes.delete');

    Route::get('especes/trashed', [EspeceController::class, 'trashed'])
        ->middleware('permission:especes.view');

    Route::post('especes/{id}/restore', [EspeceController::class, 'restore'])
        ->middleware('permission:especes.update');

    // Paramètres biologiques
    Route::get('especes/{espece}/parametres', [EspeceController::class, 'showParametres'])
        ->middleware('permission:especes.view');

    Route::put('especes/{espece}/parametres', [EspeceController::class, 'updateParametres'])
        ->middleware('permission:especes.update');
});
