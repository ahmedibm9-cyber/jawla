# Fix Story: PR-009 — Payment/Invoice State Machine

**Epic:** Financial Integrity
**Story ID:** FIX-PR-009
**Priority:** P0 — Launch Blocker
**Investigation Reference:** `bmad-output/investigation-production-readiness-2026-07-28.md`

---

## Story

**As a** finance manager
**I want** invoice and payment status transitions to form a single balanced state machine
**So that** customer and cash balances always agree with invoice/payment records

---

## Acceptance Criteria

1. **Invoice state machine defined**
   - Allowed transitions: draft→issued→partially_paid→paid→voided
   - Each transition has required fields (reason, approved_by, timestamp)
   - Terminal states (voided, paid) cannot receive payments

2. **Payment allocation is atomic**
   - Payment + invoice update + balance update in single transaction
   - Balance calculated from invoices, not stored aggregate
   - Stored balance is cached for performance, reconciled periodically

3. **Amendment creates compensating entries**
   - Amending an invoice creates a reversal for the original
   - New invoice issued with correct amounts
   - Balance updated atomically

4. **Cancellation requires approval**
   - Paid invoice cancellation requires finance manager approval
   - Cancellation creates credit note for paid amounts
   - Audit trail with reason, approver, timestamp

5. **Balance reconciliation job**
   - Daily job recalculates balances from invoice/payment records
   - Alerts on discrepancy between stored and calculated balances
   - Auto-corrects minor rounding drift

---

## Suspected Files

| File | Change |
|------|--------|
| `app/Services/InvoiceService.php` | Enforce state machine transitions |
| `app/Services/PaymentService.php` | Reject payments to terminal-state invoices |
| `app/Services/InvoiceCalculationService.php` | Atomic balance updates |
| `app/Models/Invoice.php` | Add state machine constants and transition rules |
| `app/Jobs/ReconcileBalances.php` | New job for daily balance reconciliation |
| `database/migrations/xxxx_add_invoice_state_machine.php` | Add state machine fields |

---

## Verification Steps

1. **Terminal state test:** Payment to voided invoice → expect rejection
2. **Amendment test:** Amend issued invoice → verify reversal + new invoice + correct balance
3. **Cancellation test:** Cancel paid invoice → verify approval required + credit note + audit
4. **Concurrent test:** Two payments to same invoice → verify only one succeeds
5. **Reconciliation test:** Create intentional drift → verify job corrects and alerts

---

## Implementation Notes

- **Approach:** Define allowed transitions in model, enforce in service, add reconciliation job
- **Risk:** Breaking existing invoice/payment flow during transition
- **Mitigation:** Feature flag for new state machine, backward-compatible for existing records

---

## Definition of Done

- [ ] `make verify` passes
- [ ] Invoice state machine enforced
- [ ] Payment to terminal states rejected
- [ ] Amendment creates compensating entries
- [ ] Cancellation requires approval
- [ ] Balance reconciliation job runs daily
- [ ] Audit trail for all transitions
