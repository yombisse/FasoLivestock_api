<?php

use App\Http\Controllers\Api\AnimalController;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('animals', [AnimalController::class, 'index'])
        ->middleware('permission:animals.view');

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
});
