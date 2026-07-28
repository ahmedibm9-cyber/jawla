# Jawla — Open Questions and Blockers

**Date:** 2026-07-29

**Revision:** `7b1dd3a` plus inspected working-tree changes
**Purpose:** Preserve unresolved decisions and the exact evidence needed to resolve them.

## Blocking downstream field use

### Q1. What is the authoritative offline sale pricing contract?

**Question:** Should the offline client store the quoted `unit_price`, or should `SaleSyncHandler` accept product/quantity only and let `InvoiceService` derive the server-authoritative price?

**Why it matters:** The actual UI omits `unit_price`, while the handler requires it. Every real offline sale produced by this UI is expected to fail sync.

**Evidence checked:**

- UI payload: `resources/views/livewire/app/sales-flow.blade.php:193-205`
- Outbox hashing/storage: `resources/js/offline/outbox.js:85-113`
- Handler validation: `app/Services/Sync/Handlers/SaleSyncHandler.php:23-32`
- Server price verification: `app/Services/InvoiceService.php:91-110`
- Existing queue UI test: `tests/Feature/RepFlowOfflineUxTest.php`
- Existing synthetic handler test: `tests/Feature/OfflineSyncHandlersTest.php`
- Focused offline run: 28 tests / 85 assertions passed, but no test joined the exact UI payload to the handler

**Safest resolution:** Decide and document the contract, add a producer-to-handler contract test using the exact Blade payload shape, then run an actual offline Browser flow in Linux CI. Server-authoritative price at replay time is simpler and tamper-resistant, but product requirements must decide how offline price changes are presented to the rep.

**Blocks:** Offline sales pilot and any claim that the six offline mutations work end-to-end.

### Q2. Which Feature test group retains enough state to exhaust 1 GB?

**Question:** Is memory growth caused by a specific test/file, repeated seeding, retained Eloquent/container state, result reporting, or a framework/plugin interaction?

**Why it matters:** `make test` and the repository definition of done cannot pass on the inspected host. A long suite dying without a result hides regressions.

**Evidence checked:**

- Unit-only: 142 passed, 386 assertions.
- Feature-only: 1 GB exhausted after about 12 minutes in `HasAttributes.php`.
- Unit+Feature: same 1 GB exhaustion.
- `phpunit.xml:23-43` sets `memory_limit=1024M`.
- Test bootstrap/guard reviewed: `tests/bootstrap.php`, `tests/TestCase.php`, `tests/Support/TestingDatabaseGuard.php`.

**Safest resolution:** Run Feature files in deterministic halves/groups, capture peak memory per group, then narrow to a file/test. Do not merely raise the CI limit until retained state is understood.

**Blocks:** Green `make test`, `make verify`, and trustworthy regression status.

### Q3. Why does PHPStan exit 1 without diagnostics?

**Question:** Is this a Windows process/output issue, PHPStan/Larastan bootstrap failure, result-cache corruption, or analyzer memory/process failure?

**Why it matters:** Static analysis is a required quality gate and currently supplies no actionable error.

**Evidence checked:**

- `php vendor/bin/phpstan analyse --no-progress` → exit 1, empty output.
- Verbose/debug/raw/table formats → same.
- Direct PHAR and `--no-configuration --level=0` → same.
- `phpstan.neon` inspected; analyzer is level 6, `app/`, local temp directory, 512 MB.
- PHPStan `--version` succeeds (2.2.6).

**Safest resolution:** Run the same command in Linux CI and a clean local dependency/cache environment; remove only the generated PHPStan result cache after confirming its exact path and that no concurrent task owns it.

**Blocks:** Green `make typecheck` and `make verify`.

## High-priority correctness questions

### Q4. How should policies authorize a user in a secondary active company?

**Question:** Should every record policy compare against `ActiveCompanyContext::id()` / `User::activeCompanyId()` instead of the user’s primary `company_id`?

**Why it matters:** The app permits company switching for assigned users, but shared policy code checks only the primary company. This can deny legitimate secondary-company work. `StockPolicy` also applies the helper to `Stock`, which has no `company_id`.

**Evidence checked:**

- Assignment/switch: `app/Models/User.php:80-105`, `SwitchCompanyRequest.php:9-22`
- Request context: `SetActiveCompanyContext.php:14-45`
- Policy helper: `app/Policies/Concerns/ChecksCompanyOwnership.php:8-13`
- Stock model/policy: `app/Models/Stock.php:9-29`, `app/Policies/StockPolicy.php:18-25`
- Tenancy tests under `tests/Feature/Tenancy/`

**Safest resolution:** Write policy tests for a user whose active company is a non-primary assigned company, including stock resolved through warehouse/product ownership. Then centralize active-company ownership checks.

**Blocks:** Confident multi-company admin rollout; does not currently indicate a data leak because the mismatch is fail-closed.

### Q5. Does photo capture work with the real S3-compatible adapter?

**Question:** Can `PhotoService::stripExif()` operate after upload when the configured disk is an actual S3 adapter?

**Why it matters:** It calls `Storage::disk('s3')->path()` and then local `finfo`/GD functions. The current S3 test uses a local fake, so production behavior is not exercised.

**Evidence checked:**

- Storage path: `app/Services/PhotoService.php:32-71`
- S3 configuration: `config/filesystems.php:18-75`
- URL generation: `app/Models/Photo.php:36-50`
- Test: `tests/Feature/PhotoDiskConfigTest.php:48-56`
- Readiness claim: `docs/GO_LIVE_READINESS.md:60-69`

**Safest resolution:** Add an adapter-level integration test against a disposable S3-compatible bucket. Prefer stripping EXIF from a local temporary/streamed file before upload.

**Blocks:** Confidence in production photo capture/durable storage.

### Q6. What client-safe error contract should offline sync expose?

**Question:** Which stable error codes and bilingual messages should replace raw exception messages in sync results?

**Why it matters:** `SyncService` returns `$e->getMessage()` for query and arbitrary failures, potentially exposing schema/query/internal details to authenticated clients.

**Evidence checked:** `app/Services/Sync/SyncService.php:104-127`; client rendering/storage in `resources/js/offline/sync.js:101-113`.

**Safest resolution:** Define status + public error code/message + correlation ID; report the full exception to server logs/Sentry. Add a test using a `QueryException`-like failure to prove SQL is not returned.

**Blocks:** Security hardening; does not block local development.

## Production and operational unknowns

### Q7. Does Railway actually enforce staging-before-production deployment?

**Question:** Are staging/production services both auto-deploying from `master`, and do GitHub environment approvals meaningfully gate production?

**Why it matters:** `.github/workflows/deploy.yml` does not deploy; it echoes that Railway auto-deploys and then probes production. Repository ordering may not control external auto-deploy timing.

**Evidence checked:**

- `.github/workflows/deploy.yml:11-40`
- `railway.toml:1-20`
- `docs/ARCHITECTURE_CURRENT.md:120-127`
- `docs/DEPLOYMENT.md`

**Safest resolution:** Inspect Railway service source triggers, branch/environment mappings, approval rules, migration behavior, worker service, rollback history, and latest successful deployment. Record screenshots/IDs outside source without secrets.

**Blocks:** Trustworthy release/promotion claims.

### Q8. Is ETA Phase 2 acceptance evidence complete outside the repository?

**Question:** Are real credentials, taxpayer certificate signer, official SDK validation, and an accepted preproduction document available?

**Why it matters:** The code defaults to `UnsignedEtaSigner`; production invoicing without compliance evidence is unsafe.

**Evidence checked:**

- `config/eta.php:3-30`
- `AppServiceProvider.php:66-79`
- `HttpEtaClient.php:10-26`
- `UnsignedEtaSigner.php:7-19`
- `docs/GO_LIVE_READINESS.md:42-58`

**Safest resolution:** Complete the documented preproduction process with the taxpayer/authorized operator; store no private certificate or credential in Git.

**Blocks:** Real-data production invoicing in Egypt.

### Q9. Has an independent backup and scratch restore drill passed?

**Question:** What are the measured RPO/RTO and reconciliation results from the latest encrypted off-host backup?

**Why it matters:** Scripts exist, but repository evidence contains no completed restore record.

**Evidence checked:**

- `scripts/backup.sh`
- `scripts/restore-backup.sh`
- `docs/BACKUP_RESTORE.md:38-69`
- `docs/BACKUP_RESTORE_DRILL.md`

**Safest resolution:** Authorized operator runs backup and restore to a named disposable database, reconciles company/invoice/payment/return/stock/user counts, and records results.

**Blocks:** Real-data launch and risky production migrations.

### Q10. Does the new Linux Browser CI job pass?

**Question:** Will the concurrent `browser-test` job successfully install Playwright and run the 39 browser tests on Ubuntu?

**Why it matters:** Windows is documented as unable to run the current plugin lifecycle reliably, and the new CI job has not yet been committed/run.

**Evidence checked:**

- Current working-tree changes in `.github/workflows/ci.yml`, `AGENTS.md`, `Makefile`
- `docs/TASK_CONTEXT_BROWSER_TEST_LIMITATION.md`
- Seven files under `tests/Browser/`

**Safest resolution:** Review and commit the separate browser-test task, open a PR, inspect the first Linux job, and retain its result. Do not claim pass before that.

**Blocks:** Browser/E2E verification evidence, not server-side planning.

### Q11. What is the authoritative hosting/deployment document?

**Question:** Should stale Forge/Render text be retired in favor of Railway-only current architecture, or are multiple targets intentionally supported?

**Why it matters:** Contributors can run the wrong health path, branch, deployment, storage, or backup instructions.

**Evidence checked:**

- `README.md:13-16,75-108`
- `docs/DEPLOYMENT.md:1-65`
- `docs/ARCHITECTURE_CURRENT.md:120-127`
- `railway.toml`
- `CONTRIBUTING.md:6-8`

**Safest resolution:** Name one current production authority and label every other target as historical/demo/alternative with last-verified dates.

**Blocks:** Operational clarity; not application-code exploration.

## Capacity and lifecycle questions

### Q12. What is the retention/archival policy for `sync_receipts`?

**Question:** How long must idempotency receipts remain online, and can old rows be archived without allowing a very old client operation to replay?

**Why it matters:** Receipts are delete-protected and no purge command was found. Unbounded growth affects storage/indexes, while premature deletion breaks exactly-once guarantees.

**Evidence checked:**

- `app/Models/SyncReceipt.php:11-38`
- `SyncService.php:71-115`
- Unique key migration `2026_07_20_210000_create_sync_receipts_table.php:11-24`
- Delete trigger list in migration `2026_07_26_000009...php:8-31,71-83`
- Only location-ping retention is scheduled (`bootstrap/app.php:39-41`)

**Safest resolution:** Establish maximum supported offline age, operational volume, archival design, and a protocol rule that prevents expired operations from being silently re-applied.

**Blocks:** Long-term capacity planning, not near-term development.

## Resolved during this exploration

- **CI branch mismatch:** Resolved at inspected `HEAD`; CI targets `master`.
- **Route cache closure concern:** Resolved for the current tree; `php artisan route:cache` succeeded.
- **Current enum values:** Recovered from source and corrected in the dossier.
- **Dependency audit status:** Composer and npm audits passed on 2026-07-29.
- **Unit test status:** 142 tests / 386 assertions passed; the aggregate Feature gate remains unresolved.
