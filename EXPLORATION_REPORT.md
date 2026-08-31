# Project Exploration Report

## Exploration Status

- Status: Complete
- Depth: Standard
- Explored scope: Full codebase structure, key services, routes, models, tests, deployment
- Excluded scope: Individual file deep-dives on all 90 models, 63 services
- Date: 2026-08-25
- Agent: MiMoCode

## 1. Executive Summary

- **What the project does**: Bilingual (Arabic/English) field-sales CRM/ERP for Egyptian market. Field reps run daily "jawla" (visits), sell from van stock, collect cash, record returns. Admins manage master data and see everything live.
- **Primary users or consumers**: Field sales representatives (PWA), Admins/Managers (Filament panel), Finance/Purchasing/Warehouse staff
- **Architectural style**: Laravel 13 modular monolith with service layer pattern
- **Most important runtime flow**: Rep login → Visit flow → Sell → Invoice creation → Stock decrement → Payment collection → Cash reconciliation
- **Main technical constraint**: Offline-first PWA for field reps with intermittent connectivity; atomic financial transactions
- **Highest-priority risk or unknown**: Offline sync conflict resolution; Windows E2E test limitation (upstream bug)
- **Readiness for downstream work**: High — well-documented, comprehensive test suite (975+ tests), clear architecture

## 2. Project Identity and Purpose

### Verified facts

| Finding                                                  | Evidence                                                  | Confidence |
| -------------------------------------------------------- | --------------------------------------------------------- | :--------: |
| Bilingual Arabic/English field-sales CRM                 | README.md:1-3, AGENTS.md:1-3                              |    100     |
| Laravel 13 + Filament 4 + Livewire 3                     | composer.json:11-31, package.json:1-28                    |    100     |
| PostgreSQL 16 database                                   | README.md:14, Dockerfile:20                               |    100     |
| Railway deployment with Docker                           | railway.toml:1-8, Dockerfile:1-130                        |    100     |
| Multi-tenant with company_id scoping                     | AppServiceProvider.php:55, Services/InvoiceService.php:39 |    100     |
| Service layer pattern (no business logic in controllers) | AGENTS.md:42-44, Services/ directory (63 files)           |    100     |

### Inferred intent

| Inference                                | Supporting evidence                             | What remains unknown          | Confidence |
| ---------------------------------------- | ----------------------------------------------- | ----------------------------- | :--------: |
| Egyptian market focus (VAT, ZATCA)       | docs/ZATCA_NOTES.md, config/jawla.php           | Full ZATCA integration status |     85     |
| Chemical/industrial product distribution | README.md:100 (Fulla Chemical Trading)          | Specific product types        |     80     |
| Multi-role access control (11 roles)     | docs/ROLES_MATRIX.md, spatie/laravel-permission | Complete permission matrix    |     90     |

## 3. Repository and Technology Inventory

| Area              | Finding                                            | Status   | Evidence                                |
| ----------------- | -------------------------------------------------- | -------- | --------------------------------------- |
| Languages         | PHP 8.3, JavaScript (ES modules), Blade templates  | Verified | composer.json:11, package.json:3        |
| Runtime           | PHP-FPM + Nginx (Docker)                           | Verified | Dockerfile:67-130                       |
| Frameworks        | Laravel 13, Filament 4, Livewire 3, Tailwind CSS 4 | Verified | composer.json:22-24, package.json:18-21 |
| Package manager   | Composer (PHP), npm (JS)                           | Verified | composer.json, package.json             |
| Build system      | Vite 8 (frontend), Composer scripts                | Verified | package.json:8, composer.json:60-108    |
| Test system       | Pest 4 (PHP), Playwright (E2E), k6 (load)          | Verified | composer.json:41-43, tests/ directory   |
| Deployment        | Railway (Docker, 2 replicas)                       | Verified | railway.toml:1-8                        |
| Database          | PostgreSQL 16                                      | Verified | README.md:14, Dockerfile:20             |
| External services | Sentry (errors), S3 (storage), ETA (e-invoicing)   | Verified | composer.json:28, config/sentry.php     |

## 4. Architecture

### Architectural overview

Modular monolith with clear separation:

- **Presentation**: Filament admin panel (`/admin`), Livewire PWA (`/app`), REST API (`/api`)
- **Application**: 63 service classes in `app/Services/` containing all business logic
- **Data**: 90+ Eloquent models with company_id scoping for multi-tenancy
- **Infrastructure**: PostgreSQL, Redis (cache/sessions), S3 (storage), queue workers

### Important components

| Component      | Purpose                                              | Entry point                     | Dependencies                                        | Consumers                                         | Confidence |
| -------------- | ---------------------------------------------------- | ------------------------------- | --------------------------------------------------- | ------------------------------------------------- | :--------: |
| StockService   | Atomic stock movements with FEFO/batch tracking      | app/Services/StockService.php   | Stock, Batch, Warehouse models                      | InvoiceService, ReturnService, VanTransferService |    100     |
| InvoiceService | Atomic invoice creation with stock + balance updates | app/Services/InvoiceService.php | StockService, PricingService, NumberSequenceService | Livewire components, Filament resources           |    100     |
| PaymentService | Cash collection with invoice balance updates         | app/Services/PaymentService.php | InvoiceService, CashBox model                       | CollectPayment Livewire                           |    100     |
| AlarmService   | Real-time alerts for OOS, complaints, approvals      | app/Services/AlarmService.php   | Notification, Broadcast                             | Admin widgets, Rep notifications                  |     90     |
| SyncService    | Offline-first batch sync with idempotency            | app/Services/Sync/              | SyncReceipt, idempotency keys                       | Rep PWA offline queue                             |     85     |

## 5. Runtime Flows

### Flow 1 — Rep Daily Sales (Core Business Flow)

- **Trigger**: Rep logs in at `/app`, navigates to `/app/sell/{customer}`
- **Inputs**: Customer ID, product selections, quantities, batch IDs
- **Validation**: Livewire component validates → Service validates company scope, customer status, stock availability
- **Authorization boundary**: `auth`, `license`, `ensure.rep`, `ensure.device` middleware
- **Processing steps**:
  1. `SalesFlow` Livewire component receives request
  2. Delegates to `InvoiceService::create()`
  3. `InvoiceService` validates seller, customer (must be approved), products
  4. Calculates VAT via `InvoiceCalculationService`
  5. Generates sequential invoice number via `NumberSequenceService`
  6. Creates Invoice + InvoiceItems in transaction
  7. Calls `StockService::decrement()` for each item (with FEFO/batch validation)
  8. Updates customer balance
  9. Returns invoice with snapshot data
- **State changes**: Invoice created (Issued status), Stock decremented, StockMovement recorded, Customer balance increased
- **External calls**: None (all within transaction)
- **Failure handling**: Full DB::transaction rollback on any failure; InsufficientStockException thrown before commit
- **Observability**: Activity log entry, Sentry error tracking
- **Output**: Invoice response with QR code data, PDF-ready
- **Evidence**: `app/Services/InvoiceService.php:37-190`, `app/Services/StockService.php:18-141`
- **Confidence**: 95

### Flow 2 — Offline Sync

- **Trigger**: Rep reconnects after offline period; IndexedDB outbox processed
- **Inputs**: Batch of operations (visits, invoices, payments) sorted by dependency
- **Validation**: Each operation validated server-side (auth, company scope, business rules)
- **Authorization boundary**: `auth`, `license`, `ensure.rep` middleware
- **Processing steps**:
  1. `/app/sync` endpoint receives batch
  2. `SyncHandlerRegistry` routes each operation type
  3. Each operation processed in individual transaction
  4. Idempotency checked via `(company_id, idempotency_key)` constraint
  5. Successful operations recorded in `sync_receipts` table
  6. Failed operations retained with error status
- **State changes**: Operations applied sequentially, receipts recorded
- **External calls**: None
- **Failure handling**: Individual operation failures don't block batch; failed ops flagged for retry
- **Observability**: SyncReceipt records with payload hash, protocol version
- **Output**: Batch response with success/failure per operation
- **Evidence**: `routes/rep-sync.php`, `app/Services/Sync/`
- **Confidence**: 85

## 6. Domain Model and Glossary

### Core concepts

| Concept       | Meaning in this project                   | Key rules                                                      | Evidence                     |
| ------------- | ----------------------------------------- | -------------------------------------------------------------- | ---------------------------- |
| Jawla (جولة)  | Daily field visit cycle for a rep         | Must check in, visit assigned customers, return stock/payments | README.md:3                  |
| Van Warehouse | Mobile stock location assigned to a rep   | Type='van', linked to user_id, active only                     | InvoiceService.php:390-412   |
| Batch         | Product lot with expiry tracking          | FEFO enforcement, cross-company validation                     | StockService.php:143-182     |
| Proforma      | Pre-invoice quotation with bank details   | Can be converted to Invoice, has QR + PDF                      | Livewire/QuotationFlow.php   |
| Alarm         | Real-time alert for critical events       | OOS → Finance+Manager+Executive; Complaint → Manager           | AlarmService.php             |
| SyncReceipt   | Idempotency record for offline operations | Unique (company_id, idempotency_key)                           | migrations/2026_07_20_210000 |

### State transitions

| Entity   | From     | Event          | To            | Guard or side effect                        | Evidence                   |
| -------- | -------- | -------------- | ------------- | ------------------------------------------- | -------------------------- |
| Invoice  | Draft    | submit()       | Issued        | Only seller can submit                      | InvoiceService.php:192-231 |
| Invoice  | Issued   | cancel()       | Voided        | Manager only, same-day, no payments/returns | InvoiceService.php:233-278 |
| Customer | Pending  | Admin approval | Approved      | Required before invoicing                   | InvoiceService.php:77-90   |
| Stock    | Positive | decrement()    | Zero/Positive | Throws InsufficientStockException if < 0    | StockService.php:120-125   |

## 7. Data, Storage, and Interfaces

| Interface or store | Producer                  | Consumer               | Contract                    | Auth          | Failure behavior                | Evidence               |
| ------------------ | ------------------------- | ---------------------- | --------------------------- | ------------- | ------------------------------- | ---------------------- |
| PostgreSQL         | All services              | All queries            | Eloquent ORM, parameterized | Company scope | Transaction rollback            | config/database.php    |
| Redis              | Session, cache            | App, queue             | Laravel cache driver        | N/A           | Falls back to file cache        | config/cache.php       |
| S3                 | PdfService, PhotoService  | Signed URLs            | League Flysystem            | IAM keys      | Exception logged, user notified | config/filesystems.php |
| Sentry             | Laravel exception handler | Sentry dashboard       | Sentry SDK                  | DSN           | Silent fail, logged locally     | config/sentry.php      |
| ETA API            | InvoiceService            | Egyptian Tax Authority | OAuth + JSON                | Certificate   | NullEtaClient fallback          | Services/Eta/          |

### Sensitive data

- Customer PII (names, phones, addresses) — encrypted at rest via PostgreSQL
- Financial data (invoices, payments, balances) — transactional integrity enforced
- User credentials — argon2id hashing, never stored in plaintext
- API tokens — Sanctum with 24h expiration
- Secrets — only in `.env`, never in code or logs

## 8. Development Workflow

### Prerequisites

- PHP 8.3 with extensions (pdo_pgsql, gd, mbstring, zip, bcmath, intl, opcache)
- Node.js 22+ with npm
- PostgreSQL 16
- Composer 2

### Commands

| Command          | Purpose                                             | Status   | Evidence       |
| ---------------- | --------------------------------------------------- | -------- | -------------- |
| `make setup`     | Full project setup                                  | Declared | Makefile:3-9   |
| `make dev`       | Start dev servers (PHP, queue, logs, Vite)          | Declared | Makefile:11-17 |
| `make lint`      | Run Pint (PHP CS Fixer)                             | Declared | Makefile:19-20 |
| `make typecheck` | Run PHPStan level 0                                 | Declared | Makefile:22-23 |
| `make test`      | Run Pest (Unit + Feature)                           | Declared | Makefile:30-31 |
| `make test-e2e`  | Run Playwright browser tests                        | Declared | Makefile:39-45 |
| `make verify`    | Full verification (lint + typecheck + test + build) | Declared | Makefile:66    |
| `make build`     | Build Vite assets                                   | Declared | Makefile:50-51 |
| `make migrate`   | Run migrations                                      | Declared | Makefile:53-54 |
| `make seed`      | Seed demo data                                      | Declared | Makefile:56-57 |

### CI/CD

- GitHub Actions: lint → test → security scan → build → deploy to staging → ZAP DAST → approval → production
- Railway: Docker build, 2 replicas, health check at `/health`

## 9. Quality and Risk Findings

| Priority | Finding                                    | Severity | Likelihood | Blast radius         | Evidence             | Confidence | Next action                                  |
| -------: | ------------------------------------------ | -------- | ---------- | -------------------- | -------------------- | :--------: | -------------------------------------------- |
|        1 | Windows E2E test limitation (upstream bug) | Medium   | High       | E2E coverage gap     | AGENTS.md:88-107     |     95     | Use CI for E2E, local for unit/feature       |
|        2 | 151 migrations (potential drift)           | Medium   | Medium     | Schema complexity    | database/migrations/ |     80     | Review migration history, consider squashing |
|        3 | 90 models (complexity)                     | Low      | Low        | Maintenance overhead | app/Models/          |     70     | Monitor for unused models                    |
|        4 | Offline sync conflict resolution           | High     | Medium     | Data consistency     | Services/Sync/       |     75     | Test concurrent offline operations           |

## 10. Contradictions

| Topic            | Source A                    | Source B                                     | Current interpretation              | Resolution needed |
| ---------------- | --------------------------- | -------------------------------------------- | ----------------------------------- | ----------------- |
| Filament version | README.md:13 ("Filament 4") | composer.json:22 ("filament/filament: ^4.0") | Consistent — Filament 4             | None              |
| Test count       | README.md:66 ("975 tests")  | tests/ directory structure                   | Likely accurate based on file count | None              |

## 11. Unknowns and Blockers

| ID    | Question                                                    | Why it matters                              | Evidence checked         | Safest resolution                     | Blocks |
| ----- | ----------------------------------------------------------- | ------------------------------------------- | ------------------------ | ------------------------------------- | ------ |
| Q-001 | What is the actual production deployment status?            | Determines if changes can affect live users | railway.toml, Dockerfile | Check Railway dashboard               | None   |
| Q-002 | How many active users does the system have?                 | Determines load testing requirements        | None                     | Ask client/team                       | None   |
| Q-003 | What is the current offline sync reliability in production? | Critical for field rep operations           | Services/Sync/, tests    | Review sync receipts in production DB | None   |

## 12. Confidence Summary

| Conclusion                                 | Score | Basis                                            | Why not higher                          |
| ------------------------------------------ | :---: | ------------------------------------------------ | --------------------------------------- |
| Project is a bilingual field-sales CRM     |  100  | Direct code + documentation evidence             | —                                       |
| Laravel 13 + Filament 4 + Livewire 3 stack |  100  | composer.json + package.json                     | —                                       |
| Service layer pattern enforced             |  95   | AGENTS.md rules + Services/ structure            | Some edge cases may exist               |
| Atomic financial transactions              |  95   | InvoiceService, StockService use DB::transaction | Not all services verified               |
| Offline-first PWA design                   |  85   | Sync routes, IndexedDB references in docs        | Full offline flow not traced end-to-end |
| Railway production deployment              |  90   | railway.toml, Dockerfile                         | No direct access to verify live status  |

## 13. Recommended Next Actions

1. **Immediate safe next action**: Run `make verify` to confirm full test suite passes
2. **Evidence or validation needed**: Check Railway dashboard for production status and recent deployments
3. **Suggested first implementation boundary**: Start with service layer changes (app/Services/) — well-isolated, testable, clear boundaries

## 14. Evidence Index

| Topic            | Evidence references                                                                 |
| ---------------- | ----------------------------------------------------------------------------------- |
| Project identity | README.md:1-100, CLAUDE.md:1-67, AGENTS.md:1-133                                    |
| Tech stack       | composer.json:1-129, package.json:1-28                                              |
| Architecture     | docs/ARCHITECTURE.md:1-596, AppServiceProvider.php:1-206                            |
| Business rules   | docs/BUSINESS_RULES.md:1-23, CLAUDE.md:21-55                                        |
| Security         | docs/SECURITY.md:1-124, config/jawla.php:1-29                                       |
| Core services    | Services/StockService.php, Services/InvoiceService.php, Services/PaymentService.php |
| Routes           | routes/web.php:1-136, routes/rep-sync.php, routes/rep-offline.php                   |
| Tests            | tests/Feature/ (84 files), tests/Unit/, tests/Browser/                              |
| Deployment       | railway.toml:1-8, Dockerfile:1-130, docs/DEPLOYMENT.md                              |
| Database         | database/migrations/ (151 files), app/Models/ (90 files)                            |
