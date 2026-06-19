<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\User;

class FarmAccessService
{
    public function canAccess(User $user, Farm $farm, bool $ownerOnly = false): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if ($farm->owner_id === $user->id) {
            return true;
        }

        if (!$ownerOnly) {
            return $farm->users()->where('user_id', $user->id)->exists();
        }

        return false;
    }
}
