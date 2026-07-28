# Jawla — Project Exploration Report

## Executive Summary

Jawla (جولة) is a bilingual (Arabic/English) field-sales CRM/ERP for the Egyptian market. Sales reps run daily "jawla" routes: GPS-tracked customer visits, van-stock sales, cash collection, and returns. Admins manage master data via a Filament dashboard. The system is offline-first — every financial mutation has an IndexedDB outbox path that replays through the same server-side service methods.

**Confidence: 95/100** — verified via direct code inspection, test execution, and runtime tracing.

## Project Identity and Purpose

| Attribute | Value |
|-----------|-------|
| Name | Jawla (جولة) |
| Owner | Fulla Chemical Trading Co. (`@ibrahim-fulla`) |
| Purpose | Field-sales CRM/ERP for Egyptian distribution companies |
| Users | 6-20 daily sales reps + admin/manager back-office |
| Scale | Single-tenant per deployment, multi-company within tenant |
| Connectivity | Offline-first; reps work without connectivity, sync later |
| Language | Bilingual Arabic (default) / English with RTL support |
| Currency | Egyptian Pound (EGP) — single currency, no conversion |
| E-invoicing | Egyptian Tax Authority (ETA) integration (feature-flagged off) |

## Verified Technology Stack

| Layer | Technology | Version | Evidence |
|-------|-----------|---------|----------|
| Backend | Laravel | 13.20.0 | `composer.json:18` |
| PHP | PHP | 8.3 | `composer.json:16` (platform lock) |
| Admin panel | Filament | 4.0 | `composer.json:22` |
| Rep PWA | Livewire | 3.0 | `composer.json:28` |
| CSS | Tailwind CSS | 4.0 | `package.json:14` via `@tailwindcss/vite` |
| Database | PostgreSQL | 16 | `composer.json:8` (`pdo_pgsql`) |
| Build | Vite | 8.0 | `package.json:18` via `laravel-vite-plugin` |
| Maps | Leaflet + OpenStreetMap | 1.9.4 | `package.json:11` |
| PDF | mPDF | 8.3.1 | `composer.json:31` |
| QR | simplesoftwareio/simple-qrcode | 4.2.0 | `composer.json:36` |
| Permissions | spatie/laravel-permission | 8.3.0 | `composer.json:37` |
| Auth tokens | Laravel Sanctum | 4.3.2 | `composer.json:40` |
| Error tracking | Sentry | 4.27.0 | `composer.json:42` |
| Testing | Pest | 4.7.5 | `composer.json:55` |
| Static analysis | Larastan (PHPStan level 6) | 3.4.1 | `composer.json:58` |
| Linting | Laravel Pint | 1.24.0 | `composer.json:59` |
| E2E | Playwright | 1.61.1 | `package.json:22` |

## Architecture and Runtime Flows

### System Architecture

```
┌─────────────────────────────────────────────────────┐
│                    BROWSER / PWA                     │
│  ┌──────────────┐  ┌──────────────┐  ┌───────────┐  │
│  │ Service Worker│  │  IndexedDB   │  │ Offline   │  │
│  │  (cache +    │  │  (outbox +   │  │ Indicator │  │
│  │   fallback)  │  │   idempotency│  │           │  │
│  └──────────────┘  └──────────────┘  └───────────┘  │
└──────────────────────┬──────────────────────────────┘
                       │ HTTP POST /app/sync
                       │ X-Sync-Protocol-Version
                       │ X-Device-Id
┌──────────────────────▼──────────────────────────────┐
│                 LARAVEL 13 APP                        │
│  ┌────────────────────────────────────────────────┐  │
│  │ Middleware: SecurityHeaders → SetActiveCompany  │  │
│  │           → SetLocale → ThrottlePost           │  │
│  └────────────────────┬───────────────────────────┘  │
│                       │                              │
│  ┌──────────┐  ┌──────▼──────┐  ┌────────────────┐  │
│  │ Filament │  │  Livewire   │  │  SyncController │  │
│  │  (admin) │  │  (rep PWA)  │  │  → SyncService  │  │
│  └──────────┘  └─────────────┘  └────────────────┘  │
│                       │                              │
│  ┌────────────────────▼───────────────────────────┐  │
│  │            SERVICE LAYER (36 services)          │  │
│  │  InvoiceService, PaymentService, StockService,  │  │
│  │  ReturnService, ExpenseService, PricingService, │  │
│  │  VisitReportService, ComplaintService, etc.     │  │
│  └────────────────────┬───────────────────────────┘  │
│                       │                              │
│  ┌────────────────────▼───────────────────────────┐  │
│  │         Eloquent + DB::transaction()             │  │
│  │  BelongsToCompany global scope (multi-tenancy)   │  │
│  │  AppendOnly concern (financial ledgers)          │  │
│  └────────────────────┬───────────────────────────┘  │
└───────────────────────┼──────────────────────────────┘
                        │
┌───────────────────────▼──────────────────────────────┐
│              PostgreSQL 16                            │
│  127 migrations, 65+ models, append-only triggers    │
└──────────────────────────────────────────────────────┘
```

### Key Runtime Flow: Offline Sale → Sync → Invoice

1. **Rep creates sale offline** — `SalesFlow` Livewire component adds items to cart, calculates tax via `InvoiceCalculationService`, calls `queueOffline()` which enqueues to IndexedDB outbox with SHA-256 payload hash and UUID idempotency key
2. **Device comes online** — `sync.js` detects `online` event, calls `flush()`, topologically sorts operations by `dependsOn`, POSTs batch of up to 100 operations to `/app/sync`
3. **Server receives** — `SyncController` validates envelope, delegates to `SyncService::process()`
4. **Exactly-once guarantee** — Inside `DB::transaction`, checks `sync_receipts` for existing idempotency key. If duplicate, returns stored result. If new, creates receipt with null response, runs handler, stores response.
5. **Handler execution** — `SaleSyncHandler` delegates to `InvoiceService::create()` — same code path as online sales
6. **Response reconciliation** — Client removes applied operations from outbox, marks conflicts/mismatches as failed

**Confidence: 95/100** — traced through `resources/js/offline/outbox.js:1-193`, `resources/js/offline/sync.js:1-253`, `app/Http/Controllers/App/SyncController.php:1-43`, `app/Services/Sync/SyncService.php:1-143`, `app/Services/Sync/Handlers/SaleSyncHandler.php`.

## Domain Model and Glossary

### Core Entities

| Entity | Purpose | Key Relationships |
|--------|---------|-------------------|
| `Company` | Tenant boundary | has Users, Customers, Products, Routes |
| `User` | Employee (rep, admin, manager) | belongs_to Company, has Roles |
| `Customer` | Delivery/sales target | belongs_to Company, has Route |
| `Product` | Sellable item | has Stock, ProductPrices, Batches |
| `Invoice` | Sale record | belongs_to Customer, has Items, Payments |
| `Payment` | Cash/card collection | belongs_to Customer, allocated to Invoices |
| `ReturnRecord` | Product return | belongs_to Customer, has ReturnItems |
| `Stock` | Per-product per-warehouse quantity | belongs_to Product, Warehouse |
| `StockMovement` | Audit trail for stock changes | append-only ledger |
| `Visit` | GPS-tracked customer visit | belongs_to Customer, has VisitReport |
| `Route` | Daily rep assignment | has Customers, DailyVisitAssignments |
| `VanTransfer` | Van-to-van stock transfer | has VanTransferItems |
| `CashBox` | Cash ledger per user | append-only |
| `Expense` | Rep expense logging | belongs_to CashBox |
| `SyncReceipt` | Idempotency ledger for offline sync | append-only, unique per company+key |
| `Batch` | Product batch with expiry | belongs_to Product, Warehouse |
| `PriceQuotation` | Price quote to customer | belongs_to Customer |
| `ProformaInvoice` | Pre-sale invoice | belongs_to Customer |
| `PurchaseOrder` | Supplier order | belongs_to Supplier |
| `Alarm` | Operational alert | has reads, notifications |
| `Complaint` | Customer complaint | belongs_to Customer |

### State Machines

| Entity | States | Evidence |
|--------|--------|----------|
| `InvoiceStatus` | Draft → Issued → Submitted → PartiallyPaid → Paid → Credited → Voided → Cancelled → Amended | `app/Enums/InvoiceStatus.php` |
| `VanTransferStatus` | Requested → InTransit → Received → Cancelled | `app/Enums/VanTransferStatus.php` |
| `VisitPurpose` | delivery, collection, sales, return, complaint, other | `app/Enums/VisitPurpose.php` |
| `StockReason` | sale, return, transfer_in, transfer_out, adjustment, count, import | `app/Enums/StockReason.php` |

### Business Rules (from `docs/BUSINESS_RULES.md`)

1. Stock never goes negative
2. All money mutations in DB::transaction
3. Stock changes only through StockService
4. Append-only financial ledgers (Postgres triggers)
5. Offline mutations replay through same service methods
6. Multi-tenancy: all queries scoped by company_id
7. Bilingual: every UI string via `l('arabic', 'english')`
8. RBAC: Spatie permission, step-up auth for financial actions
9. GPS geofence: server-side distance recomputation (never trust client)
10. Single currency: EGP, no conversion

### Glossary

| Term | Meaning in Jawla |
|------|-----------------|
| Jawla (جولة) | "Tour" — a rep's daily route/shift |
| Van Stock | Products loaded on the rep's vehicle for delivery |
| Proforma | Pre-sale invoice (not a financial document) |
| ETA | Egyptian Tax Authority — e-invoicing integration |
| ZATCA | Saudi e-invoicing (QR code generation) |
| CashBox | Per-rep cash ledger; all cash movements are append-only |
| Reconciliation | End-of-day cash counting and variance detection |
| Geofence | GPS radius check around customer location |
| Outbox | IndexedDB queue of offline operations pending sync |
| SyncReceipt | Server-side idempotency record per operation |

## Data Stores and Interfaces

### Primary Data Store

- **PostgreSQL 16** — single database, multi-tenant via `company_id` column
- **127 migrations** spanning initial schema through feature additions
- **65+ Eloquent models** with `BelongsToCompany` global scope
- **Append-only concerns** on financial models (Postgres triggers enforce)

### Caching

- **Local dev:** file-based cache
- **Production:** Redis for session, cache, and queue
- **Service worker:** static asset caching (cache-first), offline page fallback

### External Interfaces

| Interface | Protocol | Auth | Idempotency | Evidence |
|-----------|----------|------|-------------|----------|
| ETA E-Invoicing | REST (OAuth2) | Client credentials | UUID per document | `app/Services/Eta/HttpEtaClient.php` |
| ZATCA QR | Local generation | N/A | N/A | `app/Services/ZatcaPhase1Strategy.php` |
| GPS/Maps | Leaflet.js + OpenStreetMap | Browser API | N/A | `resources/js/` |
| Sentry | HTTPS | DSN | N/A | `config/sentry.php` |
| Thermal Printer | WebSocket/Bluetooth | Device | N/A | `app/Support/ThermalPrintFormatter.php` |
| Public API | REST (Sanctum) | Token abilities | N/A | `routes/api.php` |

### Key Environment Variables

| Variable | Purpose | Default |
|----------|---------|---------|
| `JAWLA_MODE` | production/demo | production |
| `ETA_ENABLED` | E-invoicing master switch | false |
| `ETA_CLIENT_ID/SECRET` | OAuth credentials | empty |
| `ETA_TAXPAYER_RIN` | Tax registration | empty |
| `SENTRY_LARAVEL_DSN` | Error tracking | empty |
| `JAWLA_STOCK_IMPORT_PREVIEW_TTL` | Import preview expiry | 15 min |
| `JAWLA_RETENTION_LOCATION_PINGS` | GPS data retention | 90 days |

## Development and Operations Workflow

### Setup Commands

| Command | Status | Side Effects |
|---------|--------|-------------|
| `make setup` | Declared | Installs deps, copies .env, generates key, migrates, builds assets |
| `make dev` | Declared | Starts artisan serve + queue worker + pail + npm dev |
| `make lint` | Verified | `pint --test` (dry-run, no changes) |
| `make typecheck` | Verified | `phpstan analyse` (level 6, read-only) |
| `make test` | Verified | `artisan test --testsuite=Unit,Feature` (142 unit tests pass) |
| `make test-e2e` | Declared | Playwright browser tests |
| `make build` | Verified | `npm run build` (Vite production build) |
| `make verify` | Verified | lint + typecheck + test + build |
| `make smoke` | Declared | route:list + config:cache + view:cache |

### CI/CD Pipeline

| Workflow | Trigger | Purpose | Status |
|----------|---------|---------|--------|
| `ci.yml` | push/PR to `main` | Lint + Test | **BRANCH MISMATCH** — triggers on `main`, deploy on `master` |
| `deploy.yml` | push to `master` | Staging → Production deploy | Railway auto-deploys |
| `e2e.yml` | push/PR to `master` | Playwright browser tests | Advisory (continue-on-error) |
| `security.yml` | push/PR to `master` + weekly | Gitleaks + composer/npm audit + ZAP | ZAP advisory |

### Deployment

- **Primary:** Railway (2 replicas, Redis, pre-deploy migrations)
- **Backup:** Render (free-tier, Docker-based)
- **Docker:** Alpine PHP-FPM + Nginx, port 8080
- **Backups:** `scripts/backup.sh` → pg_dump → age encryption → rclone

## Quality and Risk Findings

### Critical Risks

1. **CI/Deploy branch mismatch** — CI triggers on `main`, deploy on `master`. Current branch is `master`. CI never runs on pushes to `master`. **Confidence: 95/100** (verified: `ci.yml:4-6`, `deploy.yml:4-5`, `git branch --show-current` returns `master`).

2. **No rate limiter tests** — SECURITY.md states rate limiting on login (5/min) and POST routes (60/min), but no test covers this. **Confidence: 90/100** (grep for "rate" in tests/ returns zero hits).

3. **`route:cache` disabled** — Every request re-parses route definitions. Performance penalty in production. **Confidence: 95/100** (`docs/DEPLOYMENT.md:39`).

### High Risks

4. **E2E tests advisory-only** — `e2e.yml` uses `continue-on-error: true`. Browser tests never block deploys. **Confidence: 95/100** (`e2e.yml:7`).

5. **ZAP scan advisory-only** — Security scanning produces reports but never fails pipelines. **Confidence: 95/100** (`security.yml`).

6. **`make verify` skips dependency audits** — Only `scripts/verify` includes `composer audit` + `npm audit`. **Confidence: 90/100** (`Makefile:47-54` vs `scripts/verify:18-22`).

### Medium Risks

7. **No Livewire component tests** — 24 Livewire components, minimal testing. No state management or real-time validation tests. **Confidence: 85/100** (grep for "Livewire" in tests/ returns limited hits).

8. **No CSRF test** — Security headers tested, but CSRF token enforcement on financial mutations not tested. **Confidence: 80/100**.

9. **PR-022 CSP deferred** — Alpine.js requires `unsafe-eval`. Cannot remove without framework changes. **Confidence: 95/100** (from remediation state).

### Testing Coverage

| Category | Files | Assessment |
|----------|-------|------------|
| Unit tests | 20 files, 142 tests | Strong service-layer coverage |
| Feature tests | 80 files | Good financial integrity testing |
| Browser tests | 3 files | Minimal — one is single-page load |
| E2E tests | 1 file | XSS regression only |
| Stress tests | 15 files | k6 scripts, results static |

### Strengths

- Financial integrity: append-only ledgers, idempotency, pricing tampering tests
- Multi-tenancy: company isolation matrix tests
- Security: Sentry scrubber, security headers, XSS, deployment safety tests
- Offline sync: exactly-once semantics with payload hashing
- Documentation: architecture, security, business rules, monitoring, deployment
- Encrypted backups with restore safety guards

## Contradictions and Unknowns

### Confirmed Contradictions

1. **CI/Deploy branch mismatch** — `ci.yml` triggers on `main`, `deploy.yml` on `master`. The default branch is `master`. CI never runs on the deploy branch.
   - **Evidence:** `ci.yml:4-6`, `deploy.yml:4-5`, `git branch --show-current` = `master`
   - **Impact:** High — CI is effectively dead on the deploy branch

### Unknowns

1. **Is Railway the active deployment?** — `railway.toml` exists, deploy.yml targets Railway, but no evidence of actual Railway project status. Could be stale config.
   - **Resolution:** Check Railway dashboard or `railway status`
   - **Blocked:** No Railway CLI access in this session

2. **Are there other deployment targets?** — README mentions Render, Laravel Forge. Are these active or historical?
   - **Resolution:** Check deployment history or Railway/Render dashboards

3. **What is the actual test database state?** — Tests use `jawla_test` database. Is this running in CI or only locally?
   - **Resolution:** CI workflow confirms PostgreSQL 16 service container

## Confidence Table

| Conclusion | Confidence | Basis |
|-----------|------------|-------|
| Project is a field-sales CRM/ERP | 95 | Direct code inspection, AGENTS.md, docs |
| Laravel 13 + Filament 4 + Livewire 3 stack | 100 | composer.json, package.json verified |
| Offline-first with exactly-once sync | 95 | Traced through outbox.js, sync.js, SyncService.php |
| Multi-tenancy via company_id | 100 | BelongsToCompany trait, ActiveCompanyContext, tests |
| 142 unit tests pass | 100 | Executed `vendor/bin/pest tests/Unit` — all pass |
| CI triggers on wrong branch | 95 | ci.yml:4 vs deploy.yml:5 vs git branch |
| Service layer contains all business logic | 90 | 36 services, thin controllers/components |
| ETA integration is feature-flagged off | 100 | config/eta.php:12 `enabled => false`, NullEtaClient default |
| Append-only financial ledgers | 100 | AppendOnly trait, Postgres triggers, tests |
| Bilingual Arabic/English | 100 | l() helper, lang/ files, RTL layout |

## Recommended Next Actions

1. **Fix CI branch mismatch** — Change `ci.yml` to trigger on `master` (or rename branch to `main`)
2. **Add rate limiter tests** — Critical security gap
3. **Make `make verify` include dependency audits** — Align with `scripts/verify`
4. **Expand browser test coverage** — Current 3 tests are insufficient
5. **Consider adding `route:cache`** — Remove closure routes to enable caching
6. **Verify Railway deployment status** — Confirm if Railway is the active deployment target

## Evidence Index

| Evidence | Location | Type |
|----------|----------|------|
| Tech stack versions | `composer.json`, `package.json` | Verified fact |
| Route definitions | `routes/web.php`, `routes/api.php`, `routes/rep-sync.php` | Verified fact |
| Service layer | `app/Services/` (36 files) | Verified fact |
| Sync architecture | `resources/js/offline/outbox.js`, `sync.js`, `app/Services/Sync/` | Verified fact |
| Multi-tenancy | `app/Support/ActiveCompanyContext.php`, `app/Models/Concerns/BelongsToCompany.php` | Verified fact |
| Test execution | `vendor/bin/pest tests/Unit` — 142 pass | Verified fact |
| CI branch mismatch | `.github/workflows/ci.yml:4-6` vs `deploy.yml:4-5` | Verified fact |
| Security headers | `app/Http/Middleware/SecurityHeaders.php` | Verified fact |
| ETA config | `config/eta.php`, `app/Services/Eta/` | Verified fact |
| Deployment config | `railway.toml`, `Dockerfile`, `docker/` | Verified fact |
