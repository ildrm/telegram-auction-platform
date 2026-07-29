<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Users\Enums\UserStatus;
use App\Models\Auction;
use App\Models\User;

final class AuctionPolicy
{
    public function view(?User $user, Auction $auction): bool
    {
        if (! $auction->is_private && in_array(
            $auction->status,
            [AuctionStatus::Scheduled, AuctionStatus::Active, AuctionStatus::Completed],
            true,
        )) {
            return true;
        }

        return $user !== null && $auction->seller_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->status === UserStatus::Active
            && $user->hasPermission('auction.create');
    }

    public function update(User $user, Auction $auction): bool
    {
        return $user->status === UserStatus::Active
            && $auction->seller_id === $user->getKey()
            && $auction->status === AuctionStatus::Draft
            && $user->hasPermission('auction.update');
    }
}
