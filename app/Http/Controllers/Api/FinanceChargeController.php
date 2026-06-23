<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Services\FinanceTransactionService;
use Illuminate\Http\Request;

class FinanceChargeController extends Controller
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
     * Lister les charges avec filtres.
     */
    public function index(Request $request)
    {
        $this->authorize('view', \App\Models\Transaction::class);

        $filters = [
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'categorie_id' => $request->categorie_id,
        ];

        $charges = $this->financeTransactionService->charges($filters);

        return ApiResponse::success($charges, 'Charges récupérées avec succès.');
    }

    /**
     * Obtenir les charges par période.
     */
    public function parPeriode(Request $request)
    {
        $this->authorize('view', \App\Models\Transaction::class);

        $request->validate([
            'date_debut' => 'required|date',
            'date_fin' => 'required|date',
        ]);

        $charges = $this->financeTransactionService->chargesParPeriode(
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($charges, 'Charges par période récupérées avec succès.');
    }

    /**
     * Obtenir les charges par catégorie.
     */
    public function parCategorie(Request $request)
    {
        $this->authorize('view', \App\Models\Transaction::class);

        $charges = $this->financeTransactionService->chargesParCategorie(
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($charges, 'Charges par catégorie récupérées avec succès.');
    }
}
