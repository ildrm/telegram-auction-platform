# Operations runbook

## Runtime processes

Run the HTTP application, a database queue worker, and Laravel's scheduler as
separate supervised processes:

```bash
php artisan queue:work --queue=default --tries=3 --timeout=120
php artisan schedule:work
```

Production must use HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, a supported
MySQL release, and private persistent volumes for `storage/app/private`.

## Telegram webhook

Set a random webhook secret and the public HTTPS endpoint:

```dotenv
TELEGRAM_BOT_TOKEN=...
TELEGRAM_WEBHOOK_SECRET=...
TELEGRAM_WEBHOOK_URL=https://auction.example/api/telegram/webhook
```

Register and inspect it with:

```bash
php artisan telegram:webhook-set
php artisan telegram:webhook-info
```

The webhook validates Telegram's secret header, stores updates idempotently,
and queues processing. A repeated Telegram `update_id` is acknowledged without
running it twice. Keep queue workers running before registering the webhook.

## Health and monitoring

`GET /api/health` and `php artisan platform:health` check database access,
private media storage, overdue auctions, stale scheduled auctions, failed jobs,
and failed Telegram deliveries. The scheduler runs the command every five
minutes. Alert on a non-zero exit or HTTP 503.

The scheduler also starts and closes auctions every minute. More than five
minutes of stale lifecycle records indicates that scheduler execution is
unhealthy.

## Encrypted backup and restore

Set `BACKUP_ENCRYPTION_KEY` to an independently stored random value of at least
32 characters. Losing it makes backups unrecoverable.

```bash
php artisan platform:backup
php artisan platform:backup-verify telegram-auction-YYYYMMDD-HHMMSS-xxxxxxxx.zip
php artisan platform:restore telegram-auction-YYYYMMDD-HHMMSS-xxxxxxxx.zip --force
```

Backups include business tables and all private auction media, encrypt every
archive member with AES-256, retain checksums, and are pruned after the
configured retention period. Restore verifies checksums and creates a safety
backup before entering maintenance mode. Practice restoration against an
isolated environment; never make a production restore the first recovery test.

Copy backup archives off-host to encrypted, access-controlled storage. Do not
store the archive and its encryption key in the same system.

## Deployment

Before deployment:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
composer audit --locked
php artisan migrate --force
php artisan optimize
php artisan platform:health
```

Restart queue workers after code deployment with `php artisan queue:restart`.
Tagged versions matching `VERSION` trigger the release workflow, which builds a
production archive, checksum, and GitHub release. Roll back application code
and restore a verified database backup when a migration cannot safely roll
forward.
