# Fix Story: PR-004 — Offline Idempotency by User Action

**Epic:** Offline Sync & Data Integrity
**Story ID:** FIX-PR-004
**Priority:** P0 — Launch Blocker
**Investigation Reference:** `bmad-output/investigation-production-readiness-2026-07-28.md`

---

## Story

**As a** field sales rep
**I want** my offline actions to be processed exactly once, even if I retry or lose connectivity
**So that** I never create duplicate invoices, payments, or returns

---

## Acceptance Criteria

1. **One stable intent key per user action**
   - Intent key generated BEFORE confirmation UI
   - Persisted in IndexedDB with the business data
   - Same key reused on retry, refresh, browser restart

2. **Enqueue awaits durable completion**
   - UI shows "Saving..." until IndexedDB write confirms
   - Only then shows success/retry option
   - Network call uses the same persisted key

3. **Sync server enforces one-key-one-response**
   - Same key = return stored response (no new mutation)
   - Different key = new mutation (different user action)

4. **Multi-device handling**
   - Same key from different devices = same response
   - Device ID stored with intent for audit trail

5. **User feedback is clear**
   - "Saving offline" → "Synced" → "Confirmed"
   - Retry button uses existing key, not new key
   - Error shows specific reason (duplicate, conflict, network)

---

## Suspected Files

| File | Change |
|------|--------|
| `resources/views/livewire/app/sales-flow.blade.php` | Generate intent key before confirmation |
| `resources/views/livewire/app/collect-payment.blade.php` | Generate intent key before confirmation |
| `resources/views/livewire/app/log-return.blade.php` | Generate intent key before confirmation |
| `resources/views/livewire/app/log-expense.blade.php` | Generate intent key before confirmation |
| `resources/js/offline/outbox.js` | Persist key before enqueue, await completion |
| `app/Services/Sync/SyncService.php` | Enforce one-key-one-response |

---

## Verification Steps

1. **Playwright offline test:** Queue 3 sales offline → reconnect → verify exactly 3 invoices created
2. **Rapid click test:** Click confirm 5 times quickly → verify only 1 invoice created
3. **Refresh test:** Confirm offline → refresh page → verify same key reused
4. **Browser kill test:** Confirm offline → kill browser → reopen → verify same key
5. **Multi-device test:** Same action on phone + tablet → verify only 1 mutation
6. **Lost response test:** Confirm offline → server responds but client doesn't receive → retry → verify same key returns stored response

---

## Implementation Notes

- **Approach:** Generate UUID at form mount, persist with business data, reuse on all paths
- **Risk:** Breaking existing offline flow during transition
- **Mitigation:** Feature flag for new key generation, backward-compatible sync processing

---

## Definition of Done

- [ ] `make verify` passes
- [ ] Playwright offline tests pass (queue → sync → confirm exactly-once)
- [ ] Rapid-click test produces single mutation
- [ ] Refresh/browser-kill preserves intent key
- [ ] Multi-device test produces single mutation
- [ ] User feedback shows clear save/sync/confirm states
