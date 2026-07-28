# AGENTS.md — Agent instructions for Jawla

## Purpose

Jawla (جولة) is a bilingual (Arabic/English) field-sales CRM/ERP. Reps run
their daily "jawla": check in, pick a route, visit customers with GPS, sell
from van stock, collect cash, record returns. Admins manage master data and
see everything live.

## Repository map

```
app/ Laravel application (Filament admin, Livewire PWA, Services, Models)
config/ Laravel config files
database/ migrations, seeders, factories
docs/ Architecture, business rules, security, deployment, specs
public/ Web root (compiled assets, images, service worker)
resources/ Blade views, JS, CSS, lang files
routes/ web.php, api.php, console.php
scripts/ deploy, backup, restore, verify
tests/ Pest (Feature, Unit) + Playwright (Browser, e2e)
```

## Supported commands

| Action               | Command          |
| -------------------- | ---------------- |
| Setup                | `make setup`     |
| Dev server           | `make dev`       |
| Lint (PHP)           | `make lint`      |
| Typecheck            | `make typecheck` |
| Unit + Feature tests | `make test`      |
| E2E tests            | `make test-e2e`  | (CI only — see Browser test limitation)
| Full verify          | `make verify`    |
| Build assets         | `make build`     |
| Database migrate     | `make migrate`   |
| Database seed        | `make seed`      |
| Smoke test           | `make smoke`     |

## Architecture rules

- Monolithic Laravel 13 app. One codebase, one server, one PostgreSQL database.
- Admin panel at `/admin` (Filament 4). Rep PWA at `/app` (Livewire 3 + Tailwind).
- All business logic lives in `app/Services/`. Controllers and Livewire components
  delegate to services — they never contain business rules.
- Money mutations (invoices, payments, returns, expenses, cash box, van transfers)
  happen inside `DB::transaction()` via a Service. Never from a controller directly.
- Stock changes ONLY through `StockService`, which always writes a matching
  `stock_movements` row. Never update `stocks.quantity` directly.
- Model relationships use `with()` to prevent N+1. Do not disable
  `preventLazyLoading`.
- Pagination on every list. Never `->get()` an unbounded query.
- RTL Arabic + LTR English must work everywhere from the first commit.

## Security rules

- Secrets only in `.env`. Nothing secret reaches the frontend, JS bundles,
  Blade output, or logs. No API keys, tokens, or PATs in code.
- No shell execution: never use `exec`, `shell_exec`, `system`, `passthru`,
  `proc_open`, `eval`. No user input reaches a command line.
- All writes go through Form Requests or Livewire validation server-side.
  Use `$fillable` — never `$request->all()` into a model.
- Password hashing = argon2id. TLS enforced. Sessions httpOnly + secure
  in prod + regenerated on login.
- Rate-limit login (5/min per IP+email) and every POST route (60/min per user).
- Every destructive or financial action requires a confirmation modal that
  states the exact consequence, bilingually.
- Never commit API keys, passwords, private keys, access tokens, cloud
  credentials, production `.env` files, or real customer data.

## Database and migration rules

- Every schema change goes through a migration in `database/migrations/`.
- Migrations are immutable after release.
- Destructive operations (DROP, TRUNCATE) require explicit review.
- Large data migrations are separated from schema migrations.
- Seed data is for development/testing only — never production data.
- Tests must use isolated databases (RefreshDatabase).

## Testing expectations

- Write Pest tests alongside each phase (not in a "phase 13 test push").
- Feature tests must include the failure path for every money/stock flow.
- E2E: at minimum, rep day flow + admin master-data flow + RTL smoke.
- Run `make verify` before reporting any task complete.

### Browser (E2E) test limitation

Browser tests (`tests/Browser/`) use `pestphp/pest-plugin-browser` v4.3.1
with Playwright. There is a **known upstream bug** that prevents the
Playwright child process from staying alive during Pest's test lifecycle:

- **Affected:** Windows development machines (process lifecycle issue)
- **Not affected:** Linux CI environments (GitHub Actions, Docker)
- **Upstream issue:** https://github.com/pestphp/pest/issues/1517
- **Symptom:** `AssertionError: WebSocket client is not connected.`

Standalone PHP works correctly (server starts, WebSocket connects, Playwright
initializes). The bug is in how Pest manages child processes on Windows.

**Workarounds:**
1. Browser tests run in CI (Linux) — trust CI results for E2E verification.
2. For local E2E testing, use Laravel Dusk as an alternative.
3. Wait for upstream fix in `pest-plugin-browser` v4.4+.
4. Run Playwright server manually and connect via `AlreadyStartedPlaywrightServer`.

## Files agents must not modify

- `docs/BUSINESS_RULES.md` — spec, not implementation
- `docs/SECURITY.md` — spec, not implementation
- `.env` — secrets, never committed
- `composer.lock` / `package-lock.json` — only via `composer install` / `npm ci`
- `database/database.sqlite` — test artifact

## Generated files

- `public/build/` — Vite output, regenerated by `npm run build`
- `bootstrap/cache/` — Laravel cache, regenerated by `php artisan optimize`
- `storage/` — runtime logs, cache, compiled views

## Definition of done

A task is complete when ALL of these pass:

1. `make verify` exits 0 (lint + typecheck + test + build)
2. Business rules enforced at the service layer
3. Bilingual AR/EN + RTL verified for any UI change
4. Confirmation modal for any destructive/financial action
5. No new packages beyond the locked stack without explicit approval
6. `composer audit` and `npm audit --audit-level=high` clean
7. Relevant docs updated (architecture, business rules, or deployment as needed)
