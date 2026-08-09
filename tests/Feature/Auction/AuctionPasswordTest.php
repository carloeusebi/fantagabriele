<?php

use App\Models\Auction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('creating an auction with a password stores it hashed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('auctions.store'), [
        'name' => 'Asta con password',
        'password' => 'secret123',
        'budget_per_participant' => 500,
        'slots_goalkeeper' => 3,
        'slots_defender' => 8,
        'slots_midfielder' => 8,
        'slots_forward' => 6,
        'participants' => [
            ['name' => 'Alice', 'is_agent' => true],
            ['name' => 'Bob', 'is_agent' => false],
        ],
    ])->assertRedirect();

    $auction = Auction::query()->latest('id')->firstOrFail();

    expect($auction->hasPassword())->toBeTrue();
    expect($auction->password)->not->toBe('secret123');
    expect(Hash::check('secret123', $auction->password))->toBeTrue();
});

test('creating an auction without a password leaves it unprotected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('auctions.store'), [
        'name' => 'Asta senza password',
        'budget_per_participant' => 500,
        'slots_goalkeeper' => 3,
        'slots_defender' => 8,
        'slots_midfielder' => 8,
        'slots_forward' => 6,
        'participants' => [
            ['name' => 'Alice', 'is_agent' => true],
            ['name' => 'Bob', 'is_agent' => false],
        ],
    ])->assertRedirect();

    $auction = Auction::query()->latest('id')->firstOrFail();

    expect($auction->hasPassword())->toBeFalse();
});

test('a password protected auction is locked for other users until unlocked', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $auction = Auction::factory()->for($owner)->create([
        'password' => Hash::make('secret123'),
    ]);

    $this->actingAs($other)
        ->get(route('auctions.show', $auction))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auctions/show')
            ->where('locked', true)
            ->where('auction.id', $auction->id)
            ->missing('participants')
        );
});

test('the owner can access a password protected auction without unlocking it', function () {
    $owner = User::factory()->create();

    $auction = Auction::factory()->for($owner)->create([
        'password' => Hash::make('secret123'),
    ]);

    $this->actingAs($owner)
        ->get(route('auctions.show', $auction))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auctions/show')
            ->where('locked', false)
        );
});

test('an admin can access a password protected auction without unlocking it', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $auction = Auction::factory()->for($owner)->create([
        'password' => Hash::make('secret123'),
    ]);

    $this->actingAs($admin)
        ->get(route('auctions.show', $auction))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auctions/show')
            ->where('locked', false)
        );
});

test('an incorrect password does not unlock the auction', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $auction = Auction::factory()->for($owner)->create([
        'password' => Hash::make('secret123'),
    ]);

    $this->actingAs($other)
        ->post(route('auctions.unlock', $auction), ['password' => 'wrong-password'])
        ->assertRedirect();

    $this->actingAs($other)
        ->get(route('auctions.show', $auction))
        ->assertInertia(fn (Assert $page) => $page
            ->component('auctions/show')
            ->where('locked', true)
        );
});

test('the correct password unlocks the auction for the current session', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $auction = Auction::factory()->for($owner)->create([
        'password' => Hash::make('secret123'),
    ]);

    $this->actingAs($other)
        ->post(route('auctions.unlock', $auction), ['password' => 'secret123'])
        ->assertRedirect(route('auctions.show', $auction));

    $this->actingAs($other)
        ->get(route('auctions.show', $auction))
        ->assertInertia(fn (Assert $page) => $page
            ->component('auctions/show')
            ->where('locked', false)
        );
});
