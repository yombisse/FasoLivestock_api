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
        // Récupérer les statistiques globales (indépendantes de la ferme)
        $globalResponse = $this->dashboardApi->getGlobalStats();

        // Récupérer les données du tableau de bord (ferme courante)
        $dashboardResponse = $this->dashboardApi->getDashboard();

        // Récupérer les données des graphiques
        $chartsResponse = $this->statisticsApi->getDashboardCharts();

        $global = $globalResponse->success ? $globalResponse->data : [];
        $dashboard = $dashboardResponse->success ? $dashboardResponse->data : [];
        $charts = $chartsResponse->success ? $chartsResponse->data : [];

        return view('admin.dashboard.index', [
            'global' => $global,
            'dashboard' => $dashboard,
            'charts' => $charts,
        ]);
    }
}