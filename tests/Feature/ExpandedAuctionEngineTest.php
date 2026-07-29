<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Auctions\Actions\CloseAuctionAction;
use App\Application\Auctions\Actions\CloseDueAuctionsAction;
use App\Application\Auctions\Actions\PurchaseAuctionAction;
use App\Application\Auctions\Actions\StartDueAuctionsAction;
use App\Application\Bids\Actions\PlaceBidAction;
use App\Application\Bids\Data\PlaceBidData;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Enums\AuctionType;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ExpandedAuctionEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_proxy_bid_automatically_defends_the_existing_leader(): void
    {
        $auction = Auction::factory()->active()->create();
        $leader = User::factory()->create();
        $challenger = User::factory()->create();
        $place = $this->app->make(PlaceBidAction::class);

        $place->execute($auction, $leader, new PlaceBidData(10_500, 'USD', 20_000));
        $place->execute($auction->refresh(), $challenger, new PlaceBidData(11_000, 'USD', 12_000));

        self::assertSame(12_500, $auction->refresh()->current_price_minor);
        $this->assertDatabaseHas('bids', [
            'bidder_id' => $leader->getKey(),
            'amount_minor' => 12_500,
            'is_automatic' => true,
        ]);
    }

    public function test_bid_in_anti_sniping_window_extends_end_time(): void
    {
        $originalEnd = now()->addSeconds(20);
        $auction = Auction::factory()->active()->create(['ends_at' => $originalEnd]);

        $this->app->make(PlaceBidAction::class)->execute(
            $auction,
            User::factory()->create(),
            new PlaceBidData(10_500, 'USD'),
        );

        $auction->refresh();
        self::assertSame(1, $auction->extension_count);
        self::assertTrue($auction->ends_at->greaterThan($originalEnd));
    }

    public function test_reverse_auction_accepts_lower_offers_and_lowest_offer_wins(): void
    {
        $auction = Auction::factory()->active()->create([
            'type' => AuctionType::Reverse,
            'starting_price_minor' => 20_000,
            'current_price_minor' => 20_000,
            'minimum_increment_minor' => 500,
            'reserve_price_minor' => 18_000,
        ]);
        $first = User::factory()->create();
        $second = User::factory()->create();
        $place = $this->app->make(PlaceBidAction::class);
        $place->execute($auction, $first, new PlaceBidData(19_000, 'USD'));
        $place->execute($auction->refresh(), $second, new PlaceBidData(17_500, 'USD'));
        $auction->update(['ends_at' => now()->subSecond()]);

        $closed = $this->app->make(CloseAuctionAction::class)->execute($auction);

        self::assertSame($second->getKey(), $closed->winner_id);
        self::assertSame(AuctionStatus::Completed, $closed->status);
    }

    public function test_sealed_bid_does_not_reveal_the_leading_price_before_close(): void
    {
        $auction = Auction::factory()->active()->create(['type' => AuctionType::SealedBid]);
        $place = $this->app->make(PlaceBidAction::class);
        $first = User::factory()->create();
        $second = User::factory()->create();
        $place->execute($auction, $first, new PlaceBidData(15_000, 'USD'));
        $place->execute($auction->refresh(), $second, new PlaceBidData(17_000, 'USD'));

        self::assertSame(10_000, $auction->refresh()->current_price_minor);
        $auction->update(['ends_at' => now()->subSecond()]);
        self::assertSame($second->getKey(), $this->app->make(CloseAuctionAction::class)->execute($auction)->winner_id);
    }

    public function test_buy_now_atomically_completes_the_auction(): void
    {
        $auction = Auction::factory()->active()->create([
            'type' => AuctionType::Hybrid,
            'buy_now_price_minor' => 25_000,
        ]);
        $buyer = User::factory()->create();

        $purchased = $this->app->make(PurchaseAuctionAction::class)->execute($auction, $buyer);

        self::assertSame(AuctionStatus::Completed, $purchased->status);
        self::assertSame($buyer->getKey(), $purchased->winner_id);
        self::assertSame(25_000, $purchased->current_price_minor);
        self::assertSame(1, Bid::query()->count());
    }

    public function test_dutch_purchase_uses_elapsed_price_decrements(): void
    {
        $auction = Auction::factory()->active()->create([
            'type' => AuctionType::Dutch,
            'starting_price_minor' => 20_000,
            'current_price_minor' => 20_000,
            'price_decrement_minor' => 1_000,
            'price_decrement_interval_seconds' => 60,
            'starts_at' => now()->subMinutes(3),
        ]);

        $purchased = $this->app->make(PurchaseAuctionAction::class)->execute(
            $auction,
            User::factory()->create(),
        );

        self::assertSame(17_000, $purchased->current_price_minor);
    }

    public function test_scheduler_actions_start_and_close_due_auctions(): void
    {
        $scheduled = Auction::factory()->create([
            'status' => AuctionStatus::Scheduled,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);
        $expired = Auction::factory()->active()->create(['ends_at' => now()->subSecond()]);

        self::assertSame(1, $this->app->make(StartDueAuctionsAction::class)->execute());
        self::assertSame(1, $this->app->make(CloseDueAuctionsAction::class)->execute());
        self::assertSame(AuctionStatus::Active, $scheduled->refresh()->status);
        self::assertSame(AuctionStatus::Completed, $expired->refresh()->status);
    }
}
