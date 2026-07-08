<?php

namespace App\Policies;

use App\Models\SanteRappel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SanteRappelPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any sante rappels.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('sante.view', 'api') || $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can view the sante rappel.
     */
    public function view(User $user, SanteRappel $santeRappel): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('sante.view', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme du rappel sanitaire
        return $santeRappel->farm->owner_id === $user->id
            || $santeRappel->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create sante rappels.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('sante.create', 'api') || $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can update the sante rappel.
     */
    public function update(User $user, SanteRappel $santeRappel): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('sante.update', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme du rappel sanitaire
        return $santeRappel->farm->owner_id === $user->id
            || $santeRappel->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can delete the sante rappel.
     */
    public function delete(User $user, SanteRappel $santeRappel): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('sante.delete', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme du rappel sanitaire
        return $santeRappel->farm->owner_id === $user->id
            || $santeRappel->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can restore the sante rappel.
     */
    public function restore(User $user, SanteRappel $santeRappel): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('sante.update', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme du rappel sanitaire
        return $santeRappel->farm->owner_id === $user->id
            || $santeRappel->farm->users()->where('user_id', $user->id)->exists();
    }
}
