<?php

namespace App\Services\Auction;

use App\AuctionAssistant\Data\BidSuggestionContext;
use App\AuctionAssistant\Data\CallSuggestionContext;
use App\Exceptions\Auction\AuctionRuleException;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\Player;

/**
 * Builds the ready-to-use context the AI agents reason over. Agents never
 * query the database themselves — all Eloquent access happens here.
 */
class AuctionContextBuilder
{
    public function forCall(Auction $auction, AuctionParticipant $controlledParticipant): CallSuggestionContext
    {
        $role = $auction->current_phase;

        if (! $role) {
            throw AuctionRuleException::because('Nessuna fase attiva.');
        }

        $others = $auction->participants()
            ->whereKeyNot($controlledParticipant->id)
            ->orderBy('call_order')
            ->get();

        $availablePlayers = Player::query()
            ->where('role', $role)
            ->whereDoesntHave('assignments', fn ($query) => $query->where('auction_id', $auction->id))
            ->with('team')
            ->orderByDesc('fvm')
            ->get()
            ->map(fn (Player $player) => $this->playerSummary($player))
            ->values()
            ->all();

        return new CallSuggestionContext(
            role: $role->value,
            roleLabel: $role->label(),
            controlledParticipant: $controlledParticipant->toBoardArray(),
            otherParticipants: $others->map->toBoardArray()->values()->all(),
            availablePlayers: $availablePlayers,
        );
    }

    public function forBid(Auction $auction, Player $player, AuctionParticipant $controlledParticipant): BidSuggestionContext
    {
        $competitorsNeedingRole = $auction->participants()
            ->whereKeyNot($controlledParticipant->id)
            ->get()
            ->filter(fn (AuctionParticipant $participant) => ! $participant->hasCompletedRole($player->role))
            ->count();

        $remainingPoolSize = Player::query()
            ->where('role', $player->role)
            ->whereDoesntHave('assignments', fn ($query) => $query->where('auction_id', $auction->id))
            ->count();

        return new BidSuggestionContext(
            player: $this->playerSummary($player),
            controlledParticipant: $controlledParticipant->toBoardArray(),
            competitorsNeedingRole: $competitorsNeedingRole,
            remainingPoolSize: $remainingPoolSize,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function playerSummary(Player $player): array
    {
        return [
            'id' => $player->id,
            'name' => $player->name,
            'team' => $player->team?->name,
            'current_quotation' => $player->current_quotation,
            'fvm' => $player->fvm,
        ];
    }
}
