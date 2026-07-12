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
- Leaflet + OpenStreetMap · dompdf · simple-qrcode · Pest · Playwright
- Hosted on a single VPS via Laravel Forge · Cloudflare in front

## Quick start (once scaffolding is complete)
See `docs/DEPLOYMENT.md` for the full sequence. Short version:
1. `cp .env.example .env` and fill values.
2. Install Composer + npm dependencies (see main guide, Phase 0).
3. `php artisan key:generate && php artisan migrate --seed`.
4. `php artisan serve` + `npm run dev`.

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
