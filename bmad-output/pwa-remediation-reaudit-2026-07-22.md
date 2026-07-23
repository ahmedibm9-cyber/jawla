# PWA remediation re-audit — 2026-07-22

**Scope:** re-assessment of the original release blockers affected by the
remediation work in this change set. This is not a replacement for the
137-domain production audit and does not authorize a launch.

## Evidence ledger

| ID    | Original failed control                             | Current assessment | Evidence                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| ----- | --------------------------------------------------- | ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| P0-1  | Company isolation and sensitive Activity properties | **Partial**        | `Activity` now uses `BelongsToCompany`; `ActivityLog` has role access control; user audit properties are allowlisted. A focused cross-company regression passed. Legacy-row remediation and all-role/direct-ID proof remain.                                                                                                                                                                                                                                        |
| P0-2  | Authenticated PWA data in shared cache/storage      | **Partial**        | `sw.js` caches only an offline shell and public static assets; IndexedDB is HMAC identity-partitioned; logout clears the current outbox and sends a cache purge. Local browser evidence authenticated a synthetic rep, observed only public cache paths, then submitted the real logout form and verified both Cache Storage and the identity-partitioned IndexedDB database were empty. Shared-device and browser-matrix proof remain.                             |
| P0-3  | Financial/stock bypasses and races                  | **Partial**        | Invoice, payment, return, expense, cash-reconciliation, and van-transfer services now validate company-bound IDs at the service layer; invoice/return paths require active van warehouses; cash-box creation locks the user row and adopts legacy rows; payment/return/expense cancellation and transfer transitions are locked and idempotent where applicable. Float calculations, simultaneous conservation testing, and production-DB concurrency proof remain. |
| P0-4  | Non-atomic sync receipt acknowledgement             | **Partial**        | Receipt creation, handler mutation, and response finalization now share one transaction; receipt-finalization failure rolls back the business effect; ambiguous legacy null receipts are quarantined as conflicts. A unique-key race now re-reads the durable receipt and returns the original outcome. Concurrent production-DB and crash-boundary testing remain.                                                                                                 |
| P0-5  | False all-synced queue UI                           | **Partial**        | `jawlaSyncQueue` is registered, initializes as loading, and displays pending/failed/conflict data. A browser check reached the resolved empty state. Real offline reload/reconnect coverage remains.                                                                                                                                                                                                                                                                |
| P0-6  | Unsafe service-worker takeover                      | **Partial**        | The worker no longer calls `skipWaiting()`/`clients.claim()` automatically; activation requires an explicit client message and is deferred with queued work. A deferred update is re-offered when the queue reports no pending work. Multi-tab/version rollback testing remains.                                                                                                                                                                                    |
| P0-7  | Placeholder restore and unproven rollback           | **Fail**           | Encrypted off-host backup and scratch-only restore scripts now fail closed and runbook requirements are documented, but no configured remote, independent restore drill, measured RPO/RTO, or rollback exercise exists.                                                                                                                                                                                                                                             |
| P0-8  | Fail-open CI/security release path                  | **Partial**        | CI no longer suppresses dependency/install/audit failures, builds production assets, and blocks obvious committed secrets; CodeQL runs on pull requests and protected-branch pushes; the security workflow requires an explicit staging URL. The deploy script builds assets and fails closed on an unhealthy health endpoint. Required container/SBOM, a11y/performance, live ZAP, and signed-promotion evidence remain.                                           |
| P0-9  | Privacy/legal control absence                       | **Fail**           | A data-inventory/ownership/release-gate template exists, but counsel approval, notices, agreements, DPA/vendor register, lawful-basis review, retention schedule, and exercises are external and unprovided.                                                                                                                                                                                                                                                        |
| P0-10 | Egyptian ETA production readiness                   | **Fail**           | No certified signer, credentials, official preproduction acceptance, asynchronous delivery/reconciliation, or monitoring evidence has been added.                                                                                                                                                                                                                                                                                                                   |
| P0-11 | Primary action contrast                             | **Partial**        | Primary/success token changed to `#3D7A18`; white-text contrast is 5.26:1. Full WCAG 2.2, forced-colors, screen-reader, and device evidence remains.                                                                                                                                                                                                                                                                                                                |

## Verification evidence

- Grade **A:** focused Pest suites passed for tenant Activity isolation,
  invoice stock backing, payment guard, return behavior, offline receipt
  atomicity, and offline queue UI.
- Grade **A:** `npm run build`, `php artisan view:cache`, `composer audit`, and
  `npm audit --audit-level=high` completed with exit code 0; npm reported zero
  vulnerabilities.
- Grade **A:** local browser check authenticated a synthetic rep, confirmed an
  active service worker, observed a cache limited to `/offline`,
  `/manifest.json`, and public static assets, and observed the queue loading
  state resolve.
- Grade **A:** local browser logout test authenticated a synthetic rep, found a
  single identity-partitioned IndexedDB database, submitted the production
  logout form, reached `/app/login`, and confirmed both Cache Storage and
  IndexedDB were empty afterward.
- Grade **A:** the full PostgreSQL-backed suite passed: **332 tests, 1,038
  assertions**. This includes **12 browser E2E tests**. The historical test
  failure card was removed after the valid return fixture and number-format
  assertions were corrected.
- Grade **A:** after the final service hardening, all test groups were run with
  visible progress: **76 unit tests**, **249 feature tests**, and **12 browser
  E2E tests** passed (337 tests total). The compact aggregate reporter was
  slower than its previous run and was not used as replacement evidence.

## Release decision

**NO-GO remains.** The remediation reduces several code-level P0 risks, but the
backup/restore, privacy/legal, ETA, full CI/security, browser-matrix,
simultaneous-conservation, and independent operational evidence gates remain
open. A synthetic-data-only local demonstration is the maximum supported scope.

## Next evidence required

1. Run multi-tab/service-worker update, shared-device logout, offline reload,
   reconnect, conflict-resolution, and simultaneous money/stock tests against
   staging.
2. Configure and independently exercise encrypted off-host backup/restore and
   rollback; record measured RPO/RTO and reconciliation.
3. Obtain accountable privacy/legal/tax approvals and run signed multi-role UAT
   before authorizing a bounded synthetic-data staging pilot.
