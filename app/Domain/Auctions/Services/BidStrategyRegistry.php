<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Services;

use App\Domain\Auctions\Contracts\BidStrategy;
use App\Domain\Auctions\Enums\AuctionType;
use App\Domain\Shared\Exceptions\BusinessRuleViolation;
use App\Models\Auction;

final readonly class BidStrategyRegistry
{
    public function __construct(
        private EnglishBidStrategy $english,
        private ReverseBidStrategy $reverse,
        private SealedBidStrategy $sealed,
    ) {}

    public function for(Auction $auction): BidStrategy
    {
        return match ($auction->type) {
            AuctionType::English, AuctionType::Hybrid => $this->english,
            AuctionType::Reverse => $this->reverse,
            AuctionType::SealedBid => $this->sealed,
            AuctionType::BuyNow, AuctionType::Dutch => throw new BusinessRuleViolation(
                'bid.purchase_only',
                (string) __('bid.purchase_only'),
            ),
        };
    }
}
