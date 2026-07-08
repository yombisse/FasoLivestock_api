<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mouvement\StoreMouvementRequest;
use App\Http\Requests\Mouvement\UpdateMouvementRequest;
use App\Helpers\ApiResponse;
use App\Models\Evenement;
use App\Models\Animal;
use App\Services\EvenementMouvementService;
use Illuminate\Http\Request;

class EvenementMouvementController extends Controller
{
    private EvenementMouvementService $evenementMouvementService;

    public function __construct(EvenementMouvementService $evenementMouvementService)
    {
        $this->evenementMouvementService = $evenementMouvementService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Lister tous les mouvements avec pagination et filtres.
     */
    public function index(Request $request)
    {
        $filters = [
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'animal_id' => $request->animal_id,
            'type' => $request->type,
            'statut' => $request->statut,
        ];

        $mouvements = $this->evenementMouvementService->index($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'mouvements' => $mouvements->map(fn ($mouvement) => $this->evenementMouvementService->formatMouvement($mouvement)),
            'meta'  => [
                'total'        => $mouvements->total(),
                'per_page'     => $mouvements->perPage(),
                'current_page' => $mouvements->currentPage(),
                'last_page'    => $mouvements->lastPage(),
            ],
        ], 'Mouvements récupérés avec succès.');
    }

    /**
     * Détail d'un mouvement — Route Model Binding.
     */
    public function show(Evenement $evenement)
    {
        $this->authorize('view', $evenement);

        // Vérifier que c'est bien un mouvement
        if (!$evenement->est_mouvement) {
            return ApiResponse::error(null, 'Cet événement n\'est pas un mouvement.', 400);
        }

        $evenement->load('farm', 'animal', 'type', 'farmDestination', 'transaction');

        return ApiResponse::success(
            $this->evenementMouvementService->formatMouvement($evenement),
            'Mouvement récupéré avec succès.'
        );
    }

    /**
     * Historique des mouvements d'un animal.
     */
    public function animalHistory(string $animalId, Request $request)
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

        $mouvements = $this->evenementMouvementService->animalHistory($animal, $request->per_page ?? 15);

        return ApiResponse::success([
            'mouvements' => $mouvements->map(fn ($mouvement) => $this->evenementMouvementService->formatMouvement($mouvement)),
            'meta'  => [
                'total'        => $mouvements->total(),
                'per_page'     => $mouvements->perPage(),
                'current_page' => $mouvements->currentPage(),
                'last_page'    => $mouvements->lastPage(),
            ],
        ], 'Historique des mouvements récupéré avec succès.');
    }

    /**
     * Traçabilité complète d'un animal.
     */
    public function trace(Animal $animal)
    {
        $this->authorize('view', $animal);

        $trace = $this->evenementMouvementService->trace($animal);

        return ApiResponse::success($trace, 'Traçabilité récupérée avec succès.');
    }

    /**
     * Statistiques des mouvements pour la ferme courante.
     */
    public function statistiques(Request $request)
    {
        $farmId = $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $stats = $this->evenementMouvementService->statistiques($farmId);

        return ApiResponse::success($stats, 'Statistiques récupérées avec succès.');
    }
}
