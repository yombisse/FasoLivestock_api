<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Evenement;
use App\Models\Transaction;
use App\Models\TypeEvenement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AnimalService
{
    private ActivityLogService $activityLog;
    private EvenementService $evenementService;
    private NaissanceService $naissanceService;
    private EvenementMouvementService $evenementMouvementService;

    public function __construct(ActivityLogService $activityLog, EvenementService $evenementService, NaissanceService $naissanceService, EvenementMouvementService $evenementMouvementService)
    {
        $this->activityLog = $activityLog;
        $this->evenementService = $evenementService;
        $this->naissanceService = $naissanceService;
        $this->evenementMouvementService = $evenementMouvementService;
    }

    // =========================================================
    // LISTING
    // =========================================================

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

    public function getAll(array $filters = []): \Illuminate\Support\Collection
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

        return $query->get();
    }

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

    // =========================================================
    // CRUD ANIMAL
    // =========================================================

    public function store(array $data): Animal
    {
        $data['sync_status'] = 'synced';
        $data['version'] = 1;
        $data['origine'] = $data['origine'] ?? 'import';

        unset($data['statut']);

        $animal = Animal::create($data);

        $this->activityLog->log('created', $animal, null, $data);

        return $animal;
    }

    public function update(Animal $animal, array $data): Animal
    {
        $oldValues = $animal->toArray();

        $data['sync_status'] = 'synced';
        $data['version'] = ($animal->version ?? 1) + 1;

        unset($data['statut']);

        $animal->update($data);

        $this->activityLog->log('updated', $animal, $oldValues, $data);

        return $animal->fresh();
    }

    public function destroy(Animal $animal): bool
    {
        $oldValues = $animal->toArray();

        $animal->update([
            'sync_status' => 'synced',
            'version'     => ($animal->version ?? 1) + 1,
        ]);

        $result = $animal->delete();

        if ($result) {
            $this->activityLog->log('deleted', $animal, $oldValues, null);
        }

        return $result;
    }

    public function restore(string $id): Animal
    {
        $animal = Animal::onlyTrashed()->find($id);

        if (!$animal) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Animal not found in trash');
        }

        $animal->restore();

        $this->activityLog->log('restored', $animal, null, $animal->toArray());

        return $animal->fresh();
    }

    // =========================================================
    // DÉLÉGATION MOUVEMENTS (pour compatibilité AnimalController)
    // =========================================================

    public function birth(array $data): Animal
    {
        // Préparer les données pour NaissanceService::declarer
        $naissanceData = [
            'mother_id' => $data['mother_id'] ?? null,
            'date_naissance' => $data['date_naissance'] ?? now(),
            'nombre_petits' => 1,
            'petits' => [
                [
                    'nom' => $data['nom'],
                    'race' => $data['race'] ?? null,
                    'sexe' => $data['sexe'],
                    'espece_id' => $data['espece_id'] ?? null,
                    'lot_id' => $data['lot_id'] ?? null,
                    'poids' => $data['poids'] ?? null,
                    'numero_identification' => $data['numero_identification'] ?? null,
                    'photo' => $data['photo'] ?? null,
                ]
            ],
        ];

        $farmId = $data['farm_id'];
        $naissance = $this->naissanceService->declarer($naissanceData, $farmId);

        // Retourner le premier petit créé
        return $naissance->petits->first();
    }

    public function purchase(array $data): Animal
    {
        return $this->evenementMouvementService->achat($data, $data['farm_id']);
    }

    public function sell(Animal $animal, array $data): Animal
    {
        $this->evenementMouvementService->vente($animal, $data);
        // Recharger l'animal depuis la base pour obtenir les modifications
        return Animal::find($animal->id);
    }

    public function transfer(Animal $animal, array $data): Animal
    {
        $this->evenementMouvementService->transfert($animal, $data);
        return $animal->fresh();
    }

    public function declareDeath(Animal $animal, array $data): Animal
    {
        $this->evenementMouvementService->deces($animal, $data);
        return $animal->fresh();
    }

    public function declareLoss(Animal $animal, array $data): Animal
    {
        $this->evenementMouvementService->perte($animal, $data);
        return $animal->fresh();
    }

    public function slaughter(Animal $animal, array $data): Animal
    {
        $this->evenementMouvementService->abattage($animal, $data);
        return $animal->fresh();
    }

    public function sellLot(\App\Models\Lot $lot, array $data): array
    {
        return $this->evenementMouvementService->venteLot($lot, $data);
    }

    // =========================================================
    // FORMAT API
    // =========================================================

    public function formatAnimal(Animal $animal): array
    {
        return [
            'id' => $animal->id,
            'farm_id' => $animal->farm_id,
            'nom' => $animal->nom,
            'race' => $animal->race,
            'sexe' => $animal->sexe,
            'date_naissance' => $animal->date_naissance,
            'poids' => $animal->poids,
            'espece_id' => $animal->espece_id,
            'lot_id' => $animal->lot_id,
            'mother_id' => $animal->mother_id,
            'statut' => $animal->statut,
            'origine' => $animal->origine,
            'numero_identification' => $animal->numero_identification,
            'photo' => $animal->photo,
            'naissance_id' => $animal->naissance_id,
            'sync_status' => $animal->sync_status,
            'version' => $animal->version,
            'deleted_at' => $animal->deleted_at,
            'created_at' => $animal->created_at,
            'updated_at' => $animal->updated_at,
            'farm' => $animal->farm ? ['id' => $animal->farm->id, 'name' => $animal->farm->name] : null,
            'espece' => $animal->espece ? ['id' => $animal->espece->id, 'nom' => $animal->espece->nom] : null,
            'lot' => $animal->lot ? ['id' => $animal->lot->id, 'nom' => $animal->lot->nom] : null,
            'mother' => $animal->mother ? ['id' => $animal->mother->id, 'nom' => $animal->mother->nom] : null,
        ];
    }

    // =========================================================
    // ANIMAUX ÉLIGIBLES AUX ÉVÉNEMENTS
    // =========================================================

    /**
     * Animaux éligibles aux événements sanitaires (Vaccination, Traitement, Contrôle)
     */
    public function getEligiblesForSanitaire(string $farmId, ?string $typeEvenement = null): \Illuminate\Support\Collection
    {
        $query = Animal::query()
            ->with(['espece', 'lot'])
            ->where('farm_id', $farmId)
            ->whereNull('deleted_at');

        // Filtrer selon le type d'événement sanitaire
        switch (strtoupper($typeEvenement)) {
            case 'VACCINATION':
                // Vaccination: tous les animaux vivants dans la ferme
                // Pas de filtre de statut - tous peuvent être vaccinés
                break;
            case 'TRAITEMENT':
                // Traitement: animaux MALADE ou EN_TRAITEMENT
                $query->whereIn('statut', ['MALADE', 'EN_TRAITEMENT']);
                break;
            case 'MALADIE':
                // Déclaration maladie: tous les animaux (peuvent être SAIN et devenir MALADE)
                $query->whereIn('statut', ['SAIN', 'MALADE', 'EN_TRAITEMENT']);
                break;
            case 'CONSULTATION':
            case 'CONTROLE':
                // Consultation/Contrôle: tous les animaux vivants dans la ferme
                // Pas de filtre de statut - tous peuvent être contrôlés
                break;
            default:
                // Par défaut: tous les animaux actifs
                $query->whereIn('statut', ['SAIN', 'MALADE', 'EN_TRAITEMENT']);
        }

        return $query->get()->map(fn ($animal) => $this->formatAnimal($animal));
    }

    /**
     * Animaux éligibles aux événements de mouvement
     */
    public function getEligiblesForMouvement(string $farmId, string $typeMouvement): \Illuminate\Support\Collection
    {
        $query = Animal::query()
            ->with(['espece', 'lot'])
            ->where('farm_id', $farmId)
            ->whereNull('deleted_at');

        // Filtrer selon le type de mouvement
        switch (strtoupper($typeMouvement)) {
            case 'VENTE':
            case 'TRANSFERT':
                $query->whereIn('statut', ['SAIN', 'MALADE', 'EN_TRAITEMENT']);
                break;
            case 'DECES':
            case 'PERTE':
            case 'ABATTAGE':
                $query->whereIn('statut', ['SAIN', 'MALADE', 'EN_TRAITEMENT']);
                break;
            default:
                $query->whereIn('statut', ['SAIN', 'MALADE', 'EN_TRAITEMENT']);
        }

        return $query->get()->map(fn ($animal) => $this->formatAnimal($animal));
    }

    /**
     * Animaux éligibles aux événements de reproduction
     */
    public function getEligiblesForReproduction(string $farmId, string $typeReproduction): \Illuminate\Support\Collection
    {
        $query = Animal::query()
            ->with(['espece', 'lot', 'espece.parametre'])
            ->where('farm_id', $farmId)
            ->whereNull('deleted_at');

        switch (strtoupper($typeReproduction)) {
            case 'SAILLIE':
                // Femelles SAIN sans saillie en cours
                $query->where('sexe', 'femelle')
                    ->where('statut', 'SAIN')
                    ->whereDoesntHave('evenements', fn ($q) =>
                        $q->whereHas('type', fn ($t) => $t->where('nom_type', 'Saillie'))
                            ->whereDoesntHave('animal.evenements', fn ($e) =>
                                $e->whereHas('type', fn ($t) => $t->where('nom_type', 'Mise bas'))
                                    ->where('date_evenement', '>', $q->select('date_evenement'))
                            )
                    );
                break;

            case 'GESTATION':
                // Femelles avec une saillie récente non suivie de mise bas
                $query->where('sexe', 'femelle')
                    ->where('statut', 'SAIN')
                    ->whereHas('evenements', fn ($q) =>
                        $q->whereHas('type', fn ($t) => $t->where('nom_type', 'Saillie'))
                            ->whereDoesntHave('animal.evenements', fn ($e) =>
                                $e->whereHas('type', fn ($t) => $t->where('nom_type', 'Mise bas'))
                                    ->where('date_evenement', '>', $q->select('date_evenement'))
                            )
                    );
                break;

            case 'MISE BAS':
                // Femelles en gestation (avec événement gestation confirmé)
                $query->where('sexe', 'femelle')
                    ->where('statut', 'SAIN')
                    ->whereHas('evenements', fn ($q) =>
                        $q->whereHas('type', fn ($t) => $t->where('nom_type', 'Gestation'))
                    );
                break;

            default:
                // Par défaut: toutes les femelles SAIN
                $query->where('sexe', 'femelle')->where('statut', 'SAIN');
        }

        return $query->get()->map(fn ($animal) => $this->formatAnimal($animal));
    }

    /**
     * Mâles éligibles pour saillie
     * Retourne tous les mâles SAIN (âge non vérifié, considérés comme adultes si date_naissance null)
     */
    public function getEligibleMales(string $farmId): \Illuminate\Support\Collection
    {
        $query = Animal::query()
            ->with(['espece', 'lot', 'espece.parametre'])
            ->where('farm_id', $farmId)
            ->where('sexe', 'male')
            ->where('statut', 'SAIN')
            ->whereNull('deleted_at');

        return $query->get()->map(fn ($animal) => $this->formatAnimal($animal));
    }

    // =========================================================
    // IMPORT BATCH
    // =========================================================

    public function importBatch(array $animaux, string $farmId): array
    {
        \Log::info('importBatch started', [
            'farm_id' => $farmId,
            'animaux_count' => count($animaux),
            'animaux_data' => $animaux,
        ]);

        return DB::transaction(function () use ($animaux, $farmId) {
            $resultats = ['succes' => 0, 'erreurs' => []];
            $typeImport = TypeEvenement::where('nom_type', 'STOCK_INITIAL')
                ->whereNull('farm_id')
                ->first();

            \Log::info('TypeImport found', ['type_import' => $typeImport ? $typeImport->id : null]);

            foreach ($animaux as $index => $data) {
                try {
                    \Log::info('Processing animal', [
                        'index' => $index,
                        'data' => $data,
                    ]);

                    $animal = Animal::create(array_merge($data, [
                        'farm_id' => $farmId,
                        'origine' => Animal::ORIGINE_IMPORT,
                        'statut'  => 'SAIN',
                        'sync_status' => 'synced',
                        'version' => 1,
                    ]));

                    \Log::info('Animal created', [
                        'animal_id' => $animal->id ?? null,
                        'success' => (bool)$animal,
                    ]);

                    if ($animal) {
                        // Créer un événement traçable d'entrée
                        if ($typeImport) {
                            $this->evenementService->creerMouvement([
                                'farm_id' => $farmId,
                                'type_evenement_id' => $typeImport->id,
                                'animal_id' => $animal->id,
                                'date_evenement' => now(),
                                'description' => 'Import initial du cheptel',
                                'statut_apres' => 'SAIN',
                                'sync_status' => 'synced',
                                'version' => 1,
                            ], $animal);
                        }

                        $resultats['succes']++;
                    } else {
                        $resultats['erreurs'][] = [
                            'ligne'  => $index + 1,
                            'data'   => $data,
                            'raison' => 'Échec de création (retour null)',
                        ];
                    }
                } catch (\Exception $e) {
                    $resultats['erreurs'][] = [
                        'ligne'  => $index + 1,
                        'data'   => $data,
                        'raison' => $e->getMessage(),
                    ];
                }
            }

            return $resultats;
        });
    }
}