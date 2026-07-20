# Issue Draft — Railway 100-User Scaling Execution Backlog

## Summary

Scale Jawla on Railway from the current "works for light/medium traffic" shape to a production-grade target that can sustain about 100 real concurrent users with acceptable latency.

## Current State

- App is deployed on Railway production and healthy
- Redis service now exists and production app vars are wired to Redis
- App service is now configured for 2 replicas
- Current runtime still uses `php artisan serve`
- Latest measured API/read p95 was sub-second, but runtime concurrency is still limited by the server model

## Goals

- Make 100 concurrent real users realistic on Railway
- Keep latency low for rep/admin normal workflows
- Preserve current security controls, especially auth throttling

## Done Already

- [x] Add Railway Redis service
- [x] Wire production `SESSION_DRIVER=redis`
- [x] Wire production `CACHE_STORE=redis`
- [x] Wire production `QUEUE_CONNECTION=redis`
- [x] Set `REDIS_URL` on the app service
- [x] Scale Railway web service to 2 replicas
- [x] Add `PHP_CLI_SERVER_WORKERS=4` as interim improvement while still on `php artisan serve`
- [x] Keep Railway health checks on `/up`
- [x] Source-control these defaults in `railway.toml`

## Remaining Execution Backlog

### P0 — Runtime

- [ ] Replace `php artisan serve` with a production-grade runtime
- [ ] Choose one path:
  - [ ] Nginx + PHP-FPM
  - [ ] FrankenPHP
  - [ ] Octane / RoadRunner
- [ ] Update Railway deployment config accordingly
- [ ] Re-run K6 baseline after runtime cutover

### P0 — Routing / Deploy Optimization

- [ ] Remove closure routes from `routes/web.php`
- [ ] Enable `route:cache` in production deploy flow after closures are removed

### P1 — Session / Cache / Queue Validation

- [ ] Verify login/logout/session persistence across 2 replicas
- [ ] Verify locale switching across replicas
- [ ] Verify no session regressions in Browser suite
- [ ] Decide whether to introduce a dedicated Railway worker service

### P1 — Hot Path Query Work

- [ ] Profile admin login request path
- [ ] Profile rep home page request path
- [ ] Profile orders / visits / notifications list pages
- [ ] Add or validate indexes for:
  - [ ] unread notifications lookups
  - [ ] company-scoped rep list filters
  - [ ] login/user lookup fields

### P1 — Load Testing

- [ ] Expand credential pool for K6 login stress
- [ ] Add mixed real-world workload scenario:
  - [ ] rep reads
  - [ ] rep writes
  - [ ] admin login/read flows
- [ ] Define SLO targets:
  - [ ] read-heavy p95
  - [ ] write-flow p95
  - [ ] acceptable error rate

### P2 — Operations

- [ ] Add a worker service if queued work becomes non-trivial
- [ ] Add production monitoring dashboard for:
  - [ ] p95 latency
  - [ ] CPU
  - [ ] memory
  - [ ] DB connections
  - [ ] slow query rate

## Acceptance Criteria

- [ ] 100 realistic concurrent users tested with mixed workload
- [ ] Production runtime no longer uses `php artisan serve`
- [ ] Session/cache hot paths backed by Redis
- [ ] 2+ replicas stable under load
- [ ] Measured p95 stays within agreed target

## References

- `docs/railway-scaling-plan-100-users.md`
- `docs/perf-report-2026-07-20-railway.md`
- `railway.toml`
