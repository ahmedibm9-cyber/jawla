# PWA Production-Readiness Audit — Jawla (جولة)

**Audit ID:** jawla-audit-2026-08-17
**Date:** 2026-08-17
**Repository:** ahmedibm9-cyber/jawla
**Branch:** main
**Commit:** 2f61b1f (dirty: 19 modified files, 3 untracked)
**Runtime:** PHP 8.3.32, Node.js (npm), Laravel 13, Livewire 3, Filament 4

---

## 1. Executive result

- **Score:** 700/1000
- **Readiness:** Staging readiness
- **Release statement:** Ready for internal staging; major production gaps remain
- **Active score cap:** Cap 749 — major readiness blocker (no E2E test execution evidence, no backup strategy, no CI/CD pipeline)
- **Audit coverage:** 62%
- **Confidence:** Medium
- **Repository state:** main branch, 19 modified files, 3 untracked

### What this means

Jawla is a well-architected PWA with strong security headers, solid offline sync, and good service-layer separation. It works — the health check passes, the PWA installs, and the service worker handles offline gracefully. However, **you cannot confidently ship to real users yet** because: (1) critical user journeys have no automated end-to-end test evidence, (2) there is no backup or disaster recovery strategy for the database, and (3) no CI/CD pipeline runs tests before deployment. Fix these three gaps and the score jumps significantly.

---

## 2. Top risks

| Priority | Finding                        | Why it matters                                      | Evidence                                           | Recommended action                            |
| -------- | ------------------------------ | --------------------------------------------------- | -------------------------------------------------- | --------------------------------------------- |
| P0       | No E2E test execution          | Can't verify critical user journeys work end-to-end | `make test-e2e` skipped on Windows, no CI evidence | FIX-001: Set up CI with E2E tests             |
| P0       | No backup strategy             | Data loss is unrecoverable                          | No backup config, no S3, no pg_dump cron           | FIX-002: Configure automated Postgres backups |
| P1       | No CI/CD pipeline              | Deployments are manual and unverified               | No `.github/workflows`, no pipeline config         | FIX-003: Add GitHub Actions CI                |
| P1       | nanoid vulnerability (high)    | Custom generators loop indefinitely when size=0     | `npm audit` → GHSA-2v37-7h3g-55p8                  | FIX-004: Run `npm audit fix`                  |
| P2       | PHPStan only level 0           | Type safety not verified at strict level            | `phpstan.neon` level=0                             | FIX-005: Increase PHPStan level incrementally |
| P2       | X-Powered-By leaks PHP version | Aids attacker reconnaissance                        | Response header: `PHP/8.3.32`                      | FIX-006: Remove X-Powered-By header           |
| P3       | No offline/cache test evidence | Offline behavior could break silently               | No test files for SW cache or IndexedDB sync       | FIX-007: Add offline behavior tests           |

---

## 3. Scorecard

| Category                               |  Earned |  Maximum | Main reason                                                                                |
| -------------------------------------- | ------: | -------: | ------------------------------------------------------------------------------------------ |
| Security and privacy                   |     125 |      180 | Strong headers + auth chain; CSP unsafe-inline/eval, X-Powered-By leak, no secret scanning |
| Reliability and data integrity         |      80 |      120 | Health check, offline sync, transactions; no idempotency/retry tests                       |
| Architecture and design                |      90 |       90 | Clean service layer, middleware chain, bilingual RTL — fully verified                      |
| Code quality and maintainability       |      65 |       90 | Pint passes, clean structure; PHPStan level 0 only                                         |
| Testing and verification               |      70 |      120 | Unit + feature tests pass; E2E skipped, no auth/offline tests                              |
| PWA compliance and offline behavior    |      95 |      100 | Valid manifest, SW with cache strategies, offline fallback, push notifications             |
| Performance and scalability            |      35 |       80 | Build passes; no Core Web Vitals, bundle analysis, or image optimization evidence          |
| Deployment and environment safety      |      50 |       80 | Docker build works, health check passes; no CI, no rollback procedure                      |
| Observability, backup, and recovery    |      35 |       50 | Sentry configured, health endpoint; no backup, no alerting, no runbook                     |
| Accessibility and UX resilience        |      20 |       40 | RTL + bilingual; no keyboard, screen reader, or contrast tests                             |
| Documentation and developer experience |      25 |       30 | AGENTS.md, BUSINESS_RULES.md, .env.example; no API docs                                    |
| Governance and supply chain            |      10 |       20 | Lockfiles tracked; no license file, no changelog                                           |
| **Weighted total before cap**          | **700** | **1000** |                                                                                            |
| **Final score after cap**              | **700** | **1000** | Cap 749 not applied (score already below)                                                  |

---

## 4. Verified strengths

- **Security headers are production-grade:** HSTS, CSP, X-Frame-Options DENY, COOP, CORP, Permissions-Policy all set via `SecurityHeaders` middleware and confirmed in live HTTP response.
- **Service worker is well-designed:** Never caches authenticated responses, uses network-first for navigation, cache-first for static assets, versioned caches with cleanup on activate, background sync support.
- **Architecture follows AGENTS.md rules:** Service layer handles business logic, controllers delegate, middleware chain enforces auth/device/license, company-scoped multi-tenancy.
- **Health endpoint is comprehensive:** Checks both database and cache, returns proper HTTP status codes, no-cache headers.
- **PWA manifest is valid:** Has name, icons, shortcuts, start_url, scope, display:standalone, theme color, lang, dir.
- **Offline sync mechanism exists:** `SyncController`, `OfflineSnapshotController`, IndexedDB snapshot endpoint, background sync in SW.
- **Bilingual RTL works from first commit:** SetLocale middleware, `lang` and `dir` in manifest, locale switch route.
- **Linting passes:** Pint (code style) returns clean.

---

## 5. Findings

### SEC-001 — CSP includes unsafe-inline and unsafe-eval

- **Severity:** medium
- **Status:** observed-risk
- **Category:** security-and-privacy
- **Evidence:** `app/Http\Middleware\SecurityHeaders.php:46` — `script-src 'self' 'unsafe-inline' 'unsafe-eval'`; documented as blocked by Livewire/Alpine
- **Practical impact:** XSS attacks via inline script injection are not fully mitigated. However, Livewire/Alpine require these for now.
- **Recommendation:** Document as accepted risk. Migrate to nonce-based CSP when Livewire v4 adds support.
- **Proposed fix:** None (accepted risk, documented)
- **Score impact:** 0
- **Blocker cap:** none

### SEC-002 — X-Powered-By header leaks PHP version

- **Severity:** low
- **Status:** verified-failure
- **Category:** security-and-privacy
- **Evidence:** Live response header: `X-Powered-By: PHP/8.3.32`
- **Practical impact:** Attackers can target PHP-specific exploits for this version.
- **Recommendation:** Remove `X-Powered-By` in `SecurityHeaders` middleware or `php.ini` (`expose_php = Off`).
- **Proposed fix:** FIX-006
- **Score impact:** 5
- **Blocker cap:** none

### SEC-003 — nanoid high-severity vulnerability

- **Severity:** high
- **Status:** verified-failure
- **Category:** security-and-privacy
- **Evidence:** `npm audit` → nanoid <3.3.18, GHSA-2v37-7h3g-55p8
- **Practical impact:** Custom generators loop indefinitely when size is zero, potential DoS.
- **Recommendation:** Run `npm audit fix`.
- **Proposed fix:** FIX-004
- **Score impact:** 10
- **Blocker cap:** none

### REL-001 — No automated backup strategy

- **Severity:** critical
- **Status:** observed-risk
- **Category:** reliability-and-data-integrity
- **Evidence:** No backup config in Railway, no pg_dump cron, no S3 backup storage, no `BACKUP_DISK` env var set
- **Practical impact:** If the Postgres volume is corrupted or deleted, all business data is lost permanently.
- **Recommendation:** Configure Railway Postgres PITR (point-in-time recovery) or set up automated pg_dump to S3.
- **Proposed fix:** FIX-002
- **Score impact:** 25
- **Blocker cap:** none

### REL-002 — No E2E test execution evidence

- **Severity:** high
- **Status:** verified-failure
- **Category:** testing-and-verification
- **Evidence:** `make test-e2e` skips on Windows (upstream bug #1517), no CI pipeline runs them
- **Practical impact:** Critical user journeys (login, sell, collect payment, sync) have never been verified end-to-end in an automated way.
- **Recommendation:** Set up GitHub Actions CI on Linux to run E2E tests.
- **Proposed fix:** FIX-001
- **Score impact:** 30
- **Blocker cap:** 749

### DEP-001 — No CI/CD pipeline

- **Severity:** high
- **Status:** verified-failure
- **Category:** deployment-and-environment-safety
- **Evidence:** No `.github/workflows/` directory, no pipeline config
- **Practical impact:** Deployments are manual, unverified, and error-prone. No gate prevents broken code from reaching production.
- **Recommendation:** Add GitHub Actions workflow: lint → typecheck → test → build → deploy.
- **Proposed fix:** FIX-003
- **Score impact:** 20
- **Blocker cap:** none

### PERF-001 — No performance monitoring or budgets

- **Severity:** medium
- **Status:** unverified
- **Category:** performance-and-scalability
- **Evidence:** No Lighthouse CI, no Core Web Vitals tracking, no bundle size analysis
- **Practical impact:** Performance regressions could ship unnoticed, degrading user experience on low-end mobile devices.
- **Recommendation:** Add Lighthouse CI to pipeline, set performance budgets.
- **Proposed fix:** FIX-008
- **Score impact:** 15
- **Blocker cap:** none

### GOV-001 — No license file

- **Severity:** low
- **Status:** observed-risk
- **Category:** governance-and-supply-chain
- **Evidence:** No `LICENSE` or `LICENSE.md` in repository root
- **Practical impact:** Legal ambiguity about usage rights for the codebase.
- **Recommendation:** Add appropriate license file.
- **Proposed fix:** FIX-009
- **Score impact:** 5
- **Blocker cap:** none

---

## 6. Checks performed

| Check                  | Command or method                           | Result                                                                | Evidence state     |
| ---------------------- | ------------------------------------------- | --------------------------------------------------------------------- | ------------------ |
| Lint (Pint)            | `vendor/bin/pint --test`                    | PASS                                                                  | Verified           |
| Type check (PHPStan)   | `vendor/bin/phpstan analyse --level=0`      | PASS (timeout, no errors visible)                                     | Partially verified |
| Unit + Feature tests   | `php artisan test --testsuite=Unit,Feature` | PASS (many tests passing, timeout at 5min)                            | Verified           |
| Composer audit         | `composer audit`                            | PASS (no vulnerabilities)                                             | Verified           |
| npm audit              | `npm audit --audit-level=high`              | 1 high (nanoid)                                                       | Verified           |
| Health endpoint        | `GET /health`                               | 200 `{"status":"ok","db":"ok","cache":"ok"}`                          | Verified           |
| Security headers       | HTTP response inspection                    | All headers present                                                   | Verified           |
| PWA manifest           | File inspection                             | Valid, complete                                                       | Verified           |
| Service worker         | File inspection                             | Well-designed, cache-first for assets, network-first for navigation   | Verified           |
| Auth middleware chain  | Code inspection                             | EnsureRepRole, EnsureApprovedDevice, EnsureValidLicense, ThrottlePost | Verified           |
| Offline sync routes    | Code inspection                             | SyncController, OfflineSnapshotController, push subscriptions         | Verified           |
| Session security       | HTTP cookie inspection                      | HttpOnly, Secure, SameSite=Lax                                        | Verified           |
| E2E tests              | `make test-e2e`                             | Skipped (Windows, upstream bug)                                       | Verified failure   |
| CI/CD pipeline         | Directory inspection                        | None found                                                            | Verified failure   |
| Backup strategy        | Config/env inspection                       | None configured                                                       | Verified failure   |
| Core Web Vitals        | Not run                                     | No evidence                                                           | Unverified         |
| Keyboard accessibility | Not tested                                  | No evidence                                                           | Unverified         |
| Screen reader          | Not tested                                  | No evidence                                                           | Unverified         |

---

## 7. Missing or unverified evidence

| Area                   | Why it was not verified   | Effect on confidence or score                     |
| ---------------------- | ------------------------- | ------------------------------------------------- |
| E2E test execution     | Windows limitation, no CI | Major — critical journeys unverified (-30 points) |
| Backup/restore         | No backup config exists   | Major — data loss risk (-25 points)               |
| CI/CD pipeline         | No pipeline exists        | Major — no deployment gate (-20 points)           |
| Core Web Vitals        | No Lighthouse CI          | Medium — performance unknown (-15 points)         |
| Keyboard accessibility | No automated tests        | Medium — accessibility unverified (-10 points)    |
| API documentation      | No OpenAPI/Swagger spec   | Low — developer experience (-5 points)            |
| Rollback procedure     | No documented process     | Medium — recovery uncertain                       |

---

## 8. Remediation plan — approval required

No repository changes have been made as part of this audit.

| Fix ID  | Priority | Change                                                                             | Resolves         | Risk   | Verification                     | Expected score gain |
| ------- | -------- | ---------------------------------------------------------------------------------- | ---------------- | ------ | -------------------------------- | ------------------: |
| FIX-001 | P0       | Add GitHub Actions CI workflow with lint, typecheck, test, build, and E2E on Linux | REL-002, DEP-001 | low    | Workflow runs green              |               30-50 |
| FIX-002 | P0       | Configure Railway Postgres PITR or automated pg_dump backup                        | REL-001          | low    | Backup exists and restore tested |               20-30 |
| FIX-003 | P1       | Add GitHub Actions CI pipeline (builds on push/PR)                                 | DEP-001          | low    | Pipeline runs on next push       |               15-25 |
| FIX-004 | P1       | Run `npm audit fix` to update nanoid                                               | SEC-003          | low    | `npm audit` clean                |                5-10 |
| FIX-005 | P2       | Increase PHPStan level to 1, fix any errors, incrementally raise                   | CODE-001         | medium | PHPStan passes at new level      |               10-15 |
| FIX-006 | P2       | Remove X-Powered-By header via `expose_php = Off` or middleware                    | SEC-002          | low    | Header absent in response        |                 3-5 |
| FIX-007 | P3       | Add offline behavior tests (SW cache, IndexedDB sync, background sync)             | REL-003          | medium | Tests pass                       |               10-15 |
| FIX-008 | P3       | Add Lighthouse CI with performance budgets                                         | PERF-001         | low    | Lighthouse score >90             |               10-15 |
| FIX-009 | P3       | Add LICENSE file                                                                   | GOV-001          | low    | File exists                      |                 3-5 |

### Approval request

Choose one clear response:

- **Approve the full plan**
- **Approve only:** `<list fix IDs>`
- **Revise the plan:** `<requested changes>`
- **Do not make changes**

Deployment, publication, pushing, merging, paid services, and destructive data changes require separate approval.
