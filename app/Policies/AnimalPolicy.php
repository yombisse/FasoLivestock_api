<?php

namespace App\Policies;

use App\Models\Animal;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AnimalPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('animals.view') || $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Animal $animal): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('animals.view')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de l'animal
        return $animal->farm->owner_id === $user->id
            || $animal->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('animals.create') || $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Animal $animal): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('animals.update')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de l'animal
        return $animal->farm->owner_id === $user->id
            || $animal->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Animal $animal): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('animals.delete')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de l'animal
        return $animal->farm->owner_id === $user->id
            || $animal->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Animal $animal): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('animals.update')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de l'animal
        return $animal->farm->owner_id === $user->id
            || $animal->farm->users()->where('user_id', $user->id)->exists();
    }
}
