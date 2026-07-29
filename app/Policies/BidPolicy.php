<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Users\Enums\UserStatus;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;

final class BidPolicy
{
    public function create(User $user, Auction $auction): bool
    {
        return $user->status === UserStatus::Active
            && $auction->seller_id !== $user->getKey()
            && ! $auction->is_private
            && $user->hasPermission('bid.place');
    }

    public function view(User $user, Bid $bid): bool
    {
        return $bid->bidder_id === $user->getKey()
            || $bid->auction()->where('seller_id', $user->getKey())->exists();
    }
}
