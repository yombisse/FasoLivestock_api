<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sante\StoreSanteRappelRequest;
use App\Http\Requests\Sante\UpdateSanteRappelRequest;
use App\Helpers\ApiResponse;
use App\Models\SanteRappel;
use App\Services\Admin\SanteRappelApiService;
use Illuminate\Http\Request;

class SanteRappelController extends Controller
{
    private SanteRappelApiService $santeRappelApiService;

    public function __construct(SanteRappelApiService $santeRappelApiService)
    {
        $this->santeRappelApiService = $santeRappelApiService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Lister tous les rappels sanitaires avec pagination et filtres.
     */
    public function index(Request $request)
    {
        $filters = [
            'animal_id' => $request->animal_id,
            'type_rappel' => $request->type_rappel,
            'statut' => $request->statut,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'en_retard' => $request->en_retard,
        ];

        $rappels = $this->santeRappelApiService->index($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'rappels' => $rappels->map(fn ($rappel) => $this->santeRappelApiService->formatRappel($rappel)),
            'meta'  => [
                'total'        => $rappels->total(),
                'per_page'     => $rappels->perPage(),
                'current_page' => $rappels->currentPage(),
                'last_page'    => $rappels->lastPage(),
            ],
        ], 'Rappels sanitaires récupérés avec succès.');
    }

    /**
     * Créer un rappel sanitaire.
     */
    public function store(StoreSanteRappelRequest $request)
    {
        $this->authorize('create', SanteRappel::class);

        $userId = auth()->id();
        $data = $request->validated();

        try {
            $rappel = $this->santeRappelApiService->store($data, $userId);

            return ApiResponse::success(
                $this->santeRappelApiService->formatRappel($rappel->load(['animal', 'farm', 'evenement'])),
                'Rappel sanitaire créé avec succès.',
                201
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Détail d'un rappel sanitaire — Route Model Binding.
     */
    public function show(SanteRappel $rappel)
    {
        $this->authorize('view', $rappel);

        $rappel->load(['animal', 'farm', 'evenement']);

        return ApiResponse::success(
            $this->santeRappelApiService->formatRappel($rappel),
            'Rappel sanitaire récupéré avec succès.'
        );
    }

    /**
     * Modifier un rappel sanitaire — Route Model Binding.
     */
    public function update(UpdateSanteRappelRequest $request, SanteRappel $rappel)
    {
        $this->authorize('update', $rappel);

        $userId = auth()->id();
        $data = $request->validated();

        try {
            $rappel = $this->santeRappelApiService->update($rappel, $data, $userId);

            return ApiResponse::success(
                $this->santeRappelApiService->formatRappel($rappel->load(['animal', 'farm', 'evenement'])),
                'Rappel sanitaire mis à jour avec succès.'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 409);
        }
    }

    /**
     * Archiver un rappel sanitaire — soft delete — Route Model Binding.
     */
    public function destroy(SanteRappel $rappel)
    {
        $this->authorize('delete', $rappel);

        $this->santeRappelApiService->destroy($rappel);

        return ApiResponse::success(null, 'Rappel sanitaire archivé avec succès.');
    }

    /**
     * Lister les rappels sanitaires archivés.
     */
    public function trashed(Request $request)
    {
        $filters = [
            'animal_id' => $request->animal_id,
            'type_rappel' => $request->type_rappel,
        ];

        $rappels = $this->santeRappelApiService->trashed($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'rappels' => $rappels->map(fn ($rappel) => $this->santeRappelApiService->formatRappel($rappel)),
            'meta'  => [
                'total'        => $rappels->total(),
                'per_page'     => $rappels->perPage(),
                'current_page' => $rappels->currentPage(),
                'last_page'    => $rappels->lastPage(),
            ],
        ], 'Rappels sanitaires archivés récupérés avec succès.');
    }

    /**
     * Restaurer un rappel sanitaire archivé.
     */
    public function restore(string $id)
    {
        $this->authorize('restore', SanteRappel::class);

        $rappel = $this->santeRappelApiService->restore($id);

        return ApiResponse::success(
            $this->santeRappelApiService->formatRappel($rappel->load(['animal', 'farm', 'evenement'])),
            'Rappel sanitaire restauré avec succès.'
        );
    }

    /**
     * Obtenir les rappels à venir.
     */
    public function aVenir(Request $request)
    {
        $this->authorize('view', SanteRappel::class);

        $jours = $request->jours ?? 7;
        $rappels = $this->santeRappelApiService->aVenir($jours);

        return ApiResponse::success([
            'rappels' => $rappels,
            'periode_jours' => $jours,
        ], 'Rappels sanitaires à venir récupérés avec succès.');
    }

    /**
     * Obtenir les rappels en retard.
     */
    public function enRetard(Request $request)
    {
        $this->authorize('view', SanteRappel::class);

        $rappels = $this->santeRappelApiService->enRetard();

        return ApiResponse::success([
            'rappels' => $rappels,
        ], 'Rappels sanitaires en retard récupérés avec succès.');
    }

    /**
     * Marquer un rappel comme réalisé via un événement.
     */
    public function marquerRealise(Request $request, SanteRappel $rappel)
    {
        $this->authorize('update', $rappel);

        $request->validate([
            'type_evenement_id' => 'required|uuid|exists:type_evenements,id',
            'date_evenement' => 'nullable|date',
            'description' => 'nullable|string',
            'cout' => 'nullable|numeric|min:0',
        ]);

        $userId = auth()->id();
        $evenementData = $request->only(['type_evenement_id', 'date_evenement', 'description', 'cout']);

        try {
            $rappel = $this->santeRappelApiService->marquerRealise($rappel, $evenementData, $userId);

            return ApiResponse::success(
                $this->santeRappelApiService->formatRappel($rappel->load(['animal', 'farm', 'evenement'])),
                'Rappel sanitaire marqué comme réalisé avec succès.'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Générer automatiquement des rappels basés sur intervalle_vaccin_jours.
     */
    public function genererRappels(Request $request)
    {
        $this->authorize('create', SanteRappel::class);

        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $userId = auth()->id();

        try {
            $rappelsGeneres = $this->santeRappelApiService->genererRappelsAutomatiques($farmId, $userId);

            return ApiResponse::success([
                'rappels_generes' => collect($rappelsGeneres)->map(fn ($rappel) => $this->santeRappelApiService->formatRappel($rappel->load(['animal', 'farm']))),
                'nombre_rappels' => count($rappelsGeneres),
            ], 'Rappels générés automatiquement avec succès.');
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Reprogrammer un rappel sanitaire.
     */
    public function reprogrammer(Request $request, SanteRappel $rappel)
    {
        $this->authorize('update', $rappel);

        $request->validate([
            'nouvelle_date' => 'required|date',
        ]);

        $userId = auth()->id();
        $nouvelleDate = $request->nouvelle_date;

        try {
            $rappel = $this->santeRappelApiService->reprogrammer($rappel, $nouvelleDate, $userId);

            return ApiResponse::success(
                $this->santeRappelApiService->formatRappel($rappel->load(['animal', 'farm', 'evenement'])),
                'Rappel sanitaire reprogrammé avec succès.'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Obtenir les statistiques globales sanitaires pour la ferme courante.
     */
    public function statistiquesGlobales(Request $request)
    {
        $this->authorize('view', SanteRappel::class);

        $farmId = session('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Aucune ferme courante définie.', 400);
        }

        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;

        $statistiques = $this->santeRappelApiService->statistiquesGlobales($farmId, $dateDebut, $dateFin);

        return ApiResponse::success($statistiques, 'Statistiques globales sanitaires récupérées avec succès.');
    }
}
