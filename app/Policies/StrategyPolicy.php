<?php

namespace App\Policies;

use App\Models\Strategy;
use App\Models\User;

class StrategyPolicy
{
    /**
     * Determine whether the user can view, update, or delete the strategy.
     * Strategies are private plans, owned by exactly one user.
     */
    public function view(User $user, Strategy $strategy): bool
    {
        return $user->id === $strategy->user_id;
    }

    public function update(User $user, Strategy $strategy): bool
    {
        return $user->id === $strategy->user_id;
    }

    public function delete(User $user, Strategy $strategy): bool
    {
        return $user->id === $strategy->user_id;
    }
}
