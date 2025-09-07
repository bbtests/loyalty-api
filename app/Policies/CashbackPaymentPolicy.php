<?php

namespace App\Policies;

use App\Models\CashbackPayment;
use App\Models\User;

class CashbackPaymentPolicy
{
    /**
     * Determine whether the user can list cashback payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('list cashback payment');
    }

    /**
     * Determine whether the user can view a specific cashback payment.
     */
    public function view(User $user, ?CashbackPayment $cashbackPayment = null): bool
    {
        return $user->can('view cashback payment');
    }

    /**
     * Determine whether the user can create cashback payments.
     */
    public function create(User $user): bool
    {
        return $user->can('create cashback payment');
    }

    /**
     * Determine whether the user can update a specific cashback payment.
     */
    public function update(User $user, CashbackPayment $cashbackPayment): bool
    {
        return $user->can('edit cashback payment');
    }

    /**
     * Determine whether the user can delete a specific cashback payment.
     */
    public function delete(User $user, CashbackPayment $cashbackPayment): bool
    {
        return $user->can('delete cashback payment');
    }
}
