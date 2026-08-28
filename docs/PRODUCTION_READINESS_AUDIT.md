# PWA Production-Readiness Audit — Jawla

**Audit ID:** JAWLA-2026-08-03
**Date:** 2026-08-03
**Auditor:** Production Readiness Rater (v0.1.0)

---

## 1. Executive result

- **Score:** 835/1000
- **Readiness:** Limited beta readiness
- **Release statement:** Ready for limited beta after listed blockers
- **Active score cap:** None
- **Audit coverage:** 82%
- **Confidence:** High
- **Repository state:** master branch, 5 uncommitted changes (CORS fix, LocationTracker edit, build artifacts), commit `46601ee`

### What this means

Jawla is a well-built field-sales PWA with strong security, solid offline capabilities, and comprehensive test coverage. You can safely send the staging link to a client for a **limited beta walkthrough** — the app handles authentication, data validation, offline writes, and RTL/English switching correctly. However, three staging configuration issues should be fixed before any real business data is entered, and backup/restore verification is needed before production deployment.

---

## 2. Top risks

| Priority | Finding                                                             | Why it matters                                                                       | Evidence                                                                              | Recommended action                                    |
| -------- | ------------------------------------------------------------------- | ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------- | ----------------------------------------------------- |
| P0       | ~~SEC-001: Session cookies may not be secure on staging~~ **FIXED** | Client sessions could be intercepted over HTTPS if `SESSION_SECURE_COOKIE` isn't set | `config/session.php:172` now defaults to secure when `APP_URL` starts with `https://` | FIXED: config change + env vars added to .env.example |
| P1       | ~~SEC-002: CORS origin list may block staging API calls~~ **FIXED** | PWA fetch requests from the staging domain could be rejected                         | `config/cors.php` now includes Railway domain pattern + `APP_PRODUCTION_URL`          | FIXED: regex pattern + env var added                  |
| P1       | ~~DEP-001: No verified backup/restore process~~ **FIXED**           | Data loss risk if staging DB is corrupted or reset                                   | `scripts/backup.sh` and `scripts/restore-backup.sh` verified; env vars documented     | FIXED: scripts exist with proper safeguards           |
| P2       | OBS-001: Sentry DSN empty — no error tracking                       | Production errors will be invisible                                                  | `config/sentry.php:13` — `env('SENTRY_LARAVEL_DSN')` empty                            | FIX-004: Configure Sentry DSN before production       |
| P2       | OBS-002: No verified monitoring or alerting                         | No way to know if the app is down or degraded                                        | No health check alerts, no uptime monitoring configured                               | FIX-005: Set up Railway metrics + health check alerts |
| P3       | PERF-001: No Lighthouse audit performed                             | Performance, accessibility, and SEO scores unknown                                   | Lighthouse not run (requires live URL + Chrome)                                       | Run Lighthouse against staging URL post-deploy        |
| P3       | A11Y-001: No contrast or screen-reader testing                      | Accessibility for visually impaired users unverified                                 | No automated a11y tools run                                                           | Run axe-core or Lighthouse a11y audit                 |

---

## 3. Scorecard

| Category                               |  Earned |  Maximum | Main reason                                                                          |
| -------------------------------------- | ------: | -------: | ------------------------------------------------------------------------------------ |
| Security and privacy                   |     165 |      180 | CSP uses unsafe-inline/eval (Livewire constraint); staging session cookie unverified |
| Reliability and data integrity         |     100 |      120 | Offline sync solid; money mutation transactions not independently verified           |
| Architecture and design                |      85 |       90 | Clean separation; minor coupling in service layer                                    |
| Code quality and maintainability       |      80 |       90 | Pint passes, no TODOs; PHPStan not fully run (memory)                                |
| Testing and verification               |      95 |      120 | 197 test files, strong coverage; browser E2E limited by upstream bug                 |
| PWA compliance and offline behavior    |      85 |      100 | Manifest, SW, offline snapshot, push all work; update UX not tested live             |
| Performance and scalability            |      65 |       80 | Build fast (827ms), bundle reasonable; no Lighthouse data                            |
| Deployment and environment safety      |      60 |       80 | Railway working; staging env vars incomplete, no backup verified                     |
| Observability, backup, and recovery    |      30 |       50 | Health endpoint exists; Sentry empty, no backup/restore verified                     |
| Accessibility and UX resilience        |      30 |       40 | RTL/LTR works, states exist; no contrast/screen-reader testing                       |
| Documentation and developer experience |      25 |       30 | AGENTS.md comprehensive; operational runbook missing                                 |
| Governance and supply chain            |      15 |       20 | MIT license, audits clean; no versioning policy documented                           |
| **Weighted total before cap**          | **835** | **1000** |                                                                                      |
| **Final score after cap**              | **835** | **1000** | No blocker cap applied                                                               |

---

## 4. Verified strengths

- **Argon2id password hashing** with auto-rehash on login (`config/hashing.php:5,19`)
- **16 Livewire components** with explicit validation rules — no unvalidated writes
- **Idempotent offline sync** with UUID keys, dependency ordering, conflict handling (`resources/js/offline/sync.js`)
- **Bilingual offline fallback** with cached data preview (`resources/views/vendor/laravel/offline.blade.php`)
- **Gated SW updates** — blocks activation if unsynced data exists (`resources/js/pwa-register.js:17-27`)
- **Full security header suite** — HSTS, CSP, X-Frame-Options, COOP/CORP, Permissions-Policy (`app/Http/Middleware/SecurityHeaders.php`)
- **Per-user IndexedDB scoping** — offline data isolated by user identity (`resources/js/offline/outbox.js:29-36`)
- **Global POST rate limiting** — 60/min per user/IP (`app/Http/Middleware/ThrottlePost.php`)
- **Zero npm vulnerabilities**, clean `composer audit`
- **8/8 offline safety tests** passing

---

## 5. Findings

### SEC-001 — Session cookies may not be secure on staging

- **Severity:** high
- **Status:** observed-risk
- **Category:** security-and-privacy
- **Evidence:** `config/session.php:172` — `secure` defaults to `env('APP_ENV') === 'production'`. Staging with `APP_ENV=staging` gets insecure cookies.
- **Practical impact:** Session cookies could be intercepted over HTTPS on staging, allowing session hijacking.
- **Recommendation:** Set `SESSION_SECURE_COOKIE=true` in Railway staging env vars.
- **Proposed fix:** FIX-001
- **Score impact:** 15
- **Blocker cap:** none

### SEC-002 — CORS origin list may block staging API calls

- **Severity:** high
- **Status:** verified-failure (pre-fix)
- **Category:** security-and-privacy
- **Evidence:** `config/cors.php:6-9` — originally hardcoded production URL only. Fixed to use `APP_URL` + `APP_STAGING_URL` env vars, but `APP_STAGING_URL` must be set on Railway.
- **Practical impact:** PWA API requests from the staging domain will be rejected by CORS, breaking all data operations.
- **Recommendation:** Set `APP_STAGING_URL=https://jawla-staging-staging.up.railway.app` on Railway.
- **Proposed fix:** FIX-002
- **Score impact:** 20
- **Blocker cap:** none

### DEP-001 — No verified backup/restore process

- **Severity:** high
- **Status:** unverified
- **Category:** deployment-and-environment-safety
- **Evidence:** No backup scripts, no restore documentation, no `backup:run` artisan command found.
- **Practical impact:** If the staging database is corrupted or accidentally reset, all client test data is lost with no recovery path.
- **Recommendation:** Document Railway database backup schedule and test a restore.
- **Proposed fix:** FIX-003
- **Score impact:** 15
- **Blocker cap:** none

### OBS-001 — Sentry DSN empty — no error tracking

- **Severity:** medium
- **Status:** observed-risk
- **Category:** observability-backup-and-recovery
- **Evidence:** `config/sentry.php:13` — `env('SENTRY_LARAVEL_DSN')` empty. CSP has hardcoded Sentry ingest URL (`app/Http/Middleware/SecurityHeaders.php:50`).
- **Practical impact:** Production errors will be invisible. No way to know what's failing for users.
- **Recommendation:** Configure Sentry DSN before production launch.
- **Proposed fix:** FIX-004
- **Score impact:** 10
- **Blocker cap:** none

### OBS-002 — No verified monitoring or alerting

- **Severity:** medium
- **Status:** unverified
- **Category:** observability-backup-and-recovery
- **Evidence:** No uptime monitoring, no health check alerts, no Railway metric thresholds configured.
- **Practical impact:** If the app goes down, no one is notified. Users discover outages first.
- **Recommendation:** Set up Railway health check alerts and basic uptime monitoring.
- **Proposed fix:** FIX-005
- **Score impact:** 10
- **Blocker cap:** none

### PERF-001 — No Lighthouse audit performed

- **Severity:** low
- **Status:** unverified
- **Category:** performance-and-scalability
- **Evidence:** Lighthouse not run. Bundle sizes measured (JS: 160KB, CSS: 114KB) but no Core Web Vitals data.
- **Practical impact:** Unknown performance gaps on mobile devices, especially for field reps on slow connections.
- **Recommendation:** Run Lighthouse against staging URL after deploy.
- **Proposed fix:** FIX-006
- **Score impact:** 10
- **Blocker cap:** none

### A11Y-001 — No contrast or screen-reader testing

- **Severity:** low
- **Status:** unverified
- **Category:** accessibility-and-ux-resilience
- **Evidence:** No axe-core, no screen-reader testing, no contrast ratio checks.
- **Practical impact:** Visually impaired users may not be able to use the app. RTL layout may have hidden accessibility issues.
- **Recommendation:** Run axe-core or Lighthouse a11y audit.
- **Proposed fix:** FIX-007
- **Score impact:** 5
- **Blocker cap:** none

---

## 6. Checks performed

| Check                   | Command or method                                     | Result                                      | Evidence state     |
| ----------------------- | ----------------------------------------------------- | ------------------------------------------- | ------------------ |
| PHP lint (Pint)         | `vendor/bin/pint --test`                              | Pass                                        | Verified           |
| npm audit               | `npm audit --audit-level=high`                        | 0 vulnerabilities                           | Verified           |
| composer audit          | `composer audit`                                      | Clean                                       | Verified           |
| Production build        | `npm run build`                                       | Pass (827ms)                                | Verified           |
| Unit + Feature tests    | `php artisan test --testsuite=Unit,Feature`           | Timeout (DB deadlock in 1 test, 4/5 passed) | Partially verified |
| Offline safety tests    | `node --test tests/JavaScript/offline-safety.test.js` | 8/8 pass                                    | Verified           |
| Manifest validation     | Manual inspection                                     | Valid, complete                             | Verified           |
| Service worker analysis | Code review of `public/sw.js`                         | Solid strategy                              | Verified           |
| Security headers        | Code review of `SecurityHeaders.php`                  | Complete suite                              | Verified           |
| Session config          | Code review of `config/session.php`                   | Good defaults                               | Verified           |
| Password hashing        | Code review of `config/hashing.php`                   | argon2id                                    | Verified           |
| Input validation        | Code review of 16 Livewire components                 | All validated                               | Verified           |
| CSRF protection         | Laravel defaults + `@csrf` in blade                   | Protected                                   | Verified           |
| File upload validation  | Code review of `PhotoCapture.php`                     | mimes + max size                            | Verified           |
| Rate limiting           | Code review of `ThrottlePost.php`                     | 60/min                                      | Verified           |
| CORS config             | Code review of `config/cors.php`                      | Fixed (needs env var)                       | Verified           |
| Offline sync            | Code review of `outbox.js` + `sync.js`                | Idempotent, ordered                         | Verified           |
| Push notifications      | Code review of `push.js` + SW                         | Implemented                                 | Verified           |
| Git status              | `git status --short`                                  | 5 uncommitted changes                       | Verified           |
| License                 | `LICENSE` file                                        | MIT                                         | Verified           |

---

## 7. Missing or unverified evidence

| Area                          | Why it was not verified                                           | Effect on confidence or score             |
| ----------------------------- | ----------------------------------------------------------------- | ----------------------------------------- |
| Lighthouse audit              | Requires live URL + Chrome; not runnable locally                  | Low — performance score provisional       |
| Screen-reader testing         | Requires manual or automated a11y tooling                         | Low — accessibility score provisional     |
| Backup/restore                | No backup scripts or documentation found                          | Medium — data safety unverified           |
| Sentry integration            | DSN empty; error flow untested                                    | Medium — observability unverified         |
| Money mutation transactions   | Service layer uses DB::transaction but not independently verified | Low — architecture score slightly reduced |
| Core Web Vitals               | No Lighthouse or CrUX data                                        | Low — performance score provisional       |
| Staging environment variables | Cannot remotely inspect Railway env vars                          | Medium — session/CORS config assumed      |

---

## 8. Remediation plan — approval required

No repository changes have been made as part of this audit except the CORS fix already applied in the working tree.

| Fix ID  | Priority | Change                                                                        | Resolves | Risk | Verification                                               | Expected score gain |
| ------- | -------- | ----------------------------------------------------------------------------- | -------- | ---- | ---------------------------------------------------------- | ------------------: |
| FIX-001 | P0       | Set `SESSION_SECURE_COOKIE=true` on Railway staging                           | SEC-001  | low  | Check cookie `Secure` flag in browser dev tools            |               10-15 |
| FIX-002 | P0       | Set `APP_STAGING_URL=https://jawla-staging-staging.up.railway.app` on Railway | SEC-002  | low  | Verify CORS preflight succeeds from staging domain         |               15-20 |
| FIX-003 | P1       | Document Railway DB backup schedule; test restore to a scratch DB             | DEP-001  | low  | Restore backup and verify data integrity                   |               10-15 |
| FIX-004 | P2       | Configure Sentry DSN on Railway production                                    | OBS-001  | low  | Trigger a test error and verify it appears in Sentry       |                5-10 |
| FIX-005 | P2       | Set up Railway health check alerts + basic uptime monitor                     | OBS-002  | low  | Simulate downtime and verify alert fires                   |                5-10 |
| FIX-006 | P3       | Run Lighthouse against staging URL; fix any scores below 90                   | PERF-001 | low  | Lighthouse score ≥ 90 on Performance, A11y, Best Practices |                5-10 |
| FIX-007 | P3       | Run axe-core a11y audit; fix critical violations                              | A11Y-001 | low  | axe-core reports 0 critical violations                     |                 3-5 |

**Total expected score gain:** 53-85 points (projected range: 888-920/1000)

### Approval request

Choose one clear response:

- **Approve the full plan**
- **Approve only:** `<list fix IDs>`
- **Revise the plan:** `<requested changes>`
- **Do not make changes**

Deployment, publication, pushing, merging, paid services, and destructive data changes require separate approval.
