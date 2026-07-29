<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Services;

use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Shared\Exceptions\BusinessRuleViolation;

final class AuctionStateMachine
{
    /** @var array<string, list<AuctionStatus>> */
    private const TRANSITIONS = [
        AuctionStatus::Draft->value => [
            AuctionStatus::PendingApproval,
            AuctionStatus::Scheduled,
            AuctionStatus::Active,
        ],
        AuctionStatus::PendingApproval->value => [
            AuctionStatus::Scheduled,
            AuctionStatus::Active,
            AuctionStatus::Rejected,
        ],
        AuctionStatus::Scheduled->value => [
            AuctionStatus::Active,
            AuctionStatus::Cancelled,
        ],
        AuctionStatus::Active->value => [
            AuctionStatus::Completed,
            AuctionStatus::Cancelled,
            AuctionStatus::Suspended,
        ],
    ];

    public function assertCanTransition(AuctionStatus $from, AuctionStatus $to): void
    {
        if (! in_array($to, self::TRANSITIONS[$from->value] ?? [], true)) {
            throw new BusinessRuleViolation(
                translationKey: 'auction.invalid_transition',
                message: "Auction cannot transition from {$from->value} to {$to->value}.",
            );
        }
    }
}
