# Production readiness implementation

**Implementation date:** 2026-07-29  
**Release verdict:** **NO-GO for real company data**

This document records what was implemented from the production-readiness plan
and separates repository controls from evidence that only operators, legal/tax
owners, and customer stakeholders can provide.

## Implemented repository controls

| Area | Implemented outcome | Primary evidence |
| --- | --- | --- |
| Offline sales | The browser's price-less sale payload is accepted; server pricing remains authoritative; optional stale quoted prices are still rejected | `SaleSyncHandler`, `OfflineSyncHandlersTest` |
| Sync failures | Validation/storage/processing failures return stable bilingual codes; raw exceptions stay in server logs | `SyncService`, `lang/{ar,en}/app.php`, `OfflineSyncTest` |
| Tenant isolation | Policies use the selected active company; stock authorization and Filament queries require both product and warehouse ownership | `ChecksCompanyOwnership`, `StockPolicy`, `StockResource`, tenant matrix tests |
| Role safety | Demo passwords are random; unintended executive/system-viewer permission bleed is removed; documented map/report access is seeded | `DemoSeeder`, `RoleSeeder`, role/resource tests |
| Photo privacy | Images are decoded and re-encoded before upload on local or S3 disks, stripping metadata without object-storage path assumptions; production refuses public photo storage | `PhotoService`, photo tests |
| Runtime compatibility | Obsolete Filament traits/events, a nonexistent audit model relation, and `env()` calls outside config were removed or replaced | `LeafletMapPicker`, `Company`, `AppServiceProvider` |
| Static analysis | PHPStan output capture is disabled; runtime-safety level 0 is blocking; strict level 6 remains an explicit debt audit | `Makefile`, `scripts/verify`, `phpstan.neon`, CI |
| Container safety | Frontend assets build in a pinned Node stage; WebP support is installed; `.env*` and `storage/*` cannot enter the image | `Dockerfile`, `.dockerignore` |
| CI | Lint, runtime static analysis, production asset build, PWA budget, dependency audits, secret scan, Unit/Feature, and Linux browser tests are blocking | `.github/workflows/ci.yml` |
| DAST | ZAP scanner/runtime failures are blocking and reports are retained | `.github/workflows/security.yml` |
| Promotion | The exact CI commit is deployed to staging, dependency readiness and staging DAST must pass, then a protected production environment promotes the same commit | `.github/workflows/deploy.yml` |
| Rollback | A separately confirmed, production-approved workflow verifies `canRollback`, rolls back a named Railway deployment, waits for success, and checks readiness | `.github/workflows/rollback.yml` |
| Platform readiness | Railway traffic switching uses dependency-aware `/health`, restart-on-failure, two replicas, Redis state, and private S3 photos | `railway.toml`, `SystemPageController::health` |
| Backup proof | Encrypted off-host backup remains fail-closed; scratch restore now requires source/target reconciliation and emits a mode-0600 evidence file | `scripts/backup.sh`, `scripts/restore-backup.sh` |

## Verification state

Verified locally during implementation:

- PHPStan level 0 passes after resolving all ten runtime-level findings.
- Focused offline-sync tests: 22 tests / 67 assertions passed.
- Focused tenancy/roles/deployment-safety tests: 39 / 110 passed.
- Focused photo tests: 14 / 30 passed.
- Pint passed on each changed PHP group.
- Admin login and health endpoint tests passed after the Filament event fix.

Still required before release:

- clean full Unit + Feature suite;
- clean production asset build and PWA asset audit from the final tree;
- Linux CI browser suite (the documented Windows Playwright/Pest lifecycle bug
  prevents authoritative local E2E);
- blocking GitHub security and deployment workflow runs;
- review and planned reduction of the existing PHPStan level-6 debt.

## External mandatory gates

Repository code cannot satisfy or self-approve these:

1. ETA taxpayer credentials, certificate-backed signer, official preproduction
   acceptance, and tax-owner approval.
2. A current encrypted backup, timed scratch restore, business reconciliation,
   measured RPO/RTO, and an independent operator's evidence.
3. GitHub production required reviewers, Railway environment tokens/variables,
   production auto-deploy disabled, and an exercised staging-to-production
   rollback drill.
4. External uptime/Sentry alert delivery to named on-call owners and an incident
   exercise.
5. Staging performance/capacity run against the defined SLOs.
6. Physical Android/iOS shared-device, offline/reconnect/update/logout tests and
   signed multi-role UAT.
7. Accessibility evidence for critical Arabic RTL and English LTR journeys.
8. Privacy, employee-location notice, retention/deletion, vendor/DPA/transfer,
   support/SLA, and customer contract approvals.

## Go-live decision rule

Release authority may change the verdict only when:

- the final commit's blocking CI and security checks are green;
- staging deployment, readiness, DAST, performance, accessibility, and UAT
  evidence all reference that same release;
- backup/restore and rollback drills have current evidence;
- every external gate has a named owner, date, evidence link, and approval;
- no P0/P1 risk remains open without an explicit, time-bounded acceptance signed
  by the accountable owner.

Until then, Jawla is restricted to synthetic-data development and controlled
UAT. Passing repository tests alone does not authorize real-data production.
