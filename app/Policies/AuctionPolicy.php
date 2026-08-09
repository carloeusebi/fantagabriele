<?php

namespace App\Policies;

use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\User;

class AuctionPolicy
{
    /**
     * Determine whether the user can delete the auction.
     */
    public function delete(User $user, Auction $auction): bool
    {
        return $user->isAdmin() || $this->isOwner($user, $auction);
    }

    /**
     * Determine whether the user can advance the auction's status
     * (start/pause/resume). Reserved for admins: they run the room.
     */
    public function updateStatus(User $user, Auction $auction): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can manage the participant roster
     * (add/remove/designate agent/reorder). Auction configuration is the
     * organizer's responsibility.
     */
    public function manageParticipants(User $user, Auction $auction): bool
    {
        return $user->isAdmin() || $this->isOwner($user, $auction);
    }

    /**
     * Determine whether the user can call the given player-role slot for
     * $caller. Admins may call on behalf of anyone at any time; any other
     * user may only call as the participant they've claimed, and only
     * during that participant's turn.
     */
    public function call(User $user, Auction $auction, AuctionParticipant $caller): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $viewer = $auction->viewers()->where('user_id', $user->id)->first();

        return $viewer !== null
            && $viewer->auction_participant_id === $caller->id
            && $caller->id === $auction->current_turn_auction_participant_id;
    }

    /**
     * Determine whether the user can resolve the currently open call
     * (register/correct/revert an assignment, or mark it unsold). These
     * actions advance the auction to the next turn, so they're reserved
     * for admins.
     */
    public function resolveCall(User $user, Auction $auction): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can ask the AI advisor for a suggestion.
     * Allowed for admins and for anyone who has claimed a participant, at
     * any time — asking for advice doesn't advance the auction, so it
     * isn't restricted to a participant's own turn.
     */
    public function advise(User $user, Auction $auction): bool
    {
        if ($user->isAdmin()) {
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
