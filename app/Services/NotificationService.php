<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\SanteRappel;
use App\Models\Naissance;
use App\Models\Aliment;
use App\Models\Farm;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Get notifications for a farm with filters
     */
    public function getForFarm(string $farmId, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::query()
            ->where('farm_id', $farmId)
            ->with(['animal', 'santeRappel', 'evenement'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['lu']) && $filters['lu'] !== null) {
            $userId = $filters['user_id'] ?? auth()->id();
            if ($filters['lu']) {
                $query->whereHas('users', function ($q) use ($userId) {
                    $q->where('user_id', $userId)->where('is_read', true);
                });
            } else {
                $query->whereHas('users', function ($q) use ($userId) {
                    $q->where('user_id', $userId)->where('is_read', false);
                });
            }
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get unread notifications for a user
     */
    public function getNonLues(string $farmId, string $userId): Collection
    {
        return Notification::query()
            ->where('farm_id', $farmId)
            ->whereHas('users', function ($q) use ($userId) {
                $q->where('user_id', $userId)->where('is_read', false);
            })
            ->with(['animal', 'santeRappel', 'evenement'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Mark a notification as read for a user
     */
    public function marquerLu(Notification $notification, string $userId): bool
    {
        try {
            $notification->marquerCommeLue($userId);
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to mark notification as read: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all unread notifications as read for a user in a farm
     */
    public function marquerToutLu(string $farmId, string $userId): int
    {
        try {
            $notificationIds = Notification::query()
                ->where('farm_id', $farmId)
                ->whereHas('users', function ($q) use ($userId) {
                    $q->where('user_id', $userId)->where('is_read', false);
                })
                ->pluck('id');

            $count = 0;
            foreach ($notificationIds as $notificationId) {
                $notification = Notification::find($notificationId);
                if ($notification) {
                    $notification->marquerCommeLue($userId);
                    $count++;
                }
            }

            return $count;
        } catch (\Exception $e) {
            \Log::error('Failed to mark all notifications as read: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Create a notification and attach it to farm users
     */
    public function creerNotification(
        string $farmId,
        string $type,
        string $titre,
        string $message,
        ?string $animalId = null,
        ?string $evenementId = null,
        ?string $santeRappelId = null
    ): Notification {
        // Check if a similar notification already exists today
        $existingToday = Notification::query()
            ->where('farm_id', $farmId)
            ->where('type', $type)
            ->where('titre', $titre)
            ->whereDate('created_at', today())
            ->when($animalId, fn ($q) => $q->where('animal_id', $animalId))
            ->when($santeRappelId, fn ($q) => $q->where('sante_rappel_id', $santeRappelId))
            ->first();

        if ($existingToday) {
            return $existingToday;
        }

        return DB::transaction(function () use ($farmId, $type, $titre, $message, $animalId, $evenementId, $santeRappelId) {
            $notification = Notification::create([
                'farm_id' => $farmId,
                'animal_id' => $animalId,
                'titre' => $titre,
                'message' => $message,
                'type' => $type,
                'evenement_id' => $evenementId,
                'sante_rappel_id' => $santeRappelId,
                'sync_status' => 'synced',
                'version' => 1,
            ]);

            // Attach to all farm users
            $farm = Farm::find($farmId);
            if ($farm) {
                $userIds = $farm->users()->pluck('users.id');
                $notification->users()->attach(
                    $userIds->mapWithKeys(fn ($id) => [
                        $id => ['is_read' => false]
                    ])->toArray()
                );
            }

            $notification->update(['sent_at' => now()]);

            return $notification;
        });
    }

    /**
     * Generate intelligent alerts for a farm
     */
    public function genererAlertes(string $farmId): array
    {
        $typesGenerees = [];
        $totalGenerees = 0;

        // 1. SanteRappel en retard
        $rappelsEnRetard = SanteRappel::query()
            ->where('farm_id', $farmId)
            ->where('statut', 'EN_RETARD')
            ->whereDoesntHave('notifications', function ($q) {
                $q->where('type', 'sante_retard')->whereDate('created_at', today());
            })
            ->with(['animal'])
            ->get();

        foreach ($rappelsEnRetard as $rappel) {
            $animalNom = $rappel->animal->nom ?? $rappel->animal->numero_identification;
            $this->creerNotification(
                $farmId,
                'sante_retard',
                'Rappel sanitaire en retard',
                "Le rappel de {$rappel->type_rappel} pour l'animal {$animalNom} est en retard depuis le {$rappel->date_prevue->format('d/m/Y')}.",
                $rappel->animal_id,
                null,
                $rappel->id
            );
            $typesGenerees[] = 'sante_retard';
            $totalGenerees++;
        }

        // 2. SanteRappel à venir (7 prochains jours)
        $rappelsAVenir = SanteRappel::query()
            ->where('farm_id', $farmId)
            ->where('statut', 'EN_ATTENTE')
            ->whereBetween('date_prevue', [now(), now()->addDays(7)])
            ->whereDoesntHave('notifications', function ($q) {
                $q->where('type', 'sante_rappel')->whereDate('created_at', today());
            })
            ->with(['animal'])
            ->get();

        foreach ($rappelsAVenir as $rappel) {
            $joursRestants = now()->diffInDays($rappel->date_prevue, false);
            $animalNom = $rappel->animal->nom ?? $rappel->animal->numero_identification;
            $this->creerNotification(
                $farmId,
                'sante_rappel',
                'Rappel sanitaire à venir',
                "Rappel de {$rappel->type_rappel} pour l'animal {$animalNom} prévu dans {$joursRestants} jours ({$rappel->date_prevue->format('d/m/Y')}).",
                $rappel->animal_id,
                null,
                $rappel->id
            );
            $typesGenerees[] = 'sante_rappel';
            $totalGenerees++;
        }

        // 3. Naissance avec mise bas prévue (14 prochains jours)
        $naissancesAVenir = Naissance::query()
            ->where('farm_id', $farmId)
            ->whereBetween('date_mise_bas_prevue', [now(), now()->addDays(14)])
            ->whereDoesntHave('notifications', function ($q) {
                $q->where('type', 'mise_bas_prevue')->whereDate('created_at', today());
            })
            ->with(['mother'])
            ->get();

        foreach ($naissancesAVenir as $naissance) {
            $joursRestants = now()->diffInDays($naissance->date_mise_bas_prevue, false);
            $motherNom = $naissance->mother->nom ?? $naissance->mother->numero_identification;
            $this->creerNotification(
                $farmId,
                'mise_bas_prevue',
                'Mise bas prévue',
                "Mise bas prévue pour la mère {$motherNom} dans {$joursRestants} jours ({$naissance->date_mise_bas_prevue->format('d/m/Y')}).",
                $naissance->mother_id,
                null,
                null
            );
            $typesGenerees[] = 'mise_bas_prevue';
            $totalGenerees++;
        }

        // 4. Aliment en rupture de stock
        $alimentsRupture = Aliment::query()
            ->where('farm_id', $farmId)
            ->where('stock_actuel', '<=', 0)
            ->whereDoesntHave('notifications', function ($q) {
                $q->where('type', 'rupture_stock')->whereDate('created_at', today());
            })
            ->get();

        foreach ($alimentsRupture as $aliment) {
            $this->creerNotification(
                $farmId,
                'rupture_stock',
                'Rupture de stock',
                "L'aliment {$aliment->nom} est en rupture de stock (stock actuel: {$aliment->stock_actuel}).",
                null,
                null,
                null
            );
            $typesGenerees[] = 'rupture_stock';
            $totalGenerees++;
        }

        return [
            'generees' => $totalGenerees,
            'types' => array_unique($typesGenerees),
        ];
    }
}
