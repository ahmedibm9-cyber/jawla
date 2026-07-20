# Story CG2.3 -- Offline Conflict UX and Observability

**Status:** ready-for-dev
**Epic:** CG2 -- True Offline Transactions
**Estimated effort:** Medium (~1 week)
**Blocked by:** CG2.2
**Labels:** offline, ux, observability, p1

---

## Story

**As a** rep and support team  
**I want** clear conflict states and sync diagnostics  
**So that** failed offline actions are understandable and recoverable.

---

## Acceptance Criteria

- Rep sees pending / syncing / synced / conflict / failed states per action.
- Conflict messages explain what happened (e.g. stock no longer available).
- Manager/admin can inspect sync failures for support.
- Browser tests cover reconnect, duplicate retries, and stale-stock rejection.
- Metrics/logging exist for queue depth, replay failures, and duplicate prevention.
