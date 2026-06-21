<?php

namespace App\Services\Admin;

use App\Models\Lot;
use App\Models\Animal;
use Illuminate\Pagination\LengthAwarePaginator;

class LotApiService
{
    /**
     * Lister les lots avec pagination et recherche.
     */
    public function index(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Lot::query()
            ->withCount('animals')
            ->with(['farm'])
            ->when(isset($filters['search']), fn ($q) =>
                $q->where('nom_lot', 'like', "%{$filters['search']}%")
            )
            ->orderBy('nom_lot', 'asc');

        return $query->paginate($perPage);
    }

    /**
     * Créer un lot.
     */
    public function store(array $data, string $userId): Lot
    {
        $data['farm_id'] = $data['farm_id'] ?? null;
        $data['sync_status'] = 'synced';
        $data['last_modified_by'] = $userId;
        $data['version'] = 1;

        return Lot::create($data);
    }

    /**
     * Mettre à jour un lot.
     */
    public function update(Lot $lot, array $data, string $userId): Lot
    {
        $data['sync_status'] = 'synced';
        $data['last_modified_by'] = $userId;
        $data['version'] = ($lot->version ?? 1) + 1;

        $lot->update($data);

        return $lot->fresh();
    }

    /**
     * Supprimer (soft delete) un lot.
     */
    public function destroy(Lot $lot): bool
    {
        $lot->update([
            'sync_status' => 'synced',
            'version' => ($lot->version ?? 1) + 1,
        ]);

        return $lot->delete();
    }

    /**
     * Restaurer un lot archivé.
     */
    public function restore(string $id): Lot
    {
        $lot = Lot::onlyTrashed()->findOrFail($id);
        $lot->restore();

        return $lot->fresh();
    }

    /**
     * Lister les lots archivés.
     */
    public function trashed(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Lot::onlyTrashed()
            ->withCount('animals')
            ->with(['farm'])
            ->when(isset($filters['search']), fn ($q) =>
                $q->where('nom_lot', 'like', "%{$filters['search']}%")
            )
            ->orderBy('deleted_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Lister les animaux d'un lot.
     */
    public function animals(Lot $lot, int $perPage = 15): LengthAwarePaginator
    {
        return Animal::query()
            ->where('lot_id', $lot->id)
            ->with(['farm', 'espece', 'lot'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Affecter des animaux à un lot.
     */
    public function assignAnimals(Lot $lot, array $animalIds, string $userId): array
    {
        $animals = Animal::whereIn('id', $animalIds)->get();

        // Vérifier que tous les animaux appartiennent à la même farm que le lot
        foreach ($animals as $animal) {
            if ($animal->farm_id !== $lot->farm_id) {
                throw new \Exception('Un ou plusieurs animaux n\'appartiennent pas à la même ferme que le lot.');
            }
        }

        // Mettre à jour le lot_id de chaque animal
        foreach ($animals as $animal) {
            $animal->update([
                'lot_id' => $lot->id,
                'sync_status' => 'synced',
                'last_modified_by' => $userId,
                'version' => ($animal->version ?? 1) + 1,
            ]);
        }

        return $animals->pluck('id')->toArray();
    }

    /**
     * Retirer un animal d'un lot.
     */
    public function removeAnimal(Lot $lot, string $animalId, string $userId): bool
    {
        $animal = Animal::findOrFail($animalId);

        // Vérifier que l'animal appartient au lot
        if ($animal->lot_id !== $lot->id) {
            throw new \Exception('L\'animal n\'appartient pas à ce lot.');
        }

        // Vérifier que l'animal appartient à la même farm que le lot
        if ($animal->farm_id !== $lot->farm_id) {
            throw new \Exception('L\'animal n\'appartient pas à la même ferme que le lot.');
        }

        $animal->update([
            'lot_id' => null,
            'sync_status' => 'synced',
            'last_modified_by' => $userId,
            'version' => ($animal->version ?? 1) + 1,
        ]);

        return true;
    }

    /**
     * Statistiques d'un lot.
     */
    public function statistiques(Lot $lot): array
    {
        $animals = $lot->animals;

        return [
            'animals_count' => $animals->count(),
            'males' => $animals->where('sexe', 'male')->count(),
            'femelles' => $animals->where('sexe', 'femelle')->count(),
        ];
    }

    /**
     * Formater un lot pour la réponse API.
     */
    public function formatLot(Lot $lot): array
    {
        return [
            'id' => $lot->id,
            'farm_id' => $lot->farm_id,
            'nom_lot' => $lot->nom_lot,
            'animals_count' => $lot->animals_count ?? $lot->animals()->count(),
            'sync_status' => $lot->sync_status,
            'version' => $lot->version,
            'deleted_at' => $lot->deleted_at,
            'created_at' => $lot->created_at,
            'updated_at' => $lot->updated_at,
            // Relations
            'farm' => $lot->farm ? [
                'id' => $lot->farm->id,
                'name' => $lot->farm->name,
            ] : null,
        ];
    }
}
