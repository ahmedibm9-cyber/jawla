# Investigation Case File: b7-purchase-requests-completion

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — gap B7 from Phase Roadmap
**Severity:** Degraded UX / Missing functionality (blocks Beta B7 completion)
**Status:** Open
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-b7-purchase-requests-completion-2026-07-19.md`

---

## Summary

**One-sentence description:**
The B7 phase (Purchase Requests) has dual-review admin UI implemented but lacks Purchase Order generation upon purchasing approval, supplier comparison UI, and has a schema mismatch between migrations that will cause runtime constraint errors.

**Expected behavior:** Per PRD v1.1 PUR-1…4 and B7 phase: Rep submits purchase offer → Sales Manager reviews → Purchasing reviews → upon Purchasing approval, a Purchase Order is automatically generated → supplier comparison UI allows evaluating multiple offers.

**Actual behavior:**

- Dual-review admin actions exist (sales_approve, sales_reject, purchasing_approve, purchasing_reject) with proper role guards
- **No PO generation** when status becomes `purchasing_approved`
- **No supplier comparison UI** (multiple offers for same product can't be compared side-by-side)
- **Schema mismatch**: Migration 1 CHECK constraint has `['pending', 'reviewed_by_sales', 'approved', 'rejected']` but Migration 2 ALTER tries to change to `['pending', 'sales_approved', 'purchasing_approved', 'rejected_by_sales', 'rejected_by_purchasing']` — the old constraint will cause insertion failures

**User / business impact:** B7 phase cannot be marked complete. Purchasing team has no automated PO creation workflow. Reps/managers cannot compare supplier offers.

---

## Symptom Details

**Trigger conditions:** Structural — code-level gaps and migration conflict.

**Environments affected:** All (code-level absence).

**First observed:** 2026-07-19 (phase roadmap audit)

**Frequency:** Constant (code-level absence)

**Reproducible:** Yes

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed, reproducible, or corroborated by 2+ independent sources.
> - **[B] Probable** — single source, code-read inference, or intermittent.
> - **[C] Speculative** — pattern match or untested hypothesis only.

### Evidence Item 1: Dual-review admin actions exist

**Grade:** [A]
**Source:** `app/Filament/Resources/PurchaseRequestResource.php:97-148`
**Description:** Four actions implemented with role guards:

- `sales_approve` → status `sales_approved` (admin/sales_manager)
- `sales_reject` → status `rejected_by_sales` (admin/sales_manager)
- `purchasing_approve` → status `purchasing_approved` (admin/purchasing)
- `purchasing_reject` → status `rejected_by_purchasing` (admin/purchasing)

All use `requiresConfirmation()` and set reviewed_by/at fields.

**Implications:** Admin review workflow is complete. Next step (PO generation) missing.

---

### Evidence Item 2: No PO generation on purchasing_approved

**Grade:** [A]
**Source:** `grep -rn "purchasing_approved" app/` → only Filament action updates status; no service, event listener, or observer creates PO
**Description:** When `purchasing_approve` action runs, it only updates the PurchaseRequest status to `purchasing_approved`. No `PurchaseOrder` model is created, no `Stock` allocation occurs, no notification sent.

**Verbatim excerpt:**

```php
// PurchaseRequestResource.php:128-134
Action::make('purchasing_approve')
    ->action(function (PurchaseRequest $r) {
        $r->update([
            'status' => 'purchasing_approved',
            'purchasing_reviewed_by' => Auth::id(),
            'purchasing_reviewed_at' => now(),
        ]);
    })
```

**Implications:** Missing the core PUR-1…4 requirement: "purchase request → PO → stock allocation". Needs a service (`PurchaseOrderService::createFromPurchaseRequest`) called after status change.

---

### Evidence Item 3: No supplier comparison UI

**Grade:** [A]
**Source:** `grep -rn "supplier.*compar\|compar.*supplier" app/ resources/` → 0 results; `PurchaseRequestResource` table shows single supplier per row; no multi-supplier view
**Description:** Multiple purchase requests for the same product can exist with different suppliers/prices. No Filament page or Rep component allows side-by-side comparison.

**Implications:** PUR-3/PUR-4 "dual review" implies comparison but UI missing. Needs a Filament page (e.g., `SupplierComparisonPage`) or widget.

---

### Evidence Item 4: Migration CHECK constraint mismatch

**Grade:** [A]
**Source:** `database/migrations/2026_07_12_100038_create_purchase_requests_table.php:21` vs `database/migrations/2026_07_13_000005_alter_purchase_requests_expand_review.php:12-13`

**Verbatim excerpt:**

```php
// Migration 1 (create table):
$table->enum('status', ['pending', 'reviewed_by_sales', 'approved', 'rejected'])->default('pending');

// Migration 2 (alter):
DB::statement('ALTER TABLE purchase_requests DROP CONSTRAINT IF EXISTS purchase_requests_status_check');
DB::statement("ALTER TABLE purchase_requests ADD CONSTRAINT purchase_requests_status_check CHECK (status::text = ANY (ARRAY['pending', 'sales_approved', 'purchasing_approved', 'rejected_by_sales', 'rejected_by_purchasing']))");
```

**Description:** Migration 1 creates CHECK constraint with 4 values. Migration 2 tries to DROP and ADD a NEW constraint with 5 DIFFERENT values. However, the `DROP CONSTRAINT IF EXISTS` uses a hardcoded name `purchase_requests_status_check` which may not match the actual constraint name PostgreSQL generated (typically `purchase_requests_status_check` but not guaranteed). Also, the old values are not a subset of new values — `reviewed_by_sales` and `approved` are not in the new list, so existing rows with those values would violate the new constraint.

**Implications:** Fresh migrations will fail or produce invalid schema. Must fix before B7 ships.

---

### Evidence Item 5: Model $fillable and $casts include expanded review fields

**Grade:** [A]
**Source:** `app/Models/PurchaseRequest.php:15-28`
**Description:** Model correctly includes all dual-review fields: `sales_reviewed_by`, `sales_reviewed_at`, `sales_review_notes`, `purchasing_reviewed_by`, `purchasing_reviewed_at`, `purchasing_review_notes`. Casts include datetime casts for the reviewed_at fields.

**Implications:** Model is ready for dual-review; only migration constraint and PO generation missing.

---

### Evidence Summary

| #   | Title                                   | Grade | Source                      | Key Implication              |
| --- | --------------------------------------- | ----- | --------------------------- | ---------------------------- |
| 1   | Dual-review admin actions exist         | A     | PurchaseRequestResource.php | Admin workflow complete      |
| 2   | No PO generation on purchasing_approved | A     | grep + code read            | Core PUR requirement missing |
| 3   | No supplier comparison UI               | A     | grep + Filament resource    | PUR-3/4 comparison missing   |
| 4   | Migration CHECK constraint mismatch     | A     | Two migration files         | Fresh install will fail      |
| 5   | Model includes dual-review fields       | A     | PurchaseRequest.php         | Model ready                  |

---

## Hypotheses

### Hypothesis 1 — PO generation was deferred because PUR-1…4 only specified "dual review" not "PO creation" [Plausibility: High]

**Statement:** The PUR requirements mention "dual review" (PUR-3) but the PO creation step was considered a v1.0 item (Purchase Orders), so B7 stopped at review without completing the PO handoff.

**Supporting evidence:**

- Evidence 2 [A] — PO generation completely absent
- PUR-1…4 in PRD §1: "purchase-offer submission + Sales/Purchasing dual review" — no explicit PO generation

**Contradicting evidence:** PUR-4 implies the flow ends with a PO.

**Verification step:** Check PRD PUR-4 text; if it says "create PO", then this is a gap.

---

### Hypothesis 2 — Supplier comparison was never designed; only single-supplier per request assumed [Plausibility: Medium]

**Statement:** Each PurchaseRequest ties to one supplier. The "comparison" was expected to happen manually by viewing multiple requests in the table, not a dedicated comparison UI.

**Supporting evidence:**

- Evidence 3 [A] — no comparison UI exists
- PurchaseRequest has single `supplier_id`

**Contradicting evidence:** PUR-3 says "Sales/Purchasing dual review" — implies comparing options.

**Verification step:** Confirm with owner if multi-supplier comparison is required for beta or v1.0.

---

### Hypothesis 3 — Migration constraint mismatch is a latent bug from refactoring [Plausibility: High]

**Statement:** The second migration was added after the model was updated to 5 statuses, but the constraint name and value migration wasn't tested on a fresh database.

**Supporting evidence:**

- Evidence 4 [A] — constraint values don't match; `DROP CONSTRAINT IF EXISTS` uses hardcoded name
- The second migration's `down()` method references old constraint with different values

**Contradicting evidence:** None.

**Verification step:** Run `php artisan migrate:fresh --seed` on a clean DB — will fail on constraint creation.

---

## Suspected Components

### Component: PurchaseRequest Model & Migrations

| Attribute              | Detail                                                                                                                                                                                          |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Type                   | Domain model + schema                                                                                                                                                                           |
| File / path            | `app/Models/PurchaseRequest.php`, `database/migrations/2026_07_12_100038_create_purchase_requests_table.php`, `database/migrations/2026_07_13_000005_alter_purchase_requests_expand_review.php` |
| Responsibility         | Purchase request lifecycle, dual-review status, PO trigger                                                                                                                                      |
| Confidence             | High (grade-A evidence)                                                                                                                                                                         |
| Architecture reference | PRD PUR-1…4, B7 phase                                                                                                                                                                           |

**Why suspected:** Evidence 4 [Evidence 1, 2, 4, 5 all point here. Migration constraint will break fresh installs.

**Blast radius:** Fresh `migrate:fresh` fails; existing data with old status values may violate new constraint; PO generation missing affects entire purchasing workflow.

---

### Component: PurchaseRequestResource (Filament Admin)

| Attribute              | Detail                                               |
| ---------------------- | ---------------------------------------------------- |
| Type                   | Admin UI module                                      |
| File / path            | `app/Filament/Resources/PurchaseRequestResource.php` |
| Responsibility         | Dual-review actions, list/table, filters             |
| Confidence             | High                                                 |
| Architecture reference | B7 phase, PUR-3                                      |

**Why suspected:** Evidence 1, 3 — has review actions but missing PO generation action and comparison view.

**Blast radius:** Adding PO generation action here is low-risk; comparison view needs new page.

---

### Component: PurchaseOrderService (Missing)

| Attribute              | Detail                                                                            |
| ---------------------- | --------------------------------------------------------------------------------- |
| Type                   | Service layer                                                                     |
| File / path            | `app/Services/PurchaseOrderService.php` (to be created)                           |
| Responsibility         | Create PO from approved PurchaseRequest, allocate stock, notify supplier          |
| Confidence             | High (required by PUR-4)                                                          |
| Architecture reference | CLAUDE.md service rules — all money/stock mutations via Service in DB transaction |

**Why suspected:** Evidence 2 — no PO generation exists anywhere.

**Blast radius:** New service; must use `StockService` for allocation, `DB::transaction`, create `PurchaseOrder` + `PurchaseOrderItem` models.

---

### Component: Supplier Model & Migration

| Attribute      | Detail                                                                       |
| -------------- | ---------------------------------------------------------------------------- |
| Type           | Domain model                                                                 |
| File / path    | `app/Models/Supplier.php`, `database/migrations/*create_suppliers_table.php` |
| Responsibility | Supplier master data for comparison                                          |
| Confidence     | Medium                                                                       |

**Why suspected:** Evidence 3 — comparison needs supplier data (price, terms, lead time). Current model has basic fields; may need `lead_time_days`, `reliability_score` for comparison.

**Blast radius:** Low — additive fields only.

---

## Related Requirements

| Requirement                               | Type      | Source      | Status                                        |
| ----------------------------------------- | --------- | ----------- | --------------------------------------------- |
| PUR-1 purchase offer submission           | FR        | PRD v1.1 §1 | ✅ (Rep side done)                            |
| PUR-2 Sales Manager review                | FR        | PRD v1.1 §1 | ✅ (admin action)                             |
| PUR-3 Purchasing dual review              | FR        | PRD v1.1 §1 | ✅ (admin action)                             |
| PUR-4 PO generation + supplier comparison | FR        | PRD v1.1 §1 | **Violated** (PO missing, comparison missing) |
| REQ-PUR-1…4                               | FR        | PRD v1.1 §1 | **Partial**                                   |
| B7 phase completion                       | Milestone | Build Guide | **Blocked**                                   |

---

## Recommended Action

**Planning Response:** Option A — Create Fix Stories (multiple)

### Option A — Create Fix Stories

| Story | Title                                                              | Priority                  |
| ----- | ------------------------------------------------------------------ | ------------------------- |
| B7.1  | Fix PurchaseRequest migration CHECK constraint                     | P0 (blocks fresh install) |
| B7.2  | Create PurchaseOrderService + PO generation on purchasing_approved | P0 (core PUR-4)           |
| B7.3  | Add PO generation action to PurchaseRequestResource                | P0 (admin workflow)       |
| B7.4  | Build Supplier Comparison Filament page/widget                     | P1 (PUR-3/4 comparison)   |
| B7.5  | Add Supplier fields for comparison (lead_time, reliability)        | P2 (enhancement)          |

**Suggested order:** B7.1 → B7.2 → B7.3 → B7.4 → B7.5

---

### Story Draft: B7.1 — Fix PurchaseRequest Migration Constraint

| Field                     | Value                                                                                                              |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| Epic                      | B7 Purchase Requests                                                                                               |
| Story title               | Fix PurchaseRequest migration CHECK constraint mismatch                                                            |
| As a                      | DevOps / Release Engineer                                                                                          |
| I want                    | Fresh `migrate:fresh --seed` to succeed                                                                            |
| So that                   | CI/CD and new environments work without manual SQL fixes                                                           |
| Suggested AC 1            | Migration 1 creates table with final 5-value enum or no CHECK; Migration 2 only adds columns, no constraint change |
| Suggested AC 2            | `php artisan migrate:fresh --seed` passes on clean DB                                                              |
| Suggested AC 3            | Existing status values in seeders match new enum                                                                   |
| Suspected files / modules | Two migration files, PurchaseRequest model $fillable                                                               |
| Investigation reference   | `bmad-output/investigation-b7-purchase-requests-completion-2026-07-19.md`                                          |

---

### Story Draft: B7.2 — PurchaseOrderService + PO Generation

| Field                     | Value                                                                                                                                                                  |
| ------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Epic                      | B7 Purchase Requests                                                                                                                                                   |
| Story title               | PurchaseOrderService + auto PO generation on purchasing_approved                                                                                                       |
| As a                      | Purchasing Manager                                                                                                                                                     |
| I want                    | A Purchase Order automatically created when I approve a purchase request                                                                                               |
| So that                   | The supplier gets a formal PO, stock is allocated, and the request is closed                                                                                           |
| Suggested AC 1            | `PurchaseOrderService::createFromPurchaseRequest(PurchaseRequest $r)` creates PO + POItem, uses `StockService` to allocate/reserve stock, wraps in `DB::transaction()` |
| Suggested AC 2            | Called from `PurchaseRequestResource::purchasing_approve` action AFTER status update                                                                                   |
| Suggested AC 3            | PO number sequential per company; includes supplier, product, qty, price, delivery date, terms                                                                         |
| Suggested AC 4            | Feature test: rep submits offer → sales approves → purchasing approves → PO exists with correct data                                                                   |
| Suspected files / modules | New `app/Services/PurchaseOrderService.php`, `PurchaseOrder` model, `PurchaseOrderItem` model, `PurchaseRequestResource` action                                        |
| Investigation reference   | `bmad-output/investigation-b7-purchase-requests-completion-2026-07-19.md`                                                                                              |

---

### Story Draft: B7.3 — PO Generation Admin Action

| Field                   | Value                                                                               |
| ----------------------- | ----------------------------------------------------------------------------------- |
| Epic                    | B7 Purchase Requests                                                                |
| Story title             | Add "Generate PO" action for purchasing_approved requests                           |
| As a                    | Purchasing Manager                                                                  |
| I want                  | A "Generate PO" button on purchasing_approved requests                              |
| So that                 | I can manually trigger PO creation if auto-generation fails or for re-generation    |
| Suggested AC 1          | Action visible only when status = `purchasing_approved` && user has purchasing role |
| Suggested AC 2          | Calls `PurchaseOrderService::createFromPurchaseRequest($record)`                    |
| Suggested AC 3          | Shows success toast with PO number link                                             |
| Investigation reference | `bmad-output/investigation-b7-purchase-requests-completion-2026-07-19.md`           |

---

### Story Draft: B7.4 — Supplier Comparison Page

| Field                   | Value                                                                                                    |
| ----------------------- | -------------------------------------------------------------------------------------------------------- |
| Epic                    | B7 Purchase Requests                                                                                     |
| Story title             | Supplier Comparison Filament page for purchase requests                                                  |
| As a                    | Purchasing Manager                                                                                       |
| I want                  | A page showing all purchase requests for a product grouped by supplier with price/terms comparison       |
| So that                 | I can choose the best offer before approving                                                             |
| Suggested AC 1          | Filament page `SupplierComparison` accessible from `PurchaseRequestResource` header action               |
| Suggested AC 2          | Table grouped by product → rows per supplier with price, qty, payment_terms, status badges               |
| Suggested AC 3          | "Select Best" action sets other requests to `rejected_by_purchasing` and chosen to `purchasing_approved` |
| Investigation reference | `bmad-output/investigation-b7-purchase-requests-completion-2026-07-19.md`                                |

---

## Open Questions

1. **PO vs ProformaInvoice:** Should purchase PO use the existing `ProformaInvoice` model or a new `PurchaseOrder` model? (PRD mentions "PO" specifically for purchasing; proforma is for sales.)

2. **Stock allocation timing:** Should PO generation reserve stock immediately (allocation) or only on goods receipt? (PRD PUR-4 mentions "stock allocation" — likely reservation.)

3. **Supplier fields:** Does Supplier model need `lead_time_days`, `minimum_order_qty`, `reliability_score` for comparison? (Currently only basic fields.)

4. **Status enum final values:** Should the enum be exactly the 5 values in Migration 2, or does `reviewed_by_sales` (Migration 1) need to be kept for backward compatibility? Recommendation: use Migration 2's 5 values; update seeders.

---

## Update History

| Version | Date       | Summary of Changes              |
| ------- | ---------- | ------------------------------- |
| 1.0     | 2026-07-19 | Initial investigation case file |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
