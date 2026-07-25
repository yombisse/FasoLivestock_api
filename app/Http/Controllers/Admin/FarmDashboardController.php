<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardApiService;
use App\Services\Admin\StatisticsApiService;

class FarmDashboardController extends Controller
{
    public function __construct(
        private DashboardApiService $dashboardApi,
        private StatisticsApiService $statisticsApi
    ) {}

    public function index(string $farm)
    {
        // Récupérer les données du tableau de bord pour la ferme spécifiée
        $dashboardResponse = $this->dashboardApi->getDashboard([
            'current_farm_id' => $farm,
        ]);

        // Récupérer les données des graphiques pour la ferme spécifiée
        $chartsResponse = $this->statisticsApi->getDashboardCharts([
            'current_farm_id' => $farm,
        ]);

        $dashboard = $dashboardResponse->success ? $dashboardResponse->data : [];
        $charts = $chartsResponse->success ? $chartsResponse->data : [];

        return view('admin.dashboard.ferme', [
            'dashboard' => $dashboard,
            'charts' => $charts,
            'farmId' => $farm,
        ]);
    }
}
