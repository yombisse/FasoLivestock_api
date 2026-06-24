<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\TypeEvenement;
use App\Models\Animal;
use Illuminate\Database\Eloquent\Collection;

class SanteEvenementService
{
    /**
     * Obtenir la liste des événements sanitaires pour une ferme.
     */
    public function index(string $farmId, array $filters = []): Collection
    {
        $query = Evenement::where('farm_id', $farmId)
            ->sanitaires()
            ->with(['animal', 'animal.espece', 'type'])
            ->orderBy('date_evenement', 'desc');

        // Filtre par type d'événement
        if (!empty($filters['type'])) {
            $query->whereHas('type', fn ($q) => $q->where('nom_type', $filters['type']));
        }

        // Filtre par animal
        if (!empty($filters['animal_id'])) {
            $query->where('animal_id', $filters['animal_id']);
        }

        // Filtre par période
        if (!empty($filters['date_debut']) && !empty($filters['date_fin'])) {
            $query->whereBetween('date_evenement', [$filters['date_debut'], $filters['date_fin']]);
        }

        return $query->get();
    }

    /**
     * Créer un événement sanitaire (vaccination, traitement, maladie, consultation).
     */
    public function store(string $farmId, array $data): Evenement
    {
        // Récupérer ou créer le type d'événement
        $typeEvenement = TypeEvenement::firstOrCreate(
            ['nom_type' => strtoupper($data['type'])],
            [
                'description' => $this->getTypeDescription($data['type']),
                'farm_id' => null, // Type global
                'sync_status' => 'synced',
                'version' => 1,
            ]
        );

        return Evenement::create([
            'farm_id' => $farmId,
            'type_evenement_id' => $typeEvenement->id,
            'animal_id' => $data['animal_id'],
            'date_evenement' => $data['date_evenement'],
            'description' => $data['description'] ?? null,
            'cout' => $data['cout'] ?? 0,
            'sync_status' => 'synced',
            'last_modified_by' => auth()->id(),
            'version' => 1,
        ]);
    }

    /**
     * Obtenir un événement sanitaire par son ID.
     */
    public function show(string $evenementId): Evenement
    {
        return Evenement::with(['animal', 'animal.espece', 'type', 'farm'])
            ->sanitaires()
            ->findOrFail($evenementId);
    }

    /**
     * Modifier un événement sanitaire.
     */
    public function update(string $evenementId, array $data): Evenement
    {
        $evenement = Evenement::sanitaires()->findOrFail($evenementId);

        // Si le type change, mettre à jour le type_evenement_id
        if (!empty($data['type'])) {
            $typeEvenement = TypeEvenement::firstOrCreate(
                ['nom_type' => strtoupper($data['type'])],
                [
                    'description' => $this->getTypeDescription($data['type']),
                    'farm_id' => null,
                    'sync_status' => 'synced',
                    'version' => 1,
                ]
            );
            $data['type_evenement_id'] = $typeEvenement->id;
        }

        $evenement->update([
            'date_evenement' => $data['date_evenement'] ?? $evenement->date_evenement,
            'description' => $data['description'] ?? $evenement->description,
            'cout' => $data['cout'] ?? $evenement->cout,
            'type_evenement_id' => $data['type_evenement_id'] ?? $evenement->type_evenement_id,
            'last_modified_by' => auth()->id(),
            'version' => $evenement->version + 1,
        ]);

        return $evenement->fresh();
    }

    /**
     * Supprimer un événement sanitaire.
     */
    public function destroy(string $evenementId): bool
    {
        $evenement = Evenement::sanitaires()->findOrFail($evenementId);
        return $evenement->delete();
    }

    /**
     * Obtenir les vaccinations d'un animal.
     */
    public function vaccinationsAnimal(string $animalId): Collection
    {
        return Evenement::where('animal_id', $animalId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'VACCINATION'))
            ->with(['type'])
            ->orderBy('date_evenement', 'desc')
            ->get();
    }

    /**
     * Obtenir les traitements d'un animal.
     */
    public function traitementsAnimal(string $animalId): Collection
    {
        return Evenement::where('animal_id', $animalId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'TRAITEMENT'))
            ->with(['type'])
            ->orderBy('date_evenement', 'desc')
            ->get();
    }

    /**
     * Obtenir les maladies d'un animal.
     */
    public function maladiesAnimal(string $animalId): Collection
    {
        return Evenement::where('animal_id', $animalId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'MALADIE'))
            ->with(['type'])
            ->orderBy('date_evenement', 'desc')
            ->get();
    }

    /**
     * Obtenir les consultations d'un animal.
     */
    public function consultationsAnimal(string $animalId): Collection
    {
        return Evenement::where('animal_id', $animalId)
            ->whereHas('type', fn ($q) => $q->where('nom_type', 'CONTROLE'))
            ->with(['type'])
            ->orderBy('date_evenement', 'desc')
            ->get();
    }

    /**
     * Obtenir les statistiques sanitaires par type pour une ferme.
     */
    public function statistiquesParType(string $farmId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = Evenement::where('farm_id', $farmId)->sanitaires();

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_evenement', [$dateDebut, $dateFin]);
        }

        $evenements = $query->with('type')->get();

        $types = ['VACCINATION', 'TRAITEMENT', 'MALADIE', 'CONTROLE'];
        $statistiques = [];

        foreach ($types as $type) {
            $evenementsType = $evenements->filter(fn ($e) => strtoupper($e->type->nom_type ?? '') === $type);
            $statistiques[strtolower($type)] = [
                'nombre' => $evenementsType->count(),
                'cout_total' => $evenementsType->sum('cout'),
            ];
        }

        return $statistiques;
    }

    /**
     * Obtenir la description par défaut pour un type d'événement.
     */
    private function getTypeDescription(string $type): string
    {
        return match (strtoupper($type)) {
            'VACCINATION' => 'Vaccination ou immunisation',
            'TRAITEMENT' => 'Traitement médical ou thérapeutique',
            'MALADIE' => 'Maladie ou pathologie diagnostiquée',
            'CONTROLE' => 'Consultation vétérinaire ou contrôle sanitaire',
            default => 'Événement sanitaire',
        };
    }
}
