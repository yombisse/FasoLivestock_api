<?php

use App\Http\Controllers\Api\CategorieController;

Route::middleware('auth:sanctum')->group(function () {

    // Lister toutes les catégories
    Route::get('categories', [CategorieController::class, 'index'])
        ->middleware('permission:categories.view');

    // Créer une catégorie
    Route::post('categories', [CategorieController::class, 'store'])
        ->middleware('permission:categories.create');

    // Détail d'une catégorie
    Route::get('categories/{categorie}', [CategorieController::class, 'show'])
        ->middleware('permission:categories.view');

    // Modifier une catégorie
    Route::put('categories/{categorie}', [CategorieController::class, 'update'])
        ->middleware('permission:categories.update');

    // Supprimer une catégorie
    Route::delete('categories/{categorie}', [CategorieController::class, 'destroy'])
        ->middleware('permission:categories.delete');

    // Liste des catégories supprimées
    Route::get('categories/trashed', [CategorieController::class, 'trashed'])
        ->middleware('permission:categories.view');

    // Restaurer une catégorie supprimée
    Route::post('categories/{id}/restore', [CategorieController::class, 'restore'])
        ->middleware('permission:categories.update');
});
