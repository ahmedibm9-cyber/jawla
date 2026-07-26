# Financial Workflow Traces

Legend: **Pass** means the static implementation shows the stated control; **Partial** means a useful control exists but a required invariant is absent; **Fail** means a concrete invalid path exists; **NV** means runtime behavior was not verified.

## Mutation trace matrix

| # | Workflow | Entry and authoritative service | Transaction/locks | Principal writes | Idempotency/audit/reversal | Judgment and finding |
|---:|---|---|---|---|---|---|
| 1 | Online cash/credit sale | Rep `SalesFlow` → `InvoiceService` | One transaction; stock/customer/sequence locking present | sequence, invoice/items, stock, stock movements, customer balance | no online intent key; creation audit incomplete; cancel is unsafe | **Fail:** price and batch trust, PR-007/025 |
| 2 | Offline sale sync | IndexedDB → `SyncController` → `SyncService` → `SaleSyncHandler` → `InvoiceService` | receipt and business writes share transaction | same as online plus sync receipt | same key replays; key not bound to payload/user/type | **Fail:** PR-004/016 |
| 3 | Payment collection | `CollectPayment`/sync handler → `PaymentService` | invoice/customer/cash locks in transaction | payment, cash balance, invoice paid/status, customer balance | sync key only offline; cancel path exists; audit incomplete | **Fail:** cancelled invoice accepted, PR-009 |
| 4 | Expense logging | `LogExpense`/sync handler → `ExpenseService` | cash row lock and transaction | expense, cash balance | offline receipt only; reversal/audit incomplete | **Partial:** PR-006/011 |
| 5 | Customer return | `LogReturn`/sync handler → `ReturnService` | customer/stock transaction and locks | return/items, stock/movements, customer balance | number sequence; no provenance/cumulative key | **Fail:** stock/value can be minted, PR-003/028 |
| 6 | Invoice cancellation | action toast/admin path → `InvoiceService::cancel` | transaction and row locks | invoice status, stock/movements, customer balance | not a complete compensating ledger; reason/role/link incomplete | **Fail:** PR-006/009/031 |
| 7 | Payment cancellation | action toast/admin path → `PaymentService` cancel | transactional reverse posting | payment status, cash, invoice allocation, customer | duplicate cancel guarded, but privilege/reason/activity incomplete | **Fail:** PR-006/009 |
| 8 | Return cancellation | action toast/service | transactional stock/balance reversal | return status, stock/movements, customer balance | incomplete privileged audit chain | **Fail:** PR-006/028 |
| 9 | Expense cancellation | action toast/service | transactional cash reversal | expense status, cash balance | no complete privileged reason/link/activity | **Fail:** PR-006 |
| 10 | Invoice amendment/resubmit | `InvoiceService` amend then submit | each step transactional | cancel original, draft/items, later stock | no whole-lifecycle idempotency; payments remain problematic | **Fail:** receivable not reposted, PR-009 |
| 11 | Stock movement | callers → `StockService` movement path | locked stock row; transaction by service/caller | stock balance and matching movement | no universal operation identity; movement history deletable via FKs | **Partial:** strong primitive, PR-011/031 |
| 12 | Stock reconciliation | import/admin → `StockService::reconcile` | transaction but no proven compatible row lock | absolute stock save and adjustment movement | import checksum is useful; no concurrency version | **Fail:** lost update, PR-002/010 |
| 13 | Goods receiving/batches | receiving services | transactional happy path | PO/receipt, batches, warehouse stock/movements | workflow-specific audit; exception handling incomplete | **Partial/NV:** batch enforcement absent downstream, PR-025 |
| 14 | Van transfer ship | admin/rep → `VanTransferService::ship` | transactional source→transit postings | transfer/items, source/transit stock/movements | state checks present; role/ownership conflict | **Fail:** PR-026 |
| 15 | Van transfer receive/reject | rep/admin → transfer service | transactional transit→destination happy path | status, destination/transit stock/movements | duplicate/partial/damage/loss rules incomplete | **Partial/NV:** PR-026 |
| 16 | Cash reconciliation | Filament/service → `CashReconciliationService` | transaction around snapshot/approval | reconciliation and activity/snapshot | no independent ledger rebuild or unique closed-session invariant | **Fail:** PR-011/020 |
| 17 | Invoice PDF/QR issuance | download route → `PdfService`/`InvoiceQrService` | on-demand, outside original issuance | QR field and cached PDF | no immutable issuance snapshot/hash | **Fail:** PR-012/029 |
| 18 | ETA submission | admin/service → `EtaService`/HTTP client | remote call inside DB transaction; invoice not proven locked | remote submission, ETA identifiers/status/activity | no durable submission outbox/idempotency | **Fail/NV:** PR-030 |

## Sale atomicity trace

The strongest implemented financial path is invoice creation:

1. Resolve company/user/customer and allocate a server-side document sequence.
2. Create invoice and invoice items.
3. For each line, mutate stock through `StockService`, which locks the stock row and writes a matching movement.
4. Update customer balance.
5. Commit all writes together.

This structure is a positive control: an exception should roll back the invoice, stock, movements, and receivable together. It does not make the supplied price, optional batch, tenant relationship, number format, or later lifecycle valid. Those are separate invariants.

## Failure paths requiring independent proof

- One remaining unit sold concurrently by two sessions.
- Sale concurrent with absolute reconciliation.
- Same first invoice sequence initialized concurrently.
- Payment concurrent with invoice cancellation/amendment.
- Two payments/expenses mutating one cash box.
- Duplicate/lost-response sync delivery across devices.
- Two reversals/cancellations of the same original.
- Return concurrent with another return at the cumulative sold limit.
- Transfer receive concurrent with reject/cancel.
- ETA timeout after authority acceptance but before local commit.

No valid runtime evidence for these races was produced during this audit.

## Reconciliation requirements

Before launch, an independent job/report must rebuild and compare:

- `stocks.quantity` to the sum of immutable stock movements at the defined grain.
- Customer balance to invoices, payments, returns/credits, reversals, and opening balance.
- Cash-box balance to opening cash, collections, expenses, transfers, refunds, and reversals.
- Invoice paid/remaining/status to allocations and credit/refund events.
- Transfer source, in-transit, received, damaged/lost, and rejected totals.
- Tax document sequences to immutable issued documents and authority responses.

Reconciliation must detect drift; it must not silently overwrite source history.

