<?php

namespace App\AuctionAssistant\Data;

final class CallSuggestionContext
{
    /**
     * @param  array<string, mixed>  $controlledParticipant
     * @param  array<int, array<string, mixed>>  $otherParticipants
     * @param  array<int, array<string, mixed>>  $availablePlayers
     * @param  array<int, array<string, mixed>>  $recentPurchases
     * @param  array<string, mixed>|null  $userStrategy
     */
    public function __construct(
        public string $role,
        public string $roleLabel,
        public int $budgetPerParticipant,
        public array $controlledParticipant,
        public array $otherParticipants,
        public array $availablePlayers,
        public array $recentPurchases,
        public ?array $userStrategy = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'role_label' => $this->roleLabel,
            'budget_per_participant' => $this->budgetPerParticipant,
            'controlled_participant' => $this->controlledParticipant,
            'other_participants' => $this->otherParticipants,
            'available_players' => $this->availablePlayers,
            'recent_purchases' => $this->recentPurchases,
            'user_strategy' => $this->userStrategy,
        ];
    }
}
