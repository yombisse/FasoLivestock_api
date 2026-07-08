<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reproduction\StoreNaissanceRequest;
use App\Http\Requests\Reproduction\UpdateNaissanceRequest;
use App\Helpers\ApiResponse;
use App\Models\Naissance;
use App\Models\Animal;
use App\Services\NaissanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NaissanceController extends Controller
{
    private NaissanceService $naissanceService;

    public function __construct(NaissanceService $naissanceService)
    {
        $this->naissanceService = $naissanceService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Lister toutes les naissances avec pagination et filtres.
     */
    public function index(Request $request)
    {
        $filters = [
            'mother_id' => $request->mother_id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'mises_bas_venir' => $request->mises_bas_venir,
        ];

        $naissances = $this->naissanceService->index($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'naissances' => $naissances->map(fn ($naissance) => $this->naissanceService->formatNaissance($naissance)),
            'meta'  => [
                'total'        => $naissances->total(),
                'per_page'     => $naissances->perPage(),
                'current_page' => $naissances->currentPage(),
                'last_page'    => $naissances->lastPage(),
            ],
        ], 'Naissances récupérées avec succès.');
    }

    /**
     * Créer une naissance.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function store(StoreNaissanceRequest $request)
    {
        $this->authorize('create', Naissance::class);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'POST /reproduction/naissances',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $userId = auth()->id();
        $data = $request->validated();

        try {
            $naissance = $this->naissanceService->store($data, $userId);

            return ApiResponse::success(
                $this->naissanceService->formatNaissance($naissance->load(['mother', 'farm', 'evenement', 'petits'])),
                'Naissance créée avec succès.',
                201
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 400);
        }
    }

    /**
     * Détail d'une naissance — Route Model Binding.
     */
    public function show(Naissance $naissance)
    {
        $this->authorize('view', $naissance);

        $naissance->load(['mother', 'farm', 'evenement', 'petits']);

        return ApiResponse::success(
            $this->naissanceService->formatNaissance($naissance),
            'Naissance récupérée avec succès.'
        );
    }

    /**
     * Modifier une naissance — Route Model Binding.
     */
    public function update(UpdateNaissanceRequest $request, Naissance $naissance)
    {
        $this->authorize('update', $naissance);

        $userId = auth()->id();
        $data = $request->validated();

        try {
            $naissance = $this->naissanceService->update($naissance, $data, $userId);

            return ApiResponse::success(
                $this->naissanceService->formatNaissance($naissance->load(['mother', 'farm', 'evenement', 'petits'])),
                'Naissance mise à jour avec succès.'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage(), 409);
        }
    }

    /**
     * Archiver une naissance — soft delete — Route Model Binding.
     */
    public function destroy(Naissance $naissance)
    {
        $this->authorize('delete', $naissance);

        $this->naissanceService->destroy($naissance);

        return ApiResponse::success(null, 'Naissance archivée avec succès.');
    }

    /**
     * Lister les naissances archivées.
     */
    public function trashed(Request $request)
    {
        $filters = [
            'mother_id' => $request->mother_id,
        ];

        $naissances = $this->naissanceService->trashed($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'naissances' => $naissances->map(fn ($naissance) => $this->naissanceService->formatNaissance($naissance)),
            'meta'  => [
                'total'        => $naissances->total(),
                'per_page'     => $naissances->perPage(),
                'current_page' => $naissances->currentPage(),
                'last_page'    => $naissances->lastPage(),
            ],
        ], 'Naissances archivées récupérées avec succès.');
    }

    /**
     * Restaurer une naissance archivée.
     */
    public function restore(string $id)
    {
        $this->authorize('restore', Naissance::class);

        $naissance = $this->naissanceService->restore($id);

        return ApiResponse::success(
            $this->naissanceService->formatNaissance($naissance->load(['mother', 'farm', 'evenement', 'petits'])),
            'Naissance restaurée avec succès.'
        );
    }

    /**
     * Obtenir les prévisions de mises bas.
     */
    public function previsions(Request $request)
    {
        $this->authorize('view', Naissance::class);

        $jours = $request->jours ?? 30;
        $previsions = $this->naissanceService->previsions($jours);

        return ApiResponse::success([
            'previsions' => $previsions,
            'periode_jours' => $jours,
        ], 'Prévisions de mises bas récupérées avec succès.');
    }

    /**
     * Obtenir les femelles éligibles à une déclaration de naissance
     * (femelles avec une gestation confirmée en cours).
     */
    public function femellesEligibles(Request $request)
    {
        // $this->authorize('reproduction.view'); // Désactivé pour test

        $farmId = $request->farm_id ?? $request->input('current_farm_id');

        if (!$farmId) {
            return ApiResponse::error(null, 'Ferme non spécifiée.', 400);
        }

        $femelles = Animal::eligiblesNaissance($farmId)
            ->with(['espece', 'lot'])
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'nom' => $a->nom,
                'numero_identification' => $a->numero_identification,
                'espece' => $a->espece?->nom,
                'race' => $a->race,
                'date_gestation' => $a->evenements()
                    ->whereHas('type', fn($q) =>
                        $q->where('nom_type', 'Gestation confirmée')
                    )
                    ->where('statut', 'EN_COURS')
                    ->latest('date_evenement')
                    ->value('date_evenement'),
            ]);

        return ApiResponse::success($femelles, 'Femelles éligibles récupérées avec succès.');
    }
}
