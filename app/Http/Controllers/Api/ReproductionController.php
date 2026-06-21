<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\Animal;
use App\Services\Admin\ReproductionApiService;
use Illuminate\Http\Request;

class ReproductionController extends Controller
{
    private ReproductionApiService $reproductionApiService;

    public function __construct(ReproductionApiService $reproductionApiService)
    {
        $this->reproductionApiService = $reproductionApiService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Obtenir le dashboard de reproduction pour la ferme courante.
     */
    public function dashboard(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $dashboard = $this->reproductionApiService->dashboard($farmId);

        return ApiResponse::success($dashboard, 'Dashboard de reproduction récupéré avec succès.');
    }

    /**
     * Obtenir les prévisions de reproduction pour la ferme courante.
     */
    public function forecast(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $jours = $request->jours ?? 30;
        $forecast = $this->reproductionApiService->forecast($farmId, $jours);

        return ApiResponse::success([
            'forecast' => $forecast,
            'periode_jours' => $jours,
        ], 'Prévisions de reproduction récupérées avec succès.');
    }

    /**
     * Obtenir l'historique reproductif d'un animal.
     */
    public function historiqueAnimal(Animal $animal)
    {
        $this->authorize('view', $animal);

        $historique = $this->reproductionApiService->historiqueAnimal($animal->id);

        return ApiResponse::success($historique, 'Historique reproductif de l\'animal récupéré avec succès.');
    }

    /**
     * Obtenir les statistiques reproductives d'un animal.
     */
    public function statistiquesAnimal(Animal $animal)
    {
        $this->authorize('view', $animal);

        $statistiques = $this->reproductionApiService->statistiquesAnimal($animal->id);

        return ApiResponse::success($statistiques, 'Statistiques reproductives de l\'animal récupérées avec succès.');
    }
}
