<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Enums;

enum AuctionType: string
{
    case English = 'english';
    case Hybrid = 'hybrid';
    case BuyNow = 'buy_now';
    case Dutch = 'dutch';
    case Reverse = 'reverse';
    case SealedBid = 'sealed_bid';
}
