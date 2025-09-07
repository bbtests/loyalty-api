<?php

namespace App\Policies;

use App\Models\Badge;
use App\Models\User;

class BadgePolicy
{
    /**
     * Determine whether the user can list badges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('list badge');
    }

    /**
     * Determine whether the user can view a specific badge.
     */
    public function view(User $user, ?Badge $badge = null): bool
    {
        return $user->can('view badge');
    }

    /**
     * Determine whether the user can create badges.
     */
    public function create(User $user): bool
    {
        return $user->can('create badge');
    }

    /**
     * Determine whether the user can update a specific badge.
     */
    public function update(User $user, Badge $badge): bool
    {
        return $user->can('edit badge');
    }

    /**
     * Determine whether the user can delete a specific badge.
     */
    public function delete(User $user, Badge $badge): bool
    {
        return $user->can('delete badge');
    }
}
