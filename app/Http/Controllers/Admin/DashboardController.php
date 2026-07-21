<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardApiService;
use App\Services\Admin\StatisticsApiService;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardApiService $dashboardApi,
        private StatisticsApiService $statisticsApi
    ) {}

    public function index()
    {
        // Récupérer la ferme courante depuis la session
        $farmId = session('current_farm_id');
        
        // Si pas de ferme courante, récupérer la première ferme de l'utilisateur
        if (empty($farmId)) {
            $user = auth()->user();
            if ($user) {
                $firstFarm = $user->farms()->first();
                if ($firstFarm) {
                    $farmId = $firstFarm->id;
                    session(['current_farm_id' => $farmId]);
                }
            }
        }

        // Récupérer les statistiques globales (indépendantes de la ferme)
        $globalResponse = $this->dashboardApi->getGlobalStats();

        // Récupérer les données du tableau de bord (ferme courante)
        $dashboardResponse = $this->dashboardApi->getDashboard([
            'current_farm_id' => $farmId,
        ]);

        // Récupérer les données des graphiques
        $chartsResponse = $this->statisticsApi->getDashboardCharts([
            'current_farm_id' => $farmId,
        ]);

        $global = $globalResponse->success ? $globalResponse->data : [];
        $dashboard = $dashboardResponse->success ? $dashboardResponse->data : [];
        $charts = $chartsResponse->success ? $chartsResponse->data : [];

        return view('admin.dashboard.index', [
            'global' => $global,
            'dashboard' => $dashboard,
            'charts' => $charts,
            'current_farm_id' => $farmId,
        ]);
    }
}