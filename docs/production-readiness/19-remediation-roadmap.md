# Remediation Roadmap

This is an audit recommendation, not an applied remediation plan. Estimates are relative and require owner validation.

## P0 — Contain before any deployment or real data

| Order | Finding(s) | Outcome/evidence | Size |
|---:|---|---|---|
| 1 | PR-005 | Remove production demo/known-credential seeding; inspect any environment and rotate credentials; clean fresh-deploy proof | S–M |
| 2 | PR-001 | Fail-closed tenant context and service/policy ownership; generated two-company Filament/API/Livewire matrix | L |
| 3 | PR-002 | Server-owned stock import confirmation with cross-tenant/nonnegative hostile tests | M |
| 4 | PR-003, PR-028 | Original-sale/cumulative return and approved paid-return/credit/refund lifecycle | L–XL |
| 5 | PR-007 | Authoritative price/discount/tax policy for online and sync paths | M–L |
| 6 | PR-006, PR-031 | Privileged immutable compensating reversal with reason/link/audit; destructive history denied | L |
| 7 | PR-009 | Correct invoice/payment/amend/cancel state machine and receivable/cash postings | L |
| 8 | PR-010 | One locked/versioned stock mutation/reconciliation protocol with real parallel tests | M |
| 9 | PR-004, PR-008 | Durable awaited enqueue, stable business intent, audited/recoverable conflict resolution | M–L |

## P1 — Establish production integrity and release proof

| Order | Finding(s) | Outcome/evidence | Size |
|---:|---|---|---|
| 10 | PR-011 | Canonical decimal/posting rules, DB constraints, customer/cash/stock reconciliation | L–XL |
| 11 | PR-025, PR-026 | Enforced batch traceability and complete transfer roles/state/conservation | L–XL |
| 12 | PR-027 | Approved collision-safe sequential legal numbering and concurrency proof | M |
| 13 | PR-013, PR-014 | Close XSS; ratify RBAC; identity-aware throttling, MFA/step-up/session controls | L |
| 14 | PR-015, PR-022 | Private file path, upload hardening, GPS governance/retention, CSP/telemetry proof | L plus governance |
| 15 | PR-016 | Ratified offline scope, versioned protocol, dependency/multi-device/upgrade behavior | L–XL |
| 16 | PR-019 | Isolated test infrastructure and blocking risk-driven Unit/Feature/browser/concurrency suite | L |
| 17 | PR-018 | Immutable artifact promotion, blocking CI/security, migration compatibility, exercised rollback | L |
| 18 | PR-017 | Independent encrypted backup and timed scratch restore with reconciliation | M operational |
| 19 | PR-020 | Current capacity run, business-invariant monitoring, named incident owners and drill | M–L |

## P2 — Compliance, documents, accessibility, and supportability

| Order | Finding(s) | Outcome/evidence | Size |
|---:|---|---|---|
| 20 | PR-012, PR-029, PR-030 | Jurisdiction decision, immutable issuance, official ZATCA/ETA certification or explicit exclusion | XL/external |
| 21 | PR-023 | Accessible tokens, blocking scans, manual WCAG 2.2 AA journey evidence | M |
| 22 | PR-024 | Approved scheduled alarms, overlap/idempotency/delivery evidence | M |
| 23 | PR-021 | Ratified current architecture, role/offline/release runbooks and superseded-doc markers | M |
| 24 | Commercial gates | Signed UAT, privacy/legal/tax/security/operations/support and customer acceptance | external |

## Release evidence package after remediation

- exact commit, image digest and dependency locks;
- migration/fresh-upgrade results;
- full clean isolated test reports;
- real-browser traces and screenshots for critical journeys;
- parallel concurrency results and final reconciliation;
- tenant negative matrix;
- official compliance validation or signed exclusions;
- restore and rollback drill records;
- capacity/SLO report;
- alert and incident drill;
- accessibility report;
- approved owner decision log and signed UAT.

No task should be considered closed from code review alone when its verification column requires runtime, external authority, legal, operational, or customer evidence.

