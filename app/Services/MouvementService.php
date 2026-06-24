<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\Animal;
use App\Models\TypeEvenement;
use Illuminate\Pagination\LengthAwarePaginator;

class MouvementService
{
    private ActivityLogService $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }
    /**
     * Déterminer le statut après mouvement selon le type.
     */
    private function getStatutApres(string $typeNom): string
    {
        return match (strtoupper($typeNom)) {
            'ACHAT' => 'ACTIF',
            'VENTE' => 'VENDU',
            'DECES' => 'DECEDE',
            'PERTE' => 'PERDU',
            'ABATTAGE' => 'ABATTU',
            'TRANSFERT' => 'TRANSFERE',
            default => 'ACTIF',
        };
    }

    /**
     * Lister les mouvements avec pagination et filtres.
     */
    public function index(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Evenement::query()
            ->mouvements()
            ->with(['farm', 'animal', 'type', 'farmDestination', 'transaction'])
            ->when(isset($filters['date_debut']), fn ($q) =>
                $q->whereDate('date_evenement', '>=', $filters['date_debut'])
            )
            ->when(isset($filters['date_fin']), fn ($q) =>
                $q->whereDate('date_evenement', '<=', $filters['date_fin'])
            )
            ->when(isset($filters['animal_id']), fn ($q) =>
                $q->where('animal_id', $filters['animal_id'])
            )
            ->when(isset($filters['type']), fn ($q) =>
                $q->whereHas('type', fn ($q) =>
                    $q->where('nom_type', $filters['type'])
                )
            )
            ->when(isset($filters['statut']), fn ($q) =>
                $q->where('statut_apres', $filters['statut'])
            )
            ->orderBy('date_evenement', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Créer un mouvement avec règles métier.
     */
    public function store(array $data, string $userId): Evenement
    {
        $typeEvenement = TypeEvenement::findOrFail($data['type_evenement_id']);
        $typeNom = strtoupper($typeEvenement->nom_type);

        // Récupérer le statut avant de l'animal
        $animal = Animal::findOrFail($data['animal_id']);
        $data['statut_avant'] = $animal->statut ?? 'ACTIF';

        // Déterminer le statut après selon le type
        $data['statut_apres'] = $this->getStatutApres($typeNom);

        // Utiliser la ferme courante du contexte
        $data['farm_id'] = $data['farm_id'] ?? null;
        $data['sync_status'] = 'synced';
        $data['last_modified_by'] = $userId;
        $data['version'] = 1;

        $evenement = Evenement::create($data);

        // Log activity after successful creation
        $this->activityLog->log('created', $evenement, null, $data);

        return $evenement;
    }

    /**
     * Mettre à jour un mouvement.
     */
    public function update(Evenement $evenement, array $data, string $userId): Evenement
    {
        $data['sync_status'] = 'synced';
        $data['last_modified_by'] = $userId;
        $data['version'] = ($evenement->version ?? 1) + 1;

        $oldValues = $evenement->toArray();
        
        $evenement->update($data);

        // Log activity after successful update
        $this->activityLog->log('updated', $evenement, $oldValues, $data);

        return $evenement->fresh();
    }

    /**
     * Supprimer (soft delete) un mouvement.
     */
    public function destroy(Evenement $evenement): bool
    {
        $evenement->update([
            'sync_status' => 'synced',
            'version' => ($evenement->version ?? 1) + 1,
        ]);

        $oldValues = $evenement->toArray();
        
        $result = $evenement->delete();

        // Log activity after successful deletion
        if ($result) {
            $this->activityLog->log('deleted', $evenement, $oldValues, null);
        }

        return $result;
    }

    /**
     * Historique des mouvements d'un animal.
     */
    public function animalHistory(Animal $animal, int $perPage = 15): LengthAwarePaginator
    {
        return Evenement::query()
            ->mouvements()
            ->where('animal_id', $animal->id)
            ->with(['farm', 'type', 'farmDestination'])
            ->orderBy('date_evenement', 'desc')
            ->paginate($perPage);
    }

    /**
     * Traçabilité complète d'un animal.
     */
    public function trace(Animal $animal): array
    {
        $mouvements = Evenement::query()
            ->mouvements()
            ->where('animal_id', $animal->id)
            ->with(['farm', 'type', 'farmDestination'])
            ->orderBy('date_evenement', 'asc')
            ->get();

        $dernierMouvement = $mouvements->last();
        $provenance = $mouvements->first()?->farm;
        $destination = $dernierMouvement?->farmDestination ?? $animal->farm;

        return [
            'animal' => [
                'id' => $animal->id,
                'nom' => $animal->nom,
                'numero_identification' => $animal->numero_identification,
                'statut' => $animal->statut,
                'ferme_actuelle' => $animal->farm ? [
                    'id' => $animal->farm->id,
                    'name' => $animal->farm->name,
                ] : null,
            ],
            'ferme_actuelle' => $animal->farm ? [
                'id' => $animal->farm->id,
                'name' => $animal->farm->name,
            ] : null,
            'historique' => $mouvements->map(fn ($m) => $this->formatMouvement($m)),
            'dernier_mouvement' => $dernierMouvement ? $this->formatMouvement($dernierMouvement) : null,
            'provenance' => $provenance ? [
                'id' => $provenance->id,
                'name' => $provenance->name,
            ] : null,
            'destination' => $destination ? [
                'id' => $destination->id,
                'name' => $destination->name,
            ] : null,
        ];
    }

    /**
     * Statistiques des mouvements pour une ferme.
     */
    public function statistiques(string $farmId): array
    {
        $query = Evenement::query()
            ->mouvements()
            ->where('farm_id', $farmId);

        return [
            'achats' => (clone $query)->whereHas('type', fn ($q) =>
                $q->where('nom_type', 'ACHAT')
            )->count(),
            'ventes' => (clone $query)->whereHas('type', fn ($q) =>
                $q->where('nom_type', 'VENTE')
            )->count(),
            'deces' => (clone $query)->whereHas('type', fn ($q) =>
                $q->where('nom_type', 'DECES')
            )->count(),
            'pertes' => (clone $query)->whereHas('type', fn ($q) =>
                $q->where('nom_type', 'PERTE')
            )->count(),
            'abattages' => (clone $query)->whereHas('type', fn ($q) =>
                $q->where('nom_type', 'ABATTAGE')
            )->count(),
            'transferts' => (clone $query)->whereHas('type', fn ($q) =>
                $q->where('nom_type', 'TRANSFERT')
            )->count(),
        ];
    }

    /**
     * Formater un mouvement pour la réponse API.
     */
    public function formatMouvement(Evenement $evenement): array
    {
        return [
            'id' => $evenement->id,
            'farm_id' => $evenement->farm_id,
            'type_evenement_id' => $evenement->type_evenement_id,
            'animal_id' => $evenement->animal_id,
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
            'farm' => $evenement->farm ? [
                'id' => $evenement->farm->id,
                'name' => $evenement->farm->name,
            ] : null,
            'animal' => $evenement->animal ? [
                'id' => $evenement->animal->id,
                'nom' => $evenement->animal->nom,
                'numero_identification' => $evenement->animal->numero_identification,
            ] : null,
            'type' => $evenement->type ? [
                'id' => $evenement->type->id,
                'nom_type' => $evenement->type->nom_type,
            ] : null,
            'farm_destination' => $evenement->farmDestination ? [
                'id' => $evenement->farmDestination->id,
                'name' => $evenement->farmDestination->name,
            ] : null,
            'transaction' => $evenement->transaction ? [
                'id' => $evenement->transaction->id,
                'type' => $evenement->transaction->type,
                'montant' => $evenement->transaction->montant,
            ] : null,
        ];
    }
}
