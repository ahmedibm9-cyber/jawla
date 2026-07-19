# Repair the test suite: 13 schema errors + 1 tenancy failure block all verification

## Overview

`php artisan test` currently reports **122 tests / 108 passed / 13 errors / 1 failure**. The 13 errors are all `SQLSTATE 42P01/42703` (missing tables `companies`, `warehouses`; missing columns `packaging_type`, `batch_id`, `in_transit_warehouse_id`) against `jawla_test` — the test database schema has drifted from the migrations. The failure is `CompanyIsolationTest::test_disabled_context_sees_all_companies` (expected 2 companies, saw 20 — leaked state between tests). Nothing else (features, refactors, releases) can be trusted until this is green.

## Scope

**Included:** test DB schema reset strategy, `RefreshDatabase`/migration configuration in affected suites (`StockServiceTest`, `ReturnServiceTest`, `VanTransferServiceTest`), fixing the isolation-count assertion or its state leak.
**Excluded:** adding new test coverage (separate issues), changing production migrations.

## Technical Requirements

- Every test class touching the DB uses `RefreshDatabase` (or the project's chosen equivalent) consistently — the errors show some classes run against a stale schema while others migrate.
- `php artisan migrate:fresh --seed --env=testing` must succeed on a clean `jawla_test` database (Master Plan §16 requires PostgreSQL for certification).
- `CompanyIsolationTest` must not depend on database record counts left by other tests — assert against records it created, or reset sequence/state.

## Implementation Plan

1. Run `php artisan migrate:fresh --env=testing` against `jawla_test`; capture any migration failure — if migrations themselves fail on clean PG, fix ordering/constraints first.
2. Audit `tests/Unit/Services/*` for missing `RefreshDatabase` trait or a `DatabaseTransactions` mismatch; standardize via `tests/Pest.php` `uses()`.
3. Fix `CompanyIsolationTest.php:48` to count only fixture companies (e.g., scope the assertion to IDs it created).
4. Re-run full suite; commit.

## Acceptance Criteria

- [ ] `php artisan test` → 0 errors, 0 failures on a freshly created `jawla_test` PostgreSQL database
- [ ] `php artisan migrate:fresh --seed` passes clean
- [ ] CI (if configured) runs the suite green

## Priority

Highest (score 12.5). Every other issue's acceptance criteria depend on a trustworthy suite.

## Dependencies

- **Blocks:** #2, #3, #4, #5, #6, #7 (all require green-suite verification)
- **Blocked by:** none

## Implementation Size

- **Estimated effort:** Small (1 day)
