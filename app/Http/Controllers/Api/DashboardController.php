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
     * Obtenir le tableau de bord global pour une ferme.
     * Accepte farm_id en paramètre ou utilise la ferme courante du contexte.
     */
    public function index(Request $request)
    {
        $farmId = $request->input('farm_id') ?? $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme spécifiée ou ferme courante définie.', 400);
        }

        // Vérifier que l'utilisateur a accès à cette ferme
        $user = auth()->user();
        $farm = \App\Models\Farm::findOrFail($farmId);
        
        if (!$user->hasRole('superadmin') && 
            $farm->owner_id !== $user->id && 
            !$farm->users()->where('user_id', $user->id)->exists()) {
            return ApiResponse::error(null, 'Vous n\'avez pas accès à cette ferme.', 403);
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
