<?php

namespace App\Services;

use App\Models\Naissance;
use App\Models\Animal;
use App\Models\Evenement;
use App\Models\TypeEvenement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

class NaissanceService
{
    private ActivityLogService $activityLog;
    private NotificationService $notificationService;
    private EvenementService $evenementService;

    public function __construct(ActivityLogService $activityLog, NotificationService $notificationService, EvenementService $evenementService)
    {
        $this->activityLog = $activityLog;
        $this->notificationService = $notificationService;
        $this->evenementService = $evenementService;
    }
    /**
     * Lister les naissances avec pagination et filtres.
     */
    public function index(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Naissance::query()
            ->with(['mother', 'farm', 'evenement', 'petits'])
            ->when(isset($filters['mother_id']), fn ($q) =>
                $q->where('mother_id', $filters['mother_id'])
            )
            ->when(isset($filters['date_debut']) && isset($filters['date_fin']), fn ($q) =>
                $q->whereBetween('date_naissance', [$filters['date_debut'], $filters['date_fin']])
            )
            ->when(isset($filters['mises_bas_venir']), fn ($q) =>
                $q->misesBasAVenir($filters['mises_bas_venir'] ?? 7)
            )
            ->orderBy('date_naissance', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Obtenir toutes les naissances sans pagination (pour exports).
     */
    public function getAll(array $filters = []): \Illuminate\Support\Collection
    {
        $query = Naissance::query()
            ->with(['mother', 'farm', 'evenement', 'petits'])
            ->when(isset($filters['farm_id']), fn ($q) =>
                $q->where('farm_id', $filters['farm_id'])
            )
            ->when(isset($filters['mother_id']), fn ($q) =>
                $q->where('mother_id', $filters['mother_id'])
            )
            ->when(isset($filters['date_debut']) && isset($filters['date_fin']), fn ($q) =>
                $q->whereBetween('date_naissance', [$filters['date_debut'], $filters['date_fin']])
            )
            ->orderBy('date_naissance', 'desc');

        return $query->get();
    }

    /**
     * Créer une naissance avec gestion offline-first et logique métier.
     */
    public function store(array $data, string $userId): Naissance
    {
        return DB::transaction(function () use ($data, $userId) {
            // Vérification de conflit offline-first
            if (isset($data['version'])) {
                unset($data['version']);
            }

            $data['sync_status'] = 'synced';
            $data['last_modified_by'] = $userId;
            $data['version'] = 1;

            // Utiliser la ferme courante du contexte si non fournie
            if (!isset($data['farm_id'])) {
                $data['farm_id'] = session('current_farm_id');
            }

            // Calcul automatique de date_mise_bas_prevue si non fournie
            if (!isset($data['date_mise_bas_prevue']) && isset($data['date_saillie'])) {
                $mother = Animal::find($data['mother_id']);
                if ($mother && $mother->espece && $mother->espece->parametre) {
                    $dureeGestation = $mother->espece->parametre->duree_gestation_jours;
                    if ($dureeGestation) {
                        $data['date_mise_bas_prevue'] = \Carbon\Carbon::parse($data['date_saillie'])
                            ->addDays($dureeGestation);
                    }
                }
            }

            $creerPetits = $data['creer_petits'] ?? false;
            unset($data['creer_petits']);

            $naissance = Naissance::create($data);

            // Log activity after successful creation
            $this->activityLog->log('created', $naissance, null, $data);

            // Create notification if date_mise_bas_prevue is set
            if (isset($data['date_mise_bas_prevue']) && $data['date_mise_bas_prevue']) {
                $naissance->load('mother');
                $motherNom = $naissance->mother->nom ?? $naissance->mother->numero_identification;
                $joursRestants = now()->diffInDays($naissance->date_mise_bas_prevue, false);
                $this->notificationService->creerNotification(
                    $naissance->farm_id,
                    'mise_bas_prevue',
                    'Mise bas prévue',
                    "Mise bas prévue pour la mère {$motherNom} dans {$joursRestants} jours ({$naissance->date_mise_bas_prevue->format('d/m/Y')}).",
                    $naissance->mother_id,
                    null,
                    null
                );
            }

            // Créer automatiquement l'événement MISE_BAS si non fourni
            if (!isset($data['evenement_id'])) {
                $typeMiseBas = TypeEvenement::where('nom_type', 'MISE_BAS')->first();
                if ($typeMiseBas) {
                    $evenement = Evenement::create([
                        'farm_id' => $naissance->farm_id,
                        'type_evenement_id' => $typeMiseBas->id,
                        'animal_id' => $naissance->mother_id,
                        'date_evenement' => $naissance->date_naissance,
                        'description' => 'Mise bas enregistrée',
                        'sync_status' => 'synced',
                        'last_modified_by' => $userId,
                        'version' => 1,
                    ]);
                    $naissance->update(['evenement_id' => $evenement->id]);
                }
            }

            // Créer automatiquement les petits si demandé
            if ($creerPetits && $naissance->nombre_petits > 0) {
                $mother = Animal::find($naissance->mother_id);
                if ($mother) {
                    for ($i = 0; $i < $naissance->nombre_petits; $i++) {
                        Animal::create([
                            'farm_id' => $naissance->farm_id,
                            'espece_id' => $mother->espece_id,
                            'mother_id' => $naissance->mother_id,
                            'naissance_id' => $naissance->id,
                            'date_naissance' => $naissance->date_naissance,
                            'statut' => 'ACTIF',
                            'sync_status' => 'synced',
                            'last_modified_by' => $userId,
                            'version' => 1,
                        ]);
                    }
                }
            }

            return $naissance->fresh();
        });
    }

    /**
     * Mettre à jour une naissance avec gestion offline-first.
     */
    public function update(Naissance $naissance, array $data, string $userId): Naissance
    {
        return DB::transaction(function () use ($naissance, $data, $userId) {
            // Vérification de conflit offline-first
            if (isset($data['version'])) {
                if ($data['version'] !== $naissance->version) {
                    // Conflit détecté
                    $naissance->update([
                        'sync_status' => 'conflict',
                    ]);
                    throw new \Exception('Conflit de version détecté. Veuillez synchroniser vos données.');
                }
            }

            $data['sync_status'] = 'synced';
            $data['last_modified_by'] = $userId;
            $data['version'] = ($naissance->version ?? 1) + 1;

            // Recalculer date_mise_bas_prevue si date_saillie change
            if (isset($data['date_saillie']) && !isset($data['date_mise_bas_prevue'])) {
                $mother = Animal::find($naissance->mother_id);
                if ($mother && $mother->espece && $mother->espece->parametre) {
                    $dureeGestation = $mother->espece->parametre->duree_gestation_jours;
                    if ($dureeGestation) {
                        $data['date_mise_bas_prevue'] = \Carbon\Carbon::parse($data['date_saillie'])
                            ->addDays($dureeGestation);
                    }
                }
            }

            $oldValues = $naissance->toArray();
            
            $naissance->update($data);

            // Log activity after successful update
            $this->activityLog->log('updated', $naissance, $oldValues, $data);

            return $naissance->fresh();
        });
    }

    /**
     * Supprimer (soft delete) une naissance.
     */
    public function destroy(Naissance $naissance): bool
    {
        $naissance->update([
            'sync_status' => 'synced',
            'version' => ($naissance->version ?? 1) + 1,
        ]);

        $oldValues = $naissance->toArray();
        
        $result = $naissance->delete();

        // Log activity after successful deletion
        if ($result) {
            $this->activityLog->log('deleted', $naissance, $oldValues, null);
        }

        return $result;
    }

    /**
     * Restaurer une naissance archivée.
     */
    public function restore(string $id): Naissance
    {
        $naissance = Naissance::onlyTrashed()->findOrFail($id);
        $naissance->restore();

        // Log activity after successful restoration
        $this->activityLog->log('restored', $naissance, null, $naissance->toArray());

        return $naissance->fresh();
    }

    /**
     * Lister les naissances archivées.
     */
    public function trashed(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Naissance::onlyTrashed()
            ->with(['mother', 'farm'])
            ->when(isset($filters['mother_id']), fn ($q) =>
                $q->where('mother_id', $filters['mother_id'])
            )
            ->orderBy('deleted_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Obtenir les prévisions de mises bas à venir.
     */
    public function previsions(int $jours = 30): array
    {
        $naissances = Naissance::query()
            ->with(['mother', 'farm'])
            ->whereNotNull('date_mise_bas_prevue')
            ->whereBetween('date_mise_bas_prevue', [
                now(),
                now()->addDays($jours)
            ])
            ->orderBy('date_mise_bas_prevue')
            ->get();

        return $naissances->map(fn ($naissance) => [
            'id' => $naissance->id,
            'date_mise_bas_prevue' => $naissance->date_mise_bas_prevue,
            'jours_restants' => now()->diffInDays($naissance->date_mise_bas_prevue, false),
            'mother' => [
                'id' => $naissance->mother->id,
                'nom' => $naissance->mother->nom,
                'espece' => $naissance->mother->espece->nom ?? null,
            ],
            'farm' => [
                'id' => $naissance->farm->id,
                'name' => $naissance->farm->name,
            ],
        ])->toArray();
    }

    /**
     * Déclarer une naissance avec création des petits et événements.
     * Remplace l'observer booted() de Naissance.
     *
     * @param array $data Données de la naissance
     * @param string $farmId ID de la ferme
     * @return Naissance
     */
    public function declarer(array $data, string $farmId): Naissance
    {
        return DB::transaction(function () use ($data, $farmId) {
            // Calculer explicitement date_mise_bas_prevue si non fournie
            if (!isset($data['date_mise_bas_prevue']) && isset($data['date_saillie'])) {
                $mother = Animal::find($data['mother_id'] ?? null);
                if ($mother && $mother->espece && $mother->espece->parametres) {
                    $dureeGestation = $mother->espece->parametres->duree_gestation_jours;
                    if ($dureeGestation) {
                        $data['date_mise_bas_prevue'] = \Carbon\Carbon::parse($data['date_saillie'])
                            ->addDays($dureeGestation);
                    }
                }
            }

            $data['farm_id'] = $farmId;
            $data['sync_status'] = 'synced';
            $data['version'] = 1;

            $naissance = Naissance::create($data);

            // Créer les fiches Animal pour chaque petit
            $nombrePetits = $data['nombre_petits'] ?? 0;
            $petitsData = $data['petits'] ?? [];

            for ($i = 0; $i < $nombrePetits; $i++) {
                $petitData = $petitsData[$i] ?? [];
                $animal = Animal::create(array_merge($petitData, [
                    'farm_id' => $farmId,
                    'mother_id' => $data['mother_id'],
                    'naissance_id' => $naissance->id,
                    'date_naissance' => $data['date_naissance'],
                    'statut' => 'ACTIF',
                    'origine' => 'naissance',
                    'sync_status' => 'synced',
                    'version' => 1,
                ]));

                // Créer l'événement de naissance pour chaque petit
                $typeNaissance = TypeEvenement::where('nom_type', 'NAISSANCE')
                    ->whereNull('farm_id')
                    ->first();

                if ($typeNaissance) {
                    $this->evenementService->creerMouvement([
                        'farm_id' => $farmId,
                        'type_evenement_id' => $typeNaissance->id,
                        'animal_id' => $animal->id,
                        'date_evenement' => $data['date_naissance'],
                        'description' => 'Naissance de l\'animal',
                        'statut_apres' => 'ACTIF',
                        'sync_status' => 'synced',
                        'version' => 1,
                    ], $animal);
                }
            }

            // Créer l'événement MISE BAS pour la mère
            $typeMiseBas = TypeEvenement::where('nom_type', 'MISE BAS')->first();
            if ($typeMiseBas) {
                Evenement::create([
                    'farm_id' => $farmId,
                    'type_evenement_id' => $typeMiseBas->id,
                    'categorie' => 'REPRODUCTION',
                    'animal_id' => $data['mother_id'],
                    'date_evenement' => $data['date_naissance'],
                    'description' => 'Mise bas - ' . $nombrePetits . ' petit(s)',
                    'metadonnees' => [
                        'nombre_petits' => $nombrePetits,
                        'naissance_id' => $naissance->id,
                    ],
                    'sync_status' => 'synced',
                    'version' => 1,
                ]);
            }

            $this->activityLog->log('created', $naissance, null, $data);

            return $naissance->fresh();
        });
    }

    /**
     * Formater une naissance pour la réponse API.
     */
    public function formatNaissance(Naissance $naissance): array
    {
        return [
            'id' => $naissance->id,
            'farm_id' => $naissance->farm_id,
            'mother_id' => $naissance->mother_id,
            'date_naissance' => $naissance->date_naissance,
            'nombre_petits' => $naissance->nombre_petits,
            'poids_naissance' => $naissance->poids_naissance,
            'observation' => $naissance->observation,
            'date_saillie' => $naissance->date_saillie,
            'date_mise_bas_prevue' => $naissance->date_mise_bas_prevue,
            'evenement_id' => $naissance->evenement_id,
            'sync_status' => $naissance->sync_status,
            'version' => $naissance->version,
            'deleted_at' => $naissance->deleted_at,
            'created_at' => $naissance->created_at,
            'updated_at' => $naissance->updated_at,
            // Relations
            'mother' => $naissance->mother ? [
                'id' => $naissance->mother->id,
                'nom' => $naissance->mother->nom,
                'sexe' => $naissance->mother->sexe,
                'statut' => $naissance->mother->statut,
            ] : null,
            'farm' => $naissance->farm ? [
                'id' => $naissance->farm->id,
                'name' => $naissance->farm->name,
            ] : null,
            'evenement' => $naissance->evenement ? [
                'id' => $naissance->evenement->id,
                'date_evenement' => $naissance->evenement->date_evenement,
                'description' => $naissance->evenement->description,
            ] : null,
            'petits' => $naissance->petits->map(fn ($petit) => [
                'id' => $petit->id,
                'nom' => $petit->nom,
                'sexe' => $petit->sexe,
                'statut' => $petit->statut,
            ]),
            'nombre_enregistres' => $naissance->nombre_enregistres,
            'nombre_non_enregistres' => $naissance->nombre_non_enregistres,
        ];
    }
}
