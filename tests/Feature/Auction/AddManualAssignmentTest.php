<?php

use App\Enums\AuctionStatus;
use App\Enums\PlayerRole;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\Player;
use App\Models\PlayerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAuction(array $attributes = []): Auction
{
    return Auction::query()->create([
        'user_id' => User::factory()->create()->id,
        'name' => 'Test auction',
        'status' => AuctionStatus::InProgress,
        'budget_per_participant' => 500,
        'slots_goalkeeper' => 3,
        'slots_defender' => 8,
        'slots_midfielder' => 8,
        'slots_forward' => 6,
        'current_phase' => PlayerRole::Defender,
        ...$attributes,
    ]);
}

function createPlayer(array $attributes = []): Player
{
    static $fantaId = 1;

    return Player::query()->create([
        'fanta_id' => $fantaId++,
        'name' => fake()->name(),
        'role' => PlayerRole::Forward,
        'initial_quotation' => 10,
        'current_quotation' => 10,
        ...$attributes,
    ]);
}

test('an admin can add any player in any role to a participant at any time', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $auction = createAuction();
    $participant = AuctionParticipant::query()->create([
        'auction_id' => $auction->id,
        'name' => 'Mario',
        'call_order' => 1,
    ]);

    // The player's role differs from the auction's current phase.
    $player = createPlayer(['role' => PlayerRole::Forward]);

    $response = $this->actingAs($admin)->post(
        route('auctions.assignments.store-manual', $auction),
        [
            'player_id' => $player->id,
            'auction_participant_id' => $participant->id,
            'price' => 25,
        ],
    );

    $response->assertRedirect();

    $this->assertDatabaseHas('player_assignments', [
        'auction_id' => $auction->id,
        'auction_participant_id' => $participant->id,
        'player_id' => $player->id,
        'price' => 25,
        'auction_call_id' => null,
    ]);
});

test('a non-admin cannot add a manual assignment', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $auction = createAuction();
    $participant = AuctionParticipant::query()->create([
        'auction_id' => $auction->id,
        'name' => 'Mario',
        'call_order' => 1,
    ]);
    $player = createPlayer();

    $response = $this->actingAs($user)->post(
        route('auctions.assignments.store-manual', $auction),
        [
            'player_id' => $player->id,
            'auction_participant_id' => $participant->id,
            'price' => 25,
        ],
    );

    $response->assertForbidden();
    $this->assertDatabaseCount('player_assignments', 0);
});

test('a player already assigned in the auction cannot be added again', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $auction = createAuction();
    $participant = AuctionParticipant::query()->create([
        'auction_id' => $auction->id,
        'name' => 'Mario',
        'call_order' => 1,
    ]);
    $player = createPlayer();

    PlayerAssignment::query()->create([
        'auction_id' => $auction->id,
        'auction_participant_id' => $participant->id,
        'player_id' => $player->id,
        'price' => 10,
    ]);

    $response = $this->actingAs($admin)->post(
        route('auctions.assignments.store-manual', $auction),
        [
            'player_id' => $player->id,
            'auction_participant_id' => $participant->id,
            'price' => 25,
        ],
    );

    $response->assertRedirect();
    $this->assertDatabaseCount('player_assignments', 1);
});

test('adding a player fails when the participant cannot afford it', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $auction = createAuction(['budget_per_participant' => 20]);
    $participant = AuctionParticipant::query()->create([
        'auction_id' => $auction->id,
        'name' => 'Mario',
        'call_order' => 1,
    ]);
    $player = createPlayer();

    $response = $this->actingAs($admin)->post(
        route('auctions.assignments.store-manual', $auction),
        [
            'player_id' => $player->id,
            'auction_participant_id' => $participant->id,
            'price' => 25,
        ],
    );

    $response->assertRedirect();
    $this->assertDatabaseCount('player_assignments', 0);
});
