<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Services;

use App\Domain\Auctions\Contracts\BidStrategy;
use App\Domain\Shared\Exceptions\BusinessRuleViolation;
use App\Models\Auction;

final class SealedBidStrategy implements BidStrategy
{
    public function assertAmountIsValid(Auction $auction, int $amountMinor): void
    {
        if ($amountMinor < $auction->starting_price_minor) {
            $key = 'bid.amount_too_low';

            throw new BusinessRuleViolation($key, (string) __($key));
        }
    }

    public function visiblePriceAfterBid(Auction $auction, int $amountMinor): int
    {
        return $auction->current_price_minor;
    }

    public function supportsProxyBidding(): bool
    {
        return false;
    }
}
