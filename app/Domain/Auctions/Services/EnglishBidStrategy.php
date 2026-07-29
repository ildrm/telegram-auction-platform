<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Services;

use App\Domain\Auctions\Contracts\BidStrategy;
use App\Domain\Shared\Exceptions\BusinessRuleViolation;
use App\Models\Auction;

final class EnglishBidStrategy implements BidStrategy
{
    public function assertAmountIsValid(Auction $auction, int $amountMinor): void
    {
        if ($amountMinor < $auction->current_price_minor + $auction->minimum_increment_minor) {
            $this->fail('bid.amount_too_low');
        }
    }

    public function visiblePriceAfterBid(Auction $auction, int $amountMinor): int
    {
        return $amountMinor;
    }

    public function supportsProxyBidding(): bool
    {
        return true;
    }

    private function fail(string $key): never
    {
        throw new BusinessRuleViolation($key, (string) __($key));
    }
}
