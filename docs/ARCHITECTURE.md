# Architecture

## Stack boundaries
Monolithic Laravel 13 app serves both the admin panel (Filament, at `/admin`)
and the rep PWA (Livewire + Blade, at `/app`). One codebase, one server,
one PostgreSQL database.

## Runtime
- PHP-FPM behind Nginx.
- Queue: database driver, worker managed by Forge (Supervisor).
- Cache/session: database driver (upgrade to Redis only if metrics say so).

## Boundaries (kept intentionally few)
- Browser ↔ Laravel (HTTP + Livewire updates).
- Laravel ↔ PostgreSQL (Eloquent).
- Laravel ↔ S3-compatible backup target (write-only, nightly).
- Laravel ↔ Sentry (write-only, on exception).

Everything else is in-process.
