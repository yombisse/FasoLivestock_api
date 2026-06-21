<?php

namespace App\Services\Admin;

use App\Models\Evenement;
use App\Models\Animal;
use App\Models\TypeEvenement;
use Illuminate\Pagination\LengthAwarePaginator;

class EvenementReproductionApiService
{
    /**
     * Lister les événements de reproduction avec pagination et filtres.
     */
    public function index(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Evenement::query()
            ->with(['animal', 'type', 'farm', 'farmDestination', 'transaction'])
            ->when(isset($filters['animal_id']), fn ($q) =>
                $q->where('animal_id', $filters['animal_id'])
            )
            ->when(isset($filters['type_evenement']), fn ($q) =>
                $q->whereHas('type', fn ($q) =>
                    $q->where('nom_type', $filters['type_evenement'])
                )
            )
            ->when(isset($filters['date_debut']) && isset($filters['date_fin']), fn ($q) =>
                $q->whereBetween('date_evenement', [$filters['date_debut'], $filters['date_fin']])
            )
            ->orderBy('date_evenement', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Créer un événement de reproduction avec gestion offline-first.
     */
    public function store(array $data, string $userId): Evenement
    {
        // Vérification de conflit offline-first
        if (isset($data['version'])) {
            // Pour une création, la version ne devrait pas être fournie
            // Si elle est fournie, on l'ignore
            unset($data['version']);
        }

        $data['sync_status'] = 'synced';
        $data['last_modified_by'] = $userId;
        $data['version'] = 1;

        // Utiliser la ferme courante du contexte si non fournie
        if (!isset($data['farm_id'])) {
            $data['farm_id'] = session('current_farm_id');
        }

        return Evenement::create($data);
    }

    /**
     * Mettre à jour un événement avec gestion offline-first.
     */
    public function update(Evenement $evenement, array $data, string $userId): Evenement
    {
        // Vérification de conflit offline-first
        if (isset($data['version'])) {
            if ($data['version'] !== $evenement->version) {
                // Conflit détecté
                $evenement->update([
                    'sync_status' => 'conflict',
                ]);
                throw new \Exception('Conflit de version détecté. Veuillez synchroniser vos données.');
            }
        }

        $data['sync_status'] = 'synced';
        $data['last_modified_by'] = $userId;
        $data['version'] = ($evenement->version ?? 1) + 1;

        $evenement->update($data);

        return $evenement->fresh();
    }

    /**
     * Supprimer (soft delete) un événement.
     */
    public function destroy(Evenement $evenement): bool
    {
        $evenement->update([
            'sync_status' => 'synced',
            'version' => ($evenement->version ?? 1) + 1,
        ]);

        return $evenement->delete();
    }

    /**
     * Restaurer un événement archivé.
     */
    public function restore(string $id): Evenement
    {
        $evenement = Evenement::onlyTrashed()->findOrFail($id);
        $evenement->restore();

        return $evenement->fresh();
    }

    /**
     * Lister les événements archivés.
     */
    public function trashed(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Evenement::onlyTrashed()
            ->with(['animal', 'type', 'farm'])
            ->when(isset($filters['animal_id']), fn ($q) =>
                $q->where('animal_id', $filters['animal_id'])
            )
            ->when(isset($filters['type_evenement']), fn ($q) =>
                $q->whereHas('type', fn ($q) =>
                    $q->where('nom_type', $filters['type_evenement'])
                )
            )
            ->orderBy('deleted_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Formater un événement pour la réponse API.
     */
    public function formatEvenement(Evenement $evenement): array
    {
        return [
            'id' => $evenement->id,
            'farm_id' => $evenement->farm_id,
            'animal_id' => $evenement->animal_id,
            'type_evenement_id' => $evenement->type_evenement_id,
            'date_evenement' => $evenement->date_evenement,
            'description' => $evenement->description,
            'cout' => $evenement->cout,
            'farm_destination_id' => $evenement->farm_destination_id,
            'statut_avant' => $evenement->statut_avant,
            'statut_apres' => $evenement->statut_apres,
            'transaction_id' => $evenement->transaction_id,
            'sync_status' => $evenement->sync_status,
            'version' => $evenement->version,
            'deleted_at' => $evenement->deleted_at,
            'created_at' => $evenement->created_at,
            'updated_at' => $evenement->updated_at,
            // Relations
            'animal' => $evenement->animal ? [
                'id' => $evenement->animal->id,
                'nom' => $evenement->animal->nom,
                'sexe' => $evenement->animal->sexe,
                'statut' => $evenement->animal->statut,
            ] : null,
            'type' => $evenement->type ? [
                'id' => $evenement->type->id,
                'nom_type' => $evenement->type->nom_type,
                'description' => $evenement->type->description,
            ] : null,
            'farm' => $evenement->farm ? [
                'id' => $evenement->farm->id,
                'name' => $evenement->farm->name,
            ] : null,
            'farm_destination' => $evenement->farmDestination ? [
                'id' => $evenement->farmDestination->id,
                'name' => $evenement->farmDestination->name,
            ] : null,
            'transaction' => $evenement->transaction ? [
                'id' => $evenement->transaction->id,
                'montant' => $evenement->transaction->montant,
            ] : null,
        ];
    }
}
