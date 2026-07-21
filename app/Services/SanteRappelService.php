<?php

namespace App\Services;

use App\Models\SanteRappel;
use App\Models\Animal;
use App\Models\Evenement;
use App\Models\TypeEvenement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

class SanteRappelService
{
    private ActivityLogService $activityLog;
    private NotificationService $notificationService;

    public function __construct(ActivityLogService $activityLog, NotificationService $notificationService)
    {
        $this->activityLog = $activityLog;
        $this->notificationService = $notificationService;
    }
    /**
     * Lister les rappels sanitaires avec pagination et filtres.
     */
    public function index(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = SanteRappel::query()
            ->with(['animal', 'farm', 'evenement'])
            ->when(isset($filters['animal_id']), fn ($q) =>
                $q->where('animal_id', $filters['animal_id'])
            )
            ->when(isset($filters['type_rappel']), fn ($q) =>
                $q->where('type_rappel', $filters['type_rappel'])
            )
            ->when(isset($filters['statut']), fn ($q) =>
                $q->where('statut', $filters['statut'])
            )
            ->when(isset($filters['date_debut']) && isset($filters['date_fin']), fn ($q) =>
                $q->whereBetween('date_prevue', [$filters['date_debut'], $filters['date_fin']])
            )
            ->when(isset($filters['en_retard']), fn ($q) =>
                $q->where('statut', 'EN_RETARD')
            )
            ->orderBy('date_prevue', 'asc');

        return $query->paginate($perPage);
    }

    /**
     * Obtenir tous les rappels sanitaires sans pagination (pour exports).
     */
    public function getAll(array $filters = []): \Illuminate\Support\Collection
    {
        $query = SanteRappel::query()
            ->with(['animal', 'farm', 'evenement'])
            ->when(isset($filters['farm_id']), fn ($q) =>
                $q->where('farm_id', $filters['farm_id'])
            )
            ->when(isset($filters['animal_id']), fn ($q) =>
                $q->where('animal_id', $filters['animal_id'])
            )
            ->when(isset($filters['type_rappel']), fn ($q) =>
                $q->where('type_rappel', $filters['type_rappel'])
            )
            ->when(isset($filters['statut']), fn ($q) =>
                $q->where('statut', $filters['statut'])
            )
            ->when(isset($filters['date_debut']) && isset($filters['date_fin']), fn ($q) =>
                $q->whereBetween('date_prevue', [$filters['date_debut'], $filters['date_fin']])
            )
            ->when(isset($filters['en_retard']), fn ($q) =>
                $q->where('statut', 'EN_RETARD')
            )
            ->orderBy('date_prevue', 'asc');

        return $query->get();
    }

    /**
     * Créer un rappel sanitaire avec gestion offline-first.
     */
    public function store(array $data, string $userId): SanteRappel
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

            // Définir le statut par défaut si non fourni
            if (!isset($data['statut'])) {
                $data['statut'] = 'EN_ATTENTE';
            }

            // Vérifier si le rappel est déjà en retard
            if ($data['statut'] === 'EN_ATTENTE' && isset($data['date_prevue'])) {
                if (\Carbon\Carbon::parse($data['date_prevue'])->isPast()) {
                    $data['statut'] = 'EN_RETARD';
                }
            }

            $rappel = SanteRappel::create($data);

            // Log activity after successful creation
            $this->activityLog->log('created', $rappel, null, $data);

            return $rappel->fresh();
        });
    }

    /**
     * Mettre à jour un rappel sanitaire avec gestion offline-first.
     */
    public function update(SanteRappel $rappel, array $data, string $userId): SanteRappel
    {
        return DB::transaction(function () use ($rappel, $data, $userId) {
            // Vérification de conflit offline-first
            if (isset($data['version'])) {
                if ($data['version'] !== $rappel->version) {
                    // Conflit détecté
                    $rappel->update([
                        'sync_status' => 'conflict',
                    ]);
                    throw new \Exception('Conflit de version détecté. Veuillez synchroniser vos données.');
                }
            }

            $data['sync_status'] = 'synced';
            $data['last_modified_by'] = $userId;
            $data['version'] = ($rappel->version ?? 1) + 1;

            // Mettre à jour le statut automatiquement si date_realisee est fournie
            if (isset($data['date_realisee']) && !isset($data['statut'])) {
                $data['statut'] = 'REALISE';
            }

            // Vérifier si le rappel est en retard si statut est EN_ATTENTE et date_prevue change
            if (isset($data['statut']) && $data['statut'] === 'EN_ATTENTE' && isset($data['date_prevue'])) {
                if (\Carbon\Carbon::parse($data['date_prevue'])->isPast()) {
                    $data['statut'] = 'EN_RETARD';
                }
            }

            $oldValues = $rappel->toArray();
            
            $rappel->update($data);

            // Log activity after successful update
            $this->activityLog->log('updated', $rappel, $oldValues, $data);

            // Create notification if status changed to EN_RETARD
            if (isset($data['statut']) && $data['statut'] === 'EN_RETARD' && $oldValues['statut'] !== 'EN_RETARD') {
                $rappel->load('animal');
                $animalNom = $rappel->animal->nom ?? $rappel->animal->numero_identification;
                $this->notificationService->creerNotification(
                    $rappel->farm_id,
                    'sante_retard',
                    'Rappel sanitaire en retard',
                    "Le rappel de {$rappel->type_rappel} pour l'animal {$animalNom} est passé en retard (date prévue: {$rappel->date_prevue->format('d/m/Y')}).",
                    $rappel->animal_id,
                    null,
                    $rappel->id
                );
            }

            return $rappel->fresh();
        });
    }

    /**
     * Supprimer (soft delete) un rappel sanitaire.
     */
    public function destroy(SanteRappel $rappel): bool
    {
        $rappel->update([
            'sync_status' => 'synced',
            'version' => ($rappel->version ?? 1) + 1,
        ]);

        $oldValues = $rappel->toArray();
        
        $result = $rappel->delete();

        // Log activity after successful deletion
        if ($result) {
            $this->activityLog->log('deleted', $rappel, $oldValues, null);
        }

        return $result;
    }

    /**
     * Restaurer un rappel sanitaire archivé.
     */
    public function restore(string $id): SanteRappel
    {
        $rappel = SanteRappel::onlyTrashed()->findOrFail($id);
        $rappel->restore();

        // Log activity after successful restoration
        $this->activityLog->log('restored', $rappel, null, $rappel->toArray());

        return $rappel->fresh();
    }

    /**
     * Lister les rappels sanitaires archivés.
     */
    public function trashed(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = SanteRappel::onlyTrashed()
            ->with(['animal', 'farm'])
            ->when(isset($filters['animal_id']), fn ($q) =>
                $q->where('animal_id', $filters['animal_id'])
            )
            ->when(isset($filters['type_rappel']), fn ($q) =>
                $q->where('type_rappel', $filters['type_rappel'])
            )
            ->orderBy('deleted_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Obtenir les rappels à venir (X prochains jours).
     */
    public function aVenir(int $jours = 7): array
    {
        $rappels = SanteRappel::query()
            ->with(['animal', 'farm'])
            ->where('statut', 'EN_ATTENTE')
            ->whereBetween('date_prevue', [
                now(),
                now()->addDays($jours)
            ])
            ->orderBy('date_prevue')
            ->get();

        return $rappels->map(fn ($rappel) => [
            'id' => $rappel->id,
            'type_rappel' => $rappel->type_rappel,
            'date_prevue' => $rappel->date_prevue,
            'jours_restants' => now()->diffInDays($rappel->date_prevue, false),
            'urgence' => $this->determinerUrgence($rappel->date_prevue),
            'animal' => [
                'id' => $rappel->animal->id,
                'nom' => $rappel->animal->nom,
                'espece' => $rappel->animal->espece->nom ?? null,
            ],
            'farm' => [
                'id' => $rappel->farm->id,
                'name' => $rappel->farm->name,
            ],
        ])->toArray();
    }

    /**
     * Obtenir les rappels en retard.
     */
    public function enRetard(): array
    {
        $rappels = SanteRappel::query()
            ->with(['animal', 'farm'])
            ->where('statut', 'EN_RETARD')
            ->orderBy('date_prevue', 'asc')
            ->get();

        return $rappels->map(fn ($rappel) => [
            'id' => $rappel->id,
            'type_rappel' => $rappel->type_rappel,
            'date_prevue' => $rappel->date_prevue,
            'jours_retard' => now()->diffInDays($rappel->date_prevue, false),
            'animal' => [
                'id' => $rappel->animal->id,
                'nom' => $rappel->animal->nom,
                'espece' => $rappel->animal->espece->nom ?? null,
            ],
            'farm' => [
                'id' => $rappel->farm->id,
                'name' => $rappel->farm->name,
            ],
        ])->toArray();
    }

    /**
     * Marquer un rappel comme réalisé via un événement.
     */
    public function marquerRealise(SanteRappel $rappel, array $evenementData, string $userId): SanteRappel
    {
        return DB::transaction(function () use ($rappel, $evenementData, $userId) {
            // Créer l'événement médical
            $evenement = Evenement::create([
                'farm_id' => $rappel->farm_id,
                'animal_id' => $rappel->animal_id,
                'type_evenement_id' => $evenementData['type_evenement_id'],
                'date_evenement' => $evenementData['date_evenement'] ?? now(),
                'description' => $evenementData['description'] ?? $rappel->type_rappel,
                'cout' => $evenementData['cout'] ?? null,
                'sync_status' => 'synced',
                'last_modified_by' => $userId,
                'version' => 1,
            ]);

            // Mettre à jour le rappel
            $rappel->update([
                'statut' => 'REALISE',
                'date_realisee' => $evenement->date_evenement,
                'evenement_id' => $evenement->id,
                'sync_status' => 'synced',
                'last_modified_by' => $userId,
                'version' => ($rappel->version ?? 1) + 1,
            ]);

            return $rappel->fresh();
        });
    }

    /**
     * Déterminer le niveau d'urgence pour un rappel.
     */
    private function determinerUrgence($datePrevue): string
    {
        $joursRestants = now()->diffInDays($datePrevue, false);

        if ($joursRestants <= 0) {
            return 'retard';
        } elseif ($joursRestants <= 1) {
            return 'critique';
        } elseif ($joursRestants <= 3) {
            return 'haute';
        } elseif ($joursRestants <= 7) {
            return 'moyenne';
        } else {
            return 'normale';
        }
    }

    /**
     * Formater un rappel sanitaire pour la réponse API.
     */
    public function formatRappel(SanteRappel $rappel): array
    {
        return [
            'id' => $rappel->id,
            'farm_id' => $rappel->farm_id,
            'animal_id' => $rappel->animal_id,
            'type_rappel' => $rappel->type_rappel,
            'date_prevue' => $rappel->date_prevue,
            'date_realisee' => $rappel->date_realisee,
            'statut' => $rappel->statut,
            'note' => $rappel->note,
            'evenement_id' => $rappel->evenement_id,
            'sync_status' => $rappel->sync_status,
            'version' => $rappel->version,
            'deleted_at' => $rappel->deleted_at,
            'created_at' => $rappel->created_at,
            'updated_at' => $rappel->updated_at,
            // Relations
            'animal' => $rappel->animal ? [
                'id' => $rappel->animal->id,
                'nom' => $rappel->animal->nom,
                'sexe' => $rappel->animal->sexe,
                'statut' => $rappel->animal->statut,
            ] : null,
            'farm' => $rappel->farm ? [
                'id' => $rappel->farm->id,
                'name' => $rappel->farm->name,
            ] : null,
            'evenement' => $rappel->evenement ? [
                'id' => $rappel->evenement->id,
                'date_evenement' => $rappel->evenement->date_evenement,
                'description' => $rappel->evenement->description,
            ] : null,
        ];
    }

    /**
     * Générer automatiquement des rappels basés sur intervalle_vaccin_jours.
     */
    public function genererRappelsAutomatiques(string $farmId, string $userId): array
    {
        return DB::transaction(function () use ($farmId, $userId) {
            $rappelsGeneres = [];

            // Récupérer tous les animaux de la ferme
            $animaux = Animal::where('farm_id', $farmId)
                ->where('statut', 'SAIN')
                ->with(['espece.parametre'])
                ->get();

            foreach ($animaux as $animal) {
                if (!$animal->espece || !$animal->espece->parametre) {
                    continue;
                }

                $parametre = $animal->espece->parametre;

                // Générer un rappel de vaccination si intervalle_vaccin_jours est défini
                if ($parametre->intervalle_vaccin_jours) {
                    // Chercher le dernier rappel de vaccination réalisé pour cet animal
                    $dernierRappelVaccination = SanteRappel::where('animal_id', $animal->id)
                        ->where('type_rappel', 'VACCINATION')
                        ->where('statut', 'REALISE')
                        ->orderBy('date_realisee', 'desc')
                        ->first();

                    if ($dernierRappelVaccination) {
                        // Calculer la prochaine date de vaccination
                        $prochaineDate = \Carbon\Carbon::parse($dernierRappelVaccination->date_realisee)
                            ->addDays($parametre->intervalle_vaccin_jours);

                        // Vérifier si un rappel existe déjà pour cette date
                        $rappelExistant = SanteRappel::where('animal_id', $animal->id)
                            ->where('type_rappel', 'VACCINATION')
                            ->where('date_prevue', $prochaineDate)
                            ->first();

                        if (!$rappelExistant && $prochaineDate->isFuture()) {
                            $rappel = SanteRappel::create([
                                'farm_id' => $farmId,
                                'animal_id' => $animal->id,
                                'type_rappel' => 'VACCINATION',
                                'date_prevue' => $prochaineDate,
                                'statut' => 'EN_ATTENTE',
                                'note' => 'Rappel généré automatiquement basé sur la dernière vaccination',
                                'sync_status' => 'synced',
                                'last_modified_by' => $userId,
                                'version' => 1,
                            ]);

                            $rappelsGeneres[] = $rappel;
                        }
                    }
                }
            }

            return $rappelsGeneres;
        });
    }

    /**
     * Reprogrammer un rappel sanitaire.
     */
    public function reprogrammer(SanteRappel $rappel, string $nouvelleDate, string $userId): SanteRappel
    {
        return DB::transaction(function () use ($rappel, $nouvelleDate, $userId) {
            $rappel->update([
                'date_prevue' => \Carbon\Carbon::parse($nouvelleDate),
                'statut' => 'EN_ATTENTE',
                'sync_status' => 'synced',
                'last_modified_by' => $userId,
                'version' => ($rappel->version ?? 1) + 1,
            ]);

            // Vérifier si le rappel est en retard avec la nouvelle date
            if (\Carbon\Carbon::parse($nouvelleDate)->isPast()) {
                $rappel->update([
                    'statut' => 'EN_RETARD',
                    'version' => $rappel->version + 1,
                ]);
            }

            return $rappel->fresh();
        });
    }

    /**
     * Obtenir les statistiques globales sanitaires pour une ferme.
     */
    public function statistiquesGlobales(string $farmId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = SanteRappel::where('farm_id', $farmId);

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_prevue', [$dateDebut, $dateFin]);
        }

        $rappels = $query->get();

        $statistiquesParType = $rappels->groupBy('type_rappel')
            ->map(fn ($group) => [
                'type' => $group->first()->type_rappel,
                'total' => $group->count(),
                'realises' => $group->where('statut', 'REALISE')->count(),
                'en_attente' => $group->where('statut', 'EN_ATTENTE')->count(),
                'en_retard' => $group->where('statut', 'EN_RETARD')->count(),
            ])
            ->values();

        return [
            'farm_id' => $farmId,
            'statistiques_par_type' => $statistiquesParType,
            'total_rappels' => $rappels->count(),
            'total_realises' => $rappels->where('statut', 'REALISE')->count(),
            'total_en_attente' => $rappels->where('statut', 'EN_ATTENTE')->count(),
            'total_en_retard' => $rappels->where('statut', 'EN_RETARD')->count(),
        ];
    }
}
