<?php

declare(strict_types=1);

namespace App\Application\Auctions\Actions;

use App\Application\Audit\AuditLogger;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Services\AuctionStateMachine;
use App\Models\Auction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class TransitionAuctionAction
{
    public function __construct(
        private AuctionStateMachine $stateMachine,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(Auction $auction, AuctionStatus $to, ?User $actor): Auction
    {
        return DB::transaction(function () use ($auction, $to, $actor): Auction {
            /** @var Auction $locked */
            $locked = Auction::query()->lockForUpdate()->findOrFail($auction->getKey());
            $from = $locked->status;
            $this->stateMachine->assertCanTransition($from, $to);

            $locked->update(['status' => $to]);

            $this->auditLogger->record(
                actor: $actor,
                action: 'auction.status_changed',
                subject: $locked,
                before: ['status' => $from->value],
                after: ['status' => $to->value],
                metadata: null,
            );

            return $locked->refresh();
        });
    }
}
