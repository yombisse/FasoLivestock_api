<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\Animal;
use App\Services\Admin\SanteAnimalApiService;
use Illuminate\Http\Request;

class SanteAnimalController extends Controller
{
    private SanteAnimalApiService $santeAnimalApiService;

    public function __construct(SanteAnimalApiService $santeAnimalApiService)
    {
        $this->santeAnimalApiService = $santeAnimalApiService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Obtenir l'historique médical complet d'un animal.
     */
    public function historiqueMedical(Animal $animal)
    {
        $this->authorize('view', $animal);

        $historique = $this->santeAnimalApiService->historiqueMedical($animal->id);

        return ApiResponse::success($historique, 'Historique médical de l\'animal récupéré avec succès.');
    }

    /**
     * Obtenir les statistiques sanitaires d'un animal.
     */
    public function statistiquesSanitaires(Animal $animal)
    {
        $this->authorize('view', $animal);

        $statistiques = $this->santeAnimalApiService->statistiquesSanitaires($animal->id);

        return ApiResponse::success($statistiques, 'Statistiques sanitaires de l\'animal récupérées avec succès.');
    }

    /**
     * Obtenir le résumé sanitaire pour la ferme courante.
     */
    public function resumeFerme(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $resume = $this->santeAnimalApiService->resumeFerme($farmId);

        return ApiResponse::success($resume, 'Résumé sanitaire de la ferme récupéré avec succès.');
    }

    /**
     * Obtenir les alertes sanitaires pour la ferme courante.
     */
    public function alertesFerme(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $alertes = $this->santeAnimalApiService->alertesFerme($farmId);

        return ApiResponse::success($alertes, 'Alertes sanitaires de la ferme récupérées avec succès.');
    }
}
