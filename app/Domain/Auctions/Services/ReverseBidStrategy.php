<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Services;

use App\Domain\Auctions\Contracts\BidStrategy;
use App\Domain\Shared\Exceptions\BusinessRuleViolation;
use App\Models\Auction;

final class ReverseBidStrategy implements BidStrategy
{
    public function assertAmountIsValid(Auction $auction, int $amountMinor): void
    {
        $maximum = $auction->current_price_minor - $auction->minimum_increment_minor;

        if ($amountMinor < 1 || $amountMinor > $maximum) {
            $key = 'bid.amount_too_high';

            throw new BusinessRuleViolation($key, (string) __($key));
        }
    }

    public function visiblePriceAfterBid(Auction $auction, int $amountMinor): int
    {
        return $amountMinor;
    }

    public function supportsProxyBidding(): bool
    {
        return false;
    }
}
