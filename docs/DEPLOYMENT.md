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
