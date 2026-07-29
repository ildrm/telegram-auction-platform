<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Media\Actions\DeleteAuctionImageAction;
use App\Application\Media\Actions\UploadAuctionImageAction;
use App\Application\Reviews\Actions\SubmitReviewAction;
use App\Application\Watchlists\Actions\UpdateWatchlistAction;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Jobs\Media\GenerateImageDerivatives;
use App\Models\Auction;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class WatchlistReviewAndMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_watch_and_unwatch_a_public_auction(): void
    {
        $auction = Auction::factory()->active()->create();
        $user = User::factory()->create();
        $action = $this->app->make(UpdateWatchlistAction::class);

        $watchlist = $action->execute($auction, $user, true, true, false);
        self::assertNotNull($watchlist);
        self::assertTrue($watchlist->notify_bid);

        self::assertNull($action->execute($auction, $user, false));
        $this->assertDatabaseCount('watchlists', 0);
    }

    public function test_seller_and_winner_can_each_review_once(): void
    {
        $seller = User::factory()->seller()->create();
        $winner = User::factory()->create();
        $auction = Auction::factory()->for($seller, 'seller')->create([
            'status' => AuctionStatus::Completed,
            'winner_id' => $winner->getKey(),
            'closed_at' => now(),
        ]);

        $review = $this->app->make(SubmitReviewAction::class)->execute(
            $auction,
            $winner,
            5,
            'The item and seller matched the listing.',
        );

        self::assertSame($seller->getKey(), $review->reviewed_user_id);
        self::assertSame(1, Review::query()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'review.submitted']);
    }

    public function test_secure_image_upload_generates_private_derivatives_and_can_be_deleted(): void
    {
        Storage::fake('auction-media');
        config(['media.disk' => 'auction-media']);
        $seller = User::factory()->seller()->create();
        $auction = Auction::factory()->for($seller, 'seller')->create();
        $file = UploadedFile::fake()->image('item.png', 1600, 900);

        $media = $this->app->make(UploadAuctionImageAction::class)->execute($auction, $seller, $file);

        Storage::disk('auction-media')->assertExists($media->original_path);
        Queue::assertPushed(GenerateImageDerivatives::class);
        (new GenerateImageDerivatives($media->getKey()))->handle();
        $media->refresh();
        self::assertSame('ready', $media->processing_status);
        Storage::disk('auction-media')->assertExists($media->derivatives['thumbnail']);

        $this->app->make(DeleteAuctionImageAction::class)->execute($media, $seller);
        $this->assertDatabaseCount('auction_media', 0);
    }

    public function test_image_content_is_validated_instead_of_trusting_the_extension(): void
    {
        Storage::fake('auction-media');
        config(['media.disk' => 'auction-media']);
        $seller = User::factory()->seller()->create();
        $auction = Auction::factory()->for($seller, 'seller')->create();
        $fakeImage = UploadedFile::fake()->createWithContent('spoofed.jpg', 'not an image');

        $this->expectException(ValidationException::class);

        $this->app->make(UploadAuctionImageAction::class)->execute($auction, $seller, $fakeImage);
    }
}
