# Performance and Capacity

## Verdict

**NOT VERIFIED for current production capacity.**

## Available evidence

- PWA asset budgets passed:
  - JavaScript: 50.5 KiB gzip, budget 300 KiB.
  - CSS: 22.3 KiB gzip, budget 100 KiB.
  - Total: 503.1 KiB gzip, budget 1536 KiB.
- Production source configuration uses PHP-FPM/Nginx and two Railway replicas.
- Pagination and lazy-loading guardrails are stated repository rules and used broadly.

## Evidence gaps

The available Railway performance report is pinned to older commit `6fb6c08`, not the audited revision. It recorded login p95 around 7.1 seconds and mostly exercises health/login/read pages. Current scripts do not sufficiently cover:

- simultaneous invoice/payment/return/expense writes;
- offline sync bursts after reconnection;
- stock contention and sequence allocation;
- PDFs, QR, ETA, reports and exports;
- large customer/product/ledger/history volumes;
- Redis/queue backlog and failure;
- PostgreSQL connections, locks, slow queries, storage/IO;
- object storage/photo upload;
- two-replica deployment and cache/session behavior.

## Required capacity plan

Owners must define:

- expected companies, users, concurrent reps/admins, transactions/day;
- customer/product/batch/stock movement/invoice/line-item growth over contract term;
- supported devices/networks and offline reconnect burst;
- availability, p50/p95/p99 latency and error-rate SLOs;
- maximum PDF/report/export duration;
- database/Redis connection and storage headroom;
- retention and archive effects.

Then run a production-shaped staging test on the exact artifact. Include 50–75 users if that remains the target, but scale from approved forecasts rather than an arbitrary number. Capture CPU, memory, connections, locks, query timings, Redis, response distributions, error rates, queue/backlog, and final financial/stock reconciliation.

## Performance gate

Pass requires repeatable results within approved SLOs, no invariant drift, and documented scaling/rollback thresholds. Asset-size success alone is not a capacity result.

