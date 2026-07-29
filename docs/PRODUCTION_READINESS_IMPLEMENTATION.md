# Production readiness implementation

**Implementation date:** 2026-07-29  
**Release verdict:** **NO-GO for real company data**

This document records what was implemented from the production-readiness plan
and separates repository controls from evidence that only operators, legal/tax
owners, and customer stakeholders can provide.

## Implemented repository controls

| Area                     | Implemented outcome                                                                                                                                                                                                                                                                                                    | Primary evidence                                                                   |
| ------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| Offline sales            | The browser's price-less sale payload is accepted; server pricing remains authoritative; optional stale quoted prices are still rejected                                                                                                                                                                               | `SaleSyncHandler`, `OfflineSyncHandlersTest`                                       |
| Sync failures            | Validation/storage/processing failures return stable bilingual codes; raw exceptions stay in server logs                                                                                                                                                                                                               | `SyncService`, `lang/{ar,en}/app.php`, `OfflineSyncTest`                           |
| Tenant isolation         | Policies use the selected active company; stock authorization and Filament queries require both product and warehouse ownership                                                                                                                                                                                        | `ChecksCompanyOwnership`, `StockPolicy`, `StockResource`, tenant matrix tests      |
| Role safety              | Demo passwords are random; unintended executive/system-viewer permission bleed is removed; documented map/report access is seeded                                                                                                                                                                                      | `DemoSeeder`, `RoleSeeder`, role/resource tests                                    |
| Photo privacy            | Images are decoded and re-encoded before upload on local or S3 disks, stripping metadata without object-storage path assumptions; production refuses public photo storage                                                                                                                                              | `PhotoService`, photo tests                                                        |
| Runtime compatibility    | Obsolete Filament traits/events, a nonexistent audit model relation, and `env()` calls outside config were removed or replaced                                                                                                                                                                                         | `LeafletMapPicker`, `Company`, `AppServiceProvider`                                |
| Static analysis          | PHPStan output capture is disabled; runtime-safety level 0 is blocking; strict level 6 remains an explicit debt audit                                                                                                                                                                                                  | `Makefile`, `scripts/verify`, `phpstan.neon`, CI                                   |
| Container safety         | Node, Composer, and PHP bases are digest-pinned; phpredis is version-pinned and checksum-verified; production PHP dependencies are reused during the deterministic frontend build; build-only packages and Composer stay out of the runtime; WebP support is installed; `.env*` and `storage/*` cannot enter the image | `Dockerfile`, `.dockerignore`, `ProductionDeploymentSafetyTest`                    |
| Scheduled maintenance    | The location-retention command is single-server and overlap-protected; the container supervises PHP-FPM, nginx, and Laravel's scheduler and fails if any required process exits                                                                                                                                        | `bootstrap/app.php`, `docker/start-container.sh`, `ProductionDeploymentSafetyTest` |
| Onboarding runtime       | The broken runtime CDN dependency was replaced by a first-party bilingual, RTL-aware, keyboard-accessible tour with a dedicated Linux browser check                                                                                                                                                                    | `public/js/onboarding.js`, `resources/css/onboarding.css`, onboarding tests        |
| CI                       | Lint, runtime static analysis, production asset build, PWA budget, dependency audits, secret scan, Unit/Feature, Linux browser tests, and an exact production-container build/runtime inspection are blocking                                                                                                          | `.github/workflows/ci.yml`                                                         |
| PWA data preservation    | Logout is blocked while offline work is pending or queue state cannot be verified; storage pressure is surfaced without deleting failed work                                                                                                                                                                           | `logout-guard.js`, `outbox.js`, `offline-safety.test.js`                           |
| PWA browser verification | A first-party Playwright suite verifies the manifest and every icon, service-worker scope, Arabic RTL/English LTR, keyboard focus, offline navigation, and a response-time budget                                                                                                                                      | `playwright.config.js`, `pwa-readiness.spec.js`                                    |
| DAST                     | ZAP scanner/runtime failures are blocking and reports are retained                                                                                                                                                                                                                                                     | `.github/workflows/security.yml`                                                   |
| Promotion                | The exact CI commit is deployed to staging, dependency readiness and staging DAST must pass, then a protected production environment promotes the same commit                                                                                                                                                          | `.github/workflows/deploy.yml`                                                     |
| Rollback                 | A separately confirmed, production-approved workflow verifies `canRollback`, rolls back a named Railway deployment, waits for success, and checks readiness                                                                                                                                                            | `.github/workflows/rollback.yml`                                                   |
| Platform readiness       | Railway traffic switching uses dependency-aware `/health`, restart-on-failure, two replicas, Redis state, and private S3 photos                                                                                                                                                                                        | `railway.toml`, `SystemPageController::health`                                     |
| Availability monitoring  | A five-minute GitHub monitor retries dependency-aware health checks, opens/comments a P1 issue on failure, and closes the incident after recovery                                                                                                                                                                      | `.github/workflows/production-health.yml`                                          |
| Backup proof             | Encrypted off-host backup remains fail-closed; scratch restore now requires source/target reconciliation and emits a mode-0600 evidence file                                                                                                                                                                           | `scripts/backup.sh`, `scripts/restore-backup.sh`                                   |

## Verification state

Verified locally from the exact working tree:

- The complete PostgreSQL-backed Unit + Feature suite passed:
  **813 tests / 2,271 assertions / 0 failures** in 1,570.65 seconds.
- Pint passed repository-wide. PHPStan's blocking runtime-safety level 0 passed
  with no errors. Prettier passed every changed JavaScript, JSON, and workflow
  file.
- PHPStan level 6 debt audit: 686 findings. It remains non-blocking by design
  and must be reduced incrementally without suppressions or a generated
  baseline.
- Offline queue/logout safety passed **5 JavaScript tests**. The standalone
  Chromium PWA audit passed **4 browser journeys** in 25.7 seconds, including
  every manifest icon, service-worker scope, RTL/LTR, keyboard focus, offline
  fallback, and response budget.
- Production Vite build: 339 modules transformed. PWA compressed budgets:
  JS 52.0 KiB / 300 KiB, CSS 20.4 KiB / 100 KiB, total 502.7 KiB /
  1,536 KiB.
- npm cache-only high-severity audit: zero vulnerabilities.
- Gitleaks v8.30.1 scanned all **333 Git commits** (~16 MB) and the current
  source/configuration/documentation tree (13.48 MB): no leaks found. Generated
  dependencies, build output, runtime state, VCS metadata, and ignored browser
  capture artifacts were excluded from the current-source pass.
- The final production image builds successfully as
  `sha256:8401963634906e74aa64fcd3f22758ea46026b5d23fa907351a35e53108c729f`
  (121,374,279 bytes). Required BCMath, GD, Intl, Mbstring, PostgreSQL, Redis,
  OPcache, and Zip modules load; compiled assets exist; Composer and npm are
  absent from the runtime.
- The exact image booted in production mode against disposable PostgreSQL and
  Redis containers. `/health` returned
  `{"status":"ok","db":"ok","cache":"ok"}`, and process inspection confirmed
  supervised PHP-FPM, nginx, and `artisan schedule:work`. Startup logs also
  proved that configuration, routes, and views were cached. `/admin/login`
  returned HTTP 200 with HSTS, CSP, frame denial, MIME-sniff protection,
  referrer policy, and HttpOnly session cookies. All disposable containers and
  their network were removed after the pass.
- Strict Composer validation remains red because `composer.json` was changed
  without refreshing the lockfile content hash. No lockfile mutation was made
  and no dependency metadata was sent externally without explicit
  authorization.

Still required before release:

- repair the stale Composer lock metadata through an explicitly authorized
  Composer lock refresh, then pass strict Composer validation and a fresh audit;
- create an immutable release commit, push it, and obtain green blocking
  GitHub CI/security checks on that exact commit, including the Linux Pest
  browser suite;
- deploy that exact commit to an isolated staging environment and pass DAST,
  capacity, accessibility, device/offline, migration, rollback, and UAT gates;
- enable production backups and complete a timed scratch restore with
  reconciliation and measured RPO/RTO;
- review and planned reduction of the existing PHPStan level-6 debt;
- activate alert delivery to named on-call/support owners and exercise the
  incident process.

## Current hosted-platform evidence

Read-only Railway and GitHub inspection on 2026-07-29 established:

- Railway production is serving successfully with two replicas, but it is
  still running commit `8f4132d`, not the current readiness changes.
- Railway has no staging environment. The only environment is production.
- The production PostgreSQL volume has no backup records and no backup
  schedules. No restore drill can therefore be evidenced.
- The latest remote CI run passed Unit/Feature, the production frontend/PWA
  build, secret scanning, and ZAP DAST. Composer-based jobs failed because
  package discovery booted without `APP_ENV=testing`; browser jobs failed
  because the referenced Shepherd CDN asset returned 404. Both causes are
  corrected locally and still require same-commit Linux CI proof.
- Recent production traffic showed no application error entries and low
  resource use, but the traffic sample was too small to constitute a capacity
  or performance test.

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

## Evidence-weighted re-audit

Audit identity:

- **Audit date:** 2026-07-29
- **Repository state:** `master` at
  `6b1f43338ee7765521e76e53a37e97534427992b`, with the readiness fixes still
  uncommitted
- **Execution environment:** Windows; PHP 8.3.32; Node 24.15.0/npm 11.12.1 for
  host JavaScript checks; Composer 2.10.2 in an isolated container; production
  image uses pinned PHP 8.3.32 and Node 22 build stages
- **Not Applicable redistribution:** none

| Category                                       |  Earned | Available | Evidence-based reason for points not earned                                                                                    |
| ---------------------------------------------- | ------: | --------: | ------------------------------------------------------------------------------------------------------------------------------ |
| Security and privacy                           |     160 |       180 | Exact-release DAST, legal/privacy approval, live Composer advisory refresh, and final CSP/third-party review remain incomplete |
| Reliability and data integrity                 |     115 |       120 | Physical multi-device/update/logout testing remains unverified                                                                 |
| Architecture and design                        |      86 |        90 | Minor documentation/implementation drift and strict-analysis debt remain                                                       |
| Code quality and maintainability               |      79 |        90 | The existing PHPStan level-6 backlog has 686 findings                                                                          |
| Testing and verification                       |     110 |       120 | Exact-commit Linux Pest browser, staging DAST/load, and signed UAT evidence are absent                                         |
| PWA compliance and offline behavior            |      95 |       100 | Physical-device install, upgrade, reconnect, and shared-device evidence is absent                                              |
| Performance and scalability                    |      58 |        80 | Asset budgets pass, but production-shaped Core Web Vitals and load/capacity SLOs are unmeasured                                |
| Deployment and environment safety              |      58 |        80 | Composer lock validation is red; the current tree has no immutable green CI artifact, staging promotion, or rehearsed rollback |
| Observability, backup, and recovery            |      18 |        50 | Health works, but the monitor is not active and production has no backup/restore evidence or named alert ownership             |
| Accessibility and UX resilience                |      31 |        40 | Keyboard/RTL/LTR browser checks pass; full WCAG 2.2 AA, screen-reader, contrast, zoom, and device evidence is absent           |
| Documentation and developer experience         |      28 |        30 | Operational evidence links and owner sign-offs remain placeholders                                                             |
| Governance and supply chain                    |      14 |        20 | Pins and secret scans pass; repository license/third-party notice, branch protection, and review ownership remain unresolved   |
| **Evidence-weighted total before blocker cap** | **852** |  **1000** | **Conditional production candidate before operational caps**                                                                   |

### Readiness decision

- **Active blocker cap:** **599** — the stateful production PostgreSQL service
  has no configured backup record, no backup schedule, and no verified restore
  path.
- **Final score after cap:** **599/1000**
- **Release decision:** **Major remediation required; not ready for external
  users**
- **Audit coverage:** **87%**
- **Confidence:** **Medium** — critical local journeys and the production image
  were executed, but the exact release commit, staging, recovery, alert
  delivery, compliance, and physical-device scope remain opaque.

The earlier uncapped working score was 747. The approved repository fixes raise
the evidence-weighted uncapped result by 105 points to 852. The final score
cannot rise above 599 until production data has a credible, verified recovery
path.

### Remaining findings and approval-gated remediation

```yaml
findings:
  - id: "OPS-001"
    severity: "high"
    status: "verified-failure"
    category: "observability-incident-response-backup-recovery"
    title: "Production PostgreSQL has no credible recovery path"
    evidence:
      - "Railway production volume inspection: zero backup records and zero schedules"
      - "No current scratch-restore evidence with reconciliation and measured RPO/RTO"
    practical_impact: "A database incident could permanently lose customer, stock, payment, or invoice data."
    recommendation: "Enable managed backups, then perform and retain evidence from a timed scratch restore."
    proposed_fix_id: "FIX-007"
    score_impact: 32
    blocker_cap: 599

  - id: "DEP-001"
    severity: "high"
    status: "verified-failure"
    category: "deployment-configuration-dependencies"
    title: "Composer lock metadata is stale"
    evidence:
      - "composer validate --strict --no-check-publish exits 2"
    practical_impact: "The blocking dependency CI job will reject the release and dependency intent is not fully reproducible."
    recommendation: "Run an authorized Composer lock-only refresh, verify the diff is metadata-only, then rerun strict validation and live audit."
    proposed_fix_id: "FIX-008"
    score_impact: 8
    blocker_cap: 0

  - id: "REL-001"
    severity: "high"
    status: "unverified"
    category: "testing-deployment-observability"
    title: "The exact release has not passed immutable CI, staging, rollback, or alert delivery"
    evidence:
      - "Readiness fixes are uncommitted and unpushed"
      - "Railway production runs commit 8f4132d, not this working tree"
      - "No Railway staging environment exists"
    practical_impact: "Local success does not prove the deployable artifact or operational response will behave the same way."
    recommendation: "Commit and push the exact tree, pass blocking CI/security, deploy it to staging, and execute DAST, capacity, rollback, and alert drills."
    proposed_fix_id: "FIX-009"
    score_impact: 55
    blocker_cap: 849

  - id: "COM-001"
    severity: "high"
    status: "unverified"
    category: "security-privacy-governance"
    title: "ETA, privacy, support, and signed business acceptance remain external gates"
    evidence:
      - "No taxpayer signing certificate or accepted ETA preproduction submission"
      - "No approved employee-location privacy notice, retention/deletion policy, support ownership, or signed UAT"
    practical_impact: "The product may be technically functional but legally or operationally unsuitable for real customers."
    recommendation: "Obtain accountable-owner evidence and approvals before any real-data release."
    proposed_fix_id: "FIX-010"
    score_impact: 45
    blocker_cap: 849
```

```yaml
remediation_plan:
  audit_id: "jawla-pwa-2026-07-29-r2"
  current_score: 599
  current_status: "major-remediation-required"
  approval_required: true
  fixes:
    - id: "FIX-007"
      priority: "P0"
      resolves_findings: ["OPS-001"]
      change_summary: "Enable paid production backups and run a timed scratch restore with reconciliation."
      files_expected:
        ["external Railway configuration", "restore evidence record"]
      behavior_change: "deployment-affecting"
      risk: "medium"
      rollback: "Disable the schedule only after a replacement recovery control is verified."
      verification:
        [
          "backup record",
          "restore exit 0",
          "row-count and ledger reconciliation",
          "measured RPO/RTO",
        ]
      expected_score_gain: "25-40 plus removal of the 599 cap"
      approval_status: "pending separate cost approval"
    - id: "FIX-008"
      priority: "P0"
      resolves_findings: ["DEP-001"]
      change_summary: "Refresh Composer lock metadata and run strict validation plus a live advisory audit."
      files_expected: ["composer.lock"]
      behavior_change: "none"
      risk: "low"
      rollback: "Restore the backed-up lockfile if the diff changes package versions or dependencies."
      verification:
        ["metadata-only diff", "composer validate --strict", "composer audit"]
      expected_score_gain: "5-10"
      approval_status: "pending explicit Packagist metadata-egress approval"
    - id: "FIX-009"
      priority: "P0"
      resolves_findings: ["REL-001"]
      change_summary: "Create the immutable release, pass remote CI/security, and exercise a paid staging promotion and rollback."
      files_expected:
        ["Git commit", "GitHub check evidence", "Railway staging evidence"]
      behavior_change: "deployment-affecting"
      risk: "medium"
      rollback: "Use the protected rollback workflow with the previously verified deployment ID."
      verification:
        [
          "all required checks green",
          "staging health/DAST/load pass",
          "rollback and recovery pass",
        ]
      expected_score_gain: "35-60"
      approval_status: "pending separate commit, push, and staging-cost approval"
    - id: "FIX-010"
      priority: "P0"
      resolves_findings: ["COM-001"]
      change_summary: "Complete ETA preproduction, privacy/legal, support/on-call, accessibility/device, and signed UAT evidence."
      files_expected: ["external approvals and evidence records"]
      behavior_change: "deployment-affecting"
      risk: "high"
      rollback: "Keep ETA disabled and restrict Jawla to synthetic-data UAT until every approval is complete."
      verification:
        [
          "ETA acceptance",
          "legal/privacy approval",
          "named support/on-call",
          "signed UAT",
          "device/accessibility results",
        ]
      expected_score_gain: "35-55"
      approval_status: "pending external owners and credentials"
```

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
