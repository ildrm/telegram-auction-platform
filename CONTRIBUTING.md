# Contributing

Thank you for helping improve the Telegram Auction Platform.

## Before coding

1. Read `docs/IMPLEMENTATION_SPEC.md`.
2. Open an issue for behavior changes or schema changes so invariants and
   migration strategy can be agreed first.
3. Keep each pull request to one coherent vertical slice.

## Local checks

```bash
composer install
composer test
vendor/bin/pint --test
```

New behavior must include authorization, application-layer validation,
transactional integrity where needed, localization, auditing, tests, and
documentation. Do not add placeholders or move business rules into adapters.

Use Conventional Commits, for example:

```text
feat(auctions): add approval transition
fix(bids): lock auction before increment validation
```

Never include credentials, Telegram tokens, webhook secrets, `.env` files, or
production data in issues, tests, commits, or logs.
