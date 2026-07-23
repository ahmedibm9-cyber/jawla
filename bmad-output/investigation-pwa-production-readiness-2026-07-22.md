# Investigation Case File: pwa-production-readiness

**Date:** 2026-07-22  
**Project:** Jawla — Laravel/Livewire/Filament field-sales PWA  
**Reported By:** Product owner  
**Severity:** Data Loss / Security / Regulatory exposure if released with real data  
**Status:** Open — release blocked  
**Case File Version:** 1.0  
**Target observed at synthesis:** `master` at `36ec5ac8fdac16aa511db8a4703c05248ffea2d4`, dirty worktree  
**Full audit:** `bmad-output/pwa-production-readiness-audit-2026-07-22.md`

---

## Summary

**One-sentence description:** A static audit of all 137 numbered domains in the supplied PWA production checklist found 60 Fail, 76 Partial, 1 N/A, and 0 Pass results, including independent release blockers in tenant isolation, financial/offline state integrity, authenticated caching, recovery, CI, accessibility, privacy, and ETA readiness.

**Expected behavior:** One frozen release candidate preserves tenant/user isolation, conserves money and stock under retry/concurrency/offline recovery, exposes truthful PWA state, updates and rolls back safely, meets accessibility/performance/security requirements, restores within approved RPO/RTO, and has signed business/legal/operational evidence.

**Actual behavior:** Useful controls and tests exist, but current code has credible disclosure/corruption paths, while the release pipeline and retained evidence cannot reliably detect, block, recover from, or operate them.

**Business impact:** A normal release can expose another company/user's cached or audit data, duplicate/reverse financial and stock effects, lose or misreport offline operations, produce legally invalid Egyptian e-invoices, leave the company unable to demonstrate restoration, and create privacy/accessibility/commercial obligations without approval.

## Symptom details

**Trigger conditions include:**

- Shared/stolen device, logout, account/company/role switch, or offline navigation after authenticated use.
- Concurrent/repeated payment, return, expense, transfer, or cancellation; direct invoice-status editing; sale without a van warehouse.
- Duplicate offline request or crash between business commit and receipt completion; storage corruption/quota; queue-controller initialization.
- Service-worker update while multiple tabs, an unsaved transaction, or queued work are active.
- Deployment with stale assets, hidden dependency/audit failure, failed health check, or incompatible migration/client cache.
- Real employee location/customer photo/financial/tax data collected without completed privacy/vendor/retention controls.

**Environments affected:** Current source-controlled behavior; actual staging/production behavior remains unverified.  
**Reproducible:** Static defects are grade B; exact reproduction and verification scripts are documented in the audit appendices.  
**Audit limitation:** No runtime command, test, browser, scanner, database, network, or deployment was executed under this investigation workflow.

## Evidence

### E1 — Cross-tenant Activity Log and unsafe audit properties

**Grade:** B  
**Source:** `app/Filament/Pages/ActivityLog.php:13-79`; `app/Models/Activity.php:9-49`; `app/Observers/AuditObserver.php:11-38`  
**Observation:** Activity queries/reversal are not company-scoped or explicitly page-authorized; observer property capture can include sensitive changed user fields.  
**Implication:** Credible cross-company disclosure and unsafe reversal.

### E2 — Authenticated responses and client state cross identity boundaries

**Grade:** B  
**Source:** `public/sw.js:1-79`; `resources/js/offline/outbox.js:6-89`; `app/Http/Controllers/App/LoginController.php:51-60`  
**Observation:** Shared cache/IndexedDB lack partition, expiry, bounds, and logout purge; the worker caches authenticated navigations and generic GETs.  
**Implication:** Shared-device disclosure and stale authorization/business data.

### E3 — Financial state bypass and duplicate-effect races

**Grade:** B  
**Source:** `app/Filament/Resources/InvoiceResource.php:38-57,79-91`; `app/Services/InvoiceService.php:84-100`; `app/Services/PaymentService.php:58-87`; `app/Services/ReturnService.php:82-110`; `app/Services/ExpenseService.php:49-65`; `app/Services/VanTransferService.php:43-133`  
**Observation:** Direct status editing bypasses services, a sale may skip stock, and several terminal transitions lack consistent locks/idempotent guards.  
**Implication:** Wrong stock, cash, customer balances, and transaction histories.

### E4 — Offline receipt and UI state are not trustworthy

**Grade:** B  
**Source:** `app/Services/Sync/SyncService.php:32-100`; `resources/js/offline/sync.js:31-112`; `resources/views/livewire/app/sync-queue.blade.php:7-30`  
**Observation:** Receipt reservation/business completion are not one durable terminal state, and the view names an Alpine controller not found in source/bundle.  
**Implication:** Lost/indeterminate operations and false “all synced” feedback.

### E5 — PWA update/cache lifecycle can mix versions and leak data

**Grade:** B  
**Source:** `public/sw.js:5-79`; `resources/views/layouts/app.blade.php:98-107`  
**Observation:** Immediate takeover and broad caching have no work guard, compatibility protocol, rollback, or browser coverage.  
**Implication:** Interrupted financial work, stale/unauthorized UI, and bad-worker lockout.

### E6 — Recovery evidence is absent and contradictory

**Grade:** A/B  
**Source:** `scripts/restore-backup.sh:1-4`; `scripts/backup.sh:1-37`; `docs/BACKUP_RESTORE.md:38-68`; `docs/ROLLBACK.md:8-9`; `scripts/deploy.sh:35-38`  
**Observation:** Restore is a placeholder and the current log is empty despite an older verified claim; health failure does not abort deploy.  
**Implication:** No trustworthy data-loss recovery or release rollback guarantee.

### E7 — CI and browser evidence can create false confidence

**Grade:** B  
**Source:** `.github/workflows/ci.yml:23-59`; `.github/workflows/security.yml:3-18`; `tests/Browser/FullDayWalkthroughTest.php:78-105`; `resources/views/layouts/app.blade.php:98-105`  
**Observation:** Install/audit failures can be forced successful; CI omits major gates; browser tests are page-load smoke and disable service workers.  
**Implication:** A green pipeline does not certify critical workflows or the supplied checklist.

### E8 — Privacy, legal, licensing, and ETA readiness are incomplete

**Grade:** B/C  
**Source:** `resources/views/livewire/app/location-tracker.blade.php:1-25`; `resources/js/app.js:1-15`; `JAWLA_Software_License_Agreement.md`; `composer.json:2-10`; `app/Services/Eta/UnsignedEtaSigner.php:7-19`  
**Observation:** Personal/financial telemetry exists without the retained governance package; licensing conflicts; the agreement is unsigned; ETA signing is deliberately absent.  
**Implication:** Privacy, tax, contractual, and customer-trust exposure.

### E9 — A deterministic accessibility barrier exists

**Grade:** B  
**Source:** `resources/css/app.css:22,198-206,264-305`; `docs/DESIGN_SYSTEM.md:111-117`  
**Observation:** Green/white contrast is about 2.4:1 in core controls/focus styling, below required ratios; specialist/manual evidence is incomplete.  
**Implication:** Critical barriers remain despite an AA claim.

### Evidence summary

| Evidence | Grade | Primary release impact                        |
| -------- | ----- | --------------------------------------------- |
| E1       | B     | Cross-tenant/security disclosure              |
| E2       | B     | Shared-device privacy and stale authorization |
| E3       | B     | Money/stock corruption                        |
| E4       | B     | Lost or falsely acknowledged offline work     |
| E5       | B     | Mixed-version/stale PWA behavior              |
| E6       | A/B   | Unrecoverable or unproved data loss           |
| E7       | B     | False release confidence                      |
| E8       | B/C   | Privacy, licensing, tax, contractual exposure |
| E9       | B     | Accessibility release barrier                 |

## Ranked hypotheses

### H1 — Readiness was inferred from feature/test progress instead of one enforced evidence gate

**Confidence:** High  
**Supporting:** Traceability remains `NOT STARTED`; UAT/security/performance/restore actions remain open; CI has fail-open steps; other reports claim readiness.  
**Counter-evidence:** Meaningful tests, runbooks, and historical audits exist.  
**Conclusion:** Strongly supported. Those artifacts are not tied to one immutable candidate/configuration or a section 19 approval record.

### H2 — PWA/offline scope expanded beyond the architecture and threat model

**Confidence:** High  
**Supporting:** The guide defers full offline/Bluetooth while code ships a root worker, IndexedDB mutations, Bluetooth, location, replay, object storage, Redis, and ETA; architecture omits several boundaries.  
**Counter-evidence:** Targeted unfinished stories recognize some gaps.  
**Conclusion:** Supported. Recognition does not mitigate current behavior.

### H3 — Financial workflows evolved piecemeal, leaving multiple state authorities

**Confidence:** High  
**Supporting:** Services transact selected flows, but Filament status fields bypass them; locks/guards differ; a Money object exists but core flows use floats.  
**Counter-evidence:** Stock/numbering/selected cancellation/DB-constraint patterns are good and reusable.  
**Conclusion:** Supported. The repair is systemic but can reuse existing correct patterns.

### H4 — Documentation drift reflects unresolved deployment/product decisions

**Confidence:** High  
**Supporting:** Forge/Render/Railway, five/seven roles, route cache, queue worker, backup/restore, license, and source-precedence conflicts.  
**Counter-evidence:** Railway config and recent notes show a likely direction.  
**Conclusion:** Supported. A direction is not an approved operating baseline.

## Suspected components

| Component                                 | Relationship                                                               |           Priority |
| ----------------------------------------- | -------------------------------------------------------------------------- | -----------------: |
| Service worker, IndexedDB, sync client/UI | Cache isolation, update lifecycle, durable acknowledgement, truthful state |                 P0 |
| Financial/stock services and Filament     | State ownership, decimals, locks, idempotency, reversal                    |                 P0 |
| Activity/audit authorization              | Tenant isolation and sensitive-property handling                           |                 P0 |
| CI, Docker, Railway deploy/health         | Fail-open gates, assets, artifact promotion, topology                      |                 P0 |
| Backup/restore/rollback                   | Recovery and continuity                                                    |                 P0 |
| Privacy/legal/ETA/licensing               | Lawful operation and valid tax output                                      |                 P0 |
| Authentication/uploads                    | Privileged lifecycle, CSRF logout, private files                           | P1/P0 when exposed |
| Accessibility/performance/browser QA      | User access and field-device reliability                                   |              P0/P1 |
| Monitoring/support/incident/UAT           | Detection, response, ownership, acceptance                                 |            P0 gate |

## Open questions

1. What production scope, role model, topology, supported platforms, RTO/RPO, and offline policy do accountable owners approve?
2. Do Railway environments actually have isolated staging, private durable photos, Redis workers, centralized logs, alerts, managed backup retention, MFA, and company-controlled recovery access?
3. Has real customer/employee/location/photo data or a legally effective invoice entered the environment?
4. Was DemoSeeder run in production, and are predictable accounts present?
5. Which legal entity is controller/processor, where are data copies hosted, which vendors have DPAs, and what retention/rights/breach rules apply?
6. Who can authorize risk acceptance, rollback, privacy decisions, tax readiness, and go-live?

## Recommended response

**Option C — systemic implementation and release-readiness planning.** Several independent P0 roots span architecture, state integrity, release engineering, and operations. This should become a phased dependency-aware program, not one oversized fix story. The full audit defines four workstreams and exact release proof.

No implementation story was created because the request was an audit and the correct response crosses multiple owners/specifications. Reconcile existing overlapping stories first; add narrow stories only for uncovered blockers such as Activity Log isolation, direct invoice-status bypass, authenticated SW caching, privileged authentication, and restore implementation/drill.

## Handoff

- **Decision:** NO-GO for normal production, real-data pilot, and real Egyptian invoicing.
- **Containment:** synthetic-data demo only; ETA disabled; do not use historical test/audit counts as current release proof.
- **Primary input:** `bmad-output/pwa-production-readiness-audit-2026-07-22.md`.
- **Detailed evidence:** the three `pwa-audit-appendix-*` files listed there.
- **Next workflow:** architecture/planning to sequence P0 closure, then implementation and independent runtime verification against one clean immutable artifact.
