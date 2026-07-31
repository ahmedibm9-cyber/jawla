# Jawla ERP — AI Model Assignment & Phase Complexity Analysis

> **⚠ HISTORICAL SNAPSHOT** — Generated 2026-07-12. Role counts and phase
> estimates reflect the codebase at that date. The system now has 11 roles and
> 975 tests. For current state, see `README.md`.

**Date:** 2026-07-12 (revised)
**Project:** Jawla (جولة) — Field Sales CRM/ERP for Global Plastic Company
**Plan:** OpenCode Go

## Available Models

| Model                 | Tier               | Strengths                                                                                 |
| --------------------- | ------------------ | ----------------------------------------------------------------------------------------- |
| **GLM-5.2**           | Frontier           | Laravel/PHP, large context, architecture, instruction-following                           |
| **GLM-5.1**           | Mid                | Previous GLM — cheaper, less capable                                                      |
| **Kimi K2.7 Code**    | Code-specialist    | Strong at code generation, refactoring, debugging                                         |
| **Kimi K2.6**         | Mid                | Older Kimi, solid code generation                                                         |
| **MiMo-V2.5-Pro**     | Mid-Pro            | Balanced reasoning + coding                                                               |
| **MiMo-V2.5**         | Mid                | General purpose                                                                           |
| **MiniMax M3**        | Mid                | General purpose, decent reasoning                                                         |
| **MiniMax M2.7**      | Budget             | Older, cheaper                                                                            |
| **Qwen3.7 Max**       | Frontier-reasoning | Strongest reasoning in the pool, excellent at edge cases, compliance, math, large context |
| **Qwen3.7 Plus**      | Mid                | Good reasoning, cost-efficient                                                            |
| **Qwen3.6 Plus**      | Budget-mid         | Older Qwen, budget option                                                                 |
| **DeepSeek V4 Pro**   | Code-optimized     | Excellent code generation, cost-efficient, strong at patterns                             |
| **DeepSeek V4 Flash** | Fast-budget        | Fastest, cheapest, good for boilerplate and bulk                                          |

---

## Step 1 — Project Understanding

### Vision

Bilingual (Arabic/English, RTL-first) field sales + distribution ERP replacing Odoo + Excel for a plastics trading company in Egypt. ~10 users. Saudi expansion planned v2.

### Scope

20 build phases (0–19), ~75 database tables, 7 user roles, 23 admin Filament resources, 18 rep PWA Livewire screens, full sales/stock/invoicing/purchasing/CRM/reporting system, Egypt ETA e-invoicing compliance.

### Tech Stack

Laravel 13 · PHP 8.3 · Filament 4 (admin) · Livewire 3 (rep PWA) · PostgreSQL 16 · Tailwind 3 · Spatie Permission · mpdf · simple-qrcode · spatie/simple-excel · Leaflet/OpenStreetMap

### Architecture

Monolithic Laravel app. Service layer with interfaces (StockService, InvoiceService, PaymentService, etc.). Multi-tenant via `BelongsToCompany` global scope. Domain events for cross-cutting concerns. Cancel-not-delete for transactions. bcmath for all money math.

### Current State (post Phase 0 fix commit)

- Phase 0: 95% complete (fixed: packages, colors, timezone, security headers, PG test DB)
- Phase 1: ~35% (27 of 46+ migrations, 21 of 46+ models, empty service stubs, empty enums)
- Phase 2: ~50% (auth works, but 5 roles instead of 7, wrong permission names)
- Phases 3–19: 0%

### Key Risk Areas

1. **Money/stock integrity** — atomic transactions, no negative stock, reversal logic
2. **Egypt ETA compliance** — full API integration, not just QR generation; guide's spec is wrong
3. **Multi-tenancy** — cross-company data leakage if scope is wrong
4. **Pricing chain** — multi-level (Accounts→Manager→Rep) range enforcement
5. **Schema correctness** — 46+ tables with precise column types, FKs, constraints

---

## Step 2 — Phase Breakdown

| Track                      | Phases                                               | Character                                           |
| -------------------------- | ---------------------------------------------------- | --------------------------------------------------- |
| **A. Foundation**          | 0 (done), 1a (infra), 1b (schema), 1c (models+tests) | Architecture-critical, high reasoning, huge context |
| **B. Auth & Access**       | 2 (roles), 3 (admin panel)                           | Pattern-heavy, moderate reasoning, high volume      |
| **C. Rep Field App**       | 4, 5, 6, 7                                           | UI + business logic, moderate complexity            |
| **D. Financial Core**      | 8, 9, 14                                             | Money/stock/compliance — highest risk               |
| **E. Supply Chain**        | 10, 11, 12                                           | Domain logic, moderate-high complexity              |
| **F. Operations & Polish** | 13, 16, 17, 18, 19                                   | CRUD, reporting, seeding — lower risk               |

---

## Step 3 — Phase-by-Phase Analysis

### Phase 1a — Architecture Foundation

**Purpose:** Multi-tenancy trait/scope/context, 10 PHP enums, 10 domain exceptions, 3 value objects (Money, GpsCoordinate, PriceRange), 7 service interfaces.

**Difficulty:** 8/10 — Designing contracts that won't need breaking changes. Getting multi-tenancy right.

**Importance:** 10/10 — Every model, every query, every service depends on this.

**Required Skills:** architecture, backend, PHP 8.3 enums/readonly, Laravel global scopes, DI binding, exception hierarchy.

**Error Risk:** **Critical** — Wrong multi-tenancy = cross-company data leakage. Wrong service interface = every Phase 8+ breaks.

**Context Requirement:** **Huge** — Full guide (1,719 lines) + reviews (104 findings) + codebase (50+ files) + plan. ~80k+ tokens.

**Recommended Model:** **GLM-5.2**

**Why:** Deep architectural reasoning over large context, PHP/Laravel-specific patterns, contract design. GLM-5.2 has the context window and the Laravel knowledge. This is its core strength.

**Alternative Models:**

- **Qwen3.7 Max** — Stronger at edge-case reasoning in contract design. Use as reviewer after GLM-5.2 builds it. Catches multi-tenancy bypass scenarios GLM-5.2 might miss.
- **Kimi K2.7 Code** — Strong at PHP code generation. Use for the boilerplate enum/exception classes once the design is approved.

---

### Phase 1b — Complete Database Schema (46+ migrations)

**Purpose:** Fix 16 existing migrations + create 30 new ones with precise columns, FKs, indexes, constraints, enums.

**Difficulty:** 7/10 — Precision-critical, not conceptually hard. Danger is drift.

**Importance:** 10/10 — The schema IS the system.

**Required Skills:** SQL, PostgreSQL features (partial indexes, CHECK constraints), Laravel migrations, attention to detail.

**Error Risk:** **Critical** — Wrong quantity type = can't handle fractional tons. Missing company_id = multi-tenancy can't scope.

**Context Requirement:** **Large** — §4 (45 table definitions) + §11.46 + existing 27 migrations + audit fix list. ~50k tokens.

**Recommended Model:** **GLM-5.2**

**Why:** Precise migration generation following the spec exactly. GLM-5.2 won't "improve" on the guide's schema. Strong instruction-following prevents drift.

**Alternative Models:**

- **DeepSeek V4 Pro** — Excellent at repetitive code generation, cost-efficient. Use for the 30 new migrations (more mechanical than the 16 fixes).
- **Qwen3.7 Max** — Use for the migration review pass. Catches subtle schema inconsistencies ("this FK references a table that doesn't exist yet").

---

### Phase 1c — Models, Factories, Seeders, Tests

**Purpose:** Fix 18 existing models + create ~30 new. 46+ factories. Rewrite RoleSeeder (7 roles, ~50 permissions). 6 test files.

**Difficulty:** 6/10 — Pattern-heavy. Each model follows the same template.

**Importance:** 9/10 — Wrong fillable = mass-assignment vulnerability. Missing BelongsToCompany = multi-tenancy hole.

**Required Skills:** Laravel Eloquent, PHP, testing, factory patterns.

**Error Risk:** **High** — Wrong casts = money as float. Wrong seeder = wrong RBAC.

**Context Requirement:** **Large** — All 46+ migration schemas + existing patterns + §12 permissions. ~50k tokens.

**Recommended Model:** **GLM-5.2**

**Why:** Model generation is repetitive PHP following established patterns. GLM-5.2 is fast, precise, and won't deviate. For 46+ models + 46+ factories, high output speed and strong pattern-matching are ideal.

**Alternative Models:**

- **DeepSeek V4 Pro** — Cost-efficient for 90+ files of pattern-heavy PHP. Use for factories (most mechanical).
- **Kimi K2.7 Code** — Strong at code generation in bulk. Use for the model classes.

---

### Phase 2 — Auth & Roles (rewrite to 7 roles, ~50 permissions, Policies)

**Purpose:** Rewrite RoleSeeder to 7 roles per §5, ~50 permissions. Create Policies for all models.

**Difficulty:** 5/10 — Transcription of §12 into a seeder + standard Policy classes.

**Importance:** 8/10 — Wrong RBAC = privilege escalation or blocked access.

**Required Skills:** Laravel authorization, Spatie Permission, Policy classes.

**Error Risk:** **High** — But §12 is exhaustive, so risk is lower if followed exactly.

**Context Requirement:** **Medium** — §5, §12, existing RoleSeeder, existing tests. ~20k tokens.

**Recommended Model:** **DeepSeek V4 Pro**

**Why:** This is a transcription task — the guide defines exactly what permissions each role gets. DeepSeek V4 Pro is excellent at "read the spec, write the code" at lower cost than GLM-5.2. Strong code generation, cost-efficient.

**Alternative Models:**

- **GLM-5.2** — Perfectly adequate, slightly more expensive.
- **DeepSeek V4 Flash** — For the Policy boilerplate classes (most repetitive).

---

### Phase 3 — Admin Panel Core (23 Filament Resources)

**Purpose:** 23 admin Filament Resources with forms, tables, filters, actions, relation managers.

**Difficulty:** 6/10 — Filament is configuration-over-code. But 23 resources is high volume.

**Importance:** 8/10 — Admin panel is where all master data is managed.

**Required Skills:** Filament 4, Laravel, form/table configuration, domain schema.

**Error Risk:** **Medium** — Wrong field type = bad UX. Nothing breaks the system.

**Context Requirement:** **Medium-Large** — Schema + §6 + §11.69 (Filament convention). ~30k tokens per batch.

**Recommended Model:** **GLM-5.2** (complex resources) + **DeepSeek V4 Pro** (simple resources)

**Why:** Split the 23 resources by complexity:

- GLM-5.2 handles: Invoices, Products, Customers, Users, Companies, GIT, Purchase Orders (8 resources — complex forms, relation managers, business logic)
- DeepSeek V4 Pro handles: Categories, Modes of Payment, Tax Templates, Bank Accounts, Routes, Warehouses, Expenses, Supplier Quotations, Alarms, Complaints, Data Migrations, Warehouse Import Logs, Naming Series, Product Prices, Price Lists (15 resources — simple CRUD)

This parallelization saves ~40% cost on the simple resources.

**Alternative Models:**

- **Kimi K2.7 Code** — Good at Filament resource generation. Use for the medium-complexity resources (Batches, Suppliers, Van Transfers).

---

### Phase 4 — Rep PWA Shell

**Purpose:** Home screen with tiles, Start Work, Today's visits, Add customer. Mobile-first with bottom nav.

**Difficulty:** 5/10 — Livewire + Blade + Tailwind. Simple logic.

**Importance:** 7/10 — Rep's entry point. Must work on mobile.

**Required Skills:** Livewire 3, Blade, Tailwind, mobile-first CSS, RTL.

**Error Risk:** **Medium** — Bad mobile UX. No data integrity risk.

**Context Requirement:** **Medium** — §6 rep features, §3 UI rules, existing layout. ~20k tokens.

**Recommended Model:** **GLM-5.2**

**Why:** Livewire + Blade + Tailwind + RTL is GLM-5.2's comfort zone. Mobile-first layout requires understanding Tailwind's RTL utilities.

**Alternative Models:**

- **MiMo-V2.5-Pro** — Balanced reasoning + coding. Use if GLM-5.2 is rate-limited.
- **Kimi K2.7 Code** — Strong at frontend code. Use for the Blade/Tailwind views.

---

### Phase 5 — Visit Flow with GPS

**Purpose:** GPS geofence (1 km haversine), auto-confirm arrival, visit report, end visit.

**Difficulty:** 6/10 — GpsCoordinate VO already designed. Livewire component calls it.

**Importance:** 8/10 — GPS confirmation proves the visit happened.

**Required Skills:** Livewire, JavaScript geolocation API, GPS math, state machines.

**Error Risk:** **High** — Wrong geofence = visits confirmed when they shouldn't be.

**Context Requirement:** **Medium** — §7.3, §4.18, GpsCoordinate VO. ~15k tokens.

**Recommended Model:** **GLM-5.2**

**Why:** GPS logic is in the value object. The Livewire component is a form + state machine. GLM-5.2 handles this well.

**Alternative Models:**

- **Qwen3.7 Max** — Better at edge-case reasoning in the geofence logic ("what if GPS is inaccurate? what if the customer moved?"). Use for the geofence test design.

---

### Phase 6 — Price Quotation & Pricing Chain

**Purpose:** Multi-level pricing: Accounts → Manager → Rep ± ranges. System blocks violations.

**Difficulty:** 8/10 — Three levels with mathematical constraints. Most complex business logic so far.

**Importance:** 9/10 — Price control is core. If a rep can sell outside range, company loses money.

**Required Skills:** Business logic, mathematical range checking, PriceRange VO, service layer.

**Error Risk:** **Critical** — Wrong range check = reps sell below cost.

**Context Requirement:** **Medium-Large** — §7.4, §4.20-4.21, PriceRange VO, PricingService interface. ~25k tokens.

**Recommended Model:** **GLM-5.2**

**Why:** Strong at implementing mathematical constraints in PHP. PriceRange VO already has `contains()`. GLM-5.2 wires them together and writes boundary tests.

**Alternative Models:**

- **Qwen3.7 Max** — Better at edge-case reasoning ("what if manager_plus is negative? what if base_price is zero?"). Use for test case design.
- **Kimi K2.7 Code** — Strong at the service implementation. Use as a second implementation to cross-check.

---

### Phase 7 — Proforma Invoice

**Purpose:** Rep creates proforma from quotation, enforces ± range, includes bank info, sequential number.

**Difficulty:** 6/10 — Pricing enforcement already built. New document type, simpler lifecycle.

**Importance:** 7/10 — Rep's primary sales tool. No stock deducted yet.

**Required Skills:** Laravel, Filament/Livewire, DocumentNumberService, PDF (mpdf).

**Error Risk:** **Medium** — Wrong price = customer dispute. No stock/money changes.

**Context Requirement:** **Medium** — §4.22-4.23, §7.6. ~15k tokens.

**Recommended Model:** **DeepSeek V4 Pro**

**Why:** Standard CRUD + PDF generation. Complex parts (pricing, numbering) are already in services. DeepSeek V4 Pro handles this efficiently at lower cost.

**Alternative Models:**

- **GLM-5.2** — Perfectly capable, slightly higher cost.
- **Kimi K2.7 Code** — Good at the PDF template (Blade + mpdf).

---

### Phase 8 — Sales & Invoicing (CRITICAL)

**Purpose:** Atomic sale: invoice + items + stock decrement + movements + customer balance in one `DB::transaction()`. PDF with ETA QR. Batch tracking. Forced-rollback test mandatory.

**Difficulty:** 9/10 — Most complex single phase. Touches 5 tables, 3 services, dispatches events, generates PDF, must roll back completely on failure.

**Importance:** 10/10 — This IS the ERP. If the sale flow is wrong, the company loses money or data.

**Required Skills:** Deep Laravel, DB transactions, service orchestration, domain events, PDF, testing, bcmath.

**Error Risk:** **Critical** — Partial transaction = orphaned stock, wrong balances, corrupted data.

**Context Requirement:** **Large** — §7.1-7.2, §7.11-7.12, §4.24-4.25, §11.1-11.3, §11.50-11.53, all services. ~40k tokens.

**Recommended Model:** **GLM-5.2** (implementation) + **Qwen3.7 Max** (review + test design)

**Why:** GLM-5.2 has the strongest Laravel transaction knowledge and service orchestration. It implements the sale flow. Then **Qwen3.7 Max reviews** the implementation and designs the forced-rollback adversarial test — its superior edge-case reasoning catches what GLM-5.2 might miss (what if StockService throws after Invoice is created? what if the event listener fails?).

**Alternative Models:**

- **Kimi K2.7 Code** — Use for debugging if the transaction has subtle issues. Strong at code analysis.

---

### Phase 9 — Collections, Returns & Cash Box

**Purpose:** Payment collection with allocation across invoices. Returns with stock restoration + balance reduction. Cash box tracking. All in transactions.

**Difficulty:** 8/10 — Payment allocation across multiple invoices. Returns must not mutate original invoice.

**Importance:** 9/10 — Money in and money back. Errors compound over time.

**Required Skills:** Financial logic, DB transactions, bcmath, allocation algorithms.

**Error Risk:** **Critical** — Wrong allocation = invoice shows unpaid when paid. Cash box drift.

**Context Requirement:** **Large** — §7.7-7.9, §4.30-4.34, payment allocation rules. ~35k tokens.

**Recommended Model:** **GLM-5.2** (implementation) + **Qwen3.7 Max** (review)

**Why:** Same as Phase 8 — money-path code. GLM-5.2 implements, Qwen3.7 Max reviews the subtle "return doesn't mutate invoice, balance goes negative = credit" logic.

**Alternative Models:**

- **Kimi K2.7 Code** — For debugging allocation edge cases.

---

### Phase 10 — Purchase Requests & Supplier Management

**Purpose:** Rep purchase requests, supplier quotation comparison, purchase orders, multi-currency.

**Difficulty:** 6/10 — Moderate CRUD + comparison view + multi-currency.

**Importance:** 7/10 — Wrong PO = wrong goods ordered. But no stock/money changes until receipt.

**Required Skills:** Filament, Laravel, multi-currency handling, comparison UI.

**Error Risk:** **Medium** — Wrong currency conversion = wrong PO totals.

**Context Requirement:** **Medium** — §4.36-4.39, §7.19. ~20k tokens.

**Recommended Model:** **DeepSeek V4 Pro**

**Why:** Moderate-complexity CRUD. Cost-efficient model is sufficient. The guide defines the schema precisely.

**Alternative Models:**

- **GLM-5.2** — If the multi-currency logic proves tricky.
- **DeepSeek V4 Flash** — For the simple supplier CRUD resources.

---

### Phase 11 — Goods in Transit & Landed Cost

**Purpose:** Track international shipments. Record shipping/customs/clearance costs. Distribute landed costs proportionally. Update stock + cost price on receipt.

**Difficulty:** 8/10 — Proportional cost distribution algorithm. 4-state lifecycle with partial receipt. Moving-average cost update.

**Importance:** 8/10 — Wrong landed cost = wrong product cost = wrong margins on every subsequent sale.

**Required Skills:** Mathematical algorithms, DB transactions, stock service, cost valuation, state machines.

**Error Risk:** **High** — Wrong cost distribution = silently wrong margins. Hard to detect.

**Context Requirement:** **Large** — §4.9-4.11, §7.14-7.15, §11.45, LandedCostService interface. ~30k tokens.

**Recommended Model:** **GLM-5.2**

**Why:** Proportional cost distribution requires precise bcmath math. GLM-5.2 handles this. The state machine + transaction is similar to Phase 8 patterns.

**Alternative Models:**

- **Qwen3.7 Max** — For verifying the proportional distribution math. Strongest at mathematical reasoning. Use for the test design.
- **Kimi K2.7 Code** — For the GIT Filament resource (UI is simpler than the math).

---

### Phase 12 — Batch Tracking, COA & Expiry

**Purpose:** Batch creation, COA PDF upload, stock per batch, expiry tracking, auto-alarm at 30 days, batch selection in transactions.

**Difficulty:** 6/10 — Batch is an extra dimension on stock. COA is a file upload. Expiry alarm is a scheduled job.

**Importance:** 7/10 — COA required by customers. Expiry prevents selling expired goods.

**Required Skills:** Laravel, secure file uploads, scheduled jobs, Filament, batch-aware queries.

**Error Risk:** **High** — Selling expired batch = liability. Block is in service layer.

**Context Requirement:** **Medium** — §4.6, §7.16-7.17, §11.56. ~20k tokens.

**Recommended Model:** **GLM-5.2**

**Why:** Batch tracking touches many tables but the pattern is consistent. GLM-5.2 handles the scheduled job and alarm efficiently.

**Alternative Models:**

- **DeepSeek V4 Pro** — For the batch-aware Filament resources (mechanical once the pattern is set).
- **Kimi K2.7 Code** — For the COA file upload handler.

---

### Phase 13 — Alarms & Notifications

**Purpose:** 7 alarm triggers, alarm dashboard grouped by type/severity, manager response workflow.

**Difficulty:** 5/10 — Event-driven (domain event → listener → AlarmService::raise). Dashboard is a Filament widget.

**Importance:** 7/10 — Notifications, not money/stock logic.

**Required Skills:** Laravel events/listeners, Filament widgets, notification dispatch.

**Error Risk:** **Medium** — Missing a trigger = manager doesn't know about a problem.

**Context Requirement:** **Medium** — §7.22-7.23, §4.40, AlarmService interface. ~15k tokens.

**Recommended Model:** **DeepSeek V4 Pro**

**Why:** Each alarm is a listener class — pattern-heavy. The 7 triggers are explicitly listed. Cost-efficient model is sufficient.

**Alternative Models:**

- **GLM-5.2** — If DeepSeek V4 Pro isn't available.
- **DeepSeek V4 Flash** — For the alarm dashboard widget (UI-only).

---

### Phase 14 — Egypt ETA E-Invoicing (CRITICAL)

**Purpose:** Full ETA API integration: OAuth2 auth, token refresh, JSON document submission, SHA256 content hash UUID, UUID chaining, URL-format QR, retry queue, cancellation.

**Difficulty:** 10/10 — Hardest phase. External government API, compliance, the guide's §11.23 is WRONG (says JSON, real format is URL with SHA256 UUID).

**Importance:** 10/10 — Non-compliant invoices = legal penalties.

**Required Skills:** API integration, OAuth2, SHA256 hashing, JSON serialization, error handling, queue jobs, compliance.

**Error Risk:** **Critical** — Wrong QR = invoices rejected. Wrong UUID = chain broken. Legal consequences.

**Context Requirement:** **Large** — Must hold the corrected §11.23 (from review, NOT the guide), EtaIntegrationService interface, invoice schema. ~30k tokens. **Must know the guide is wrong and follow the correction.**

**Recommended Model:** **Qwen3.7 Max** — **NOT GLM-5.2**

**Why:** This is the one phase where GLM-5.2 is NOT the best choice. Qwen3.7 Max is the strongest reasoning model in the available pool:

1. **Compliance reasoning** — Understanding WHY the URL format is correct (not TLV/JSON) and getting it right without testing against the real API.
2. **The guide is wrong** — Qwen3.7 Max is better at "follow the correction document, not the original guide" instructions. Its superior instruction-following under conflicting information is critical here.
3. **Hashing/serialization** — The SHA256 content hash requires precise byte-level JSON serialization. Qwen3.7 Max is more careful about encoding edge cases.
4. **External API reasoning** — Qwen3.7 Max has stronger ability to reason about external API contracts, OAuth2 flows, and error patterns that aren't in the codebase.

**Alternative Models:**

- **GLM-5.2** — Fallback. Can do it, but risk of subtle compliance error is higher. Use if Qwen3.7 Max is unavailable.
- **Kimi K2.7 Code** — For the retry queue job implementation (strong at async code patterns).

---

### Phase 16 — Reports & Dashboard

**Purpose:** Dashboard widgets, report queries (aggregations), Excel export, Leaflet visit map.

**Difficulty:** 6/10 — SQL aggregations + Filament chart widgets + Excel export.

**Importance:** 6/10 — Read-only. No data integrity risk.

**Required Skills:** SQL aggregation, Filament widgets, Excel export, Leaflet/JS.

**Error Risk:** **Low** — Wrong report = wrong number on screen.

**Context Requirement:** **Medium** — §6.23, existing schema. ~20k tokens.

**Recommended Model:** **DeepSeek V4 Pro**

**Why:** Report queries are SQL + Filament configuration — pattern-heavy. Cost-efficient model is sufficient.

**Alternative Models:**

- **DeepSeek V4 Flash** — For the simple widget configurations.
- **Qwen3.7 Max** — For the complex multi-join aggregation queries if performance is an issue.
- **Kimi K2.7 Code** — For the Leaflet map JS component.

---

### Phase 17 — Data Migration from Odoo

**Purpose:** Import wizards for customers, suppliers, products, invoices, stock, batches. Opening balances.

**Difficulty:** 7/10 — Source data format unknown. Import wizards need CSV/Excel parsing, validation, chunking.

**Importance:** 6/10 — One-time operation. Important for go-live.

**Required Skills:** Data import, CSV/Excel parsing, chunking, validation, error handling.

**Error Risk:** **High** — Wrong import = wrong opening balances. But re-runnable.

**Context Requirement:** **Medium** — §4.45, §8 Phase 17. ~15k tokens. Source format is unknown.

**Recommended Model:** **GLM-5.2**

**Why:** Good at data import patterns (chunking, upsert, validation). Handles the "unknown source format" uncertainty well — makes reasonable assumptions and documents them.

**Alternative Models:**

- **Qwen3.7 Max** — Better at handling the "unknown source format" uncertainty. Use if the Odoo export is complex.
- **DeepSeek V4 Pro** — For the import wizard UI (Filament form).

---

### Phase 18 — PWA Polish

**Purpose:** manifest.json (exists), service worker, standalone display, offline shell.

**Difficulty:** 4/10 — Standard PWA setup. Service worker is JS.

**Importance:** 5/10 — Nice-to-have. Not critical.

**Required Skills:** PWA, service workers, caching strategies, JS.

**Error Risk:** **Low** — Bad service worker = stale content. Fixable.

**Context Requirement:** **Small** — Existing manifest.json, §8 Phase 18. ~5k tokens.

**Recommended Model:** **DeepSeek V4 Flash**

**Why:** Standard PWA boilerplate. Fastest, cheapest model. No domain knowledge needed.

**Alternative Models:**

- **DeepSeek V4 Pro** — If the service worker needs custom caching strategies.
- **GLM-5.2** — Overkill but works fine.

---

### Phase 19 — Seed Data & Final Test Pass

**Purpose:** Comprehensive seeder: 2 companies, 3 routes, ~15 customers, 25+ products, 3 suppliers, 3 reps, batches, sample visits, quotations, proformas, invoices, GIT, alarms. All 7 roles.

**Difficulty:** 5/10 — Detailed recipe from §8 Phase 19. Creating interconnected data.

**Importance:** 7/10 — Demo is what the client sees.

**Required Skills:** Laravel seeders, factories, data relationships.

**Error Risk:** **Low** — Bad seed = confusing demo. Re-run anytime.

**Context Requirement:** **Medium** — §8 Phase 19, existing factories. ~15k tokens.

**Recommended Model:** **GLM-5.2**

**Why:** Good at creating realistic interconnected seed data. Holds the full seed list in context and generates coherent datasets where invoices reference real customers and products.

**Alternative Models:**

- **DeepSeek V4 Pro** — Cost-efficient for the repetitive factory calls.
- **Kimi K2.7 Code** — For the seeder logic if relationships are complex.

---

## Step 4 — Overall Workflow Recommendation

```
Phase 0 (done) ──────────────────────────────────────────── ✅
    │
Phase 1a (Architecture Foundation) ─────────────────────── GLM-5.2
    │                                          (review: Qwen3.7 Max)
    │
Phase 1b (Database Schema) ─────────────────────────────── GLM-5.2 (16 fixes)
    │                                          DeepSeek V4 Pro (30 new)
    │                                          (review: Qwen3.7 Max)
    │
Phase 1c (Models, Factories, Tests) ────────────────────── GLM-5.2 (models)
    │                                          DeepSeek V4 Pro (factories)
    │
Phase 2 (Auth & Roles) ─────────────────────────────────── DeepSeek V4 Pro
    │
Phase 3 (Admin Panel — 23 Resources) ───────────────────── GLM-5.2 (8 complex)
    │                                          DeepSeek V4 Pro (15 simple)
    │
    ├── Phase 4 (Rep PWA Shell) ─────────────────────────── GLM-5.2
    │   │
    │   Phase 5 (Visit Flow + GPS) ──────────────────────── GLM-5.2
    │   │                                      (test: Qwen3.7 Max)
    │   │
    │   Phase 6 (Price Quotation Chain) ─────────────────── GLM-5.2
    │   │                                      (test: Qwen3.7 Max)
    │   │
    │   Phase 7 (Proforma Invoice) ──────────────────────── DeepSeek V4 Pro
    │
Phase 8 (Sales & Invoicing) ────────────────────────────── GLM-5.2
    │  ⚠️ CRITICAL — Qwen3.7 Max reviews + designs rollback test
    │
Phase 9 (Collections, Returns, Cash Box) ───────────────── GLM-5.2
    │  ⚠️ CRITICAL — Qwen3.7 Max reviews
    │
Phase 10 (Purchase Requests & Suppliers) ───────────────── DeepSeek V4 Pro
    │
Phase 11 (GIT & Landed Cost) ───────────────────────────── GLM-5.2
    │  ⚠️ landed cost math — Qwen3.7 Max verifies
    │
Phase 12 (Batch Tracking, COA, Expiry) ─────────────────── GLM-5.2
    │
Phase 13 (Alarms & Notifications) ──────────────────────── DeepSeek V4 Pro
    │
Phase 14 (Egypt ETA E-Invoicing) ───────────────────────── Qwen3.7 Max ⚠️
    │  ⚠️ CRITICAL — GLM-5.2 is NOT the primary here
    │  (Kimi K2.7 Code for retry queue)
    │
Phase 16 (Reports & Dashboard) ─────────────────────────── DeepSeek V4 Pro
    │                                          (Kimi K2.7 Code for Leaflet)
    │
Phase 17 (Data Migration from Odoo) ────────────────────── GLM-5.2
    │
Phase 18 (PWA Polish) ──────────────────────────────────── DeepSeek V4 Flash
    │
Phase 19 (Seed Data & Final Tests) ─────────────────────── GLM-5.2
```

---

## Step 5 — Model Strength Matrix

| Model                 | Coding | Architecture | Reasoning | UI   | Backend | Debugging | Long Context | Speed | Cost Efficiency | Best Use Cases                                                                                                     |
| --------------------- | ------ | ------------ | --------- | ---- | ------- | --------- | ------------ | ----- | --------------- | ------------------------------------------------------------------------------------------------------------------ |
| **GLM-5.2**           | 9/10   | 9/10         | 8/10      | 7/10 | 9/10    | 8/10      | 9/10         | 8/10  | 7/10            | Laravel/PHP, service layer, transactions, multi-file orchestration, spec adherence                                 |
| **Qwen3.7 Max**       | 8/10   | 9/10         | 10/10     | 7/10 | 8/10    | 9/10      | 10/10        | 7/10  | 5/10            | Edge-case reasoning, compliance, math verification, test design, security review, "follow correction not original" |
| **DeepSeek V4 Pro**   | 9/10   | 7/10         | 7/10      | 6/10 | 8/10    | 7/10      | 8/10         | 9/10  | 9/10            | Pattern-heavy code, migration generation, CRUD resources, seeders, factories, cost-efficient bulk                  |
| **DeepSeek V4 Flash** | 7/10   | 5/10         | 6/10      | 6/10 | 7/10    | 6/10      | 7/10         | 10/10 | 10/10           | Boilerplate, config files, PWA, simple CRUD, fastest cheapest option                                               |
| **Kimi K2.7 Code**    | 9/10   | 7/10         | 7/10      | 7/10 | 8/10    | 9/10      | 8/10         | 9/10  | 7/10            | Code generation, refactoring, debugging, async patterns, frontend code                                             |
| **MiMo-V2.5-Pro**     | 7/10   | 7/10         | 7/10      | 7/10 | 7/10    | 7/10      | 7/10         | 8/10  | 7/10            | Balanced mid-tier, good fallback for any phase                                                                     |
| **Qwen3.7 Plus**      | 7/10   | 6/10         | 7/10      | 6/10 | 7/10    | 7/10      | 7/10         | 9/10  | 8/10            | Good reasoning at lower cost, mid-complexity tasks                                                                 |
| **GLM-5.1**           | 7/10   | 7/10         | 7/10      | 6/10 | 7/10    | 7/10      | 7/10         | 8/10  | 8/10            | Previous GLM, budget option for simple phases                                                                      |
| **MiniMax M3**        | 7/10   | 6/10         | 7/10      | 6/10 | 7/10    | 6/10      | 7/10         | 8/10  | 7/10            | General purpose mid-tier                                                                                           |
| **Kimi K2.6**         | 7/10   | 6/10         | 6/10      | 6/10 | 7/10    | 7/10      | 7/10         | 8/10  | 8/10            | Older Kimi, budget code generation                                                                                 |
| **Qwen3.6 Plus**      | 6/10   | 5/10         | 6/10      | 5/10 | 6/10    | 6/10      | 6/10         | 9/10  | 9/10            | Budget mid-tier                                                                                                    |
| **MiMo-V2.5**         | 6/10   | 5/10         | 6/10      | 6/10 | 6/10    | 6/10      | 6/10         | 9/10  | 8/10            | General purpose budget                                                                                             |
| **MiniMax M2.7**      | 5/10   | 4/10         | 5/10      | 5/10 | 5/10    | 5/10      | 5/10         | 9/10  | 10/10           | Cheapest, simplest tasks only                                                                                      |

### Key differentiators

- **GLM-5.2** is the best all-rounder. Strongest Laravel knowledge, large context, idiomatic PHP. Primary for 12 phases.
- **Qwen3.7 Max** is the best reasoner. Use for the one phase where reasoning > code generation (Phase 14 ETA), and for adversarial test design / review on the 4 critical phases (1a, 8, 9, 11).
- **DeepSeek V4 Pro** is the best value for code-heavy work. Use for 4 pattern-heavy phases (2, 10, 13, 16) + factory generation + simple Filament resources.
- **DeepSeek V4 Flash** is the cheapest. Use for mechanical phases (18) where domain knowledge isn't needed.
- **Kimi K2.7 Code** is the best debugger. Use for debugging complex transaction issues (Phase 8/9) and async patterns (Phase 14 retry queue).

---

## Step 6 — Bottleneck Detection

### Phases where weaker models will fail

| Phase  | Bottleneck                                                   | Risk     | Strongest model needed                         |
| ------ | ------------------------------------------------------------ | -------- | ---------------------------------------------- |
| **1a** | Multi-tenancy design — one missed edge case = data leakage   | Critical | GLM-5.2 (build) + Qwen3.7 Max (review)         |
| **1b** | 46+ migrations with precise types — integer vs decimal(12,3) | Critical | GLM-5.2 (fixes) + DeepSeek V4 Pro (new tables) |
| **8**  | Atomic sale transaction — partial commit = corrupted data    | Critical | GLM-5.2 (build) + Qwen3.7 Max (test design)    |
| **9**  | Payment allocation + return linkage — subtle balance math    | Critical | GLM-5.2 (build) + Qwen3.7 Max (review)         |
| **14** | ETA API integration — guide's spec is WRONG                  | Critical | Qwen3.7 Max (primary)                          |

### Phases where hallucinations are common

| Phase  | Hallucination risk                                   | Why                                     | Mitigation                                                                                   |
| ------ | ---------------------------------------------------- | --------------------------------------- | -------------------------------------------------------------------------------------------- |
| **1a** | Medium — may invent a "better" multi-tenancy pattern | Many blog posts, some wrong             | Stick to the review's C-2 spec                                                               |
| **8**  | High — may invent a "simpler" transaction pattern    | Many blog-post patterns, some incorrect | Forced-rollback test (designed by Qwen3.7 Max)                                               |
| **14** | **Critical** — may follow the guide's wrong §11.23   | Guide says JSON, correction says URL    | Feed the correction explicitly; Qwen3.7 Max handles "ignore original, use correction" better |

### Phases where context limits become problematic

| Phase  | Context load | Mitigation                                                                                       |
| ------ | ------------ | ------------------------------------------------------------------------------------------------ |
| **1a** | ~80k tokens  | GLM-5.2's context handles it. If not, read only §4, §7, §11.50, and review C-2/C-5/C-6/C-8/C-10. |
| **1b** | ~50k tokens  | Process in batches: master data → transactional → STEAL tables.                                  |
| **8**  | ~40k tokens  | Pre-load only relevant sections per step.                                                        |

### Phases where agent loops frequently occur

| Phase  | Loop risk                                       | Mitigation                                                   |
| ------ | ----------------------------------------------- | ------------------------------------------------------------ |
| **1b** | Medium — migration FK conflicts                 | Process in strict dependency order                           |
| **3**  | Low — Filament 4 API differs from training data | Check Filament 4 docs when uncertain                         |
| **14** | High — external API can't be tested             | Mock the ETA API in tests; implement against documented spec |

---

## Step 7 — Parallelization Strategy

```
Phase 1a (foundation) ──► Phase 1b (schema) ──► Phase 1c (models)
                                                    │
                                              Phase 2 (auth)
                                                    │
                    ┌───────────────────────────────┼──────────────────┐
                    │                               │                  │
            Phase 3 (admin)              Phase 4 (rep shell)    Phase 18 (PWA)
            ├── GLM-5.2 (complex)        │                       (independent)
            └── DeepSeek V4 Pro (simple) Phase 5 (visits)
                                    │
                            Phase 6 (pricing)
                                    │
                            Phase 7 (proforma)
                                    │
                            Phase 8 (sales) ◄── BLOCKING
                                    │
                    ┌───────────────┼───────────────┐
                    │               │               │
            Phase 9 (collect)  Phase 10 (PO)  Phase 11 (GIT)
                    │               │               │
                    └───────┬───────┘               │
                            │                       │
                    Phase 12 (batch) ◄──────────────┘
                            │
                    Phase 13 (alarms)
                            │
                    Phase 14 (ETA) ◄── BLOCKING (compliance)
                            │
                    Phase 16 (reports)
                            │
                    Phase 17 (migration)
                            │
                    Phase 19 (seed)
```

### Parallel opportunities

| Group | Phases                               | Why independent                       | Models                              |
| ----- | ------------------------------------ | ------------------------------------- | ----------------------------------- |
| A     | Phase 3 complex + Phase 3 simple     | Different resources, no shared files  | GLM-5.2 + DeepSeek V4 Pro           |
| B     | Phase 4 (rep shell) + Phase 18 (PWA) | PHP vs JS, no overlap                 | GLM-5.2 + DeepSeek V4 Flash         |
| C     | Phase 9 + Phase 10 + Phase 11        | Different domains, no shared services | GLM-5.2 + DeepSeek V4 Pro + GLM-5.2 |
| D     | Phase 13 + Phase 16                  | Read-only, no shared writes           | DeepSeek V4 Pro + DeepSeek V4 Pro   |

---

## Step 8 — Final Recommendation

### Phase assignment table (sorted by importance)

| Phase                             | Diff | Imp | Recommended Model                                     | Why                                                                                        | Backup Model    |
| --------------------------------- | ---- | --- | ----------------------------------------------------- | ------------------------------------------------------------------------------------------ | --------------- |
| **8 — Sales & Invoicing**         | 9    | 10  | **GLM-5.2** + Qwen3.7 Max (review)                    | Laravel transactions, service orchestration. Qwen3.7 Max designs the forced-rollback test. | Qwen3.7 Max     |
| **1a — Architecture Foundation**  | 8    | 10  | **GLM-5.2** + Qwen3.7 Max (review)                    | Multi-tenancy, contracts, VOs. Qwen3.7 Max catches edge cases.                             | Qwen3.7 Max     |
| **1b — Database Schema**          | 7    | 10  | **GLM-5.2** (fixes) + DeepSeek V4 Pro (new)           | 46+ migrations, precise types. Split by complexity.                                        | Qwen3.7 Max     |
| **14 — Egypt ETA**                | 10   | 10  | **Qwen3.7 Max**                                       | Strongest reasoning. Guide is wrong. Compliance. **GLM-5.2 is NOT primary.**               | GLM-5.2         |
| **9 — Collections & Returns**     | 8    | 9   | **GLM-5.2** + Qwen3.7 Max (review)                    | Payment allocation, return linkage. Money-path.                                            | Qwen3.7 Max     |
| **6 — Price Quotation**           | 8    | 9   | **GLM-5.2**                                           | Multi-level range enforcement, math constraints.                                           | Qwen3.7 Max     |
| **1c — Models, Factories, Tests** | 6    | 9   | **GLM-5.2** (models) + DeepSeek V4 Pro (factories)    | Pattern-heavy, 90+ files. Split by type.                                                   | Kimi K2.7 Code  |
| **11 — GIT & Landed Cost**        | 8    | 8   | **GLM-5.2** + Qwen3.7 Max (math verify)               | Proportional distribution, moving average.                                                 | Kimi K2.7 Code  |
| **2 — Auth & Roles**              | 5    | 8   | **DeepSeek V4 Pro**                                   | Transcription of §12 into seeder + Policies.                                               | GLM-5.2         |
| **3 — Admin Panel**               | 6    | 8   | **GLM-5.2** (8 complex) + DeepSeek V4 Pro (15 simple) | Filament resources, split by complexity.                                                   | Kimi K2.7 Code  |
| **5 — Visit Flow + GPS**          | 6    | 8   | **GLM-5.2**                                           | GPS geofence, state machine, Livewire.                                                     | Qwen3.7 Max     |
| **19 — Seed Data**                | 5    | 7   | **GLM-5.2**                                           | Interconnected realistic data, all tables.                                                 | DeepSeek V4 Pro |
| **4 — Rep PWA Shell**             | 5    | 7   | **GLM-5.2**                                           | Livewire + mobile-first Tailwind + RTL.                                                    | MiMo-V2.5-Pro   |
| **10 — Purchase Requests**        | 6    | 7   | **DeepSeek V4 Pro**                                   | Moderate CRUD + multi-currency.                                                            | GLM-5.2         |
| **12 — Batch Tracking**           | 6    | 7   | **GLM-5.2**                                           | Batch dimension, COA upload, expiry alarm.                                                 | DeepSeek V4 Pro |
| **13 — Alarms**                   | 5    | 7   | **DeepSeek V4 Pro**                                   | 7 event listeners + dashboard. Pattern-heavy.                                              | GLM-5.2         |
| **7 — Proforma**                  | 6    | 7   | **DeepSeek V4 Pro**                                   | CRUD + PDF, pricing already built.                                                         | GLM-5.2         |
| **17 — Data Migration**           | 7    | 6   | **GLM-5.2**                                           | Import wizards, unpredictable source.                                                      | Qwen3.7 Max     |
| **16 — Reports**                  | 6    | 6   | **DeepSeek V4 Pro** + Kimi K2.7 Code (Leaflet)        | SQL aggregations + widgets + map.                                                          | GLM-5.2         |
| **18 — PWA Polish**               | 4    | 5   | **DeepSeek V4 Flash**                                 | Standard PWA boilerplate. Cheapest.                                                        | DeepSeek V4 Pro |

### Model usage distribution

| Model                 | Primary phases                                    | Reviewer phases  | Rationale                                                                                             |
| --------------------- | ------------------------------------------------- | ---------------- | ----------------------------------------------------------------------------------------------------- |
| **GLM-5.2**           | 12 (1a, 1b, 1c, 3, 4, 5, 6, 8, 9, 11, 12, 17, 19) | —                | Best all-rounder: Laravel, PHP, large context, precise. The workhorse.                                |
| **Qwen3.7 Max**       | 1 (14)                                            | 4 (1a, 8, 9, 11) | Strongest reasoner. ETA compliance (primary). Adversarial test design + math verification (reviewer). |
| **DeepSeek V4 Pro**   | 4 (2, 10, 13, 16) + sub-primary (1b, 1c, 3, 7)    | —                | Best value. Pattern-heavy code, CRUD, factories, simple resources.                                    |
| **DeepSeek V4 Flash** | 1 (18)                                            | —                | Cheapest + fastest. PWA boilerplate.                                                                  |
| **Kimi K2.7 Code**    | 0                                                 | —                | Best debugger. Use for debugging Phase 8/9 transactions + Phase 14 async.                             |

### Cost optimization

- **GLM-5.2** handles 60% of primary work (architecture + money-path)
- **DeepSeek V4 Pro** handles 20% of primary + 30% of sub-primary work at ~40% lower cost
- **Qwen3.7 Max** reserved for 5% of primary (compliance) + 20% as reviewer — premium where it matters
- **DeepSeek V4 Flash** handles 5% (PWA) at ~80% lower cost
- **Kimi K2.7 Code** on-call for debugging — costs nothing if not needed

### The one key insight

**GLM-5.2 is the right default for 12 of 20 phases.** The ONE exception is **Phase 14 (Egypt ETA)** — the guide's spec is factually wrong, and the AI must follow a correction document. That requires **Qwen3.7 Max**, the strongest reasoning model available.

**Qwen3.7 Max as reviewer (not primary) on 4 critical phases** catches edge cases without paying the premium on every phase. It designs the forced-rollback test for Phase 8, verifies the landed cost math for Phase 11, and reviews the multi-tenancy design for Phase 1a.

**DeepSeek V4 Pro for 4 pattern-heavy phases** saves ~40% token cost with no quality loss — the guide defines the output, the model just transcribes it into code.

**DeepSeek V4 Flash for PWA** saves ~80% on a phase that needs no domain knowledge.
