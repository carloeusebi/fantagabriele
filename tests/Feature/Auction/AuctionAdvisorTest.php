<?php

use App\AuctionAssistant\Contracts\AuctionAssistant;
use App\AuctionAssistant\Data\BidSuggestion;
use App\AuctionAssistant\Data\BidSuggestionContext;
use App\AuctionAssistant\Data\CallSuggestion;
use App\AuctionAssistant\Data\CallSuggestionContext;
use App\Enums\AuctionStatus;
use App\Enums\PlayerRole;
use App\Models\Auction;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;

test('the advisor streams advice from the perspective of the participant the requesting user claimed, not the designated agent participant', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();

    $auction = Auction::factory()->for($owner)->create([
        'status' => AuctionStatus::InProgress,
        'current_phase' => PlayerRole::Goalkeeper,
    ]);

    $agentParticipant = $auction->participants()->create([
        'name' => 'Bot', 'is_agent' => true, 'call_order' => 1,
    ]);
    $myParticipant = $auction->participants()->create([
        'name' => 'Me', 'is_agent' => false, 'call_order' => 2,
    ]);

    $auction->viewers()->create([
        'user_id' => $user->id,
        'auction_participant_id' => $myParticipant->id,
    ]);

    $team = Team::query()->create(['name' => 'Test FC']);
    Player::query()->create([
        'fanta_id' => 1,
        'team_id' => $team->id,
        'name' => 'Il Portiere',
        'role' => PlayerRole::Goalkeeper,
        'initial_quotation' => 1,
        'current_quotation' => 1,
    ]);

    $assistant = new class implements AuctionAssistant
    {
        public ?CallSuggestionContext $context = null;

        public function suggestCall(CallSuggestionContext $context): CallSuggestion
        {
            $this->context = $context;

            return new CallSuggestion((int) $context->availablePlayers[0]['id'], 'test');
        }

        public function suggestMaxBid(BidSuggestionContext $context): BidSuggestion
        {
            return new BidSuggestion(1, 'test');
        }

        public function streamCall(CallSuggestionContext $context): Generator
        {
            yield 'sto pensando...';

            return $this->suggestCall($context);
        }

        public function streamMaxBid(BidSuggestionContext $context): Generator
        {
            yield 'sto pensando...';

            return $this->suggestMaxBid($context);
        }
    };

    $this->app->instance(AuctionAssistant::class, $assistant);

    $response = $this->actingAs($user)->get(route('auctions.advisor.call', $auction));

    $response->assertOk()->assertStreamed();

    $streamed = $response->streamedContent();

    expect($assistant->context)->not->toBeNull();
    expect($assistant->context->controlledParticipant['id'])->toBe($myParticipant->id);
    expect($assistant->context->controlledParticipant['id'])->not->toBe($agentParticipant->id);
    expect($streamed)->toContain('event: delta');
    expect($streamed)->toContain('event: result');
});

test('a user cannot get advice for an auction they have not joined as a participant', function () {
    $owner = User::factory()->create();
    $spectator = User::factory()->create();

    $auction = Auction::factory()->for($owner)->create([
        'status' => AuctionStatus::InProgress,
        'current_phase' => PlayerRole::Goalkeeper,
    ]);

    $auction->participants()->create(['name' => 'Bot', 'is_agent' => true, 'call_order' => 1]);
    $auction->viewers()->create(['user_id' => $spectator->id, 'auction_participant_id' => null]);

    $this->actingAs($spectator)
        ->getJson(route('auctions.advisor.call', $auction))
        ->assertForbidden();
});

test('an admin without a claimed participant cannot get advice', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $auction = Auction::factory()->for($owner)->create([
        'status' => AuctionStatus::InProgress,
        'current_phase' => PlayerRole::Goalkeeper,
    ]);

    $auction->participants()->create(['name' => 'Bot', 'is_agent' => true, 'call_order' => 1]);

    $this->actingAs($admin)
        ->getJson(route('auctions.advisor.call', $auction))
        ->assertUnprocessable();
});
