# UltraPlan: Production Readiness Remediation

**Plan ID:** jawla-remediation-2026-08-17
**Scope:** Close the 700→800+ gap identified in the PWA Production-Readiness Audit
**Documentation level:** Strict (payments, public users, regulated workflows)

---

## 1. Problem statement

Jawla scored **700/1000** (Staging Readiness). Three blockers prevent production:

1. No automated E2E test execution (no CI, Windows skip)
2. No database backup strategy
3. No CI/CD pipeline

Nine fixes are identified. The goal is to reach **800+ (Limited Beta Ready)**.

---

## 2. Non-goals

- Full accessibility audit (keyboard, screen reader)
- Core Web Vitals / Lighthouse CI setup
- ZATCA e-invoicing
- Redis migration (database-backed cache/sessions/queue are fine for beta)
- CDN for static assets
- API documentation / OpenAPI spec

---

## 3. Fix summary

| Fix     | Priority | What changes                                        | Risk   | Expected gain |
| ------- | -------- | --------------------------------------------------- | ------ | ------------- |
| FIX-001 | P0       | GitHub Actions CI workflow (lint→test→build)        | low    | +30-50        |
| FIX-002 | P0       | Automated Postgres backup (Railway PITR or pg_dump) | low    | +20-30        |
| FIX-004 | P1       | `npm audit fix` for nanoid vulnerability            | low    | +5-10         |
| FIX-006 | P1       | Remove X-Powered-By header                          | low    | +3-5          |
| FIX-009 | P3       | Add LICENSE file                                    | low    | +3-5          |
| FIX-005 | P2       | PHPStan level 0→1 (incremental)                     | medium | +10-15        |
| FIX-003 | P1       | CI/CD deploy-on-push (built on FIX-001)             | low    | +15-25        |
| FIX-007 | P3       | Offline behavior tests                              | medium | +10-15        |
| FIX-008 | P3       | Lighthouse CI (skipped for now)                     | low    | +10-15        |

**FIX-003** depends on FIX-001 (CI workflow must exist first).
**FIX-007** depends on FIX-001 (needs CI to run the tests).
**FIX-008** is deferred (needs CI + non-Windows environment first).

---

## 4. Approval gates

| Gate | After                              | Criteria                                                    |
| ---- | ---------------------------------- | ----------------------------------------------------------- |
| G1   | FIX-001, FIX-004, FIX-006, FIX-009 | CI runs green, npm audit clean, header gone, LICENSE exists |
| G2   | FIX-002                            | Backup exists and can be restored                           |
| G3   | FIX-003                            | Deploy-on-push pipeline works end-to-end                    |
| G4   | FIX-005                            | PHPStan level 1 passes, no regressions                      |
| G5   | FIX-007                            | Offline tests pass in CI                                    |

---

## 5. Milestone 1 — Quick wins (FIX-001, FIX-004, FIX-006, FIX-009)

**Objective:** Ship the easiest fixes first. Get CI running.

### FIX-001: GitHub Actions CI workflow

**Actor:** Developer
**Files:** `.github/workflows/ci.yml` (new), `Makefile` (unchanged)

**Acceptance criteria:**

- [ ] `.github/workflows/ci.yml` exists and runs on push + PR to `main`
- [ ] Steps: checkout → setup PHP → composer install → pint → phpstan (level 0) → test (unit+feature) → npm ci → npm build
- [ ] Runs on `ubuntu-latest`
- [ ] All steps pass

**Verification:** `act -l` or push to branch and check Actions tab.

**Note:** E2E tests (Playwright) are skipped here — they require a running server and database. They go in FIX-003/FIX-007.

### FIX-004: npm audit fix

**Actor:** Developer
**Files:** `package.json`, `package-lock.json`

**Acceptance criteria:**

- [ ] `npm audit --audit-level=high` returns 0
- [ ] nanoid >= 3.3.18

**Verification:** Run `npm audit`.

### FIX-006: Remove X-Powered-By

**Actor:** Developer
**Files:** `app/Http/Middleware/SecurityHeaders.php`

**Acceptance criteria:**

- [ ] Response header `X-Powered-By` is absent
- [ ] All other security headers still present

**Verification:** `curl -I http://localhost:8000/` — no X-Powered-By.

**Implementation:** Add `$response->headers->remove('X-Powered-By');` in SecurityHeaders middleware. Don't touch php.ini (not available in FPM containers).

### FIX-009: LICENSE file

**Actor:** Developer
**Files:** `LICENSE` (new)

**Acceptance criteria:**

- [ ] LICENSE file exists in repo root
- [ ] License type is appropriate (proprietary or chosen open source)

**Verification:** `ls LICENSE`.

---

## 6. Milestone 2 — Backup strategy (FIX-002)

**Objective:** Ensure data is recoverable.

### FIX-002: Automated Postgres backup

**Actor:** DevOps
**Dependencies:** None (parallel with M1)

**Acceptance criteria:**

- [ ] Railway Postgres has PITR enabled, OR
- [ ] `scripts/backup.sh` exists and dumps to a durable location (S3/Railway volume), OR
- [ ] Railway backup addon configured

**Verification:** Restore a backup to a test database and verify data integrity.

**Decision needed:** Which backup method? (Railway PITR, pg_dump to S3, or Railway backup addon)

---

## 7. Milestone 3 — CI/CD pipeline (FIX-003)

**Objective:** Automate deployment on push.

### FIX-003: Deploy-on-push pipeline

**Actor:** DevOps
**Dependencies:** FIX-001
**Files:** `.github/workflows/deploy.yml` (new)

**Acceptance criteria:**

- [ ] On push to `main`, workflow: lint → test → build → deploy to Railway
- [ ] Uses Railway CLI or GitHub Action for deployment
- [ ] Secrets stored in GitHub Actions secrets

**Verification:** Push a commit and verify deployment triggers.

**Note:** Railway CLI authentication in CI requires a Railway API token stored as a GitHub secret.

---

## 8. Milestone 4 — Type safety (FIX-005)

**Objective:** Improve code quality with stricter static analysis.

### FIX-005: PHPStan level 0→1

**Actor:** Developer
**Dependencies:** None
**Files:** `phpstan.neon`, possibly source files if errors found

**Acceptance criteria:**

- [ ] `phpstan.neon` level changed to 1
- [ ] `vendor/bin/phpstan analyse` passes
- [ ] No test regressions

**Verification:** Run phpstan and tests.

**Note:** If level 1 produces many errors, fix the highest-impact ones first and leave the rest as tech debt.

---

## 9. Milestone 5 — Offline tests (FIX-007)

**Objective:** Verify offline behavior works end-to-end.

### FIX-007: Offline behavior tests

**Actor:** Developer
**Dependencies:** FIX-001 (CI must exist)
**Files:** `tests/Browser/OfflineSyncTest.php` (new)

**Acceptance criteria:**

- [ ] Test: service worker caches static assets
- [ ] Test: background sync queues operations
- [ ] Test: offline → online sync completes
- [ ] Tests run in CI (Linux, headless)

**Verification:** CI passes with new tests.

**Note:** These are Pest browser tests using Playwright. They require a running server + database. In CI, spin up the app with `php artisan serve` + SQLite for testing.

---

## 10. Critical path

```
FIX-001 ──┬──► G1 ──► FIX-003 ──► G3
FIX-004 ──┤
FIX-006 ──┤
FIX-009 ──┘

FIX-002 ──────────────► G2

FIX-005 ──► G4

FIX-007 ──► G5 (depends on FIX-001)
```

**Estimated duration:** 2-4 hours for M1+M2, 1-2 hours for M3, 30 min for M4, 2-3 hours for M5.

---

## 11. Risks

| Risk                                 | Impact         | Mitigation                                         |
| ------------------------------------ | -------------- | -------------------------------------------------- |
| FIX-005 produces many PHPStan errors | Delay          | Fix critical ones, leave rest as tech debt         |
| Railway API token expires in CI      | Deploy failure | Use Railway's long-lived tokens, document rotation |
| Playwright tests flaky in CI         | CI instability | Use retries, headless mode, SQLite for test DB     |
| Backup restore fails silently        | Data loss      | Test restore monthly, document RTO/RPO             |

---

## 12. Output contract

```yaml
plan_result:
  scope:
    [FIX-001, FIX-002, FIX-003, FIX-004, FIX-005, FIX-006, FIX-007, FIX-009]
  non_goals: [Lighthouse CI, Redis, ZATCA, CDN, accessibility audit, API docs]
  acceptance_criteria_count: 24
  architecture_decisions: []
  milestones: [Quick wins, Backup, CI/CD, Type safety, Offline tests]
  critical_path: [FIX-001, FIX-003, G1]
  approval_gates: [G1, G2, G3, G4, G5]
  risks: [PHPStan errors, CI token expiry, flaky E2E, backup restore]
  documents_written: [PRODUCTION_REMEDIATION_ULTRAPLAN.md]
  next_vertical_slice: FIX-001 (GitHub Actions CI workflow)
  recommended_next_skill: ai-implementation-strategist
```
