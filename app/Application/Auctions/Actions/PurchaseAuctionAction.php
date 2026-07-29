<?php

declare(strict_types=1);

namespace App\Application\Auctions\Actions;

use App\Application\Audit\AuditLogger;
use App\Application\Notifications\NotificationService;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Enums\AuctionType;
use App\Domain\Auctions\Services\DutchPriceCalculator;
use App\Domain\Shared\Exceptions\BusinessRuleViolation;
use App\Domain\Users\Enums\UserStatus;
use App\Models\Auction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class PurchaseAuctionAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private NotificationService $notifications,
        private DutchPriceCalculator $dutchPrices,
    ) {}

    public function execute(Auction $auction, User $buyer): Auction
    {
        return DB::transaction(function () use ($auction, $buyer): Auction {
            /** @var Auction $locked */
            $locked = Auction::query()->lockForUpdate()->findOrFail($auction->getKey());
            $this->assertCanPurchase($locked, $buyer);
            $price = $this->purchasePrice($locked);

            $bid = $locked->bids()->create([
                'bidder_id' => $buyer->getKey(),
                'currency' => $locked->currency,
                'amount_minor' => $price,
                'maximum_bid_minor' => $price,
                'is_automatic' => false,
                'placed_at' => now(),
            ]);

            $before = ['status' => $locked->status->value, 'current_price_minor' => $locked->current_price_minor];
            $locked->update([
                'winner_id' => $buyer->getKey(),
                'current_price_minor' => $price,
                'status' => AuctionStatus::Completed,
                'closed_at' => now(),
            ]);

            $this->auditLogger->record(
                actor: $buyer,
                action: 'auction.purchased',
                subject: $locked,
                before: $before,
                after: ['winner_id' => $buyer->getKey(), 'price_minor' => $price, 'bid_id' => $bid->getKey()],
                metadata: null,
            );
            $this->notifyPurchase($locked, $buyer);

            return $locked;
        }, 3);
    }

    private function assertCanPurchase(Auction $auction, User $buyer): void
    {
        if ($buyer->status !== UserStatus::Active || ! $buyer->hasPermission('bid.place')) {
            $this->fail('bid.inactive_user');
        }

        if ($auction->is_private
            || $auction->status !== AuctionStatus::Active
            || now()->lessThan($auction->starts_at)
            || ! now()->lessThan($auction->ends_at)) {
            $this->fail('bid.auction_not_active');
        }

        if ($auction->seller_id === $buyer->getKey()) {
            $this->fail('bid.self_bid');
        }

        if (! in_array($auction->type, [AuctionType::BuyNow, AuctionType::Hybrid, AuctionType::Dutch], true)) {
            $this->fail('bid.purchase_unavailable');
        }
    }

    private function purchasePrice(Auction $auction): int
    {
        if ($auction->type !== AuctionType::Dutch) {
            if ($auction->buy_now_price_minor === null) {
                $this->fail('bid.purchase_unavailable');
            }

            return $auction->buy_now_price_minor;
        }

        return $this->dutchPrices->currentPrice($auction);
    }

    private function notifyPurchase(Auction $auction, User $buyer): void
    {
        foreach ([$auction->seller()->firstOrFail(), $buyer] as $recipient) {
            $this->notifications->send(
                $recipient,
                'auction.purchased',
                (string) __('notification.purchase_title'),
                (string) __('notification.purchase_message', ['auction' => $auction->title]),
                ['auction_id' => $auction->getKey()],
            );
        }
    }

    private function fail(string $key): never
    {
        throw new BusinessRuleViolation($key, (string) __($key));
    }
}
