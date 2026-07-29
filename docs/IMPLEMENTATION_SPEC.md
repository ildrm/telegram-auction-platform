# Telegram Auction Platform — Implementation Specification

## 1. Purpose

Build an open-source, Telegram-first auction platform as a Laravel modular
monolith. Telegram, the REST API, scheduled commands, and the future Filament
panel are adapters around one application layer. No presentation adapter owns
business rules.

This document replaces the original 266-rule constitution as the executable
engineering contract. The constitution was directionally strong but too broad
to verify, repeated many rules, mixed version-one scope with future ideas, and
used absolutes such as “never hardcode strings” that conflict with safe enums,
translation keys, and protocol constants.

## 2. Quality hierarchy

When requirements conflict, use this order:

1. Data integrity and security
2. Correct business behavior
3. Backward compatibility
4. Operability and observability
5. Maintainability and testability
6. Performance supported by measurements
7. Convenience

“Future-proof” means stable boundaries and migrations, not speculative
abstractions. Duplication is preferable to a premature abstraction until a
shared business concept is proven.

## 3. Architecture

The application is a modular monolith with inward dependencies:

```text
Telegram / HTTP / Filament / CLI
             ↓
      Application actions
             ↓
   Domain rules and value objects

Infrastructure implements ports required by the application/domain.
Laravel models are persistence adapters and may contain relationships, casts,
query scopes, and no cross-aggregate workflows.
```

Actions are the transaction boundary. DTOs are immutable validated inputs.
Policies authorize capabilities. Database constraints protect invariants.
Events describe completed facts and side effects run after commit.

## 4. Delivery phases

### Phase 1 — English auction core (implemented)

- Telegram-compatible user identity
- Categories
- Draft creation and activation state transition
- Active auction discovery
- Transactional bid placement using row locks
- Self-bid prevention, minimum increment, account status, and time-window rules
- Append-only bid and audit history
- REST adapter, policies, translations, factories, seed data, and tests

### Phase 2 — Telegram experience (implemented)

- Signed webhook adapter and idempotent update ingestion
- Persistent conversation state and auction-creation wizard
- Inline keyboards, pagination, localized rendering, and queued delivery
- Retry policy, rate limiting, and webhook operations guide

### Phase 3 — Moderation and Filament (implemented)

- Roles and permissions
- Approval/rejection workflow
- Categories, users, reports, translations, and audit resources
- Dashboard metrics derived from documented queries

### Phase 4 — Auction expansion (implemented)

- Hybrid/buy-now, Dutch, reverse, and sealed-bid strategies
- Proxy bidding and anti-sniping
- Closing scheduler, winner selection, notifications, watchlists, and reviews

### Phase 5 — Media and operations (implemented)

- Secure uploads, image derivatives, backup/restore, monitoring, and release
  automation

No phase may claim completion until its migrations, authorization, validation,
auditing, localization, documentation, and automated acceptance tests pass.

## 5. Core decisions

- Store money as integer minor units plus ISO 4217 currency. This eliminates
  floating-point and database-decimal ambiguity.
- Store instants in UTC and render in the user’s IANA timezone.
- Use numeric internal primary keys; public identifiers can be added separately.
- Bids and audit records are append-only.
- Use Eloquent directly unless a query-specific repository demonstrates value.
- Use MySQL in production and SQLite only for deterministic automated tests.
- External paid infrastructure is not required. Database queue and filesystem
  storage are supported defaults.

## 6. Phase 1 invariants

An auction can receive a bid only when it is active and the current UTC time is
within its start/end window. A seller cannot bid on their auction. A bidder must
be active. The bid currency is the auction currency. The amount must be at least
the current price plus the minimum increment. Bid insertion, price update, and
audit insertion occur in one transaction while the auction row is locked.

An auction state changes only through the state machine. Phase 1 permits:

- draft → pending approval, scheduled, or active
- pending approval → scheduled, active, or rejected
- scheduled → active or cancelled
- active → completed, cancelled, or suspended

## 7. Definition of done

A change is done when:

- behavior and failure modes are documented;
- inputs are validated at the adapter and application boundary;
- authorization is enforced before mutation;
- multi-record mutations are transactional;
- important mutation outcomes are audited without secrets;
- user messages are translation-backed;
- tests cover the happy path, authorization, and critical invariants;
- formatting and the complete test suite pass;
- no placeholder, TODO, or knowingly dead code is introduced.

## 8. Delivery status

All five phases are represented by production code, migrations, authorization,
validation, auditing, localization, runbooks, and automated acceptance tests.
Payment settlement remains intentionally outside version 1.0; auction results
identify the buyer and seller but do not move funds.
