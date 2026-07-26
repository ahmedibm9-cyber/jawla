# Concurrency and Idempotency

## Control inventory

| Mutation | Locking/idempotency observed | Gap | Status |
|---|---|---|---|
| Sale stock decrement | stock row lock; nonnegative check | no valid parallel final-unit test; client price/batch invalid | Partial |
| Customer balance posting | customer lock in core services | amendment/cancellation state drift | Fail |
| Cash balance posting | cash-box lock in payment/expense | no valid concurrent payment/expense test | Not verified |
| Number allocation | sequence row lock after creation | first-use conflict, suffix/collision, rollback/year behavior | Not verified |
| Stock reconciliation | absolute save after movement sum | no compatible lock/version with concurrent movement | Fail |
| Return quantity | stock/customer locks | no sold/cumulative-return lock or provenance | Fail |
| Invoice cancellation/payment | separate locked transactions | payment accepts cancelled invoice; interleavings untested | Fail |
| Transfer ship/receive | transactional state path | partial/loss/reject/duplicate concurrency incomplete | Partial |
| Offline same-key replay | company/key unique receipt and atomic handler | no payload/type/user hash | Partial |
| Offline repeated intent | new UUID each enqueue | duplicate real-world action | Fail |
| ETA submission | remote call inside transaction | no durable outbox/idempotent authority key | Fail |

## PostgreSQL-specific risk

The code uses `lockForUpdate()` in several mutation services, which is appropriate. A concurrency claim still requires all participants to lock the same rows in a consistent order. Stock reconciliation is the clearest violation: it derives and saves an absolute value without demonstrating the locked protocol used by delta movements.

First-use sequence initialization must also be tested with independent connections. In PostgreSQL, catching a unique violation inside a transaction without a savepoint can leave the transaction aborted.

## Offline receipt semantics

The receipt is a useful exactly-once mechanism for one identical transport key:

- unique `(company_id, idempotency_key)`;
- business mutation and receipt are atomic;
- replay can return the stored result.

It is not yet semantic idempotency:

- the key is not bound to operation type, payload hash, or initiating user;
- the client allocates a new UUID for every click;
- devices cannot recognize the same real-world event;
- causal dependencies are not represented;
- old-client protocol behavior is not versioned.

## Required concurrency harness

Use isolated PostgreSQL databases and genuinely simultaneous connections/processes. Each test must assert final rows, movements/postings, statuses, audit events, and absence of partial writes:

1. Two sales for the final unit.
2. Sale versus stock reconciliation.
3. Two reconciliations with stale counts.
4. First document number allocation and year rollover.
5. Payment versus cancellation/amendment.
6. Two payments and payment versus expense on one cash box.
7. Two cumulative returns against one sale line.
8. Duplicate sync same key/same payload and same key/different payload/type/user.
9. Two devices submitting the same business intent with different keys.
10. Transfer receive versus reject/cancel.
11. ETA double-submit, timeout, retry, and lost local response.
12. Double reversal.

## Audit evidence limitation

No result from the audit’s concurrent shared-database test attempts can support a concurrency pass/fail conclusion. Setup deadlocks occurred during `RefreshDatabase`, not inside the application mutation scenarios. The correct classification is **NOT VERIFIED**.

