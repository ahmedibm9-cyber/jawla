# Jawla Production-Readiness Executive Verdict

## Audit identity

- Mode: `AUDIT_ONLY`
- Audited branch: `master`
- Audited commit: `ba768f7106b52fa8d2905daadc07cd6091ff0c26`
- Audit date: 2026-07-26
- Verdict: **NOT READY**
- Production-readiness score: **35/100**
- Commercial scope currently supportable: synthetic-data demonstration or internal engineering pilot only

The verdict is forced by ten Critical findings, unresolved tenant isolation, untrusted financial inputs, unsafe offline queue behavior, production seeding of known credentials/demo data, and the absence of current trustworthy end-to-end, recovery, rollback, and concurrency evidence.

## Severity summary

| Severity | Count |
|---|---:|
| Critical | 10 |
| High | 18 |
| Medium | 3 |
| Low | 0 |
| Total | 31 |

## Score

| Domain | Weight | Awarded | Reason |
|---|---:|---:|---|
| Financial and inventory integrity | 20 | 8 | Transactions exist, but return, pricing, cancellation, amendment, arithmetic, and audit-chain invariants fail |
| Security and authentication | 15 | 6 | Several sound defaults; tenant fail-open, stored XSS, RBAC drift, and privileged-session gaps remain |
| Test and release evidence | 15 | 6 | Broad Pest inventory and CI exist; current runtime evidence is inconclusive and critical browser/concurrency paths are absent |
| Offline/PWA reliability | 12 | 3 | Same-key replay receipts exist; real-browser durability, duplicate-intent, discard, dependency, and upgrade behavior fail or are unproven |
| Database integrity and tenancy | 10 | 4 | PostgreSQL FKs and a stock check exist; cross-tenant and financial constraints are largely application-only |
| Deployment and disaster recovery | 10 | 1 | Scripts and documents exist; production seeding is unsafe and restore/rollback are unproven |
| Monitoring and maintainability | 8 | 2 | Basic health/Sentry scaffolding; no verified alerts, incident ownership, reconciliation, or reliable current architecture record |
| Performance | 5 | 3 | Asset budgets pass; production-shaped current capacity is not verified |
| Accessibility | 5 | 2 | RTL, skip link, focus, and reduced motion exist; contrast failures and no WCAG 2.2 AA evidence |
| **Total** | **100** | **35** | |

## Top five launch blockers

1. **Tenant boundary failure:** Filament requests do not initialize `ActiveCompanyContext`; company global scopes fail open when context is null.
2. **Financial and stock integrity:** mutable Livewire stock-import state, arbitrary return lines, rep-supplied prices, and cancellation/amendment paths can create incorrect stock, balances, or cash.
3. **Unsafe production deployment:** Railway pre-deploy executes a known-password super-admin command and `DemoSeeder` against the production database.
4. **Offline financial reliability:** repeated confirmation creates new idempotency keys, while queued financial actions can be permanently discarded without confirmation, audit, or recovery.
5. **No trustworthy release proof:** concurrency, real-browser offline behavior, full rep/admin journeys, restore, rollback, current capacity, and operational monitoring are not verified.

## What is already sound

- Major financial services generally use `DB::transaction()`.
- Same-key offline replay has a database receipt and atomic handler/receipt structure.
- PostgreSQL foreign keys are widely used and stock quantity has a nonnegative check.
- Argon2id, session regeneration, secure/HttpOnly production cookie settings, CSRF, POST throttling, and several security headers exist.
- Rep PDF endpoints perform explicit user and company checks; inspected PDF interpolation is escaped.
- Public API routes use Sanctum abilities, active-company context, and throttling.
- Service-worker cache policy intentionally avoids storing authenticated HTML.
- Composer and npm advisory scans were clean at audit time; Pint and PWA asset budgets passed.

These controls are valuable but do not neutralize the launch blockers.

## Evidence confidence

- Static repository evidence: high confidence at the pinned commit.
- Runtime application evidence: limited. Concurrent agents unintentionally targeted the same `jawla_test` PostgreSQL database, producing setup deadlocks and an invalid full-suite run. These results are classified as **NOT VERIFIED**, not product pass/fail evidence.
- External environment evidence: not available. Production/staging settings, Railway promotion gates, storage policy, Sentry alerts, backups, legal approvals, branch protection, and signed UAT remain **NOT VERIFIED**.
- Working-tree caveat: unrelated concurrent changes appeared after pinning the commit. The audit uses the pinned commit as its source-of-truth reference and does not assess those uncommitted edits.

## Release rule

Do not deploy for real customers, employees, money, stock, tax invoices, or location data until every Critical finding is closed with reproducible evidence, High launch gates are accepted or closed by named owners, and an independent release candidate passes the required test, restore, rollback, privacy, and UAT gates.
