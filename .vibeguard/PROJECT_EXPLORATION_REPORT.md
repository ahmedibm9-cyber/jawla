# Jawla Project Map

## Directory and Module Map

```
app/                    # Laravel application core
  Console/              # Artisan commands
  Data/                 # Data services
  Enums/                # PHP enums
  Exceptions/           # Custom exceptions
  Filament/             # Filament admin panel (v4)
    Auth/               # Auth login/register pages
    Resources/          # CRUD resources (users, companies, products, etc.)
    Widgets/            # Dashboard widgets
  Http/                 # Controllers, middleware, requests
    /App/               # App-specific controllers (LoginController)
    /Middleware/        # SecurityHeaders, FilamentAuthenticate, EnsureRepRole
    /Livewire/          # Livewire component controllers (NOT blades!)
  Models/               # Eloquent models (User, Company, Product, etc.)
  Observers/            # Model observers (AuditObserver)
  Providers/            # Service providers (AppServiceProvider, Filament auth)
  Services/             # Business logic services (StockService, PricingService, etc.)
  Support/              # Helper classes

config/                 # Laravel config files
database/               # Migrations, seeders, factories
public/                 # Web root, compiled assets, service worker
resources/              # Blade views, JS, CSS, lang files
routes/                 # web.php, api.php, console.php
storage/                # Runtime logs, cache, compiled views
temp/                   # Temporary files
vendor/                 # Composer dependencies
```

## Entry Points

| Path       | Handler                                     | Notes                                                |
| ---------- | ------------------------------------------- | ---------------------------------------------------- |
| `/`        | `SystemPageController::root()`              | Root redirect                                        |
| `/login`   | `Filament\Auth\Pages\Login`                 | Unified login for reps + admins                      |
| `/app/`    | Livewire components (Home, SalesFlow, etc.) | Protected by `auth.license.ensure.rep.ensure.device` |
| `/admin/`  | Filament panel                              | Protected by `auth`                                  |
| `/health`  | `SystemPageController::health()`            | Returns `{"status":"ok","db":"ok","cache":"ok"}`     |
| `/offline` | `SystemPageController::offline()`           | PWA offline status                                   |

## Component Relationships

```
Login (Filament) → authenticates user →
  ├─ if hasRole(sales_rep, rep) → redirect /app → Home → Livewire components
  └─ else → redirect to Filament admin panel

StockService → sellFromVan() → DB::transaction() →
  ├─ reduces van stock quantity
  ├─ writes stock_movements row
  └─ never allows negative stock

User → hasAnyRole(['sales_rep','rep']) → determines login redirect
Company → is_active → filters all data queries
```

## Data-Flow Diagram

```
Rep Action (PWA)
    ↓
Livewire Component (SalesFlow, TodaysCustomers, etc.)
    ↓
HTTP request → /livewire/update (POST)
    ↓
Auth check (session + license + ensure.rep + ensure.device)
    ↓
Service layer (StockService, PricingService, etc.)
    ↓
Database (PostgreSQL) — filtered by active company_id
    ↓
Response → Livewire re-render
    ↓
UI update (RTL Arabic / LTR English)
```

## Critical Files

| File                                                       | Purpose                                                              | Change Sensitivity                                                     |
| ---------------------------------------------------------- | -------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| `app/Models/User.php`                                      | Core user model with Spatie roles; `is_active` check during login    | **HIGH** — any change to $fillable, boot(), or role logic affects auth |
| `app/Services/StockService.php`                            | Stock management; never allow negative stock; writes stock_movements | **CRITICAL** — money-flow path; one bug = financial loss               |
| `app/Http/Middleware/SecurityHeaders.php`                  | X- Powered-By removal, HSTS, CSP, etc.                               | **MEDIUM** — security improvement; low blast radius                    |
| `config/cache.php`                                         | Default cache store ('database' → 'file')                            | **MEDIUM** — affects Livewire /livewire/update behavior                |
| `config/session.php`                                       | Session driver ('database'), lifetime (120 min)                      | **MEDIUM** — session loss causes login failures                        |
| `database/seeders/DemoSeeder.php`                          | Full demo dataset (company, products, customers, invoices, routes)   | **HIGH** — seeds production-like data                                  |
| `phpstan.neon`                                             | PHPStan config (level 0 → 1 planned)                                 | **MEDIUM** — type safety; may surface errors                           |
| `.github/workflows/ci.yml`                                 | CI pipeline                                                          | **HIGH** — gate for all merges                                         |
| `railway.toml`                                             | Deploy config (healthcheck, restart policy)                          | **HIGH** — controls deployment success                                 |
| `resources/views/livewire/app/login-form-layout.blade.php` | Login form HTML (if used standalone)                                 | **LOW** — mostly Filament-driven                                       |

## Change-Sensitive Zones

- **Don't modify** `app/Models/User.php` `boot()` method's `LicenseService::assertCanActivateUser()` call without understanding the license check
- **Don't update** `stocks.quantity` directly — always use `StockService` (blatant rule from AGENTS.md)
- **Don't** set `$request->all()` into a model — always use Form Requests or `$fillable` (AGENTS.md rule)
- **Do** use `with()` on model relationships to prevent N+1 (AGENTS.md rule)
- **Do** paginate every list query (AGENTS.md rule)
- **Do** ensure RTL Arabic + LTR English works from first commit (AGENTS.md rule)

## Generated or Protected Areas

- `vendor/` — never edit manually; use Composer
- `bootstrap/cache/` — regenerate via `php artisan clear-compiled` / `optimize`
- `storage/` — runtime logs, cache, compiled views; do not edit manually
- `public/build/` — Vite output; regenerate via `npm run build`
- `composer.lock` — only via `composer install`; never hand-edit
- `package-lock.json` — only via `npm ci`; never hand-edit

## Generated Files (from this exploration)

- `.vibeguard/PRODUCTION_EXPLORATION_REPORT.md` — this report
- `.vibeguard/PROJECT_MAP.md` — this map
- `.vibeguard/COMMANDS.md` — commands list (see below)
- `.vibeguard/OPEN_QUESTIONS.md` — open questions (see below)
- `.vibeguard/EXPLORATION_MANIFEST.json` — machine-readable manifest (see below)
