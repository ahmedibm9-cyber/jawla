# Story CG2.1 -- Offline Data Model and IndexedDB Persistence

**Status:** ready-for-dev
**Epic:** CG2 -- True Offline Transactions
**Estimated effort:** Large (~1 week)
**Blocked by:** none
**Labels:** offline, indexeddb, architecture, p1

---

## Story

**As a** field rep  
**I want** my working-day data available without connectivity  
**So that** I can continue work when the network drops.

---

## Acceptance Criteria

- Introduce client-side persistence for rep assignments, customers, products, and draft transactions.
- Data is scoped by company + user and cleared safely on logout/account switch.
- Storage model explicitly separates read cache from pending write queue.
- Existing localStorage drafts are migrated or deprecated safely.
- Data model is documented for downstream sync implementation.
