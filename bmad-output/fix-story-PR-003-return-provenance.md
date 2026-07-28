# Fix Story: PR-003 — Return Provenance & Cumulative Caps

**Epic:** Inventory & Financial Integrity
**Story ID:** FIX-PR-003
**Priority:** P0 — Launch Blocker
**Investigation Reference:** `bmad-output/investigation-production-readiness-2026-07-28.md`

---

## Story

**As a** finance manager
**I want** every return to be traceable to a specific sold item with quantity caps
**So that** fake or excessive returns cannot generate stock or credits without a matching historical sale

---

## Acceptance Criteria

1. **Invoice-linked returns resolve provenance server-side**
   - Return lines reference `invoice_item_id`, not arbitrary product/price
   - Server resolves product, unit_price, batch, company from the invoice item
   - Client-supplied price/product ignored for invoice-linked returns

2. **Cumulative return quantity capped**
   - Total returned quantity for an invoice item cannot exceed original sold quantity
   - Prior returns deducted before validation
   - Attempting over-return returns clear error with "already returned X of Y"

3. **Standalone returns authorized separately**
   - Returns without an invoice reference require explicit authorization (manager approval or system rule)
   - Authorized reasons: damaged goods, expired stock, promotional return
   - Audit trail with reason, authorizer, timestamp

4. **Stock restoration uses original batch**
   - Returned stock goes to same batch it was sold from (if batch-tracked)
   - If batch is depleted/closed, return to quarantine warehouse
   - Batch traceability maintained

5. **Credit note matches return value**
   - Credit note amount = returned quantity × original invoice unit_price
   - No arbitrary pricing on credit notes
   - Customer balance updated atomically

---

## Suspected Files

| File | Change |
|------|--------|
| `app/Livewire/App/LogReturn.php` | Resolve provenance from invoice_item_id |
| `app/Services/ReturnService.php` | Enforce cumulative caps, batch resolution |
| `app/Services/StockService.php` | Batch-aware stock restoration |
| `app/Services/InvoiceCalculationService.php` | Credit note = original price × qty |
| `app/Models/ReturnRecord.php` | Add invoice_item_id, batch_id, reason fields |

---

## Verification Steps

1. **Provenance test:** Return with arbitrary product/price → server uses invoice item data
2. **Over-return test:** Return more than sold → expect rejection with "already returned X of Y"
3. **Duplicate return test:** Return same item twice → second return respects remaining quantity
4. **Cross-company test:** Return referencing other company's invoice → expect rejection
5. **Batch test:** Return batch-tracked product → stock restored to original batch
6. **Standalone test:** Return without invoice → expect authorization requirement
7. **Concurrent test:** Two returns for same invoice item → only one succeeds

---

## Definition of Done

- [ ] `make verify` passes
- [ ] Invoice-linked returns use server-resolved provenance
- [ ] Cumulative return caps enforced
- [ ] Standalone returns require authorization
- [ ] Batch traceability maintained
- [ ] Credit notes match original pricing
- [ ] Test coverage for all scenarios
