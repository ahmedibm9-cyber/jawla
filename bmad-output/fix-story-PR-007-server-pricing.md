# Fix Story: PR-007 — Server-Authoritative Pricing

**Epic:** Sales & Pricing Integrity
**Story ID:** FIX-PR-007
**Priority:** P0 — Launch Blocker
**Investigation Reference:** `bmad-output/investigation-production-readiness-2026-07-28.md`

---

## Story

**As a** finance manager
**I want** all prices to be resolved server-side from authorized price lists
**So that** reps cannot manipulate pricing, and all sales comply with company pricing policy

---

## Acceptance Criteria

1. **Server resolves all prices**
   - Client sends product_id + quantity only
   - Server looks up unit_price from price list (or quotation approval)
   - Client price is ignored for invoice creation

2. **Price override requires explicit authorization**
   - Rep can request price within floor/ceiling range
   - Price outside range requires manager approval (existing PriceQuotation flow)
   - Override logged with reason, rep, manager, timestamp

3. **Price list versioning**
   - Price lists have effective dates
   - Stale price lists rejected with clear error
   - Price changes logged with audit trail

4. **VAT calculated server-side**
   - VAT amount from product.vat_applicable flag
   - No client-provided tax amounts accepted

5. **Test coverage**
   - Tampered price test: client sends different price → server uses list price
   - Out-of-range test: price outside floor/ceiling → requires approval
   - Stale price test: expired price list → clear error
   - Concurrent price change test: price list updated during sale → handles gracefully

---

## Suspected Files

| File | Change |
|------|--------|
| `app/Livewire/App/SalesFlow.php` | Remove price from client payload |
| `app/Services/InvoiceService.php` | Resolve price server-side |
| `app/Services/PricingService.php` | Add price list resolution logic |
| `app/Services/InvoiceCalculationService.php` | Use server-resolved prices |
| `app/Models/PriceList.php` | Add effective dates, versioning |
| `app/Livewire/App/PriceQuotationRequest.php` | Integrate with approval flow |

---

## Verification Steps

1. **Tampered price test:** Submit invoice with modified price → verify server uses list price
2. **Floor/ceiling test:** Submit price outside range → verify approval required
3. **Stale price test:** Use expired price list → verify clear error message
4. **Concurrent test:** Update price list during sale → verify handles gracefully
5. **VAT test:** Submit invoice with modified tax → verify server calculates from product flag

---

## Implementation Notes

- **Approach:** Price resolution in service layer, client only sends product_id + quantity + optional approved quotation_id
- **Risk:** Breaking existing sales flow during transition
- **Mitigation:** Feature flag for old vs new pricing, backward-compatible for existing quotations

---

## Definition of Done

- [ ] `make verify` passes
- [ ] Client-provided prices ignored for invoice creation
- [ ] Price override requires approval workflow
- [ ] All prices logged with audit trail
- [ ] VAT calculated server-side
- [ ] Test coverage for tampered/out-of-range/stale/concurrent scenarios
