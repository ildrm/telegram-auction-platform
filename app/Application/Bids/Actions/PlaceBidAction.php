<?php

declare(strict_types=1);

namespace App\Application\Bids\Actions;

use App\Application\Auctions\Services\AntiSnipingService;
use App\Application\Audit\AuditLogger;
use App\Application\Bids\Data\PlaceBidData;
use App\Application\Bids\Services\ProxyBidResolver;
use App\Application\Notifications\NotificationService;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Enums\AuctionType;
use App\Domain\Auctions\Services\BidStrategyRegistry;
use App\Domain\Bids\Events\BidPlaced;
use App\Domain\Shared\Exceptions\BusinessRuleViolation;
use App\Domain\Users\Enums\UserStatus;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final readonly class PlaceBidAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private BidStrategyRegistry $strategies,
        private ProxyBidResolver $proxyBids,
        private AntiSnipingService $antiSniping,
        private NotificationService $notifications,
    ) {}

    public function execute(Auction $auction, User $bidder, PlaceBidData $data): Bid
    {
        if (Gate::forUser($bidder)->denies('create', [Bid::class, $auction])) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($auction, $bidder, $data): Bid {
            /** @var Auction $locked */
            $locked = Auction::query()->lockForUpdate()->findOrFail($auction->getKey());
            $this->assertCommonRules($locked, $bidder, $data);

            $strategy = $this->strategies->for($locked);
            $strategy->assertAmountIsValid($locked, $data->amountMinor);

            $maximum = $data->maximumBidMinor ?? $data->amountMinor;

            if ($maximum < $data->amountMinor || (! $strategy->supportsProxyBidding() && $maximum !== $data->amountMinor)) {
                $this->fail('bid.invalid_maximum');
            }

            $previousPrice = $locked->current_price_minor;
            $previousLeaderId = $this->currentLeaderId($locked);
            $bid = $locked->bids()->create([
                'bidder_id' => $bidder->getKey(),
                'currency' => $data->currency,
                'amount_minor' => $data->amountMinor,
                'maximum_bid_minor' => $maximum,
                'is_automatic' => false,
                'placed_at' => now(),
            ]);

            $visiblePrice = $strategy->visiblePriceAfterBid($locked, $data->amountMinor);
            $newLeaderId = $locked->type === AuctionType::SealedBid ? null : $bidder->getKey();

            if ($strategy->supportsProxyBidding()) {
                $resolution = $this->proxyBids->resolve($locked, $bid, $previousPrice);
                $visiblePrice = $resolution['price'];
                $newLeaderId = $resolution['winner_id'];

                if ($resolution['automatic_amount'] !== null) {
                    $locked->bids()->create([
                        'bidder_id' => $resolution['winner_id'],
                        'currency' => $locked->currency,
                        'amount_minor' => $resolution['automatic_amount'],
                        'maximum_bid_minor' => null,
                        'is_automatic' => true,
                        'placed_at' => now(),
                    ]);
                }
            }

            $locked->current_price_minor = $visiblePrice;
            $extended = $this->antiSniping->extendWhenNecessary($locked);
            $locked->save();

            $this->auditLogger->record(
                actor: $bidder,
                action: 'bid.placed',
                subject: $bid,
                before: null,
                after: [
                    'auction_id' => $locked->getKey(),
                    'amount_minor' => $data->amountMinor,
                    'currency' => $data->currency,
                ],
                metadata: [
                    'previous_price_minor' => $previousPrice,
                    'visible_price_minor' => $visiblePrice,
                    'extended' => $extended,
                ],
            );

            $this->notifyParticipants($locked, $bidder, $previousLeaderId, $newLeaderId);
            BidPlaced::dispatch($bid->getKey(), $locked->getKey(), $bidder->getKey());

            return $bid;
        }, 3);
    }

    private function assertCommonRules(Auction $auction, User $bidder, PlaceBidData $data): void
    {
        if ($bidder->status !== UserStatus::Active) {
            $this->fail('bid.inactive_user');
        }

        if ($auction->status !== AuctionStatus::Active) {
            $this->fail('bid.auction_not_active');
        }

        if (now()->lessThan($auction->starts_at) || ! now()->lessThan($auction->ends_at)) {
            $this->fail('bid.outside_bidding_window');
        }

        if ($auction->seller_id === $bidder->getKey()) {
            $this->fail('bid.self_bid');
        }

        if ($data->currency !== $auction->currency) {
            $this->fail('bid.currency_mismatch');
        }
    }

    private function currentLeaderId(Auction $auction): ?int
    {
        if ($auction->type === AuctionType::SealedBid) {
            return null;
        }

        $query = $auction->bids()->where('is_automatic', false);

        if ($auction->type === AuctionType::Reverse) {
            return $query->orderBy('amount_minor')->value('bidder_id');
        }

        return $query->orderByDesc(DB::raw('COALESCE(maximum_bid_minor, amount_minor)'))->value('bidder_id');
    }

    private function notifyParticipants(
        Auction $auction,
        User $bidder,
        ?int $previousLeaderId,
        ?int $newLeaderId,
    ): void {
        $this->notifications->send(
            $auction->seller()->firstOrFail(),
            'bid.placed',
            (string) __('notification.new_bid_title'),
            (string) __('notification.new_bid_message', ['auction' => $auction->title]),
            ['auction_id' => $auction->getKey()],
        );

        if ($auction->type !== AuctionType::SealedBid
            && $previousLeaderId !== null
            && $newLeaderId !== $previousLeaderId
            && $previousLeaderId !== $bidder->getKey()) {
            $previousLeader = User::query()->find($previousLeaderId);

            if ($previousLeader !== null) {
                $this->notifications->send(
                    $previousLeader,
                    'bid.outbid',
                    (string) __('notification.outbid_title'),
                    (string) __('notification.outbid_message', ['auction' => $auction->title]),
                    ['auction_id' => $auction->getKey()],
                );
            }
        }

        $auction->watchlists()
            ->where('notify_bid', true)
            ->whereNotIn('user_id', array_filter([$bidder->getKey(), $auction->seller_id, $previousLeaderId]))
            ->with('user')
            ->each(function ($watchlist) use ($auction): void {
                $this->notifications->send(
                    $watchlist->user,
                    'watchlist.bid',
                    (string) __('notification.watchlist_bid_title'),
                    (string) __('notification.watchlist_bid_message', ['auction' => $auction->title]),
                    ['auction_id' => $auction->getKey()],
                );
            });
    }

    private function fail(string $key): never
    {
        throw new BusinessRuleViolation($key, (string) __($key));
    }
}
