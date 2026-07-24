# Investigation Case File: v1.0-deferred-items

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — Phase Roadmap deferred items per PRD v1.1 §3
**Severity:** Deferred (not blocking Beta)
**Status:** Open — Cataloged for v1.0 planning
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-v1-0-deferred-items-2026-07-19.md`

---

## Summary

**One-sentence description:**
Eleven v1.0 deferred items are explicitly listed in PRD v1.1 and Build Guide as "go-live gate" or "post-beta" — none are required for Beta Done. This file catalogs them with current state assessment for future sprint planning.

**Expected behavior:** These features ship in v1.0 (post-beta, pre-go-live).

**Actual behavior:** All are explicitly deferred. Some have partial implementation (expenses, van transfers, returns in admin); others are entirely missing (cash recon UI, supplier PO workflow, transit/landed cost, batch/COA/expiry, Odoo migration).

**User / business impact:** None for Beta. These are go-live requirements for production deployment with real customers.

---

## Symptom Details

**Trigger conditions:** Structural — explicitly deferred per PRD v1.1 §3 and Build Guide.

**Environments affected:** Future (v1.0 track).

**First observed:** PRD v1.1 phase map (2026-07-19).

**Frequency:** Constant (deliberate deferral).

**Reproducible:** N/A — intentional deferral.

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed in code/docs
> - **[B] Probable** — inferred from partial implementation
> - **[C] Speculative** — not yet investigated

### Evidence Item 1: Returns (partial admin implementation)

**Grade:** [A]
**Source:** `app/Filament/Resources/ReturnRecordResource.php`, `app/Services/ReturnService.php`, `app/Livewire/App/LogReturn.php`
**Description:** Admin has full CRUD for `ReturnRecordResource`. Rep has `LogReturn` page with basic form. Missing: return-to-invoice linkage (Evidence 9 in B0 audit), return reason dropdown, photo capture, stock impact preview, undo.
**Implications:** Core logic exists; rep UI incomplete; no supplier return workflow.

---

### Evidence Item 2: Cash Reconciliation UI (missing)

**Grade:** [A]
**Source:** `grep -rn "cash.reconcile\|cashbox.reconcil\|daily.reconcil" app/ resources/` → 0 results
**Description:** Build Guide §7 mentions "cash recon UI" as v1.0. No Filament page, no rep component, no service for reconciliation workflow. CashBox model exists with `opening_balance`, `closing_balance`.
**Implications:** Full greenfield for v1.0. Needs: reconciliation form, variance report, manager approval, audit trail.

---

### Evidence Item 3: Expenses (partial — admin only)

**Grade:** [A]
**Source:** `app/Filament/Resources/ExpenseResource.php` (full CRUD), `app/Livewire/App/LogExpense.php` (basic form)
**Description:** Admin has full ExpenseResource. Rep has LogExpense page with basic form (category, amount, note). Missing: receipt photo capture, date picker (backdate), recurring expense flag, running total, cashbox warning, budget indicator.
**Implications:** Core exists; rep UX gaps per B0 audit (M7, G1, G4).

---

### Evidence Item 4: Van Transfers (partial admin implementation)

**Grade:** [A]
**Source:** `app/Filament/Resources/VanTransferResource.php`, `app/Services/VanTransferService.php`
**Description:** Admin has full VanTransferResource with ship/receive actions and item lines. Service handles stock movement. Missing: rep-side van transfer page, transfer tracking UI, in-transit quantity column in StockResource (Amendment R3), ETA display.
**Implications:** Admin workflow complete; rep visibility missing; in-transit qty column needed for Stock import (Amendment R3).

---

### Evidence Item 5: Supplier Comparison + POs (missing)

**Grade:** [A]
**Source:** `app/Filament/Resources/PurchaseRequestResource.php` (no comparison), `grep -rn "purchase.order\|supplier.compar" app/` → 0 results
**Description:** PUR-4 requires "supplier comparison + POs". Current: PurchaseRequest has single supplier per request. No PO model, no comparison UI, no multi-supplier offer evaluation.
**Implications:** Major v1.0 greenfield. Needs: PO model + service, comparison page, supplier rating fields.

---

### Evidence Item 6: Transit + Landed Cost (missing)

**Grade:** [A]
**Source:** `grep -rn "transit\|landed.cost\|in.transit\|freight\|customs" app/` → 0 results
**Description:** Amendment R3 requires "read-only in-transit quantity column" in Stock import. No transit tracking, no landed cost calculation (freight + customs + insurance allocated to product cost).
**Implications:** Required for accurate stock visibility and true COGS. v1.0 scope.

---

### Evidence Item 7: Batch/COA/Expiry + Backfill (missing)

**Grade:** [A]
**Source:** `grep -rn "batch\|lot\|expiry\|coa\|certificate" app/Models/` → 0 results
**Description:** v1.0 requires batch/lot tracking, expiry dates, COA (certificate of analysis), and historical backfill for existing stock. Current `Stock` model has no batch/lot fields.
**Implications:** Major schema change. Needs: Batch model, expiry alerts, FEFO logic, backfill migration.

---

### Evidence Item 8: ETA Full Compliance — ZATCA Phase 2 (Go-Live Gate)

**Grade:** [A]
**Source:** `docs/spec/Jawla_Beta_PRD_v1.1.md:54` ("ETA full compliance (**go-live gate**)")
**Description:** ZATCA Phase 2 compliance is explicit **go-live gate**. Current: ZATCA Phase 1 implemented (QR code, TLV). Phase 2 requires: CSID (cryptographic stamp identifier), invoice cryptographic stamp, clearance request for B2B, reporting for B2C, onboarding flow.
**Implications:** Hard blocker for Saudi production. Must complete before any go-live.

---

### Evidence Item 9: Full Reports/Exports/Map (partial)

**Grade:** [A]
**Source:** `app/Filament/Pages/ReportsPage.php` (basic), `app/Filament/Widgets/*` (8 widgets), `app/Filament/Resources/*/Pages/List*.php` (Filament exports)
**Description:** Admin has ReportsPage with basic tables and Filament Excel export on resources. Missing: scheduled reports, custom report builder, PDF batch export, route map visualization (rep visits on map), rep performance dashboards beyond 4 widgets.
**Implications:** Filament exports cover 80%; scheduled/custom reports and map view are v1.0.

---

### Evidence Item 10: Odoo Migration (missing)

**Grade:** [A]
**Source:** `grep -rn "odoo\|erpnext" app/ database/ docs/` → 0 results
**Description:** Build Guide §2.1 explicitly evaluates and **rejects** ERPNext; Odoo migration listed in v1.0 as deferred. No migration scripts, no mapping docs.
**Implications:** If client has existing Odoo data, migration tooling needed for go-live.

---

### Evidence Summary

| #   | Item                         | Grade | Current State         | v1.0 Effort  |
| --- | ---------------------------- | ----- | --------------------- | ------------ |
| 1   | Returns                      | A     | Admin ✅, Rep partial | Medium       |
| 2   | Cash Reconciliation UI       | A     | Missing               | Large        |
| 3   | Expenses                     | A     | Admin ✅, Rep partial | Medium       |
| 4   | Van Transfers                | A     | Admin ✅, Rep missing | Medium       |
| 5   | Supplier Comparison + POs    | A     | Missing               | Large        |
| 6   | Transit + Landed Cost        | A     | Missing               | Large        |
| 7   | Batch/COA/Expiry + Backfill  | A     | Missing               | Large        |
| 8   | ZATCA Phase 2 (Go-Live Gate) | A     | Phase 1 done          | **Critical** |
| 9   | Reports/Exports/Map          | A     | Partial (80%)         | Medium       |
| 10  | Odoo Migration               | A     | Not started           | Large        |

---

## Hypotheses

### Hypothesis 1 — These were deliberately deferred to keep Beta scope minimal [Plausibility: High]

**Statement:** PRD v1.1 §3 explicitly lists these as "v1.0" track. The team agreed to ship Beta with only B0–B8 core.

**Supporting evidence:** PRD phase map shows all 11 items under v1.0 column; none under B0–B8.

**Contradicting evidence:** None — this is by design.

**Verification step:** Confirm with owner that v1.0 sprint starts after Beta Done demo.

---

### Hypothesis 2 — ZATCA Phase 2 is the true go-live blocker [Plausibility: High]

**Statement:** Among all v1.0 items, ZATCA Phase 2 is the only one labeled "**go-live gate**" — everything else can ship after go-live with a patch.

**Supporting evidence:** PRD v1.1 §3 row for v1.0: "ETA full compliance (**go-live gate**)".

**Contradicting evidence:** None.

**Verification step:** Prioritize ZATCA Phase 2 architecture spike immediately after Beta Done.

---

### Hypothesis 3 — Some v1.0 items have hidden dependencies on Beta code [Plausibility: Medium]

**Statement:** Batch/COA/Expiry requires Stock model changes that affect Sales Flow, Returns, Van Transfers. Transit/landed cost affects Stock Import. Cash Recon needs WorkSession + CashBox + Payment integration.

**Supporting evidence:** Stock model is central to B3–B5 features.

**Contradicting evidence:** None yet.

**Verification step:** Architecture review before v1.0 sprint to identify migration sequencing.

---

## Suspected Components

| Component           | Type                    | Files                                                                    | Blast Radius            |
| ------------------- | ----------------------- | ------------------------------------------------------------------------ | ----------------------- |
| ZATCA Phase 2       | Service + Config        | New `ZatcaPhase2Service`, `csid` storage, onboarding flow                | Saudi production only   |
| Purchase Order      | Model + Service         | New `PurchaseOrder`, `PurchaseOrderItem`, `PurchaseOrderService`         | Purchasing workflow     |
| Supplier Comparison | Filament Page           | New `SupplierComparisonPage`                                             | Purchasing UI           |
| Cash Reconciliation | Filament Page + Service | New `CashReconciliationPage`, `CashReconciliationService`                | Finance workflow        |
| Transit/Landed Cost | Model + Service         | `Stock` add `in_transit_qty`, `LandedCostService`                        | Stock accuracy          |
| Batch/COA/Expiry    | Model + Migration       | `Batch` model, `Stock` add `batch_id`, `expiry_date`                     | Stock + Sales + Returns |
| Odoo Migration      | Scripts                 | `database/migrations/*odoo*`, `app/Console/Commands/MigrateFromOdoo.php` | One-time data import    |

---

## Related Requirements

| Requirement                                  | Source                  | Status           |
| -------------------------------------------- | ----------------------- | ---------------- |
| Returns, cash recon, expenses, van transfers | PRD v1.0                | Deferred         |
| Supplier comparison + POs                    | PRD PUR-4               | Deferred         |
| Transit + landed cost                        | Amendment R3            | Deferred         |
| Batch/COA/expiry + backfill                  | PRD STK-3               | Deferred         |
| ZATCA Phase 2                                | PRD ETA full compliance | **Go-Live Gate** |
| Full reports/exports/map                     | PRD v1.0                | Deferred         |
| Odoo migration                               | Build Guide §2.1        | Deferred         |

---

## Recommended Action

**Planning Response:** Option C — Escalate to planning (v1.0 sprint planning)

**Rationale:** These are explicitly deferred per PRD. No investigation needed now. They require a dedicated v1.0 planning session after Beta Done.

**Specific gaps to address in planning:**

1. ZATCA Phase 2 architecture spike (top priority — go-live gate)
2. v1.0 sprint sequencing (which items can parallelize)
3. Schema migration strategy for Batch/COA/Expiry (affects Stock)
4. Whether Odoo migration is needed (client decision)

---

## Open Questions

1. **ZATCA Phase 2 timeline:** When does the client need Saudi production? Drives entire v1.0 schedule.
2. **Odoo migration scope:** Does the client have existing Odoo data? If not, can drop from v1.0.
3. **Batch/COA/Expiry scope:** Is full lot tracking needed or just expiry dates? Affects schema complexity.
4. **Cash recon UX:** Does the client have a defined reconciliation workflow (variance thresholds, approval chain) or need discovery?
5. **Map visualization:** Route map for rep visits — Leaflet pins or full routing? (Amendment R3 mentions "read-only in-transit quantity column" but not map).

---

## Update History

| Version | Date       | Summary of Changes                        |
| ------- | ---------- | ----------------------------------------- |
| 1.0     | 2026-07-19 | Initial cataloging of v1.0 deferred items |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
