<?php

namespace App\Policies;

use App\Models\Espece;
use App\Models\User;

class EspecePolicy
{
    /**
     * Déterminer si l'utilisateur peut voir les espèces.
     */
    public function view(User $user, Espece $espece): bool
    {
        return $user->hasRole('superadmin') || $user->hasPermissionTo('especes.view', 'api');
    }

    /**
     * Déterminer si l'utilisateur peut créer des espèces.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('superadmin') || $user->hasPermissionTo('especes.create', 'api');
    }

    /**
     * Déterminer si l'utilisateur peut modifier des espèces.
     */
    public function update(User $user, Espece $espece): bool
    {
        return $user->hasRole('superadmin') || $user->hasPermissionTo('especes.update', 'api');
    }

    /**
     * Déterminer si l'utilisateur peut supprimer des espèces.
     */
    public function delete(User $user, Espece $espece): bool
    {
        return $user->hasRole('superadmin') || $user->hasPermissionTo('especes.delete', 'api');
    }

    /**
     * Déterminer si l'utilisateur peut restaurer des espèces.
     */
    public function restore(User $user): bool
    {
        return $user->hasRole('superadmin') || $user->hasPermissionTo('especes.update', 'api');
    }
}
