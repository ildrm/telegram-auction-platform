<?php

declare(strict_types=1);

namespace App\Application\Bids\Services;

use App\Models\Auction;
use App\Models\Bid;

final class ProxyBidResolver
{
    /** @return array{price: int, winner_id: int, automatic_amount: ?int} */
    public function resolve(Auction $auction, Bid $incoming, int $previousPrice): array
    {
        $limits = $auction->bids()
            ->where('is_automatic', false)
            ->get(['bidder_id', 'amount_minor', 'maximum_bid_minor'])
            ->groupBy('bidder_id')
            ->map(fn ($bids): int => (int) $bids->max(
                fn (Bid $bid): int => max($bid->amount_minor, $bid->maximum_bid_minor ?? 0),
            ))
            ->sortDesc();

        $winnerId = (int) $limits->keys()->first();
        $highestLimit = (int) $limits->first();

        if ($limits->count() === 1) {
            return [
                'price' => $incoming->amount_minor,
                'winner_id' => $winnerId,
                'automatic_amount' => null,
            ];
        }

        $runnerUpLimit = (int) $limits->values()->get(1);
        $price = min(
            $highestLimit,
            max($previousPrice + $auction->minimum_increment_minor, $runnerUpLimit + $auction->minimum_increment_minor),
        );

        if ($winnerId === $incoming->bidder_id) {
            $price = max($price, $incoming->amount_minor);
        }

        return [
            'price' => $price,
            'winner_id' => $winnerId,
            'automatic_amount' => $winnerId !== $incoming->bidder_id && $price > $previousPrice ? $price : null,
        ];
    }
}
