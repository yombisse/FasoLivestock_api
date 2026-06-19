<?php

namespace App\Policies;

use App\Models\Farm;
use App\Models\User;

class FarmPolicy
{
    public function view(User $user, Farm $farm): bool
    {
        return $user->hasRole('superadmin')
            || $farm->owner_id == $user->id
            || $farm->users->contains($user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('superadmin');
    }

    public function update(User $user, Farm $farm): bool
    {
        return $user->hasRole('superadmin')
            || $farm->owner_id == $user->id;
    }

    public function delete(User $user, Farm $farm): bool
    {
        return $user->hasRole('superadmin')
            || $farm->owner_id == $user->id;
    }

    public function manageMembers(User $user, Farm $farm): bool
    {
        return $user->hasRole('superadmin')
            || $farm->owner_id == $user->id;
    }
}
