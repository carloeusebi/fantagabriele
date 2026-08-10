<?php

namespace App\AuctionAssistant\Contracts;

use App\AuctionAssistant\Data\BidSuggestion;
use App\AuctionAssistant\Data\BidSuggestionContext;
use App\AuctionAssistant\Data\CallSuggestion;
use App\AuctionAssistant\Data\CallSuggestionContext;
use App\AuctionAssistant\Data\StrategyContext;
use App\AuctionAssistant\Data\TeamStrategy;
use App\Models\Auction;
use App\Models\User;
use Generator;

interface AuctionAssistant
{
    public function suggestCall(CallSuggestionContext $context, Auction $auction, User $user): CallSuggestion;

    public function suggestMaxBid(BidSuggestionContext $context, Auction $auction, User $user): BidSuggestion;

    /**
     * Build (or revise) the participant's overall auction strategy: how to
     * split the budget across roles and which players to prioritize.
     */
    public function strategy(StrategyContext $context, Auction $auction, User $user): TeamStrategy;

    /**
     * Stream the assistant's reasoning as free-form text chunks while it
     * narrates which player it would call, finally returning the same
     * suggestion suggestCall() would produce.
     *
     * @return Generator<int, string, mixed, CallSuggestion>
     */
    public function streamCall(CallSuggestionContext $context, Auction $auction, User $user): Generator;

    /**
     * Stream the assistant's reasoning as free-form text chunks while it
     * narrates how much it would bid, finally returning the same
     * suggestion suggestMaxBid() would produce.
     *
     * @return Generator<int, string, mixed, BidSuggestion>
     */
    public function streamMaxBid(BidSuggestionContext $context, Auction $auction, User $user): Generator;
}
