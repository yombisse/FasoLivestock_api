<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function update(User $actor, User $target): bool
    {
        if ($target->hasRole('superadmin') && !$actor->hasRole('superadmin')) {
            return false;
        }
        return true;
    }

    public function toggleActive(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }
        if ($target->hasRole('superadmin') && !$actor->hasRole('superadmin')) {
            return false;
        }
        return true;
    }

    public function delete(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }
        if ($target->hasRole('superadmin') && !$actor->hasRole('superadmin')) {
            return false;
        }
        return true;
    }
}
