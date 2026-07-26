# System Inventory

## Audited baseline

| Item | Observed |
|---|---|
| Revision | `master` at `ba768f7106b52fa8d2905daadc07cd6091ff0c26` |
| PHP | 8.3.32 |
| Laravel | 13.20.0 |
| Filament | 4.12.1 |
| Livewire | 3.8.2 |
| Node/npm | 24.15.0 / 11.12.1 |
| Vite/Tailwind | 8.1.4 / 4.3.2 |
| Database | PostgreSQL configured |
| Queue/session/cache | Production Railway config uses Redis; local `artisan about` reported database queue/session and file cache |
| Test framework | Pest 4.7.5 |
| Browser framework | Playwright 1.61.1 |
| Deployment | Railway, Docker, Nginx/PHP-FPM |
| Observability | Laravel logs and Sentry package/configuration |
| Locks | `composer.lock` and `package-lock.json` tracked |

`artisan about` was executed in the local configuration only: environment `local`, debug enabled, timezone `Africa/Cairo`, locale `ar`. It is not evidence of production settings.

## Repository surface

| Component | Count |
|---|---:|
| Application routes | 100 |
| Route files | 4 |
| Middleware classes | 6 |
| Migrations | 108 |
| Models | 59 |
| Services | 61 |
| Policies | 23 |
| Livewire classes | 25 |
| Filament classes/pages/resources | 95 |
| Console commands | 2 |
| Jobs | 0 |
| Tests | 99 |
| Working documentation files | 56 |
| PWA/offline source files | 6 |
| GitHub workflows | 3 |

No `app/Actions` implementation or scheduled application work was found. `routes/console.php` contains only the default `inspire` command.

## Major business domains

- Company, users, roles, permissions, and company context
- Customers, suppliers, warehouses, vans, routes, and visits
- Products, categories, units, prices, batches, stock, and stock movements
- Invoices, invoice items, payments, returns, expenses, cash boxes, and van transfers
- Purchase orders, receiving, supplier comparison, and quotations
- Work sessions, GPS pings, photos, signatures, PDFs, reports, and activity views
- Rep PWA with IndexedDB outbox and `/app/sync`
- ZATCA QR generation and Egyptian ETA document-building scaffolding

## Entry points and boundaries

- Filament administrator panel under `/admin`
- Rep application under `/app`
- Public API v1 protected by Sanctum
- Livewire update and upload endpoints
- PDF download endpoints for rep-owned records
- Offline sync controller and operation handlers
- Railway build/deploy, Docker runtime, backup and restore scripts

## Dependency posture

Installed notable packages include Spatie Permission, Sanctum, mPDF, Simple QR Code, Sentry Laravel, Filament, Livewire, and Pest. The primary guide mentions packages or tools not installed or not wired as required:

- Spatie Activitylog: not installed; a custom activity model/observers are used.
- Spatie Laravel Backup: not installed.
- Larastan/PHPStan: no executable/dependency found.
- DOMPDF and Filament Excel packages named in the guide: not installed.

`composer validate --no-check-publish` passed but warned that six dependency constraints are unbounded. Composer audit and npm audit reported no known advisories at audit time.

## Architecture drift

The controlling production guide describes Laravel 12, Tailwind 3, database queues, Forge/VPS, and a Spatie backup package. The repository uses Laravel 13, Tailwind 4, Railway, Redis in production configuration, and custom backup scripts. Role, offline, and release claims also conflict across documents. This drift is a release-governance issue, not merely editorial debt.

## Inventory limits

This inventory does not assert the state of production services, environment variables, database contents, object storage, domains/TLS, branch protection, external monitoring, legal documents, or signed customer acceptance.

