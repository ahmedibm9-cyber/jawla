# Production Remediation Log

## 2026-07-26 — Program start

- Mode: `FIX_APPROVED_FINDINGS`
- Branch: `remediation/production-readiness`
- Audit baseline: `ba768f7106b52fa8d2905daadc07cd6091ff0c26`
- Starting commit: `d605f0d062fcad2be9d195a5aa4aa69b37819c3e`
- Current phase: Phase 0 — reliable test and branch safety

### Baseline delta

The starting commit is one direct commit after the audited revision. It contains authentication unification, the audit package, and two artifacts that were not part of the audit-authoring boundary: tracked `cookies.txt` and `docs/production-readiness/production-readiness.rar`. The cookie file contains a localhost XSRF token and must not remain tracked or be echoed. No production service or credential was accessed.

### Baseline commands

| Command                                                                                                                  | Result                                                                           |
| ------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------- |
| `git branch --show-current`                                                                                              | `remediation/production-readiness`                                               |
| `git rev-parse HEAD`                                                                                                     | `d605f0d062fcad2be9d195a5aa4aa69b37819c3e`                                       |
| `git diff --name-status ba768f7..HEAD`                                                                                   | Authentication changes plus committed audit package and artifacts recorded above |
| `php -v`                                                                                                                 | PHP 8.3.32                                                                       |
| Composer version                                                                                                         | Current shell could not locate the Composer executable; discovery remains open   |
| `node --version`                                                                                                         | v24.15.0                                                                         |
| `npm.cmd --version`                                                                                                      | 11.12.1                                                                          |
| `php artisan about`                                                                                                      | Laravel 13.20.0; local environment; PostgreSQL; debug enabled locally            |
| `php artisan migrate:fresh --force --no-interaction` with `DB_DATABASE=jawla_test_remediation_p0`                        | Fresh isolated PostgreSQL schema completed                                       |
| `php artisan test --compact --testsuite=Unit,Feature --do-not-cache-result` with `DB_DATABASE=jawla_test_remediation_p0` | Running; final evidence pending                                                  |

### Phase 0 observations

- `phpunit.xml` hardcodes the shared database name `jawla_test`.
- CI intentionally runs serially because per-process databases were previously unmigrated/racy.
- `tests/bootstrap.php` contains migration logic but is not referenced by `phpunit.xml`.
- Several tests use `DatabaseTransactions` and therefore require schema before the first such test.
- CI’s current secret grep does not reject tracked browser cookie jars; `cookies.txt` is tracked in the starting commit.
- `vendor/bin/phpstan` was absent at the audit baseline; static-analysis gate remains to be established.

No finding is marked resolved yet.

## 2026-07-26 — Phase 0 evidence

### Red/green baseline

1. The first isolated sequential run completed 468 tests with 467 passes and one failure: guest `/app` redirected to `/login` instead of the approved Filament `/admin/login`.
2. The existing assertion was preserved. `bootstrap/app.php` was corrected.
3. Focused lifecycle coverage passed 7/7; affected authentication coverage passed 18/18.
4. The post-fix isolated sequential Unit and Feature run passed 476/476 tests with 1,325 assertions.

### Database safety and isolation

- A test-first `TestingDatabaseGuard` now rejects any environment other than `testing`, any connection other than PostgreSQL, and any database outside `jawla_test`/`jawla_test_*`.
- Red evidence was the absent guard class; green evidence is 8/8 tests with 13 assertions.
- An explicit negative integration run with `DB_DATABASE=jawla` stopped all eight tests before assertions with the expected safety exception.
- PHPUnit bootstrap safely provisions/migrates a missing sequential test database and restores PHPUnit error/exception handlers.
- A `DatabaseTransactions` service suite passed 8/8 with 23 assertions on the bootstrap-provisioned database.
- Laravel per-worker isolation passed:
  - Unit: 106/106, 301 assertions.
  - Feature: 370/370, 1,024 assertions.
  - Exact combined CI command: 476/476, 1,325 assertions.
- CI now uses two recreated/dropped PostgreSQL worker databases instead of a shared serial test database.

### Branch and secret safety

- Removed tracked `cookies.txt`, which contained a localhost XSRF token. The file is recoverable from Git history but is no longer present in the working tree.
- Removed the opaque duplicate `production-readiness.rar`; canonical audit evidence remains Markdown/JSON.
- Added ignore and CI filename gates for cookie/private-key artifacts.
- Added a Gitleaks PR/history scan job using the documented v2 action.
- Existing pattern scan found no obvious tracked secret match outside excluded lockfiles/docs.

### Residual PR-019 work

The test infrastructure portion is complete, but PR-019 remains open until later phases add and pass the required tenant-negative, financial concurrency/lifecycle, offline browser, migration, restore/rollback, PDF/QR, accessibility, security, and operational suites.

## 2026-07-26 — Phase 1 evidence

Phase 1 implements PR-005, PR-001, PR-013, and the canonical-role/bypass-removal portion of PR-014. Detailed traceability is in `phase-1-catastrophic-containment.md`.

### Exit gates

- No known/default credentials: pass. Production bootstrap requires an environment-supplied strong secret; demo passwords are generated; performance credentials have no default fallback.
- No production demo seeding: pass. Production predeploy runs no seeder/bootstrap, and `DemoSeeder` independently refuses production mode.
- Tenant negative matrix: pass. The focused matrix passed 9/9 tests and 20 assertions.
- Stored map XSS: pass. DOM-only popup construction passed contextual sink tests and 2/2 real-browser payload suites.
- Canonical role provisioning: pass. All five canonical roles are seeded; the global legacy authorization bypass is gone; `system_viewer` mutation is denied.

### Verification

- Phase 1 focused PHP gate: 23/23 tests, 84 assertions.
- Affected regression bundle: 91/91 tests, 312 assertions.
- Pint verification: pass.
- Vite production build: pass, 337 modules transformed.
- Playwright stored-XSS suite: 2/2 passed.
- Exact isolated PostgreSQL Unit/Feature CI command: 496/496 tests, 1,402 assertions.

PR-001, PR-005, and PR-013 are resolved by executable evidence. PR-014 remains in progress because the approved Phase 5 work still includes complete legacy-role removal, MFA, step-up authentication, session controls, throttling, logout, and proxy-trust hardening.

## 2026-07-26 — Phase 2 evidence

Phase 2 implements PR-002, PR-003, PR-006, PR-007, PR-009, PR-010, PR-028, and PR-031. Detailed traceability is in `phase-2-core-money-inventory-integrity.md`.

### Exit gates

- Atomic and authorized money/stock workflows: pass through locked services, manager-only approval/reversal boundaries, and same-company checks.
- Approved return/refund lifecycle: pass for unpaid, partially/fully settled credit behavior, manager-approved cash refunds, and externally confirmed bank/card refunds.
- Append-only history: pass through ORM guards, restrictive foreign keys, compensating records, and direct PostgreSQL delete triggers.
- Reconciliation: pass for expected snapshot, physical count, approval, immutable delta, intervening-movement rejection, and read-only customer/cash/stock ledger drift reporting.
- PostgreSQL concurrency: final-unit stock, duplicate payment intent, sale-versus-count, and return-versus-sale races pass on two independent connections; the concurrency file also passed three consecutive repeat runs.

### Verification

- Fresh PostgreSQL migrations: pass through `2026_07_26_000011`.
- Consolidated Phase 2 focused suite: 151/151 tests, 441 assertions.
- Exact two-worker Unit/Feature CI gate: 522/522 tests, 1,515 assertions.
- Pint: pass after formatting the dirty Phase 2 set.
- PHPStan: unavailable because the executable is not installed; dependencies were not changed.
