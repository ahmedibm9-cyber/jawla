# Warehouse stock CSV import wizard (REQ-STK-1/2, tickets R-05/B2-06)

## Overview

The client's interim stock process is a daily CSV import; only the `WarehouseImportLog` model and the `spatie/simple-excel` dependency exist. Needed: upload → validate → preview accepted/rejected counts → confirm → apply through `StockService` → history. D-03 (real client file) is still pending, so build against the documented mock format with a swappable column map.

## Scope

**Included:** Filament import action on `StockResource`, downloadable bilingual template, staging/preview step, transactional apply via `StockService::reconcile()` (absolute counts), `warehouse_import_logs` history page, checksum idempotency, row-level error report, read-only transit quantity column.
**Excluded:** silent product creation (forbidden), final client-file mapping (blocked on D-03), delta semantics unless D-03 says so.

## Technical Requirements

- Validate: file type/size, headings, encoding, row count, numeric values, duplicates, warehouse/product/company ownership; preview changes nothing.
- Apply all valid rows in a transaction (or documented safe chunks); every delta through `StockService` with a matching `stock_movements` row; failed chunk rolls back.
- Re-importing the same file (checksum) is blocked with a clear message.
- Transit quantity stored/displayed read-only; never added to sellable stock or oversell checks.
- `StockPolicy`: only warehouse_keeper + admins may import.

## Acceptance Criteria

- [ ] Preview shows accepted/rejected counts and row-level errors without mutating stock
- [ ] Confirmed import creates exact matching stock movements; forced failure rolls back
- [ ] Cross-company IDs rejected; unauthorized roles 403
- [ ] Duplicate file blocked by checksum; history page paginated
- [ ] Full R-05 test list from the Master Plan passes

## Priority

Score 2.5 (high effort) but must-have for beta acceptance; schedule after #2/#3.

## Dependencies

- **Blocks:** B2 gate; **Blocked by:** #1; final mapping blocked by client decision D-03

## Implementation Size

- **Estimated effort:** Large (3–5 days) — split into sub-issues: (a) parser+validation+preview, (b) transactional apply+idempotency, (c) history UI+template+tests
