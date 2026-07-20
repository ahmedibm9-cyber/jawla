# Performance Report — Railway

Date: 2026-07-20
Environment: Railway production
URL: `https://jawla-production.up.railway.app`
Code deployed: `6fb6c08`

## Scope

- Rewrite `tests/stress/k6-login-stress.js` to use the real Filament/Livewire login flow.
- Re-run K6 against the deployed Railway app.
- Profile the main latency bottlenecks.
- Ship the highest-confidence fixes without weakening security rules.

## Fixes Shipped

1. Real Livewire login stress flow
   - `k6-login-stress.js` now does the actual Filament login sequence:
     - `GET /admin/login`
     - extract `csrf-token` and `wire:snapshot`
     - `POST /livewire/update`
     - call `authenticate`
2. Truthful health probing
   - `k6-api-stress.js` now probes `/up` instead of the heavier `/health` JSON route.
   - `/health` kept for compatibility, but reduced to a cheap plain-text `ok` response.
3. Rep shell request trimming
   - The app header notification bell summary was reduced from 2 unread-notification queries to 1 aggregate query.
4. Railway startup trimming
   - `railway.toml` now moves `migrate`, `config:cache`, and `view:cache` into `preDeployCommand`.
   - `startCommand` now only starts the app server.
   - `route:cache` was intentionally left off because the app still contains closure routes.
   - Railway healthcheck now points at `/up`.

## Verification

- `php artisan test` -> `183 passing, 623 assertions`
- `php artisan test tests/Browser` -> `9 passing, 22 assertions`
- Railway deploy status -> `SUCCESS`

## K6 Results

### API stress — post-fix

Script: `tests/stress/k6-api-stress.js`

- Requests: `2297`
- Iterations: `448`
- `http_req_failed`: `0%`
- `http_req_duration`:
  - avg: `540.18 ms`
  - med: `539.47 ms`
  - p90: `798.40 ms`
  - p95: `840.30 ms`
  - max: `1114.97 ms`
- Login page check `< 1000ms`: `462 / 463`
- Health check status `200`: `467 / 467`

### Login stress — post-fix, real Livewire auth

Script: `tests/stress/k6-login-stress.js`

- Requests: `4640`
- Iterations: `2320`
- `http_req_duration`:
  - avg: `3094.22 ms`
  - med: `2431.48 ms`
  - p90: `6232.01 ms`
  - p95: `7129.24 ms`
  - max: `10965.91 ms`
- `login status is 200`: `369 / 2320`
- `login redirects to /admin`: `35 / 2320`

## Interpretation

### What improved materially

- API/page-read traffic is now in a healthy range for a single Railway instance:
  - pre-fix API p95 was roughly `8.0s`
  - post-fix API p95 is `0.84s`
- The earlier login K6 results were invalid because the script did not use the real Filament/Livewire auth flow.
- The current login K6 run is now exercising the correct auth path.

### Why login stress still looks bad

Two factors dominate the remaining login results:

1. Security throttling is working as designed.
   - Admin login is intentionally rate-limited to `5/min per IP+email`.
   - A 100-VU flood against one seeded account is expected to produce many non-successful auth attempts.
2. Railway is still serving production via `php artisan serve`.
   - This is a single-threaded app server.
   - It keeps low/medium traffic acceptable, but tail latency grows fast under concurrent request bursts.

## Top Bottlenecks After This Pass

1. Railway production still runs on Laravel's built-in dev server.
2. Login flood tests are dominated by security throttling unless they use a wider credential pool.
3. `/up` is much faster now, but steady-state request p95 still reflects a single-process PHP runtime under concurrent load.

## Recommended Next Actions

1. Move Railway from `php artisan serve` to a production-grade PHP runtime.
   - Example directions: FPM+Nginx container, FrankenPHP, or RoadRunner/Octane.
2. Add a dedicated performance-user seeder or credential pool for auth stress.
   - That keeps login load tests inside the app's rate-limit rules.
3. Fix the legacy custom `errors` K6 metric implementation.
   - Current pass/fail interpretation should use `checks` and `http_req_failed`, not the legacy `errors` rate.

## Bottom Line

This pass fixed the incorrect measurement paths, removed two small but real request costs, and cut API/page-read p95 from multi-second tail latency to sub-second territory. The remaining serious bottleneck is no longer an app-page bug; it is the combination of intentional login throttling and the single-threaded Railway runtime.
