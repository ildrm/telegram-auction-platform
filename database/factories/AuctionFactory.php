<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Enums\AuctionType;
use App\Models\Auction;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Auction> */
final class AuctionFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'seller_id' => User::factory(),
            'category_id' => Category::factory(),
            'winner_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(8)),
            'description' => fake()->paragraphs(2, true),
            'type' => AuctionType::English,
            'status' => AuctionStatus::Draft,
            'currency' => 'USD',
            'starting_price_minor' => 10_000,
            'current_price_minor' => 10_000,
            'minimum_increment_minor' => 500,
            'reserve_price_minor' => null,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
            'is_private' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => AuctionStatus::Active,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
        ]);
    }
}
