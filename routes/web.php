<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\FarmController;
use App\Http\Controllers\Admin\RoleUserController;

Route::prefix('admin')->name('admin.')->group(function () {

    // ─── Routes publiques (sans auth) ────────────────────────
    Route::middleware('guest.admin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])
            ->name('login');
        Route::post('/login', [AuthController::class, 'login'])
            ->name('login.post');
        Route::get('/register', [AuthController::class, 'showRegisterForm'])
            ->name('register');
        Route::post('/register', [AuthController::class, 'register'])
            ->name('register.submit');
        Route::get('/verify-2fa', [AuthController::class, 'show2fa'])
            ->name('show2fa');
        Route::post('/verify-2fa', [AuthController::class, 'verify2fa'])
            ->name('verify2fa');
    });

    // ─── Routes protégées (avec auth) ────────────────────────
    Route::middleware('admin.auth')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        // Redirect /admin → dashboard
        Route::get('/', fn() => redirect()->route('admin.dashboard'));

        // ─── Utilisateurs ─────────────────────────────────────
        Route::get('/users/trashed', [UserController::class, 'trashed'])
            ->name('users.trashed');
        Route::patch('/users/{id}/restore', [UserController::class, 'restore'])
            ->name('users.restore');
        Route::patch('/users/{id}/toggle-active', [UserController::class, 'toggleActive'])
            ->name('users.toggle-active');
        Route::resource('users', UserController::class);

        // ─── Rôles ────────────────────────────────────────────
        Route::get('/roles/trashed', [RoleController::class, 'trashed'])
            ->name('roles.trashed');
        Route::patch('/roles/{id}/restore', [RoleController::class, 'restore'])
            ->name('roles.restore');
        Route::resource('roles', RoleController::class);

        // ─── Role ↔ Users (AJAX) ──────────────────────────────
        Route::get('/role-users/{roleId}/users', [RoleUserController::class, 'users'])
            ->name('role-users.users');
        Route::post('/role-users/{roleId}/attach', [RoleUserController::class, 'attach'])
            ->name('role-users.attach');
        Route::post('/role-users/{roleId}/detach/{userId}', [RoleUserController::class, 'detach'])
            ->name('role-users.detach');

        // ─── Fermes ───────────────────────────────────────────
        Route::get('/farms/trashed', [FarmController::class, 'trashed'])
            ->name('farms.trashed');
        Route::patch('/farms/{id}/restore', [FarmController::class, 'restore'])
            ->name('farms.restore');
        Route::post('/farms/{id}/users', [FarmController::class, 'manageUsers'])
            ->name('farms.users.manage');
        Route::delete('/farms/{id}/users/{userId}', [FarmController::class, 'removeUser'])
            ->name('farms.users.remove');
        Route::resource('farms', FarmController::class);
    });
});

// Redirect racine → admin
Route::get('/', fn() => redirect('/admin/dashboard'));