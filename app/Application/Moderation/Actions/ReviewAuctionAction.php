<?php

declare(strict_types=1);

namespace App\Application\Moderation\Actions;

use App\Application\Audit\AuditLogger;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Services\AuctionStateMachine;
use App\Domain\Moderation\Enums\ModerationDecision;
use App\Models\Auction;
use App\Models\AuctionModeration;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ReviewAuctionAction
{
    public function __construct(
        private AuctionStateMachine $stateMachine,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(
        Auction $auction,
        User $moderator,
        ModerationDecision $decision,
        ?string $reason,
    ): Auction {
        if (! $moderator->hasPermission('auction.approve')) {
            throw new AuthorizationException;
        }

        if ($decision === ModerationDecision::Rejected && trim((string) $reason) === '') {
            throw ValidationException::withMessages([
                'reason' => [__('moderation.rejection_reason_required')],
            ]);
        }

        return DB::transaction(function () use ($auction, $moderator, $decision, $reason): Auction {
            /** @var Auction $locked */
            $locked = Auction::query()->lockForUpdate()->findOrFail($auction->getKey());
            $target = $decision === ModerationDecision::Rejected
                ? AuctionStatus::Rejected
                : ($locked->starts_at->isFuture() ? AuctionStatus::Scheduled : AuctionStatus::Active);

            $this->stateMachine->assertCanTransition($locked->status, $target);
            $before = ['status' => $locked->status->value];

            $locked->update([
                'status' => $target,
                'approved_by' => $decision === ModerationDecision::Approved ? $moderator->getKey() : null,
                'approved_at' => $decision === ModerationDecision::Approved ? now() : null,
                'rejection_reason' => $decision === ModerationDecision::Rejected ? trim((string) $reason) : null,
            ]);

            AuctionModeration::query()->create([
                'auction_id' => $locked->getKey(),
                'moderator_id' => $moderator->getKey(),
                'decision' => $decision,
                'reason' => $reason,
            ]);

            $this->auditLogger->record(
                actor: $moderator,
                action: "auction.{$decision->value}",
                subject: $locked,
                before: $before,
                after: ['status' => $target->value],
                metadata: ['reason' => $reason],
            );

            return $locked->refresh();
        });
    }
}
