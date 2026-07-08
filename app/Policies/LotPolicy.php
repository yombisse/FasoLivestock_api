<?php

namespace App\Policies;

use App\Models\Lot;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LotPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any lots.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('lots.view', 'api') || $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can view the lot.
     */
    public function view(User $user, Lot $lot): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('lots.view', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme du lot
        return $lot->farm->owner_id === $user->id
            || $lot->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create lots.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('lots.create', 'api') || $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can update the lot.
     */
    public function update(User $user, Lot $lot): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('lots.update', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme du lot
        return $lot->farm->owner_id === $user->id
            || $lot->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can delete the lot.
     */
    public function delete(User $user, Lot $lot): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('lots.delete', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme du lot
        return $lot->farm->owner_id === $user->id
            || $lot->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can restore the lot.
     */
    public function restore(User $user, Lot $lot): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('lots.update', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme du lot
        return $lot->farm->owner_id === $user->id
            || $lot->farm->users()->where('user_id', $user->id)->exists();
    }
}
