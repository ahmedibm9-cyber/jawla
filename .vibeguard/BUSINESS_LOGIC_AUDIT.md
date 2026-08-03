# Business Logic Audit — 2026-08-03

## Scope

Services: InvoiceService, PaymentService, ReturnService, StockService, PricingService, ExpenseService, VanTransferService, CashReconciliationService, CollectionSubmissionService, WebhookService, SalesFlow, VisitFlow.

## Findings

### 1. FIXED — Stock inflation via VanTransferService::receive()

- **Severity:** High
- **File:** `app/Services/VanTransferService.php:98`
- **Root cause:** `receive()` accepted `quantity` without validating `received <= ordered`. A rep could mark a transfer as fully received with any quantity, inflating stock.
- **Fix:** Added `received > ordered` validation. 25 VanTransfer tests pass.

### 2. NO ISSUE — Payment idempotency

- PaymentService uses `intent_id` with `lockForUpdate()` to deduplicate concurrent payment submissions.
- Customer, invoice, and batch rows also locked within the same transaction.

### 3. NO ISSUE — Return quantity inflation

- ReturnService sums all prior non-cancelled returns via `ReturnItem::whereHas('return', ...)->sum('quantity')` and rejects `quantity > remaining`.
- Uses `lockForUpdate()` on original invoice items.

### 4. NO ISSUE — Expense cash box guard

- ExpenseService checks `amount > cashBox.balance` before deducting.
- User and cash box locked via `lockForUpdate()`.

### 5. NO ISSUE — Pricing manipulation

- PricingService resolves price server-side from `Product.price` and `Company.rep_discount_percent`. Reps cannot pass arbitrary unit prices; `InvoiceService::create()` validates via `PricingService::effectivePrice()`.

### 6. NO ISSUE — Collection submission step-skip

- Multi-step approval workflow enforces permissions at each gate: `collections.review` (sequence 1), `collections.reconcile` (sequence 2).
- Finance reconciliation only by the same reviewer who approved (`finance_reviewed_by === actor.id`).
- Evidence photos required at submit, review, and reconcile stages.

### 7. NO ISSUE — Webhook HMAC signing

- `hash_hmac('sha256', $body, $endpoint->secret)` with `X-Jawla-Signature: sha256=...` header.
- Exponential backoff retries (5 max), configurable timeout.

### 8. NO ISSUE — Cross-company data isolation

- `BelongsToCompany` trait adds global scope filtering + blocks cross-company writes on creating/updating/deleting.
- Policies use `ChecksCompanyOwnership` to verify `user.activeCompanyId === model.company_id`.

### 9. NO ISSUE — Cash reconciliation

- CashReconciliationService is an audit record; it never mutates the cash box.
- Only `pending` status can be reviewed. Reviewer must belong to the same company.

### 10. NO ISSUE — Race conditions

- `lockForUpdate()` used extensively (86 call sites) across Payment, VanTransfer, Stock, Expense, Collection flows.
- All money mutations wrapped in `DB::transaction()`.

## Summary

| Severity | Count | Status                  |
| -------- | ----- | ----------------------- |
| High     | 1     | Fixed (stock inflation) |
| Medium   | 0     | —                       |
| Low      | 0     | —                       |
| Info     | 0     | —                       |

All other audited flows follow security best practices: server-authoritative pricing, row-level locking, company scoping, status transition guards, and BC-math for money.
