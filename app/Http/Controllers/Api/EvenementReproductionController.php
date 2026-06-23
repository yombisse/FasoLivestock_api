<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reproduction\StoreEvenementReproductionRequest;
use App\Http\Requests\Reproduction\UpdateEvenementReproductionRequest;
use App\Helpers\ApiResponse;
use App\Models\Evenement;
use App\Services\EvenementReproductionService;
use Illuminate\Http\Request;

class EvenementReproductionController extends Controller
{
    private EvenementReproductionService $evenementReproductionService;

    public function __construct(EvenementReproductionService $evenementReproductionService)
    {
        $this->evenementReproductionService = $evenementReproductionService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Lister tous les événements de reproduction avec pagination et filtres.
     */
    public function index(Request $request)
    {
        $filters = [
            'animal_id' => $request->animal_id,
            'type_evenement' => $request->type_evenement,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
        ];

        $evenements = $this->evenementReproductionService->index($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'evenements' => $evenements->map(fn ($evenement) => $this->evenementReproductionService->formatEvenement($evenement)),
            'meta'  => [
                'total'        => $evenements->total(),
                'per_page'     => $evenements->perPage(),
                'current_page' => $evenements->currentPage(),
                'last_page'    => $evenements->lastPage(),
            ],
        ], 'Événements de reproduction récupérés avec succès.');
    }

    /**
     * Créer un événement de reproduction.
     */
    public function store(StoreEvenementReproductionRequest $request)
    {
        $this->authorize('create', Evenement::class);

        $userId = auth()->id();
        $data = $request->validated();

        try {
            $evenement = $this->evenementReproductionService->store($data, $userId);

            return ApiResponse::success(
                $this->evenementReproductionService->formatEvenement($evenement->load(['animal', 'type', 'farm'])),
                'Événement de reproduction créé avec succès.',
                201
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Détail d'un événement de reproduction — Route Model Binding.
     */
    public function show(Evenement $evenement)
    {
        $this->authorize('view', $evenement);

        $evenement->load(['animal', 'type', 'farm', 'farmDestination', 'transaction']);

        return ApiResponse::success(
            $this->evenementReproductionService->formatEvenement($evenement),
            'Événement de reproduction récupéré avec succès.'
        );
    }

    /**
     * Modifier un événement de reproduction — Route Model Binding.
     */
    public function update(UpdateEvenementReproductionRequest $request, Evenement $evenement)
    {
        $this->authorize('update', $evenement);

        $userId = auth()->id();
        $data = $request->validated();

        try {
            $evenement = $this->evenementReproductionService->update($evenement, $data, $userId);

            return ApiResponse::success(
                $this->evenementReproductionService->formatEvenement($evenement->load(['animal', 'type', 'farm'])),
                'Événement de reproduction mis à jour avec succès.'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 409);
        }
    }

    /**
     * Archiver un événement de reproduction — soft delete — Route Model Binding.
     */
    public function destroy(Evenement $evenement)
    {
        $this->authorize('delete', $evenement);

        $this->evenementReproductionService->destroy($evenement);

        return ApiResponse::success(null, 'Événement de reproduction archivé avec succès.');
    }

    /**
     * Lister les événements de reproduction archivés.
     */
    public function trashed(Request $request)
    {
        $filters = [
            'animal_id' => $request->animal_id,
            'type_evenement' => $request->type_evenement,
        ];

        $evenements = $this->evenementReproductionService->trashed($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'evenements' => $evenements->map(fn ($evenement) => $this->evenementReproductionService->formatEvenement($evenement)),
            'meta'  => [
                'total'        => $evenements->total(),
                'per_page'     => $evenements->perPage(),
                'current_page' => $evenements->currentPage(),
                'last_page'    => $evenements->lastPage(),
            ],
        ], 'Événements de reproduction archivés récupérés avec succès.');
    }

    /**
     * Restaurer un événement de reproduction archivé.
     */
    public function restore(string $id)
    {
        $this->authorize('restore', Evenement::class);

        $evenement = $this->evenementReproductionService->restore($id);

        return ApiResponse::success(
            $this->evenementReproductionService->formatEvenement($evenement->load(['animal', 'type', 'farm'])),
            'Événement de reproduction restauré avec succès.'
        );
    }
}
