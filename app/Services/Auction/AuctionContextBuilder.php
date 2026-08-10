<?php

namespace App\Services\Auction;

use App\AuctionAssistant\Data\BidSuggestionContext;
use App\AuctionAssistant\Data\CallSuggestionContext;
use App\AuctionAssistant\Data\StrategyContext;
use App\Enums\PlayerRole;
use App\Exceptions\Auction\AuctionRuleException;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\Player;
use App\Models\PlayerAssignment;

/**
 * Builds the ready-to-use context the AI agents reason over. Agents never
 * query the database themselves — all Eloquent access happens here.
 *
 * Official player quotations (FVM, current/initial quotation) are
 * deliberately never included in what's sent to the agents: they're a
 * generic rating unrelated to any specific auction's initial credits (a
 * league with 200 credits and one with 1000 credits, same slot count,
 * make the same FVM meaningless for either). `fvm` is only ever used here
 * as an internal DB ordering/truncation heuristic — its value is never
 * exposed in the payload. Agents instead get `budget_per_participant`
 * (this auction's real initial credits) and `recent_purchases` (prices
 * actually paid in this auction) to calibrate value.
 */
class AuctionContextBuilder
{
    private const int RECENT_PURCHASES_LIMIT = 10;

    private const int STRATEGY_PLAYERS_PER_ROLE = 25;

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
            ->orderBy('name')
            ->get()
            ->map(fn (Player $player) => $this->playerSummary($player))
            ->values()
            ->all();

        return new CallSuggestionContext(
            role: $role->value,
            roleLabel: $role->label(),
            budgetPerParticipant: $auction->budget_per_participant,
            controlledParticipant: $controlledParticipant->toBoardArray(),
            otherParticipants: $others->map->toBoardArray()->values()->all(),
            availablePlayers: $availablePlayers,
            recentPurchases: $this->recentPurchases($auction),
            userStrategy: $this->userStrategy($controlledParticipant),
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
            budgetPerParticipant: $auction->budget_per_participant,
            controlledParticipant: $controlledParticipant->toBoardArray(),
            competitorsNeedingRole: $competitorsNeedingRole,
            remainingPoolSize: $remainingPoolSize,
            recentPurchases: $this->recentPurchases($auction),
            userStrategy: $this->userStrategy($controlledParticipant),
        );
    }

    public function forStrategy(Auction $auction, AuctionParticipant $controlledParticipant): StrategyContext
    {
        $others = $auction->participants()
            ->whereKeyNot($controlledParticipant->id)
            ->orderBy('call_order')
            ->get();

        $availablePlayersByRole = collect(PlayerRole::cases())->mapWithKeys(
            fn (PlayerRole $role) => [
                $role->value => Player::query()
                    ->where('role', $role)
                    ->whereDoesntHave('assignments', fn ($query) => $query->where('auction_id', $auction->id))
                    ->with('team')
                    ->orderByDesc('fvm')
                    ->limit(self::STRATEGY_PLAYERS_PER_ROLE)
                    ->get()
                    ->map(fn (Player $player) => $this->playerSummary($player))
                    ->values()
                    ->all(),
            ]
        )->all();

        return new StrategyContext(
            budgetPerParticipant: $auction->budget_per_participant,
            roleSlots: collect(PlayerRole::cases())->mapWithKeys(
                fn (PlayerRole $role) => [$role->value => $auction->slotsFor($role)]
            )->all(),
            controlledParticipant: $controlledParticipant->toBoardArray(),
            otherParticipants: $others->map->toBoardArray()->values()->all(),
            recentPurchases: $this->recentPurchases($auction),
            availablePlayersByRole: $availablePlayersByRole,
            userStrategy: $this->userStrategy($controlledParticipant),
        );
    }

    /**
     * The participant's own claimer may have picked one of their saved
     * strategies to guide the AI instead of letting it build one from
     * scratch. When present, every agent (strategy/call/bid) should weigh
     * it heavily.
     *
     * @return array<string, mixed>|null
     */
    private function userStrategy(AuctionParticipant $controlledParticipant): ?array
    {
        return $controlledParticipant->viewer?->strategy?->toBriefArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentPurchases(Auction $auction, int $limit = self::RECENT_PURCHASES_LIMIT): array
    {
        return PlayerAssignment::query()
            ->where('auction_id', $auction->id)
            ->with(['player.team', 'participant'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (PlayerAssignment $assignment) => [
                'player_name' => $assignment->player->name,
                'role' => $assignment->player->role->value,
                'team' => $assignment->player->team?->name,
                'price' => $assignment->price,
                'buyer' => $assignment->participant->name,
                'percent_of_budget' => $auction->budget_per_participant > 0
                    ? round($assignment->price / $auction->budget_per_participant * 100, 1)
                    : null,
            ])
            ->values()
            ->all();
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
        ];
    }
}
