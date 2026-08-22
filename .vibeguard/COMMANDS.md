# Jawla Commands

## Setup

```bash
composer install                      # Install PHP dependencies
cp -n .env.example .env || true      # Copy env (create from .env.example if missing)
php artisan key:generate              # Generate APP_KEY
php artisan migrate --force            # Run all migrations (force re-run)
npm install                           # Install Node dependencies
npm run dev                           # Start Vite dev server
```

## Dev Server

```bash
make dev                              # Start local dev server (php artisan serve + Vite + queue + pail)
# Or manually: npx concurrently ... (see Makefile dev target)
```

## Lint / Typecheck

```bash
make lint                             # vendor/bin/pint --test (PHP syntax check)
make typecheck                        # phpstan analyse --level=0 (quick audit)
make typecheck-strict                 # phpstan analyse --level=6 (strict; may surface errors)
```

## Tests

```bash
make test                             # php artisan test --testsuite=Unit,Feature (with PAO_DISABLE=1)
make test:offline                     # node --test tests/JavaScript/offline-safety.test.js
make test:browser                     # Browser E2E — SKIP on Windows (pest-plugin-browser bug #1517);
                                     #        Run in CI (Linux) or use Laravel Dusk locally
make test:all                         # composer test + composer test:browser
```

## Database

```bash
make migrate                          # php artisan migrate --force
make seed                             # php artisan db:seed --class=DemoSeeder (requires JAWLA_MODE=demo)
make smoke                            # Basic smoke: route:list, config:cache, view:cache
```

## Build Assets

```bash
make build                            # npm run build (Vite production build)
```

## Full Verify

```bash
make verify                           # lint + typecheck + test + build (exits 0 when all pass)
```

## Railway Operations

```bash
railway variables set ...             # Set env vars on Railway (JAWLA_MODE, APP_KEY, DB_*, etc.)
railway up                            # Deploy to Railway
railway status                        # Check deployment status
railway logs                          # View build/deploy logs
railway deploy                        # Trigger new deployment
```

## Key Environment Variables

| Variable         | Value (local)                                         | Value (Railway prod)                      | Notes                             |
| ---------------- | ----------------------------------------------------- | ----------------------------------------- | --------------------------------- |
| `APP_ENV`        | `local`                                               | `production`                              | Affects config loading            |
| `APP_DEBUG`      | `true`                                                | `false`                                   | Error detail level                |
| `APP_URL`        | `http://localhost`                                    | `https://jawla-production.up.railway.app` | Must match deployed URL           |
| `JAWLA_MODE`     | `demo`                                                | `production` (or `demo`)                  | Controls seeder behavior          |
| `SESSION_DRIVER` | `database`                                            | `database`                                | Must match DB sessions table      |
| `CACHE_STORE`    | `file`                                                | `file`                                    | Affects Livewire /livewire/update |
| `DB_CONNECTION`  | `pgsql`                                               | `pgsql`                                   | PostgreSQL                        |
| `DB_HOST`        | `127.0.0.1`                                           | `postgres.railway.internal`               | Railway internal DNS              |
| `DB_PORT`        | `5432`                                                | `5432`                                    | PostgreSQL port                   |
| `DB_DATABASE`    | `jawla`                                               | `railway`                                 | Railway default DB name           |
| `DB_USERNAME`    | `postgres`                                            | `postgres`                                | DB superuser                      |
| `DB_PASSWORD`    | `postgres`                                            | `kqBLFViLuuGeLNMJGezIkpzDVmEjkThF`        | DB password from Railway          |
| `APP_KEY`        | `base64:csLw9dDFy7FsWvXhCWWefUPMvdABLaeKdBiKBcRJ4TQ=` | Must be set                               | Laravel encryption key            |
| `RAILWAY_TOKEN`  | —                                                     | Required for CI deploy                    | GitHub Actions secret             |

## Commands Verified During Exploration

| Command                                  | Status      | Notes                                             |
| ---------------------------------------- | ----------- | ------------------------------------------------- |
| `php artisan tinker`                     | ✅ Verified | Works; can run DB queries                         |
| `php artisan migrate --force`            | ✅ Verified | Runs migrations; may fail if cache table missing  |
| `php artisan db:seed --class=DemoSeeder` | ✅ Verified | Requires `JAWLA_MODE=demo`                        |
| `vendor/bin/pint --test`                 | ✅ Verified | PHP syntax formatting check                       |
| `vendor/bin/phpstan analyse --level=0`   | ✅ Verified | Quick PHPStan audit                               |
| `vendor/bin/phpstan analyse --level=6`   | ⚠️ Inferred | Strict mode; may surface many errors              |
| `npm audit --audit-level=high`           | ✅ Verified | 0 vulnerabilities after nanoid patch              |
| `npm run build`                          | ✅ Verified | Vite production build                             |
| `railway up`                             | ⚠️ Declared | Builds & deploys; may fail on phpredis rate-limit |
| `railway status`                         | ✅ Verified | Shows deployment status                           |
| `railway variables`                      | ✅ Verified | Lists all env vars for a service                  |
| `curl -I http://localhost:8000/`         | ✅ Verified | Check response headers                            |
| `php artisan route:list`                 | ✅ Verified | List all registered routes                        |
| `php artisan config:cache`               | ✅ Verified | Cache config; must match env vars                 |
