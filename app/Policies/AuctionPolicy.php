<?php

namespace App\Policies;

use App\Models\Auction;
use App\Models\User;

class AuctionPolicy
{
    /**
     * Determine whether the user can delete the auction.
     */
    public function delete(User $user, Auction $auction): bool
    {
        return $this->isOwner($user, $auction);
    }

    /**
     * Determine whether the user can advance the auction's status
     * (start/pause/resume).
     */
    public function updateStatus(User $user, Auction $auction): bool
    {
        return $this->isOwner($user, $auction);
    }

    /**
     * Determine whether the user can manage the participant roster
     * (add/remove/designate agent/reorder). Auction configuration is the
     * organizer's responsibility.
     */
    public function manageParticipants(User $user, Auction $auction): bool
    {
        return $this->isOwner($user, $auction);
    }

    /**
     * Determine whether the user can perform live auction actions
     * (call a player, register/correct/revert an assignment). Allowed for
     * the organizer and for anyone who has claimed a participant —
     * spectators are read-only.
     */
    public function act(User $user, Auction $auction): bool
    {
        if ($this->isOwner($user, $auction)) {
            return true;
        }

        $viewer = $auction->viewers()->where('user_id', $user->id)->first();

        return $viewer !== null && $viewer->auction_participant_id !== null;
    }

    private function isOwner(User $user, Auction $auction): bool
    {
        return $user->id === $auction->user_id;
    }
}
