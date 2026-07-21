# Jawla V1 — Go-Live Readiness Runbook

**Date:** 2026-07-21
**Purpose:** the single decision surface for shipping V1. Everything buildable is
done and verified green; each remaining gate is reduced to the _exact_ input
needed and the (small) work that follows once it's provided.

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

**Shippability of the code itself: ready.** The blockers below are inputs and
external passes, not defects.

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

With B2 (photos) done and B3 (backups) decided, **only B1 and B4 remain**:

- **Egypt (ETA-regulated):** **do not launch** until B1 (ETA e-invoicing) is
  closed — it's a legal gate, pending client credentials (status: "coming").
- **Non-ETA / pilot:** **launchable now** — the code is green, photos are durable,
  the backup posture is decided. Run B4 against staging before a wide rollout, and
  have an operator execute the backup restore drill once.

**The one remaining hard blocker is B1** (ETA credentials). Everything else is
either done or an operator/staging task.

---

## D. Open repo hygiene (non-blocking)

- `skill-*.txt` / `pwa-skill-*.txt` show as deleted in the working tree (artifacts
  of a declined `npx skills` command). Decide: remove from the repo, or restore.
- Shared working tree has had concurrent auto-commits; confirm branch ownership to
  prevent a repeat of the login-route drift (see the LOGIN investigation).
