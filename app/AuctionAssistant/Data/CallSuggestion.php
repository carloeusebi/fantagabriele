<?php

namespace App\AuctionAssistant\Data;

final class CallSuggestion
{
    public function __construct(
        public int $playerId,
        public string $reasoning,
    ) {}
}
