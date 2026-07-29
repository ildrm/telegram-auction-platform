<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Bids\Actions\PlaceBidAction;
use App\Application\Bids\Data\PlaceBidData;
use App\Domain\Bids\Events\BidPlaced;
use App\Domain\Shared\Exceptions\BusinessRuleViolation;
use App\Models\Auction;
use App\Models\AuditLog;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class PlaceBidActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_places_a_bid_and_updates_price_atomically(): void
    {
        Event::fake([BidPlaced::class]);
        $auction = Auction::factory()->active()->create();
        $bidder = User::factory()->create();

        $bid = $this->app->make(PlaceBidAction::class)->execute(
            auction: $auction,
            bidder: $bidder,
            data: new PlaceBidData(amountMinor: 10_500, currency: 'USD'),
        );

        self::assertSame(10_500, $bid->amount_minor);
        self::assertSame(10_500, $auction->refresh()->current_price_minor);
        self::assertSame(1, Bid::query()->count());
        self::assertSame(1, AuditLog::query()->where('action', 'bid.placed')->count());
        Event::assertDispatched(BidPlaced::class);
    }

    public function test_it_rejects_a_bid_below_the_minimum_increment(): void
    {
        $auction = Auction::factory()->active()->create();
        $bidder = User::factory()->create();

        try {
            $this->app->make(PlaceBidAction::class)->execute(
                auction: $auction,
                bidder: $bidder,
                data: new PlaceBidData(amountMinor: 10_499, currency: 'USD'),
            );
            self::fail('Expected the bid to be rejected.');
        } catch (BusinessRuleViolation $exception) {
            self::assertSame('bid.amount_too_low', $exception->translationKey);
        }

        self::assertSame(0, Bid::query()->count());
        self::assertSame(10_000, $auction->refresh()->current_price_minor);
        self::assertSame(0, AuditLog::query()->count());
    }

    public function test_it_rejects_self_bidding(): void
    {
        $seller = User::factory()->create();
        $auction = Auction::factory()->for($seller, 'seller')->active()->create();

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $this->app->make(PlaceBidAction::class)->execute(
            auction: $auction,
            bidder: $seller,
            data: new PlaceBidData(amountMinor: 10_500, currency: 'USD'),
        );
    }

    public function test_it_rejects_a_different_currency_without_writing_data(): void
    {
        $auction = Auction::factory()->active()->create();
        $bidder = User::factory()->create();

        try {
            $this->app->make(PlaceBidAction::class)->execute(
                auction: $auction,
                bidder: $bidder,
                data: new PlaceBidData(amountMinor: 10_500, currency: 'EUR'),
            );
            self::fail('Expected the currency to be rejected.');
        } catch (BusinessRuleViolation $exception) {
            self::assertSame('bid.currency_mismatch', $exception->translationKey);
        }

        self::assertSame(0, Bid::query()->count());
        self::assertSame(0, AuditLog::query()->count());
    }
}
