<?php

namespace App\AuctionAssistant\Data;

final class BidSuggestionContext
{
    /**
     * @param  array<string, mixed>  $player
     * @param  array<string, mixed>  $controlledParticipant
     * @param  array<int, array<string, mixed>>  $recentPurchases
     * @param  array<string, mixed>|null  $userStrategy
     */
    public function __construct(
        public array $player,
        public int $budgetPerParticipant,
        public array $controlledParticipant,
        public int $competitorsNeedingRole,
        public int $remainingPoolSize,
        public array $recentPurchases,
        public ?array $userStrategy = null,
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
            'user_strategy' => $this->userStrategy,
        ];
    }
}
