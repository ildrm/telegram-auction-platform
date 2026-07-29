<?php

declare(strict_types=1);

namespace App\Application\Bids\Data;

final readonly class PlaceBidData
{
    public function __construct(
        public int $amountMinor,
        public string $currency,
        public ?int $maximumBidMinor = null,
    ) {}
}
