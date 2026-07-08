<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\FarmController;
use App\Http\Controllers\Admin\RoleUserController;
use App\Http\Controllers\Admin\AnimalController;
use App\Http\Controllers\Admin\EspeceController;
use App\Http\Controllers\Admin\LotController;
use App\Http\Controllers\Admin\AlimentController;
use App\Http\Controllers\Admin\RationController;
use App\Http\Controllers\Admin\SanteAnimalController;
use App\Http\Controllers\Admin\SanteRappelController;
use App\Http\Controllers\Admin\ReproductionController;
use App\Http\Controllers\Admin\EvenementReproductionController;
use App\Http\Controllers\Admin\NaissanceController;
use App\Http\Controllers\Admin\MouvementController;
use App\Http\Controllers\Admin\FinanceTransactionController;
use App\Http\Controllers\Admin\SanteEvenementController;

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
        Route::get('/users/search', [UserController::class, 'search'])
            ->name('users.search');
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

        // Administration ferme
        Route::get('/farms/{id}/manage', [FarmController::class, 'manage'])
            ->name('farms.manage');
        Route::post('/farms/{id}/members', [FarmController::class, 'addMembers'])
            ->name('farms.members.add');
        Route::post('/farms/{id}/members/update-role', [FarmController::class, 'updateRole'])
            ->name('farms.members.update-role');
        Route::delete('/farms/{farmId}/members/{userId}', [FarmController::class, 'removeMember'])
            ->name('farms.members.remove');

        Route::resource('farms', FarmController::class);

        // ─── Animaux ───────────────────────────────────────────
        // IMPORTANT : toutes les routes statiques doivent être déclarées
        // AVANT Route::resource() pour ne pas être capturées par les
        // routes paramétrées {animal} du resource controller.
        Route::get('/animals/trashed', [AnimalController::class, 'trashed'])
            ->name('admin.animals.trashed');
        Route::patch('/animals/{id}/restore', [AnimalController::class, 'restore'])
            ->name('admin.animals.restore');
        Route::get('/animals/lots-by-farm', [AnimalController::class, 'getLotsByFarm'])
            ->name('admin.animals.lots-by-farm');
        Route::get('/animals/mothers', [AnimalController::class, 'getMothers'])
            ->name('admin.animals.mothers');
        // Routes dédiées aux formulaires de création
        Route::get('/animals/create-achat', [AnimalController::class, 'createAchat'])
            ->name('admin.animals.create-achat');
        Route::get('/animals/create-naissance', [AnimalController::class, 'createNaissance'])
            ->name('admin.animals.create-naissance');
        // Route dédiée à l'achat (POST sans {animal}) — doit précéder resource()
        Route::post('/animals/purchase', [AnimalController::class, 'purchase'])
            ->name('admin.animals.purchase');
        // Route dédiée à la naissance (POST sans {animal}) — doit précéder resource()
        Route::post('/animals/naissance', [AnimalController::class, 'naissance'])
            ->name('admin.animals.naissance');
        // Routes dédiées à l'import cheptel
        Route::get('/animals/import', [AnimalController::class, 'importWizard'])
            ->name('admin.animals.import');
        Route::post('/animals/import', [AnimalController::class, 'importStore'])
            ->name('admin.animals.import.store');
        Route::resource('animals', AnimalController::class);

        // ─── Espèces ──────────────────────────────────────────
        Route::get('/especes/trashed', [EspeceController::class, 'trashed'])
            ->name('especes.trashed');
        Route::patch('/especes/{id}/restore', [EspeceController::class, 'restore'])
            ->name('especes.restore');
        Route::get('/especes/{id}/parametres', [EspeceController::class, 'editParametres'])
            ->name('especes.parametres');
        Route::put('/especes/{id}/parametres', [EspeceController::class, 'updateParametres'])
            ->name('especes.parametres.update');
        Route::resource('especes', EspeceController::class);

        // ─── Lots ─────────────────────────────────────────────
        Route::get('/lots/trashed', [LotController::class, 'trashed'])
            ->name('lots.trashed');
        Route::patch('/lots/{id}/restore', [LotController::class, 'restore'])
            ->name('lots.restore');
        Route::get('/lots/{id}/assign', [LotController::class, 'assign'])
            ->name('lots.assign');
        Route::post('/lots/{id}/assign', [LotController::class, 'storeAssign'])
            ->name('lots.storeAssign');
        Route::delete('/lots/{lotId}/animals/{animalId}', [LotController::class, 'removeAnimal'])
            ->name('lots.removeAnimal');
        Route::resource('lots', LotController::class);

        // ─── Aliments ─────────────────────────────────────────
        Route::get('/aliments/trashed', [AlimentController::class, 'trashed'])
            ->name('aliments.trashed');
        Route::patch('/aliments/{id}/restore', [AlimentController::class, 'restore'])
            ->name('aliments.restore');
        Route::resource('aliments', AlimentController::class);

        // ─── Rations ──────────────────────────────────────────
        Route::get('/rations/trashed', [RationController::class, 'trashed'])
            ->name('rations.trashed');
        Route::patch('/rations/{id}/restore', [RationController::class, 'restore'])
            ->name('rations.restore');
        Route::resource('rations', RationController::class);

        // ─── Rappels sanitaires ───────────────────────────────
        Route::get('/sante-rappels/trashed', [SanteRappelController::class, 'trashed'])
            ->name('sante-rappels.trashed');
        Route::patch('/sante-rappels/{id}/restore', [SanteRappelController::class, 'restore'])
            ->name('sante-rappels.restore');
        Route::get('/sante-rappels/a-venir', [SanteRappelController::class, 'aVenir'])
            ->name('sante-rappels.a-venir');
        Route::get('/sante-rappels/en-retard', [SanteRappelController::class, 'enRetard'])
            ->name('sante-rappels.en-retard');
        Route::post('/sante-rappels/{id}/marquer-realise', [SanteRappelController::class, 'marquerRealise'])
            ->name('sante-rappels.marquer-realise');
        Route::resource('sante-rappels', SanteRappelController::class);

        // ─── Santé animale ────────────────────────────────────
        Route::get('/sante/animals/{animalId}/historique-medical', [SanteAnimalController::class, 'historiqueMedical'])
            ->name('sante.historique-medical');
        Route::get('/sante/animals/{animalId}/statistiques-sanitaires', [SanteAnimalController::class, 'statistiquesSanitaires'])
            ->name('sante.statistiques-sanitaires');
        Route::get('/sante/resume-ferme', [SanteAnimalController::class, 'resumeFerme'])
            ->name('sante.resume-ferme');
        Route::get('/sante/alertes-ferme', [SanteAnimalController::class, 'alertesFerme'])
            ->name('sante.alertes-ferme');

        // ─── Événements sanitaires ────────────────────────────
        Route::get('/sante-evenements/trashed', [SanteEvenementController::class, 'trashed'])
            ->name('sante-evenements.trashed');
        Route::patch('/sante-evenements/{id}/restore', [SanteEvenementController::class, 'restore'])
            ->name('sante-evenements.restore');
        Route::get('/sante-evenements/statistiques', [SanteEvenementController::class, 'statistiques'])
            ->name('sante-evenements.statistiques');
        Route::resource('sante-evenements', SanteEvenementController::class);

        // ─── Reproduction ─────────────────────────────────────
        Route::get('/reproduction/dashboard', [ReproductionController::class, 'dashboard'])
            ->name('reproduction.dashboard');
        Route::get('/reproduction/forecast', [ReproductionController::class, 'forecast'])
            ->name('reproduction.forecast');
        Route::get('/reproduction/animals/{animalId}/historique', [ReproductionController::class, 'historiqueAnimal'])
            ->name('reproduction.historique-animal');
        Route::get('/reproduction/animals/{animalId}/stats', [ReproductionController::class, 'statistiquesAnimal'])
            ->name('reproduction.statistiques-animal');

        // ─── Événements de reproduction ──────────────────────
        Route::get('/evenements-reproduction/trashed', [EvenementReproductionController::class, 'trashed'])
            ->name('evenements-reproduction.trashed');
        Route::patch('/evenements-reproduction/{id}/restore', [EvenementReproductionController::class, 'restore'])
            ->name('evenements-reproduction.restore');
        Route::resource('evenements-reproduction', EvenementReproductionController::class);

        // ─── Naissances ───────────────────────────────────────
        Route::get('/naissances/trashed', [NaissanceController::class, 'trashed'])
            ->name('naissances.trashed');
        Route::patch('/naissances/{id}/restore', [NaissanceController::class, 'restore'])
            ->name('naissances.restore');
        Route::get('/naissances/previsions', [NaissanceController::class, 'previsions'])
            ->name('naissances.previsions');
        Route::resource('naissances', NaissanceController::class);

        // ─── Mouvements ───────────────────────────────────────
        Route::get('/mouvements/trashed', [MouvementController::class, 'trashed'])
            ->name('mouvements.trashed');
        Route::patch('/mouvements/{id}/restore', [MouvementController::class, 'restore'])
            ->name('mouvements.restore');
        Route::get('/mouvements/animals/{animalId}/historique', [MouvementController::class, 'animalHistory'])
            ->name('mouvements.animal-history');
        Route::get('/mouvements/trace/{animalId}', [MouvementController::class, 'trace'])
            ->name('mouvements.trace');
        Route::get('/mouvements/statistiques', [MouvementController::class, 'statistiques'])
            ->name('mouvements.statistiques');
        Route::resource('mouvements', MouvementController::class);

        // ─── Finance ──────────────────────────────────────────
        Route::get('/finance/trashed', [FinanceTransactionController::class, 'trashed'])
            ->name('finance.trashed');
        Route::patch('/finance/{id}/restore', [FinanceTransactionController::class, 'restore'])
            ->name('finance.restore');
        Route::get('/finance/bilan', [FinanceTransactionController::class, 'bilan'])
            ->name('finance.bilan');
        Route::get('/finance/statistiques-globales', [FinanceTransactionController::class, 'statistiquesGlobales'])
            ->name('finance.statistiques-globales');
        Route::resource('finance', FinanceTransactionController::class);
    });
});

// Redirect racine → admin
Route::get('/', fn() => redirect('/admin/dashboard'));