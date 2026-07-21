# Deployment (Laravel Forge on Hetzner / DigitalOcean)

## Server baseline

- Ubuntu 24.04 LTS · PHP 8.3 · Nginx · PostgreSQL 16 · Supervisor.
- Cloudflare in front (proxy on, TLS full-strict).

## Environment

- `APP_ENV=production`, `APP_DEBUG=false`.
- `.env` values set in Forge; never in git.

## Deploy on push (main branch)

See `scripts/deploy.sh`. Steps: pull → `composer install --no-dev` →
`npm ci && npm run build` → `php artisan migrate --force` →
config/route/view caches → `queue:restart` → health check `/up` →
rollback on failure.

## Post-deploy checks

- Health endpoint returns 200.
- Sentry receives a test event.
- Nightly backup ran within the last 24 h.

## Railway production notes

- Health check path: `/up`
- Keep cache / sessions / queue on Redis in Railway production:
  - `SESSION_DRIVER=redis`
  - `SESSION_CONNECTION=default`
  - `SESSION_STORE=redis`
  - `CACHE_STORE=redis`
  - `QUEUE_CONNECTION=redis`
  - `REDIS_URL=${{Redis.REDIS_URL}}`
- Current source-controlled Railway scaling defaults:
  - `numReplicas = 2`
  - `PHP_CLI_SERVER_WORKERS = 4`
- `route:cache` is intentionally disabled until closure routes are removed from `routes/web.php`.

## Queue worker (Railway)

The app uses `QUEUE_CONNECTION=redis` in production. A queue worker is needed
for background jobs (PDF generation, notification dispatch, offline sync
replays). To set up on Railway:

1. Railway dashboard → New → Worker (from the same GitHub repo)
2. Start command: `php artisan queue:work redis --tries=3 --timeout=60`
3. Add the same `DB_*`, `REDIS_*`, `APP_*` env vars as the web service
4. Set `QUEUE_CONNECTION=redis`

The worker auto-restarts on deploy via `php artisan queue:restart` in the
deploy script.

## Resource limits

| Service       | CPU       | Memory | Replicas |
| ------------- | --------- | ------ | -------- |
| Web (PHP-FPM) | 0.5 vCPU  | 512MB  | 2        |
| Queue worker  | 0.25 vCPU | 256MB  | 1        |
| PostgreSQL    | 0.5 vCPU  | 1GB    | 1        |
| Redis         | 0.25 vCPU | 256MB  | 1        |

These are Railway defaults. Monitor with `railway metrics` and scale up if
response times exceed 2s or queue depth grows.
