# Monitoring and Incident Response

## Verdict

**NOT VERIFIED.**

## Present scaffolding

- Sentry package/configuration and a scrubber exist.
- Structured Laravel logging and rotation are configured.
- Railway uses a basic health endpoint.
- Readiness/privacy documents mention operational roles and external monitoring.

## Required production signals

| Signal | Required detection/escalation |
|---|---|
| Availability and latency | endpoint, dependency, p95/p99, error-rate SLO alerts |
| PostgreSQL | connections, locks/deadlocks, slow queries, replication/backup state, storage |
| Redis/queues | connectivity, memory, failed jobs, oldest job/backlog |
| Backups | success, age, size anomaly, restore-drill due date |
| Financial integrity | customer/cash/invoice reconciliation drift, negative/invalid state |
| Inventory integrity | stock versus movement drift, rejected negative mutations, transfer imbalance |
| Sync | duplicate-intent trend, conflicts, oldest queued record, protocol/version failures |
| Security | login abuse, tenant-deny/escape signals, privileged role/session changes, reversals |
| Privacy | file access anomalies, GPS access/retention jobs, telemetry scrubbing failures |
| Platform | CPU, RAM, disk, TLS/domain expiry, deploy health and rollback |

## Incident runbook requirements

- Named incident commander, technical lead, finance/data owner, privacy/security lead, support/customer communications owner, and backup restore operator.
- SEV definitions, acknowledgement/update/resolution targets, escalation channels and alternates.
- Immediate containment for known credential, tenant escape, duplicate money/stock mutation, data loss, tax submission ambiguity, and GPS/file exposure.
- Evidence preservation, audit-log protection, customer/regulator notification decision, and post-incident reconciliation.
- Recovery/rollback authority and dual control for ledger corrections.
- Post-incident review with corrective actions and verification.

## Proof required

Activate the actual production monitors, emit safe synthetic test events, prove paging/ticket delivery and acknowledgement, run an outage plus financial-integrity tabletop, and retain the signed record. Configuration files alone are not operational evidence.

