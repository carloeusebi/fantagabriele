<?php

namespace App\AuctionAssistant\Contracts;

use App\AuctionAssistant\Data\BidSuggestion;
use App\AuctionAssistant\Data\BidSuggestionContext;
use App\AuctionAssistant\Data\CallSuggestion;
use App\AuctionAssistant\Data\CallSuggestionContext;
use Generator;

interface AuctionAssistant
{
    public function suggestCall(CallSuggestionContext $context): CallSuggestion;

    public function suggestMaxBid(BidSuggestionContext $context): BidSuggestion;

    /**
     * Stream the assistant's reasoning as free-form text chunks while it
     * narrates which player it would call, finally returning the same
     * suggestion suggestCall() would produce.
     *
     * @return Generator<int, string, mixed, CallSuggestion>
     */
    public function streamCall(CallSuggestionContext $context): Generator;

    /**
     * Stream the assistant's reasoning as free-form text chunks while it
     * narrates how much it would bid, finally returning the same
     * suggestion suggestMaxBid() would produce.
     *
     * @return Generator<int, string, mixed, BidSuggestion>
     */
    public function streamMaxBid(BidSuggestionContext $context): Generator;
}
