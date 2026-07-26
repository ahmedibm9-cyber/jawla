# Database Integrity

## Invariant assessment

| Invariant | DB enforcement | Service enforcement | Judgment |
|---|---|---|---|
| Stock cannot be negative | `stocks.quantity >= 0` check | locked stock movement path rejects insufficient stock | **Pass/Partial:** reconciliation race remains |
| Every stock change has a movement | no trigger/ledger equality constraint | intended through `StockService` | **Partial:** convention and delete paths can break it |
| Same-company foreign relationships | mostly independent scalar FKs | inconsistent explicit checks/global scope | **Fail** |
| Positive payment/expense/return/transfer values | incomplete checks | validation varies by entry point | **Fail** |
| Invoice arithmetic and VAT equation | no equation checks | float calculation and rounding | **Partial/Fail** |
| `paid <= total` and valid status | incomplete | lifecycle service logic | **Fail** under cancellation/amendment |
| Cash/customer balances equal ledger | none | mutable increments/decrements | **Fail; no independent reconstruction** |
| Source and destination differ | incomplete | workflow-specific | **Fail/Partial** |
| Financial/stock history append-only | cascade/delete paths exist | soft-delete/convention only | **Fail** |
| Document number per-company sequence | sequence table/unique columns | `NumberSequenceService` | **Partial/NV** |
| Idempotency identity | unique `(company_id,idempotency_key)` | same-key replay | **Partial:** no type/payload/user binding |
| Return bounded by original sale | no composite/provenance constraints | absent | **Fail** |
| Batch required for tracked product | no conditional FK/check | absent in sale/return | **Fail** |
| Reversal links original immutably | incomplete | inconsistent service/activity behavior | **Fail** |

## Positive controls

- PostgreSQL is used in configured test/production topology.
- Foreign keys are common.
- Stock quantity has an explicit nonnegative check.
- Several status/reason/unit fields use check constraints.
- Critical service paths use transactions and row locks.
- Sync receipts have a company/key unique constraint.

## Integrity gaps

### Cross-company references

An invoice can have a company ID and customer ID that are each independently valid without the database proving the customer belongs to that company. The same structural issue applies across warehouses, products, batches, users, source documents, and financial children. The tenant fail-open finding makes these missing composite guarantees more consequential.

### Ledger immutability

Cascade relationships can delete stock movements or financial evidence when parent records are deleted. Application soft deletion and policies reduce likelihood but do not enforce append-only history. A restricted production database role, immutable original records, and compensating entries are not demonstrated.

### Stored aggregates

`stocks.quantity`, customer balances, cash-box balances, invoice paid amounts, and statuses are stored aggregates. They improve read performance, but there is no scheduled independent rebuild/report to prove them against immutable source events.

### Arithmetic

Database decimal columns do not prevent PHP float conversion before persistence. The invoice posting path uses floats and per-line rounding even though a BCMath money helper exists. Currency scale, quantity scale, rounding mode, inclusive/exclusive VAT, discount order, and cross-system canonical representation require owner approval and property/boundary tests.

## Migration safety

Static inspection found 108 migrations and numerous forward schema changes. The audit did not run `migrate:status` or migrations because the user prohibited development/production database migration access. The following remain **NOT VERIFIED**:

- exact schema applied in any deployed environment;
- fresh install and upgrade from each supported version;
- migration lock/duration on production-shaped data;
- backward/forward compatibility during two-replica rollout;
- irreversible migration rollback/data-preservation behavior;
- constraints validated against existing data.

## Required database evidence

1. Fresh and upgrade migration run on isolated ephemeral PostgreSQL.
2. Constraint test suite that directly attempts invalid rows.
3. Parallel two-connection tests for all balance/stock/sequence transitions.
4. Ledger rebuild and drift report using production-shaped synthetic data.
5. Delete/cascade tests proving historical records survive.
6. Explain/analyze and index review for large tenant/date/status queries.
7. Restricted application database role and migration-role separation.

