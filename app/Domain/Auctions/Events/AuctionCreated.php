<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class AuctionCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $auctionId,
        public int $sellerId,
    ) {}
}
