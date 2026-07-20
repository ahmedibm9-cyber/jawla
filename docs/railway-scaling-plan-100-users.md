# Railway 100-User Scaling Plan

Date: 2026-07-20
App: Jawla
Target: fast, stable operation for about 100 real concurrent users

## Current Baseline

From the latest verified production measurements:

- Default test suite: `183 passing, 623 assertions`
- Browser suite: `9 passing, 22 assertions`
- Railway app URL: `https://jawla-production.up.railway.app`
- API/read stress p95: about `840 ms`
- Login stress p95: about `7.1 s`

Important context:

- Production still runs on `php artisan serve`
- Sessions are database-backed
- Cache is database-backed
- Login is intentionally rate-limited at `5/min per IP+email`

That means the app is currently functional and reasonably healthy for light/medium usage, but it is not yet shaped like a production-grade 100-user Railway deployment.

---

## 1. 100-User Scaling Architecture Plan For Railway

## Goal

Support about 100 real concurrent users with acceptable response times during normal rep/admin usage, while keeping security rules intact.

## Target Architecture

### App Runtime

- Replace `php artisan serve`
- Move to a production-grade PHP runtime on Railway:
  - preferred safe path: `Nginx + PHP-FPM`
  - strong alternative: `FrankenPHP`
  - optional later path: `Laravel Octane + RoadRunner`

Why:

- `php artisan serve` is single-threaded and becomes the main tail-latency bottleneck under concurrent traffic.

### Railway Services

Run at least these services/components:

1. `web`
   - Jawla HTTP app
   - minimum `2 replicas`
2. `postgres`
   - primary relational database
3. `redis`
   - cache
   - sessions
   - queue backend if adopted
4. `worker`
   - queue worker for async notifications/jobs if moved beyond inline sync behavior

### Request/Data Flow

1. User hits Railway edge/load balancer
2. Request goes to one of 2+ app replicas
3. App replica reads session from Redis
4. Hot cached reads come from Redis where possible
5. Persistent records remain in PostgreSQL
6. Slow/non-blocking work moves to queue worker where appropriate

### App Configuration Targets

- `SESSION_DRIVER=redis`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis` when queue migration is ready
- Railway health check: `/up`
- warmup commands in `preDeployCommand`, not `startCommand`

### Production Topology Recommendation

#### Minimum 100-user target topology

- `2 web replicas`
- `1 postgres`
- `1 redis`
- `1 worker`

#### Safer topology once traffic grows

- `3 web replicas`
- `1 postgres` with monitored connection limits
- `1 redis`
- `1-2 workers`

### Capacity Intent

This architecture is designed to make 100 concurrent users realistic by addressing the actual bottlenecks:

- single-threaded PHP runtime
- DB-backed session/cache hot paths
- per-request repeated counts/queries
- no horizontal app concurrency

---

## 2. Step-By-Step Implementation Checklist

## Phase A — Runtime Foundation

1. Replace Railway `startCommand` runtime
2. Introduce production-grade web server/runtime
3. Keep `preDeployCommand` for:
   - `php artisan migrate --force`
   - `php artisan config:cache`
   - `php artisan view:cache`
4. Do not add `route:cache` until closure routes are removed
5. Confirm `/up` stays the Railway health endpoint

Definition of done:

- deploy succeeds reliably
- app serves without `php artisan serve`
- health check stays green

## Phase B — Session/Cache Concurrency

1. Add Redis service on Railway
2. Move sessions to Redis
3. Move cache to Redis
4. Validate login, logout, locale switching, and rep navigation across replicas

Definition of done:

- no DB-backed session hot path
- cache/session envs use Redis in production
- multi-replica requests remain stable

## Phase C — Horizontal Scaling

1. Increase app service to `2 replicas`
2. Verify sticky sessions are not required once Redis sessions are active
3. Monitor:
   - p95 latency
   - CPU
   - memory
   - DB connections

Definition of done:

- two replicas serve traffic correctly
- no auth/session breakage between replicas

## Phase D — DB Hot-Path Hardening

1. Profile the slowest real pages/endpoints
2. Add/verify indexes for:
   - notifications unread lookups
   - company-scoped list pages
   - login/user lookup fields
   - visits/orders filtering columns
3. Review N+1 risks in rep shell and admin login-adjacent flows

Definition of done:

- slow-query set reduced
- p95 improves under same load profile

## Phase E — Async Work Separation

1. Identify synchronous work on hot requests
2. Move safe background tasks to queue workers where possible
3. Add/verify dedicated Railway worker process

Definition of done:

- heavy non-critical work no longer blocks user response path

## Phase F — Realistic Capacity Testing

1. Expand K6 tests to use realistic traffic mix
2. Add wider credential pool for auth load
3. Distinguish:
   - read-heavy traffic
   - rep write flows
   - admin/reporting traffic
4. Set explicit SLOs:
   - API/read p95 target
   - login p95 target
   - acceptable error budget

Definition of done:

- 100-user claim is backed by realistic test evidence, not just burst survival

---

## 3. Priority List Of Code/Runtime Changes For This Repo

## Priority 0 — Must Do First

1. Replace `php artisan serve` in Railway production
   - File: `railway.toml`
   - Reason: current top concurrency bottleneck

2. Add Redis and move session/cache off PostgreSQL
   - Affects production env config and likely `.env` shape / deployment docs
   - Reason: reduces DB contention on hot request paths

3. Run 2 web replicas on Railway
   - Railway service config change
   - Reason: required for real horizontal concurrency

## Priority 1 — High Impact Repo Changes

4. Remove closure routes so `route:cache` becomes available
   - File: `routes/web.php`
   - Move route closures into controller/invokable handlers
   - Reason: route cache is currently blocked by closure routes

5. Keep `/up` as the only authoritative cheap health endpoint
   - File: `bootstrap/app.php` and route policy around `routes/web.php`
   - Reason: health probes must stay lightweight and consistent

6. Keep request-path queries minimal in shared layouts
   - File: `resources/views/layouts/app.blade.php`
   - Reason: shared layout work multiplies across every rep request

## Priority 2 — DB/Query Improvements

7. Audit unread notification query performance under production data
   - Files:
     - `resources/views/layouts/app.blade.php`
     - related notification models/tables

8. Audit rep list pages under load
   - Likely files:
     - `app/Livewire/App/Home.php`
     - `app/Livewire/App/Visits.php`
     - `app/Livewire/App/Orders.php`
     - `app/Livewire/App/Notifications.php`

9. Verify company-scoped indexes on hot tables
   - especially `notifications`, `visits`, `invoices`, `customers`, `stocks`

## Priority 3 — Testing/Observability

10. Replace single-account auth flood tests with broader credential pools
    - File: `tests/stress/k6-login-stress.js`
    - Reason: current auth stress is dominated by intentional rate limiting

11. Add realistic mixed workload K6 scenarios
    - Files under `tests/stress/`
    - Reason: 100 real users are not 100 simultaneous login attempts

12. Add a simple production perf dashboard/checklist
    - Latency
    - CPU
    - memory
    - DB connections
    - slow queries

---

## Recommended Execution Order

1. `railway.toml` runtime replacement
2. Railway Redis service + session/cache migration
3. 2 web replicas
4. closure-route cleanup so route caching becomes available
5. DB/query audit on rep shell + hot pages
6. realistic 100-user K6 campaign

---

## What "100 Users Fast" Should Mean

Use a clear operational definition:

- `100 real concurrent users`
- mostly normal rep/admin activity
- not 100 simultaneous login floods on one account/IP
- target outcomes:
  - read-heavy p95 < `1000 ms`
  - key write flows stay comfortably responsive
  - no material increase in request failure rate

---

## Bottom Line

To make Jawla work perfectly fast for about 100 real concurrent users on Railway, the first move is **not** another small Laravel micro-optimization. It is:

1. stop using `php artisan serve`
2. move sessions/cache to Redis
3. run multiple web replicas
4. then tune the app/query hot paths

That sequence will unlock far more capacity than trying to squeeze another few percent out of the current single-process production shape.
