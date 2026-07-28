# Jawla — Project Map

## Directory and Module Map

```
jawla/
├── app/                          # Laravel application code
│   ├── Console/Commands/         # 3 artisan commands (BootstrapProduction, PurgeLocationPings, SeedTransactions)
│   ├── Enums/                    # 5 enums (InvoiceStatus, StockReason, VanTransferStatus, VisitPurpose, VisitStatus)
│   ├── Exceptions/Domain/        # 3 domain exceptions (DomainException, GeofenceViolation, InsufficientStock)
│   ├── Filament/                 # Admin panel
│   │   ├── Auth/                 # Custom login + response
│   │   ├── Forms/                # Custom form components
│   │   ├── Pages/                # 11 admin pages
│   │   ├── Resources/            # 24 Filament resources
│   │   └── Widgets/              # 7 dashboard widgets
│   ├── Http/
│   │   ├── Controllers/          # SystemPage, CompanyContext, App (Login, Pdf, Sync), Api/V1
│   │   ├── Middleware/           # 7 middleware (SecurityHeaders, SetActiveCompanyContext, etc.)
│   │   ├── Requests/            # Form request validation
│   │   └── Resources/           # API resources
│   ├── Livewire/App/            # 24 Livewire components (rep PWA)
│   ├── Models/                  # 65+ Eloquent models
│   │   └── Concerns/            # AppendOnly, BelongsToCompany traits
│   ├── Notifications/           # 6 notification classes
│   ├── Observers/               # AuditObserver (User, PriceQuotation)
│   ├── Policies/                # 25 policy classes
│   ├── Providers/               # 4 service providers
│   ├── Services/                # 36 service classes
│   │   ├── Contracts/           # 11 service interfaces
│   │   ├── Eta/                 # 8 ETA e-invoicing files
│   │   └── Sync/                # 4 sync system files + handlers
│   └── Support/                 # 7 support classes (ActiveCompanyContext, GpsCoordinate, Money, etc.)
├── bootstrap/                    # Laravel bootstrap (app.php, providers.php)
├── config/                       # 19 config files
├── database/
│   ├── migrations/              # 127 migration files
│   ├── seeders/                 # 4 seeders (Database, Demo, PerfUser, Role)
│   └── factories/               # 21 model factories
├── docker/                       # Nginx config + container startup
├── docs/                         # 50+ documentation files
│   ├── production-readiness/     # 31 audit/remediation files
│   └── adr/                     # 2 architecture decision records
├── lang/                         # Arabic + English translations
├── public/                       # Web root (compiled assets, service worker, PWA manifest)
├── resources/
│   ├── js/                       # Offline sync JS (outbox.js, sync.js, status-indicator.js)
│   ├── css/                      # app.css (Tailwind)
│   └── views/                    # Blade templates (layouts, components, livewire)
├── routes/                       # 4 route files (web, api, rep-sync, console)
├── scripts/                      # Deploy, backup, restore, verify scripts
├── tests/
│   ├── Unit/                     # 20 test files (17 service tests)
│   ├── Feature/                  # 80 test files (12 subdirectories)
│   ├── Browser/                  # 3 Playwright test files
│   ├── e2e/                      # 1 Playwright E2E test
│   ├── k6/                       # 2 k6 load test scripts
│   └── stress/                   # 15 stress test scripts + results
├── .github/workflows/            # 4 CI/CD workflows
├── Makefile                      # 14 build targets
├── composer.json                 # PHP dependencies
├── package.json                  # JS dependencies
├── phpstan.neon                  # Static analysis config (level 6)
├── pint.json                     # Code style config (laravel preset)
├── phpunit.xml                   # Test configuration
├── railway.toml                  # Railway deployment config
├── Dockerfile                    # Alpine PHP-FPM + Nginx
└── vite.config.js                # Vite build config
```

## Entry Points

### HTTP Entry Points

| Route | Handler | Middleware | Purpose |
|-------|---------|------------|---------|
| `GET /` | `SystemPageController::root` | web | Root redirect |
| `GET /login` | Filament Login | web | Unified login |
| `GET /admin` | Filament AdminPanel | web, auth | Admin dashboard |
| `GET /app` | Livewire Home | web, auth, ensure.rep | Rep dashboard |
| `POST /app/sync` | `SyncController::store` | web, auth, ensure.rep, throttle | Offline sync |
| `GET /health` | `SystemPageController::health` | web | Health check |
| `GET /api/v1/*` | API controllers | auth:sanctum | Public API |

### CLI Entry Points

| Command | File | Purpose |
|---------|------|---------|
| `app:purge-location-pings` | `app/Console/Commands/PurgeLocationPings.php` | Daily GPS data purge |
| `app:bootstrap-production` | `app/Console/Commands/BootstrapProduction.php` | Production setup |
| `db:seed-transactions` | `app/Console/Commands/SeedTransactionsCommand.php` | Demo data seeding |

### Scheduled Tasks

| Schedule | Command | Evidence |
|----------|---------|----------|
| Daily | `app:purge-location-pings` | `bootstrap/app.php:40` |

## Component Relationships

```
                    ┌─────────────┐
                    │   Company   │ (tenant boundary)
                    └──────┬──────┘
                           │ hasMany
         ┌─────────────────┼─────────────────┐
         │                 │                 │
    ┌────▼────┐     ┌──────▼──────┐    ┌────▼────┐
    │  User   │     │  Customer   │    │ Product │
    │(rep/    │     └──────┬──────┘    └────┬────┘
    │ admin)  │            │                │
    └────┬────┘       hasMany│          hasMany│
         │                   │                │
    hasMany│            ┌────▼────┐    ┌──────▼──────┐
         │             │ Visit   │    │    Stock     │
    ┌────▼────┐        └────┬────┘    │(per warehouse)│
    │ Invoice │             │         └──────────────┘
    └────┬────┘        hasOne│
         │             ┌────▼──────┐
    hasMany│        │VisitReport │
    ┌──────▼──────┐  └───────────┘
    │ InvoiceItem │
    └─────────────┘
         │
    belongsTo│
    ┌───────▼───────┐
    │ Payment       │ (allocated to invoices)
    │ ReturnRecord  │ (restores stock)
    │ Expense       │ (deducts from cash box)
    └───────────────┘
```

## Data Flow Diagram

### Online Sale Flow

```
User → SalesFlow (Livewire) → PricingService → InvoiceCalculationService
  → InvoiceService::create() → [DB::transaction]
    → Invoice::create()
    → InvoiceItem::create() (foreach)
    → StockService::decrement()
    → StockMovement::create()
    → Customer balance update
  → ThermalPrintFormatter → Print payload
```

### Offline Sale Flow

```
User → SalesFlow (Livewire) → queueOffline()
  → outbox.js: enqueue() → IndexedDB
  → [device comes online]
  → sync.js: flush() → topological sort → POST /app/sync
  → SyncController → SyncService::process()
    → [DB::transaction]
    → SyncReceipt::create() (idempotency check)
    → SaleSyncHandler → InvoiceService::create() (same as online)
    → SyncReceipt update with response
  → Client: remove from outbox
```

## Critical Files

| File | Why Critical | Blast Radius |
|------|-------------|-------------|
| `app/Services/InvoiceService.php` | Creates invoices atomically | All sales, returns, amendments |
| `app/Services/StockService.php` | Stock mutations | All inventory operations |
| `app/Services/PaymentService.php` | Payment collection | All financial receipts |
| `app/Services/Sync/SyncService.php` | Offline sync ingest | All offline operations |
| `app/Support/ActiveCompanyContext.php` | Multi-tenancy | Every request |
| `app/Http/Middleware/SecurityHeaders.php` | Security headers | Every response |
| `app/Models/Concerns/BelongsToCompany.php` | Tenant scoping | All queries |
| `app/Models/Concerns/AppendOnly.php` | Financial integrity | All ledger models |
| `resources/js/offline/sync.js` | Client-side sync engine | All offline operations |
| `resources/js/offline/outbox.js` | IndexedDB queue | All offline data |
| `routes/web.php` | All web routes | Every HTTP request |
| `bootstrap/app.php` | App bootstrapping | Application lifecycle |

## Change-Sensitive Zones

1. **Financial mutation paths** — Any change to `InvoiceService`, `PaymentService`, `ReturnService`, `StockService` requires transaction testing
2. **Sync handlers** — Changes to `SyncService` or handlers affect offline data integrity
3. **Multi-tenancy** — `BelongsToCompany` scope and `ActiveCompanyContext` — any bypass leaks data across companies
4. **Middleware stack** — Security headers, rate limiting, auth — changes affect every request
5. **Database migrations** — 127 existing; new migrations must be immutable after release
6. **Eloquent models** — `$fillable`, `$casts`, relationships — changes affect all consumers
7. **Config values** — `config/jawla.php`, `config/eta.php` — runtime behavior changes
8. **JavaScript offline layer** — `outbox.js`, `sync.js` — client-side data integrity
9. **Service worker** — `public/sw.js` — caching behavior, offline fallback
10. **Translations** — `lang/ar/`, `lang/en/` — bilingual parity required

## Generated or Protected Areas

| Path | Status | Reason |
|------|--------|--------|
| `vendor/` | Generated | Composer dependencies |
| `node_modules/` | Generated | npm dependencies |
| `public/build/` | Generated | Vite output |
| `bootstrap/cache/` | Generated | Laravel cache |
| `storage/` | Generated | Runtime logs, cache |
| `.env` | Protected | Secrets, never commit |
| `composer.lock` | Protected | Only via composer install |
| `package-lock.json` | Protected | Only via npm ci |
| `database/database.sqlite` | Protected | Test artifact |
| `docs/BUSINESS_RULES.md` | Protected | Spec, not implementation |
| `docs/SECURITY.md` | Protected | Spec, not implementation |
