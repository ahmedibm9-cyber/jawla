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
- Hosted on a single VPS via Laravel Forge · Cloudflare in front

## Quick start

Short version:
1. `cp .env.example .env` and fill values.
2. `composer install && npm install`
3. `php artisan key:generate && php artisan migrate --seed`
4. `php artisan serve` + `npm run dev`

### Demo credentials (via DemoSeeder)

| Role            | Email                | Password |
|-----------------|----------------------|----------|
| Admin           | admin@jawla.test     | password |
| Sales Manager   | manager@jawla.test   | password |
| Finance         | accounts@jawla.test  | password |
| Purchasing      | purchasing@jawla.test| password |
| Warehouse       | warehouse@jawla.test | password |
| Executive       | executive@jawla.test | password |
| Rep             | rep@jawla.test       | password |

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

`php artisan test` — 32 tests, 105 assertions covering:
- Auth + roles (admin/rep login, rate limit, locale switch)
- Stock service increment/decrement/transfer + insufficient stock rollback
- Company isolation (tenancy guard)
- Alarm broadcast (complaint → manager, OOS → 3 roles, pending customer → manager)
- Invoice flow (atomic create, oversell rollback, payment closes invoice, cancel reverses)
- AM1 → AM9 end-to-end narrative (26 assertions, single test case)

## Deploy to Render (free)

A free-tier deployment for client demos. Uses Docker + Render Blueprint.

### Prerequisites
- GitHub repo with this code pushed
- Render account (https://render.com)

### Steps
1. Push code to GitHub (include `Dockerfile`, `render.yaml`, `scripts/render-start.sh`).
2. Go to https://dashboard.render.com → New → Blueprint.
3. Select your GitHub repo. Render reads `render.yaml` and provisions:
   - **Web service** (free plan, Docker runtime)
   - **PostgreSQL** (free plan, 90-day limit)
4. Set `APP_KEY` in the Render dashboard → Environment (generate with `php artisan key:generate` locally, paste the base64 key).
5. Set `APP_URL` to your Render URL (`https://jawla.onrender.com`).
6. Deploy. The `render-start.sh` entrypoint auto-runs:
   - `php artisan migrate --force`
   - `php artisan db:seed --class=DemoSeeder --force`
   - `php artisan config:cache && route:cache && view:cache`
7. After deploy, visit `https://<your-app>.onrender.com/` — redirects to `/app` (rep login).

### Demo URLs
- Rep app: `https://<your-app>.onrender.com/app`
- Admin panel: `https://<your-app>.onrender.com/admin`

### Free-tier limitations
- **Cold start:** service sleeps after 15 min idle → ~30s wake on first request.
- **PostgreSQL:** free for 90 days, then must upgrade or data is deleted.
- **File storage:** ephemeral — generated PDFs/signatures are lost on redeploy. Acceptable for demo; switch to S3/R2 for persistence.

## Docs index
- `docs/ARCHITECTURE.md` — stack + boundaries.
- `docs/BUSINESS_RULES.md` — non-negotiables (stock, atomic sales, VAT).
- `docs/ROLES_MATRIX.md` — five roles × permissions.
- `docs/DESIGN_SYSTEM.md` — 60/30/10, typography, component states.
- `docs/SECURITY.md` — auth, sessions, headers, secrets policy.
- `docs/ZATCA_NOTES.md` — Saudi Phase 1 QR TLV encoding.
- `docs/TESTING.md` — Pest + Playwright strategy.
- `docs/DEPLOYMENT.md` — Forge, deploy script, rollback.
- `docs/BACKUP_RESTORE.md` — nightly backup + restore drill.

## Licence
Proprietary — Fulla Chemical Trading Co. All rights reserved.
