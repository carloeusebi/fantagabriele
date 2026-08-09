<?php

namespace App\AuctionAssistant\Contracts;

use App\AuctionAssistant\Data\BidSuggestion;
use App\AuctionAssistant\Data\BidSuggestionContext;
use App\AuctionAssistant\Data\CallSuggestion;
use App\AuctionAssistant\Data\CallSuggestionContext;

interface AuctionAssistant
{
    public function suggestCall(CallSuggestionContext $context): CallSuggestion;

    public function suggestMaxBid(BidSuggestionContext $context): BidSuggestion;
}
