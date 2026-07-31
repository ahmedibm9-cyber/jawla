# Jawla V1 — Go-Live Readiness Runbook

> **⚠ HISTORICAL SNAPSHOT** — Generated 2026-07-21. Test counts (303/332) and
> readiness assessments reflect the codebase at that date. For current numbers,
> see `README.md` or run `php artisan test`.

**Date:** 2026-07-21
**Purpose:** the single decision surface for shipping V1. Everything buildable is
done and verified green; each remaining gate is reduced to the _exact_ input
needed and the (small) work that follows once it's provided.

> **Current release authority — 2026-07-22.** This document's earlier
> “launchable now” language is superseded by
> `bmad-output/pwa-production-readiness-audit-2026-07-22.md` and its remediation
> re-audit. The application is **NO-GO for real company data and production
> operation** until all mandatory section-19 gates in the supplied production
> checklist have evidence or a formally approved, time-bounded risk acceptance.
> A passing repository test suite is necessary but not sufficient.
>
> **Implementation update — 2026-07-29.** The offline-sale contract,
> active-company/stock authorization, photo sanitization, runtime PHPStan
> defects, blocking CI, staged promotion, dependency readiness, and rollback
> controls have been remediated in the working release. The exact local tree
> now passes 813 Unit/Feature tests, 2,271 assertions, five offline safety tests,
> four standalone Chromium PWA checks, production asset build, and
> production-container health against disposable PostgreSQL and Redis. The
> verdict remains **NO-GO** because immutable-commit CI/staging evidence and the
> external ETA,
> backup/restore, operations, legal/privacy, accessibility, performance, and
> signed UAT gates are still outstanding. See
> `docs/PRODUCTION_READINESS_IMPLEMENTATION.md`.

---

## A. Verified green (build-side complete)

Evidence, not assertion — all re-run on `master` (== `origin/master`) 2026-07-21:

| Area                             | Status              | Evidence                                                                        |
| -------------------------------- | ------------------- | ------------------------------------------------------------------------------- |
| Full Feature+Unit suite          | ✅                  | **303 passed**, 967 assertions, 0 failures                                      |
| All Blade views compile          | ✅                  | `view:cache` clean (customers 500 fixed)                                        |
| Rep-login fragility              | ✅ closed + guarded | `/app` → `/app/login` live; `RepLoginLifecycleTest` 5/5; LOGIN.1                |
| Rep IDOR surfaces                | ✅                  | visit (ownership), sell (company scope), PDF (`abort_unless`), undo (ownership) |
| Offline (CG2) — all 6 rep writes | ✅                  | exactly-once sync + outbox + queued UX                                          |
| Prod runtime                     | ✅                  | php-fpm + nginx (not `artisan serve`); `route:cache` on                         |
| Prod health                      | ✅                  | Online, 2/2 replicas, `/up` 200                                                 |

**Repository verification:** the current PostgreSQL-backed suite passed **332
tests / 1,038 assertions**, including **12 browser E2E tests**. This evidence
does not satisfy staging, infrastructure, legal, or business acceptance gates.

---

## B. Go-live gates — each with the exact ask

Ordered by what actually blocks launch.

### B1. ETA Phase 2 e-invoicing — THE go-live gate (Egypt)

- **Built (autonomous, this session):** the full transport is now in place, not
  just the gate — `HttpEtaClient` (OAuth client-credentials → `/documentsubmissions`
  → response mapping), an `EtaSigner` seam (`UnsignedEtaSigner` default), and a
  conditional binding that activates only when `eta.enabled` + base URLs are set
  (else the inert `NullEtaClient`). `EtaDocumentBuilder` maps the invoice to the
  ETA v1.0 shape. Unit-tested with faked HTTP (accept/reject/HTTP-error/auth-fail).
- **Still blocked on (genuinely external):**
  1. Client's ETA **credentials** (client id/secret, taxpayer RIN, base URLs).
  2. The taxpayer **signing certificate** + a CAdES-BES `EtaSigner` implementation
     — submitting unsigned is (correctly) rejected by ETA, never a false success.
  3. **Preprod validation** of the produced document + endpoints against the
     official ETA SDK before flipping to production.
- **Then (small):** set the env config, implement the cert signer, run one preprod
  submission, confirm acceptance. No call-site changes.
- **Risk if skipped:** non-compliant invoicing in Egypt → cannot legally operate.

### B2. Durable photo storage — ✅ DONE (2026-07-21)

- **Decision:** Railway bucket (object storage).
- **Delivered:** `league/flysystem-aws-s3-v3` installed; `PhotoService` writes to
  a config-driven disk (`PHOTO_DISK=s3`); `Photo::url()` returns short-lived
  signed URLs for the private bucket; provisioned Railway bucket `jawla-photos`
  (ams); S3 creds set on the app service; **round-trip validated** against the
  live bucket (put/get/delete OK). Photos are now durable + replica-shared.
- **Remaining:** none for V1. Existing local-disk photos keep resolving via their
  per-row disk; only new photos go to the bucket.

### B3. Backups — ✅ DECIDED (2026-07-21)

- **Decision:** Railway managed Postgres backups are sufficient for V1;
  `spatie/laravel-backup` intentionally not installed. Recorded in `BACKUP_RESTORE.md`.
- **Remaining (operator, pre-go-live):** run the documented `pg_dump`→restore
  drill once against a scratch DB and record it in the backup log. Manual step,
  needs DB creds.

### B4. Live performance + security passes — 🟡 prep done, run pending

- **Done (autonomous):**
  - k6 scripts fixed to gate on the **built-in** `http_req_failed` + `checks`
    (not the misleading legacy `errors` metric, now diagnostic-only).
  - **V1 SLOs defined** in the k6 thresholds:
    - Reads/pages: `p95 < 1.5s`, `http_req_failed < 5%`, `checks > 95%`.
    - Writes/auth: `p95 < 2.5s`, `http_req_failed < 5%`, `checks > 90%`.
  - **Credential pool** added — `PerfUserSeeder` (perf-rep-1..N) + `PERF_POOL_SIZE`
    so auth load spreads across accounts instead of tripping the 5/min throttle.
  - Code-level IDOR review clean (visit/sell/PDF/undo enforce ownership/company).
- **Remaining (needs a live target — not the dev box):**
  - Run k6 against **staging** (seed `PerfUserSeeder` there first) to confirm the
    SLOs hold, and a scheduled Burp/IDOR pass on auth + invoice + PDF.
- **Risk if skipped:** SLOs are defined but not yet measured under load.

---

## C. Recommended launch decision

**Do not launch any real-data pilot or production workload yet.** ETA
credentials/certification, a measured independent backup-and-restore drill,
deployment rollback evidence, staging security/performance/accessibility and
device/offline checks, named incident/support ownership, privacy/legal approval,
and signed business UAT are all mandatory release gates. See
`docs/PRIVACY_AND_OPERATIONS_GATES.md` for the accountable-owner evidence
required to change this decision.

---

## D. Open repo hygiene (non-blocking)

- `skill-*.txt` / `pwa-skill-*.txt` show as deleted in the working tree (artifacts
  of a declined `npx skills` command). Decide: remove from the repo, or restore.
- Shared working tree has had concurrent auto-commits; confirm branch ownership to
  prevent a repeat of the login-route drift (see the LOGIN investigation).
