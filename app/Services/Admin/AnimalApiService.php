<?php

namespace App\Services\Admin;

use App\Models\Animal;
use Illuminate\Pagination\LengthAwarePaginator;

class AnimalApiService
{
    /**
     * Lister les animaux avec pagination et filtres.
     */
    public function index(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Animal::query()
            ->with(['farm', 'espece', 'lot', 'mother'])
            ->when(isset($filters['farm_id']), fn ($q) =>
                $q->where('farm_id', $filters['farm_id'])
            )
            ->when(isset($filters['search']), fn ($q) =>
                $q->where(fn ($q) =>
                    $q->where('nom', 'like', "%{$filters['search']}%")
                      ->orWhere('race', 'like', "%{$filters['search']}%")
                      ->orWhere('numero_identification', 'like', "%{$filters['search']}%")
                )
            )
            ->when(isset($filters['race']), fn ($q) =>
                $q->where('race', 'like', "%{$filters['race']}%")
            )
            ->when(isset($filters['sexe']), fn ($q) =>
                $q->where('sexe', $filters['sexe'])
            )
            ->when(isset($filters['espece_id']), fn ($q) =>
                $q->where('espece_id', $filters['espece_id'])
            )
            ->when(isset($filters['lot_id']), fn ($q) =>
                $q->where('lot_id', $filters['lot_id'])
            )
            ->when(isset($filters['statut']), fn ($q) =>
                $q->where('statut', $filters['statut'])
            )
            ->when(isset($filters['date_naissance_from']), fn ($q) =>
                $q->whereDate('date_naissance', '>=', $filters['date_naissance_from'])
            )
            ->when(isset($filters['date_naissance_to']), fn ($q) =>
                $q->whereDate('date_naissance', '<=', $filters['date_naissance_to'])
            )
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Créer un animal.
     */
    public function store(array $data): Animal
    {
        $data['sync_status'] = 'synced';
        $data['version'] = 1;

        return Animal::create($data);
    }

    /**
     * Mettre à jour un animal.
     */
    public function update(Animal $animal, array $data): Animal
    {
        $data['sync_status'] = 'synced';
        $data['version'] = ($animal->version ?? 1) + 1;

        $animal->update($data);

        return $animal->fresh();
    }

    /**
     * Supprimer (soft delete) un animal.
     */
    public function destroy(Animal $animal): bool
    {
        $animal->update([
            'sync_status' => 'synced',
            'version' => ($animal->version ?? 1) + 1,
        ]);

        return $animal->delete();
    }

    /**
     * Restaurer un animal archivé.
     */
    public function restore(string $id): Animal
    {
        $animal = Animal::onlyTrashed()->findOrFail($id);
        $animal->restore();

        return $animal->fresh();
    }

    /**
     * Lister les animaux archivés.
     */
    public function trashed(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Animal::onlyTrashed()
            ->with(['farm', 'espece', 'lot', 'mother'])
            ->when(isset($filters['farm_id']), fn ($q) =>
                $q->where('farm_id', $filters['farm_id'])
            )
            ->when(isset($filters['search']), fn ($q) =>
                $q->where(fn ($q) =>
                    $q->where('nom', 'like', "%{$filters['search']}%")
                      ->orWhere('race', 'like', "%{$filters['search']}%")
                )
            )
            ->orderBy('deleted_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Formater un animal pour la réponse API.
     */
    public function formatAnimal(Animal $animal): array
    {
        return [
            'id'                   => $animal->id,
            'farm_id'              => $animal->farm_id,
            'nom'                  => $animal->nom,
            'race'                 => $animal->race,
            'sexe'                 => $animal->sexe,
            'date_naissance'       => $animal->date_naissance,
            'poids'                => $animal->poids,
            'espece_id'            => $animal->espece_id,
            'lot_id'               => $animal->lot_id,
            'mother_id'            => $animal->mother_id,
            'statut'               => $animal->statut,
            'numero_identification' => $animal->numero_identification,
            'photo'                => $animal->photo,
            'naissance_id'         => $animal->naissance_id,
            'sync_status'          => $animal->sync_status,
            'version'              => $animal->version,
            'deleted_at'           => $animal->deleted_at,
            'created_at'           => $animal->created_at,
            'updated_at'           => $animal->updated_at,
            // Relations
            'farm'                 => $animal->farm ? [
                'id'   => $animal->farm->id,
                'name' => $animal->farm->name,
            ] : null,
            'espece'               => $animal->espece ? [
                'id'   => $animal->espece->id,
                'nom'  => $animal->espece->nom,
            ] : null,
            'lot'                  => $animal->lot ? [
                'id'   => $animal->lot->id,
                'nom'  => $animal->lot->nom,
            ] : null,
            'mother'               => $animal->mother ? [
                'id'   => $animal->mother->id,
                'nom'  => $animal->mother->nom,
            ] : null,
        ];
    }
}
