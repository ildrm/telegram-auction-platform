<?php

declare(strict_types=1);

namespace App\Application\Auctions\Actions;

use App\Domain\Auctions\Enums\AuctionStatus;
use App\Models\Auction;

final readonly class CloseDueAuctionsAction
{
    public function __construct(private CloseAuctionAction $closeAuction) {}

    public function execute(int $limit = 500): int
    {
        $closed = 0;
        $auctions = Auction::query()
            ->where('status', AuctionStatus::Active)
            ->where('ends_at', '<=', now())
            ->limit($limit)
            ->get();

        foreach ($auctions as $auction) {
            if ($this->closeAuction->execute($auction)->status === AuctionStatus::Completed) {
                $closed++;
            }
        }

        return $closed;
    }
}
