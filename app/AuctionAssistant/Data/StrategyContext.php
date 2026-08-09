<?php

namespace App\AuctionAssistant\Data;

final class StrategyContext
{
    /**
     * @param  array<string, int>  $roleSlots
     * @param  array<string, mixed>  $controlledParticipant
     * @param  array<int, array<string, mixed>>  $otherParticipants
     * @param  array<int, array<string, mixed>>  $recentPurchases
     * @param  array<string, array<int, array<string, mixed>>>  $availablePlayersByRole
     */
    public function __construct(
        public int $budgetPerParticipant,
        public array $roleSlots,
        public array $controlledParticipant,
        public array $otherParticipants,
        public array $recentPurchases,
        public array $availablePlayersByRole,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'budget_per_participant' => $this->budgetPerParticipant,
            'role_slots' => $this->roleSlots,
            'controlled_participant' => $this->controlledParticipant,
            'other_participants' => $this->otherParticipants,
            'recent_purchases' => $this->recentPurchases,
            'available_players_by_role' => $this->availablePlayersByRole,
        ];
    }
}
