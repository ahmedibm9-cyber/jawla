# Phase 2 — Core Money and Inventory Integrity

Date: 2026-07-26
Branch: `remediation/production-readiness`
Database evidence: isolated PostgreSQL test database only

## Implemented findings

- PR-002: Stock imports use server-staged parsed rows, opaque hashed tokens, expiry, checksum and snapshot revalidation, single use, and manager approval for opening/large changes.
- PR-003: Returns require an original invoice line; product, batch, price, tax, company, and remaining quantity are resolved and locked server-side. Damaged stock enters quarantine.
- PR-006: Rep Undo was removed from committed sales, payments, returns, and expenses. Manager reversals require a reason, preserve originals, link through the reversal ledger, check dependencies, and are idempotent.
- PR-007: The rep UI displays server-resolved prices and has no price editor. Invoice creation rejects stale/tampered quotes. Sales managers can create audited, bounded customer overrides through the admin panel.
- PR-009: Draft/issue/amend, payment allocation, terminal-state rejection, overpayment credit, payment reversal, duplicate intent behavior, and read-only ledger drift reporting use one locked lifecycle.
- PR-010: Stock counts capture an expected snapshot, physical quantity, variance, reason, submission, approval, and immutable delta movement. Intervening movement rejects the count, including a real independent-connection sale/count race.
- PR-028: Returns issue linked credit notes. Paid/excess credits create customer credit. Cash refunds require manager approval and sufficient cash; bank/card refunds remain pending until external confirmation.
- PR-031: Application deletion guards, PostgreSQL delete triggers, and restrictive history foreign keys protect financial, stock, expense, cash-reconciliation, transfer, audit, sequence, sync, credit, refund, reversal, import, and count ledgers.

The approved remediation decision for a bounded, audited sales-manager price override is the controlling Phase 2 requirement. Representatives still cannot edit authoritative prices.

## Forward migrations

1. `2026_07_26_000002_create_stock_import_previews_table.php`
2. `2026_07_26_000003_create_return_credit_refund_ledger.php`
3. `2026_07_26_000004_add_payment_allocation_and_idempotency.php`
4. `2026_07_26_000005_add_audit_to_product_prices.php`
5. `2026_07_26_000006_create_reversals_table.php`
6. `2026_07_26_000007_allow_numberless_invoice_drafts.php`
7. `2026_07_26_000008_create_stock_count_sessions.php`
8. `2026_07_26_000009_enforce_append_only_ledgers.php`
9. `2026_07_26_000010_harden_refund_lifecycle.php`
10. `2026_07_26_000011_snapshot_line_tax_and_reversal_amount.php`

Fresh migration of all Phase 2 migrations passed on PostgreSQL.

## Executable evidence

- Consolidated Phase 2 focused suite: 151 tests passed, 441 assertions.
- Exact two-worker Unit/Feature CI gate: 522 tests passed, 1,515 assertions.
- Independent-connection PostgreSQL concurrency:
  - simultaneous final-unit stock decrements produce exactly one success and no negative stock;
  - simultaneous retries of one payment intent produce exactly one payment and one balance/cash posting;
  - a sale racing an inventory count cannot be overwritten by the count;
  - a return racing a sale preserves both locked movements;
  - the concurrency file passed three additional consecutive runs.
- Ledger drift reporting covers customer balances, cash-box balances, and stock movement totals without mutating production ledgers.
- Service-authorization coverage proves a system viewer cannot mutate invoices, payments, or returns.
- Refund/return gate: 12 tests passed, 46 assertions.
- Focused invoice/amendment/reversal/payment gate: 25 tests passed, 62 assertions.
- Laravel Pint formatted the complete dirty Phase 2 PHP set.
- `phpstan` is not installed in the repository; no dependency was added during this phase.

## Residual scope

Phase 4 still owns end-to-end removal of authoritative floating-point arithmetic, full database numeric invariants, automated drift correction, batch/FEFO enforcement, transfer conservation, legal numbering, and immutable issued artifacts. Phase 3 still owns the complete versioned offline intent/payload/device protocol. These later approved items do not reopen the Phase 2 lifecycle and concurrency fixes.
