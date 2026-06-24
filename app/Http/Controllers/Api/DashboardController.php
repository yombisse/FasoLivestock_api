<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Obtenir le tableau de bord global pour la ferme courante.
     */
    public function index(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $dashboard = $this->dashboardService->getDashboard($farmId);

        return ApiResponse::success($dashboard, 'Tableau de bord récupéré avec succès.');
    }

    /**
     * Obtenir les statistiques globales (toutes fermes).
     */
    public function globalStats(Request $request)
    {
        $global = $this->dashboardService->getGlobalStats();

        return ApiResponse::success($global, 'Statistiques globales récupérées avec succès.');
    }
}
