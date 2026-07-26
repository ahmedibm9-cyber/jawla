# Risk Register

All findings apply to commit `ba768f7106b52fa8d2905daadc07cd6091ff0c26`. “Verification” describes evidence required to close the finding; no remediation was applied.

## PR-001 — Filament tenant scope fails open

- Severity: **Critical**
- Domain/status: tenancy and authorization — **FAIL**
- Affected workflows: all Filament resources, record pages, selectors, bulk actions, maps, reports, and imports
- Business impact: an authorized panel user may access or mutate another company’s records.
- Technical impact: `BelongsToCompany` adds a scope only when `ActiveCompanyContext` is non-null; Filament middleware does not initialize it.
- Exploit/failure scenario: a user navigates to or searches a record ID belonging to another company while the global scope is inactive.
- Evidence: `app/Models/Concerns/BelongsToCompany.php:13-18`; `app/Support/ActiveCompanyContext.php:7-26`; `app/Http/Middleware/SetActiveCompanyContext.php:14-20`; `app/Providers/Filament/AdminPanelProvider.php:84-99`; `app/Policies/InvoicePolicy.php:11-31`; `app/Providers/AuthServiceProvider.php:13-21`.
- Root cause: tenant context is optional/fail-open and is not in the Filament request middleware chain.
- Existing safeguards: company columns, global-scope trait, policies, some explicit company queries.
- Why insufficient: policies are mainly role-based and the scope silently disappears.
- Recommended remediation: make tenant resolution mandatory and fail closed at every authenticated entry point; enforce ownership in policies/services and add database-level defenses where possible.
- Verification: generated two-company allow/deny matrix for direct IDs, global search, relations, bulk actions, Livewire updates, imports, exports, and APIs.
- Effort/owner/dependencies: L; security/backend; canonical tenant and platform-superuser model.
- Launch blocker: **Yes**

## PR-002 — Stock import confirmation trusts mutable client state

- Severity: **Critical**
- Domain/status: inventory/tenancy — **FAIL**
- Affected workflow: warehouse stock CSV preview and confirmation
- Business impact: tampered confirmation data can create negative stock or apply another company’s product/warehouse.
- Technical impact: public Livewire `$preview` rows are accepted at confirm; the service applies their IDs and quantities without independently rebuilding or validating the preview, and `StockService` lacks complete company/nonnegative invariants.
- Exploit/failure scenario: change a preview row’s product ID or counted quantity in the Livewire payload after upload.
- Evidence: `app/Livewire/StockImport.php:23-30,72-85,95-110`; `app/Services/StockImportService.php:132-149`; `app/Services/StockService.php:47-71`.
- Root cause: a browser round trip is treated as trusted server state.
- Existing safeguards: initial file validation and transactional service execution.
- Why insufficient: validation occurs before the mutable state used for mutation.
- Recommended remediation: store a signed/server-side import token and revalidate company, warehouse, product, quantity, and freshness inside the transaction.
- Verification: hostile Livewire-payload tests for negative quantities and cross-company IDs plus rollback assertions.
- Effort/owner/dependencies: M; inventory/backend/security; authoritative import format.
- Launch blocker: **Yes**

## PR-003 — Returns can mint stock and credit

- Severity: **Critical**
- Domain/status: returns/stock/finance — **FAIL**
- Affected workflow: rep return entry and invoice-linked returns
- Business impact: arbitrary products, quantities, and prices may create stock and customer credit without a matching historical sale.
- Technical impact: return lines and `againstInvoiceId` are accepted without proving invoice ownership, sold item, remaining returnable quantity, price, batch, or prior returns.
- Exploit/failure scenario: submit an unsold product or excessive quantity with an arbitrary price and optional invoice reference.
- Evidence: `app/Livewire/App/LogReturn.php:47-69`; `app/Services/ReturnService.php:46-100`.
- Root cause: return provenance and cumulative-return invariants are not authoritative server checks.
- Existing safeguards: transaction, general model validation, and stock service usage.
- Why insufficient: atomic incorrect data remains incorrect.
- Recommended remediation: resolve immutable sale lines server-side, cap cumulative return quantities, define standalone-return authorization, and lock the referenced records.
- Verification: over-return, duplicate-return, cross-company, wrong-product, wrong-price, concurrent-return, and rollback tests.
- Effort/owner/dependencies: L; finance/inventory/product; owner decision on unreferenced returns.
- Launch blocker: **Yes**

## PR-004 — Repeated offline confirmation creates duplicate business intents

- Severity: **Critical**
- Domain/status: offline sync/idempotency — **FAIL**
- Affected workflows: offline sale, payment, return, and expense
- Business impact: retries can create duplicate invoices, payments, stock movements, or expenses.
- Technical impact: UI calls `enqueue()` without awaiting durable completion, then invokes a network-backed Livewire method while offline; every retry receives a new UUID.
- Exploit/failure scenario: confirm offline, see ambiguous UI, retry, reconnect, and sync two valid distinct keys.
- Evidence: `resources/views/livewire/app/sales-flow.blade.php:194-204`; `collect-payment.blade.php:78-90`; `log-return.blade.php:82-94`; `log-expense.blade.php:56-66`; `resources/js/offline/outbox.js:45-75`.
- Root cause: idempotency identifies transport attempts, not one stable user business intent, and durable enqueue is not an awaited UI boundary.
- Existing safeguards: unique same-key receipt and atomic handler/receipt transaction.
- Why insufficient: separate clicks/devices generate different keys.
- Recommended remediation: allocate and persist one intent key before confirmation, await transaction completion, disable/reconcile repeated submits, and define multi-device duplicate-intent rules.
- Verification: Playwright offline, rapid-click, refresh, browser-kill, lost-response, multi-tab, and multi-device tests asserting one mutation.
- Effort/owner/dependencies: M/L; PWA/sync/product; supported-device and multi-device policy.
- Launch blocker: **Yes**

## PR-005 — Production pre-deploy seeds known credentials and demo finance data

- Severity: **Critical**
- Domain/status: deployment/security/data integrity — **FAIL**
- Affected workflow: every Railway production deployment
- Business impact: deployment may fail on a fresh database or create a known-password privileged user and synthetic commercial records.
- Technical impact: pre-deploy executes migrations, `app:seed-super-admin`, and `DemoSeeder --force`; the command uses `superadmin@jawla.test`/`password`, and the demo seeder creates financial and stock records.
- Exploit/failure scenario: first production deploy either stops because no company exists or creates reachable known credentials and contaminates ledgers.
- Evidence: `railway.toml:2`; `app/Console/Commands/SeedSuperAdmin.php:23-49`; `database/factories/UserFactory.php:34`; `database/seeders/DemoSeeder.php:47-128,463-787`.
- Root cause: demo/bootstrap behavior is embedded in the production lifecycle.
- Existing safeguards: partial demo-company idempotence.
- Why insufficient: the dangerous initial execution remains and the credential is publicly knowable from source.
- Recommended remediation: remove all demo and known-credential creation from production promotion; use an auditable one-time tenant/bootstrap ceremony with generated secrets.
- Verification: ephemeral production-like fresh deploy proving no demo records/known credentials; investigate any deployed database and rotate affected credentials.
- Effort/owner/dependencies: S for pipeline, M for investigation; DevOps/security/product.
- Launch blocker: **Yes**

## PR-006 — Rep cancellation/reversal breaks immutable financial history

- Severity: **Critical**
- Domain/status: reversal/audit — **FAIL**
- Affected workflows: invoice, payment, return, and expense cancellation through action toast
- Business impact: a rep can cancel historical own money/stock actions without the manager/system-viewer control, reason, server expiry, or complete audit chain required by the specification.
- Technical impact: server handlers authorize ownership but do not enforce a short TTL, privileged role, mandatory reason, or immutable reversal event linking original and compensating entries.
- Exploit/failure scenario: a rep revisits or triggers cancellation for an older transaction, changing balances and stock outside the authorized reversal process.
- Evidence: `app/Livewire/App/ActionToast.php:50-103`; `tests/Feature/ActionToastUndoTest.php:74-90`; limited observer registration in `app/Providers/AppServiceProvider.php:92-94`; `app/Livewire/ActivityLog.php:56-70`.
- Root cause: UI undo and accounting reversal are modeled as the same capability.
- Existing safeguards: service-layer cancellation and ownership checks.
- Why insufficient: reversibility alone does not provide authorization, immutability, explanation, or traceability.
- Recommended remediation: define non-destructive compensating transactions, privileged approval, mandatory bilingual consequence/reason, original linkage, and immutable audit entries.
- Verification: role/TTL/reason matrix, repeated reversal, rollback, ledger linkage, and immutable-audit tests.
- Effort/owner/dependencies: L; finance/product/security; approved reversal policy.
- Launch blocker: **Yes**

## PR-007 — Rep-supplied prices are not bounded by an authoritative price policy

- Severity: **Critical**
- Domain/status: sales/pricing — **FAIL**
- Affected workflow: invoice creation and rep price editing
- Business impact: a rep can undercharge, overcharge, manipulate VAT/base value, or bypass contracted/product pricing.
- Technical impact: positive client `unit_price` is accepted into invoice items without a server-side price list, permission, floor/ceiling, or approval rule.
- Exploit/failure scenario: modify price in the Livewire request and complete a sale at any positive amount.
- Evidence: `app/Livewire/App/SalesFlow.php:135-143,243-258`; `app/Services/InvoiceService.php:93-103`.
- Root cause: editable UI state is treated as pricing authority.
- Existing safeguards: numeric/positive validation and transactional invoice creation.
- Why insufficient: syntactic validity is not commercial authorization.
- Recommended remediation: server-resolve price and tax policy; model discounts/overrides explicitly with permission, bounds, reason, and audit.
- Verification: tampered price, discount boundary, role, stale price-list, tax, and concurrent price-change tests.
- Effort/owner/dependencies: M/L; sales/finance/product; pricing and discount policy.
- Launch blocker: **Yes**

## PR-008 — Unsynced financial actions can be permanently discarded

- Severity: **Critical**
- Domain/status: offline data loss/audit — **FAIL**
- Affected workflow: sync queue pending, failed, and conflict actions
- Business impact: the only copy of a cash sale/payment/return/expense can be lost accidentally or deliberately.
- Technical impact: a direct Discard button permanently removes IndexedDB records without a consequence-specific confirmation, server audit, approval, or recoverable tombstone.
- Exploit/failure scenario: discard an unsynced cash payment and later have no evidence it was collected.
- Evidence: `resources/views/livewire/app/sync-queue.blade.php:41-61`; `resources/js/offline/sync.js:156-164`; `resources/js/offline/outbox.js:90-92`.
- Root cause: queue maintenance is treated as local UI state rather than financial exception handling.
- Existing safeguards: visible button only.
- Why insufficient: no recovery, accountability, or dual control.
- Recommended remediation: prohibit local deletion of financial intents; route exceptions to a durable, audited resolution workflow with exact bilingual consequence.
- Verification: offline queue/resolution tests for accidental discard, privilege, audit, recovery, and eventual reconciliation.
- Effort/owner/dependencies: M; PWA/finance/support; conflict ownership policy.
- Launch blocker: **Yes**

## PR-009 — Payment and invoice amendment/cancellation can drift balances

- Severity: **Critical**
- Domain/status: receivables/cash — **FAIL**
- Affected workflow: collect payment, cancel invoice, amend and resubmit invoice
- Business impact: customer and cash balances may disagree with invoices/payments.
- Technical impact: payment accepts a cancelled invoice; amend/cancel posting and resubmission do not form a single demonstrably balanced state machine, and amended submission omits the original customer-balance posting path.
- Failure scenario: cancel/amend an invoice, resubmit it, then apply a payment to the cancelled/original state and produce negative or understated balances.
- Evidence: `app/Services/PaymentService.php:32-44`; `app/Services/InvoiceService.php:163-196`.
- Root cause: status transitions and ledger postings are not encoded as one authoritative transition model.
- Existing safeguards: transactions and service calls.
- Why insufficient: separately atomic transitions can compose into an invalid financial state.
- Recommended remediation: define allowed transitions and double-entry-style posting/reversal effects; reject terminal invoice payment and reconcile stored balances.
- Verification: table-driven lifecycle tests including amendment, cancellation, partial/over-payment, duplicate and concurrent calls.
- Effort/owner/dependencies: L; finance/backend; approved invoice lifecycle.
- Launch blocker: **Yes**

## PR-010 — Stock reconciliation can overwrite concurrent movement

- Severity: **Critical**
- Domain/status: concurrency/inventory — **FAIL/NOT VERIFIED**
- Affected workflow: stock reconciliation concurrent with sale, return, receiving, or transfer
- Business impact: a valid concurrent movement can disappear from the stored stock balance.
- Technical impact: reconciliation reads and writes the balance without a demonstrated `FOR UPDATE` lock or serialization strategy over the stock row.
- Failure scenario: reconcile a counted quantity while a sale decrements the same stock row; the later write overwrites the other mutation.
- Evidence: `app/Services/StockService.php:47-72`; no focused concurrent-process reconciliation test found.
- Root cause: absolute-set reconciliation and delta movements do not share a proven locking protocol.
- Existing safeguards: transactions, stock movement records, nonnegative database check.
- Why insufficient: transactions without compatible row locks permit lost updates depending on the statements used.
- Recommended remediation: one locked mutation primitive, explicit expected-version/count semantics, retry policy, and reconciliation variance approval.
- Verification: true parallel PostgreSQL tests for sale/reconcile, return/reconcile, transfer/reconcile, deadlock retry, and ledger equality.
- Effort/owner/dependencies: M; inventory/database; reconciliation policy.
- Launch blocker: **Yes**

## PR-011 — Monetary precision, stored balances, and database invariants are insufficient

- Severity: **High**
- Domain/status: database/financial arithmetic — **FAIL**
- Affected workflows: invoices, payments, returns, expenses, cash boxes, tax totals, stock and transfers
- Business impact: rounding drift, invalid amounts, and divergence between source transactions and stored balances may accumulate silently.
- Technical impact: core paths convert decimal values to PHP floats and round; customer/cash/stock balances are mutable aggregates with no scheduled reconciliation. Database checks largely omit positive amounts, VAT bounds, source/destination inequality, and cross-company ownership.
- Evidence: `app/Services/InvoiceCalculationService.php:11-28`; `InvoiceService.php:97-103,151`; `ReturnService.php:67`; `PaymentService`; `ExpenseService`; only an explicit stock nonnegative check was found in `database/migrations/2026_07_12_100003_create_stocks_table.php:32`.
- Root cause: correctness relies on distributed application conventions rather than a single decimal/posting model plus database constraints and reconciliation.
- Existing safeguards: decimal database columns, rounding, transactions, money helper, foreign keys.
- Why insufficient: the authoritative invoice path does not consistently use the money helper; FKs do not establish same-company relationships.
- Recommended remediation: integer-minor-unit or strict decimal arithmetic, centralized posting, database checks, same-company enforcement strategy, and scheduled reconciliation.
- Verification: boundary/fractional/VAT/property tests, database constraint tests, ledger rebuild, and long-sequence reconciliation.
- Effort/owner/dependencies: L/XL; finance/database; currency, quantity-scale, and tax rounding rules.
- Launch blocker: **Yes**

## PR-012 — Tax/e-invoice compliance is incomplete and overstated

- Severity: **High**
- Domain/status: ZATCA/ETA/invoice compliance — **FAIL/NOT VERIFIED**
- Affected workflow: invoice numbering, QR, PDF, ZATCA Phase 2 selection, Egyptian ETA document generation
- Business impact: customers may receive documents that are not legally valid or are represented beyond implemented certification.
- Technical impact: the “Phase 2” strategy is effectively the Phase 1 TLV implementation; certification, signing, clearance/reporting, device/CSID lifecycle, ETA submission, rejection handling, and audited samples are absent.
- Evidence: `app/Services/Zatca/ZatcaPhase2Strategy.php:5-10`; shared TLV tags 1–5 in the base strategy; compliance and deployment documents keep tax invoices gated.
- Root cause: data/format scaffolding is named as a compliance phase without the external regulated workflow.
- Existing safeguards: server-generated numbers, QR/TLV generation, PDF escaping, feature/config gates.
- Why insufficient: valid syntax is not certification, signing, submission, acceptance, or legal approval.
- Recommended remediation: define jurisdiction/scope, use the relevant authority specification and certified integration process, retain immutable submission/response evidence, and legally review sample documents.
- Verification: official validator/certification environment, signed samples, rejection/retry/clock/certificate tests, sequence and archival audit.
- Effort/owner/dependencies: XL plus external lead time; tax/legal/integration; jurisdiction and provider decision.
- Launch blocker: **Yes if tax/compliance is advertised**

## PR-013 — Stored XSS in rep live map

- Severity: **High**
- Domain/status: browser security — **FAIL**
- Affected workflow: manager/executive live rep map
- Business impact: a rep-controlled name may execute in a privileged viewer’s browser.
- Technical impact: stored names are interpolated into Leaflet popup HTML without contextual encoding; CSP permits inline/eval execution.
- Failure scenario: save hostile HTML/event content in a profile name and have a manager open the live map.
- Evidence: `app/Livewire/App/ProfilePage.php:54-64,79-87`; `app/Services/LocationPingService.php:65-77`; `resources/views/filament/pages/rep-live-map.blade.php:62-66`; `app/Http/Middleware/SecurityHeaders.php:37-45`.
- Root cause: HTML-string construction at a stored-data trust boundary.
- Existing safeguards: string length validation and CSP.
- Why insufficient: validation does not encode output, and CSP is permissive.
- Recommended remediation: text-node/DOM construction or contextual encoding; harden CSP.
- Verification: browser payload corpus proving text-only rendering and no execution.
- Effort/owner/dependencies: S/M; frontend/security; CSP approach.
- Launch blocker: **Yes**

## PR-014 — Role and privileged-authentication model does not match the approved contract

- Severity: **High**
- Domain/status: RBAC/authentication/session — **FAIL/PARTIAL**
- Affected workflow: provisioning, admin/PWA access, finance, reports, users, stock, GPS
- Business impact: required roles cannot be provisioned as specified; operators may grant broader legacy roles, while privileged accounts lack complete MFA/step-up/session safeguards.
- Technical impact: required `system_viewer`, `hr_admin`, and `sales_rep` are absent; legacy roles and global bypasses dominate. Filament login rate key is not the specified email+IP limiter; MFA, role-specific absolute expiry, and admin session revocation were not found.
- Evidence: `docs/Jawla_Production_Build_Guide.md:101-111`; `docs/ROLES_MATRIX.md:3-22`; `database/seeders/RoleSeeder.php:13-98`; `app/Models/User.php:142-151`; `app/Http/Middleware/EnsureRepRole.php:13-20`; `app/Providers/AuthServiceProvider.php:13-21`; `app/Filament/Auth/Pages/Login.php:15-22,92-101`; `bootstrap/app.php:21`; `routes/web.php:48-50`.
- Root cause: legacy authorization and framework defaults were retained without a ratified migration/threat model.
- Existing safeguards: Spatie permissions, policies, five-attempt limiter, Argon2id, session regeneration, secure production cookies.
- Why insufficient: safeguards implement a different role model and incomplete privileged access controls.
- Recommended remediation: ratify/migrate one matrix; eliminate unnecessary bypasses; enforce identity-aware throttling, trustworthy proxy config, MFA/step-up, session inventory/revocation, and POST logout.
- Verification: generated positive/negative role matrix and authentication abuse/session lifecycle tests.
- Effort/owner/dependencies: L; product/security/backend/infrastructure; canonical roles and MFA/proxy decisions.
- Launch blocker: **Yes**

## PR-015 — Private files and continuous GPS lack production privacy proof

- Severity: **High**
- Domain/status: file security/privacy — **FAIL/NOT VERIFIED**
- Affected workflow: visit photos, signatures, COAs, periodic rep location tracking
- Business impact: customer/site images, signatures, and employee coordinates may be publicly exposed, retained indefinitely, or collected without approved governance.
- Technical impact: photo disk defaults public; local photo URLs are public; upload hardening/EXIF stripping is incomplete. A hidden global tracker stores precise coordinates about every minute with no retention job or visible governance control.
- Evidence: `config/filesystems.php:19-23,55-61`; `app/Models/Photo.php:36-50`; `app/Livewire/App/PhotoCapture.php:28-41`; `app/Services/PhotoService.php:27-55`; `resources/views/layouts/app.blade.php:106`; `resources/views/livewire/app/location-tracker.blade.php:1-24`; `app/Services/LocationPingService.php:22-52`; `docs/PRIVACY_AND_OPERATIONS_GATES.md:3-24`.
- Root cause: deployment convention and pending external governance are treated as controls.
- Existing safeguards: authentication, random paths, S3 temporary URLs when configured, open-shift check, coordinate validation/deduplication.
- Why insufficient: repository defaults can expose files; there is no lawful-basis, transparency, retention/deletion, or physical-presence assurance.
- Recommended remediation: fail closed to private storage and authorized delivery; validate/re-encode files; disable real tracking until privacy gates, visible state, retention, access logging, and subject-right processes are approved.
- Verification: anonymous/cross-tenant file denial, malicious-file suite, EXIF removal, GPS notice/shift/retention/export/delete and manager-isolation tests.
- Effort/owner/dependencies: M/L plus governance; privacy/legal/security/infrastructure; object store and retention policy.
- Launch blocker: **Yes**

## PR-016 — Offline scope, protocol evolution, and multi-device behavior are undefined

- Severity: **High**
- Domain/status: PWA/offline architecture — **FAIL/PARTIAL**
- Affected workflow: offline launch/navigation, sync ordering, conflicts, deployment upgrades
- Business impact: field work can stop after reload/route change, and old or multiple devices can create lost, duplicated, or stranded operations.
- Technical impact: service worker pre-caches only `/offline` and manifest; authenticated shell/data are not available offline. Same key/different payload returns the old response, no dependency graph or protocol version exists, and unresolved queue entries can defer client updates indefinitely.
- Evidence: `public/sw.js:1-20,58-77`; `app/Services/Sync/SyncService.php:36-40,66-77`; `database/migrations/2026_07_20_210000_create_sync_receipts_table.php:15-23`; `resources/js/offline/outbox.js:35-38,78-83`; `resources/js/pwa-register.js:15-17`; `docs/ARCHITECTURE.md:26-35`.
- Root cause: graceful degradation, queued writes, and full offline operation are not separated into a versioned product contract.
- Existing safeguards: public-only cache policy, serial server handling, same-key receipt.
- Why insufficient: it does not cover offline restart/navigation, causal dependencies, semantic request mismatch, multi-device intent, or client/backend compatibility.
- Recommended remediation: ratify offline scope and supported devices; version the protocol, hash operation payload/type, model dependencies/conflicts, and define upgrade/retention behavior.
- Verification: offline install/reload/navigation, dependency order, same-key mismatch, multi-device, quota/abort, service-worker upgrade, and old-client compatibility tests.
- Effort/owner/dependencies: L/XL; product/PWA/backend; advertised scope and compatibility window.
- Launch blocker: **Yes**

## PR-017 — Backup and disaster recovery are not operationally proven

- Severity: **High**
- Domain/status: DR/data durability — **NOT VERIFIED**
- Affected workflow: database/object recovery after corruption, operator error, or platform failure
- Business impact: permanent loss or prolonged outage of financial, stock, customer, signature, and audit data.
- Technical impact: custom guarded scripts exist, but the restore log is empty; image lacks `age`/`rclone`; external backup service, retention, independence, alerting, reconciliation, RPO, and RTO are unverified.
- Evidence: `docs/BACKUP_RESTORE.md:3-9,61-68`; `scripts/backup.sh:7-25`; `Dockerfile:5-24`.
- Root cause: documented procedure has not been exercised and recorded for the actual production topology.
- Existing safeguards: `pg_dump`, encryption/upload script, guarded scratch restore procedure.
- Why insufficient: a backup is not proven until independently restored and reconciled.
- Recommended remediation: operationalize independent encrypted backups and run scheduled restore drills with owners and alerting.
- Verification: timed scratch restore of exact production-shaped backup, attachment/object recovery, row/ledger/stock reconciliation, RPO/RTO record.
- Effort/owner/dependencies: M operational; operations/database/security; platform/storage/retention decisions.
- Launch blocker: **Yes**

## PR-018 — Deployment, rollback, and CI promotion are not safe release gates

- Severity: **High**
- Domain/status: release engineering — **FAIL/PARTIAL**
- Affected workflow: build, promotion, migration, health evaluation, rollback
- Business impact: broken or incompatible releases may reach users without an automatic recovery path.
- Technical impact: deploy script targets mutable `master`, health is static, no application rollback occurs, migration precedes promotion without a proven backup gate, committed build output may differ from CI build, E2E/security are advisory, and PHPStan/Larastan is absent.
- Evidence: `scripts/deploy.sh:12,39-44`; `railway.toml:2`; `app/Http/Controllers/SystemPageController.php:36-40`; `Dockerfile:28-34`; `.github/workflows/ci.yml`; `.github/workflows/e2e.yml:3-7,19-21,51-52`; `.github/workflows/security.yml:36-43,91-95`.
- Root cause: source deployment and advisory checks replace immutable artifact promotion and exercised release controls.
- Existing safeguards: PostgreSQL CI, Pint/build/audits/tests, Railway health probe and replicas, rollback documentation.
- Why insufficient: the exact promoted artifact and schema compatibility are not proven; health ignores critical dependencies.
- Recommended remediation: immutable artifact/checksum promotion, blocking required checks, dependency-aware health, expand/contract migration policy, pre-release recovery gate, and rehearsed rollback.
- Verification: staging promotion of pinned artifact, forced failure, app/client/schema rollback/roll-forward, and data reconciliation.
- Effort/owner/dependencies: L; DevOps/QA/database; branch protection and platform authority.
- Launch blocker: **Yes**

## PR-019 — Current tests do not provide trustworthy release evidence

- Severity: **High**
- Domain/status: verification — **NOT VERIFIED**
- Affected workflow: all release-critical behavior
- Business impact: severe defects can survive because critical paths are absent or the current run cannot distinguish infrastructure collision from behavior.
- Technical impact: audit runs collided on one shared PostgreSQL database and are invalid. Existing coverage lacks true parallel stock/money mutation, real IndexedDB/network-loss flows, multi-device sync, comprehensive tenant IDOR, service-worker upgrade, restore/rollback, accessibility, and full browser day journeys.
- Evidence: 99 test files; invalid audit results recorded in `11-test-evidence.md`; `tests/Feature/RepFlowOfflineUxTest.php:23-27`; skipped admin walkthrough at `tests/Browser/FullDayWalkthroughTest.php:114-126`.
- Root cause: test topology is not isolated for parallel audit execution and critical behavior is asserted mostly at service/static level.
- Existing safeguards: broad Pest feature suite and PostgreSQL CI.
- Why insufficient: quantity does not replace high-risk failure, concurrency, and browser evidence.
- Recommended remediation: isolate databases/processes, establish a release evidence manifest, and add blocking risk-driven suites.
- Verification: clean full suite plus named critical test matrix at exact release candidate.
- Effort/owner/dependencies: L; QA/backend/PWA/DevOps; stable release candidate and isolated test infrastructure.
- Launch blocker: **Yes**

## PR-020 — Capacity, monitoring, reconciliation alerts, and incident response are unproven

- Severity: **High**
- Domain/status: operations/performance — **NOT VERIFIED**
- Affected workflow: production load, dependency outage, financial drift, backup failure, security incident
- Business impact: degraded performance, corruption, or outage may go undetected or lack accountable response.
- Technical impact: old load evidence targets a prior commit and mostly reads/auth; health is static. Sentry/uptime/backup/reconciliation/duplicate-sync/tenant alerts, named on-call roles, tested channels, and incident drills are not evidenced.
- Evidence: `docs/perf-report-2026-07-20-railway.md:3-6,58-71`; `docs/GO_LIVE_READINESS.md:79-93`; `config/sentry.php`; `docs/PRIVACY_AND_OPERATIONS_GATES.md:28-39`; `/up` implementation.
- Root cause: operational scaffolding has not been converted into measurable service objectives and exercised controls.
- Existing safeguards: Sentry configuration/scrubber, structured logs, health probe, two replicas, passing PWA asset budget.
- Why insufficient: external configuration and response capability remain unknown; current production-shaped write capacity is absent.
- Recommended remediation: define SLOs/capacity/data volumes, instrument business invariants, assign named owners/channels, and exercise performance and incident scenarios.
- Verification: realistic staging k6/browser run; test alert delivery; reconciliation/backup-age alarms; SEV tabletop and outage drill.
- Effort/owner/dependencies: M/L; operations/performance/support/finance; production topology and SLO decisions.
- Launch blocker: **Yes**

## PR-021 — Specifications and operational documentation contradict implementation

- Severity: **High**
- Domain/status: maintainability/governance — **FAIL**
- Affected workflow: onboarding, deployment, authorization, offline promises, backup, support
- Business impact: operators and customers may rely on the wrong platform, role, offline, recovery, or compliance claim.
- Technical impact: primary guide and working docs conflict with dependencies, Railway/Redis topology, role seeder, route caching, package installation, CI, and offline behavior.
- Evidence: `docs/Jawla_Production_Build_Guide.md:31-51,101-111,187-215`; `docs/ARCHITECTURE.md:9-35`; `docs/DEPLOYMENT.md:39`; `docs/GO_LIVE_READINESS.md:28`; actual Composer/npm/workflows/`railway.toml`.
- Root cause: phase/spec documents were not reconciled through architecture decisions as implementation changed.
- Existing safeguards: extensive documentation and explicit privacy/readiness caveats.
- Why insufficient: mutually incompatible sources cannot all govern a release.
- Recommended remediation: owner-ratified ADRs and one current release contract; mark superseded claims and align runbooks/tests.
- Verification: documentation-to-code checklist reviewed by engineering, operations, product, security, finance, and support.
- Effort/owner/dependencies: M; technical/product owner; resolution of owner decisions.
- Launch blocker: **Yes**

## PR-022 — CSP and telemetry privacy hardening are incomplete

- Severity: **Medium**
- Domain/status: browser/telemetry security — **PARTIAL**
- Affected workflow: all web pages and exception reporting
- Business impact: injection/CDN compromise impact is wider; PII, financial values, or GPS fields may reach an external telemetry provider.
- Technical impact: CSP allows `unsafe-inline`, `unsafe-eval`, and unpinned `unpkg.com`; Sentry scrubber covers common secrets/request sections but not every event surface or domain-sensitive key.
- Evidence: `app/Http/Middleware/SecurityHeaders.php:37-45`; map views loading Leaflet from `unpkg.com`; `config/sentry.php:55-115`; `app/Support/SentryScrubber.php:19-81`.
- Root cause: compatibility-first CSP and generic secret filtering without a complete data classification.
- Existing safeguards: restrictive defaults, HSTS/frame/nosniff headers, default PII disabled, SQL bindings disabled.
- Why insufficient: stored XSS has a permissive execution environment and sensitive business data can occur in breadcrumbs/messages/contexts.
- Recommended remediation: bundle/pin assets, nonce/hash CSP, CSP reporting, all-surface allowlist/redaction, provider DPA/retention review.
- Verification: hardened browser flows and serialized synthetic Sentry event inspection.
- Effort/owner/dependencies: M; frontend/security/privacy; frontend compatibility and data classification.
- Launch blocker: conditional; PR-013 independently blocks launch.

## PR-023 — WCAG 2.2 AA is not demonstrated

- Severity: **Medium**
- Domain/status: accessibility — **FAIL/PARTIAL**
- Affected workflow: admin and rep Arabic/English interfaces
- Business impact: users with low vision, keyboard-only navigation, or assistive technology may not complete core work; commercial accessibility claims are unsupported.
- Technical impact: several normal-text/badge combinations fail 4.5:1, and no axe/Lighthouse/pa11y or manual screen-reader/zoom evidence exists.
- Evidence: `resources/css/app.css:40,370-381`; calculated examples: muted text on white 4.05:1, warning badge 2.86:1, danger 3.95:1, info 4.24:1; `tests/Browser/FullDayWalkthroughTest.php:114-126`.
- Root cause: design tokens and component states lack enforced accessibility acceptance criteria.
- Existing safeguards: `lang`/`dir`, skip link, native dialog, focus styling, reduced-motion support, 44px base buttons.
- Why insufficient: positive primitives do not prove journey-level operation or contrast.
- Recommended remediation: accessible tokens/components plus blocking automated and manual acceptance matrix.
- Verification: axe scans, NVDA/TalkBack, keyboard, 200% zoom/reflow, high contrast, reduced motion, target size, Arabic RTL and English LTR.
- Effort/owner/dependencies: M; frontend/QA/accessibility; supported-device matrix.
- Launch blocker: owner decision if contractual; required before claiming AA.

## PR-024 — Scheduled operational alarms are absent

- Severity: **Medium**
- Domain/status: scheduler/warehouse operations — **FAIL**
- Affected workflow: batch expiry, in-transit ageing, reconciliation and other promised periodic controls
- Business impact: expiring stock or operational exceptions may remain unnoticed.
- Technical impact: no application jobs/schedule exists; batch service exposes reads only.
- Evidence: `routes/console.php:1-8`; no `app/Jobs`; `app/Services/BatchService.php:24-45`; `docs/Jawla_Build_Guide_v1_Reference.md:479`; `tests/Feature/BatchExpiryTest.php:32-75`.
- Root cause: dashboards/query helpers were treated as automatic alarms.
- Existing safeguards: administrators can query current batch status.
- Why insufficient: manual discovery is not scheduled notification or escalation.
- Recommended remediation: define required alarms, idempotent generation, overlap controls, delivery/escalation, and ownership.
- Verification: time-travel, repeat-run, failure/retry, overlap, and delivery tests.
- Effort/owner/dependencies: M; backend/warehouse/operations; contractual alarm scope and channels.
- Launch blocker: owner decision based on sold scope.

## PR-025 — Batch traceability is bypassed by sale and return paths

- Severity: **High**
- Domain/status: batch/expiry inventory — **FAIL**
- Affected workflow: online/offline sale, return, reversal, and batch-tracked stock
- Business impact: lot, expiry, recall, and FEFO traceability can be lost for products configured to require batches.
- Technical impact: sale and return UI/sync payloads omit batch IDs, while the stock mutation service does not enforce a batch for `track_batch` products.
- Evidence: `app/Livewire/App/SalesFlow.php`; `app/Services/Sync/Handlers/SaleSyncHandler.php`; `app/Livewire/App/LogReturn.php`; `app/Services/Sync/Handlers/ReturnSyncHandler.php`; `app/Services/StockService.php`.
- Root cause: batch is optional at mutation boundaries instead of a product-derived invariant.
- Existing safeguards: batch model/services and batch-aware stock structures exist.
- Why insufficient: callers can create unbatched balances and movements.
- Recommended remediation: enforce an eligible same-company batch in the locked service path and define FEFO, damaged return, reversal, and transfer rules.
- Verification: tracked-product matrix across online/offline sale, return, reversal and transfer, including expired/depleted/wrong-company batches.
- Effort/owner/dependencies: L; warehouse/backend; FEFO and damaged-stock policy.
- Launch blocker: **Yes where tracked inventory is in scope**

## PR-026 — Van transfer authorization and exception lifecycle are inconsistent

- Severity: **High**
- Domain/status: inventory transfer/RBAC — **FAIL/PARTIAL**
- Affected workflow: ship, receive, reject, cancel, loss/damage, and partial receipt
- Business impact: transfers may be impossible for intended operators or may move stock without a complete conservation/exception record.
- Technical impact: `ship()` requires the source rep while the admin action targets manager/warehouse roles; source ownership is not tied consistently, rejection ignores user identity, and same-source/destination, partial receipt, damage/loss, and post-shipment cancellation are incomplete.
- Evidence: `app/Services/VanTransferService.php:68-76,143-153`; `app/Filament/Resources/VanTransferResource.php:108-126`; `app/Livewire/App/VanTransfers.php:18-37`.
- Root cause: role responsibilities and the transfer state machine were not specified together.
- Existing safeguards: transactional happy path and source/transit/destination movements.
- Why insufficient: happy-path atomicity does not define authorization or conservation under exceptions.
- Recommended remediation: ratify actors and states; enforce source/destination ownership, quantity, conservation, and immutable exception postings.
- Verification: full role/state matrix including reject, retry, partial, damaged/lost, duplicate receive, same endpoints, and concurrency.
- Effort/owner/dependencies: L/XL; warehouse/product/backend; transfer operating policy.
- Launch blocker: **Yes**

## PR-027 — Invoice numbering is not proven sequential and collision-safe

- Severity: **High**
- Domain/status: numbering/database/compliance — **PARTIAL/NOT VERIFIED**
- Affected workflow: first invoice/return sequence, year boundary, companies sharing abbreviation
- Business impact: duplicate, non-monotonic, or rejected document numbers can disrupt accounting and regulatory records.
- Technical impact: row locking is sound after initialization, but a random one-hex suffix makes displayed numbers non-monotonic; global uniqueness plus shared abbreviations creates collision risk; first-use insert conflict handling inside PostgreSQL may leave a transaction aborted.
- Evidence: `app/Services/NumberSequenceService.php:14-49`; no first-use parallel, rollback, same-abbreviation, or year-boundary tests found.
- Root cause: database sequence allocation and public anti-guessability were mixed in one identifier.
- Existing safeguards: server generation, company/year sequence row, row lock, unique document column.
- Why insufficient: uniqueness is not the same as per-company sequential compliance, and initialization races are unproven.
- Recommended remediation: separate internal/public opaque identifiers from legally sequential document numbers and use an atomic database allocation primitive.
- Verification: parallel first-use and steady-state issuance, rollback/no-gap policy, same-abbreviation companies, year rollover, and accounting/tax approval.
- Effort/owner/dependencies: M; database/finance/compliance; legal numbering rule.
- Launch blocker: **Yes**

## PR-028 — Paid returns, credits, and refunds lack a valid accounting lifecycle

- Severity: **High**
- Domain/status: returns/credit notes — **FAIL**
- Affected workflow: return after full/partial payment, customer credit, refund, later credit application
- Business impact: legitimate paid returns cannot be processed correctly, while cancelling paid documents can leave cash attached to a cancelled invoice.
- Technical impact: return value is limited by current customer balance, so a fully paid invoice with zero balance cannot produce the documented negative credit; no compliant credit/debit-note or refund lifecycle was found.
- Evidence: `app/Services/ReturnService.php:91-100`; invoice/payment cancellation and amendment paths described in PR-009.
- Root cause: receivable balance is used as return authorization instead of an accounting posting outcome.
- Existing safeguards: customer balance lock and transaction.
- Why insufficient: it blocks valid returns and does not model settlement of cash or credit.
- Recommended remediation: define jurisdiction-appropriate credit note, refund, customer credit, and allocation state machines with original-document linkage.
- Verification: unpaid/partial/paid return, cash/card refund, credit carry-forward, over-return, cancellation, and tax-document tests.
- Effort/owner/dependencies: XL; finance/tax/product/backend; approved accounting policy.
- Launch blocker: **Yes**

## PR-029 — Issued PDFs are not immutable or reproducible

- Severity: **High**
- Domain/status: document integrity/compliance — **FAIL**
- Affected workflow: first/delayed PDF generation, regeneration after cache loss, master-data edits
- Business impact: a historical invoice can render with changed company, customer, product, currency, or QR data.
- Technical impact: PDF is generated on retrieval from current master data; no immutable issuance snapshot or issued-file hash is retained, and currency is hardcoded to EGP including Saudi documents.
- Evidence: `app/Services/PdfService.php:51-72,141-220`.
- Root cause: presentation generation is separated from issuance without an immutable document snapshot.
- Existing safeguards: cached generated file, escaped interpolated values, server-controlled route.
- Why insufficient: cache deletion or delayed first generation changes history.
- Recommended remediation: generate from an immutable issuance snapshot, retain hash/version, and bind QR/tax fields and currency at issuance.
- Verification: mutate all master data/delete cache after issuance and prove byte/hash or canonical-content stability and authorized access.
- Effort/owner/dependencies: L; backend/compliance; legal archival format and retention.
- Launch blocker: **Yes for legal/tax invoices**

## PR-030 — Egyptian ETA integration is a non-idempotent unsigned scaffold

- Severity: **High**
- Domain/status: ETA compliance/integration — **FAIL**
- Affected workflow: document build, signing, submission, retry, rejection, certification
- Business impact: documents may be rejected, submitted twice, or represented as production-compliant without the required signature/schema.
- Technical impact: builder states schema validation is outstanding, fields are hardcoded/incomplete, active signer is unsigned, and remote submission occurs inside a database transaction without a locked idempotent submission state.
- Evidence: `app/Services/Eta/EtaDocumentBuilder.php:11-14,31-46,69`; `app/Services/Eta/UnsignedEtaSigner.php:7-19`; `app/Services/Eta/EtaService.php:30-53`; `app/Services/Eta/HttpEtaClient.php:19-26`.
- Root cause: integration seam/scaffold is present before regulated production implementation.
- Existing safeguards: configurable client/service abstraction and stored response fields.
- Why insufficient: abstraction does not provide signature, certification, retry safety, rejection workflow, or official schema proof.
- Recommended remediation: complete certified schema/signing/submission workflow with an outbox/idempotency state machine and immutable request/response audit.
- Verification: official preproduction certification, canonical signing vectors, concurrent submit/retry, rejection/correction, timeout/lost-response, and archival tests.
- Effort/owner/dependencies: XL plus external lead time; compliance/integration/operations; ETA credentials/certification and legal scope.
- Launch blocker: **Yes for Egypt ETA use**

## PR-031 — Financial and stock history is not database-append-only

- Severity: **High**
- Domain/status: database/audit immutability — **FAIL**
- Affected workflow: deletion of products, warehouses, payments, returns, expenses, and related records
- Business impact: deleting master or financial records can erase evidence required for reconciliation, disputes, and compliance.
- Technical impact: financial/stock relationships include cascading deletion paths; admin policies permit deletion of core resources; append-only behavior is not enforced by database privileges/constraints.
- Evidence: stock movement migration cascading warehouse/product relationships; payment/return/expense FK and policy definitions; batch FK behavior changed to `SET NULL`.
- Root cause: referential cleanup conventions were applied to ledger-like history.
- Existing safeguards: normal screens use soft deletion in several places and compensating services exist for some flows.
- Why insufficient: application convention cannot protect against every ORM/admin/direct database deletion path.
- Recommended remediation: prohibit destructive ledger deletion, restrict database roles, retain immutable originals, and use compensating records.
- Verification: policy, ORM, cascade, and restricted-database-user deletion attempts proving history remains and reconciliation still succeeds.
- Effort/owner/dependencies: L; database/backend/audit; retention and legal policy.
- Launch blocker: **Yes**
