<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Auctions\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuctionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_public_auctions_are_discoverable(): void
    {
        $active = Auction::factory()->active()->create();
        Auction::factory()->create();

        $response = $this->getJson('/api/v1/auctions');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->getKey());
    }

    public function test_reserve_price_is_hidden_from_public_until_close(): void
    {
        $auction = Auction::factory()->active()->create(['reserve_price_minor' => 50_000]);

        $this->getJson("/api/v1/auctions/{$auction->slug}")
            ->assertOk()
            ->assertJsonMissingPath('data.reserve_price_minor');

        $auction->update(['status' => AuctionStatus::Completed, 'closed_at' => now()]);

        $this->getJson("/api/v1/auctions/{$auction->slug}")
            ->assertOk()
            ->assertJsonPath('data.reserve_price_minor', 50_000);
    }

    public function test_private_auction_is_not_discoverable_to_an_unrelated_user(): void
    {
        $auction = Auction::factory()->active()->create(['is_private' => true]);

        $this->actingAs(User::factory()->create())
            ->getJson("/api/v1/auctions/{$auction->slug}")
            ->assertForbidden();
    }
}
