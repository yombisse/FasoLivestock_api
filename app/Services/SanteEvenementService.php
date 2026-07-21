<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\TypeEvenement;
use App\Models\Animal;
use App\Models\Transaction;
use App\Models\Categorie;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SanteEvenementService
{
    /**
     * Obtenir la liste des événements sanitaires pour une ferme.
     */
    public function index(string $farmId, array $filters = []): array
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

        // Filtre par recherche
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('animal', fn ($a) => $a->where('nom', 'like', "%{$search}%")
                                                  ->orWhere('numero_identification', 'like', "%{$search}%"));
            });
        }

        // Pagination
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        $evenements = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'evenements' => $evenements->items(),
            'meta' => [
                'current_page' => $evenements->currentPage(),
                'last_page' => $evenements->lastPage(),
                'per_page' => $evenements->perPage(),
                'total' => $evenements->total(),
            ],
        ];
    }

    /**
     * Créer un événement sanitaire (vaccination, traitement, maladie, consultation).
     */
    public function store(string $farmId, array $data): Evenement
    {
        return DB::transaction(function () use ($farmId, $data) {
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

            $animal = Animal::find($data['animal_id']);
            $statutAvant = $animal ? $animal->statut : null;
            
            $evenement = Evenement::create([
                'farm_id' => $farmId,
                'type_evenement_id' => $typeEvenement->id,
                'animal_id' => $data['animal_id'],
                'date_evenement' => $data['date_evenement'],
                'description' => $data['description'] ?? null,
                'cout' => $data['cout'] ?? 0,
                'sync_status' => 'synced',
                'last_modified_by' => auth()->id(),
                'version' => 1,
                'statut_avant' => $statutAvant,
                'statut_apres' => $statutAvant, // Sera mis à jour ci-dessous si nécessaire
            ]);

            // Mettre à jour automatiquement le statut de l'animal
            if (strtoupper($data['type']) === 'MALADIE') {
                if ($animal) {
                    $animal->update([
                        'statut' => 'MALADE',
                        'last_modified_by' => auth()->id(),
                        'version' => $animal->version + 1,
                    ]);
                    $evenement->update(['statut_apres' => 'MALADE']);
                }
            }

            if (strtoupper($data['type']) === 'TRAITEMENT') {
                if ($animal) {
                    $animal->update([
                        'statut' => 'EN_TRAITEMENT',
                        'last_modified_by' => auth()->id(),
                        'version' => $animal->version + 1,
                    ]);
                    $evenement->update(['statut_apres' => 'EN_TRAITEMENT']);
                }
            }

            // Créer une transaction financière via le service centralisé si le coût est > 0
            $cout = $data['cout'] ?? 0;
            if ($cout > 0) {
                // Déterminer la catégorie selon le type d'événement
                $typeEvenement = strtoupper($data['type']);
                $categorie = match ($typeEvenement) {
                    'MALADIE' => 'FRAIS_MALADIE',
                    default => 'FRAIS_SANITAIRE',
                };

                app(EvenementTransactionService::class)->creerTransactionDepuisEvenement(
                    $evenement,
                    (float) $cout,
                    $categorie,
                    auth()->id()
                );
            }

            return $evenement->fresh(['transaction']);
        });
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
        return DB::transaction(function () use ($evenementId, $data) {
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

            $oldCout = $evenement->cout;
            $newCout = $data['cout'] ?? $oldCout;

            $evenement->update([
                'date_evenement' => $data['date_evenement'] ?? $evenement->date_evenement,
                'description' => $data['description'] ?? $evenement->description,
                'cout' => $newCout,
                'type_evenement_id' => $data['type_evenement_id'] ?? $evenement->type_evenement_id,
                'last_modified_by' => auth()->id(),
                'version' => $evenement->version + 1,
            ]);

            // Synchroniser la transaction associée via le service centralisé si le coût change
            if ($oldCout !== $newCout) {
                // Déterminer la catégorie selon le type d'événement
                $typeEvenement = $evenement->type?->nom_type ? strtoupper($evenement->type->nom_type) : 'SANITAIRE';
                $categorie = match ($typeEvenement) {
                    'MALADIE' => 'FRAIS_MALADIE',
                    default => 'FRAIS_SANITAIRE',
                };

                app(EvenementTransactionService::class)->synchroniserTransactionDepuisEvenement(
                    $evenement,
                    (float) $newCout,
                    $categorie
                );
            }

            return $evenement->fresh(['transaction']);
        });
    }

    /**
     * Supprimer un événement sanitaire.
     * Soft-delete l'événement et sa transaction associée si elle existe.
     */
    public function destroy(string $evenementId): bool
    {
        return DB::transaction(function () use ($evenementId) {
            $evenement = Evenement::sanitaires()->findOrFail($evenementId);

            // Soft-delete la transaction associée via le service centralisé
            app(EvenementTransactionService::class)->supprimerTransactionDepuisEvenement($evenement);

            // Soft-delete l'événement
            return $evenement->delete();
        });
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
