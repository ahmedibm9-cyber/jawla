# Decision Log

## Investigation: Production Readiness — 2026-07-28

- **Symptom:** 10 Critical + 21 High production-readiness findings across tenancy, financials, offline sync, security, and operations — audit score 35/100
- **Primary hypothesis:** Architectural gaps at critical trust boundaries — service layer exists but lacks authoritative server-side validation, database constraints, and state machine discipline
- **Primary suspected components:** BelongsToCompany, StockService, InvoiceService, PaymentService, ReturnService, SyncService
- **Case file:** `bmad-output/investigation-production-readiness-2026-07-28.md`
- **Recommended response:** Option C — Escalate to Planning (systemic issue spanning multiple epics)

### Fix Stories Created

| Story | Finding | Priority |
|-------|---------|----------|
| `fix-story-PR-001-tenant-isolation.md` | PR-001: Tenant scope fails open | P0 |
| `fix-story-PR-002-stock-import.md` | PR-002: Stock import trusts client | P0 |
| `fix-story-PR-003-return-provenance.md` | PR-003: Returns mint stock/credit | P0 |
| `fix-story-PR-004-offline-idempotency.md` | PR-004: Offline duplicate intents | P0 |
| `fix-story-PR-005-production-credentials.md` | PR-005: Demo creds in production | P0 |
| `fix-story-PR-006-reversal-process.md` | PR-006: Reversals break history | P0 |
| `fix-story-PR-007-server-pricing.md` | PR-007: Unbounded pricing | P0 |
| `fix-story-PR-008-protect-unsynced.md` | PR-008: Discardable financials | P0 |
| `fix-story-PR-009-state-machine.md` | PR-009: Payment/invoice drift | P0 |
| `fix-story-PR-010-reconciliation-locking.md` | PR-010: Reconciliation overwrite | P0 |
