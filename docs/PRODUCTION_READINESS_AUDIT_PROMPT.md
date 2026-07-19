# Production Readiness Verification Prompt

**Jawla (جولة) — Bilingual Field-Sales CRM/ERP for Fulla Chemical Trading Co.**

---

You are a Senior Laravel Security & Performance Architect. Perform a COMPREHENSIVE production readiness audit of **Jawla (جولة)** — a bilingual Arabic/English field-sales CRM/ERP for **Fulla Chemical Trading Co.**

================================================================================
PROJECT CONTEXT (DO NOT ASSUME — VERIFY AGAINST CODEBASE)
================================================================================

Stack: Laravel 13 · PHP 8.3 · Filament 4 (admin at `/admin`) · Livewire 3 + Tailwind 3 + Alpine (rep PWA at `/app`) · PostgreSQL 16 · Spatie Permission (5 roles) · spatie/laravel-activitylog · Leaflet + OSM · mpdf · simple-qrcode · Pest · Playwright
Hosting: Single VPS (Ubuntu 24.04, PHP-FPM, Nginx) via Laravel Forge · Cloudflare proxy (TLS full-strict) · Database driver for queue/cache/session · Nightly S3 backup
Environment: `APP_ENV=production` · `APP_DEBUG=false` · `.env` only in Forge

Architecture: Monolith serving two surfaces. Boundaries: Browser↔Laravel, Laravel↔PostgreSQL, Laravel→S3 (backup), Laravel→Sentry (errors). In-process everything else.

Multi-tenancy: `BelongsToCompany` trait on ALL models with `company_id`. `ActiveCompanyContext` middleware + `ActiveCompanyContext` service. Company isolation MANDATORY.

Roles (spatie/laravel-permission):

- `system_viewer` (super-admin)
- `hr_admin`
- `sales_manager`
- `warehouse_keeper`
- `sales_rep` (only role with `/app` access)

================================================================================
NON-NEGOTIABLE BUSINESS RULES (VERIFY EACH IN CODE + TESTS)
================================================================================

1. **NO NEGATIVE VAN STOCK** — `StockService::decrement()` rejects before commit
2. **ATOMIC SALES** — `InvoiceService::create()` wraps invoice+items+stock_decrement+movements+balance in single `DB::transaction()`
3. **MONEY MATH** — `Money` VO (bcmath) for ALL currency ops. VAT = subtotal × `company.vat_percent`/100 on VAT-eligible products only
4. **COLLECTIONS ATOMIC** — Cash box + customer balance + invoice paid/remaining in one transaction
5. **RETURNS** — Increase van stock (movement row) + reduce customer balance
6. **EXPENSES** — Decrease rep's cash box
7. **ROUTE LOCK** — Rep visits only assigned route customers; off-route = custom flag + flagged in reports
8. **SEQUENTIAL NUMBERS** — Invoice/return numbers per-company, server-generated, immutable
9. **STOCK ONLY VIA StockService** — Every qty change writes `stock_movements` row in same transaction
10. **REVERSAL = COMPENSATING TRANSACTION** — Never `delete()`. `ReversalService` symmetry tested.

================================================================================
SECURITY REQUIREMENTS (OWASP ASVS LEVEL 2 + LARAVEL SPECIFICS)
================================================================================

MUST VERIFY (code + config + headers):

- [ ] Argon2id password hashing (`config/hashing.php`)
- [ ] Rate limiting: login 5/min per IP+email; all POST 60/min per user
- [ ] Session: httpOnly, secure, same_site=lax, regenerate on login
- [ ] CSP header via middleware (`script-src 'self' 'unsafe-inline' cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com; img-src 'self' data:; connect-src 'self'`)
- [ ] HSTS, X-Frame-Options: DENY, X-Content-Type-Options: nosniff, Referrer-Policy: strict-origin-when-cross-origin
- [ ] No secrets in code/.env.example — all in Forge environment
- [ ] SQL injection impossible — Eloquent only, no raw bindings with user input
- [ ] XSS impossible — Blade `{{ }}` escaping + Filament/Livewire auto-escape; verify no `{!! !!}` with user data
- [ ] CSRF on all state-changing routes (`VerifyCsrfToken` middleware)
- [ ] File upload validation: mime, size, extension allowlist; stored outside public; served via signed URLs
- [ ] PDF generation (mpdf) — no user HTML injection; template-controlled only
- [ ] Activity log on all financial/stock mutations (`spatie/laravel-activitylog`)
- [ ] Prevent lazy loading in non-production (`Model::preventLazyLoading(!app()->isProduction())`)
- [ ] Pagination on EVERY list query — no unbounded `->get()`
- [ ] Custom bilingual 403/404/419/500 pages (ar/en)
- [ ] Sentry DSN configured, errors captured, PII scrubbed

================================================================================
PERFORMANCE & SCALABILITY (TARGET: 100+ CONCURRENT REPS + 20 ADMINS)
================================================================================

- [ ] N+1 eliminated — all relations eager-loaded via `with()` in controllers/services
- [ ] DB indexes on: `company_id` (all tables), foreign keys, `stock.quantity` (CHECK >=0), `invoices.company_id+number`, `visits.rep_id+date`, `stock_movements.stock_id+created_at`
- [ ] Partial indexes where applicable (e.g., active customers only)
- [ ] Query count per typical request < 50 (Laravel Telescope/Clockwork)
- [ ] Static assets: Vite manifest, Cloudflare caching, Brotli/gzip via Nginx
- [ ] Queue workers: 4+ processes, supervisor managed, horizon not needed yet
- [ ] PDF generation offloaded to queue for proformas/invoices > 10 items
- [ ] Redis upgrade path documented (session/cache/queue) — currently database driver
- [ ] Connection pooling: PgBouncer config ready for >200 connections
- [ ] Load test: k6 or artillery script simulating 100 reps doing visit→sell→collect flow for 10 min; p95 < 2s, error rate < 0.1%

================================================================================
DATA INTEGRITY & MIGRATIONS
================================================================================

- [ ] All 46+ migrations idempotent, runnable on fresh + existing DB
- [ ] FK constraints ON DELETE RESTRICT/SET NULL as appropriate
- [ ] CHECK constraints: `stocks.quantity >= 0`, `vat_percent >= 0`
- [ ] Decimal(12,3) for quantities, Decimal(15,4) for money
- [ ] Soft deletes on audit-critical tables (invoices, customers, products, stock_movements)
- [ ] Seeders: RoleSeeder (7 roles, 50 permissions), DatabaseSeeder (1 company + 7 users)
- [ ] Odoo migration script (Phase 17) tested against sample export — idempotent, logs errors

================================================================================
BILINGUAL RTL/LTR (AR/EN) — EVERY SURFACE
================================================================================

- [ ] `dir="rtl"` on `<html>` when ar, ltr when en (middleware sets locale)
- [ ] All UI strings via `__()` / `trans()` — zero hardcoded text in Blade/Livewire/Filament
- [ ] Filament: `->label(__('...'))`, `->placeholder(__('...'))`, table columns translated
- [ ] Error messages: bilingual keys in validation, exception hierarchy
- [ ] Noto Kufi Arabic font loaded, Latin fallback; no layout shift on switch
- [ ] Date/number formatting locale-aware (Carbon, NumberFormatter)
- [ ] RTL CSS: logical properties (`margin-inline-start`), `:dir()` pseudo-class, no hardcoded left/right
- [ ] Icon mirroring for directional icons (chevron, arrow)
- [ ] Playwright E2E: RTL smoke test on both `/admin` and `/app`

================================================================================
ROLE-BASED ACCESS CONTROL (VERIFY MATRIX FROM docs/ROLES_MATRIX.md)
================================================================================

- [ ] Filament: `Panel::authGuard('web')` + policies on every Resource
- [ ] Rep PWA: route middleware `role:sales_rep` on `/app/*`
- [ ] API/Inertia endpoints: ability checks via `Gate::allows()`
- [ ] Cross-role data leakage test: `sales_rep` cannot see other reps' visits; `warehouse_keeper` cannot access invoices
- [ ] 403 custom page shown (not exception dump) with bilingual message

================================================================================
ZATCA PHASE 1 COMPLIANCE (Saudi e-invoicing)
================================================================================

- [ ] `ZatcaPhase1Strategy::generateTLV()` produces byte-exact match to ZATCA test vectors
- [ ] QR code on proforma/invoice contains: seller name, VAT reg, timestamp, invoice total, VAT total
- [ ] Cryptographic stamp (if Phase 2) — strategy class exists, key management via Forge env
- [ ] Unit test: known input → expected TLV base64 output (`docs/ZATCA_NOTES.md`)

================================================================================
TESTING COVERAGE (TARGET: ≥70% on app/Services, 100% business rules)
================================================================================

- [ ] Pest: Unit (Money, VAT, StockService, ReversalService, Invoice numbering, ZATCA TLV)
- [ ] Pest: Feature — full sale flow atomic, collection atomic, return restores stock, role matrix 403s, route lock, rate limiter
- [ ] Playwright E2E: Rep day flow (login→start work→visit→sell 3 items→collect→return→end day→verify admin), Admin create product→load van→verify stock, RTL smoke
- [ ] CI: GitHub Actions runs Pest + Playwright on every push; fails on <70% services coverage

================================================================================
DEPLOYMENT & OPERATIONS (scripts/deploy.sh + Forge)
================================================================================

- [ ] deploy.sh: git pull → composer install --no-dev → npm ci && npm run build → artisan migrate --force → config:cache route:cache view:cache → queue:restart → health check `/up`
- [ ] Rollback: git checkout previous tag → re-run deploy steps
- [ ] Health endpoint: `/up` returns 200 with DB connectivity check
- [ ] Sentry test event fires on deploy
- [ ] Nightly backup: pg_dump → S3 (lifecycle 30 days) → restore drill documented (`docs/BACKUP_RESTORE.md`)
- [ ] Forge: Daemon workers (`queue:work --sleep=3 --tries=3`), scheduled commands (backup, alarm cleanup)

================================================================================
DELIVERABLE: PRODUCTION READINESS REPORT
================================================================================

Produce a Markdown report with:

1. **EXECUTIVE SUMMARY** — Go/No-Go with risk score (1-10)
2. **CRITICAL BLOCKERS** (must fix before launch) — file:line references
3. **HIGH RISK** — file:line, impact, remediation
4. **MEDIUM/LOW** — file:line, recommendation
5. **PERFORMANCE BASELINE** — query counts, load test results
6. **SECURITY CHECKLIST** — pass/fail per item above
7. **COMPLIANCE** — Business rules, ZATCA, RTL, RBAC — pass/fail
8. **TEST GAPS** — missing coverage per `docs/TESTING.md`
9. **DEPLOYMENT READINESS** — deploy.sh, Forge, backup, rollback
10. **SIGNED OFF BY** — your name/date

================================================================================
TOOLS AVAILABLE
================================================================================

- Full codebase read (grep, glob, read)
- Run Pest: `php artisan test --coverage`
- Run Playwright: `npx playwright test`
- Laravel Telescope (if installed) or Clockwork for query profiling
- k6/artillery for load testing (install if needed)
- Forge SSH access for config verification

================================================================================
BEGIN AUDIT NOW — START WITH CRITICAL PATH: Auth → StockService → InvoiceService → RBAC → ZATCA → Load Test
================================================================================
