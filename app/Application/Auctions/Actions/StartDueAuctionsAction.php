<?php

declare(strict_types=1);

namespace App\Application\Auctions\Actions;

use App\Application\Audit\AuditLogger;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Models\Auction;
use Illuminate\Support\Facades\DB;

final readonly class StartDueAuctionsAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function execute(int $limit = 500): int
    {
        $started = 0;
        $ids = Auction::query()
            ->where('status', AuctionStatus::Scheduled)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->limit($limit)
            ->pluck('id');

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$started): void {
                $auction = Auction::query()->lockForUpdate()->find($id);

                if ($auction === null || $auction->status !== AuctionStatus::Scheduled || now()->lessThan($auction->starts_at)) {
                    return;
                }

                $auction->update(['status' => AuctionStatus::Active]);
                $this->auditLogger->record(
                    actor: null,
                    action: 'auction.started',
                    subject: $auction,
                    before: ['status' => AuctionStatus::Scheduled->value],
                    after: ['status' => AuctionStatus::Active->value],
                    metadata: null,
                );
                $started++;
            }, 3);
        }

        return $started;
    }
}
