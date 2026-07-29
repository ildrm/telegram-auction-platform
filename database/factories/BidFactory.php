<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Bid> */
final class BidFactory extends Factory
{
    public function definition(): array
    {
        return [
            'auction_id' => Auction::factory()->active(),
            'bidder_id' => User::factory(),
            'currency' => 'USD',
            'amount_minor' => 10_500,
            'placed_at' => now(),
        ];
    }
}
