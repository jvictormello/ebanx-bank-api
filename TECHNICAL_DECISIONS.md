# Banking API — Technical Notes & Decisions

## Overview

This document complements the main README and provides deeper insight into technical decisions, trade-offs, and implementation details.

It is intended to help reviewers understand:
- why certain choices were made
- how the system behaves under real conditions
- what would change in a production scenario

---

## Key Technical Decisions

### 1. Redis as Primary Store

Redis was chosen as the single source of state for this challenge.

Rationale:
- extremely fast read/write
- simple data model fits the problem
- avoids unnecessary relational complexity
- aligns with ephemeral nature of the challenge

Trade-off:
- no persistence guarantees
- no audit trail
- not suitable for real financial systems

---

### 2. Redis Hash Data Model

Balances and overdraft limits are stored in two Redis hashes:

- accounts => { account_id: balance }
- overdraft_limits => { account_id: overdraft_limit }

Why:
- reduces key management complexity
- allows atomic operations with minimal overhead
- simple to reason about

Notes:
- the original challenge only needed `accounts`
- `overdraft_limits` was added as an exploratory enhancement beyond the original scope
- when an account has no configured overdraft limit, the effective limit is `0`
- new accounts receive their overdraft limit during the first deposit
- existing accounts cannot silently change their overdraft limit through later deposits

Trade-off:
- not horizontally partitioned
- not ideal for very large datasets

---

### 3. Atomic Operations via Lua

Withdraw and transfer use Lua scripts executed inside Redis.

Why:
- guarantees atomic execution
- prevents race conditions
- avoids partial updates
- keeps balance and overdraft validation in the data layer

Example problem avoided:
- two concurrent transfers modifying the same balance
- race conditions when creating a new account and assigning its initial overdraft limit

Trade-off:
- introduces Redis-specific logic
- harder to debug than plain commands

---

### 4. Minimal Infrastructure

No:
- MySQL/PostgreSQL
- Nginx
- queues

Why:
- focus on core logic
- reduce setup complexity
- faster evaluation

Trade-off:
- not production-ready architecture
- lacks scalability layers

---

### 5. Feature Tests Over Unit Tests

Only feature tests were implemented.

Why:
- validates real HTTP contract
- covers full request lifecycle
- more aligned with challenge expectations

Trade-off:
- less isolation of components
- harder to pinpoint internal failures

---

## Runtime Challenges & Fixes

### 1. PHP Built-in Server Issue

Problem:
- php artisan serve failed with permission issues inside Docker

Solution:
- replaced with: php -S 0.0.0.0:8000 -t public public/index.php

---

### 2. Laravel Default Database Usage

Problem:
- Laravel attempted to use SQLite for sessions/cache

Solution:
- switched to:
  - SESSION_DRIVER=file
  - CACHE_STORE=file
  - QUEUE_CONNECTION=sync

---

### 3. CSRF Blocking API Requests

Problem:
- POST endpoints returned 419 due to CSRF

Solution:
- excluded /reset and /event from CSRF validation

---

### 4. Redis Driver Edge Case

Problem:
- Redis returned false instead of null for missing values

Solution:
- repository treats both null and false as non-existent account

---

### 5. Redis Prefix Interference

Problem:
- Redis prefix affected Lua script key resolution

Solution:
- disabled prefix via REDIS_PREFIX=

---

## Limitations

Current implementation does NOT handle:

- transaction history
- idempotency
- authentication

Notes:
- insufficient funds validation is implemented
- overdraft support exists in this branch as an exploratory enhancement
- both are still intentionally lightweight and not meant to model a real banking ledger

---

## How This Would Evolve in Production

If extended to a real system:

### Data Layer
- replace Redis with relational DB + ledger
- introduce transactions and consistency guarantees

### Domain
- account model with:
  - balance
  - available balance
  - overdraft limit

### Reliability
- idempotent operations
- retries and circuit breakers

### Observability
- logs
- metrics
- tracing

---

## Final Notes

The implementation prioritizes:
- correctness of behavior
- simplicity
- clarity for review and discussion

It is intentionally minimal, but structured in a way that allows safe evolution into a more complex system.
