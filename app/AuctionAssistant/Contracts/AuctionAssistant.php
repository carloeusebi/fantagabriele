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
     * Decide which player to call (the same decision suggestCall() would
     * produce), then stream free-form text chunks narrating the reasoning
     * behind that already-made decision.
     *
     * @return Generator<int, string, mixed, CallSuggestion>
     */
    public function streamCall(CallSuggestionContext $context, Auction $auction, User $user): Generator;

    /**
     * Decide how much to bid (the same decision suggestMaxBid() would
     * produce), then stream free-form text chunks narrating the reasoning
     * behind that already-made decision.
     *
     * @return Generator<int, string, mixed, BidSuggestion>
     */
    public function streamMaxBid(BidSuggestionContext $context, Auction $auction, User $user): Generator;
}
