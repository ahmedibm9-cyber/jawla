# Story CG2.2 -- Sync Queue and Idempotent API Contract

**Status:** ready-for-dev
**Epic:** CG2 -- True Offline Transactions
**Estimated effort:** Large (~1-2 weeks)
**Blocked by:** CG2.1
**Labels:** offline, sync, api, idempotency, p1

---

## Story

**As a** platform owner  
**I want** offline writes synced safely and exactly once  
**So that** money and stock operations never duplicate on reconnect.

---

## Acceptance Criteria

- FIFO sync queue exists for sales, collections, returns, expenses, and visit reports.
- Every queued write carries an idempotency key accepted server-side.
- Server rejects stale or conflicting writes with structured error payloads.
- Queue retries use exponential backoff and preserve order.
- Financial and stock services remain the final source of truth on replay.
