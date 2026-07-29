<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Application\Admin\DashboardMetricsQuery;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class PlatformStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $metrics = app(DashboardMetricsQuery::class)->execute();

        return [
            Stat::make('Active users (30d)', $metrics['active_users_30d']),
            Stat::make('Pending auctions', $metrics['pending_auctions']),
            Stat::make('Active auctions', $metrics['active_auctions']),
            Stat::make('Completed auctions (30d)', $metrics['completed_auctions_30d']),
            Stat::make('Bids (24h)', $metrics['bids_24h']),
            Stat::make('Open reports', $metrics['open_reports']),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasPermission('audit.view') ?? false;
    }
}
