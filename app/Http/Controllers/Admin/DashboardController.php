<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardApiService;
use App\Services\Admin\FarmApiService;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardApiService $dashboardApi,
        private FarmApiService $farmApi
    ) {}

    public function index()
    {
        // Récupérer les statistiques globales (indépendantes de la ferme)
        $globalResponse = $this->dashboardApi->getGlobalStats();

        // Récupérer la liste des fermes accessibles à l'utilisateur
        $farmsResponse = $this->farmApi->getAll();

        $global = $globalResponse->success ? $globalResponse->data : [];
        $farms = $farmsResponse->success ? ($farmsResponse->data['farms'] ?? []) : [];

        return view('admin.dashboard.global', [
            'global' => $global,
            'farms' => $farms,
        ]);
    }
}