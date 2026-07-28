# Fix Story: PR-006 — Reversal Process with Audit Trail

**Epic:** Financial Integrity & Audit
**Story ID:** FIX-PR-006
**Priority:** P0 — Launch Blocker
**Investigation Reference:** `bmad-output/investigation-production-readiness-2026-07-28.md`

---

## Story

**As a** finance manager
**I want** all reversals/cancellations to follow a controlled process with audit trail
**So that** financial history is immutable and every reversal is authorized, explained, and traceable

---

## Acceptance Criteria

1. **Reversal requires privileged role**
   - Reps can request cancellation (with reason)
   - Finance manager or admin must approve
   - System Viewer can observe but not act

2. **Reversal has mandatory TTL**
   - Cancellation requests expire after configurable window (e.g., 24 hours)
   - Expired requests require new submission
   - TTL enforced server-side, not just UI

3. **Mandatory bilingual reason**
   - Reversal requires reason text in both AR and EN
   - Reason stored in Reversal record, not just Activity log
   - Character minimum enforced (e.g., 10 chars)

4. **Compensating entries, not deletion**
   - Reversal creates Reversal record linking original and compensating entries
   - Original record marked `reversed` but not deleted
   - Compensating entries (credit notes, stock adjustments) created atomically

5. **Immutable audit chain**
   - Reversal record includes: original_id, original_type, compensating_ids, reason, requested_by, approved_by, timestamps
   - Reversal records are append-only (cannot be deleted/modified)
   - Activity log entry with full context

6. **Balance reconciliation**
   - Reversal updates customer/cash balances atomically
   - Stock adjustments go through StockService
   - All in single transaction

---

## Suspected Files

| File | Change |
|------|--------|
| `app/Livewire/App/ActionToast.php` | Route to reversal request, not direct cancel |
| `app/Services/ReversalService.php` | New service for reversal process |
| `app/Models/Reversal.php` | Immutable reversal record model |
| `app/Services/InvoiceService.php` | Reversal creates compensating entries |
| `app/Services/PaymentService.php` | Reversal creates compensating entries |
| `app/Services/ReturnService.php` | Reversal creates compensating entries |
| `app/Services/StockService.php` | Reversal stock adjustments |
| `database/migrations/xxxx_create_reversals_table.php` | Reversal table |

---

## Verification Steps

1. **Role test:** Rep requests cancellation → manager approves → reversal completes
2. **Unauthorized test:** Rep tries direct cancel → expect rejection
3. **TTL test:** Cancellation request after 24 hours → expect expiry error
4. **Reason test:** Missing reason → expect rejection; missing AR → expect rejection
5. **Immutability test:** Try to delete Reversal record → expect rejection
6. **Compensating test:** Reverse paid invoice → verify credit note created, balance updated
7. **Audit test:** Verify Reversal record links original and compensating entries
8. **Concurrent test:** Two reversals for same record → only one succeeds

---

## Definition of Done

- [ ] `make verify` passes
- [ ] Reversals require privileged approval
- [ ] TTL enforced server-side
- [ ] Mandatory bilingual reason required
- [ ] Compensating entries created (not deletion)
- [ ] Immutable audit chain established
- [ ] Balance reconciliation atomic
- [ ] Test coverage for role/TTL/reason/immutability scenarios
