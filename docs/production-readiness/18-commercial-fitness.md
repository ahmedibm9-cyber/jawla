# Commercial Fitness

## Verdict

Jawla is **not commercially fit for real-client production use** at the audited revision. It is supportable only as a synthetic-data demonstration or internal engineering pilot with no production claims.

## Why commercial acceptance would be unsafe

- Tenant isolation can fail open in the administrator panel.
- Financial/stock mutations accept unauthorized or insufficiently proven inputs.
- Production deployment can create known credentials and demo ledger data.
- Offline behavior can duplicate or irrecoverably discard financial intent.
- Tax/compliance integrations are partial scaffolds and issued documents are not immutable.
- Backup restore, rollback, capacity, monitoring, concurrency, and full user journeys are not proven.
- Employee GPS and customer media privacy gates are incomplete.
- The role model and architecture contract contradict implementation.

## Claims that must not be made

- “Production ready” or “safe for real money/stock.”
- “Full offline sales day” or “exactly once across devices.”
- “Multi-tenant isolated” until PR-001 is closed and independently verified.
- “ZATCA Phase 2 compliant.”
- “Egypt ETA production integrated.”
- “WCAG 2.2 AA compliant.”
- “Backups/rollback/disaster recovery proven.”
- “GPS proves physical presence” or “fraud-proof attendance.”
- Any uptime, performance, RPO/RTO, security, privacy, or support SLA not measured and contracted.

## Potential commercial strengths after gates

- Broad route-sales domain coverage.
- Arabic RTL and English interface foundations.
- Unified admin and rep workflows.
- Transactional service structure and stock movement model.
- Offline queued-write foundation.
- PostgreSQL, CI, PWA, PDF/QR, API, and operational scaffolding.

These are product assets, not release evidence.

## Commercial acceptance checklist

1. Signed functional scope and explicit exclusions.
2. Named jurisdictions, tax claims, currencies, invoice/credit/refund rules.
3. Canonical roles, tenant ownership, platform administration, and segregation of duties.
4. Offline/device/network support contract and data-loss/conflict policy.
5. Privacy notice, employee GPS governance, DPA/subprocessors, retention/deletion, breach process.
6. Security acceptance including tenant/IDOR, authentication, file storage, telemetry, penetration testing, vulnerability policy.
7. Capacity/SLO, availability, support hours, incident severity/response, maintenance window.
8. Backup retention, RPO, RTO, restoration responsibility and exit/data-export procedure.
9. Accessibility target and supported devices/browsers.
10. Signed multi-role UAT, training, operational readiness, opening balances/stock migration and reconciliation.
11. Pricing, payment terms, warranty, liability, SLA credits, support/upgrade term, change control, termination, and data handback.
12. Exact release artifact and evidence manifest approved by engineering, security, finance, operations, privacy/legal, product, and customer.

## Go-to-market recommendation

Pause production onboarding and external readiness/compliance claims. Use the current build only with synthetic data while Critical controls and owner decisions are resolved. Re-audit a frozen release candidate after the P0/P1 roadmap and independent operational drills.

