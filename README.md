# Jawla (جولة)

Bilingual (Arabic/English) field-sales CRM/ERP. Reps run their daily "jawla":
check in, pick a route, visit customers with GPS, sell from van stock, collect
cash, record returns. Admins manage master data and see everything live.

> **Do not start coding from this file.** See `docs/` and the main
> `Jawla_Production_Build_Guide.md` at the project root of the sibling repo.
> This repo starts as a scaffold and is filled phase by phase per the guide.

## Tech

- Laravel 13 (PHP 8.3) monolith · Filament 4 (admin) · Livewire 3 + Tailwind 3 (rep PWA)
- PostgreSQL 16 · spatie/laravel-permission · spatie/laravel-activitylog
- Leaflet + OpenStreetMap · mpdf · simple-qrcode · Pest · Playwright
- Railway (Docker, php-fpm + nginx, 2 replicas) · Cloudflare in front

## Quick start

Short version:

1. `cp .env.example .env` and fill values.
2. `composer install && npm install`
3. `php artisan key:generate && php artisan migrate --seed`
4. `php artisan serve` + `npm run dev`

### Demo credentials (via DemoSeeder)

> All demo accounts share the same password in this environment. **Do not use this pattern outside the demo seeder** — see `docs/SECURITY.md` and the credential-rotation runbook before any production change. Credentials are generated at seed time and written to `storage/app/private/demo-credentials.json` (gitignored).

| Role           | Email                 | Panel  |
| -------------- | --------------------- | ------ |
| Super Admin    | superadmin@jawla.test | /admin |
| Admin          | admin@jawla.test      | /admin |
| Sales Manager  | manager@jawla.test    | /admin |
| Finance        | accounts@jawla.test   | /admin |
| Purchasing     | purchasing@jawla.test | /admin |
| Warehouse      | warehouse@jawla.test  | /admin |
| Executive      | executive@jawla.test  | /admin |
| Rep #1 (Cairo) | rep@jawla.test        | /app   |
| Rep #2 (Giza)  | rep2@jawla.test       | /app   |

Generated values are also dumped to `storage/app/private/demo-credentials.json` on every seed run.

- Admin panel: http://localhost:8000/admin
- Rep app: http://localhost:8000/app

### Demo walkthrough — AM1→AM9 narrative

The seed data reproduces the client's voice-message narrative:

1. Manager assigns 5 visits (DailyVisitAssignments seeded for rep, today).
2. Rep logs in at /app → sees today's 5 visits on Home.
3. Rep taps visit → GPS geofence check (1.5km) → Confirm Arrival → visit report with signature → visit closed.
4. Rep flags new customer (More → Add Customer, pending → manager approves via Filament Customers resource).
5. Rep goes to /app/quotations → picks pending PriceQuotationRequest → negotiates within floor range → Confirm Price → Create Proforma.
6. Proforma shows bank details, QR code, WhatsApp share button, PDF download.
7. Manager reviews Quotation Requests in Filament (/admin → Quotation Requests → approves with base + ± range).
8. Invoice issued via Filament -> Captures Payment -> Reduces stock atomically -> Updates customer balance.
9. Rep `Material 952` out-of-stock: Filament Alarms dashboard shows critical red alarm broadcast to Finance, Manager, Executive.
10. Rep logs complaint (More → Log Complaint) → Complaint + alarm to Sales Manager → Manager resolves.
11. Rep submits Purchase Offer (More → Purchase Offer) → Sales approves → Purchasing approves sequentially.

### Tests

`php artisan test` — 975 tests covering:

- Auth + roles (admin/rep login, rate limit, locale switch)
- Stock service increment/decrement/transfer + insufficient stock rollback
- Company isolation (tenancy guard)
- Alarm broadcast (complaint → manager, OOS → 3 roles, pending customer → manager)
- Invoice flow (atomic create, oversell rollback, payment closes invoice, cancel reverses)
- AM1 → AM9 end-to-end narrative (26 assertions, single test case)

## Deploy

Production runs on Railway. See `docs/DEPLOYMENT.md` for the full workflow.

- Docker + php-fpm + nginx
- PostgreSQL 16 (Railway managed)
- 2 replicas for availability

## Docs index

- `docs/ARCHITECTURE.md` — stack + boundaries.
- `docs/BUSINESS_RULES.md` — non-negotiables (stock, atomic sales, VAT).
- `docs/ROLES_MATRIX.md` — 11 roles × permissions.
- `docs/DESIGN_SYSTEM.md` — 60/30/10, typography, component states.
- `docs/SECURITY.md` — auth, sessions, headers, secrets policy.
- `docs/ZATCA_NOTES.md` — Saudi Phase 1 QR TLV encoding.
- `docs/TESTING.md` — Pest + Playwright strategy.
- `docs/DEPLOYMENT.md` — Railway deployment, rollback.
- `docs/BACKUP_RESTORE.md` — nightly backup + restore drill.

## Licence

Proprietary — Fulla Chemical Trading Co. All rights reserved.
