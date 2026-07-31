# Worklog — Jawla (جولة) Bilingual Field-Sales CRM/ERP

## Project Overview

- **Stack:** Laravel 13 + Filament 4 + Livewire 3 + PostgreSQL 16
- **Runtime:** PHP-FPM behind Nginx, deployed on Railway
- **Purpose:** Bilingual (AR/EN, RTL/LTR) field-sales CRM with PWA rep app
- **Repository:** `C:\projects\jawla`

---

## Git Statistics

| Metric                  | Value                                                                          |
| ----------------------- | ------------------------------------------------------------------------------ |
| **Total commits**       | 321                                                                            |
| **Contributors**        | ahmedibm9-cyber, dependabot[bot], v0, Railway Agent                            |
| **Time span**           | 2026-07-12 → 2026-07-31 (19 days)                                              |
| **Branches (local)**    | master, feat/unified-login, recovery/beta-checkpoint-pre-r1                    |
| **Branches (remote)**   | 13 total incl. dependabot + feature branches                                   |
| **Tags**                | (none)                                                                         |
| **Files tracked**       | ~986                                                                           |
| **Test files**          | 181                                                                            |
| **Models**              | 69                                                                             |
| **Migrations**          | 130                                                                            |
| **Services**            | 66 (incl. contracts, ETA, Sync subdirectories)                                 |
| **Filament Resources**  | 24 (with page classes)                                                         |
| **Livewire components** | 24                                                                             |
| **Policies**            | 25                                                                             |
| **Commit types**        | fix: 120, feat: 83, docs: 16, build: 9, test: 8, perf: 7, chore: 6, others: 19 |

---

## Phase-by-Phase Work History

### Phase 0 — Foundation (`42abe9a`, `966d14f`)

**July 12, 2026**

- Initial Laravel 13 scaffold with Filament 4 + Livewire 3
- PostgreSQL 16 configuration
- Security headers middleware (CSP, HSTS, XSS protection)
- Argon2id password hashing
- Timezone, locale, test database configuration
- **Commits:** `373e584` (Phase 1 — DB & models), `42abe9a` (Phase 2 — auth & roles), `966d14f` (Phase 0 gaps)

### Phase 1 — Database & Architecture (`47e20e9` → `564cb08`)

**July 12-13, 2026**

- **1a:** Architecture foundation — BelongsToCompany trait, ActiveCompanyContext middleware, domain exceptions, value objects (Money, GpsCoordinate, PriceRange), service contracts, bilingual error pages
- **1b:** Complete database schema — 56 migration files, 46+ tables with FKs, CHECK constraints, decimal(12,3) quantities, partial indexes
- **1c:** 59 models, factories, seeders, RoleSeeder (7 roles, ~50 permissions), StockService tests, CompanyIsolationTest
- **Build guide docs:** JAWLA_Build_Guide_Certification.md, FinalSupplement, Review, StressTest, Repository_Audit (~5,700 lines of spec)

### Phase 2 — Auth & Roles (`f6bfdce`)

**July 13, 2026**

- Admin login (Filament `/admin`), rep login (`/app`) with rate limiting (5/min)
- 7 roles: Super Admin, Admin, Sales Manager, Accounts, Executive, Purchasing, Rep
- ~50 permissions in dot notation
- Locale switching (AR↔EN, RTL/LTR)
- Security headers, argon2id hashing
- Custom bilingual 403/404/419/500 error pages

### Phase 3 — Admin Panel B2 (`5d6a098`)

**July 13, 2026**

- 12 Filament resources: Company, User, Product, Customer, Route
- Policy classes for all resources
- GPS map picker component (LeafletMapPicker)
- Stock import feature (Excel/CSV via Maatwebsite)
- **Commits:** `5d6a098`, `a73d8e1`, `c59e37d`, `7a48dff`, `5e36852`

### Phase 4 — Rep PWA B3-B4 (`a73d8e1`, `c59e37d`)

**July 13, 2026**

- Rep visit flow with GPS tracking, stepper UI, signature capture, customer search
- Quotation chain with floor price enforcement
- Invoice service, alarm broadcast, purchase requests
- Demo seeder with realistic test data
- 9 Livewire components for rep operations

### Phase 5 — Beta Phase R (`1698e71` → `a2b956b`)

**July 13-15, 2026**

- **R2:** Bottom tabs (stock, more), maps deep-link, skeleton/toast CSS, language keys
- **R3:** Complaint cycle, customer creation, alarm broadcast, offline drafts
- **R4:** PDF+QR generation (ZATCA), payments service, dashboards (SalesToday, VisitsToday, OpenAlarms, PendingQuotations), reports
- **R5:** Purchase offers, AM1-AM9 E2E smoke test, README, AR/EN audit
- Render.com deploy attempt, then switch to Railway
- **Commits:** `a4f6b40`, `53be50f`, `27b8244`, `1698e71`, `a2b956b`

### Phase 6 — Railway Deployment (`473b7c2` → `ecc5a53`)

**July 15-16, 2026**

- Nixpacks build configuration → Docker nginx + php-fpm runtime
- Railway object storage (B2 — signed URLs for photos)
- Redis migration and web service scaling
- Performance optimization (SPA mode, file cache, K6 stress testing)
- **Commits:** 15+ commits across deployment fixes

### Phase 7 — v1.0 Feature Completion (`ecc5a53` → `650c8dc`)

**July 16-17, 2026**

- ETA e-invoicing module (ZATCA Phase 2 compliance)
- Batch/COA/expiry management
- Goods-in-transit receive + landed-cost allocation
- Customer map visualization
- Sales targets & attainment (CG5)
- Van transfer rep page (receive into van)
- Supplier comparison page (PUR-4)
- Cash reconciliation (rep count + manager review)
- **Commits:** `ecc5a53`, `650c8dc`, `9e30a30`, `01afce9`, `daf9287`, `e2af5aa`

### Phase 8 — CG Features (Connectivity Gap) (`db1f1c1` → `b1bbd6c`)

**July 16-17, 2026**

- **CG1:** Bluetooth field printing for invoices and receipts
- **CG2:** Offline-sync foundation — exactly-once ingest, client outbox + sync, offline handlers for all rep writes (payments, expenses, returns, complaints, visit reports, sales), sync-queue viewer + header badge
- **CG3:** Live rep tracking (on-shift pings + manager live map)
- **CG4:** Public API v1 foundation (Sanctum scoped tokens), admin API token management
- **CG5:** Sales targets & attainment
- **CG6:** Photo capture component (reusable)
- **CG7:** Barcode scan → product lookup in Sales Flow

### Phase 9 — Security & Hardening (`6d2e313` → `a701460`)

**July 16-17, 2026**

- Security audit — authorization checks, ZATCA secret hiding, session secure defaults
- CSP hardening, COOP/COEP/CORP headers
- Rate limiting on PDF generation (10/min per IP)
- Input validation across all form endpoints (13 findings)
- Sentry error tracking (backend + frontend) with PII scrubbing
- Architecture fixes — race conditions, negative balances, predictability
- WebP images, token consolidation, woff2 cleanup
- **Commits:** `6d2e313`, `a701460`, `4b79990`, `e217330`, `9ea8a85`, `36ec5ac`

### Phase 10 — UI/UX Audit & Overhaul (`63cee81` → `add0c9c`)

**July 17-18, 2026**

- **Session 3 UI/UX Overhaul:** Tab bar extraction (9→1 components), safe area support (iOS), CSS consolidation, gradient hero, icon badges, form input system, success screens, brand logo
- **Session 4 Gap Coverage:** Confirmation modals on 4 pages, translation keys (AR/EN), stress testing
- 20 UI/UX issues fixed from notes.txt
- WCAG 2.1 AA compliance — 180+ accessibility improvements (aria-*, focus-visible, skip link, reduced-motion)
- Web interface guidelines compliance
- **Commits:** `63cee81`, `add0c9c`, `c37df2f`, `7de47a8`

### Phase 11 — Test Hardening & Bug Fixes (`f5b90f3` → `ec392e7`)

**July 18-19, 2026**

- 15 previously untested user stories now covered
- InvoiceService van warehouse optional for non-rep users
- SeedSuperAdmin creates super_admin role if missing
- Redirect loop fix on login
- Rate limiter, role seeder, browser test fixes
- Session conflict fix — redirect rep-only users to /app
- Unified /login endpoint per LOGIN.1 story
- Parallel test failure resolution
- **Commits:** `f5b90f3`, `ad9c00f`, `add0c9c`, `88490d1`, `ec392e7`, `0cecb3c`

### Phase 12 — Architecture Deepening (`feat/unified-login` branch)

**July 17, 2026**

- **Ticket #1:** NumberSequenceService — sequential + gapless document numbers with FOR UPDATE row lock
- **Ticket #2:** InvoiceCalculationService — shared calculation seam with DTOs, per-line VAT
- **Ticket #3:** QuotationFlow deepened via service seam + DB::transaction()
- **Ticket #4:** 7 missing Policy classes for Filament resources (16 tests, 62 assertions)
- **Commits:** 10 commits merged into master

### Phase 13 — Recent Fixes & Polish (`d0cb856` → HEAD)

**July 24, 2026**

- Translation helper refactoring — replaced hardcoded English strings with `__()`/`l()` helpers across views and lang files
- AlarmResource getNavigationLabel fix for mixed Arabic/English text
- /admin/expenses table made searchable
- DemoSeeder idempotency fixes for Railway redeploys
- Rep PWA responsive design polish for tablet/laptop/desktop
- Service worker navigation fix — cached pages offline before fallback
- **Latest commit:** `47b4f8c` — "fix: add getNavigationLabel to AlarmResource to prevent mixed Arabic/English text"

---

## Codebase Architecture Summary

### Application Layer

| Layer                   | Count | Details                                                                   |
| ----------------------- | ----- | ------------------------------------------------------------------------- |
| **Models**              | 69    | Eloquent models with BelongsToCompany trait, Fillable/casts/relationships |
| **Services**            | 66    | Including 11 contracts, ETA submodule (6), Sync submodule (9)             |
| **Filament Resources**  | 24    | Admin CRUD resources with page classes                                    |
| **Livewire Components** | 24    | Rep PWA interactive components                                            |
| **Policies**            | 25    | Authorization policies for Filament resources                             |
| **Migrations**          | 130   | Schema migrations including guide columns                                 |
| **Value Objects**       | 4     | Money, GpsCoordinate, PriceRange, Bilingual                               |
| **Domain Exceptions**   | 10    | Domain-specific exception classes                                         |

### Key Service Architecture

- **StockService** — All stock mutations go through this, always writes stock_movements
- **InvoiceService** — Invoice creation with stock decrement, wrapped in DB::transaction()
- **NumberSequenceService** — Sequential + gapless document numbers with FOR UPDATE lock
- **InvoiceCalculationService** — Per-line VAT calculation with DTOs
- **PaymentService** — Payment processing with balance guards
- **SyncService** — Offline sync with exactly-once ingest, HMAC-authenticated identity partition
- **ETA Services** — ZATCA Phase 2 e-invoicing with HTTP transport + signer seam
- **PdfService** — PDF generation with ZATCA QR codes
- **PhotoService** — Signed URL generation for private object storage

### Frontend Architecture

- **Admin Panel:** Filament 4 PHP resources with 23+ resources
- **Rep PWA:** Livewire 3 + Blade with offline support via service worker
- **Offline Sync:** IndexedDB outbox → sync endpoint with idempotency receipts
- **Design System:** Tokens, typography (IBM Plex Sans Arabic), brand color #3d7a18
- **Accessibility:** WCAG 2.1 AA compliance, RTL/LTR support

### Infrastructure

- **Deployment:** Railway (Docker nginx + php-fpm)
- **Database:** PostgreSQL 16
- **Cache/Session:** Database driver (Redis prepared)
- **Object Storage:** S3-compatible (Railway buckets)
- **Error Tracking:** Sentry with PII scrubbing
- **Backup:** Encrypted to S3-compatible target
- **CI:** GitHub Actions (CI + Security)

---

## Key Technical Decisions

| Decision                                        | Rationale                                                  |
| ----------------------------------------------- | ---------------------------------------------------------- |
| Monolithic Laravel app (not split)              | Single codebase, one server, one PG DB for B2B field sales |
| Database session/cache driver                   | Upgrade to Redis only if metrics require                   |
| DB::transaction() for all money/stock mutations | Prevents partial state on any failure                      |
| StockService as single entry point              | Every stock change writes stock_movements row              |
| NumberSequenceService with FOR UPDATE lock      | Guarantees gapless document numbering                      |
| Offline via IndexedDB + sync endpoint           | No P2P; server is authoritative for all writes             |
| HMAC-authenticated identity partition           | Prevents cross-rep data leakage in offline outbox          |
| Nixpacks → Docker nginx + php-fpm               | Railway build compatibility                                |
| WebP images + woff2 fonts                       | Performance optimization                                   |
| CSP with strict directives                      | Security hardening                                         |

---

## Test Coverage

| Test Suite                    | Count   |
| ----------------------------- | ------- |
| Auth (Admin/Rep/Login/Locale) | 14      |
| Roles                         | 3       |
| Tenancy                       | 2       |
| StockService                  | 5+      |
| InvoiceFlow                   | 4       |
| AlarmBroadcast                | 3       |
| AM1→AM9 E2E                   | 1       |
| NumberSequenceService         | 6       |
| InvoiceCalculationService     | 7       |
| Policies (7 resources)        | 16      |
| Additional feature tests      | ~18     |
| **Total test files**          | **181** |
