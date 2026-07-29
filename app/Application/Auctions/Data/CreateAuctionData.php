<?php

declare(strict_types=1);

namespace App\Application\Auctions\Data;

use App\Domain\Auctions\Enums\AuctionType;
use Carbon\CarbonImmutable;

final readonly class CreateAuctionData
{
    public function __construct(
        public int $categoryId,
        public string $title,
        public string $description,
        public AuctionType $type,
        public string $currency,
        public int $startingPriceMinor,
        public int $minimumIncrementMinor,
        public ?int $reservePriceMinor,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public bool $isPrivate,
        public ?int $buyNowPriceMinor = null,
        public ?int $priceDecrementMinor = null,
        public ?int $priceDecrementIntervalSeconds = null,
        public bool $antiSnipingEnabled = true,
        public int $maxExtensions = 10,
    ) {}
}
