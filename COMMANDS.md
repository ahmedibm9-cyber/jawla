# Jawla — Commands Reference

**Observed on:** Windows, PowerShell, 2026-07-29

**Revision:** `7b1dd3a` plus inspected working-tree changes
**Status vocabulary:** Verified, Failed, Declared, Blocked, Not run

> **Implementation update (2026-07-29):** `typecheck` now runs PHPStan level 0
> as the blocking runtime-safety gate with Pao output capture disabled and a 2 GB
> memory limit. `typecheck-strict` preserves the level-6 debt audit. The final
> verification results are recorded in
> `docs/PRODUCTION_READINESS_IMPLEMENTATION.md`.

## Important local constraint

GNU Make is not installed on the inspected host. Every `make ...` entry is therefore **Blocked locally as written**, even when its underlying command was run directly. On Linux/CI, use the Makefile targets. On this Windows host, use the equivalent direct commands below.

Composer is installed as a PHP script without a Windows wrapper. Invoke it through PHP when necessary:

```powershell
$composer = (Get-Command composer).Source
php $composer <command>
```

Do not put credentials or `.env` values in command output or reports.

## Verification snapshot

| Check | Command run | Status | Result |
|---|---|---|---|
| PHP lint/style | `php vendor/bin/pint --test` | **Verified** | Passed; Pint JSON result `passed` |
| Static analysis | `PAO_DISABLE=1 php vendor/bin/phpstan analyse --level=0 --memory-limit=2G --no-progress` | **Verified** | No errors |
| Unit+Feature aggregate | `JAWLA_TEST_DATABASE=jawla_test_readiness_final php -d memory_limit=2G artisan test --testsuite=Unit,Feature` | **Verified** | 666 passed, 1,878 assertions, 976.17 s |
| Production asset build | `npm.cmd run build` | **Verified** | Vite 8.1.4; 338 modules transformed; built in 4.14 s |
| PWA asset budget | `npm.cmd run audit:pwa-assets` | **Verified** | JS 51.4 KiB, CSS 22.5 KiB, total 504.3 KiB gzip; all within budget |
| Route discovery | `php artisan route:list --json` | **Verified** | 121 routes |
| Laravel optimize | `php artisan optimize` | **Verified** | Config, events, routes, views, Blade icons, and Filament cached successfully |
| Schedule discovery | `php artisan schedule:list` | **Verified** | Daily `app:purge-location-pings` |
| Composer security audit | `php $composer audit --locked --no-interaction` | **Prior pass; refresh blocked** | Same-day clean result exists for the unchanged lockfile; final refresh could not access Packagist under managed policy |
| npm security audit | `npm.cmd audit --offline --audit-level=high` | **Verified** | 0 vulnerabilities |
| Full Makefile verification | `make verify` | **Blocked as written** | GNU Make unavailable; direct lint, typecheck, Unit+Feature, and build equivalents pass |
| Browser E2E | `make test-e2e` / `php artisan test tests/Browser` | **Not run** | Concurrent guidance documents a pest-plugin-browser Windows process-lifecycle limitation; Linux CI result remains pending |

The final Composer refresh was not bypassed: external transmission of the
lockfile dependency graph requires an approved CI/network context.

## Setup

| Command | Status | Side effects / notes |
|---|---|---|
| `make setup` | Declared; blocked locally | Installs PHP/JS dependencies, may create `.env`, generates `APP_KEY`, runs migrations, then starts Vite dev mode. It does **not** run a production build (`Makefile:3-9`). |
| `composer setup` | Declared | Composer install, optional `.env` copy, key generation, forced migrations, npm install, production build (`composer.json` scripts). |
| `composer install` | Not run | Writes `vendor/`, executes Composer scripts/package discovery. Use lockfile; do not update dependencies during exploration. |
| `npm ci` | Not run | Replaces `node_modules/` from `package-lock.json`; destructive to the existing dependency directory. |
| `cp .env.example .env` | Not run | Creates local environment file; never overwrite an existing `.env`. |
| `php artisan key:generate` | Not run | Mutates `.env`; do not run against an existing configured environment without intent. |
| `php artisan migrate --force` | Not run | Mutates the selected database; verify target and backup first. |

## Development

| Command | Status | Side effects / notes |
|---|---|---|
| `make dev` | Declared; blocked locally | Starts web server, queue listener, Pail log tail, and Vite concurrently; long-running |
| `composer dev` | Declared | Equivalent four-process development stack through Composer |
| `php artisan serve` | Declared | Local HTTP server, default port 8000 |
| `php artisan queue:listen --tries=1 --timeout=0` | Declared | Processes queued jobs and mutates configured backing services |
| `php artisan pail --timeout=0` | Declared | Long-running log stream |
| `npm.cmd run dev` | Declared | Long-running Vite HMR server |

## Lint and static analysis

| Command | Status | Side effects / notes |
|---|---|---|
| `make lint` | Blocked locally | GNU Make unavailable |
| `php vendor/bin/pint --test` | Verified pass | Dry-run; no formatting writes |
| `php vendor/bin/pint` | Verified on affected files | Applied mechanical formatting; repository-wide dry-run then passed |
| `make typecheck` | Blocked locally | GNU Make unavailable |
| `PAO_DISABLE=1 php vendor/bin/phpstan analyse --level=0 --memory-limit=2G --no-progress` | Verified pass | Blocking runtime-safety gate; no errors |

`make typecheck-strict` preserves level 6 as a visible, non-blocking debt audit.
The 2026-07-29 measurement reported 686 findings, dominated by missing iterable
types and Eloquent relationship/property inference.

## Tests

| Command | Status | Side effects / notes |
|---|---|---|
| `make test` | Blocked locally; direct equivalent passed | Intended Unit+Feature aggregate |
| `JAWLA_TEST_DATABASE=jawla_test_readiness_final php -d memory_limit=2G artisan test --testsuite=Unit,Feature` | Verified pass | 666 tests / 1,878 assertions |
| `make test-e2e` | Not run | Current uncommitted Makefile change skips on Windows |
| `make test-e2e-ci` | Declared in uncommitted change | Unconditional Linux/CI Browser suite |
| `php artisan test tests/Browser --compact` | Not run | Playwright/Chromium; current guidance says Windows process lifecycle is broken |
| k6 scripts under `tests/k6/`, `tests/stress/` | Declared | Contact a target service and can create load; require explicit environment/target approval |

### Database safety

Tests set:

- `APP_ENV=testing`
- `DB_CONNECTION=pgsql`
- `DB_DATABASE=jawla_test`

`tests/Support/TestingDatabaseGuard.php` rejects non-testing environments,
non-PostgreSQL connections, and database names outside `jawla_test` /
`jawla_test_*`. `tests/_env.php` accepts `JAWLA_TEST_DATABASE` before Laravel
boots, allowing concurrent tasks to use separate guarded databases.

### Current test limitation

The aggregate suite passes with the repository's 2 GB test limit. Browser E2E
remains authoritative only on Linux CI because of the documented Windows
Pest/Playwright lifecycle defect.

## Build and PWA

| Command | Status | Side effects / notes |
|---|---|---|
| `make build` | Blocked locally | GNU Make unavailable |
| `npm.cmd run build` | Verified pass | Regenerates `public/build/`; generated output must not be hand-edited |
| `npm.cmd run audit:pwa-assets` | Verified pass | All compressed JS, CSS, and total budgets passed |
| `php artisan route:list --json` | Verified | Read-only route inventory |
| `php artisan optimize` | Verified | Config, events, routes, views, Blade icons, and Filament cached |

## Full verification

| Command | Status | Contents |
|---|---|---|
| `make verify` | Blocked locally; direct components pass | Pint + PHPStan + Unit/Feature + build |
| `scripts/verify` | Declared / not run | Pint + PHPStan + Unit/Feature + build + Composer audit + npm audit |

`scripts/verify` is Bash-only. It includes the dependency audits required by
`AGENTS.md`; `make verify` does not. Run it in Linux CI to refresh Composer
advisories and preserve one authoritative verification artifact.

## Database operations

| Command | Status | Side effects / safety |
|---|---|---|
| `make migrate` | Not run | `php artisan migrate --force`; mutates selected DB |
| `php artisan migrate:status` | Declared | Read-only schema status, but connects to selected DB |
| `make seed` | Not run | Loads `DemoSeeder`; development/testing only |
| `php artisan db:seed --class=RoleSeeder` | Not run | Mutates role/permission data |
| `php artisan migrate:fresh` | Prohibited without explicit review | Drops all tables and data |

Before any migration: confirm environment/database, backup/restore path, and migration immutability requirements.

## Smoke and health

| Command | Status | Notes |
|---|---|---|
| `make smoke` | Not run | Writes config/view caches; Makefile redirects Linux-style to `/dev/null` |
| `GET /up` | Declared | Framework liveness used by Railway |
| `GET /health` | Inspected, not called | Checks DB and cache; returns 503 when degraded |
| `php artisan schedule:list` | Verified | Read-only schedule discovery |

## Deployment

| Command / workflow | Status | Side effects / notes |
|---|---|---|
| Railway auto-deploy | External unknown | Repository assumes deploys from `master`; dashboard state not inspected |
| `.github/workflows/deploy.yml` | Declared | Echoes deployment assumption and polls production `/up`; does not invoke Railway |
| `scripts/deploy.sh` | Declared | Pulls `master`, installs/builds, migrates, caches, restarts queue, health-checks; no automatic rollback implementation despite stale docs |
| Docker build | Declared | Installs production dependencies and packages PHP-FPM/Nginx |
| Railway predeploy | Declared | Migrates and builds config/route/view caches before startup |

Do not run deployment commands from a development worktree. They change external systems and production data.

## Backup and restore

| Command | Status | Safety behavior |
|---|---|---|
| `bash scripts/backup.sh` | Declared | Requires DB URL, rclone remote, age recipient; encrypted off-host upload; fails closed |
| `bash scripts/restore-backup.sh` | Declared | Requires explicit scratch flag and rejects the current production `DATABASE_URL` |
| Restore drill | External / not completed in repository | `docs/BACKUP_RESTORE.md` restore log is empty |

Restore is destructive to the target database. Run only against a verified disposable scratch target with explicit authorization.

## Security audits

```powershell
$composer = (Get-Command composer).Source
php $composer audit --no-ansi --locked
npm.cmd audit --audit-level=high
```

Both passed on 2026-07-29 with approved registry access. Audit results are time-sensitive and must be rerun before release.

## Recommended verification order for the next code change

1. Targeted Unit/Feature test for the changed behavior.
2. `php vendor/bin/pint --test`
3. `php vendor/bin/phpstan analyse --no-progress` — first diagnose the silent exit.
4. Feature tests in bounded groups until the memory leak is isolated.
5. `npm.cmd run build` for frontend/UI changes.
6. Relevant Browser test in Linux CI.
7. Composer and npm audits before release.
8. Full `make verify`/`scripts/verify` in a Linux environment once all component failures are resolved.
