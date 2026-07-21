<?php

namespace App\Services;

use App\Models\Farm;
use Spatie\Permission\Models\Role;

/**
 * Service de gestion des membres d'une ferme.
 *
 * Sémantique syncWithoutDetaching :
 * - Les membres non listés sont conservés (ajout/maintien uniquement).
 * - Pour un sync exact (détacher les absents), utiliser sync() à la place.
 */
class FarmMembershipService
{
    public function syncMembers(Farm $farm, array $users, ?string $excludeId = null): void
    {
        $syncData = collect($users)
            ->reject(fn ($u) => $excludeId !== null && $u['id'] === $excludeId)
            ->mapWithKeys(fn ($u) => [
                $u['id'] => ['role_id' => $u['role_id']]
            ])
            ->toArray();

        // syncWithoutDetaching : ajoute/maintient les membres sans détacher ceux non listés
        $farm->users()->syncWithoutDetaching($syncData);
    }

    public function removeMembers(Farm $farm, array $userIds): void
    {
        $farm->users()->detach($userIds);
    }

    /**
     * Obtenir les rôles disponibles pour les fermes (rôles Spatie avec guard_name='api')
     */
    public function getAvailableRoles(): array
    {
        return Role::where('guard_name', 'api')
            ->get()
            ->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
            ])
            ->toArray();
    }
}
