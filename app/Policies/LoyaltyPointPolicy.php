<?php

namespace App\Policies;

use App\Models\LoyaltyPoint;
use App\Models\User;

class LoyaltyPointPolicy
{
    /**
     * Determine whether the user can list loyalty points.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('list loyalty point');
    }

    /**
     * Determine whether the user can view a specific loyalty point.
     */
    public function view(User $user, ?LoyaltyPoint $loyaltyPoint = null): bool
    {
        return $user->can('view loyalty point');
    }

    /**
     * Determine whether the user can create loyalty points.
     */
    public function create(User $user): bool
    {
        return $user->can('create loyalty point');
    }

    /**
     * Determine whether the user can update a specific loyalty point.
     */
    public function update(User $user, LoyaltyPoint $loyaltyPoint): bool
    {
        return $user->can('edit loyalty point');
    }

    /**
     * Determine whether the user can delete a specific loyalty point.
     */
    public function delete(User $user, LoyaltyPoint $loyaltyPoint): bool
    {
        return $user->can('delete loyalty point');
    }
}
