<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Services;

use App\Domain\Shared\Exceptions\BusinessRuleViolation;
use App\Models\Auction;
use Carbon\CarbonInterface;

final class DutchPriceCalculator
{
    public function currentPrice(Auction $auction, ?CarbonInterface $at = null): int
    {
        if ($auction->price_decrement_minor === null || $auction->price_decrement_interval_seconds === null) {
            $key = 'bid.purchase_unavailable';

            throw new BusinessRuleViolation($key, (string) __($key));
        }

        $elapsed = max(0, $auction->starts_at->diffInSeconds($at ?? now(), false));
        $steps = intdiv((int) $elapsed, $auction->price_decrement_interval_seconds);

        return max(1, $auction->starting_price_minor - ($steps * $auction->price_decrement_minor));
    }
}
