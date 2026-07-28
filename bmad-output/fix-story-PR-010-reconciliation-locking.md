# Fix Story: PR-010 — Stock Reconciliation Locking

**Epic:** Inventory Integrity
**Story ID:** FIX-PR-010
**Priority:** P0 — Launch Blocker
**Investigation Reference:** `bmad-output/investigation-production-readiness-2026-07-28.md`

---

## Story

**As a** warehouse manager
**I want** stock reconciliation to lock the stock row during execution
**So that** concurrent sales, returns, or transfers cannot overwrite each other

---

## Acceptance Criteria

1. **Reconciliation uses same locking primitive as sales**
   - `lockForUpdate()` on stock row before read/write
   - Same `StockService::move()` path used for delta adjustments
   - No direct `Stock->save()` outside service layer

2. **Reconciliation is delta-based, not absolute-set**
   - Input: counted quantity
   - System calculates delta = counted - current
   - Delta applied via `StockService::move()` with reason `reconciliation`

3. **Concurrent operations serialized**
   - Sale during reconciliation → waits for lock, then proceeds
   - Reconciliation during sale → waits for lock, then proceeds
   - No lost updates

4. **Variance approval**
   - Reconciliation with variance > threshold requires manager approval
   - Threshold configurable (e.g., 5% or 10 units)
   - Approval logged with reason

5. **Ledger equality verified**
   - After reconciliation, stock_movements sum matches stock.quantity
   - Discrepancy flagged and alert sent

---

## Suspected Files

| File | Change |
|------|--------|
| `app/Services/StockService.php` | Add reconcile() with lockForUpdate |
| `app/Livewire/App/StockReconciliation.php` | Use delta-based reconciliation |
| `app/Models/Stock.php` | Add variance_threshold to config |
| `app/Jobs/VerifyStockLedger.php` | New job for daily ledger verification |

---

## Verification Steps

1. **Lock test:** Start reconciliation → start concurrent sale → verify one waits
2. **Delta test:** Reconcile from 100 to 110 → verify +10 movement recorded
3. **No absolute-set test:** Reconcile to 110 → verify stock_movements sum = 110
4. **Variance test:** Variance > threshold → expect approval required
5. **Ledger test:** After reconciliation → verify stock_movements sum = stock.quantity
6. **Concurrent sale+reconcile test:** Both complete without data loss

---

## Definition of Done

- [ ] `make verify` passes
- [ ] Reconciliation uses lockForUpdate()
- [ ] Delta-based, not absolute-set
- [ ] Concurrent operations serialized
- [ ] Variance approval enforced
- [ ] Ledger equality verified
- [ ] Test coverage for all scenarios
