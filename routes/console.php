<?php

declare(strict_types=1);

use App\Application\Auctions\Actions\CloseDueAuctionsAction;
use App\Application\Auctions\Actions\StartDueAuctionsAction;
use App\Application\Operations\BackupService;
use App\Application\Operations\OperationalHealthService;
use App\Infrastructure\Telegram\TelegramClient;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('auctions:start-due', function (StartDueAuctionsAction $action): void {
    $this->info("Started {$action->execute()} auction(s).");
})->purpose('Start scheduled auctions whose start time has arrived');

Artisan::command('auctions:close-due', function (CloseDueAuctionsAction $action): void {
    $this->info("Closed {$action->execute()} auction(s).");
})->purpose('Close active auctions whose end time has passed');

Schedule::command('auctions:start-due')->everyMinute()->withoutOverlapping();
Schedule::command('auctions:close-due')->everyMinute()->withoutOverlapping();

Artisan::command('platform:backup', function (BackupService $backups): void {
    $this->info('Created encrypted backup: '.$backups->create());
})->purpose('Create and verify an encrypted database and media backup');

Artisan::command('platform:backup-verify {filename}', function (BackupService $backups, string $filename): void {
    $manifest = $backups->verify($filename);
    $this->info("Backup verified (created {$manifest['created_at']}).");
})->purpose('Verify backup decryption, structure, and checksums');

Artisan::command('platform:restore {filename} {--force}', function (BackupService $backups, string $filename): int {
    if (! $this->option('force')) {
        $this->error('Restore is destructive. Re-run with --force after confirming the target environment.');

        return 1;
    }

    $safetyBackup = $backups->create();
    $this->components->task("Safety backup created: {$safetyBackup}");
    Artisan::call('down', ['--retry' => 60]);

    try {
        $backups->restore($filename);
    } finally {
        Artisan::call('up');
    }

    $this->info('Restore completed and the application has left maintenance mode.');

    return 0;
})->purpose('Restore an encrypted backup after creating a safety backup');

Artisan::command('platform:health', function (OperationalHealthService $health): int {
    $result = $health->inspect();

    foreach ($result['checks'] as $name => $check) {
        $line = ($check['healthy'] ? 'OK' : 'FAIL')." {$name}";
        $this->{$check['healthy'] ? 'info' : 'error'}($line);
    }

    return $result['healthy'] ? 0 : 1;
})->purpose('Check database, storage, scheduler, queue, and Telegram delivery health');

Schedule::command('platform:health')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('platform:backup')->dailyAt('02:00')->withoutOverlapping();

Artisan::command('telegram:webhook-set', function (TelegramClient $telegram): void {
    $url = config('telegram.webhook_url') ?: rtrim((string) config('app.url'), '/').'/api/telegram/webhook';
    $secret = (string) config('telegram.webhook_secret');

    if ($secret === '' || strlen($secret) > 256) {
        throw new RuntimeException('TELEGRAM_WEBHOOK_SECRET must contain between 1 and 256 characters.');
    }

    $telegram->call('setWebhook', [
        'url' => $url,
        'secret_token' => $secret,
        'allowed_updates' => ['message', 'callback_query'],
        'drop_pending_updates' => false,
    ]);
    $this->info("Telegram webhook configured for {$url}.");
})->purpose('Register the configured HTTPS webhook with Telegram');

Artisan::command('telegram:webhook-info', function (TelegramClient $telegram): void {
    $result = $telegram->call('getWebhookInfo', []);
    $info = (array) ($result['result'] ?? []);
    $this->line(json_encode([
        'url' => $info['url'] ?? null,
        'pending_update_count' => $info['pending_update_count'] ?? null,
        'last_error_date' => $info['last_error_date'] ?? null,
        'last_error_message' => $info['last_error_message'] ?? null,
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
})->purpose('Display Telegram webhook delivery status without exposing secrets');
