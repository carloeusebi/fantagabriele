<?php

namespace App\AuctionAssistant\Data;

final class BidSuggestionContext
{
    /**
     * @param  array<string, mixed>  $player
     * @param  array<string, mixed>  $controlledParticipant
     * @param  array<int, array<string, mixed>>  $recentPurchases
     */
    public function __construct(
        public array $player,
        public int $budgetPerParticipant,
        public array $controlledParticipant,
        public int $competitorsNeedingRole,
        public int $remainingPoolSize,
        public array $recentPurchases,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'player' => $this->player,
            'budget_per_participant' => $this->budgetPerParticipant,
            'controlled_participant' => $this->controlledParticipant,
            'competitors_needing_role' => $this->competitorsNeedingRole,
            'remaining_pool_size' => $this->remainingPoolSize,
            'recent_purchases' => $this->recentPurchases,
        ];
    }
}
