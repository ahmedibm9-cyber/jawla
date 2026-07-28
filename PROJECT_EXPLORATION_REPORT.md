# Jawla — Project Exploration Report

**Exploration date:** 2026-07-29

**Repository:** `C:\projects\jawla`

**Revision inspected:** `7b1dd3a` on `master`, plus the working-tree changes listed under “Scope and state”
**Status:** **Complete exploration; downstream implementation is safe to plan, but the application is not ready for a real-data production launch.**

> **Post-exploration implementation addendum (2026-07-29):** The offline-sale
> payload mismatch, active-company stock/policy defects, object-storage image
> sanitization, and PHPStan runtime-symbol defects identified by this dossier
> were remediated after the exploration snapshot. Release/promotion controls
> were also implemented. The original evidence below remains the baseline that
> motivated those changes; current status is tracked in
> `docs/PRODUCTION_READINESS_IMPLEMENTATION.md`.

## Executive summary

Jawla (جولة) is a bilingual Arabic/English field-sales CRM/ERP for Egyptian distribution teams. Reps use a Livewire PWA at `/app` to run routes, visit customers with GPS, sell from van stock, collect payments, record returns and expenses, and reconcile cash. Back-office roles use a Filament panel at `/admin`. The deployment unit is one Laravel 13 monolith backed by PostgreSQL; application-level `company_id` scoping supports a user switching among assigned companies.

The strongest architectural pattern is the service layer: financial and stock-changing flows delegate to services, use database transactions, and route inventory changes through `StockService`. The most important runtime path is offline mutation replay: IndexedDB outbox → `POST /app/sync` → `SyncService` → a typed handler → the same domain service used online.

The highest-priority verified defect is in that path. The offline sales UI queues each item without `unit_price`, while `SaleSyncHandler` requires `items.*.unit_price`; a real offline sale therefore reaches sync as an invalid payload and is marked failed. The test suite exercises the queue UI and the handler separately with synthetic payloads, so it does not close this contract gap.

Current verification is mixed: Pint, the Unit suite, Vite build, Composer audit, and npm audit pass. PHPStan exits 1 without diagnostics on this host. The Feature suite, even when run alone, exhausts its configured 1 GB memory limit. Consequently `make verify` is not green and the repository’s own definition of done is unmet.

## Scope and state

- **Verified fact:** Primary target is the single repository at `C:\projects\jawla`; no sibling project was needed to reconstruct the running application.
- **Verified fact:** Investigation was read-only except for the five exploration dossier files.
- **Verified fact:** The shared worktree changed during exploration. Revision `7b1dd3a` became `HEAD`, and concurrent edits now exist in `.github/workflows/ci.yml`, `AGENTS.md`, `Makefile`, plus untracked `docs/TASK_CONTEXT_BROWSER_TEST_LIMITATION.md`. Those edits were inspected but not modified by this exploration.
- **Constraint:** `.env` values and credentials were not read or reported. Only environment-variable names from committed configuration were considered.
- **Constraint:** `docs/BUSINESS_RULES.md`, `docs/SECURITY.md`, lockfiles, generated assets, and application code were not edited.

## Project identity and purpose

| Conclusion | Classification | Evidence |
|---|---|---|
| Field-sales CRM/ERP for reps and back-office users | Verified fact | `AGENTS.md:3-8`; `README.md:1-5` |
| Arabic and English, with RTL/LTR rendering | Verified fact | `resources/views/layouts/app.blade.php:1-2`; `app/Http/Middleware/SetLocale.php:9-23` |
| Egyptian operating context and EGP-only accounting | Verified fact | `composer.json:5`; `docs/adr/0001-single-currency-egp.md`; `app/Services/InvoiceService.php:131-139` |
| One deployable Laravel monolith | Verified fact | `AGENTS.md:40-43`; `bootstrap/app.php:15-54` |
| Multi-company access within one application deployment | Verified fact | `app/Models/User.php:80-105`; `app/Http/Requests/SwitchCompanyRequest.php:9-22` |
| Expected scale is 6–20 reps | Tentative inference | Mentioned in earlier project material, but no current capacity contract or production metrics were found |

## Verified technology stack

Runtime versions were queried from the installed environment/package metadata on 2026-07-29.

| Layer | Installed version / contract | Evidence |
|---|---|---|
| PHP | 8.3.32; project constraint `^8.3` | `composer.json:12`; `php --version` |
| Laravel | 13.20.0 | `composer.json:23`; `php artisan --version`; installed Composer metadata |
| Filament | 4.12.1 | `composer.json:22`; installed Composer metadata |
| Livewire | 3.8.2 (transitive through Filament) | installed Composer metadata; Livewire components under `app/Livewire/App/` |
| PostgreSQL | Required application/test database; deployment targets PostgreSQL 16 | `AGENTS.md:42`; `phpunit.xml:31-36`; `.github/workflows/ci.yml:25-37` |
| Tailwind CSS | 4.3.2 | `package.json:13,19`; installed npm metadata; `resources/css/app.css:1` |
| Vite | 8.1.4 | `package.json:20`; installed npm metadata |
| Playwright | 1.61.1 | `package.json:12`; installed npm metadata |
| Pest | Constraint `^4.7.5` | `composer.json:54` |
| Larastan/PHPStan | Larastan 3.10.0 / PHPStan 2.2.6 | installed Composer metadata / command output |
| Laravel Pint | 1.29.3 | installed Composer metadata |
| Maps | Leaflet 1.9.4 + OpenStreetMap tiles | `package.json:24`; `resources/css/app.css:2`; `SecurityHeaders.php:43` |
| Storage | Local disks plus S3-compatible object storage | `config/filesystems.php:16-75` |
| Error tracking | Sentry Laravel 4.x and browser SDK 10.67.0 | `composer.json:28`; `package.json:23`; `config/sentry.php:10-61` |
| PDF / QR | mPDF 8.3, simple-qrcode 4.2 | `composer.json:27,29` |

## Repository inventory

Counts are from `rg --files` on the inspected working tree and exclude dependencies/generated build output.

| Surface | Inventory |
|---|---:|
| Application PHP files | 333 |
| Eloquent models | 68 |
| Service-layer PHP files, including contracts/sync/ETA | 66 |
| Rep Livewire components | 24 |
| Filament files | 99 |
| Migrations | 127, containing 73 `Schema::create` calls |
| HTTP routes | 121 from `php artisan route:list --json` |
| PHP test files | 113 |
| Browser test files | 7 |
| Documentation files | 99 |

Primary modules:

- `app/Livewire/App/`: rep PWA interaction layer.
- `app/Filament/`: admin resources, pages, widgets, authentication.
- `app/Services/`: financial, inventory, visits, purchasing, offline sync, ETA, photos, PDFs.
- `app/Models/`: Eloquent domain/data model.
- `app/Policies/` and middleware: authorization, tenancy context, throttling, security headers.
- `resources/js/offline/`: device-scoped IndexedDB outbox and sync engine.
- `database/migrations/`: PostgreSQL schema and integrity constraints.
- `routes/`: web, public API, console, and isolated rep-sync route.
- `tests/`: Pest Unit/Feature/Browser plus k6/stress assets.

## Architecture

### Request and process entry points

- `bootstrap/app.php:15-45` registers web/console routing, `/up`, middleware, schedule, and JSON exception behavior.
- `app/Providers/AppServiceProvider.php:43-170` binds services, configures rate limiters, registers `/api` and `/app/sync`, and applies production boot checks.
- `app/Providers/Filament/AdminPanelProvider.php:39-113` defines `/admin`, resource discovery, widgets, and admin middleware.
- `routes/web.php:32-103` exposes `/login`, `/health`, company switching, and the rep PWA.
- `routes/api.php:19-28` exposes read-only, Sanctum-authenticated product/customer APIs.
- `routes/rep-sync.php:6-13` exposes the offline sync endpoint behind web auth, rep role, and POST throttling.
- `bootstrap/app.php:39-41` schedules `app:purge-location-pings` daily.

### Runtime boundaries

1. **HTTP/UI layer:** Filament, Livewire components, controllers, middleware, validation.
2. **Service layer:** transactions, business invariants, numbering, price calculation, stock mutation, integrations.
3. **Data layer:** Eloquent models and PostgreSQL constraints/triggers.
4. **Device offline layer:** service worker for public assets/offline fallback, IndexedDB for authenticated mutation outbox.
5. **Infrastructure:** Railway/Docker configuration, PostgreSQL, Redis in production, S3-compatible photo storage, Sentry, ETA.

## Traced runtime flow: offline sale → sync → invoice

### Intended path

1. Rep confirms an invoice while offline.
2. Browser queues a `sale` record in a device/user-scoped IndexedDB database.
3. The outbox generates a UUID idempotency key, SHA-256 payload hash, and device ID.
4. On reconnect/visibility, the client posts up to 100 ordered operations to `/app/sync`.
5. The controller validates the envelope and maps protocol/device/hash fields.
6. `SyncService` checks company context, reserves `(company_id, idempotency_key)` in a transaction, invokes the registered handler, and stores the handler response atomically.
7. `SaleSyncHandler` calls `InvoiceService::create()`.
8. `InvoiceService` authorizes the seller, locks customer/user records, recalculates server-authoritative prices and tax, creates invoice/items/snapshots, decrements van stock through `StockService`, writes movements, and updates customer balance.
9. Client deletes applied/duplicate records or marks mismatch/failed/conflict records for review.

### Evidence

- Queue and identity: `resources/js/offline/outbox.js:6-18,21-37,85-113`.
- Flush and reconciliation: `resources/js/offline/sync.js:59-121,124-140,216-249`.
- Endpoint validation: `app/Http/Controllers/App/SyncController.php:18-41`.
- Exactly-once transaction: `app/Services/Sync/SyncService.php:50-127`.
- Handler registration: `app/Providers/SyncServiceProvider.php:20-31`.
- Invoice transaction: `app/Services/InvoiceService.php:36-188`.
- Stock lock/non-negative check/movement: `app/Services/StockService.php:96-140`.

### Observed contract break

- The UI queues `items` with only `product_id` and `quantity`: `resources/views/livewire/app/sales-flow.blade.php:193-205`.
- `SaleSyncHandler` requires `items.*.unit_price`: `app/Services/Sync/Handlers/SaleSyncHandler.php:23-32`.
- The handler validates before calling `InvoiceService`: `SaleSyncHandler.php:25-36`.
- `sync.js` marks a `failed` result as failed in the outbox: `resources/js/offline/sync.js:101-113`.

**Conclusion:** a sale produced by the actual offline UI cannot satisfy the current server contract. **Confidence 98/100.** Not 100 because a live browser-to-database reproduction was not run; the static producer/consumer mismatch is otherwise direct.

The three focused offline test files passed together (28 tests, 85 assertions), but they verify the queue UI and server handlers separately. They do not submit the exact Blade-produced sale payload to `SaleSyncHandler`, so the green targeted result does not contradict the contract defect.

### Failure and observability behavior

- Network/401/419 leaves items pending for retry (`sync.js:97-120`).
- Unsupported, invalid, or handler failures become per-operation results rather than aborting the batch (`SyncService.php:58-64,125-127`).
- Duplicate keys return stored results; hash mismatches are quarantined (`SyncService.php:71-85`).
- Legacy receipts with a null response become conflicts requiring support (`SyncService.php:69-78`).
- Sentry is registered for exceptions (`bootstrap/app.php:42-53`) and scrubs sensitive keys (`app/Support/SentryScrubber.php:19-81`).
- **Risk:** sync failure responses expose raw exception messages (`SyncService.php:125-127`), which can include internal database/query details.

## Domain model and glossary

### Core domains

| Domain | Core records and responsibilities |
|---|---|
| Tenant and identity | `Company`, `User`, `company_user`, roles/permissions, active company context |
| Route execution | `Route`, `DailyVisitAssignment`, `WorkSession`, `Visit`, `VisitReport`, `LocationPing` |
| Sales | `Customer`, `Product`, `PriceList`, `ProductPrice`, `Invoice`, `InvoiceItem`, `Payment` |
| Inventory | `Warehouse`, `Stock`, `StockMovement`, `Batch`, `VanTransfer`, stock counts/imports |
| Returns and corrections | `ReturnRecord`, `ReturnItem`, `CreditNote`, `CustomerCredit`, `Refund`, `Reversal` |
| Procurement | `Supplier`, `PurchaseRequest`, `PurchaseOrder`, `GoodsInTransit`, `LandedCost` |
| Operations | `Expense`, `CashBox`, `CashReconciliation`, `Complaint`, `Alarm`, `Task`, `Photo` |
| Offline delivery | `SyncReceipt`, client outbox record, protocol/device/hash metadata |

### State vocabulary

- Invoice enum: `draft`, `issued`, `submitted`, `cancelled`, `amended`, `partially_paid`, `paid`, `credited`, `voided` (`app/Enums/InvoiceStatus.php:5-16`).
- Van transfer enum: `pending`, `accepted`, `shipped`, `received`, `rejected`, `cancelled` (`app/Enums/VanTransferStatus.php:5-13`).
- Visit enum: `open`, `closed` (`app/Enums/VisitStatus.php:5-9`).
- Visit purposes: `sale`, `collection`, `return`, `survey`, `other`, `custom_visit` (`app/Enums/VisitPurpose.php:5-13`).
- Stock reasons: sale, return, transfer, adjustment, initial, purchase, landed cost, transit, inter-company, reversal (`app/Enums/StockReason.php:5-19`).

### Important invariants

- No negative stock; row lock plus rejection in `StockService` (`StockService.php:103-139`).
- Invoice creation, numbering, item creation, stock decrement, and balance update share one transaction (`InvoiceService.php:36-188`).
- Supplied prices are checked against server-authoritative effective prices (`InvoiceService.php:91-110`).
- Company-scoped models fail closed when no active company is set (`BelongsToCompany.php:12-61`).
- User switching is limited to assigned companies (`SwitchCompanyRequest.php:9-22`).
- Financial/ledger tables are deletion-protected in Eloquent and PostgreSQL (`AppendOnly.php:7-14`; migration `2026_07_26_000009...php:71-83`).
- **Clarification:** “append-only” means delete-protected, not immutable. Models such as invoices and sync receipts are intentionally updated as lifecycle state changes.

### Glossary

| Term | Jawla meaning |
|---|---|
| Jawla | A rep’s daily field round/route |
| Van stock | Inventory held in a rep-associated van warehouse |
| Active company | The company selected for the current request/session |
| Proforma | Pre-sale commercial document, not the final financial invoice |
| Reconciliation | End-of-day comparison of expected and counted cash |
| FEFO | Earliest-expiry-first batch selection |
| Outbox | Device-local IndexedDB queue of unsynced rep writes |
| Sync receipt | Server idempotency record for a successfully applied/replayed operation |
| ETA | Egyptian Tax Authority e-invoicing integration |
| ZATCA | Saudi QR/e-invoicing compatibility code present in the product |

## Data stores and interfaces

### Persistence

- PostgreSQL is the application and test-system database. Tests are pinned to `jawla_test`; `TestingDatabaseGuard` rejects other names (`phpunit.xml:25-39`; `tests/Support/TestingDatabaseGuard.php:9-21`).
- The tenant boundary is an active-company service plus `BelongsToCompany` global scope/create/update/delete guards (`ActiveCompanyContext.php:9-103`; `BelongsToCompany.php:10-62`).
- Redis is configured for production session/cache/queue use (`railway.toml:11-19`; `docs/DEPLOYMENT.md:26-50`).
- Browser IndexedDB holds pending/failed/conflict outbox records (`outbox.js:6-8,93-169`).
- Cache Storage contains public build/images/icons and an offline page only; authenticated HTML/API/PDF responses are network-only (`public/sw.js:58-95`).
- Photos target a configurable local/S3 disk and store the disk per row (`config/filesystems.php:18-75`; `PhotoService.php:21-48`).

### External and public interfaces

| Interface | Producer → consumer | Auth / contract | Failure behavior |
|---|---|---|---|
| Rep sync | PWA → Laravel `/app/sync` | Session + CSRF + rep role + throttle; max 100 ops; v1 header | Per-operation statuses; network/session failures remain queued |
| Public API v1 | External client → `/api/v1/products`, `/customers`, `/whoami` | Sanctum token, abilities, company context, 60/min token/IP | Laravel JSON errors; lists paginated max 100 |
| ETA | Laravel → ETA OAuth/document APIs | Client credentials + taxpayer certificate signer | Disabled by default; null/unsigned path rejects rather than claiming success |
| S3-compatible photos | Laravel → Railway bucket/S3 | Server-side object-storage credentials | Storage adapter reports failures; see S3 EXIF risk below |
| Sentry | Server/browser → Sentry | DSN; PII and sensitive-key controls | Optional when DSN/package unavailable |
| Maps/GPS | Browser → geolocation and OSM tiles | Browser permission; no OSM auth | UI must handle denied/unavailable position |

ETA details: OAuth token cache and 30-second timeouts exist (`HttpEtaClient.php:34-83`), but no retry/backoff is implemented. A real CAdES-BES signer and preproduction validation remain go-live blockers (`UnsignedEtaSigner.php:7-19`; `docs/GO_LIVE_READINESS.md:42-58`).

Environment-variable names and purposes are summarized in `COMMANDS.md`; no values are included here.

## Development and operations

The declared contributor interface is the Makefile (`AGENTS.md:24-38`; `Makefile:1-56`). GNU Make is not installed on the inspected Windows host, so underlying commands were run directly. Detailed statuses and side effects are in `COMMANDS.md`.

Deployment evidence points primarily to Railway:

- Docker runs PHP-FPM plus Nginx (`Dockerfile:1-58`; `docker/start-container.sh:1-14`).
- Railway predeploy migrates and caches configuration/routes/views, starts two replicas, and checks `/up` (`railway.toml:1-20`).
- The GitHub deploy workflow does not call a deployment API; it assumes Railway auto-deploy and only echoes/smoke-checks (`.github/workflows/deploy.yml:11-40`).
- Backup/restore helpers fail closed, but the restore log is empty (`scripts/backup.sh:1-27`; `scripts/restore-backup.sh:1-28`; `docs/BACKUP_RESTORE.md:59-69`).

## Quality and risk findings

| ID | Severity | Finding | Classification / confidence | Recommended next investigation |
|---|---|---|---|---|
| R1 | Critical | Offline sale producer omits required `unit_price`; sync deterministically rejects real queued sales | Verified fact, 98 | Add a producer/consumer contract test, choose server-only pricing or include the quoted price, then run an offline browser flow |
| R2 | High | Feature suite exhausts 1 GB; repository verification cannot pass on current host | Verified runtime, 100 | Split Feature files to locate retained state/leak; make aggregate suite pass before relying on CI |
| R3 | High | Multi-company policy checks compare `user.company_id`, not active company; `StockPolicy` checks a `Stock` model with no `company_id` | Verified code, 93 | Add secondary-company policy tests; make policy ownership resolve through active company/warehouse |
| R4 | High | Production S3 photo path is passed to local `finfo`/GD EXIF code; current tests use `Storage::fake('s3')`, which is local | Strong inference, 84 | Test against an actual S3 adapter; strip metadata before upload or via stream/temp file |
| R5 | Medium | Sync returns raw throwable/query exception messages to authenticated clients | Verified code, 95 | Return a stable bilingual error code/message; report full exception server-side only |
| R6 | High | Deploy workflow assumes external Railway auto-deploy; repository code does not prove staging/production ordering or promotion gates | Verified repository / external status unknown, 78 | Inspect Railway service settings and environment approvals; record actual deployment and rollback evidence |
| R7 | Medium | “Append-only” wording overstates immutability; only deletes are blocked while updates are common | Verified fact, 95 | Document delete-protected lifecycle ledgers precisely and test allowed/forbidden mutations |
| R8 | Medium | Current documentation conflicts on Tailwind, hosting, branches, route caching, and test counts | Verified contradiction, 98 | Make `ARCHITECTURE_CURRENT.md` and this dossier authoritative; update stale README/CONTRIBUTING/DEPLOYMENT text in a separate task |
| R9 | High | ETA signer/preprod evidence and independent restore drill remain missing; current release authority says NO-GO | Verified docs / external completion unknown, 95 | Complete named go-live gates and retain evidence before real data |
| R10 | Medium | New browser-test CI coverage exists only in concurrent uncommitted changes and has not run on Linux CI | Verified worktree fact, 100 | Review/commit the separate browser-test task, then inspect first CI result |

### Multi-company policy contradiction

`SetActiveCompanyContext` and `SwitchCompanyRequest` allow a user to select any assigned company (`SetActiveCompanyContext.php:19-39`; `SwitchCompanyRequest.php:9-22`). However, `ChecksCompanyOwnership` compares only the user’s primary `company_id` to the model (`app/Policies/Concerns/ChecksCompanyOwnership.php:8-13`). This is fail-closed rather than a cross-company leak, but it can deny legitimate access after switching. `Stock` has no `company_id` (`app/Models/Stock.php:9-29`), yet `StockPolicy::view` uses the same check (`StockPolicy.php:18-20`).

### S3 photo contradiction

The committed readiness document claims durable S3 photo storage is complete (`docs/GO_LIVE_READINESS.md:60-69`). `PhotoService` stores first, then calls `Storage::disk($disk)->path($path)` and local filesystem/GD functions (`PhotoService.php:32-71`). The S3 test substitutes a local fake (`tests/Feature/PhotoDiskConfigTest.php:48-56`), so it does not validate the real adapter behavior.

## Contradictions and stale evidence

1. `README.md:13-16` says Tailwind 3, includes `spatie/laravel-activitylog`, and names Forge; installed dependencies/current architecture show Tailwind 4, a custom `Activity` model, and Railway configuration.
2. `CONTRIBUTING.md:6-8` says `main`; Git and workflows use `master`.
3. `docs/DEPLOYMENT.md:1-18` describes Forge, while lines 26-65 and `railway.toml` describe Railway.
4. `docs/DEPLOYMENT.md:39` says route caching is disabled; `railway.toml:2` enables it, and `php artisan route:cache` succeeded during exploration.
5. `docs/GO_LIVE_READINESS.md:20-34` preserves older passing test counts that do not describe the current 2026-07-29 run.
6. The prior exploration dossier called the CI branch mismatch critical. At inspected `HEAD`, `.github/workflows/ci.yml:3-7` targets `master`; that mismatch is resolved.
7. The prior dossier’s enum/state lists did not match current enums; this report uses source enums.

## Unknowns ledger

See `OPEN_QUESTIONS.md` for resolution steps. The decisions currently unsafe to assume are:

- whether Railway auto-deploy/approvals actually enforce staged production promotion;
- whether S3 photo capture succeeds through the real adapter;
- which Feature test group causes aggregate memory growth;
- whether secondary-company users can complete all policy-protected workflows;
- whether ETA credentials/signer/preprod acceptance and restore/rollback drills exist outside the repository;
- whether the new Linux browser-test job passes after the concurrent changes are committed.

## Confidence table

| Major conclusion | Score | Why not higher |
|---|---:|---|
| Project purpose and user journeys | 98 | Production user count and current adoption are not observable from code |
| Installed technology stack | 100 | Direct package/runtime evidence |
| Monolith/service-layer architecture | 97 | Not every Filament action/service call was traced |
| Online invoice transaction | 98 | Direct code plus tests inspected; no live UI execution |
| Offline sync engine semantics | 96 | Direct code and server tests; no live browser replay |
| Offline sale contract defect | 98 | Direct producer/consumer mismatch; no live end-to-end reproduction |
| Tenant scoping model | 94 | Core scope/context verified; not every model/policy exhaustively tested |
| Multi-company policy defect | 93 | Direct code; exact affected UI actions were sampled, not exhaustively enumerated |
| Deployment design | 82 | Repository configuration is clear; external Railway settings are not visible |
| Production readiness NO-GO | 95 | Current authoritative runbook says NO-GO; external evidence could have changed |
| Current verification status | 100 | Commands run and results inspected on 2026-07-29 |

## Recommended next actions

1. Fix and regression-test the offline sale payload contract before any field pilot.
2. Isolate the Feature-suite memory growth and restore a green aggregate `make test`/`make verify`.
3. Align multi-company policy ownership with `ActiveCompanyContext`, including `Stock`.
4. Exercise photo capture against a real S3-compatible adapter and correct EXIF processing if needed.
5. Replace raw sync exception messages with stable client-safe errors and server-side reporting.
6. Complete external production gates: ETA signer/preprod, restore and rollback drills, staging security/performance/accessibility/device tests, named operations ownership, and UAT.
7. Reconcile stale repository docs only after the concurrent browser-test task is reviewed.

## Readiness for downstream work

- **Planning a code change:** Ready. Use `PROJECT_MAP.md`, the flow above, and the risk list.
- **Implementing offline sales:** Ready only if R1 is the first invariant addressed.
- **General feature development:** Ready, with tenant/service/transaction constraints preserved.
- **Production launch or real customer data:** Not ready. R1/R2 plus the documented external go-live gates block a safe launch.

## Evidence index

High-value starting points:

- Instructions: `AGENTS.md`, `CLAUDE.md`
- Current architecture: `docs/ARCHITECTURE_CURRENT.md`
- Release authority: `docs/GO_LIVE_READINESS.md`
- Boot/routing: `bootstrap/app.php`, `app/Providers/AppServiceProvider.php`, `routes/web.php`, `routes/rep-sync.php`, `routes/api.php`
- Offline client: `resources/js/offline/outbox.js`, `resources/js/offline/sync.js`
- Offline server: `app/Http/Controllers/App/SyncController.php`, `app/Services/Sync/SyncService.php`, `app/Services/Sync/Handlers/`
- Sales/stock: `app/Services/InvoiceService.php`, `app/Services/StockService.php`, `app/Services/PricingService.php`
- Tenancy: `app/Support/ActiveCompanyContext.php`, `app/Models/Concerns/BelongsToCompany.php`, `app/Policies/Concerns/ChecksCompanyOwnership.php`
- Deployment/recovery: `railway.toml`, `Dockerfile`, `.github/workflows/`, `scripts/backup.sh`, `scripts/restore-backup.sh`
- Tests: `tests/Feature/OfflineSyncTest.php`, `tests/Feature/OfflineSyncHandlersTest.php`, `tests/Feature/RepFlowOfflineUxTest.php`, `tests/Feature/Tenancy/`
