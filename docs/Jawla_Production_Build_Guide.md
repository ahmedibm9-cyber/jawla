# Jawla (جولة) — Field Sales CRM/ERP · Production Build Guide **v2**

> **Single source of truth for building this system.** Written for Claude Code
> to execute directly, phase by phase, without asking the owner technical
> questions. Every decision — technical and business — is now final,
> including the owner's calls in §13 (dual-country tax with ZATCA, Forge
> hosting, dynamic rep count). Nothing is left to ask. Build.
>
> v2 changes: 5-role model, full design system, testing strategy, security
> hardening, CI/CD, performance rules, activity log with reverse/redo,
> infrastructure & operations. ERPNext evaluated and **rejected** (see §2.1).

---

## 0. Rules for Claude Code

1. Build **phases in order** (§12). Each phase has a Definition of Done — meet it before moving on. Commit after every phase.
2. **Non-negotiable business rules** (§8) and **non-negotiable engineering rules** (§9) apply to every line of code in every phase, not just their own section.
3. Bilingual **Arabic (primary, RTL) + English** from day one.
4. If a pinned package version conflicts, use the nearest stable version and note it. Do not stop to ask.
5. After each phase, print what was built + how to test it manually.

---

## 1. Product summary

Field sales management for a distribution company. **Rep PWA** (mobile web app): check in, pick route, visit customers (GPS), sell from van stock, collect cash, record returns, log expenses. **Admin panel** (desktop): master data, stock, approvals, cash reconciliation, live reports, activity log. One codebase, one server, one database.

---

## 2. Locked tech stack

| Layer | Choice | Notes |
|---|---|---|
| Backend | **Laravel 12** (PHP 8.3) | Monolith. Upgrade path to 13 later |
| Admin UI | **Filament 4** | Entire admin panel |
| Rep UI | **Livewire 3 + Alpine + Tailwind 3** | Mobile-first PWA, no separate JS app |
| DB | **PostgreSQL 16** | Relational + real transactions |
| Auth | Laravel sessions + **Sanctum** | Web sessions; tokens reserved for future native app |
| Roles | **spatie/laravel-permission** | 5 roles (§5) |
| Activity log | **spatie/laravel-activitylog** | Powers the Activity Log + Reverse feature (§7) |
| Maps/GPS | **Leaflet + OpenStreetMap** + browser Geolocation | No API key, no billing |
| PDF / QR | barryvdh/laravel-dompdf + simplesoftwareio/simple-qrcode | Invoices |
| Excel | pxlrbt/filament-excel | Report export |
| Jobs | Laravel Queues (database driver) | No Redis needed at this scale |
| Tests | **Pest** (unit/feature) + **Laravel Dusk or Playwright** (E2E) | §10 |
| Errors | **Sentry** (free tier) | Error tracking + alerts |
| Backups | **spatie/laravel-backup** → S3-compatible storage | Daily, automated |
| CDN/WAF | **Cloudflare free tier** | Caching, TLS, basic DDoS protection |
| CI/CD | **GitHub + GitHub Actions** | Tests + security scans on every push (§11) |
| Hosting | Single VPS (Ubuntu 24) via **Laravel Forge** or plain Docker Compose | `[OWNER]` — see pending decisions |

### 2.1 ERPNext — evaluated and rejected
ERPNext would provide inventory/accounting out of the box, but it is a Python/Frappe platform: every custom feature (visit flow, GPS, rep PWA, activity-log reversal) means fighting a foreign framework, and iterating on it with an AI coding agent is far slower than on a clean Laravel codebase. **Decision: custom build.** Do not revisit.

### 2.2 Claude Code skills
If available in the environment, install/use skills for **Playwright** (E2E tests) and any UI/UX review skills (e.g. design-review or "taste"-type skills) when building rep screens. They are aids, not requirements.

---

## 3. Design system (build in Phase 0.5, use everywhere)

Create `resources/css/design-system.css` + a small Blade component library (`x-ds-*`). All screens use these components — never ad-hoc styles.

**Color — 60/30/10 rule:**
- **60% neutral base:** white `#FFFFFF` / light gray `#F5F5F4` backgrounds and cards.
- **30% secondary:** dark slate `#1F2937` for text, headers, nav surfaces.
- **10% accent:** crimson `#9B1C31` — primary buttons, active states, key highlights only. If crimson is everywhere, it highlights nothing.

**Semantic colors (fixed, never repurposed):**
- Success `#16A34A` (saved, paid, in stock) · Warning `#D97706` (low stock, pending) · Danger `#DC2626` (errors, destructive, overdue) · Info `#2563EB` (hints, notices).

**Typography scale (fixes H1 scaling):**
- Fluid heading sizes with `clamp()`: H1 `clamp(1.5rem, 4vw, 2.25rem)`, H2 `clamp(1.25rem, 3vw, 1.75rem)`, H3 `1.25rem`, body `1rem/1.6`, small `0.875rem`. One H1 per page. Font: **IBM Plex Sans Arabic** (Google Fonts) + system fallback.

**Component states — every interactive element defines all six:**
`normal → hover → focus/selected → active(clicked) → loading(in-progress) → disabled`.
Loading state on buttons: spinner + disabled + label change (Livewire `wire:loading`). Disabled: 50% opacity + `cursor-not-allowed`. Focus: visible ring (accessibility).

**Required UI behaviors:**
- **Confirmation modals** for every destructive or financial action (delete, cancel invoice, reverse action, end day) — bilingual, state the consequence, default button = safe option.
- **Tooltips** on every icon-only button and every abbreviation (Filament has these built in; use Alpine for the rep app).
- **Skeleton loaders** on every list/table while data loads — never a blank screen (`wire:loading` skeleton rows).
- **Optimistic UI only where safe:** cart quantity changes and UI toggles render instantly; **money and stock writes are never optimistic** — they show a loading state and confirm from the server.
- **Empty states:** every list has a friendly bilingual empty state with a next-step action.
- Touch targets ≥ 44px; bottom-anchored primary actions on mobile; dark mode enabled in Filament.

---

## 4. Database schema

Identical to v1 (§4 of `Jawla_Build_Guide_v1_Reference.md`, which sits alongside this file) — 20 tables: companies, users, warehouses, product_categories, products, stocks, stock_movements, routes, route_user, customers, work_sessions, visits, invoices, invoice_items, payments, returns, return_items, expenses, van_transfers (+items), cash_boxes. Money = `decimal(12,2)`. Soft deletes on customers, products, invoices, users. `stock_movements` is append-only.

**v2 additions:**
- `companies` gains `country (enum: 'SA','EG')` and `zatca_enabled (bool default false)` — drives currency, VAT %, and invoice QR format (§13.1).
- `activity_log` (from spatie/laravel-activitylog) with custom columns: `is_reversed (bool default false)`, `reversed_by (FK users nullable)`, `reversed_at (nullable)`, `reversal_of (FK activity_log nullable)`.
- Indexes: `invoices(customer_id, issued_at)`, `visits(user_id, checkin_at)`, `payments(collected_at)`, `stocks(warehouse_id, product_id)` unique, plus FK indexes everywhere.

---

## 5. Roles & permissions (5 roles — spatie)

| Role | Purpose | Key permissions | Restrictions |
|---|---|---|---|
| **system_viewer** (Founder) | Oversees everything | Full control of the whole system; assigns tasks to sales manager; sees all reports, logs, money | None |
| **hr_admin** | People & system admin | Create/edit/deactivate users; assign roles & routes; general system settings; view activity log | Cannot edit invoices, stock, or prices |
| **sales_manager** | Instructs & follows up | View all reps' activity/reports live; approve pending invoices; create & assign visit plans/tasks; edit routes & customers; reverse actions (§7); monitor cash boxes | Cannot manage users; cannot change prices |
| **warehouse_keeper** | Inventory | Manage main-warehouse stock; load/unload vans; stock adjustments (logged); approve van transfers; stock reports | No access to invoices, payments, customers |
| **sales_rep** | Field worker (main data enterer) | Own routes/customers only; check in/out; visits; sell from own van; collect cash; returns; expenses; export/share the invoice PDF | Sees nothing of other reps; no price edits; no direct stock edits |

Panel access: `/admin` → all roles except sales_rep (each sees only their permitted resources via Filament policies). `/app` → sales_rep only. Seed one user per role (`*@jawla.test` / strong seeded password printed by the seeder — not `password`).

---

## 6. Feature modules

Same as v1 §6 (admin: companies, users, products, stock, routes, customers, invoices, collections/returns, reports · rep: home, start work, customers, visit, sell, collect, return, expenses, end day), **plus:**

- **Tasks / follow-up:** sales_manager creates tasks (e.g. "visit customer X this week") assigned to a rep; rep sees tasks on home; manager sees completion status. Simple `tasks` table: `id, company_id, created_by, assigned_to, customer_id (nullable), title, note, due_date, status (open/done), completed_at`.
- **Activity Log window** (admin): filterable timeline of every action (who/what/when/before-after), with **Reverse** and **Re-apply** buttons per §7.

---

## 7. Activity log + Reverse/Redo (the safe "undo")

A literal undo button is unsafe in a financial system, so:

1. **Everything is logged.** spatie/laravel-activitylog on all models: creator, timestamp, old/new values.
2. **Reverse = compensating transaction, never deletion.** Available to sales_manager and system_viewer only, from the Activity Log window:
   - Reverse invoice → status `cancelled`, stock restored (+`stock_movements`), customer balance corrected. Original row remains.
   - Reverse payment → cash box and balances corrected with a logged counter-entry.
   - Reverse return / stock adjustment / van transfer → symmetric counter-movement.
   - Non-financial edits (customer name, etc.) → restore previous values from the log.
3. **Redo = re-apply** a reversed action (runs the original operation again through the same service, re-validated — e.g. stock must still be available).
4. Every reverse/redo is itself a logged activity linking to the original (`reversal_of`). Confirmation modal states exactly what will change. Time window: reversals allowed same-day by sales_manager, any time by system_viewer.

Implement in a `ReversalService`; only services mutate money/stock (§9).

---

## 8. Business rules (non-negotiable, enforced in services)

Identical to v1 §7: no negative van stock · atomic sales in `DB::transaction()` · money math (subtotal, VAT %, total, remaining) · collections update cash box + balances + invoice · returns restore stock & reduce balance · expenses reduce cash box · route lock with flagged custom visits · sequential server-side invoice/return numbers · stock changes only via `StockService` + movement rows.

---

## 9. Engineering rules (non-negotiable, every phase)

**Security**
- **Secrets only in `.env`**; `.env` in `.gitignore`; provide `.env.example` with placeholders. No API keys, tokens, or credentials ever in code, blade views, JS, or client responses. Nothing secret reaches the frontend.
- **No shell access paths:** never use `exec/shell_exec/system/passthru/proc_open`; disable them in production `php.ini`. No user input ever reaches a command line, `eval`, or raw SQL (Eloquent/query builder only, parameterized).
- **Validation everywhere:** every write goes through a Form Request or Livewire validation rules — server-side, regardless of any client-side checks. Whitelist fields (`$fillable`), never `$request->all()` into models.
- **Modern crypto only:** password hashing = **argon2id** (`config/hashing.php`); TLS 1.2+ enforced at Cloudflare/server; Laravel's built-in AES-256-GCM encryption for anything sensitive at rest; never MD5/SHA1 for anything security-related.
- **Sessions:** secure, httpOnly, sameSite=lax cookies; session regeneration on login; 12h absolute lifetime for admin, 16h for reps (field day); logout invalidates.
- **Rate limiting:** login `5/min per IP+email` with lockout backoff; all `/app` and `/admin` POST routes throttled (`60/min per user`); any future API `throttle:api`.
- **Webhooks (if any are ever added):** HMAC-SHA256 signature verification + timestamp tolerance ±5 min (prevents spoofing/replay). Outgoing webhooks signed the same way.
- **Headers:** force HTTPS redirect, HSTS, X-Content-Type-Options, X-Frame-Options DENY, referrer-policy, and a CSP (script-src 'self' + Vite assets).
- **Uploads:** images only (validated mime + size ≤ 2MB), stored outside webroot via Laravel storage, served through signed routes.
- CSRF on (default), XSS-safe via Blade escaping (never `{!! !!}` on user input).

**Performance (the vibe-coding failure list — all prevented)**
- **No N+1 queries:** `Model::preventLazyLoading(! app()->isProduction())` in `AppServiceProvider` — dev/CI throw on lazy loads; use eager loading (`with()`) everywhere.
- **Pagination on every list** — Filament tables paginate by default; rep lists use `simplePaginate(25)`. Never `->get()` an unbounded table.
- **Caching:** `config:cache`, `route:cache`, `view:cache`, `event:cache` in deploy script; dashboard aggregates cached 60s (`Cache::remember`); static assets fingerprinted by Vite + cached at Cloudflare.
- **Async:** anything slow (PDF batches, Excel export of big ranges, notifications) → queued jobs, with UI showing in-progress state.
- **Skeleton loaders** per §3 — no blank waits.

**Quality**
- **Error boundaries:** custom bilingual 403/404/419/500 pages; Livewire component errors render a friendly retry card, never a stack trace; `APP_DEBUG=false` in production; all exceptions → Sentry.
- **Logging:** daily rotating logs (14 days); log every auth event, permission denial, reversal, and stock adjustment with user id.
- **Monitoring:** Sentry for exceptions; UptimeRobot (free) hitting `/up` health endpoint; queue failure alerts.
- **Backups:** spatie/laravel-backup — nightly DB + weekly full, shipped off-server (S3-compatible), 30-day retention, monthly restore test documented in README.
- **Dependencies:** `composer audit` + `npm audit` in CI (§11); Dependabot enabled; pin major versions.

---

## 10. Testing strategy (Phase 13, but write tests alongside each phase)

- **Unit (Pest):** money math, VAT calc, `StockService` (negative-stock rejection), `ReversalService` symmetry, invoice numbering.
- **Feature/integration (Pest):** full sale flow (invoice + stock + movements + balance in one transaction, rollback on forced failure) · collection updates all three ledgers · return restores stock · role matrix (each role hitting each forbidden route → 403) · route lock · rate limiter.
- **E2E (Dusk or Playwright):** rep day: login → start work → route → visit (mock geolocation) → sell 3 items → collect → return → end day → numbers verified in admin. Admin: create product → load van → see stock. RTL smoke test (Arabic renders, direction correct).
- **Coverage target:** all §8 business rules covered; aim ≥70% on `app/Services`.
- CI runs the whole suite on every push (§11).

---

## 11. CI/CD & security automation (GitHub Actions)

Repo on GitHub, `main` protected (PRs only, CI must pass).

**`ci.yml` on every push/PR:** PHP 8.3 + Postgres service → `composer install` → **Pint** (style) → **Larastan level 6** (static analysis) → Pest suite → `composer audit` + `npm audit --audit-level=high` (fail on high/critical) → build assets.

**`security.yml` weekly + on release:** **OWASP ZAP baseline scan** (official GitHub Action) against a staging deployment — report uploaded as artifact, build fails on High alerts. GitHub **Dependabot** + **CodeQL** enabled.

**Manual (documented in README, not automated):** before go-live, a manual pass with **Burp Suite Community** on the auth flow, invoice endpoints, and IDOR checks (rep A trying rep B's resources), plus a full ZAP active scan on staging. Findings tracked as issues.

**Deploy:** push to `main` → deploy script (Forge or `deploy.sh`): pull, `composer install --no-dev`, migrate `--force`, cache configs/routes/views, restart queue, health-check `/up`, rollback on failure. Zero-downtime not required for v1.

---

## 12. Build phases

Phases 0–12 identical to v1 §8 (setup → schema → auth → admin core → rep shell → visits → sales → collections → returns → expenses/transfers → reports → PWA polish → seed/demo), **with these v2 modifications woven in:**

- **Phase 0.5 (new):** Design system per §3 — tokens, Blade components (`x-ds-button` with all six states, `x-ds-modal`, `x-ds-tooltip`, `x-ds-skeleton`, `x-ds-empty`), typography scale, 60/30/10 palette, semantic colors. All later phases use these.
- **Phase 2:** implement the **5-role** matrix (§5) instead of 3 roles; Filament policies per resource per role.
- **Phase 3:** add **warehouse_keeper** views; every stock action behind confirmation modal.
- **Phase 6–9:** all financial screens use confirmation modals, loading states, and are covered by feature tests as they're built.
- **Phase 10:** add the **Tasks/follow-up** module and the **Activity Log window with Reverse/Re-apply** (§7).
- **Phase 13 (new) — Test hardening:** complete the §10 suite; fix everything it finds; wire `ci.yml`.
- **Phase 14 (new) — Security & ops hardening:** §9 checklist audit end-to-end; security headers; rate limits verified; Sentry + backups + uptime monitor live; `security.yml` + ZAP baseline green; deploy script tested with rollback; README updated (setup, credentials, flows, restore procedure, Burp checklist).

**Definition of production-ready v1:** all phases done · both hard rules proven by tests (over-sell blocked; forced mid-transaction failure fully rolls back) · CI green including audits · ZAP baseline clean of High alerts · backups restoring successfully · bilingual RTL throughout · seeded demo from one command.

**Deferred to v2.1 (do not build):** full offline sync, Bluetooth printing, WhatsApp sending, AI route optimization, load balancer/multi-server (single VPS + Cloudflare comfortably serves 100+ concurrent users for this workload; revisit only if metrics say so).

---

## 13. Owner decisions — FINAL (locked, build accordingly)

1. **Tax regime: BOTH, switchable per company.** Each company row carries its own tax profile — add to `companies`: `country (enum: 'SA','EG')`, `zatca_enabled (bool)`. Behavior:
   - `country='EG'` → EGP, default `vat_percent=14.00`, standard invoice PDF with a simple QR (invoice number + total).
   - `country='SA'` → SAR, default `vat_percent=15.00`, `zatca_enabled=true` → invoice PDF QR is **ZATCA Phase 1 compliant**: Base64 TLV encoding of (1) seller name, (2) VAT registration number, (3) invoice timestamp ISO 8601, (4) total with VAT, (5) VAT amount. Implement in an `InvoiceQrService` with a per-country strategy; unit-test the TLV output.
   - All money displays use the company's currency symbol; VAT % always read from the company row, never hardcoded.
2. **Hosting: one VPS (Hetzner CX32 or DigitalOcean 4GB, Ubuntu 24) managed via Laravel Forge.** Chosen because the owner is non-technical: Forge handles server provisioning, TLS, deploy-on-push, queue workers, and scheduled backups from a dashboard. Total ~$30–40/mo. Cloudflare free tier in front.
3. **Scale: rep count is dynamic (HR creates reps), so hardcode no limits.** Architecture note: a single 4GB VPS + Postgres comfortably handles 50+ concurrent field reps for this workload. Add a `System health` note in the README: if reps exceed ~75 or dashboard queries slow past 1s, upgrade the VPS one tier before considering anything more complex. Rep creation must be self-service for hr_admin (van warehouse + cash box auto-created, §5) with zero developer involvement.

---

*End of guide v2. Begin at Phase 0; apply §3, §8, §9 from the first line of code.*
