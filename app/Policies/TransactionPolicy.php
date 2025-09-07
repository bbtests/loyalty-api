<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Determine whether the user can list transactions.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('list transaction');
    }

    /**
     * Determine whether the user can view a specific transaction.
     */
    public function view(User $user, ?Transaction $transaction = null): bool
    {
        return $user->can('view transaction');
    }

    /**
     * Determine whether the user can create transactions.
     */
    public function create(User $user): bool
    {
        return $user->can('create transaction');
    }

    /**
     * Determine whether the user can update a specific transaction.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        return $user->can('edit transaction');
    }

    /**
     * Determine whether the user can delete a specific transaction.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->can('delete transaction');
    }
}
