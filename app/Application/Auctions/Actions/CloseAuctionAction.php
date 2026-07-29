<?php

declare(strict_types=1);

namespace App\Application\Auctions\Actions;

use App\Application\Audit\AuditLogger;
use App\Application\Notifications\NotificationService;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Enums\AuctionType;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CloseAuctionAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private NotificationService $notifications,
    ) {}

    public function execute(Auction $auction): Auction
    {
        return DB::transaction(function () use ($auction): Auction {
            /** @var Auction $locked */
            $locked = Auction::query()->lockForUpdate()->findOrFail($auction->getKey());

            if ($locked->status !== AuctionStatus::Active || now()->lessThan($locked->ends_at)) {
                return $locked;
            }

            $winningBid = $this->winningBid($locked);
            $reserveMet = $this->reserveMet($locked, $winningBid);
            $winnerId = $reserveMet ? $winningBid->bidder_id : null;

            $locked->update([
                'winner_id' => $winnerId,
                'current_price_minor' => $winningBid?->amount_minor ?? $locked->current_price_minor,
                'status' => AuctionStatus::Completed,
                'closed_at' => now(),
            ]);

            $this->auditLogger->record(
                actor: null,
                action: 'auction.closed',
                subject: $locked,
                before: ['status' => AuctionStatus::Active->value],
                after: [
                    'status' => AuctionStatus::Completed->value,
                    'winner_id' => $winnerId,
                    'reserve_met' => $reserveMet,
                ],
                metadata: ['winning_bid_id' => $winningBid?->getKey()],
            );
            $this->notifyOutcome($locked, $winnerId);

            return $locked;
        }, 3);
    }

    private function winningBid(Auction $auction): ?Bid
    {
        $query = $auction->bids()->with('bidder');

        return match ($auction->type) {
            AuctionType::Reverse => $query->orderBy('amount_minor')->orderBy('placed_at')->first(),
            default => $query->orderByDesc('amount_minor')->orderBy('placed_at')->first(),
        };
    }

    private function reserveMet(Auction $auction, ?Bid $winningBid): bool
    {
        if ($winningBid === null) {
            return false;
        }

        if ($auction->reserve_price_minor === null) {
            return true;
        }

        return $auction->type === AuctionType::Reverse
            ? $winningBid->amount_minor <= $auction->reserve_price_minor
            : $winningBid->amount_minor >= $auction->reserve_price_minor;
    }

    private function notifyOutcome(Auction $auction, ?int $winnerId): void
    {
        $seller = $auction->seller()->firstOrFail();
        $this->notifications->send(
            $seller,
            'auction.closed',
            (string) __('notification.closed_title'),
            (string) __('notification.closed_message', ['auction' => $auction->title]),
            ['auction_id' => $auction->getKey(), 'winner_id' => $winnerId],
        );

        if ($winnerId !== null) {
            $winner = User::query()->findOrFail($winnerId);
            $this->notifications->send(
                $winner,
                'auction.won',
                (string) __('notification.won_title'),
                (string) __('notification.won_message', ['auction' => $auction->title]),
                ['auction_id' => $auction->getKey()],
            );
        }

        $auction->watchlists()
            ->where('notify_closing', true)
            ->whereNotIn('user_id', array_filter([$auction->seller_id, $winnerId]))
            ->with('user')
            ->each(function ($watchlist) use ($auction): void {
                $this->notifications->send(
                    $watchlist->user,
                    'watchlist.closed',
                    (string) __('notification.closed_title'),
                    (string) __('notification.closed_message', ['auction' => $auction->title]),
                    ['auction_id' => $auction->getKey()],
                );
            });
    }
}
