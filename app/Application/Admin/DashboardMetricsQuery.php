<?php

declare(strict_types=1);

namespace App\Application\Admin;

use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Moderation\Enums\ReportStatus;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Report;
use App\Models\User;

final class DashboardMetricsQuery
{
    /** @return array<string, int> */
    public function execute(): array
    {
        return [
            'active_users_30d' => User::query()
                ->where('last_seen_at', '>=', now()->subDays(30))
                ->count(),
            'pending_auctions' => Auction::query()
                ->where('status', AuctionStatus::PendingApproval)
                ->count(),
            'active_auctions' => Auction::query()
                ->where('status', AuctionStatus::Active)
                ->count(),
            'completed_auctions_30d' => Auction::query()
                ->where('status', AuctionStatus::Completed)
                ->where('updated_at', '>=', now()->subDays(30))
                ->count(),
            'bids_24h' => Bid::query()
                ->where('placed_at', '>=', now()->subDay())
                ->count(),
            'open_reports' => Report::query()
                ->whereIn('status', [ReportStatus::Open, ReportStatus::Reviewing])
                ->count(),
        ];
    }
}
