<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Services\FinanceTransactionService;
use Illuminate\Http\Request;

class FinanceReportController extends Controller
{
    private FinanceTransactionService $financeTransactionService;

    public function __construct(FinanceTransactionService $financeTransactionService)
    {
        $this->financeTransactionService = $financeTransactionService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Obtenir le bilan financier.
     */
    public function bilan(Request $request)
    {
        $this->authorize('view', \App\Models\Transaction::class);

        $bilan = $this->financeTransactionService->bilan(
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($bilan, 'Bilan financier récupéré avec succès.');
    }

    /**
     * Obtenir le bilan financier par période.
     */
    public function bilanParPeriode(Request $request)
    {
        $this->authorize('view', \App\Models\Transaction::class);

        $request->validate([
            'date_debut' => 'required|date',
            'date_fin' => 'required|date',
        ]);

        $bilan = $this->financeTransactionService->bilan(
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($bilan, 'Bilan financier par période récupéré avec succès.');
    }

    /**
     * Obtenir le bilan financier par ferme.
     */
    public function bilanParFerme(Request $request, string $farmId)
    {
        $this->authorize('view', \App\Models\Transaction::class);

        $bilan = $this->financeTransactionService->bilanParFerme(
            $farmId,
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($bilan, 'Bilan financier par ferme récupéré avec succès.');
    }

    /**
     * Obtenir les statistiques globales financières.
     */
    public function statistiquesGlobales(Request $request)
    {
        $this->authorize('view', \App\Models\Transaction::class);

        $statistiques = $this->financeTransactionService->statistiquesGlobales(
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($statistiques, 'Statistiques globales financières récupérées avec succès.');
    }
}
