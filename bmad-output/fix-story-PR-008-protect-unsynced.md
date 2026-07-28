# Fix Story: PR-008 — Protect Unsynced Financial Records

**Epic:** Offline Data Integrity
**Story ID:** FIX-PR-008
**Priority:** P0 — Launch Blocker
**Investigation Reference:** `bmad-output/investigation-production-readiness-2026-07-28.md`

---

## Story

**As a** finance manager
**I want** unsynced financial records to be protected from accidental or deliberate discard
**So that** cash sales, payments, returns, and expenses are never permanently lost

---

## Acceptance Criteria

1. **No local deletion of financial intents**
   - Sync queue Discard button removed for financial operations (sale, payment, return, expense)
   - Only non-financial items (e.g., profile photo upload) can be discarded locally

2. **Exception resolution workflow**
   - Conflicting/failed financial items route to a resolution queue
   - Support/finance team reviews and resolves (retry, merge, or manually enter)
   - Resolution logged with who, what, when, why

3. **Consequence-specific confirmation**
   - If any discard is allowed (non-financial), confirmation shows exact consequence
   - Bilingual (AR/EN) with specific data that will be lost
   - Requires explicit confirmation action

4. **Tombstone for discarded items**
   - Discarded items create a tombstone record (not full data, but intent type + timestamp + reason)
   - Tombstones visible to support team for reconciliation
   - Tombstones are append-only

5. **Periodic reconciliation**
   - Daily job checks for stranded offline items (queued > 24 hours)
   - Alerts finance team for manual resolution
   - Dashboard shows pending offline items count

---

## Suspected Files

| File | Change |
|------|--------|
| `resources/views/livewire/app/sync-queue.blade.php` | Remove Discard for financial items |
| `resources/js/offline/sync.js` | Add tombstone on discard |
| `resources/js/offline/outbox.js` | Mark financial items as non-discardable |
| `app/Jobs/ReconcileOfflineItems.php` | New job for daily reconciliation |
| `app/Models/SyncTombstone.php` | New model for tombstone records |
| `database/migrations/xxxx_create_sync_tombstones.php` | Tombstone table |

---

## Verification Steps

1. **Discard test:** Attempt to discard financial item → expect rejection
2. **Non-financial test:** Discard profile photo upload → expect confirmation with consequence
3. **Resolution test:** Conflicting payment → routes to resolution queue → support resolves
4. **Tombstone test:** Discard allowed item → verify tombstone created
5. **Reconciliation test:** Item queued > 24 hours → alert fires
6. **Dashboard test:** Pending items count visible to finance team

---

## Definition of Done

- [ ] `make verify` passes
- [ ] Financial items cannot be discarded locally
- [ ] Exception resolution workflow exists
- [ ] Consequence confirmation is bilingual and specific
- [ ] Tombstones created for discarded items
- [ ] Daily reconciliation job runs
- [ ] Dashboard shows pending offline items
- [ ] Test coverage for all scenarios
