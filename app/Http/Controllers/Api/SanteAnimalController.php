<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\Animal;
use App\Services\SanteAnimalService;
use Illuminate\Http\Request;

class SanteAnimalController extends Controller
{
    private SanteAnimalService $santeAnimalService;

    public function __construct(SanteAnimalService $santeAnimalService)
    {
        $this->santeAnimalService = $santeAnimalService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Obtenir l'historique médical complet d'un animal.
     */
    public function historiqueMedical(string $animalId)
    {
        $animal = Animal::find($animalId);
        
        if (!$animal) {
            return ApiResponse::error(
                'Animal non trouvé. Veuillez synchroniser vos données locales avec le serveur avant de charger l\'historique.',
                null,
                404
            );
        }

        $this->authorize('view', $animal);

        $historique = $this->santeAnimalService->historiqueMedical($animal->id);

        return ApiResponse::success($historique, 'Historique médical de l\'animal récupéré avec succès.');
    }

    /**
     * Obtenir les statistiques sanitaires d'un animal.
     */
    public function statistiquesSanitaires(string $animalId)
    {
        $animal = Animal::find($animalId);
        
        if (!$animal) {
            return ApiResponse::error(
                'Animal non trouvé. Veuillez synchroniser vos données locales avec le serveur avant de charger les statistiques.',
                null,
                404
            );
        }

        $this->authorize('view', $animal);

        $statistiques = $this->santeAnimalService->statistiquesSanitaires($animal->id);

        return ApiResponse::success($statistiques, 'Statistiques sanitaires de l\'animal récupérées avec succès.');
    }

    /**
     * Obtenir le résumé sanitaire pour la ferme courante.
     */
    public function resumeFerme(Request $request)
    {
        $farmId = $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $resume = $this->santeAnimalService->resumeFerme($farmId);

        return ApiResponse::success($resume, 'Résumé sanitaire de la ferme récupéré avec succès.');
    }

    /**
     * Obtenir les alertes sanitaires pour la ferme courante.
     */
    public function alertesFerme(Request $request)
    {
        $farmId = $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $alertes = $this->santeAnimalService->alertesFerme($farmId);

        return ApiResponse::success($alertes, 'Alertes sanitaires de la ferme récupérées avec succès.');
    }
}
