<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Telegram\Enums\TelegramDeliveryStatus;
use App\Models\Auction;
use App\Models\TelegramDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class OperationalHealthService
{
    /** @return array{healthy: bool, checks: array<string, array{healthy: bool, value?: int|string}>} */
    public function inspect(): array
    {
        $checks = [];

        try {
            DB::select('SELECT 1');
            $checks['database'] = ['healthy' => true];
        } catch (Throwable) {
            $checks['database'] = ['healthy' => false];
        }

        try {
            Storage::disk((string) config('media.disk'))->exists('.');
            $checks['media_storage'] = ['healthy' => true];
        } catch (Throwable) {
            $checks['media_storage'] = ['healthy' => false];
        }

        $checks['overdue_active_auctions'] = [
            'healthy' => ($overdue = Auction::query()
                ->where('status', AuctionStatus::Active)
                ->where('ends_at', '<', now()->subMinutes(5))
                ->count()) === 0,
            'value' => $overdue,
        ];
        $checks['stale_scheduled_auctions'] = [
            'healthy' => ($stale = Auction::query()
                ->where('status', AuctionStatus::Scheduled)
                ->where('starts_at', '<', now()->subMinutes(5))
                ->where('ends_at', '>', now())
                ->count()) === 0,
            'value' => $stale,
        ];
        $checks['failed_jobs'] = [
            'healthy' => ($failedJobs = SchemaSafeCount::table('failed_jobs')) === 0,
            'value' => $failedJobs,
        ];
        $checks['failed_telegram_deliveries_24h'] = [
            'healthy' => ($failedDeliveries = TelegramDelivery::query()
                ->where('status', TelegramDeliveryStatus::Failed)
                ->where('updated_at', '>=', now()->subDay())
                ->count()) === 0,
            'value' => $failedDeliveries,
        ];

        return [
            'healthy' => collect($checks)->every(fn (array $check): bool => $check['healthy']),
            'checks' => $checks,
        ];
    }
}
