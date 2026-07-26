# Phase 1 — Catastrophic Containment Evidence

Date: 2026-07-26

Branch: `remediation/production-readiness`

Baseline: `ba768f7106b52fa8d2905daadc07cd6091ff0c26`

## Exit-gate result

| Gate                               | Result | Executable evidence                                                                                                                                                                        |
| ---------------------------------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| No known/default credentials       | Pass   | Production bootstrap requires a strong environment-supplied secret; demo and performance credentials are generated or explicitly supplied. The source credential scan returned no matches. |
| No production demo seeding         | Pass   | `railway.toml` performs migrations and cache preparation only. `DatabaseSeeder` invokes `DemoSeeder` only in explicit demo mode, and `DemoSeeder` independently refuses production mode.   |
| Tenant negative matrix             | Pass   | `CompanyIsolationMatrixTest`: 9 tests, 20 assertions. The complete Phase 1 PHP gate passed 23 tests and 84 assertions.                                                                     |
| Stored XSS payloads do not execute | Pass   | Contextual PHP sink test plus Playwright execution tests for rep/customer map popups: 2 browser tests passed across the stored payload corpus.                                             |
| Canonical roles can be provisioned | Pass   | `RoleSeederTest` proves all five canonical roles and the read-only `system_viewer` contract.                                                                                               |

## PR-001 — fail-closed company context

- Added a forward-only `company_user` assignment table with backfill from each user's primary company.
- Users may be assigned to several companies; the selected company is stored in session, displayed in the UI, validated against membership, and recorded as an activity when switched.
- Web, API, and Filament requests establish company context before business queries. Invalid selectors are rejected.
- Company-scoped models return no records when an enforced context is absent and reject mismatched creates, updates, and deletes.
- Core money, stock, sync, PDF, location, photo, report, widget, and Livewire paths now resolve or assert the active company rather than trusting a user's primary-company field.
- Service boundaries reject company mismatches even when a caller possesses a model identifier.

The generated matrix proves positive same-company access and negative cross-company behavior for model queries, service writes, web sessions, API headers, Filament record routes, maps, and company switching.

## PR-005 — deployment-mode separation

- `JAWLA_MODE` accepts only `production` or `demo` and defaults fail-safe to `production`.
- Ordinary production predeploy no longer runs any seeder or bootstrap command.
- Demo data is allowed only in demo mode and credentials are uniquely generated into private storage with restrictive file permissions.
- Demo mode permanently displays a bilingual evaluation banner, watermarks financial documents, and disables external tax submission.
- Production bootstrap is a one-time, empty-database transaction requiring explicit confirmation and a strong secret supplied through `JAWLA_BOOTSTRAP_ADMIN_PASSWORD`.
- Demo and production database separation and bootstrap steps are documented in `deployment-modes-and-bootstrap.md`.

## PR-013 — stored map-popup XSS

- Map popups are now constructed with DOM nodes, `textContent`, and text nodes.
- Stored representative/customer values no longer enter Leaflet through an HTML-string interpolation sink.
- The source sink audit found only trusted framework/layout slots and the static PWA install template outside vendor/build output.
- Browser tests inject element, event-handler, SVG, and URL-oriented payloads into both popup kinds and verify that no payload executes and no active injected node is created.
- The existing response CSP remains enforced. The broader nonce/report-only rollout and removal of legacy unsafe directives remains tracked under PR-022.

## Essential PR-014 — canonical role containment

- The seeder provisions `sales_rep`, `sales_manager`, `hr_admin`, `warehouse_keeper`, and `system_viewer`.
- The global `super_admin`/`admin` authorization bypass was removed.
- `system_viewer` is explicitly read-only and cannot mutate products.
- Core rep/admin access accepts canonical roles while temporary legacy-role compatibility remains during the controlled migration.

PR-014 remains open. Removal of all legacy roles plus MFA, step-up authentication, session inventory/revocation, throttling, logout, and trusted-proxy completion belongs to Phase 5.

## Commands and results

```text
php artisan test --compact tests/Feature/Deployment/ProductionDeploymentSafetyTest.php tests/Feature/Security/MapPopupXssTest.php tests/Feature/Tenancy/CompanyIsolationMatrixTest.php tests/Feature/Roles/RoleSeederTest.php
PASS — 23 tests, 84 assertions

php artisan test --compact [affected regression bundle]
PASS — 91 tests, 312 assertions

php artisan test --compact tests/Feature/EtaEInvoicingTest.php tests/Feature/HttpEtaClientTest.php
PASS — 11 tests, 40 assertions

php vendor/bin/pint --test
PASS

npm.cmd run build
PASS — 337 modules transformed

npx.cmd playwright test tests/e2e/map-popup-xss.spec.mjs --reporter=line
PASS — 2 tests

php artisan test --parallel --recreate-databases --drop-databases --processes=2 --compact --testsuite=Unit,Feature
PASS — 496 tests, 1,402 assertions
```

The repository-wide PostgreSQL Unit/Feature command recreated and dropped two isolated worker databases and is the final Phase 1 commit gate.
