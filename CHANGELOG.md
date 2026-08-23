# Changelog

All notable changes to Jawla are documented here.

Format follows [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Added

- Core Web Vitals monitoring via Sentry (`web-vitals@4`)
- Periodic background sync in service worker
- A11y tests: color contrast (EN+AR), 200%/400% zoom
- Offline PWA end-to-end tests
- Lighthouse CI integration
- Health check now verifies storage write+delete
- Push notification retry (3 attempts, 1s backoff)
- Password policy: `->symbols()` requirement
- PHPStan level 3 with baseline
- CI: composer caching, concurrency groups, `npm audit --omit=dev`

### Changed

- CSP: removed `unpkg.com` from `style-src`, restricted WebSocket to `wss:` only
- CSP: added `upgrade-insecure-requests`
- Removed deprecated `X-XSS-Protection` header
- Moved `laravel/tinker` from `require` to `require-dev`
- PHPStan level 0 → 3

### Fixed

- README license contradiction (was "Proprietary", LICENSE file is MIT)

### Removed

- Dead files: `welcome.blade.php`, `onboarding-translations.blade.php`, `LeafletMapPicker.php`, `DataMigration.php`, `test-sort.js`
- Empty `docker/Dockerfile`
- `.env` artifact from CI (security bug)

## [1.0.0] - 2026-08-01

### Added

- Initial production release
- Laravel 13 / PHP 8.3 / Filament 4 / Livewire 3 / Tailwind 3
- PostgreSQL 16 with 24 migrations
- 11 roles × 300 permissions (spatie/laravel-permission)
- 941 test methods (Unit + Feature + Browser + JavaScript)
- PWA: service worker, offline snapshot, background sync, push notifications
- Deployment: Railway (Docker, php-fpm + nginx, 2 replicas) + Cloudflare
- Monitoring: Sentry DSN, structured logging, health check endpoint
