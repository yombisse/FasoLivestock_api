<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alimentation\StoreRationRequest;
use App\Http\Requests\Alimentation\UpdateRationRequest;
use App\Helpers\ApiResponse;
use App\Models\Ration;
use App\Services\RationService;
use Illuminate\Http\Request;

class RationController extends Controller
{
    private RationService $rationService;

    public function __construct(RationService $rationService)
    {
        $this->rationService = $rationService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Lister toutes les rations avec pagination et filtres.
     */
    public function index(Request $request)
    {
        $filters = [
            'aliment_id' => $request->aliment_id,
            'animal_id' => $request->animal_id,
            'lot_id' => $request->lot_id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
        ];

        $rations = $this->rationService->index($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'rations' => $rations->map(fn ($ration) => $this->rationService->formatRation($ration)),
            'meta'  => [
                'total'        => $rations->total(),
                'per_page'     => $rations->perPage(),
                'current_page' => $rations->currentPage(),
                'last_page'    => $rations->lastPage(),
            ],
        ], 'Rations récupérées avec succès.');
    }

    /**
     * Créer une ration.
     */
    public function store(StoreRationRequest $request)
    {
        $this->authorize('create', Ration::class);

        $userId = auth()->id();
        $data = $request->validated();

        try {
            $ration = $this->rationService->store($data, $userId);

            return ApiResponse::success(
                $this->rationService->formatRation($ration->load(['farm', 'aliment', 'animal', 'lot'])),
                'Ration créée avec succès.',
                201
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Détail d'une ration — Route Model Binding.
     */
    public function show(Ration $ration)
    {
        $this->authorize('view', $ration);

        $ration->load(['farm', 'aliment', 'animal', 'lot']);

        return ApiResponse::success(
            $this->rationService->formatRation($ration),
            'Ration récupérée avec succès.'
        );
    }

    /**
     * Modifier une ration — Route Model Binding.
     */
    public function update(UpdateRationRequest $request, Ration $ration)
    {
        $this->authorize('update', $ration);

        $userId = auth()->id();
        $data = $request->validated();

        try {
            $ration = $this->rationService->update($ration, $data, $userId);

            return ApiResponse::success(
                $this->rationService->formatRation($ration->load(['farm', 'aliment', 'animal', 'lot'])),
                'Ration mise à jour avec succès.'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 409);
        }
    }

    /**
     * Archiver une ration — soft delete — Route Model Binding.
     */
    public function destroy(Ration $ration)
    {
        $this->authorize('delete', $ration);

        $this->rationService->destroy($ration);

        return ApiResponse::success(null, 'Ration archivée avec succès.');
    }

    /**
     * Lister les rations archivées.
     */
    public function trashed(Request $request)
    {
        $filters = [
            'aliment_id' => $request->aliment_id,
            'animal_id' => $request->animal_id,
            'lot_id' => $request->lot_id,
        ];

        $rations = $this->rationService->trashed($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'rations' => $rations->map(fn ($ration) => $this->rationService->formatRation($ration)),
            'meta'  => [
                'total'        => $rations->total(),
                'per_page'     => $rations->perPage(),
                'current_page' => $rations->currentPage(),
                'last_page'    => $rations->lastPage(),
            ],
        ], 'Rations archivées récupérées avec succès.');
    }

    /**
     * Restaurer une ration archivée.
     */
    public function restore(string $id)
    {
        $this->authorize('restore', Ration::class);

        $ration = $this->rationService->restore($id);

        return ApiResponse::success(
            $this->rationService->formatRation($ration->load(['farm', 'aliment', 'animal', 'lot'])),
            'Ration restaurée avec succès.'
        );
    }

    /**
     * Distribuer une ration à un lot.
     */
    public function distribuerLot(Request $request)
    {
        $this->authorize('create', Ration::class);

        $request->validate([
            'lot_id' => 'required|uuid|exists:lots,id',
            'aliment_id' => 'required|uuid|exists:aliments,id',
            'quantite_par_animal' => 'required|numeric|min:0',
            'date_distribution' => 'nullable|date',
            'heure_distribution' => 'nullable|date_format:H:i',
            'observation' => 'nullable|string',
        ]);

        $userId = auth()->id();
        $data = $request->only(['lot_id', 'aliment_id', 'quantite_par_animal', 'date_distribution', 'heure_distribution', 'observation']);

        try {
            $result = $this->rationService->distribuerLot($data, $userId);

            return ApiResponse::success([
                'ration' => $this->rationService->formatRation($result['ration']->load(['farm', 'aliment', 'lot'])),
                'nombre_animaux' => $result['nombre_animaux'],
                'quantite_par_animal' => $result['quantite_par_animal'],
                'quantite_totale' => $result['quantite_totale'],
            ], 'Ration distribuée au lot avec succès.', 201);
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Distribuer une ration à plusieurs animaux.
     */
    public function distribuerAnimaux(Request $request)
    {
        $this->authorize('create', Ration::class);

        $request->validate([
            'animal_ids' => 'required|array',
            'animal_ids.*' => 'uuid|exists:animals,id',
            'aliment_id' => 'required|uuid|exists:aliments,id',
            'quantite_par_animal' => 'required|numeric|min:0',
            'date_distribution' => 'nullable|date',
            'heure_distribution' => 'nullable|date_format:H:i',
            'observation' => 'nullable|string',
        ]);

        $userId = auth()->id();
        $data = $request->only(['animal_ids', 'aliment_id', 'quantite_par_animal', 'date_distribution', 'heure_distribution', 'observation']);

        try {
            $result = $this->rationService->distribuerAnimaux($data, $userId);

            return ApiResponse::success([
                'rations' => collect($result['rations'])->map(fn ($ration) => $this->rationService->formatRation($ration->load(['farm', 'aliment', 'animal']))),
                'nombre_animaux' => $result['nombre_animaux'],
                'quantite_par_animal' => $result['quantite_par_animal'],
                'quantite_totale' => $result['quantite_totale'],
            ], 'Ration distribuée aux animaux avec succès.', 201);
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Obtenir l'historique alimentaire d'un animal.
     */
    public function historiqueAnimal(Request $request, string $animalId)
    {
        $this->authorize('view', Ration::class);

        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;

        $historique = $this->rationService->historiqueAnimal($animalId, $dateDebut, $dateFin);

        return ApiResponse::success($historique, 'Historique alimentaire de l\'animal récupéré avec succès.');
    }

    /**
     * Obtenir l'historique alimentaire d'un lot.
     */
    public function historiqueLot(Request $request, string $lotId)
    {
        $this->authorize('view', Ration::class);

        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;

        $historique = $this->rationService->historiqueLot($lotId, $dateDebut, $dateFin);

        return ApiResponse::success($historique, 'Historique alimentaire du lot récupéré avec succès.');
    }

    /**
     * Obtenir la consommation totale d'un animal.
     */
    public function consommationAnimal(Request $request, string $animalId)
    {
        $this->authorize('view', Ration::class);

        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;

        $consommation = $this->rationService->consommationAnimal($animalId, $dateDebut, $dateFin);

        return ApiResponse::success($consommation, 'Consommation de l\'animal récupérée avec succès.');
    }

    /**
     * Obtenir la consommation totale d'un lot.
     */
    public function consommationLot(Request $request, string $lotId)
    {
        $this->authorize('view', Ration::class);

        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;

        $consommation = $this->rationService->consommationLot($lotId, $dateDebut, $dateFin);

        return ApiResponse::success($consommation, 'Consommation du lot récupérée avec succès.');
    }

    /**
     * Obtenir les statistiques globales d'alimentation pour la ferme courante.
     */
    public function statistiquesGlobales(Request $request)
    {
        $this->authorize('view', Ration::class);

        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;

        $statistiques = $this->rationService->statistiquesGlobales($farmId, $dateDebut, $dateFin);

        return ApiResponse::success($statistiques, 'Statistiques globales d\'alimentation récupérées avec succès.');
    }
}
