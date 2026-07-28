# Investigation Case File: jawla-production-readiness

**Date:** 2026-07-28
**Project:** Jawla (جولة)
**Reported By:** BMAD Vibe-Explore + Production Readiness Audit
**Severity:** Critical — Multiple data loss, security, and financial integrity failures
**Status:** Open — Hypothesis Confirmed
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-production-readiness-2026-07-28.md`

---

## Summary

**One-sentence description of the issue:**
Jawla has 10 Critical and 21 High production-readiness findings spanning tenancy isolation, financial integrity, offline sync, security, and operational controls — a comprehensive audit score of **35/100**.

**Expected behavior:**
A production-ready system where: company data is isolated, financial mutations are atomic and auditable, offline sync creates exactly-once business intents, pricing is server-authoritative, and operational controls (backup, monitoring, rollback) are proven.

**Actual behavior:**
Ten critical pathways fail: tenant scope is fail-open (PR-001), stock import trusts mutable client state (PR-002), returns can mint stock/credit without provenance (PR-003), offline retries create duplicates (PR-004), demo credentials deploy to production (PR-005), reversals break immutable history (PR-006), prices have no server bounds (PR-007), unsynced financials can be discarded (PR-008), payment/invoice amendments drift balances (PR-009), and stock reconciliation can overwrite concurrent movement (PR-010).

**User / business impact:**
- **Data loss risk:** Financial records, stock movements, and audit trails can be created, duplicated, or destroyed
- **Security risk:** Cross-company data leak via tenant fail-open; XSS in live map; known credentials in production
- **Financial risk:** Unbounded pricing, drifted balances, invalid returns/credits, incomplete ETA compliance
- **Operational risk:** No proven backup/restore, no rollback, no incident response, untested E2E flows
- **Launch blocker:** All 10 Critical findings are launch blockers per the audit

---

## Symptom Details

**Trigger conditions:**
- Every authenticated request in Filament admin (tenant scope fail-open)
- Every stock import confirmation (mutable client state)
- Every return submission (no provenance check)
- Every offline operation retry (duplicate intents)
- Every production deployment (demo credentials seed)
- Every cancellation/reversal action (breaks immutable history)
- Every invoice creation (unbounded pricing)
- Every sync queue discard (permanent financial data loss)
- Every payment/invoice amendment (balance drift)
- Every stock reconciliation (concurrent overwrite)

**Environments affected:**
- [x] Production — All Critical findings apply
- [x] Staging — Same code paths
- [ ] Development / local — Same code paths but demo data

**First observed:** 2026-07-28 (audit commit `ba768f7106b52fa8d2905daadc07cd6091ff0c26`)
**Frequency:** Every request/action in affected pathways
**Reproducible:** Yes — all findings are code-level architectural gaps, not intermittent bugs

---

## Evidence

### Evidence Item 1: Tenant Scope Fail-Open

**Grade:** [A] Confirmed
**Source:** `app/Models/Concerns/BelongsToCompany.php:13-18`; `app/Support/ActiveCompanyContext.php:7-26`; `app/Http/Middleware/SetActiveCompanyContext.php:14-20`
**Description:**
`BelongsToCompany` adds a scope only when `ActiveCompanyContext` is non-null. Filament middleware does not initialize it. A user navigating to a record ID belonging to another company while the global scope is inactive can access or mutate cross-company data.

**Implications:** Complete failure of multi-tenant data isolation in the admin panel.

---

### Evidence Item 2: Stock Import Trusts Client State

**Grade:** [A] Confirmed
**Source:** `app/Livewire/StockImport.php:23-30,72-85,95-110`; `app/Services/StockImportService.php:132-149`
**Description:**
Public Livewire `$preview` rows are accepted at confirmation. The service applies their IDs and quantities without independently rebuilding or validating the preview. Tampered confirmation data can create negative stock or apply another company's product/warehouse.

**Implications:** Inventory corruption and cross-company data contamination.

---

### Evidence Item 3: Returns Mint Stock/Credit Without Provenance

**Grade:** [A] Confirmed
**Source:** `app/Livewire/App/LogReturn.php:47-69`; `app/Services/ReturnService.php:46-100`
**Description:**
Return lines and `againstInvoiceId` are accepted without proving invoice ownership, sold item, remaining returnable quantity, price, batch, or prior returns. Arbitrary products, quantities, and prices may create stock and customer credit without a matching historical sale.

**Implications:** Fake returns generate inventory and financial credits without corresponding sales.

---

### Evidence Item 4: Offline Retry Creates Duplicate Intents

**Grade:** [A] Confirmed
**Source:** `resources/views/livewire/app/sales-flow.blade.php:194-204`; `collect-payment.blade.php:78-90`; `log-return.blade.php:82-94`; `log-expense.blade.php:56-66`; `resources/js/offline/outbox.js:45-75`
**Description:**
UI calls `enqueue()` without awaiting durable completion, then invokes a network-backed Livewire method while offline. Every retry receives a new UUID. Retries can create duplicate invoices, payments, stock movements, or expenses.

**Implications:** Duplicate business intents from normal user retry behavior.

---

### Evidence Item 5: Production Deploys Demo Credentials

**Grade:** [A] Confirmed
**Source:** `railway.toml:2`; `app/Console/Commands/SeedSuperAdmin.php:23-49`; `database/seeders/DemoSeeder.php:47-128,463-787`
**Description:**
Pre-deploy executes migrations, `app:seed-super-admin`, and `DemoSeeder --force`. The command uses `superadmin@jawla.test`/`password`, and the demo seeder creates financial and stock records.

**Implications:** Known credentials and synthetic commercial records in production.

---

### Evidence Item 6: Reversals Break Immutable History

**Grade:** [A] Confirmed
**Source:** `app/Livewire/App/ActionToast.php:50-103`; `tests/Feature/ActionToastUndoTest.php:74-90`
**Description:**
Server handlers authorize ownership but do not enforce a short TTL, privileged role, mandatory reason, or immutable reversal event linking original and compensating entries. Reps can cancel historical money/stock actions without proper audit trail.

**Implications:** Financial history can be altered without proper authorization or audit.

---

### Evidence Item 7: Prices Have No Server Bounds

**Grade:** [A] Confirmed
**Source:** `app/Livewire/App/SalesFlow.php:135-143,243-258`; `app/Services/InvoiceService.php:93-103`
**Description:**
Positive client `unit_price` is accepted into invoice items without a server-side price list, permission, floor/ceiling, or approval rule. Reps can undercharge, overcharge, or bypass contracted pricing.

**Implications:** Revenue leakage and pricing manipulation.

---

### Evidence Item 8: Unsynced Financials Can Be Discarded

**Grade:** [A] Confirmed
**Source:** `resources/views/livewire/app/sync-queue.blade.php:41-61`; `resources/js/offline/sync.js:156-164`
**Description:**
A direct Discard button permanently removes IndexedDB records without a consequence-specific confirmation, server audit, approval, or recoverable tombstone. The only copy of a cash sale/payment/return/expense can be lost.

**Implications:** Permanent loss of financial records from normal queue management.

---

### Evidence Item 9: Payment/Invoice Amendments Drift Balances

**Grade:** [A] Confirmed
**Source:** `app/Services/PaymentService.php:32-44`; `app/Services/InvoiceService.php:163-196`
**Description:**
Payment accepts a cancelled invoice; amend/cancel posting and resubmission do not form a single demonstrably balanced state machine. Customer and cash balances may disagree with invoices/payments.

**Implications:** Financial records become inconsistent, affecting reconciliation.

---

### Evidence Item 10: Stock Reconciliation Overwrites Concurrent Movement

**Grade:** [A] Confirmed
**Source:** `app/Services/StockService.php:47-72`; no focused concurrent-process reconciliation test found
**Description:**
Reconciliation reads and writes the balance without a demonstrated `FOR UPDATE` lock or serialization strategy over the stock row. A valid concurrent movement can disappear from the stored stock balance.

**Implications:** Inventory accuracy compromised during concurrent operations.

---

### Evidence Summary

| # | Title | Grade | Source | Key Implication |
|---|-------|-------|--------|----------------|
| 1 | Tenant Scope Fail-Open | [A] | BelongsToCompany.php | Cross-company data leak |
| 2 | Stock Import Trusts Client | [A] | StockImport.php | Inventory corruption |
| 3 | Returns Mint Stock/Credit | [A] | ReturnService.php | Fake returns generate credits |
| 4 | Offline Duplicate Intents | [A] | outbox.js | Duplicate invoices/payments |
| 5 | Production Demo Credentials | [A] | SeedSuperAdmin.php | Known credentials in prod |
| 6 | Reversals Break History | [A] | ActionToast.php | Altered financial audit |
| 7 | Unbounded Pricing | [A] | SalesFlow.php | Revenue leakage |
| 8 | Discardable Financials | [A] | sync-queue.blade.php | Lost financial records |
| 9 | Balance Drift | [A] | PaymentService.php | Inconsistent finances |
| 10 | Reconciliation Overwrite | [A] | StockService.php | Inventory inaccuracy |

---

## Hypotheses

### Hypothesis 1 — Architectural Gaps, Not Implementation Bugs [Plausibility: High]

**Statement:**
The Critical findings are architectural gaps where business rules are enforced at the application layer but lack authoritative server-side validation, database-level constraints, or proper state machine definitions. The codebase has the right service layer structure but is missing the enforcement depth needed for production financial/inventory operations.

**Supporting evidence:**
- All 10 Critical findings show application-level validation without database-level enforcement [A]
- Service layer exists but relies on client-provided state for critical operations [A]
- Financial mutations are transactional but lack state machine discipline [A]

**Contradicting evidence:**
- Some database constraints exist (stock nonnegative check)
- Transactions provide atomicity but not correctness

**Verification step:**
Audit each Critical path for: (1) server-side authoritative state resolution, (2) database constraint enforcement, (3) state machine transition validation, (4) audit trail completeness.

---

### Hypothesis 2 — Offline-First Architecture Mismatch [Plausibility: High]

**Statement:**
The PWA offline architecture is incomplete — it provides transport-level idempotency (intent keys per attempt) but not business-level idempotency (one stable intent per user action). This creates a fundamental tension between offline usability and data integrity.

**Supporting evidence:**
- PR-004: Intent keys are per-transport-attempt, not per-user-action [A]
- PR-008: Queue management treats financial records as local UI state [A]
- PR-016: Offline scope, protocol evolution, multi-device undefined [A]

**Contradicting evidence:**
- Sync service has handler-level receipt writing
- Same-key receipt prevents reprocessing

**Verification step:**
Map the complete offline user journey: queue → retry → sync → receipt → confirmation. Identify where business intent identity is lost or duplicated.

---

### Hypothesis 3 — Spec-Implementation Drift [Plausibility: High]

**Statement:**
Documentation and specifications describe intended behaviors (roles, offline scope, compliance) that diverge from actual implementation. This drift creates false confidence and makes release decisions based on incorrect assumptions.

**Supporting evidence:**
- PR-014: Required roles absent, legacy roles dominate [A]
- PR-021: Docs contradict implementation on multiple fronts [A]
- PR-012: Tax compliance overstated — "Phase 2" is actually Phase 1 TLV [A]

**Contradicting evidence:**
- Core business rules doc exists and aligns with service layer
- Architecture docs accurately describe the service pattern

**Verification step:**
Create a documentation-to-code checklist for each critical workflow (invoice, payment, return, sync). Mark each claim as verified/contradicted/missing.

---

## Suspected Components

### Component: Tenant Isolation (BelongsToCompany + ActiveCompanyContext)

| Attribute | Detail |
|-----------|--------|
| Type | Model concern + singleton |
| File / path | `app/Models/Concerns/BelongsToCompany.php`, `app/Support/ActiveCompanyContext.php` |
| Responsibility | Enforce company-scoped queries globally |
| Confidence | High |
| Architecture reference | `AGENTS.md#architecture-rules` |

**Why suspected:**
Filament middleware does not initialize the context, making the global scope fail-open.

**Blast radius:**
All Filament resources, global search, relations, bulk actions, Livewire updates, imports, exports, and APIs can access cross-company data.

---

### Component: StockService

| Attribute | Detail |
|-----------|--------|
| Type | Service |
| File / path | `app/Services/StockService.php` |
| Responsibility | All stock mutations (move, increment, decrement, reconcile) |
| Confidence | High |
| Architecture reference | `AGENTS.md#architecture-rules` |

**Why suspected:**
Core stock operations lack company validation in some paths, reconciliation lacks locking, batch enforcement is incomplete.

**Blast radius:**
Inventory accuracy, cross-company stock contamination, financial integrity.

---

### Component: InvoiceService + InvoiceCalculationService

| Attribute | Detail |
|-----------|--------|
| Type | Service |
| File / path | `app/Services/InvoiceService.php`, `app/Services/InvoiceCalculationService.php` |
| Responsibility | Invoice creation, pricing, calculation, amendment |
| Confidence | High |
| Architecture reference | `docs/BUSINESS_RULES.md` |

**Why suspected:**
Client-provided prices accepted without server authority, amendment/cancel/resubmit breaks balance invariants.

**Blast radius:**
Revenue leakage, incorrect customer balances, non-compliant invoices.

---

### Component: PaymentService

| Attribute | Detail |
|-----------|--------|
| Type | Service |
| File / path | `app/Services/PaymentService.php` |
| Responsibility | Payment collection, allocation, idempotency |
| Confidence | High |
| Architecture reference | `docs/BUSINESS_RULES.md` |

**Why suspected:**
Accepts cancelled invoices, amendment paths don't form balanced state machine.

**Blast radius:**
Cash balance drift, unallocated payments, reconciliation failures.

---

### Component: ReturnService

| Attribute | Detail |
|-----------|--------|
| Type | Service |
| File / path | `app/Services/ReturnService.php` |
| Responsibility | Return processing, credit notes, stock restoration |
| Confidence | High |
| Architecture reference | `docs/BUSINESS_RULES.md` |

**Why suspected:**
No provenance check on returned items, unlimited returns against balance, no batch enforcement.

**Blast radius:**
Fake returns generate stock and credits without corresponding sales.

---

### Component: SyncService + Offline Outbox

| Attribute | Detail |
|-----------|--------|
| Type | Service + JS module |
| File / path | `app/Services/Sync/SyncService.php`, `resources/js/offline/outbox.js` |
| Responsibility | Offline queue, sync processing, conflict resolution |
| Confidence | High |
| Architecture reference | `docs/ARCHITECTURE.md#offline-sync` |

**Why suspected:**
Intent keys are per-attempt not per-action, queue discard is permanent, no multi-device policy.

**Blast radius:**
Duplicate business intents, permanent financial data loss, offline unusability.

---

## Related Requirements

| Requirement | Type | Source | Status |
|-------------|------|--------|--------|
| Company data isolation | NFR | `AGENTS.md#architecture-rules` | Violated (PR-001) |
| Money mutations in DB::transaction | FR | `AGENTS.md#architecture-rules` | Partial — atomic but not correct |
| Stock changes ONLY through StockService | FR | `AGENTS.md#architecture-rules` | At Risk (PR-002, PR-010) |
| Server-authoritative pricing | FR | `docs/BUSINESS_RULES.md` | Violated (PR-007) |
| Confirmation modal for destructive/financial actions | FR | `AGENTS.md#security-rules` | Violated (PR-008) |
| Bilingual AR/EN | NFR | `AGENTS.md#architecture-rules` | Met |
| RTL support | NFR | `AGENTS.md#architecture-rules` | Met |
| Rate limiting | NFR | `AGENTS.md#security-rules` | Partial (defined but untested) |
| Password hashing = argon2id | NFR | `AGENTS.md#security-rules` | Met |
| Session httpOnly + secure | NFR | `AGENTS.md#security-rules` | Met |

---

## Recommended Action

**Planning Response:** Option C — Escalate to Planning

Given the scope (10 Critical + 21 High findings across the entire system), individual fix stories would miss the architectural patterns. The root cause is systemic: the codebase has the right structure (services, transactions, traits) but lacks enforcement depth at critical trust boundaries.

### Recommended Next Steps

1. **Create Architecture Decision Records (ADRs)** for:
   - Tenant isolation: mandatory fail-closed resolution at every entry point
   - Financial state machines: allowed transitions, posting/reversal effects
   - Offline idempotency: one stable intent per user action, not per transport
   - Pricing authority: server-resolved price policy with permission/bounds/audit
   - Reversal process: compensating transactions, privileged approval, immutable audit

2. **Prioritize by blast radius:**
   - **P0 (launch blockers):** PR-001 (tenant), PR-004 (offline dupes), PR-005 (demo creds), PR-007 (pricing), PR-009 (balance drift)
   - **P1 (pre-launch):** PR-002 (stock import), PR-003 (returns), PR-006 (reversals), PR-008 (discard), PR-010 (reconciliation)
   - **P2 (post-launch hardening):** PR-011 through PR-031

3. **Create fix stories** for each P0 finding with:
   - Specific verification steps from the evidence
   - Test requirements (unit, feature, E2E)
   - Rollback/compensation plan

---

## Open Questions

1. What is the approved pricing policy (floor/ceiling ranges, discount authorization, approval workflow)?
2. What is the approved reversal/cancellation policy (TTL, roles, reason requirements)?
3. What is the offline scope decision (single device, multi-device, supported browsers)?
4. What is the batch tracking policy (FEFO enforcement, damaged stock handling)?
5. What is the ETA compliance scope (Egypt only, Phase 2 timeline, certification provider)?

---

## Update History

| Version | Date | Summary of Changes |
|---------|------|--------------------|
| 1.0 | 2026-07-28 | Initial investigation — 10 Critical + 21 High findings from production readiness audit |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
