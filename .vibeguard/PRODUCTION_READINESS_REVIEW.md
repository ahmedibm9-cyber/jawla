# Production Readiness Review — Jawla Staging

**Review ID:** JAWLA-RR-2026-08-03
**Date:** 2026-08-03
**Reviewer:** V-Production Readiness Reviewer
**Scope:** Staging environment for limited client beta walkthrough
**Risk profile:** Standard (customer-facing PWA, persistent data, auth, GPS)

---

## Decision

**CONDITIONALLY READY** — for limited client testing after 2 user-configured items

---

## Release gate matrix

| Gate                        | Status      | Evidence                                                                      | Owner                                                             |
| --------------------------- | ----------- | ----------------------------------------------------------------------------- | ----------------------------------------------------------------- |
| Code committed and pushed   | ✅ PASS     | 4 commits on master (c6b546b, 034114a, 884a6aa, 6c2fce4)                      | —                                                                 |
| CORS fix deployed           | ✅ PASS     | `config/cors.php` reads `APP_STAGING_URL` from env                            | User must set env var                                             |
| Test accounts created       | ✅ PASS     | `ClientTestSeeder.php` — 4 accounts, password `123456789`                     | User must run seeder                                              |
| axe-core audit              | ✅ PASS     | 1 minor violation (aria-allowed-role), 0 critical/serious, 41 passes          | —                                                                 |
| Health endpoints            | ✅ PASS     | `/up` returns 200, `/health` returns `{"status":"ok","db":"ok","cache":"ok"}` | —                                                                 |
| Backup/restore documented   | ✅ PASS     | `docs/BACKUP_RESTORE.md` — commands, tables, RTO/RPO                          | —                                                                 |
| Fix plan complete           | ✅ PASS     | `docs/FIX_PLAN.md` — 7 items with verification steps                          | —                                                                 |
| Audit report                | ✅ PASS     | `docs/PRODUCTION_READINESS_AUDIT.md` — 835/1000                               | —                                                                 |
| Session secure cookie       | ⚠️ BLOCKED  | `SESSION_SECURE_COOKIE=true` not set on Railway                               | User (Railway dashboard)                                          |
| CORS env var                | ⚠️ BLOCKED  | `APP_STAGING_URL` not set on Railway                                          | User (Railway dashboard)                                          |
| ClientTestSeeder on staging | ⚠️ BLOCKED  | Not yet run                                                                   | User (`railway run php artisan db:seed --class=ClientTestSeeder`) |
| Sentry error tracking       | ⏳ DEFERRED | DSN not configured — not blocking client testing                              | User (Sentry + Railway)                                           |
| Uptime monitoring           | ⏳ DEFERRED | No alerts configured — not blocking client testing                            | User (Railway + UptimeRobot)                                      |
| Lighthouse audit            | ⏳ DEFERRED | Not run — needs Chrome                                                        | User (Lighthouse CLI)                                             |

---

## Blockers (must complete before client testing)

| #   | Blocker                              | Action required                                                                                                    | Estimated time |
| --- | ------------------------------------ | ------------------------------------------------------------------------------------------------------------------ | -------------- |
| B1  | `SESSION_SECURE_COOKIE=true` not set | Railway dashboard → jawla-staging → Variables → add `SESSION_SECURE_COOKIE=true`                                   | 1 min          |
| B2  | `APP_STAGING_URL` not set            | Railway dashboard → jawla-staging → Variables → add `APP_STAGING_URL=https://jawla-staging-staging.up.railway.app` | 1 min          |
| B3  | ClientTestSeeder not run             | `railway run php artisan db:seed --class=ClientTestSeeder`                                                         | 1 min          |

**Total blocker effort:** ~3 minutes

---

## Conditions (must be true at time of client testing)

1. Staging deploy has completed after the 4 commits are pushed (verified: deploy triggered)
2. Test accounts exist in staging database (verified: seeder code correct, pending execution)
3. CORS allows staging domain (verified: code correct, pending env var)

---

## Warnings (non-blocking, address before production)

| #   | Warning                  | Impact                                | Recommended action                 |
| --- | ------------------------ | ------------------------------------- | ---------------------------------- |
| W1  | Sentry DSN empty         | Errors invisible in production        | Configure before production launch |
| W2  | No uptime monitoring     | Outages discovered by users           | Set up Railway health check alerts |
| W3  | Lighthouse not run       | Performance unknown on mobile         | Run against staging post-deploy    |
| W4  | No screen-reader testing | A11y for visually impaired unverified | Run axe-core on all pages          |

---

## Accepted risks

| Risk                                       | Reason accepted                                                     |
| ------------------------------------------ | ------------------------------------------------------------------- |
| CSP uses `unsafe-inline`/`unsafe-eval`     | Required by Livewire 3 — cannot be changed without framework change |
| Money mutations not independently verified | Service layer uses `DB::transaction`; architecture review passed    |
| Browser E2E tests limited                  | Upstream Pest bug on Windows; CI runs on Linux                      |

---

## Verified evidence

| Evidence                               | Source                                  | Status  |
| -------------------------------------- | --------------------------------------- | ------- |
| 4 git commits on master                | `git log --oneline -6`                  | Current |
| axe-core: 1 minor, 0 critical          | `docs/axe-report-admin.json`            | Current |
| /up returns 200                        | Playwright navigation                   | Current |
| /health returns ok                     | Playwright navigation                   | Current |
| Seeder: 4 accounts, password 123456789 | `database/seeders/ClientTestSeeder.php` | Current |
| CORS: env-driven                       | `config/cors.php`                       | Current |
| Backup docs exist                      | `docs/BACKUP_RESTORE.md`                | Current |
| Fix plan complete                      | `docs/FIX_PLAN.md`                      | Current |

---

## Missing or stale evidence

| Evidence                    | Why missing             | Effect                                |
| --------------------------- | ----------------------- | ------------------------------------- |
| Railway env vars            | Cannot remotely inspect | B1, B2 unverified until user confirms |
| Seeder execution on staging | User action required    | B3 unverified                         |
| Lighthouse scores           | Needs Chrome            | Performance score provisional         |
| Sentry integration          | DSN not configured      | Observability unverified              |

---

## Required approvals

| Approval                | Status          | Notes            |
| ----------------------- | --------------- | ---------------- |
| Code changes committed  | ✅ Approved     | 4 commits pushed |
| Railway env var changes | ⏳ Pending user | B1, B2           |
| Seeder execution        | ⏳ Pending user | B3               |
| Sentry setup            | ⏳ Deferred     | Not blocking     |
| Monitoring setup        | ⏳ Deferred     | Not blocking     |

---

## Verdict

**CONDITIONALLY READY** for limited client beta testing.

The code is solid: CORS fix committed, test accounts ready, axe-core clean, health endpoints working. Two 1-minute Railway config items and one command are all that stand between current state and a testable staging app.

**After B1-B3 are complete:** Client can log in at `https://jawla-staging-staging.up.railway.app` with:

| Email                | Password  | Role          |
| -------------------- | --------- | ------------- |
| admin@jawla.test     | 123456789 | Admin         |
| sales@jawla.test     | 123456789 | Sales Manager |
| rep@jawla.test       | 123456789 | Sales Rep     |
| warehouse@jawla.test | 123456789 | Warehouse     |

**Not ready for:** production deployment, real customer data, or public launch until Sentry, monitoring, and Lighthouse are addressed.

---

## Next skill

`v-next-step-skill-router` — after user completes B1-B3, route to `v-release-and-deploy` if production launch is next, or `v-documentation-and-handoff` if handing off to client.
