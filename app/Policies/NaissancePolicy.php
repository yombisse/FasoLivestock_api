<?php

namespace App\Policies;

use App\Models\Naissance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NaissancePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any naissances.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('reproduction.view', 'api') || $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can view the naissance.
     */
    public function view(User $user, Naissance $naissance): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('reproduction.view', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de la naissance
        return $naissance->farm->owner_id === $user->id
            || $naissance->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create naissances.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('reproduction.create', 'api') || $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can update the naissance.
     */
    public function update(User $user, Naissance $naissance): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('reproduction.update', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de la naissance
        return $naissance->farm->owner_id === $user->id
            || $naissance->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can delete the naissance.
     */
    public function delete(User $user, Naissance $naissance): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('reproduction.delete', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de la naissance
        return $naissance->farm->owner_id === $user->id
            || $naissance->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can restore the naissance.
     */
    public function restore(User $user, Naissance $naissance): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('reproduction.update', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de la naissance
        return $naissance->farm->owner_id === $user->id
            || $naissance->farm->users()->where('user_id', $user->id)->exists();
    }
}
