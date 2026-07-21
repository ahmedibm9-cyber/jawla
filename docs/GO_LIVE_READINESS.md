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

### B2. Durable photo storage

- **Blocked on:** a decision (object storage) + one approval.
- **Minimal ask:** "Yes, use Railway bucket / Cloudflare R2 / S3" + confirm the
  new package (`league/flysystem-aws-s3-v3`) is approved.
- **Then (small):** add an `s3` disk (dormant until env vars set), point
  `PhotoService` at it, `storage:link` becomes unnecessary. One-env-flip cutover.
- **Risk if skipped:** photos are per-replica + lost on redeploy (2 replicas,
  ephemeral FS). Acceptable only if photos are non-critical at launch.

### B3. Backup automation + a recorded restore drill

- **Blocked on:** package decision + an operator with DB creds running the drill.
- **Minimal ask:** approve `spatie/laravel-backup` + an S3-compatible bucket, OR
  confirm "Railway managed Postgres backups are sufficient for V1."
- **Then (small):** if approved, add the package + schedule; either way, run the
  documented `pg_dump`→restore drill once and record it in `BACKUP_RESTORE.md`.
- **Risk if skipped:** unproven recovery path. The manual procedure exists; only
  the _executed drill_ is missing.

### B4. Live performance + security passes

- **Blocked on:** running against a live target (can't be done from the dev box).
- **Minimal ask:** a green light to run k6 against staging (not prod), and a
  scheduled Burp/IDOR pass on auth + invoice + PDF.
- **Then (small):** expand the k6 credential pool, run the baseline, set read/write
  p95 + error SLOs. Code-level IDOR review is already clean.
- **Risk if skipped:** no measured SLOs; the code review substitutes partially.

---

## C. Recommended launch decision

- **Egypt (ETA-regulated):** **do not launch** until B1 is closed — it's a legal gate.
- **Non-ETA / pilot:** **launchable now** if B2 is accepted as a known limitation
  (or closed) and B3 is answered ("Railway backups sufficient" is a valid V1 answer).
- Either way, B4 should run against **staging** before a wide rollout.

**One input unblocks the most:** the ETA credentials (B1). Provide any of B1–B3's
"minimal ask" and the follow-on work is small and already scoped above.

---

## D. Open repo hygiene (non-blocking)

- `skill-*.txt` / `pwa-skill-*.txt` show as deleted in the working tree (artifacts
  of a declined `npx skills` command). Decide: remove from the repo, or restore.
- Shared working tree has had concurrent auto-commits; confirm branch ownership to
  prevent a repeat of the login-route drift (see the LOGIN investigation).
