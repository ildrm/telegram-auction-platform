<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Services\AuctionStateMachine;
use App\Domain\Shared\Exceptions\BusinessRuleViolation;
use PHPUnit\Framework\TestCase;

final class AuctionStateMachineTest extends TestCase
{
    public function test_it_allows_a_draft_to_be_activated(): void
    {
        (new AuctionStateMachine)->assertCanTransition(
            AuctionStatus::Draft,
            AuctionStatus::Active,
        );

        self::assertTrue(true);
    }

    public function test_it_rejects_a_completed_auction_reactivation(): void
    {
        $this->expectException(BusinessRuleViolation::class);

        (new AuctionStateMachine)->assertCanTransition(
            AuctionStatus::Completed,
            AuctionStatus::Active,
        );
    }
}
