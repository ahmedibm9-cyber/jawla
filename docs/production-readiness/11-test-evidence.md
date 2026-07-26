# Test Evidence

## Evidence verdict

The repository has a substantial Pest suite, but this audit did **not** obtain a valid clean application test run. Concurrent read-only audit agents unintentionally ran existing tests against the same configured `jawla_test` PostgreSQL database. Their `RefreshDatabase` setup/teardown operations collided, causing deadlocks and transient missing table/column errors.

These results are infrastructure-contaminated and are classified **NOT VERIFIED**. They are neither product failures nor passes.

## Commands executed

| Command | Result | Evidence use |
|---|---|---|
| `git branch --show-current` / `git rev-parse HEAD` / `git status --short` / lockfile tracking | exit 0 | pinned baseline and detected later unrelated worktree changes |
| PHP/Composer/Node/npm/Playwright version checks | tools identified; `psql` CLI absent | inventory only |
| `php artisan about` | exit 0, 6.816s | local configuration inventory only |
| `php artisan route:list --except-vendor` | exit 0, 6.56s; 100 routes | route inventory |
| `php artisan route:list --path=admin -vv` | exit 0, 11.2s; 64 routes | static middleware evidence for PR-001 |
| focused Livewire/admin route listings | exit 0 | middleware evidence only |
| `composer validate --no-check-publish` | exit 0, 4.21s; valid, six unbounded constraints | dependency metadata |
| `composer audit --locked --no-interaction` | exit 0, 5.99s; no advisories | point-in-time advisory evidence |
| `npm audit --audit-level=high --package-lock-only` | exit 0, 10.3s; zero vulnerabilities | point-in-time advisory evidence |
| `php vendor\bin\pint --test` | exit 0, 20.3s | formatting check passed |
| `php vendor\bin\phpstan analyse --no-progress --memory-limit=512M` | exit 1; executable absent | static-analysis gate missing, not a code-analysis result |
| `npm.cmd run audit:pwa-assets` | exit 0, 1.4s | JS 50.5 KiB, CSS 22.3 KiB, total 503.1 KiB gzip; budgets passed |
| full Unit/Feature test attempt | did not complete; exact spawned PHP processes terminated after more than six minutes of shared-DB contention | invalid; no pass/fail conclusion |
| focused financial suite, 19 files | exit 1 after 542.487s; 138 tests, 98 reported pass, 40 setup errors | invalid shared-DB run; counts are not release evidence |
| focused security/tenancy suite | exit 2; 38 setup deadlock errors, zero assertions | invalid shared-DB run |
| focused offline/sync suite, 3 files | exit 1 after 120.537s; 28 tests, 2 reported pass, 26 infrastructure errors | invalid shared-DB run |

No further database test command was run after the collision was identified.

## What existing tests appear to cover statically

- Invoice/service happy and selected failure paths.
- Stock nonnegative behavior and movement creation.
- Serial same-key offline replay.
- Receipt/business mutation rollback structure.
- Roles/policies for the implemented legacy role model.
- Security header presence.
- API authentication, abilities, issuance/revocation, and selected company scope.
- GPS validation/deduplication and intended manager access.
- Photo upload and PDF authorization paths.
- Batch helpers/FEFO behavior.

Static presence of tests does not establish that they pass at the audited release candidate.

## Material missing or insufficient scenarios

### Concurrency

- two sales for final stock;
- sale versus reconciliation;
- first sequence initialization;
- payment versus cancellation/amendment;
- concurrent cash-box mutations;
- cumulative returns;
- double reversal;
- concurrent duplicate sync;
- ETA submit retry/lost response.

### Browser/PWA

- real IndexedDB enqueue and transaction abort;
- network disabled during confirmation;
- reload/launch/route change offline;
- rapid click, multi-tab, and multi-device duplicate intent;
- lost response after server commit;
- service-worker install/update and old-client migration;
- queue conflict/discard privilege, audit, and recovery;
- lost/shared device and logout/session-expiry purge.

### Security/tenancy

- generated two-company Filament IDOR matrix;
- global search, relations, bulk actions, import/export, maps, Livewire initial/update boundaries;
- stored-XSS browser execution corpus;
- MFA/step-up, proxy spoofing, session revocation/absolute expiry;
- private object/file access, malformed/polyglot/EXIF tests;
- outbound telemetry redaction inspection.

### Financial/compliance

- authoritative pricing/discount approval;
- original-sale/cumulative return/batch proof;
- paid return/refund/credit note;
- amendment/cancellation/payment lifecycle;
- immutable PDF after master changes/cache loss;
- sequence initialization/year rollover/same abbreviation;
- official ZATCA vectors and ETA certification/rejection.

### Release/operations

- fresh/upgrade migrations on an isolated database;
- staging deployment of the exact artifact;
- rollback and schema/client compatibility;
- independent backup restore and reconciliation;
- current production-shaped load and data volumes;
- alert delivery and incident drill;
- signed multi-role UAT.

## Tests deliberately not run

- No development or production migrations or `migrate:status`.
- No production/staging connection.
- No production credentials/data.
- No backup or restore.
- No deployment or rollback.
- No live k6/load test.
- No browser run requiring a dev server/database after the test collision.
- No destructive FK/data manipulation outside existing isolated tests.
- No official ZATCA/ETA external submission.
- No external object store, Sentry, Railway, monitoring, email, or other production-service call.

## Required rerun protocol

1. Freeze the release candidate and record its commit/artifact digest.
2. Allocate a unique ephemeral PostgreSQL database per runner; forbid shared `RefreshDatabase` targets.
3. Run formatting, static analysis, dependency audits, Unit, Feature, browser, concurrency, and compliance suites in blocking CI.
4. Preserve JUnit, screenshots/traces, migration logs, dependency reports, and artifact checksums.
5. Treat skipped or advisory critical suites as release failures.

