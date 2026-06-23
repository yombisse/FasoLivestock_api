<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lot\StoreLotRequest;
use App\Http\Requests\Lot\UpdateLotRequest;
use App\Http\Requests\Lot\AssignAnimalsToLotRequest;
use App\Helpers\ApiResponse;
use App\Models\Lot;
use App\Services\LotService;
use Illuminate\Http\Request;

class LotController extends Controller
{
    private LotService $lotService;

    public function __construct(LotService $lotService)
    {
        $this->lotService = $lotService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Lister tous les lots avec pagination et recherche.
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->search,
        ];

        $lots = $this->lotService->index($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'lots' => $lots->map(fn ($lot) => $this->lotService->formatLot($lot)),
            'meta'  => [
                'total'        => $lots->total(),
                'per_page'     => $lots->perPage(),
                'current_page' => $lots->currentPage(),
                'last_page'    => $lots->lastPage(),
            ],
        ], 'Lots récupérés avec succès.');
    }

    /**
     * Créer un lot.
     */
    public function store(StoreLotRequest $request)
    {
        $this->authorize('create', Lot::class);

        $userId = auth()->id();
        $data = $request->validated();

        // Utiliser la ferme courante du contexte si non fournie
        if (!isset($data['farm_id'])) {
            $data['farm_id'] = session('current_farm_id');
        }

        $lot = $this->lotService->store($data, $userId);

        return ApiResponse::success(
            $this->lotService->formatLot($lot->load('farm')),
            'Lot créé avec succès.',
            201
        );
    }

    /**
     * Détail d'un lot — Route Model Binding.
     */
    public function show(Lot $lot)
    {
        $this->authorize('view', $lot);

        $lot->loadCount('animals');
        $lot->load('farm');

        return ApiResponse::success(
            $this->lotService->formatLot($lot),
            'Lot récupéré avec succès.'
        );
    }

    /**
     * Modifier un lot — Route Model Binding.
     */
    public function update(UpdateLotRequest $request, Lot $lot)
    {
        $this->authorize('update', $lot);

        $userId = auth()->id();
        $lot = $this->lotService->update($lot, $request->validated(), $userId);

        return ApiResponse::success(
            $this->lotService->formatLot($lot->load('farm')),
            'Lot mis à jour avec succès.'
        );
    }

    /**
     * Archiver un lot — soft delete — Route Model Binding.
     */
    public function destroy(Lot $lot)
    {
        $this->authorize('delete', $lot);

        $this->lotService->destroy($lot);

        return ApiResponse::success(null, 'Lot archivé avec succès.');
    }

    /**
     * Lister les lots archivés.
     */
    public function trashed(Request $request)
    {
        $filters = [
            'search' => $request->search,
        ];

        $lots = $this->lotService->trashed($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'lots' => $lots->map(fn ($lot) => $this->lotService->formatLot($lot)),
            'meta'  => [
                'total'        => $lots->total(),
                'per_page'     => $lots->perPage(),
                'current_page' => $lots->currentPage(),
                'last_page'    => $lots->lastPage(),
            ],
        ], 'Lots archivés récupérés avec succès.');
    }

    /**
     * Restaurer un lot archivé.
     */
    public function restore(string $id)
    {
        $this->authorize('restore', Lot::class);

        $lot = $this->lotService->restore($id);

        return ApiResponse::success(
            $this->lotService->formatLot($lot->load('farm')),
            'Lot restauré avec succès.'
        );
    }

    /**
     * Lister les animaux d'un lot.
     */
    public function animals(Lot $lot, Request $request)
    {
        $this->authorize('view', $lot);

        $animals = $this->lotService->animals($lot, $request->per_page ?? 15);

        return ApiResponse::success([
            'animals' => $animals->map(fn ($animal) => [
                'id' => $animal->id,
                'nom' => $animal->nom,
                'race' => $animal->race,
                'sexe' => $animal->sexe,
                'date_naissance' => $animal->date_naissance,
                'poids' => $animal->poids,
                'statut' => $animal->statut,
                'numero_identification' => $animal->numero_identification,
                'photo' => $animal->photo,
                'farm' => $animal->farm ? [
                    'id' => $animal->farm->id,
                    'name' => $animal->farm->name,
                ] : null,
                'espece' => $animal->espece ? [
                    'id' => $animal->espece->id,
                    'nom' => $animal->espece->nom,
                ] : null,
                'lot' => $animal->lot ? [
                    'id' => $animal->lot->id,
                    'nom_lot' => $animal->lot->nom_lot,
                ] : null,
            ]),
            'meta'  => [
                'total'        => $animals->total(),
                'per_page'     => $animals->perPage(),
                'current_page' => $animals->currentPage(),
                'last_page'    => $animals->lastPage(),
            ],
        ], 'Animaux du lot récupérés avec succès.');
    }

    /**
     * Affecter des animaux à un lot.
     */
    public function assignAnimals(AssignAnimalsToLotRequest $request, Lot $lot)
    {
        $this->authorize('update', $lot);

        $userId = auth()->id();
        $animalIds = $request->animal_ids;

        try {
            $assignedIds = $this->lotService->assignAnimals($lot, $animalIds, $userId);

            return ApiResponse::success([
                'assigned_animal_ids' => $assignedIds,
                'count' => count($assignedIds),
            ], 'Animaux affectés au lot avec succès.');
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Retirer un animal d'un lot.
     */
    public function removeAnimal(Lot $lot, string $animalId)
    {
        $this->authorize('update', $lot);

        $userId = auth()->id();

        try {
            $this->lotService->removeAnimal($lot, $animalId, $userId);

            return ApiResponse::success(null, 'Animal retiré du lot avec succès.');
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Statistiques d'un lot.
     */
    public function statistiques(Lot $lot)
    {
        $this->authorize('view', $lot);

        $stats = $this->lotService->statistiques($lot);

        return ApiResponse::success($stats, 'Statistiques du lot récupérées avec succès.');
    }
}
