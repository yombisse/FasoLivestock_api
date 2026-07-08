<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Animal\StoreAnimalRequest;
use App\Http\Requests\Animal\UpdateAnimalRequest;
use App\Http\Requests\Animal\AnimalNaissanceRequest;
use App\Http\Requests\Animal\AnimalAchatRequest;
use App\Http\Requests\Animal\ImportAnimalRequest;
use App\Helpers\ApiResponse;
use App\Models\Animal;
use App\Services\AnimalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AnimalController extends Controller
{
    private AnimalService $animalService;

    public function __construct(AnimalService $animalService)
    {
        $this->animalService = $animalService;
    }

    // =========================================================
    // MÉTHODES PUBLIQUES
    // =========================================================

    /**
     * Lister tous les animaux avec pagination et filtres.
     */
    public function index(Request $request)
    {
        $filters = [
            'farm_id' => $request->farm_id,
            'search' => $request->search,
            'race' => $request->race,
            'sexe' => $request->sexe,
            'espece_id' => $request->espece_id,
            'lot_id' => $request->lot_id,
            'statut' => $request->statut,
            'date_naissance_from' => $request->date_naissance_from,
            'date_naissance_to' => $request->date_naissance_to,
        ];

        $animals = $this->animalService->index($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'animals' => $animals->map(fn ($animal) => $this->animalService->formatAnimal($animal)),
            'meta'  => [
                'total'        => $animals->total(),
                'per_page'     => $animals->perPage(),
                'current_page' => $animals->currentPage(),
                'last_page'    => $animals->lastPage(),
            ],
        ], 'Animaux récupérés avec succès.');
    }

    /**
     * Créer un animal.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function store(StoreAnimalRequest $request)
    {
        $this->authorize('create', Animal::class);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'POST /animals',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $animal = $this->animalService->store($request->validated());

        return ApiResponse::success(
            $this->animalService->formatAnimal($animal->load('farm', 'espece', 'lot', 'mother')),
            'Animal créé avec succès.',
            201
        );
    }

    /**
     * Détail d'un animal — Route Model Binding.
     */
    public function show(Animal $animal)
    {
        $this->authorize('view', $animal);

        $animal->load('farm', 'espece', 'lot', 'mother');

        return ApiResponse::success(
            $this->animalService->formatAnimal($animal),
            'Animal récupéré avec succès.'
        );
    }

    /**
     * Modifier un animal — Route Model Binding.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function update(UpdateAnimalRequest $request, Animal $animal)
    {
        $this->authorize('update', $animal);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'PUT /animals/{id}',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'animal_id' => $animal->id,
        ]);

        $animal = $this->animalService->update($animal, $request->validated());

        return ApiResponse::success(
            $this->animalService->formatAnimal($animal->load('farm', 'espece', 'lot', 'mother')),
            'Animal mis à jour avec succès.'
        );
    }

    /**
     * Archiver un animal — soft delete — Route Model Binding.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function destroy(Animal $animal)
    {
        $this->authorize('delete', $animal);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'DELETE /animals/{id}',
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'animal_id' => $animal->id,
        ]);

        $this->animalService->destroy($animal);

        return ApiResponse::success(null, 'Animal archivé avec succès.');
    }

    /**
     * Lister les animaux archivés.
     */
    public function trashed(Request $request)
    {
        $filters = [
            'farm_id' => $request->farm_id,
            'search' => $request->search,
        ];

        $animals = $this->animalService->trashed($filters, $request->per_page ?? 15);

        return ApiResponse::success([
            'animals' => $animals->map(fn ($animal) => $this->animalService->formatAnimal($animal)),
            'meta'  => [
                'total'        => $animals->total(),
                'per_page'     => $animals->perPage(),
                'current_page' => $animals->currentPage(),
                'last_page'    => $animals->lastPage(),
            ],
        ], 'Animaux archivés récupérés avec succès.');
    }

    /**
     * Restaurer un animal archivé.
     */
    public function restore(string $id)
    {
        $this->authorize('restore', Animal::class);

        $animal = $this->animalService->restore($id);

        return ApiResponse::success(
            $this->animalService->formatAnimal($animal->load('farm', 'espece', 'lot', 'mother')),
            'Animal restauré avec succès.'
        );
    }

    // =========================================================
    // ACTIONS MÉTIER (MOUVEMENTS)
    // =========================================================

    /**
     * Achat d'un animal.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function purchase(AnimalAchatRequest $request)
    {
        $this->authorize('create', Animal::class);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'POST /animals/purchase',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $animal = $this->animalService->purchase($request->validated());

        return ApiResponse::success(
            $this->animalService->formatAnimal($animal->load('farm', 'espece', 'lot', 'mother')),
            'Animal acheté avec succès.',
            201
        );
    }

    /**
     * Naissance d'un animal.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function birth(AnimalNaissanceRequest $request)
    {
        $this->authorize('create', Animal::class);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'POST /animals/birth',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $animal = $this->animalService->birth($request->validated());

        return ApiResponse::success(
            $this->animalService->formatAnimal($animal->load('farm', 'espece', 'lot', 'mother')),
            'Animal créé par naissance avec succès.',
            201
        );
    }

    /**
     * Vente d'un animal.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function sell(Request $request, Animal $animal)
    {
        $this->authorize('update', $animal);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'POST /animals/{id}/sell',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'animal_id' => $animal->id,
        ]);

        $animal = $this->animalService->sell($animal, $request->all());

        return ApiResponse::success(
            $this->animalService->formatAnimal($animal->load('farm', 'espece', 'lot', 'mother')),
            'Animal vendu avec succès.'
        );
    }

    /**
     * Transfert d'un animal.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function transfer(Request $request, Animal $animal)
    {
        $this->authorize('update', $animal);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'POST /animals/{id}/transfer',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'animal_id' => $animal->id,
        ]);

        $animal = $this->animalService->transfer($animal, $request->all());

        return ApiResponse::success(
            $this->animalService->formatAnimal($animal->load('farm', 'espece', 'lot', 'mother')),
            'Animal transféré avec succès.'
        );
    }

    /**
     * Déclaration de décès.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function declareDeath(Request $request, Animal $animal)
    {
        $this->authorize('update', $animal);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'POST /animals/{id}/declare-death',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'animal_id' => $animal->id,
        ]);

        $animal = $this->animalService->declareDeath($animal, $request->all());

        return ApiResponse::success(
            $this->animalService->formatAnimal($animal->load('farm', 'espece', 'lot', 'mother')),
            'Décès déclaré avec succès.'
        );
    }

    /**
     * Déclaration de perte.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function declareLoss(Request $request, Animal $animal)
    {
        $this->authorize('update', $animal);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'POST /animals/{id}/declare-loss',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'animal_id' => $animal->id,
        ]);

        $animal = $this->animalService->declareLoss($animal, $request->all());

        return ApiResponse::success(
            $this->animalService->formatAnimal($animal->load('farm', 'espece', 'lot', 'mother')),
            'Perte déclarée avec succès.'
        );
    }

    /**
     * Abattage.
     * LEGACY: Cette méthode devrait être remplacée par le canal sync push/pull.
     */
    public function slaughter(Request $request, Animal $animal)
    {
        $this->authorize('update', $animal);

        // Log d'avertissement pour appel hors canal sync
        Log::warning('REST API appelée hors canal sync', [
            'endpoint' => 'POST /animals/{id}/slaughter',
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'animal_id' => $animal->id,
        ]);

        $animal = $this->animalService->slaughter($animal, $request->all());

        return ApiResponse::success(
            $this->animalService->formatAnimal($animal->load('farm', 'espece', 'lot', 'mother')),
            'Abattage enregistré avec succès.'
        );
    }

    /**
     * Vente d'un lot d'animaux.
     */
    public function sellLot(Request $request, \App\Models\Lot $lot)
    {
        $this->authorize('update', Animal::class);

        $resultats = $this->animalService->sellLot($lot, $request->all());

        return ApiResponse::success($resultats, 'Lot vendu avec succès.');
    }

    // =========================================================
    // IMPORT BATCH
    // =========================================================

    public function importBatch(ImportAnimalRequest $request): JsonResponse
    {
        $resultats = $this->animalService->importBatch(
            $request->validated('animaux'),
            $request->validated('farm_id')
        );

        $code = empty($resultats['erreurs']) ? 201 : 207;

        return response()->json([
            'success' => true,
            'message' => "{$resultats['succes']} animal(s) importé(s) avec succès.",
            'data' => [
                'succes'  => $resultats['succes'],
                'erreurs' => $resultats['erreurs'],
            ],
        ], $code);
    }
}
