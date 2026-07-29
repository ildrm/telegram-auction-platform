<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Operations\BackupService;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class OperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_encrypted_backup_is_created_and_checksum_verified(): void
    {
        Storage::fake('backups');
        Storage::fake('auction-media');
        config([
            'backup.disk' => 'backups',
            'backup.encryption_key' => str_repeat('k', 32),
            'media.disk' => 'auction-media',
        ]);
        User::factory()->create();
        Storage::disk('auction-media')->put('auctions/1/originals/example.jpg', 'image-bytes');

        $backups = $this->app->make(BackupService::class);
        $filename = $backups->create();
        $manifest = $backups->verify($filename);

        Storage::disk('backups')->assertExists($filename);
        self::assertSame(1, $manifest['format_version']);
        self::assertArrayHasKey('users', $manifest['tables']);
        self::assertArrayHasKey('auctions/1/originals/example.jpg', $manifest['media']);
    }

    public function test_health_endpoint_reports_overdue_scheduler_work(): void
    {
        Storage::fake('auction-media');
        config(['media.disk' => 'auction-media']);
        Auction::factory()->active()->create([
            'status' => AuctionStatus::Active,
            'ends_at' => now()->subMinutes(10),
        ]);

        $this->getJson('/api/health')
            ->assertServiceUnavailable()
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('checks.overdue_active_auctions.healthy', false)
            ->assertJsonPath('checks.overdue_active_auctions.value', 1);
    }
}
