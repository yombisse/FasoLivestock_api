<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mouvement\StoreMouvementRequest;
use App\Http\Requests\Mouvement\UpdateMouvementRequest;
use App\Helpers\ApiResponse;
use App\Models\Evenement;
use App\Models\Animal;
use App\Services\MouvementService;
use Illuminate\Http\Request;

class MouvementController extends Controller
{
    private MouvementService $mouvementService;

    public function __construct(MouvementService $mouvementService)
    {
        $this->mouvementService = $mouvementService;
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

        $mouvements = $this->mouvementService->index($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'mouvements' => $mouvements->map(fn ($mouvement) => $this->mouvementService->formatMouvement($mouvement)),
            'meta'  => [
                'total'        => $mouvements->total(),
                'per_page'     => $mouvements->perPage(),
                'current_page' => $mouvements->currentPage(),
                'last_page'    => $mouvements->lastPage(),
            ],
        ], 'Mouvements récupérés avec succès.');
    }

    /**
     * Créer un mouvement.
     */
    public function store(StoreMouvementRequest $request)
    {
        $this->authorize('create', Evenement::class);

        $userId = auth()->id();
        $data = $request->validated();

        // Utiliser la ferme courante du contexte si non fournie
        if (!isset($data['farm_id'])) {
            $data['farm_id'] = session('current_farm_id');
        }

        $mouvement = $this->mouvementService->store($data, $userId);

        return ApiResponse::success(
            $this->mouvementService->formatMouvement($mouvement->load('farm', 'animal', 'type', 'farmDestination', 'transaction')),
            'Mouvement créé avec succès.',
            201
        );
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
            $this->mouvementService->formatMouvement($evenement),
            'Mouvement récupéré avec succès.'
        );
    }

    /**
     * Modifier un mouvement — Route Model Binding.
     */
    public function update(UpdateMouvementRequest $request, Evenement $evenement)
    {
        $this->authorize('update', $evenement);

        // Vérifier que c'est bien un mouvement
        if (!$evenement->est_mouvement) {
            return ApiResponse::error(null, 'Cet événement n\'est pas un mouvement.', 400);
        }

        $userId = auth()->id();
        $mouvement = $this->mouvementService->update($evenement, $request->validated(), $userId);

        return ApiResponse::success(
            $this->mouvementService->formatMouvement($mouvement->load('farm', 'animal', 'type', 'farmDestination', 'transaction')),
            'Mouvement mis à jour avec succès.'
        );
    }

    /**
     * Archiver un mouvement — soft delete — Route Model Binding.
     */
    public function destroy(Evenement $evenement)
    {
        $this->authorize('delete', $evenement);

        // Vérifier que c'est bien un mouvement
        if (!$evenement->est_mouvement) {
            return ApiResponse::error(null, 'Cet événement n\'est pas un mouvement.', 400);
        }

        $this->mouvementService->destroy($evenement);

        return ApiResponse::success(null, 'Mouvement archivé avec succès.');
    }

    /**
     * Historique des mouvements d'un animal.
     */
    public function animalHistory(Animal $animal, Request $request)
    {
        $this->authorize('view', $animal);

        $mouvements = $this->mouvementService->animalHistory($animal, $request->per_page ?? 15);

        return ApiResponse::success([
            'mouvements' => $mouvements->map(fn ($mouvement) => $this->mouvementService->formatMouvement($mouvement)),
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

        $trace = $this->mouvementService->trace($animal);

        return ApiResponse::success($trace, 'Traçabilité récupérée avec succès.');
    }

    /**
     * Statistiques des mouvements pour la ferme courante.
     */
    public function statistiques(Request $request)
    {
        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $stats = $this->mouvementService->statistiques($farmId);

        return ApiResponse::success($stats, 'Statistiques récupérées avec succès.');
    }
}
