<?php

namespace Database\Factories;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Auction>
 */
class AuctionFactory extends Factory
{
    protected $model = Auction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'password' => null,
            'status' => AuctionStatus::Setup,
            'budget_per_participant' => 500,
            'slots_goalkeeper' => 3,
            'slots_defender' => 8,
            'slots_midfielder' => 8,
            'slots_forward' => 6,
        ];
    }
}
