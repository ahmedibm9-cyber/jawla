# Contributing to Jawla

Even for a single-developer or AI-driven build, follow these rules — they
keep the git history clean and the CI green.

## Prerequisites

- PHP 8.3+ with extensions: bcmath, gd, intl, mbstring, pdo_pgsql, zip
- Composer 2
- Node.js 22+ with npm
- PostgreSQL 16
- Redis (optional, for production cache/session/queue)

## Development setup

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
```

In a second terminal: `php artisan serve`

## Branching

- `main` is protected. All work goes through a pull request.
- Branch names: `feat/phase-N-short-slug`, `fix/short-slug`, `chore/…`, `docs/…`.

## Commits

- Conventional Commits: `feat:`, `fix:`, `chore:`, `docs:`, `test:`, `refactor:`.
- One logical change per commit. Reference the phase number when relevant.

## Code style

- PHP: Laravel Pint (`vendor/bin/pint`). Run before committing.
- PHPStan level 3 (`vendor/bin/phpstan analyse --level=3`).
- All business logic in `app/Services/`. Controllers and Livewire components delegate to services.
- Money mutations must be inside `DB::transaction()`.
- Use `$fillable` on all models — never `$guarded = []`.
- Bilingual: every user-facing string uses `l('عربي', 'English')`.

## Running tests

```bash
# Unit + Feature (requires PostgreSQL)
php artisan test

# Single test file
php artisan test tests/Unit/Services/PaymentServiceTest.php

# Browser/E2E (requires Playwright)
npx playwright install chromium
php artisan test tests/Browser

# JavaScript tests (PWA, offline, a11y)
npm run test:pwa-browser
npm run test:offline
npm run test:a11y
```

## Before opening a PR

- `vendor/bin/pest` passes.
- `vendor/bin/pint` clean.
- `vendor/bin/phpstan analyse` clean at the configured level.
- `composer audit` and `npm audit --audit-level=high` clean.

## PR template

Fill every section in `.github/PULL_REQUEST_TEMPLATE.md`.
