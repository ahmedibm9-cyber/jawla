# Business rules (non-negotiable)

1. **No negative van stock.** Reject at `StockService::decrement()` before
   any transaction commits.
2. **Atomic sales.** `InvoiceService::create()` wraps invoice + items + stock
   decrement + movements + balance update in one `DB::transaction()`.
3. **Money math.** Line total = qty × unit_price. Subtotal = Σ line totals.
   VAT = subtotal × (company.vat_percent / 100) applied only to VAT-eligible
   products. Total = subtotal + VAT. Remaining = total − paid.
4. **Collections** update cash box + customer balance + invoice paid/remaining
   in one transaction.
5. **Returns** increase van stock (with a movement row) and reduce customer
   balance.
6. **Expenses** decrease the rep's cash box.
7. **Route lock.** A rep can only open visits for customers on their active
   route; off-route visits require the "custom visit" flag and are flagged
   in reports.
8. **Sequential numbers.** Invoice and return numbers are per-company,
   server-generated, immutable.
9. **Stock only via `StockService`** — every quantity change writes a
   `stock_movements` row in the same transaction.
10. **Reversal is compensating, never deletion.** See `docs/ROLES_MATRIX.md`
    and §7 of the main guide.
