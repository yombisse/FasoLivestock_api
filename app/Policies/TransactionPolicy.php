<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransactionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any transactions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('transactions.view', 'api') || $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can view the transaction.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('transactions.view', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de la transaction
        return $transaction->farm->owner_id === $user->id
            || $transaction->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create transactions.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('transactions.create', 'api') || $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can update the transaction.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('transactions.update', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de la transaction
        return $transaction->farm->owner_id === $user->id
            || $transaction->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can delete the transaction.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('transactions.delete', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de la transaction
        return $transaction->farm->owner_id === $user->id
            || $transaction->farm->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can restore the transaction.
     */
    public function restore(User $user, Transaction $transaction): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if (!$user->hasPermissionTo('transactions.update', 'api')) {
            return false;
        }

        // Vérifier que l'utilisateur a accès à la ferme de la transaction
        return $transaction->farm->owner_id === $user->id
            || $transaction->farm->users()->where('user_id', $user->id)->exists();
    }
}
