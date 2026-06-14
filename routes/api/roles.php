<?php

use App\Http\Controllers\Api\RoleController;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('roles/permissions', [RoleController::class, 'permissions'])
        ->middleware('permission:roles.view');

    Route::get('roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.view');

    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('permission:roles.create');

    Route::get('roles/{role}', [RoleController::class, 'show'])
        ->middleware('permission:roles.view');

    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:roles.update');

    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.delete');
});