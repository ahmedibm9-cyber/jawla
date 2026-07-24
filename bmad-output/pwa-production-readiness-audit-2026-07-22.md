# Jawla PWA Production-Readiness Audit

> **⚠️ ARCHIVED: 2026-07-24 — Code-level findings remediated; operational gates documented.**
> See `ISSUES_ARCHIVE.md` (root) for the definitive status.

**Audit date:** 2026-07-22  
**Checklist:** `C:\Users\Ahmed\OneDrive\Desktop\New Text Document (3).txt`  
**Method:** Static forensic repository review under the `bmad-investigate` workflow  
**Repository snapshot at synthesis:** `36ec5ac8fdac16aa511db8a4703c05248ffea2d4` on `master`, with 30 dirty/untracked worktree entries  
**Release decision:** **NO-GO for normal production, any real-data pilot, and real Egyptian invoicing**

## Executive result

The checklist contains 137 numbered audit domains in sections 2 through 18. Every domain was assessed exactly once. Sections 19 and 23 were then evaluated as release gates and decision questions; sections 20 through 22 are launch guidance, suggested tooling, and the register template rather than additional product controls.

| Result    |   Count | Meaning in this audit                                                                                    |
| --------- | ------: | -------------------------------------------------------------------------------------------------------- |
| Pass      |       0 | No domain had both complete static evidence and the required retained runtime/operational proof.         |
| Partial   |      76 | A meaningful control exists, but the domain is incomplete, contradicted, or lacks required verification. |
| Fail      |      60 | A required control is absent, unsafe, contradicted, or demonstrably unsuitable for release.              |
| N/A       |       1 | Web push is not in the current launch scope; reassess if enabled.                                        |
| **Total** | **137** | Complete numbered-domain coverage.                                                                       |

| Audit slice                                                                                 | Domains | Fail | Partial | N/A |
| ------------------------------------------------------------------------------------------- | ------: | ---: | ------: | --: |
| Generated code, backend, security, data, integrations, selected QA                          |      40 |   21 |      19 |   0 |
| Functional frontend, performance, PWA, reliability, accessibility, devices, UX, selected QA |      59 |   20 |      38 |   1 |
| Governance, privacy, observability, DevOps, UAT, operations                                 |      38 |   19 |      19 |   0 |

The application does have useful controls: transactions around several critical flows, negative-stock protection in the main stock service, idempotency scaffolding, pagination in many lists, Argon2id hashing, rate limits, security headers, PWA manifest/install scaffolding, RTL foundations, and a substantial automated test suite. The no-go follows because multiple P0 paths can disclose or corrupt real data and because the release evidence required to detect, block, recover from, and operate those failures is absent.

## Scope and evidence rules

- Source precedence followed repository `AGENTS.md`: the Production Build Guide and Reference Guide were primary, then `docs/`. A conflicting source-precedence document is recorded as a governance defect.
- Current source/configuration observations are generally evidence grade **B**. Grade **A** is reserved for independently corroborated current evidence; grade **C** denotes an absence inference, stale assertion, or external question.
- No test, build, lint, application server, browser, scanner, database, network, Railway, Sentry, ETA, or deployment command was run. The investigation workflow explicitly limits this pass to static evidence. Historical pass counts were not promoted to current proof.
- The worktree changed during the audit and was dirty at synthesis. Release verification must be repeated against one frozen clean SHA and its exact deployed configuration.

## P0 release blockers

### P0-1 — Cross-company audit disclosure and sensitive audit properties

`Activity` lacks the normal company scope, while the Filament Activity Log page reads and reverses unscoped records and has no explicit page-access guard. Its observer may retain changed user fields other than `updated_at`, including password hashes.

**Evidence:** `app/Filament/Pages/ActivityLog.php:13-79`, `app/Models/Activity.php:9-49`, `app/Observers/AuditObserver.php:11-38` (grade B).  
**Gate:** company-scope and authorize list/filter/reversal; allowlist audit properties; remediate legacy rows; independently test every role and direct record ID across two companies.

### P0-2 — Authenticated PWA content survives logout and identity changes

The root-scoped service worker cache-first serves authenticated navigations and caches generic GET responses without an eligibility allowlist, status check, identity partition, expiry, size bound, or logout purge. IndexedDB/localStorage are also not partitioned or cleared.

**Evidence:** `public/sw.js:1-79`, `resources/js/offline/outbox.js:6-89`, `resources/js/app.js:240-319`, `app/Http/Controllers/App/LoginController.php:51-60` (grade B).  
**Gate:** cache only explicitly public versioned assets/a generic offline shell; never cache authenticated HTML/API/PDF/error responses; identity-scope approved offline state; safely purge or quarantine it at identity changes; pass shared-device tests with real SW behavior.

### P0-3 — Money and stock can be corrupted by bypasses and races

The admin invoice resource can directly edit transactional status, bypassing stock, cash, balance, reversal, and audit services. Invoice creation can complete when the rep has no van warehouse, silently skipping stock movement. Payment, return, expense, and van-transfer terminal transitions do not consistently lock/guard state. Core money paths use binary floats despite a BCMath money value object.

**Evidence:** `app/Filament/Resources/InvoiceResource.php:38-57,79-91`, `app/Services/InvoiceService.php:30-113`, `app/Services/PaymentService.php:13-87`, `app/Services/ReturnService.php:38-110`, `app/Services/ExpenseService.php:49-65`, `app/Services/VanTransferService.php:43-133`, `app/Services/InvoiceCalculationService.php:11-30` (grade B).  
**Gate:** remove direct state mutation; require stock backing; use authorized service commands with decimal money, expected state/version, locks, idempotency, compensating reversal, and audit; pass simultaneous conservation and forced-rollback tests.

### P0-4 — Offline “exactly once” acknowledgement is not crash/concurrency safe

Sync receipt reservation and business mutation do not form one durable terminal state machine. A concurrent duplicate can observe a null response, and a crash can leave business state committed without a terminal receipt.

**Evidence:** `app/Services/Sync/SyncService.php:32-100`, `database/migrations/2026_07_20_210000_create_sync_receipts_table.php:11-24`, `resources/js/offline/sync.js:31-112` (grade B).  
**Gate:** implement atomic terminal receipt semantics or a leased processing/recovery state machine; never acknowledge a duplicate without a terminal result; prove exactly one business effect at every crash boundary and under concurrent same-key requests.

### P0-5 — The sync-queue screen can falsely report “all synced”

The view initializes `x-data="jawlaSyncQueue"`, but no matching controller was found in source or the committed bundle. Its zero-valued initial UI can display an empty/success state without loading the queue.

**Evidence:** `resources/views/livewire/app/sync-queue.blade.php:7-30`, `resources/js/offline/sync.js:98-112`, `tests/Feature/RepFlowOfflineUxTest.php:144-149` (grade B).  
**Gate:** implement/register the controller, bind durable pending/syncing/failed/conflict states, fail closed while loading, and test offline enqueue/reload/reconnect with real Alpine/server assertions.

### P0-6 — Service-worker updates can take over during financial work

The worker calls `skipWaiting()` during install and `clients.claim()` after deleting old caches, with no prompt, unsynced-work guard, multi-tab coordination, compatibility handshake, or bad-worker recovery.

**Evidence:** `public/sw.js:5-25`, `resources/views/layouts/app.blade.php:98-107` (grade B).  
**Gate:** stage updates, coordinate clients, defer activation during critical/pending work, retain compatible rollback, and test old/new builds with two tabs and interrupted transactions.

### P0-7 — Backup restoration and full rollback are not demonstrated

The restore script is a placeholder. The current backup log records no drill, while another document claims restoration was verified. The optional script keeps seven days in `/tmp`. Deployment treats health-check failure as a warning and has no immutable-artifact/client-state rollback evidence.

**Evidence:** `scripts/restore-backup.sh:1-4`, `scripts/backup.sh:1-37`, `docs/BACKUP_RESTORE.md:38-68`, `docs/ROLLBACK.md:8-9`, `scripts/deploy.sh:35-38` (grades A/B).  
**Gate:** monitored independent encrypted database/object backups; alternate-operator restore and invariant reconciliation; measured RPO/RTO; staged bad-release rollback covering app, DB, SW, IndexedDB, queue, and sessions.

### P0-8 — CI can appear green after critical failures

Composer/npm installation and audits have success-forcing fallbacks. CI omits the production asset build, type/static checks, critical browser journeys, accessibility/performance gates, secret/SAST/container/SBOM/license controls, and artifact promotion. Browser automation disables the SW; the “full day” test only visits pages.

**Evidence:** `.github/workflows/ci.yml:23-59`, `.github/workflows/security.yml:3-18`, `tests/Browser/FullDayWalkthroughTest.php:78-105`, `resources/views/layouts/app.blade.php:98-105`, `Dockerfile:1-33` (grade B).  
**Gate:** make every required check blocking; build one immutable signed artifact; run real mutations plus SW/offline, multi-browser, a11y, performance, security, and recovery gates; prove seeded defects stop promotion.

### P0-9 — Privacy/legal controls do not cover actual collection

The app processes employee/customer identity, location, signatures/photos, financial/tax records, browser caches/outbox data, and Sentry telemetry/replay. No retained ROPA, approved bilingual notice, lawful-basis/location review, retention/deletion schedule, rights workflow, vendor/DPA register, breach plan, or signed acceptance was found. The unsigned agreement has placeholders and describes Forge/VPS/Egypt hosting while deployment uses Railway.

**Evidence:** `resources/views/livewire/app/location-tracker.blade.php:1-25`, `resources/js/app.js:1-15`, `JAWLA_Software_License_Agreement.md`, `railway.toml:1-21`, `docs/BACKUP_RESTORE.md:26-30` (grades B/C).  
**Gate:** no real personal/company data until accountable counsel/owners approve inventory, lawful bases, notice, vendors/transfers, retention/deletion, rights, breach handling, terms, hosting facts, and user/employee processes.

### P0-10 — Real Egyptian electronic invoicing is intentionally incomplete

Runtime always binds `UnsignedEtaSigner`; official credential/signing and preproduction evidence are absent, and the remote ETA call occurs inside a DB transaction without a resilient outbox/reconciliation boundary.

**Evidence:** `app/Services/Eta/UnsignedEtaSigner.php:7-19`, `app/Providers/AppServiceProvider.php:62-74`, `app/Services/Eta/EtaService.php:30-54`, `docs/GO_LIVE_READINESS.md:33-49` (grades A/B).  
**Gate:** keep real invoicing disabled until certified signing, official preproduction approval, asynchronous idempotent delivery/reconciliation, monitoring, and recovery are demonstrated.

### P0-11 — Core interactive contrast fails the accessibility target

Brand green `#6DB83B` is paired with white text and used for focus/outline states. Contrast is approximately 2.4:1, below 4.5:1 normal-text and 3:1 non-text/focus thresholds, contradicting the design-system AA claim.

**Evidence:** `resources/css/app.css:22,198-206,264-305`, `docs/DESIGN_SYSTEM.md:111-117` (grade B).  
**Gate:** correct semantic tokens and retain automated plus manual keyboard, forced-colors, zoom/reflow, screen-reader, Arabic/English, and device evidence against WCAG 2.2 AA.

## Other high-priority findings

- **Authentication:** no configured privileged MFA, usable reset journey, or strong admin-created password policy; session behavior contradicts the role policy; session encryption defaults off; GET `/admin/logout` mutates state without CSRF.
- **Uploads:** photo code permits 5 MB against a 2 MB spec, `PHOTO_DISK` does not match the deployment variable, and default/public storage plus missing re-encoding/malware/bomb/rate/retention controls create risk.
- **Audit/observability:** sparse success-only audit coverage, mutable records, missing correlation/retention, no durable centralized log/alert-delivery proof, and dependency-blind health checks.
- **Deployment:** Railway/Redis/two replicas conflict with Forge/VPS/database-store and Render docs; route cache/worker requirements conflict; the Docker image is floating/root.
- **Licensing/provenance:** Composer says MIT while project materials describe proprietary distribution; no retained SBOM, notices, asset provenance, or commercial-use review exists.
- **API/database:** useful abilities, scopes, throttles, pagination, transactions, locks, and constraints exist, but no OpenAPI compatibility contract, global lock order, deadlock/failover/pool proof, full tenant constraints, or production-volume migration evidence.
- **PWA/data:** no quotas/expiry/eviction policy, IndexedDB upgrade/corruption recovery, conflict/version protocol, or reliable slow/flapping/captive-network behavior.
- **Performance:** no approved JS/CSS/font/image/CWV/API/memory/battery budgets or CI enforcement. A historical 7.1-second login p95 used an older stack and cannot certify this commit.
- **Accessibility/UX:** nested interactive markup, page heading defects, no screen-reader acceptance proof, undersized controls, incomplete onboarding, and weak interruption/permission/storage recovery.
- **Browser/device:** only Chromium automation; no approved platform matrix, iOS PWA certification, real printer certification, visual baseline, or SW browser test.
- **Operations:** no accountable RACI/deputies, support SLA/runbooks, alert test, incident exercise, independent operator drill, training records, or signed multi-role UAT.

## Specification and evidence contradictions

1. `AGENTS.md` makes production guides primary; `docs/spec/SOURCE_PRECEDENCE.md` labels the production guide historical.
2. Governing docs prescribe five roles; code/README seed seven. Activity Log checks a missing `system_viewer` role.
3. Docs describe Forge/VPS/database stores; config uses Railway, Redis, and two replicas. README retains a Render path that force-seeds demo credentials.
4. Docs say route cache is disabled; Railway/deploy enable it.
5. One runbook requires a Redis worker; another says none is needed; container start launches no worker.
6. The guide requires off-server 30-day backups/monthly restores; current script uses `/tmp`/seven days and restore is a placeholder.
7. One document says restore verified; the newer log records no drill.
8. Deployment claims asset build/rollback on failed health; Docker/CI/deploy do not build assets and health failure is non-fatal.
9. Requirements traceability says `NOT STARTED`; readiness reports claim engineering completion.
10. The guide defers full offline/Bluetooth while code ships them and readiness describes sync as green.
11. Upload spec requires private signed files up to 2 MB; photo code allows 5 MB and deployment variables do not configure the disk it reads.
12. Composer declares MIT while project/license materials are proprietary; the agreement is unsigned and incomplete.

## Section 19 release gates

| Gate family                          | Decision                                                                                                                           |
| ------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------- |
| P0 closure / accepted risk           | **Fail** — eleven blockers; no authorized, owned, expiring acceptances.                                                            |
| Security closure                     | **Fail** — no current retained SAST/secret/container/authenticated DAST/manual/penetration retest package; CI audits non-blocking. |
| Authentication/authorization/tenancy | **Fail** — current Activity Log isolation defect; privileged lifecycle incomplete.                                                 |
| Money/stock/calculation/workflow     | **Fail** — status bypass, missing stock backing, races, float money, no simultaneous/accounting proof.                             |
| Production config/secrets/build      | **Fail** — contradictory topology/config, no reviewed redacted config, immutable promotion, or current secret evidence.            |
| PWA install/update/cache/offline     | **Fail** — unsafe caching/update/state/UI and WebDriver bypass.                                                                    |
| Backup/restore/rollback              | **Fail** — restore placeholder/empty log and no complete drill.                                                                    |
| Monitoring/alerts/health             | **Fail** — SDK scaffolding without operated delivery/recovery proof.                                                               |
| Browser/device/WCAG/performance      | **Fail** — no matrix/budgets/specialist proof; deterministic contrast defect.                                                      |
| Retention/incident/support ownership | **Fail** — policies, owners, escalation, training, and exercises absent.                                                           |

All mandatory business gates also fail: named owners have not approved workflows/terminology/roles/outputs; client UAT and device acceptance remain open; training/support are not operational; legal/privacy/tax review is incomplete; and no authorized measured pilot plan exists.

## Recommended response: Option C — systemic readiness program

This is not one bug story. The blockers span the financial state model, PWA storage architecture, authorization, release engineering, privacy/legal, and operations.

### Workstream 0 — freeze and contain

1. Freeze normal production and real-data onboarding; keep ETA disabled.
2. Restrict any demo to synthetic data/disposable accounts and label outputs non-binding.
3. Select one scope/role/topology baseline and one clean release SHA; name accountable primary/deputy owners and acceptance authorities.

### Workstream 1 — close code-level P0s

1. Repair Activity Log tenant authorization and audit redaction.
2. Replace authenticated SW caching; identity-scope and clean client storage.
3. Centralize financial transitions, decimal money, locks, idempotency, stock backing, reversals, and audit.
4. Make offline receipts crash-safe; implement the queue controller and conflict/recovery UX.
5. Replace GET logout; complete privileged MFA/reset/password/session controls; fail photos closed to private storage.
6. Correct contrast and invalid interactive semantics.

### Workstream 2 — trustworthy release path

1. Remove CI success fallbacks; add build/type/unit/integration/critical E2E/SW/a11y/performance/security/SBOM/license/container gates.
2. Pin tools/images/actions and promote one signed immutable artifact through isolated staging.
3. Reconcile Railway/Redis/worker/storage/route-cache config and validate a redacted production contract.
4. Add dependency-aware readiness, durable logs, dashboards, alert ownership/tests, and release correlation.

### Workstream 3 — recovery and operation

1. Configure monitored independent encrypted DB/object backups.
2. Have an alternate operator restore/reconcile the exact release backup and record RPO/RTO.
3. Demonstrate rollback across replicas, DB, queue, SW, IndexedDB/outbox, and sessions.
4. Complete incident, privacy-rights, failed-sync, stale-cache, vendor-outage, certificate, and access-recovery runbooks/exercises.

### Workstream 4 — legal, user, and release evidence

1. Complete ROPA, privacy notice, location basis, vendor DPAs, retention/deletion, rights, breach, terms, license/provenance, and ETA approval.
2. Run complete rep-day/admin flows with real mutations on the approved platform matrix and actual SW behavior.
3. Complete independent accounting, security/retest, WCAG 2.2, performance/load/soak, and printer/PDF/output certification.
4. Train roles, run production-like UAT, close/retest defects, approve limitations, and authorize a measured pilot with abort/rollback authority.

## What changes the decision

A future go requires one clean immutable artifact/configuration to satisfy all section 19 gates: no unresolved P0/Critical/High defect; no cross-user/company leakage; conserved money/stock under simultaneous/crash tests; safe SW/offline/update/logout; independent restore/rollback within approved RPO/RTO; blocking CI/security/a11y/performance evidence; accountable monitoring/support/incident ownership; and signed legal, role-owner, output, and UAT approvals.

## Detailed 137-domain register

1. `bmad-output/pwa-audit-appendix-a-security-backend-2026-07-22.md` — sections 3, 4.2–4.3, 6, 9, 10, 17.1–17.2, 17.4, 17.8–17.9 (40 domains).
2. `bmad-output/pwa-audit-appendix-b-frontend-pwa-2026-07-22.md` — sections 4.1, 4.4–4.5, 5, 7, 8, 11, 12, 13, 17.3, 17.5–17.7 (59 domains).
3. `bmad-output/pwa-audit-appendix-c-governance-operations-2026-07-22.md` — sections 2, 14, 15, 16, 17.10, 18, plus sections 19 and 23 (38 domains plus gates).

## Sections 20–23 disposition

- **20:** stop at Stage 1; internal technical validation is incomplete.
- **21:** use the suggested tools in the verification program; they were deliberately not run here.
- **22:** register format applied in the appendices.
- **23:** unsafe or unproved on every release-significant question; exact answers are in Appendix C. Questions 1–3 are worded as hazards, so the appendix interprets the safe condition rather than forcing a literal “yes.”
