<?php

use App\Http\Controllers\Api\UserController;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('users', [UserController::class, 'index'])
        ->middleware('permission:users.view');

    Route::post('users', [UserController::class, 'store'])
        ->middleware('permission:users.create');

    Route::get('users/{user}', [UserController::class, 'show'])
        ->middleware('permission:users.view');

    Route::put('users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.update');

    Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
        ->middleware('permission:users.update');

    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete');

    Route::get('users/trashed', [UserController::class, 'trashed'])
        ->middleware('permission:users.view');

    Route::post('users/{id}/restore', [UserController::class, 'restore'])
        ->middleware('permission:users.update');

    // Liste des utilisateurs qui peuvent être propriétaires de ferme (superadmin ou avec rôle owner)
    Route::get('users/potential-owners', [UserController::class, 'potentialOwners'])
        ->middleware('permission:users.view');
});