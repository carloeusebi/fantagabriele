<?php

namespace App\AuctionAssistant\Data;

final class CallSuggestionContext
{
    /**
     * @param  array<string, mixed>  $controlledParticipant
     * @param  array<int, array<string, mixed>>  $otherParticipants
     * @param  array<int, array<string, mixed>>  $availablePlayers
     */
    public function __construct(
        public string $role,
        public string $roleLabel,
        public array $controlledParticipant,
        public array $otherParticipants,
        public array $availablePlayers,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'role_label' => $this->roleLabel,
            'controlled_participant' => $this->controlledParticipant,
            'other_participants' => $this->otherParticipants,
            'available_players' => $this->availablePlayers,
        ];
    }
}
