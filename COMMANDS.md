# Jawla — Commands Reference

## Setup

| Command | Status | Side Effects |
|---------|--------|-------------|
| `make setup` | Declared | Installs composer deps, copies .env.example, generates app key, runs migrations, installs npm deps, builds assets |
| `composer install` | Declared | Installs PHP dependencies |
| `npm install` | Declared | Installs JS dependencies |
| `cp .env.example .env` | Declared | Creates .env file |
| `php artisan key:generate` | Declared | Generates APP_KEY |
| `php artisan migrate` | Declared | Runs database migrations |

## Development

| Command | Status | Side Effects |
|---------|--------|-------------|
| `make dev` | Declared | Starts artisan serve + queue worker + pail log tail + npm dev (concurrently) |
| `php artisan serve` | Declared | Starts local dev server on port 8000 |
| `npm run dev` | Declared | Starts Vite dev server with HMR |
| `php artisan queue:work` | Declared | Processes queued jobs |
| `php artisan pail` | Declared | Tails Laravel log files |

## Testing

| Command | Status | Side Effects |
|---------|--------|-------------|
| `make test` | Verified | Runs `php artisan test --testsuite=Unit,Feature` — 142 unit tests, 386 assertions pass |
| `make test-e2e` | Declared | Runs Playwright browser tests via `pest --testsuite=Browser` |
| `vendor/bin/pest tests/Unit` | Verified | 142 unit tests pass |
| `vendor/bin/pest tests/Feature` | Declared | Feature test suite |
| `vendor/bin/phpunit` | Declared | Underlying PHPUnit runner |

## Linting and Static Analysis

| Command | Status | Side Effects |
|---------|--------|-------------|
| `make lint` | Verified | `vendor/bin/pint --test` — dry-run, no file changes |
| `make typecheck` | Verified | `vendor/bin/phpstan analyse` — level 6, read-only |

## Build

| Command | Status | Side Effects |
|---------|--------|-------------|
| `make build` | Verified | `npm run build` — Vite production build |
| `npm run build` | Verified | Compiles assets to `public/build/` |

## Verification

| Command | Status | Side Effects |
|---------|--------|-------------|
| `make verify` | Verified | lint + typecheck + test + build (4 steps) |
| `scripts/verify` | Declared | pint + phpstan + test + npm build + composer audit + npm audit (6 steps) |

**Note:** `make verify` does NOT include `composer audit` or `npm audit`. `scripts/verify` does. The AGENTS.md definition-of-done requires 7 checks including audits — only `scripts/verify` satisfies this.

## Database

| Command | Status | Side Effects |
|---------|--------|-------------|
| `make migrate` | Declared | Runs `php artisan migrate` |
| `php artisan migrate` | Declared | Applies pending migrations |
| `php artisan migrate:fresh` | Declared | Drops all tables and re-migrates (DESTRUCTIVE) |
| `make seed` | Declared | Runs `php artisan db:seed` |
| `php artisan db:seed` | Declared | Runs seeders (demo data in non-production) |
| `php artisan db:seed --class=RoleSeeder` | Declared | Seeds roles only |

## Smoke Test

| Command | Status | Side Effects |
|---------|--------|-------------|
| `make smoke` | Declared | route:list + config:cache + view:cache |

## Deployment

| Command | Status | Side Effects |
|---------|--------|-------------|
| `scripts/deploy.sh` | Declared | Full deploy: pull, install, build, migrate, cache, restart queue, health check |
| `scripts/backup.sh` | Declared | pg_dump → age encryption → rclone upload |
| `scripts/restore-backup.sh` | Declared | Requires ALLOW_SCRATCH_RESTORE=1, refuses production DB |

## Security Audit

| Command | Status | Side Effects |
|---------|--------|-------------|
| `composer audit` | Verified | Checks PHP dependencies for known vulnerabilities |
| `npm audit --audit-level=high` | Verified | Checks JS dependencies for known vulnerabilities |

## CI/CD Workflows

| Workflow | Trigger | Jobs |
|----------|---------|------|
| `ci.yml` | push/PR to `main` | lint (Pint) + test (Pest with PostgreSQL) |
| `deploy.yml` | push to `master` | staging → production deploy + health check |
| `e2e.yml` | push/PR to `master` | Playwright browser tests (advisory) |
| `security.yml` | push/PR to `master` + weekly | Gitleaks + dependency audit + ZAP |

**CRITICAL:** `ci.yml` triggers on `main` but `deploy.yml` triggers on `master`. The default branch is `master`. CI never runs on the deploy branch. This must be fixed.

## Important Notes

- **Never run `migrate:fresh` in production** — destroys all data
- **Never run `db:seed` in production** — loads demo data
- **TestingDatabaseGuard** — test suite refuses to run unless `APP_ENV=testing`, `DB_CONNECTION=pgsql`, and DB name starts with `jawla_test`
- **Backups require** `AGE_RECIPIENT` and `RCLONE_REMOTE` env vars
- **Restore requires** `ALLOW_SCRATCH_RESTORE=1` and refuses production DB
