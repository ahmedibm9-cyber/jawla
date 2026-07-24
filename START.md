# Agent Start Prompt

You are one of **4 agents** working on the Jawla Laravel PWA. Your job:

1. Read this file fully.
2. Read `agents-chat.md` to see who's already claimed what.
3. Claim your domain, append your entry, then work **only** on your domain.

## Domain 1: Rep PWA (Field Sales App)

**Agent name:** `rep-pwa-agent`
**Owns:**

- `app/Livewire/App/` — all Livewire components (Home, SalesFlow, LogReturn, LogExpense, VisitFlow, StockSearch, CollectPayment, etc.)
- `resources/views/livewire/app/` — Blade templates for rep screens
- `resources/views/components/` — shared PWA components
- `routes/web.php` — `/app/*` routes
- `public/sw.js`, `public/manifest.json` — service worker + PWA manifest
- `resources/js/` — client-side JS (IndexedDB sync, offline queue, camera)
- Offline sync, background fetch, push notifications

**Focus:** Usability, offline reliability, RTL/LTR, mobile performance, touch targets.

## Domain 2: Admin Panel (Filament)

**Agent name:** `admin-agent`
**Owns:**

- `app/Filament/` — all resources, pages, widgets, clusters
- `app/Filament/Resources/` — all 23+ admin resources
- `resources/views/filament/` — custom Filament views
- `config/filament.php`
- Admin routes, middleware, access control

**Focus:** Resource CRUD, role-based access, dashboard widgets, report pages, data tables.

## Domain 3: Backend Services & Business Logic

**Agent name:** `backend-agent`
**Owns:**

- `app/Services/` — all service classes (StockService, InvoiceService, PaymentService, ReversalService, ReturnService, ExpenseService, etc.)
- `app/Models/` — Eloquent models, relationships, scopes
- `app/Http/Requests/` — form request validation
- `database/migrations/` — schema changes
- `database/seeders/` — seeders (DemoSeeder, etc.)
- Business rules: stock mutations, financial transactions, ZATCA compliance
- Activity logging, audit trails

**Focus:** Transaction safety, data integrity, stock correctness, financial accuracy, ZATCA.

## Domain 4: Infrastructure, Testing & DevOps

**Agent name:** `infra-agent`
**Owns:**

- `.github/workflows/` — CI/CD (ci.yml, security.yml)
- `tests/` — Pest tests, feature tests, unit tests
- `.testsprite/` — TestSprite E2E plans
- `railway.toml`, `Dockerfile` — deployment config
- `docs/` — architecture, security, business rules documentation
- `phpunit.xml`, `pest.php` — test configuration
- Sentry integration, monitoring
- Performance: caching, queues, Redis config

**Focus:** Test coverage, CI pipeline, deployment reliability, monitoring, documentation.

---

## Rules for all agents

1. **Read `agents-chat.md` before starting.** If someone already owns a file you need, talk to them (via the chat file) — don't touch it.
2. **Only edit files in your domain.** If you find a bug outside your domain, log it in `agents-chat.md` for the responsible agent to fix.
3. **Commit often.** Use conventional commits: `feat(scope): description`, `fix(scope): description`.
4. **Run `php -l` on every PHP file you edit.** No syntax errors.
5. **Run `php artisan test --compact` before committing** if you touched Services, Models, or Livewire components.
6. **Never commit secrets.** Never commit `.env` values. Never log sensitive data.
7. **Communicate blockers.** If you're stuck, write it in `agents-chat.md` with `Status: blocked` and what you need.
8. **Claim your domain now.** Read the chat file, append your entry with `Status: in_progress`, then start.
