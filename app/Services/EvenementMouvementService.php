<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\Animal;
use App\Models\TypeEvenement;
use Illuminate\Pagination\LengthAwarePaginator;

class EvenementMouvementService
{
    private ActivityLogService $activityLog;
    private EvenementService $evenementService;

    public function __construct(ActivityLogService $activityLog, EvenementService $evenementService)
    {
        $this->activityLog = $activityLog;
        $this->evenementService = $evenementService;
    }
    /**
     * Déterminer le statut après mouvement selon le type.
     */
    private function getStatutApres(string $typeNom): ?string
    {
        return match(strtoupper($typeNom)) {
            'VENTE'     => 'VENDU',
            'DECES'     => 'DECEDE',
            'PERTE'     => 'PERDU',
            'ABATTAGE'  => 'ABATTU',
            'TRANSFERT' => 'TRANSFERE',
            default     => null, // ACHAT et autres : statut reste ACTIF
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

        // Dériver la categorie du TypeEvenement
        if (!isset($data['categorie'])) {
            $data['categorie'] = $typeEvenement->categorie ?? 'MOUVEMENT';
        }

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
     * Achat d'un animal avec création de l'animal et événement traçable.
     *
     * @param array $data Données de l'achat
     * @param string $farmId ID de la ferme
     * @return Animal
     */
    public function achat(array $data, string $farmId): Animal
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $farmId) {
            $animalData = collect($data)->except([
                'prix_achat', 'date_achat', 'provenance', 'type_evenement_id', 'source_type'
            ])->toArray();
            $animalData['origine'] = 'achat';
            $animalData['farm_id'] = $farmId;

            $animal = Animal::create($animalData);

            // Déterminer la description
            $description = null;
            if (!empty($data['farm_source_id'])) {
                $sourceFarm = \App\Models\Farm::find($data['farm_source_id']);
                $description = $sourceFarm ? $sourceFarm->name : null;
            } elseif (!empty($data['provenance'])) {
                $description = $data['provenance'];
            }

            // Créer la transaction
            $categorieAchat = \App\Models\Categorie::where('nom_categorie', 'Achat d\'animaux')->first();

            $transaction = \App\Models\Transaction::create([
                'farm_id' => $farmId,
                'animal_id' => $animal->id,
                'type_transaction' => 'SORTIE',
                'montant' => $data['prix_achat'] ?? 0,
                'date_transaction' => $data['date_achat'] ?? now(),
                'description' => $description,
                'categorie_id' => $categorieAchat?->id,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'sync_status' => 'synced',
                'version' => 1,
            ]);

            // Mettre à jour le statut de l'animal AVANT de créer l'événement
            $animal->update(['statut' => 'ACTIF']);

            // Créer l'événement via EvenementService
            $typeAchat = TypeEvenement::where('nom_type', 'Achat')
                ->whereNull('farm_id')
                ->firstOrFail();

            $this->evenementService->creerMouvement([
                'farm_id' => $farmId,
                'type_evenement_id' => $typeAchat->id,
                'animal_id' => $animal->id,
                'date_evenement' => $data['date_achat'] ?? now(),
                'description' => $description,
                'cout' => $data['prix_achat'] ?? 0,
                'statut_apres' => 'ACTIF',
                'transaction_id' => $transaction->id,
                'sync_status' => 'synced',
                'version' => 1,
            ], $animal);

            return $animal->fresh();
        });
    }

    /**
     * Vente d'un animal.
     *
     * @param Animal $animal Animal à vendre
     * @param array $data Données de la vente
     * @return void
     */
    public function vente(Animal $animal, array $data): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($animal, $data) {
            $typeVente = TypeEvenement::where('nom_type', 'Vente')
                ->whereNull('farm_id')
                ->firstOrFail();

            $categorieVente = \App\Models\Categorie::where('nom_categorie', 'Vente d\'animaux')->first();

            $transaction = \App\Models\Transaction::create([
                'farm_id' => $animal->farm_id,
                'animal_id' => $animal->id,
                'type_transaction' => 'ENTREE',
                'montant' => $data['prix_vente'] ?? 0,
                'date_transaction' => $data['date_vente'] ?? now(),
                'description' => $data['acheteur'] ?? null,
                'categorie_id' => $categorieVente?->id,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'sync_status' => 'synced',
                'version' => 1,
            ]);

            // Mettre à jour le statut de l'animal AVANT de créer l'événement
            $animal->update(['statut' => 'VENDU']);

            $this->evenementService->creerMouvement([
                'farm_id' => $animal->farm_id,
                'type_evenement_id' => $typeVente->id,
                'animal_id' => $animal->id,
                'date_evenement' => $data['date_vente'] ?? now(),
                'description' => $data['acheteur'] ?? null,
                'cout' => $data['prix_vente'] ?? 0,
                'statut_avant' => $animal->getOriginal('statut') ?? 'ACTIF',
                'statut_apres' => 'VENDU',
                'transaction_id' => $transaction->id,
                'sync_status' => 'synced',
                'version' => 1,
            ], $animal);
        });
    }

    /**
     * Transfert d'un animal vers une autre ferme.
     *
     * @param Animal $animal Animal à transférer
     * @param array $data Données du transfert
     * @return void
     */
    public function transfert(Animal $animal, array $data): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($animal, $data) {
            $typeTransfert = TypeEvenement::where('nom_type', 'Transfert')
                ->whereNull('farm_id')
                ->firstOrFail();

            // Mettre à jour le statut de l'animal AVANT de créer l'événement
            $animal->update(['statut' => 'TRANSFERE']);

            $this->evenementService->creerMouvement([
                'farm_id' => $animal->farm_id,
                'type_evenement_id' => $typeTransfert->id,
                'animal_id' => $animal->id,
                'date_evenement' => $data['date_transfert'] ?? now(),
                'description' => $data['motif'] ?? null,
                'statut_avant' => $animal->getOriginal('statut') ?? 'ACTIF',
                'statut_apres' => 'TRANSFERE',
                'farm_destination_id' => $data['farm_destination_id'],
                'sync_status' => 'synced',
                'version' => 1,
            ], $animal);
        });
    }

    /**
     * Déclaration de décès d'un animal.
     *
     * @param Animal $animal Animal décédé
     * @param array $data Données du décès
     * @return void
     */
    public function deces(Animal $animal, array $data): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($animal, $data) {
            $typeDeces = TypeEvenement::where('nom_type', 'Décès')
                ->whereNull('farm_id')
                ->firstOrFail();

            // Mettre à jour le statut de l'animal AVANT de créer l'événement
            $animal->update(['statut' => 'MORT']);

            $this->evenementService->creerMouvement([
                'farm_id' => $animal->farm_id,
                'type_evenement_id' => $typeDeces->id,
                'animal_id' => $animal->id,
                'date_evenement' => $data['date_deces'] ?? now(),
                'description' => trim(($data['cause'] ?? '') . ' ' . ($data['observation'] ?? '')),
                'statut_avant' => $animal->getOriginal('statut') ?? 'ACTIF',
                'statut_apres' => 'MORT',
                'sync_status' => 'synced',
                'version' => 1,
            ], $animal);
        });
    }

    /**
     * Déclaration de perte d'un animal.
     *
     * @param Animal $animal Animal perdu
     * @param array $data Données de la perte
     * @return void
     */
    public function perte(Animal $animal, array $data): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($animal, $data) {
            $typePerte = TypeEvenement::where('nom_type', 'Perte')
                ->whereNull('farm_id')
                ->firstOrFail();

            // Mettre à jour le statut de l'animal AVANT de créer l'événement
            $animal->update(['statut' => 'PERDU']);

            $this->evenementService->creerMouvement([
                'farm_id' => $animal->farm_id,
                'type_evenement_id' => $typePerte->id,
                'animal_id' => $animal->id,
                'date_evenement' => $data['date_perte'] ?? now(),
                'description' => trim(($data['motif'] ?? '') . ' ' . ($data['observation'] ?? '')),
                'statut_avant' => $animal->getOriginal('statut') ?? 'ACTIF',
                'statut_apres' => 'PERDU',
                'sync_status' => 'synced',
                'version' => 1,
            ], $animal);
        });
    }

    /**
     * Abattage d'un animal.
     *
     * @param Animal $animal Animal à abattre
     * @param array $data Données de l'abattage
     * @return void
     */
    public function abattage(Animal $animal, array $data): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($animal, $data) {
            $typeAbattage = TypeEvenement::where('nom_type', 'Abattage')
                ->whereNull('farm_id')
                ->firstOrFail();

            // Mettre à jour le statut de l'animal AVANT de créer l'événement
            $animal->update(['statut' => 'MORT']);

            $this->evenementService->creerMouvement([
                'farm_id' => $animal->farm_id,
                'type_evenement_id' => $typeAbattage->id,
                'animal_id' => $animal->id,
                'date_evenement' => $data['date_abattage'] ?? now(),
                'description' => trim(($data['motif'] ?? '') . ' poids: ' . ($data['poids_carcasse'] ?? '')),
                'cout' => $data['valeur_carcasse'] ?? null,
                'statut_avant' => $animal->getOriginal('statut') ?? 'ACTIF',
                'statut_apres' => 'MORT',
                'sync_status' => 'synced',
                'version' => 1,
            ], $animal);
        });
    }

    /**
     * Vente d'un lot d'animaux.
     *
     * @param \App\Models\Lot $lot Lot à vendre
     * @param array $data Données de la vente
     * @return array Résultat de la vente avec détails
     */
    public function venteLot(\App\Models\Lot $lot, array $data): array
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($lot, $data) {
            // Récupérer tous les animaux ACTIF du lot
            $animaux = $lot->animals()->where('statut', 'ACTIF')->get();

            if ($animaux->isEmpty()) {
                throw new \Exception('Aucun animal actif dans ce lot.');
            }

            $resultats = [
                'total_animaux' => $animaux->count(),
                'animaux_vendus' => 0,
                'animaux_echoues' => [],
                'montant_total' => $data['prix_total'] ?? 0,
            ];

            foreach ($animaux as $animal) {
                try {
                    // Vendre chaque animal individuellement
                    $this->vente($animal, $data);
                    $resultats['animaux_vendus']++;
                } catch (\Exception $e) {
                    $resultats['animaux_echoues'][] = [
                        'animal_id' => $animal->id,
                        'animal_nom' => $animal->nom,
                        'raison' => $e->getMessage(),
                    ];
                }
            }

            // Optionnel : Archiver le lot après vente complète
            if ($resultats['animaux_vendus'] === $resultats['total_animaux']) {
                $lot->delete();
            }

            return $resultats;
        });
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
