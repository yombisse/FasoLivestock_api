<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    private StatisticsService $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * Obtenir toutes les données pour le tableau de bord graphique.
     */
    public function dashboardCharts(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $charts = $this->statisticsService->dashboardCharts(
            $farmId,
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($charts, 'Données graphiques du tableau de bord récupérées avec succès.');
    }

    /**
     * Obtenir les données pour le graphique d'évolution financière.
     */
    public function financialEvolution(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $request->validate([
            'period' => 'nullable|in:daily,weekly,monthly,yearly',
        ]);

        $chart = $this->statisticsService->financialEvolution(
            $farmId,
            $request->date_debut,
            $request->date_fin,
            $request->period ?? 'monthly'
        );

        return ApiResponse::success($chart, 'Graphique d\'évolution financière récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique des revenus par catégorie.
     */
    public function revenueByCategory(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $chart = $this->statisticsService->revenueByCategory(
            $farmId,
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($chart, 'Graphique des revenus par catégorie récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique des charges par catégorie.
     */
    public function expenseByCategory(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $chart = $this->statisticsService->expenseByCategory(
            $farmId,
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($chart, 'Graphique des charges par catégorie récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique du cheptel par espèce.
     */
    public function herdBySpecies(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $chart = $this->statisticsService->herdBySpecies($farmId);

        return ApiResponse::success($chart, 'Graphique du cheptel par espèce récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique du cheptel par sexe.
     */
    public function herdBySex(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $chart = $this->statisticsService->herdBySex($farmId);

        return ApiResponse::success($chart, 'Graphique du cheptel par sexe récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique des mouvements.
     */
    public function movementsStats(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $request->validate([
            'period' => 'nullable|in:daily,weekly,monthly,yearly',
        ]);

        $chart = $this->statisticsService->movementsStats(
            $farmId,
            $request->date_debut,
            $request->date_fin,
            $request->period ?? 'monthly'
        );

        return ApiResponse::success($chart, 'Graphique des mouvements récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique des événements sanitaires.
     */
    public function healthEventsEvolution(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $request->validate([
            'period' => 'nullable|in:daily,weekly,monthly,yearly',
        ]);

        $chart = $this->statisticsService->healthEventsEvolution(
            $farmId,
            $request->date_debut,
            $request->date_fin,
            $request->period ?? 'monthly'
        );

        return ApiResponse::success($chart, 'Graphique des événements sanitaires récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique de reproduction.
     */
    public function reproductionStats(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $chart = $this->statisticsService->reproductionStats(
            $farmId,
            $request->date_debut,
            $request->date_fin
        );

        return ApiResponse::success($chart, 'Graphique de reproduction récupéré avec succès.');
    }

    /**
     * Obtenir les données pour le graphique des naissances par mois.
     */
    public function birthsByMonth(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $request->validate([]);

        $months = $request->months ?? 12;

        $chart = $this->statisticsService->birthsByMonth($farmId, $months);

        return ApiResponse::success($chart, 'Graphique des naissances par mois récupéré avec succès.');
    }
}
