<?php

use App\AuctionAssistant\Contracts\AuctionAssistant;
use App\AuctionAssistant\Data\BidSuggestion;
use App\AuctionAssistant\Data\BidSuggestionContext;
use App\AuctionAssistant\Data\CallSuggestion;
use App\AuctionAssistant\Data\CallSuggestionContext;
use App\AuctionAssistant\Data\StrategyContext;
use App\AuctionAssistant\Data\TeamStrategy;
use App\Enums\AuctionStatus;
use App\Enums\PlayerRole;
use App\Models\Auction;
use App\Models\AuctionAdvisorEntry;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Records every context it was asked to reason over, so tests can assert on
 * what the controller built, without making real AI calls.
 */
class RecordingAuctionAssistant implements AuctionAssistant
{
    public ?CallSuggestionContext $callContext = null;

    public ?BidSuggestionContext $bidContext = null;

    public ?StrategyContext $strategyContext = null;

    public function suggestCall(CallSuggestionContext $context, Auction $auction, User $user): CallSuggestion
    {
        $this->callContext = $context;

        return new CallSuggestion((int) $context->availablePlayers[0]['id'], 'test', false);
    }

    public function suggestMaxBid(BidSuggestionContext $context, Auction $auction, User $user): BidSuggestion
    {
        $this->bidContext = $context;

        return new BidSuggestion(1, 'test', false);
    }

    public function strategy(StrategyContext $context, Auction $auction, User $user): TeamStrategy
    {
        $this->strategyContext = $context;

        return new TeamStrategy('piano di test', [], []);
    }

    public function streamCall(CallSuggestionContext $context, Auction $auction, User $user): Generator
    {
        yield 'sto pensando...';

        return $this->suggestCall($context, $auction, $user);
    }

    public function streamMaxBid(BidSuggestionContext $context, Auction $auction, User $user): Generator
    {
        yield 'sto pensando...';

        return $this->suggestMaxBid($context, $auction, $user);
    }
}

function fakeAuctionAssistant(): RecordingAuctionAssistant
{
    $assistant = new RecordingAuctionAssistant;
    app()->instance(AuctionAssistant::class, $assistant);

    return $assistant;
}

test('the advisor streams call advice from the perspective of the participant the requesting user claimed, only during their own turn', function () {
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

    $auction->update(['current_turn_auction_participant_id' => $myParticipant->id]);

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

    $assistant = fakeAuctionAssistant();

    $response = $this->actingAs($user)->get(route('auctions.advisor.call', $auction));

    $response->assertOk()->assertStreamed();

    $streamed = $response->streamedContent();

    expect($assistant->callContext)->not->toBeNull();
    expect($assistant->callContext->controlledParticipant['id'])->toBe($myParticipant->id);
    expect($assistant->callContext->controlledParticipant['id'])->not->toBe($agentParticipant->id);
    expect($streamed)->toContain('event: delta');
    expect($streamed)->toContain('event: result');

    expect(AuctionAdvisorEntry::query()->where('kind', 'call')->exists())->toBeTrue();
});

test('a participant who is not on turn cannot ask who to call, but can still ask for a max bid suggestion', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();

    $auction = Auction::factory()->for($owner)->create([
        'status' => AuctionStatus::InProgress,
        'current_phase' => PlayerRole::Goalkeeper,
    ]);

    $otherParticipant = $auction->participants()->create([
        'name' => 'Altro', 'is_agent' => false, 'call_order' => 1,
    ]);
    $myParticipant = $auction->participants()->create([
        'name' => 'Me', 'is_agent' => false, 'call_order' => 2,
    ]);

    $auction->update(['current_turn_auction_participant_id' => $otherParticipant->id]);

    $auction->viewers()->create([
        'user_id' => $user->id,
        'auction_participant_id' => $myParticipant->id,
    ]);

    $team = Team::query()->create(['name' => 'Test FC']);
    $player = Player::query()->create([
        'fanta_id' => 1,
        'team_id' => $team->id,
        'name' => 'Il Portiere',
        'role' => PlayerRole::Goalkeeper,
        'initial_quotation' => 1,
        'current_quotation' => 1,
    ]);

    $call = $auction->calls()->create([
        'player_id' => $player->id,
        'called_by_auction_participant_id' => $otherParticipant->id,
        'status' => 'open',
    ]);
    $auction->update(['current_call_id' => $call->id]);

    fakeAuctionAssistant();

    $this->actingAs($user)
        ->getJson(route('auctions.advisor.call', $auction))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('auctions.advisor.bid', $auction))
        ->assertOk()
        ->assertStreamed();
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

test('any claimed participant can generate the AI strategy at any time, and it gets recorded', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();

    $auction = Auction::factory()->for($owner)->create([
        'status' => AuctionStatus::InProgress,
        'current_phase' => PlayerRole::Goalkeeper,
    ]);

    $otherParticipant = $auction->participants()->create([
        'name' => 'Altro', 'is_agent' => false, 'call_order' => 1,
    ]);
    $myParticipant = $auction->participants()->create([
        'name' => 'Me', 'is_agent' => false, 'call_order' => 2,
    ]);

    // It's not my turn, but strategy isn't turn-restricted.
    $auction->update(['current_turn_auction_participant_id' => $otherParticipant->id]);

    $auction->viewers()->create([
        'user_id' => $user->id,
        'auction_participant_id' => $myParticipant->id,
    ]);

    $assistant = fakeAuctionAssistant();

    $this->actingAs($user)
        ->postJson(route('auctions.advisor.strategy', $auction))
        ->assertOk();

    expect($assistant->strategyContext)->not->toBeNull();
    expect($assistant->strategyContext->controlledParticipant['id'])->toBe($myParticipant->id);

    $entry = AuctionAdvisorEntry::query()->where('kind', 'strategy')->sole();
    expect($entry->auction_id)->toBe($auction->id);
    expect($entry->user_id)->toBe($user->id);
    expect($entry->reasoning)->toBe('piano di test');
});
