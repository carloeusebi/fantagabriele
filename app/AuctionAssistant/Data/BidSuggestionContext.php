<?php

namespace App\AuctionAssistant\Data;

final class BidSuggestionContext
{
    /**
     * @param  array<string, mixed>  $player
     * @param  array<string, mixed>  $controlledParticipant
     */
    public function __construct(
        public array $player,
        public array $controlledParticipant,
        public int $competitorsNeedingRole,
        public int $remainingPoolSize,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'player' => $this->player,
            'controlled_participant' => $this->controlledParticipant,
            'competitors_needing_role' => $this->competitorsNeedingRole,
            'remaining_pool_size' => $this->remainingPoolSize,
        ];
    }
}
