<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Contracts;

use App\Models\Auction;

interface BidStrategy
{
    public function assertAmountIsValid(Auction $auction, int $amountMinor): void;

    public function visiblePriceAfterBid(Auction $auction, int $amountMinor): int;

    public function supportsProxyBidding(): bool;
}
