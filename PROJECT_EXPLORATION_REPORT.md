# PROJECT EXPLORATION REPORT

## Executive Summary

Jawla (جولة) is a bilingual (Arabic/English) field-sales CRM/ERP application built for the Egyptian market. It manages sales representatives' daily field operations: check-in, route planning, customer visits with GPS tracking, van stock sales, cash collection, and returns. The system supports both admin (Filament) and rep (Livewire PWA) interfaces.

**Primary Purpose:** Field sales management and ERP for Egyptian market operations
**Confidence:** 95/100 (direct evidence from AGENTS.md, code structure, and business rules)

## Project Identity and Purpose

- **Name:** Jawla (جولة) - Arabic for "tour" or "journey"
- **Target Users:** Field sales representatives (reps) and administrators
- **Core Workflow:** Reps run daily "jawla" - check in, pick route, visit customers, sell from van stock, collect cash, record returns
- **Admin Functions:** Master data management, real-time monitoring, approvals
- **Market:** Egyptian market with VAT/e-invoicing support (ETA/ZATCA)

## Verified Technology Stack

| Component      | Technology                             | Evidence                                              |
| -------------- | -------------------------------------- | ----------------------------------------------------- |
| Backend        | Laravel 13, PHP 8.3+                   | `composer.json:11-12`                                 |
| Admin Panel    | Filament 4                             | `composer.json:22`, `AdminPanelProvider.php`          |
| Rep PWA        | Livewire 3 + Tailwind CSS              | `package.json:21-22`, `resources/views/livewire/app/` |
| Database       | PostgreSQL (primary), SQLite (testing) | `composer.json:16-18`, `AGENTS.md:44`                 |
| Frontend Build | Vite 8                                 | `package.json:22`                                     |
| Testing        | Pest 4, Playwright                     | `composer.json:41-43`                                 |
| Auth           | Laravel Sanctum                        | `composer.json:23`                                    |
| Permissions    | Spatie Permission                      | `composer.json:30`                                    |
| PDF Generation | mPDF                                   | `composer.json:27`                                    |
| QR Codes       | SimpleSoftwareIO QR Code               | `composer.json:29`                                    |
| Excel Import   | Spatie Simple Excel                    | `composer.json:31`                                    |
| Error Tracking | Sentry                                 | `composer.json:28`                                    |
| Mapping        | Leaflet                                | `package.json:26`                                     |

## Architecture and Runtime Flows

### High-Level Architecture

- **Monolithic Laravel app** with single PostgreSQL database
- **Dual interface pattern:** Admin panel (`/admin`) and Rep PWA (`/app`)
- **Service layer pattern:** Business logic in `app/Services/`, controllers delegate
- **Multi-tenant:** Company-scoped via `company_id` columns

### Key Runtime Flow: Rep Sales Process

1. **Login** → `/login` (unified for reps and admins)
2. **Start Day** → Work session with GPS location
3. **Route Selection** → Daily visit assignments
4. **Customer Visit** → GPS check-in, geofence validation
5. **Create Invoice** → `SalesFlow.php` → `InvoiceService::create()` with stock decrement
6. **Collect Payment** → `PaymentService::collect()` with cash box update
7. **Record Returns** → Stock increment with movement tracking
8. **Cash Reconciliation** → End-of-day cash box balancing

### Service Layer Pattern

All business logic lives in `app/Services/`:

- `InvoiceService.php` - Invoice creation with stock/financial transactions
- `StockService.php` - Stock movements with audit trail
- `PaymentService.php` - Payment collection with idempotency
- `VanTransferService.php` - Van stock transfers
- `ReturnService.php` - Return processing

**Key Rule:** Money mutations happen inside `DB::transaction()` via services, never directly from controllers.

## Domain Model and Glossary

### Core Entities

- **Company** - Multi-tenant organization
- **User** - Rep or admin with roles/permissions
- **Customer** - B2B customer with approval workflow
- **Product** - Items sold from van stock
- **Invoice** - Sales transaction with ETA/ZATCA e-invoicing
- **Payment** - Cash collection with idempotency
- **Visit** - Customer visit with GPS tracking
- **WorkSession** - Daily work period
- **Route** - Sales route with customer assignments
- **Stock** - Van inventory with movement audit trail
- **CashBox** - Rep's cash balance

### Key Business Rules

1. **No negative van stock** - Rejected at `StockService::decrement()`
2. **Atomic sales** - Invoice + items + stock + movements in one transaction
3. **Sequential numbers** - Per-company, server-generated, immutable
4. **Route lock** - Reps can only visit customers on active route
5. **Reversal is compensating, never deletion** - Audit trail preserved

### State Machines

- **InvoiceStatus:** Draft → Issued → Submitted → PartiallyPaid → Paid → Credited/Cancelled/Amended
- **VisitStatus:** Open → Closed
- **Customer status:** pending → approved/rejected

## Data Stores and Interfaces

### Primary Data Store

- **PostgreSQL** - Single database with company-scoped tables
- **Key tables:** companies, users, customers, products, invoices, payments, stocks, visits

### External Integrations

- **ETA (Egypt Tax Authority)** - E-invoicing via `HttpEtaClient`
- **ZATCA** - Saudi Arabia e-invoicing support
- **Sentry** - Error tracking
- **Push notifications** - Via `HttpPushGateway`

### API Surface

- **Rep PWA routes** - `/app/*` with auth + license + device middleware
- **Admin API** - Filament auto-registered
- **Public API v1** - Sanctum-protected endpoints

## Development and Operations Workflow

### Setup Commands

| Command          | Purpose              | Status   |
| ---------------- | -------------------- | -------- |
| `make setup`     | Initial setup        | Declared |
| `make dev`       | Start dev server     | Declared |
| `make lint`      | PHP linting          | Declared |
| `make typecheck` | PHPStan analysis     | Declared |
| `make test`      | Unit + Feature tests | Declared |
| `make test-e2e`  | Browser tests        | Declared |
| `make verify`    | Full verification    | Declared |
| `make build`     | Build assets         | Declared |
| `make migrate`   | Database migration   | Declared |
| `make seed`      | Seed demo data       | Declared |

### Quality Gates

- `make verify` = lint + typecheck + test + test-offline + build
- Browser tests have Windows limitation (pest-plugin-browser bug)
- PHPStan level 0 for CI, level 6 for strict audit

## Quality and Risk Findings

### High Confidence Items

- **Service layer pattern** - Consistent across all business logic
- **Transaction safety** - Money mutations wrapped in DB::transaction
- **Stock audit trail** - Every movement creates stock_movements row
- **Multi-tenant isolation** - Company-scoped via BelongsToCompany trait
- **Bilingual support** - RTL Arabic + LTR English throughout

### Medium Confidence Items

- **E-invoicing integration** - ETA client built but may need production testing
- **Offline sync** - PWA with IndexedDB, sync handlers exist
- **Performance testing** - k6 tests exist but not verified

### Unknowns

- **Production deployment status** - Railway config exists but unverified
- **Real customer data** - Never committed (per security rules)
- **ETA production readiness** - Unsigned signer until certificate provisioned

## Contradictions and Unknowns

### Verified Facts

- All business logic in services layer (evidence: `app/Services/` structure)
- Stock changes only through StockService (evidence: `StockService.php:18-26`)
- Money mutations in DB::transaction (evidence: `InvoiceService.php:40`, `PaymentService.php:40`)
- Company-scoped multi-tenancy (evidence: `BelongsToCompany` trait usage)

### Unknowns

1. **Production deployment target** - Railway config exists but no live verification
2. **ETA production certificate** - Noted as "last go-live gate" in `AppServiceProvider.php:87`
3. **Offline sync reliability** - Handlers exist but no end-to-end test verification
4. **Performance under load** - k6 tests exist but results not reviewed

## Confidence Table

| Area                    | Confidence | Evidence Quality                        |
| ----------------------- | ---------- | --------------------------------------- |
| Architecture pattern    | 95/100     | Direct code inspection                  |
| Business rules          | 95/100     | AGENTS.md + BUSINESS_RULES.md           |
| Service layer pattern   | 95/100     | Multiple service files inspected        |
| Transaction safety      | 95/100     | DB::transaction usage in key services   |
| Multi-tenancy           | 95/100     | BelongsToCompany trait usage            |
| E-invoicing integration | 75/100     | Client built, but unsigned signer       |
| Offline sync            | 70/100     | Handlers exist, not end-to-end verified |
| Production readiness    | 60/100     | Config exists, deployment not verified  |

## Recommended Next Actions

1. **Verify production deployment** - Test Railway deployment end-to-end
2. **Test ETA integration** - Verify e-invoicing with test certificates
3. **Validate offline sync** - Test rep PWA offline → online workflow
4. **Performance testing** - Review k6 test results and optimize
5. **Security audit** - Verify all middleware and rate limiting in production

## Evidence Index

- `AGENTS.md` - Project purpose and architecture rules
- `composer.json` - Technology stack
- `app/Services/InvoiceService.php` - Invoice creation with transactions
- `app/Services/StockService.php` - Stock movement pattern
- `app/Services/PaymentService.php` - Payment collection with idempotency
- `app/Models/Invoice.php` - Invoice entity structure
- `app/Models/Customer.php` - Customer entity with approval workflow
- `app/Models/Visit.php` - Visit with GPS tracking
- `app/Providers/AppServiceProvider.php` - Service bindings and middleware
- `app/Providers/Filament/AdminPanelProvider.php` - Admin panel configuration
- `database/migrations/` - Schema evolution (140 migrations)
- `docs/BUSINESS_RULES.md` - Non-negotiable business rules
