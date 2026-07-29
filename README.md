# Telegram Auction Platform

A production-oriented, Telegram-first auction platform built as a Laravel 12
modular monolith.

The repository implements the complete platform roadmap:

- Telegram-native user identities
- Signed, idempotent Telegram webhooks, localized conversations, and queued delivery
- English, hybrid, buy-now, Dutch, reverse, and sealed-bid auctions
- Proxy bidding, anti-sniping, scheduled closing, watchlists, and reviews
- Transactional, concurrency-safe bid placement
- Role/permission authorization, moderation workflows, and a Filament admin panel
- Immutable bid history and append-only audit records
- Private image uploads with queued WebP derivatives and expiring signed URLs
- Health monitoring, encrypted backup/restore, and release automation
- REST endpoints that reuse the same application actions
- English and Persian user-facing messages

The [implementation specification](docs/IMPLEMENTATION_SPEC.md) explains the
optimized scope, architecture, acceptance criteria, and delivery phases.

## Requirements

- PHP 8.2+ (PHP 8.4 is the production target)
- Composer 2
- PHP extensions: fileinfo, GD, ZIP
- MySQL 8+ for production; SQLite is used by the test suite

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
composer test
```

Run the web/API process and database queue worker separately:

```bash
php artisan serve
php artisan queue:work
php artisan schedule:work
```

## API slice

- `GET /api/v1/auctions` — active auction discovery
- `GET /api/v1/auctions/{auction}` — auction detail
- `POST /api/v1/auctions` — create a draft (authenticated seller)
- `POST /api/v1/auctions/{auction}/bids` — place a bid (authenticated user)
- `POST /api/v1/auctions/{auction}/purchase` — buy now / accept Dutch price
- `PUT /api/v1/auctions/{auction}/watchlist` — update watch preferences
- `POST /api/v1/auctions/{auction}/reviews` — review a completed transaction
- `POST /api/v1/auctions/{auction}/media` — securely upload an auction image

See the [operations runbook](docs/OPERATIONS.md) for Telegram registration,
workers, monitoring, encrypted recovery, and release procedures.

Presentation layers must call application actions; they must not reproduce
auction or bidding rules.

## Quality

```bash
composer test
vendor/bin/pint --test
```

## License

MIT
