<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Animal\StoreAnimalRequest;
use App\Http\Requests\Animal\UpdateAnimalRequest;
use App\Helpers\ApiResponse;
use App\Models\Animal;
use App\Services\AnimalService;
use Illuminate\Http\Request;

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
     */
    public function store(StoreAnimalRequest $request)
    {
        $this->authorize('create', Animal::class);

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
     */
    public function update(UpdateAnimalRequest $request, Animal $animal)
    {
        $this->authorize('update', $animal);

        $animal = $this->animalService->update($animal, $request->validated());

        return ApiResponse::success(
            $this->animalService->formatAnimal($animal->load('farm', 'espece', 'lot', 'mother')),
            'Animal mis à jour avec succès.'
        );
    }

    /**
     * Archiver un animal — soft delete — Route Model Binding.
     */
    public function destroy(Animal $animal)
    {
        $this->authorize('delete', $animal);

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
}
