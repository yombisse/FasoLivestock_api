<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FarmDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\FarmController;
use App\Http\Controllers\Admin\FarmAdminController;
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
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ActivityLogController;

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

        // ─── SYSTÈME ─────────────────────────────────────────
        // Dashboard global
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
        Route::get('/users/all', [UserController::class, 'all'])
            ->name('users.all');
        Route::resource('users', UserController::class);

        // ─── Rôles ────────────────────────────────────────────
        Route::get('/roles/trashed', [RoleController::class, 'trashed'])
            ->name('roles.trashed');
        Route::patch('/roles/{id}/restore', [RoleController::class, 'restore'])
            ->name('roles.restore');
        Route::get('/roles/all', [RoleController::class, 'all'])
            ->name('roles.all');
        Route::resource('roles', RoleController::class);

        // ─── Role ↔ Users (AJAX) ──────────────────────────────
        Route::get('/role-users/{roleId}/users', [RoleUserController::class, 'users'])
            ->name('role-users.users');
        Route::post('/role-users/{roleId}/attach', [RoleUserController::class, 'attach'])
            ->name('role-users.attach');
        Route::post('/role-users/{roleId}/detach/{userId}', [RoleUserController::class, 'detach'])
            ->name('role-users.detach');

        // ─── Fermes (SYSTÈME) ─────────────────────────────────
        Route::resource('farms', FarmController::class)
            ->except(['show']);
        Route::get('/farms/trashed', [FarmController::class, 'trashed'])
            ->name('farms.trashed');
        Route::patch('/farms/{id}/restore', [FarmController::class, 'restore'])
            ->name('farms.restore');

        // ─── Ferme (FARM-SCOPED) ───────────────────────────────
        Route::prefix('fermes/{farm}')->middleware('farm.context')->group(function () {
            Route::get('/dashboard', [FarmDashboardController::class, 'index'])
                ->name('ferme.dashboard');
            Route::get('/manage', [FarmAdminController::class, 'manage'])
                ->name('farms.manage');
            Route::post('/members', [FarmAdminController::class, 'addMembers'])
                ->name('farms.members.add');
            Route::post('/members/update-role', [FarmAdminController::class, 'updateRole'])
                ->name('farms.members.update-role');
            Route::delete('/members/{userId}', [FarmAdminController::class, 'removeMember'])
                ->name('farms.members.remove');

            // ─── Animaux ───────────────────────────────────────────
            Route::get('/animals/trashed', [AnimalController::class, 'trashed'])
                ->name('animals.trashed');
            Route::patch('/animals/{id}/restore', [AnimalController::class, 'restore'])
                ->name('animals.restore');
            Route::get('/animals/lots-by-farm', [AnimalController::class, 'getLotsByFarm'])
                ->name('animals.lots-by-farm');
            Route::get('/animals/mothers', [AnimalController::class, 'getMothers'])
                ->name('animals.mothers');
            // Routes pour animaux éligibles aux événements
            Route::get('/animals/eligible/sanitaire', [AnimalController::class, 'getEligibleForSanitaire'])
                ->name('animals.eligible.sanitaire');
            Route::get('/animals/eligible/reproduction', [AnimalController::class, 'getEligibleForReproduction'])
                ->name('animals.eligible.reproduction');
            Route::get('/animals/eligible/male', [AnimalController::class, 'getEligibleMales'])
                ->name('animals.eligible.male');
            // Routes dédiées aux formulaires de création
            Route::get('/animals/create-achat', [AnimalController::class, 'createAchat'])
                ->name('animals.create-achat');
            Route::get('/animals/create-naissance', [AnimalController::class, 'createNaissance'])
                ->name('animals.create-naissance');
            Route::get('/animals/create-sale', [AnimalController::class, 'createSale'])
                ->name('animals.create-sale');
            // Route dédiée à l'achat (POST sans {animal}) — doit précéder resource()
            Route::post('/animals/purchase', [AnimalController::class, 'purchase'])
                ->name('animals.purchase');
            // Route dédiée à la naissance (POST sans {animal}) — doit précéder resource()
            Route::post('/animals/naissance', [AnimalController::class, 'naissance'])
                ->name('animals.naissance');
            // Route dédiée à la vente (POST sans {animal}) — doit précéder resource()
            Route::post('/animals/sell', [AnimalController::class, 'sell'])
                ->name('animals.sell');
            // Routes dédiées à l'import cheptel
            Route::get('/animals/import', [AnimalController::class, 'importWizard'])
                ->name('animals.import');
            Route::post('/animals/import', [AnimalController::class, 'importStore'])
                ->name('animals.import.store');
            Route::resource('animals', AnimalController::class);

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

            // ─── Santé (FARM-SCOPED) ───────────────────────────
            Route::get('/sante-evenements/trashed', [SanteEvenementController::class, 'trashed'])
                ->name('sante-evenements.trashed');
            Route::patch('/sante-evenements/{id}/restore', [SanteEvenementController::class, 'restore'])
                ->name('sante-evenements.restore');
            Route::get('/sante-evenements/statistiques', [SanteEvenementController::class, 'statistiques'])
                ->name('sante-evenements.statistiques');
            Route::resource('sante-evenements', SanteEvenementController::class);

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

            // ─── Reproduction ─────────────────────────────────────
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

            // ─── Rapports ────────────────────────────────────────
            Route::get('/rapports', [ReportController::class, 'index'])
                ->name('rapports.index');
            
            // Routes d'export
            Route::get('/rapports/export/animaux', [ReportController::class, 'exportAnimaux'])
                ->name('rapports.export.animaux');
            Route::get('/rapports/export/transactions', [ReportController::class, 'exportTransactions'])
                ->name('rapports.export.transactions');
            Route::get('/rapports/export/sante-rappels', [ReportController::class, 'exportSanteRappels'])
                ->name('rapports.export.sante-rappels');
            Route::get('/rapports/export/naissances', [ReportController::class, 'exportNaissances'])
                ->name('rapports.export.naissances');
            Route::get('/rapports/pdf/cheptel', [ReportController::class, 'pdfCheptel'])
                ->name('rapports.pdf.cheptel');
            Route::get('/rapports/pdf/sanitaire', [ReportController::class, 'pdfSanitaire'])
                ->name('rapports.pdf.sanitaire');
            Route::get('/rapports/pdf/financier', [ReportController::class, 'pdfFinancier'])
                ->name('rapports.pdf.financier');

            // ─── Logs ───────────────────────────────────────────
            Route::get('/logs', [ActivityLogController::class, 'index'])
                ->name('logs.index');
        });


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

        // ─── Santé animale (SYSTÈME - routes non farm-scoped) ──
        Route::get('/sante/animals/{animalId}/historique-medical', [SanteAnimalController::class, 'historiqueMedical'])
            ->name('sante.historique-medical');
        Route::get('/sante/animals/{animalId}/statistiques-sanitaires', [SanteAnimalController::class, 'statistiquesSanitaires'])
            ->name('sante.statistiques-sanitaires');
        Route::get('/sante/resume-ferme', [SanteAnimalController::class, 'resumeFerme'])
            ->name('sante.resume-ferme');
        Route::get('/sante/alertes-ferme', [SanteAnimalController::class, 'alertesFerme'])
            ->name('sante.alertes-ferme');

        // ─── Reproduction ─────────────────────────────────────
        Route::get('/reproduction/dashboard', [ReproductionController::class, 'dashboard'])
            ->name('reproduction.dashboard');
        Route::get('/reproduction/forecast', [ReproductionController::class, 'forecast'])
            ->name('reproduction.forecast');
        Route::get('/reproduction/animals/{animalId}/historique', [ReproductionController::class, 'historiqueAnimal'])
            ->name('reproduction.historique-animal');
        Route::get('/reproduction/animals/{animalId}/stats', [ReproductionController::class, 'statistiquesAnimal'])
            ->name('reproduction.statistiques-animal');
    });
});

// Redirect racine → admin
Route::get('/', fn() => redirect('/admin/dashboard'));