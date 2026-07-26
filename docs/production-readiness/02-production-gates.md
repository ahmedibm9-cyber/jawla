# Production Gates

## Gate decision

| Gate | Status | Blocking evidence | Required evidence to pass |
|---|---|---|---|
| Tenant isolation | FAIL | PR-001 | Two-company negative matrix through Filament initial requests, Livewire, policies, relation selectors, bulk actions, imports, exports, and direct IDs |
| Financial correctness | FAIL | PR-002, PR-003, PR-006, PR-007, PR-009, PR-011 | Authoritative server inputs, immutable reversal rules, reconciliation, and independent failure-path verification |
| Inventory integrity | FAIL | PR-002, PR-003, PR-010, PR-011 | Concurrent stock proof, cross-company FK/ownership proof, and ledger-to-balance reconciliation |
| Offline/PWA exactly-once behavior | FAIL | PR-004, PR-008, PR-016 | Real-browser offline/retry/multi-device/upgrade suite and recoverable conflict/discard policy |
| Authentication and privileged access | FAIL | PR-014 | Approved role matrix, MFA/step-up decision, identity-aware throttling, session lifecycle, CSRF-safe logout |
| Security and privacy | FAIL | PR-013, PR-015, PR-022 | Stored-XSS closure, private-file proof, GPS governance, CSP and telemetry evidence |
| Tax/compliance | FAIL | PR-012 | Jurisdictional owner decision, certified integration scope, signed sample documents, negative validation |
| Test evidence | NOT VERIFIED | PR-019 | Clean isolated PostgreSQL run plus blocking E2E and concurrency suite at the audited release candidate |
| Backup and restore | NOT VERIFIED | PR-017 | Independent encrypted backup, scratch restore, reconciliation, measured RPO/RTO, signed drill record |
| Deployment and rollback | FAIL | PR-005, PR-018 | No production seeders/known credentials, pinned artifact promotion, compatible migration, exercised rollback |
| Monitoring and incident response | NOT VERIFIED | PR-020, PR-024 | Named owners, tested alerts, incident runbook/tabletop, reconciliation and backup-age alarms |
| Performance/capacity | NOT VERIFIED | PR-020 | Current production-shaped staging load test including writes, sync, PDFs/reports, DB/Redis saturation |
| Accessibility | FAIL/PARTIAL | PR-023 | Contrast correction evidence, blocking automated scans, manual keyboard/screen-reader/zoom/RTL proof |
| Documentation/supportability | FAIL | PR-021 | Ratified architecture and runbooks matching implementation and release topology |
| Commercial/legal acceptance | NOT VERIFIED | Owner decisions | Contract scope, SLA, warranty, privacy/DPA, tax scope, support model, and signed multi-role UAT |

## Hard release conditions

The following conditions are mandatory:

1. Close all Critical findings.
2. Resolve or explicitly accept each High risk through a named accountable owner; security, tenant, money, stock, restore, and rollback risks cannot be waived by engineering alone.
3. Produce a clean immutable release candidate and rerun the complete evidence suite against an isolated PostgreSQL test database.
4. Complete a real-browser rep day flow, admin master-data flow, Arabic RTL/LTR smoke, network-loss recovery, and two-tenant IDOR matrix.
5. Exercise backup restore and rollback independently, with reconciled financial and stock totals.
6. Remove known/demo credential creation and synthetic financial seeding from production deployment.
7. Activate tested monitoring and incident channels before real users or real data.
8. Obtain privacy/legal approval before GPS, photos, signatures, customer data, or telemetry are enabled.
9. Obtain signed product/UAT acceptance for every role and advertised offline/compliance capability.

## Definition of “ready”

“Ready” means evidence from the exact promoted artifact, not confidence based on source inspection, old test results, documentation, or a developer database. A gate marked **NOT VERIFIED** is treated as blocking where money, stock, tenant isolation, personal data, recovery, or legal claims are involved.

