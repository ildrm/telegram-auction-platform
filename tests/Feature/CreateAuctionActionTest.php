<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Auctions\Actions\CreateAuctionAction;
use App\Application\Auctions\Data\CreateAuctionData;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Enums\AuctionType;
use App\Domain\Auctions\Events\AuctionCreated;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class CreateAuctionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_audited_draft(): void
    {
        Event::fake([AuctionCreated::class]);
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $auction = $this->app->make(CreateAuctionAction::class)->execute(
            seller: $seller,
            data: new CreateAuctionData(
                categoryId: $category->getKey(),
                title: 'Rare Mechanical Watch',
                description: 'A documented mechanical watch in excellent condition.',
                type: AuctionType::English,
                currency: 'USD',
                startingPriceMinor: 25_000,
                minimumIncrementMinor: 1_000,
                reservePriceMinor: 40_000,
                startsAt: CarbonImmutable::now()->addHour(),
                endsAt: CarbonImmutable::now()->addDays(2),
                isPrivate: false,
            ),
        );

        self::assertSame(AuctionStatus::Draft, $auction->status);
        self::assertSame(25_000, $auction->current_price_minor);
        self::assertStringStartsWith('rare-mechanical-watch-', $auction->slug);
        self::assertSame(1, AuditLog::query()->where('action', 'auction.created')->count());
        Event::assertDispatched(AuctionCreated::class);
    }
}
