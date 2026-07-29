<?php

declare(strict_types=1);

namespace App\Domain\Bids\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class BidPlaced implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $bidId,
        public int $auctionId,
        public int $bidderId,
    ) {}
}
