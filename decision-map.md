# Decision Map — Architecture Deepening

Goal: surface and resolve design decisions blocking architecture improvements.
See `docs/ARCHITECTURE.md` for stack overview.

## #1: NumberSequenceService — sequential + gapless

Blocked by: none
Type: Prototype

### Question

Current `generate()` uses `random_int()` — non-sequential, non-deterministic.
Replace with sequential per `(docType, company_id, year)` using row-level
`FOR UPDATE` lock on `naming_series` table.

Pre-decided:
- Year-aware format: `{prefix}-{company_abbr}-{year}-{padded}`
  (e.g. `INV-GPC-2026-00001`)
- Uses existing `NamingSeries` model (`current_number`, `pad_length`)
- Lock: `DB::transaction()` → `lockForUpdate()` → increment → save → return
- Auto-create `NamingSeries` row if missing (default prefix from docType)
- Seeder populates rows for `sales_invoice`, `proforma`, `return`, `expense`,
  `purchase_request`

### Answer

Implemented in `app/Services/NumberSequenceService.php`:
- `DB::transaction()` with `lockForUpdate()` on `NamingSeries` row
- Format: `{prefix}-{company_abbr}-{year}-{padded_number}`
- Auto-creates NamingSeries row if missing (prefix derived from docType)
- Company abbreviation looked up per call (keeps interface clean)
- 6 Pest tests cover: formatted output, sequential, per-docType isolation,
  per-company isolation, auto-create, respects existing seed data
- DemoSeeder already seeds `sales_invoice` (INV) and `proforma_invoice` (PF)

## #2: Extract InvoiceCalculationService

Blocked by: none (independent)
Type: Grilling + Prototype

### Question

`InvoiceService::create` and `QuotationFlow::createProforma` both inline identical
calculation logic (line_total = qty × unit_price, VAT filtering, grand total).
Extract behind a standalone contract.

Pre-decided:
- Standalone `Contracts\InvoiceCalculationService` with its own seam
- Raw inputs (float qty, float price, bool vatApplicable) — not Eloquent models
- Tests need no DB (pure math assertions)

Open decisions:
1. Single method `calculate(array $lines, float $vatPercent): CalculationResult`
   vs multi-method (calculateLineTotal, calculateVat, etc.)?
2. Does PricingService get absorbed into this, or stay separate?
3. Does ZATCA QR data construction live here or in PdfService?

### Answer

(filled when resolved)

## #3: QuotationFlow — deepen via service seams

Blocked by: #1, #2
Type: Prototype

### Question

`QuotationFlow::createProforma()` generates proforma numbers inline (bypassing
NumberSequenceService), calculates VAT/totals inline (bypassing pricing), and
writes to `ProformaInvoice` / `ProformaInvoiceItem` without a transaction.
Deepen by delegating to #1 and #2.

Open decisions:
1. Does `ProformaInvoice::create()` move into a `ProformaService` for
   transaction safety, or stay in the Livewire component?
2. Does `confirmPrice()` validation move to `PricingService`?
3. After this ticket, do we fix `VisitFlow` and `SubmitPurchaseOffer` (same
   pattern — direct model writes) in a separate ticket?

### Answer

(filled when resolved)

## #4: Policy gaps — 7 unprotected resources + 2 undefined Gates

Blocked by: none
Type: Grilling

### Question

7 of 13 Filament resources have no Policy class. 2 Gates in `ProductResource`
(`products.manage_prices`, `products.view_cost`) are used but never defined
(silently return false).

| Resource | Currently unprotected |
|---|---|
| InvoiceResource | view/edit: any authenticated user |
| ProformaInvoiceResource | view/edit: any authenticated user |
| DailyVisitAssignmentResource | view/edit: any authenticated user |
| PurchaseRequestResource | view/edit: any authenticated user |
| ComplaintResource | view/edit: any authenticated user |
| AlarmResource | view: any authenticated user |
| PriceQuotationRequestResource | view/price: any authenticated user |

Open decisions:
1. Should policies mirror `ROLES_MATRIX.md` roles directly (role-based) or
   use Spatie permission checks (capability-based)?
2. Create `AuthServiceProvider` for Gate definitions?
3. Can unprotected resources share a base policy?

### Answer

(filled when resolved)

## #5: (Future) ReportsPage pagination + service seam

Blocked by: none
Type: Prototype

### Question

`ReportsPage` uses `->limit(100)` instead of pagination on all 4 tabs.
Fix with `->paginate()`.

(YAGNI note: a full `ReportService` seam is speculative — keep queries
inline until a second caller appears.)

### Answer

(filled when resolved)

## #6: (Future) Inline form schemas + deduplication

Blocked by: none
Type: Prototype

### Question

- InvoiceResource / ProformaInvoiceResource are ~60% duplicate code
- `$l` lambda copy-pasted in 10 resources
- `is_active` ternary filter + icon column repeated in 6 resources
- `LeafletMapPicker` form component exists but unused (CustomerResource
  uses raw Alpine instead)

### Answer

(filled when resolved)

## #7: (Future) CONTEXT.md — domain glossary

Blocked by: none
Type: Grilling

### Question

No `CONTEXT.md` exists. Create one to document domain terms (Proforma,
Collection, Van Stock, Naming Series, etc.) and reduce ambiguity across sessions.

### Answer

(filled when resolved)

---

## Dependency graph

```
#1 ──┐
     ├──→ #3
#2 ──┘

#4 (independent, parallel with #1/#2/#3)
#5 (independent, future)
#6 (independent, future)
#7 (independent, useful before #3)
```
