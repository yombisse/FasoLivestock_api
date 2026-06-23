<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Services\FinanceTransactionService;
use Illuminate\Http\Request;

class FinanceRevenueController extends Controller
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
     * Lister les revenus avec filtres.
     */
    public function index(Request $request)
    {
        $this->authorize('view', \App\Models\Transaction::class);

        $filters = [
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'categorie_id' => $request->categorie_id,
            'animal_id' => $request->animal_id,
        ];

        $revenus = $this->financeTransactionService->revenus($filters);

        return ApiResponse::success($revenus, 'Revenus récupérés avec succès.');
    }

    /**
     * Obtenir les revenus par période.
     */
    public function parPeriode(Request $request)
    {
        $this->authorize('view', \App\Models\Transaction::class);

        $request->validate([
            'date_debut' => 'required|date',
            'date_fin' => 'required|date',
        ]);

        $revenus = $this->financeTransactionService->revenusParPeriode(
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($revenus, 'Revenus par période récupérés avec succès.');
    }

    /**
     * Obtenir les revenus par catégorie.
     */
    public function parCategorie(Request $request)
    {
        $this->authorize('view', \App\Models\Transaction::class);

        $revenus = $this->financeTransactionService->revenusParCategorie(
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($revenus, 'Revenus par catégorie récupérés avec succès.');
    }

    /**
     * Obtenir les revenus par animal.
     */
    public function parAnimal(Request $request)
    {
        $this->authorize('view', \App\Models\Transaction::class);

        $revenus = $this->financeTransactionService->revenusParAnimal(
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($revenus, 'Revenus par animal récupérés avec succès.');
    }
}
