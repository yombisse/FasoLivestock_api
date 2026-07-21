<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reproduction\StoreEvenementReproductionRequest;
use App\Http\Requests\Reproduction\UpdateEvenementReproductionRequest;
use App\Http\Requests\Reproduction\SaillieRequest;
use App\Http\Requests\Reproduction\ChaleurRequest;
use App\Http\Requests\Reproduction\GestationRequest;
use App\Http\Requests\Reproduction\MiseBasRequest;
use App\Helpers\ApiResponse;
use App\Models\Evenement;
use App\Models\TypeEvenement;
use App\Services\EvenementReproductionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Evenement::class);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'POST /reproduction/evenements',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $userId = auth()->id();
        
        // Déterminer le type d'événement pour utiliser le bon FormRequest
        $typeEvenementId = $request->type_evenement_id;
        $typeEvenement = TypeEvenement::find($typeEvenementId);
        
        if (!$typeEvenement) {
            return ApiResponse::error(null, 'Type d\'événement introuvable.', 400);
        }

        $nomType = strtoupper($typeEvenement->nom_type ?? '');

        // Bloquer la création manuelle d'événements MISE BAS
        // Ils sont créés automatiquement lors de la déclaration de naissance
        if ($nomType === 'MISE BAS') {
            return ApiResponse::error(null, 'Les événements MISE BAS sont créés automatiquement lors de la déclaration de naissance.', 400);
        }

        // Validation selon le type d'événement
        $validator = null;
        switch ($nomType) {
            case 'SAILLIE':
                $validator = validator($request->all(), [
                    'animal_id' => 'required|string|min:16|max:20|exists:animals,id',
                    'date_evenement' => 'required|date',
                    'description' => 'nullable|string',
                    'cout' => 'nullable|numeric|min:0',
                    'male_id' => 'nullable|string|min:16|max:20|exists:animals,id',
                    'metadonnees.male_id' => 'nullable|string|min:16|max:20|exists:animals,id',
                    'metadonnees.male_nom' => 'nullable|string|max:255',
                    'metadonnees.type_saillie' => 'nullable|in:naturelle,insemination_artificielle',
                    'metadonnees.veterinaire' => 'nullable|string|max:255',
                    'metadonnees.success' => 'nullable|boolean',
                    'metadonnees.nombre_tentatives' => 'nullable|integer|min:1',
                    'metadonnees.note' => 'nullable|string',
                ]);
                break;
            case 'CHALEUR':
                $validator = validator($request->all(), [
                    'animal_id' => 'required|string|min:16|max:20|exists:animals,id',
                    'date_evenement' => 'required|date',
                    'description' => 'nullable|string',
                    'cout' => 'nullable|numeric|min:0',
                ]);
                break;
            case 'GESTATION CONFIRMÉE':
            case 'GESTATION CONFIRMEE':
                $validator = validator($request->all(), [
                    'animal_id' => 'required|string|min:16|max:20|exists:animals,id',
                    'date_evenement' => 'required|date',
                    'description' => 'nullable|string',
                    'cout' => 'nullable|numeric|min:0',
                ]);
                break;
            default:
                // Pour les autres types, utiliser le FormRequest générique
                $validator = validator($request->all(), [
                    'animal_id' => 'required|string|min:16|max:20|exists:animals,id',
                    'date_evenement' => 'required|date',
                    'description' => 'nullable|string',
                    'cout' => 'nullable|numeric|min:0',
                ]);
                break;
        }

        if ($validator && $validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $request->all();

        try {
            $evenement = $this->evenementReproductionService->store($validated, $userId);

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
