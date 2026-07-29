<?php

declare(strict_types=1);

namespace App\Application\Watchlists\Actions;

use App\Application\Audit\AuditLogger;
use App\Models\Auction;
use App\Models\User;
use App\Models\Watchlist;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class UpdateWatchlistAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function execute(
        Auction $auction,
        User $user,
        bool $watching,
        bool $notifyBid = false,
        bool $notifyClosing = true,
    ): ?Watchlist {
        if ($auction->is_private || ! $user->hasPermission('auction.view')) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($auction, $user, $watching, $notifyBid, $notifyClosing): ?Watchlist {
            $watchlist = Watchlist::query()->whereBelongsTo($user)->whereBelongsTo($auction)->first();

            if (! $watching) {
                $watchlist?->delete();
                $this->auditLogger->record($user, 'watchlist.removed', $auction, null, null, null);

                return null;
            }

            $watchlist = Watchlist::query()->updateOrCreate(
                ['user_id' => $user->getKey(), 'auction_id' => $auction->getKey()],
                ['notify_bid' => $notifyBid, 'notify_closing' => $notifyClosing],
            );
            $this->auditLogger->record(
                $user,
                'watchlist.updated',
                $auction,
                null,
                ['notify_bid' => $notifyBid, 'notify_closing' => $notifyClosing],
                null,
            );

            return $watchlist;
        });
    }
}
