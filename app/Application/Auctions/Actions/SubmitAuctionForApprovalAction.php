<?php

declare(strict_types=1);

namespace App\Application\Auctions\Actions;

use App\Domain\Auctions\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class SubmitAuctionForApprovalAction
{
    public function __construct(private TransitionAuctionAction $transition) {}

    public function execute(Auction $auction, User $seller): Auction
    {
        if ($auction->seller_id !== $seller->getKey() || ! $seller->hasPermission('auction.submit')) {
            throw new AuthorizationException;
        }

        return $this->transition->execute(
            auction: $auction,
            to: AuctionStatus::PendingApproval,
            actor: $seller,
        );
    }
}
