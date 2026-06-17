<?php

use App\Http\Controllers\Api\RoleController;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('roles/permissions', [RoleController::class, 'permissions'])
        ->middleware('permission:roles.view');

    Route::get('roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.view');

    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('permission:roles.create');

    Route::get('roles/{id}', [RoleController::class, 'show'])
        ->middleware('permission:roles.view');

    Route::put('roles/{id}', [RoleController::class, 'update'])
        ->middleware('permission:roles.update');

    Route::delete('roles/{id}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.delete');
});