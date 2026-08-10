<?php

use App\Ai\Agents\SuggestMaxBidAgent;
use App\AuctionAssistant\Data\BidSuggestionContext;
use App\AuctionAssistant\LaravelAiAuctionAssistant;
use App\Models\Auction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The advisor conversation is shared across every player evaluated during
 * an auction, so the prompt sent for each bid must name the player being
 * priced explicitly. Otherwise, once the conversation already discusses an
 * earlier player, the model can anchor on that earlier player instead of
 * the one described in the freshly-encoded context.
 */
test('the max bid prompt explicitly names the player being evaluated', function () {
    $auction = Auction::factory()->create();
    $user = User::factory()->create();

    SuggestMaxBidAgent::fake();

    $context = new BidSuggestionContext(
        player: ['id' => 2, 'name' => 'Martinez Jo.', 'team' => 'Inter'],
        budgetPerParticipant: 500,
        controlledParticipant: ['id' => 40, 'name' => 'Carlo', 'budget_remaining' => 500],
        competitorsNeedingRole: 3,
        remainingPoolSize: 10,
        recentPurchases: [],
    );

    app(LaravelAiAuctionAssistant::class)->suggestMaxBid($context, $auction, $user);

    SuggestMaxBidAgent::assertPrompted(
        fn ($prompt) => str_contains($prompt->prompt, 'Martinez Jo.') && str_contains($prompt->prompt, '2')
    );
});
