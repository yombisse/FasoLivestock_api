<?php

namespace App\Policies;

use App\Models\Ration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any rations.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('alimentation.view', 'api') || $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can view the ration.
     */
    public function view(User $user, Ration $ration): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('alimentation.view', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de la ration
        return $ration->farm->owner_id === $user->id
            || $ration->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create rations.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('alimentation.create', 'api') || $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can update the ration.
     */
    public function update(User $user, Ration $ration): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('alimentation.update', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de la ration
        return $ration->farm->owner_id === $user->id
            || $ration->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can delete the ration.
     */
    public function delete(User $user, Ration $ration): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('alimentation.delete', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de la ration
        return $ration->farm->owner_id === $user->id
            || $ration->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can restore the ration.
     */
    public function restore(User $user, Ration $ration): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('alimentation.update', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de la ration
        return $ration->farm->owner_id === $user->id
            || $ration->farm->users()->where('user_id', $user->id)->exists();
    }
}
