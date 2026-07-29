<?php

declare(strict_types=1);

namespace App\Application\Reviews\Actions;

use App\Application\Audit\AuditLogger;
use App\Application\Settings\SettingService;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Reviews\Enums\ReviewStatus;
use App\Domain\Shared\Exceptions\BusinessRuleViolation;
use App\Models\Auction;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class SubmitReviewAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private SettingService $settings,
    ) {}

    public function execute(Auction $auction, User $reviewer, int $rating, ?string $comment): Review
    {
        if ($rating < 1 || $rating > 5) {
            $this->fail('review.invalid_rating');
        }

        if ($auction->status !== AuctionStatus::Completed || $auction->winner_id === null) {
            $this->fail('review.auction_ineligible');
        }

        $participantIds = [$auction->seller_id, $auction->winner_id];

        if (! in_array($reviewer->getKey(), $participantIds, true)) {
            $this->fail('review.not_participant');
        }

        $windowDays = (int) $this->settings->get('auction.review_window_days', 30);

        if ($auction->closed_at === null || now()->greaterThan($auction->closed_at->addDays($windowDays))) {
            $this->fail('review.window_closed');
        }

        $reviewedUserId = $reviewer->getKey() === $auction->seller_id
            ? $auction->winner_id
            : $auction->seller_id;

        return DB::transaction(function () use ($auction, $reviewer, $reviewedUserId, $rating, $comment): Review {
            if (Review::query()->whereBelongsTo($auction)->where('reviewer_id', $reviewer->getKey())->exists()) {
                $this->fail('review.already_submitted');
            }

            $review = Review::query()->create([
                'auction_id' => $auction->getKey(),
                'reviewer_id' => $reviewer->getKey(),
                'reviewed_user_id' => $reviewedUserId,
                'rating' => $rating,
                'comment' => $comment,
                'status' => ReviewStatus::Published,
            ]);
            $this->auditLogger->record(
                $reviewer,
                'review.submitted',
                $review,
                null,
                ['auction_id' => $auction->getKey(), 'rating' => $rating],
                null,
            );

            return $review;
        }, 3);
    }

    private function fail(string $key): never
    {
        throw new BusinessRuleViolation($key, (string) __($key));
    }
}
