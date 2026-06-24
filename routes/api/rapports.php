<?php

use App\Http\Controllers\Api\ExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:rapports.export'])->group(function () {
    // Excel exports
    Route::get('/rapports/export/animaux', [ExportController::class, 'animaux']);
    Route::get('/rapports/export/transactions', [ExportController::class, 'transactions']);
    Route::get('/rapports/export/sante-rappels', [ExportController::class, 'santeRappels']);
    Route::get('/rapports/export/naissances', [ExportController::class, 'naissances']);
    
    // PDF reports
    Route::get('/rapports/pdf/cheptel', [ExportController::class, 'rapportCheptel']);
    Route::get('/rapports/pdf/sanitaire', [ExportController::class, 'rapportSanitaire']);
    Route::get('/rapports/pdf/financier', [ExportController::class, 'rapportFinancier']);
});
