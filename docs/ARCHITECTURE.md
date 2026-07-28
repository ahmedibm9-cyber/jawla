# Architecture

> **Superseded** by `ARCHITECTURE_CURRENT.md` (updated 2026-07-28). This
> document is retained for historical reference only.

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

## PWA trust boundaries and data flow

The rep application is an authenticated Livewire application at `/app`; it is
not a public offline data replica. The following boundaries must remain intact:

1. **Browser → Laravel.** Session-cookie authentication and server-side
   policies decide every protected read and write. Client state is never an
   authorization decision.
2. **Service worker → public cache only.** `public/sw.js` may cache the offline
   shell and fingerprinted public assets. It must not cache navigations,
   authenticated HTML, API responses, PDFs, or user uploads. A worker update
   waits for an explicit client activation request.
3. **Browser IndexedDB → sync endpoint.** The rep outbox is partitioned by an
   HMAC-derived authenticated identity. It holds only pending operation payloads
   and terminal sync state for that identity. Logout clears browser storage and
   asks the worker to purge client data before form submission continues.
4. **Sync endpoint → database.** `/app/sync` authenticates a rep, scopes every
   operation to the rep's company, and records a durable idempotency receipt in
   the same transaction as the handler's business write. A replay returns the
   stored response; an ambiguous legacy receipt is a support conflict, never a
   silent retry.
5. **Laravel → external services.** S3-compatible object storage is used for
   private photos when configured; backup archives are encrypted before upload;
   Sentry and the ETA service are optional external dependencies. A failed
   external dependency must surface as an actionable failure and must not make
   a financial/stock mutation appear complete.

## Failure behavior

- A lost connection leaves a queued operation visible as pending; a terminal
  failure or conflict stays visible until the rep resolves it or support
  intervenes.
- A service-worker update is deferred while queued work exists and is offered
  again after the queue drains. Activation is user-confirmed and reloads only
  after `controllerchange`.
- A service-layer validation or transaction failure rolls back financial and
  stock mutations. The UI reports the failure; it must not claim success.
- A missing active van warehouse rejects sales and returns at the service layer.
- A cross-company lookup must be rejected by the company scope or an explicit
  company comparison; elevated access is a documented policy decision, not a
  client-side bypass.

## Operational boundaries still requiring external evidence

Production deployment, DNS/TLS, Railway configuration, object-storage policy,
backup retention, alert delivery, ETA certification, privacy approvals, and
real-device/browser testing are not provable from this repository. Their
evidence and named owners are tracked in `PRIVACY_AND_OPERATIONS_GATES.md` and
the release register; absence of that evidence is a launch blocker.
