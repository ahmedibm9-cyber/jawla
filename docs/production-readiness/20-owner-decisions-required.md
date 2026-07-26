# Owner Decisions Required

These decisions materially change the correct implementation or launch scope and cannot be inferred by engineering.

## Product and commercial scope

1. What exact workflows are sold in v1, and which are explicitly excluded?
2. Is the system a synthetic/internal pilot, limited production pilot, or general commercial release?
3. What companies, users, concurrent sessions, transactions/day, and multi-year data volumes are contracted?
4. What availability, latency, support hours, maintenance windows, warranty, SLA credits, and upgrade terms apply?
5. Who signs final UAT and production acceptance for each role?

## Roles, tenancy, and privileged access

6. Do the five primary-guide roles supersede the eight implemented legacy roles, or is the guide formally superseded?
7. What is the exact allow/deny matrix for every sensitive action?
8. Is there a cross-tenant platform super-admin? If yes, what MFA, step-up, break-glass, session, audit, and approval controls apply?
9. Who may reverse/cancel invoices, payments, returns, expenses, transfers, stock adjustments, or sync conflicts?
10. Is dual approval required for financial/stock corrections, user/role changes, and exports?

## Finance and inventory

11. What are the authoritative price lists, discount bounds, override roles/reasons, tax-inclusive rules, and rounding mode?
12. Are quantities fractional; at what scale per unit/product?
13. What is the invoice lifecycle for draft, issued, amended, cancelled, paid, partially paid, returned, credited, and refunded?
14. How are paid returns, negative customer credit, cash/card refunds, advances, and credit application handled?
15. Are standalone returns allowed? What proof/approval and damaged/quarantine stock rules apply?
16. What is the batch/FEFO/expiry/recall policy?
17. What are transfer rules for partial receipt, damage, loss, rejection, cancellation, and source ownership?
18. What document sequence format, gap/rollback/year-boundary rule, and public non-guessable identifier are legally approved?
19. Who owns reconciliation, variance thresholds, corrections, opening balances, and migration sign-off?

## Offline and devices

20. Is “offline” limited to queuing a write from an already loaded form, or must the full rep day survive launch/reload/navigation offline?
21. Which browsers, OS versions, device classes, storage quotas, and network conditions are supported?
22. May one account use multiple devices/tabs, and how is one real-world business intent identified?
23. What operation dependencies and conflict policy apply?
24. Who may discard/resolve an unsynced financial operation, and what audit/recovery is required?
25. What is the client/backend compatibility window during deployments?
26. What are outbox retention, encryption, shared/lost-device, logout/session-expiry, remote-revocation, and support-recovery policies?

## Compliance and legal

27. Which jurisdictions/entities require ZATCA Phase 1, Phase 2, Egyptian ETA, VAT invoices, credit/debit notes, or other rules?
28. What certified provider, credentials/certificates, authority environment, archival and response-retention model apply?
29. What may be marketed before certification?
30. What privacy lawful basis, notice, consent/legitimate-interest, precision, retention, deletion/export, DPA, subprocessor, and breach rules apply to GPS, photos, signatures, customer data, and telemetry?
31. Is WCAG 2.2 AA contractual or publicly claimed?

## Operations

32. Is Railway the ratified platform, formally replacing Forge/VPS documentation?
33. How are tenants and the first privileged user provisioned without production seeders?
34. What backup cadence, retention, independence, region/provider separation, encryption key custody, RPO, RTO, and drill frequency apply?
35. Who is the named deploy approver, rollback authority, restore operator, incident commander, security/privacy lead, finance/data owner, and customer communications owner?
36. What monitors, alert channels, severity targets, response times, and escalation paths are required?
37. Which scheduled batch/transit/reconciliation/retention alarms are in contract?
38. What branch protection, required checks, penetration testing, vulnerability SLA, and release evidence retention are mandatory?

Every answer should identify the accountable approver, effective date, source contract/policy, and the test or operational evidence that will prove implementation.

