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

- **Blocked on:** client's ETA credentials + signing certificate (production).
- **Minimal ask:** ETA client ID/secret, the CSID/private-key material, and the
  target environment (preprod vs prod).
- **Then (small):** wire the credentials into the existing (already-gated)
  e-invoicing path, submit a test document to ETA preprod, confirm acceptance.
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

### B4. Live performance + security passes

- **Blocked on:** running against a live target (can't be done from the dev box).
- **Minimal ask:** a green light to run k6 against staging (not prod), and a
  scheduled Burp/IDOR pass on auth + invoice + PDF.
- **Then (small):** expand the k6 credential pool, run the baseline, set read/write
  p95 + error SLOs. Code-level IDOR review is already clean.
- **Risk if skipped:** no measured SLOs; the code review substitutes partially.

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
