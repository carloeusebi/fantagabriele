<?php

namespace App\AuctionAssistant\Data;

final class BidSuggestion
{
    public function __construct(
        public int $maxPrice,
        public string $reasoning,
    ) {}
}
