# Jawla (جولة) — BETA Build Guide
## Scoped from Client Voice Messages · Target: Saturday July 18, 2026

**Sources of truth:**
- `Jawla_Beta_PRD_from_Voice_Messages.md` — Client's own words (AM1–AM9)
- `Jawla_Build_Guide_v1_Reference.md` — Locked technical reference (scope narrowed)

**This document is the single build plan for the beta.**
Do not build anything not listed here unless a blocking question is resolved.

---

## 1. Immediate Assessment — Current State

### Repository status (C:\projects\jawla)

| Commit | What | Status |
|---|---|---|
| `f035c78` | Phase 1 — initial 27 migrations + 21 models | ✅ Old baseline |
| `199db75` | Phase 2 — auth & roles (5 roles, wrong) | ⚠️ Superceded |
| `b17b6ad` | fix: phase 0 gaps | ✅ Done |
| `6cb67b6` | feat: phase 1a — architecture foundation | ✅ Done |
| `f0dbb5d` | feat: phase 1b — complete 46+ migrations | ✅ Done |
| (uncommitted) | Phase 1c — models, factories, RoleSeeder rewrite, StockServiceTest, CompanyIsolationTest | 🟡 In progress |

### What works now

| Layer | Status | Evidence |
|---|---|---|
| **Architecture** | ✅ | Multi-tenancy (BelongsToCompany trait + ActiveCompanyContext + middleware), 10 PHP enums filled, 10 domain exceptions, 3 value objects (Money, GpsCoordinate, PriceRange), 7 service interfaces bound. |
| **Database** | ✅ | 46+ tables on PostgreSQL 17. `migrate:fresh` clean. All columns, FKs, constraints, partial indexes per guide. `decimal(12,3)` on quantities, CHECK `>= 0` on stocks. |
| **Auth** | ✅ | Admin login (Filament /admin), rep login (/app). Rate limiting (5/min). Locale switching (AR↔EN RTL/LTR). Security headers. argon2id hashing. |
| **Models (existing)** | 🟡 | 18 updated (Company, Product, Customer, Invoice, InvoiceItem, ReturnRecord, ReturnItem, Payment, Expense, VanTransfer, VanTransferItem, CashBox, WorkSession, Visit, Stock, StockMovement, Route, Warehouse). All have HasFactory + BelongsToCompany where applicable. Correct fillable/casts/relationships. |
| **Models (new)** | ✅ | 30 new created (Batch, Supplier, ModeOfPayment, Alarm, GoodsInTransit, GoodsInTransitItem, LandedCost, PurchaseOrder, PurchaseOrderItem, PurchaseRequest, SupplierQuotation, PriceList, ProductPrice, CustomerGroup, Territory, NamingSeries, CompanyBankAccount, TaxTemplate, TaxTemplateLine, InvoiceTax, DailyVisitAssignment, VisitReport, PriceQuotationRequest, PriceQuotation, ProformaInvoice, ProformaInvoiceItem, OutOfStockRequest, Complaint, WarehouseImportLog, DataMigration). |
| **Services** | 🟡 | StockService implemented (increment/decrement/transfer/balance/reconcile). 6 others stubbed. |
| **Seeders** | ✅ | RoleSeeder rewritten to 7 roles with ~50 permissions in dot notation. DatabaseSeeder seeds 1 company + 7 users (one per role). |
| **Tests** | 🟡 | 14 auth/locale/role tests pass. 5 new StockServiceTest pass. 3 new CompanyIsolationTest pending (factory issue). Old RoleSeederTest needs update (was 5 roles). |

### What doesn't work yet

| Issue | Impact | Fix |
|---|---|---|
| CompanyIsolationTest fails | Factory FK violations | Fix CustomerFactory to use `Route::factory()` instead of `route_id => 1` |
| Old RoleSeederTest expects 5 roles | Tests fail | Already rewritten to 7 roles, just need to commit |
| Uncommitted Phase 1c changes | Work lost on crash | Need to commit |
| No Filament Resources | Admin panel is blank | Phase 3 not started |
| No Rep PWA logic | Livewire components are empty stubs | Phase 4 not started |
| No business logic beyond StockService | Can't sell, quote, etc. | Phase 5+ not started |

---

## 2. BETA Scope Definition

### What the client expects (from voice messages §9 suggested beta cut)

The client explicitly demands these workflows for the beta:

| # | Workflow | Source requirements | Priority |
|---|---|---|---|
| 1 | **Roles & Users** — Amr as admin, 7 roles with permissions | REQ-ROL-1 to REQ-ROL-8 | M |
| 2 | **Customer records** — GPS coordinates, pending approval, manager gate | REQ-CUS-1 to REQ-CUS-4 | M |
| 3 | **Daily visit assignment** — Manager assigns visits, rep sees day view | REQ-VST-1 to REQ-VST-3 | M |
| 4 | **GPS arrival confirmation** — Geofence 1–2 km, Confirmed Arrival button | REQ-VST-4 to REQ-VST-6 | M |
| 5 | **Visit reports** — Rep submits after visit | REQ-VST-7 | M |
| 6 | **Quotation request** — Rep submits → Manager sees Requested queue | REQ-PRC-4, REQ-PRC-5 | M |
| 7 | **Manager pricing** — Sets approved price + negotiation range | REQ-PRC-1 to REQ-PRC-3, REQ-PRC-6 | M |
| 8 | **Rep negotiation** — Within range, starts high, never below floor | REQ-PRC-7 | M |
| 9 | **Proforma invoice** — Rep issues directly, floor enforced, bank details auto | REQ-INV-1 to REQ-INV-4 | M |
| 10 | **Stock import** — Warehouse keeper imports daily file | REQ-STK-1, REQ-STK-2 | M |
| 11 | **Rep stock lookup** — Real-time availability search | REQ-STK-4, REQ-STK-5 | M |
| 12 | **Out-of-stock alarms** — Broadcaster to Finance + Sales Manager + Exec | REQ-ALM-1 to REQ-ALM-4 | M |
| 13 | **Complaint alarms** — Rep records, alarm generated, Manager responds | REQ-CRM-1 to REQ-CRM-3 | M |
| 14 | **Purchase offers** — Rep submits, dual review by Sales + Purchasing | REQ-PUR-1 to REQ-PUR-4 | M |
| 15 | **Reports** — Visit reports, quotation reports, proformas viewable by Finance/Management | REQ-RPT-1 to REQ-RPT-3 | M |

### What's explicitly OUT of beta (client's own words)

| Item | Client's reason | Source |
|---|---|---|
| Multi-currency | "Finance handles all of that. You don't need to worry" | AM3, AM5 |
| Nested range delegation | "we'll discuss those details later" | AM2 |
| Automated inventory integration | "until we later automate" | AM2 |
| Exact ± limits | "will later be configured" | AM6 |
| Van stock / cash box / returns / expenses | Not in voice messages | PRD §8 |
| Batches / COA | Not in voice messages | PRD §8 |
| Goods-in-transit / landed cost | Not in voice messages | PRD §8 |
| Egypt ETA e-invoicing | Not in voice messages | PRD §8 |

### Key technical specifications for beta

| Spec | Beta decision | Source |
|---|---|---|
| Currency | EGP only. Build money columns per guide but UI exposes EGP only | REQ-TEC-5 + PRD §8 C3 |
| Pricing enforcement | Floor only until Q1/Q2 resolved. Configurable plus/minus stored but only floor enforced in beta | REQ-PRC-7 + Q1/Q2 pending |
| Geofence radius | 1.5 km (midpoint of 1–2 km). Behavior out-of-range: warn but allow manual confirm (flagged) — client must sign off | REQ-VST-5 + Q3 pending |
| Stock import format | Require sample file from client before implementing (Q4 blocker) | REQ-STK-2 + Q4 |
| Purchase review | Sequential: Sales reviews first, then Purchasing. Either can reject (default until Q5 resolved) | REQ-PUR-3 + Q5 |
| Complaint lifecycle | Simple: open → in_progress → resolved/closed. Manager only. No customer feedback loop | REQ-CRM-1 to REQ-CRM-3 + Q6 |
| Proforma upper limit | Not enforced in beta (Q2 pending). Floor enforced per REQ-INV-3 | AM9 + Q2 |

---

## 3. BETA Build Phases (narrowed from the v1 guide)

### What to build (in order)

| BETA Phase | v1 Guide Ref | What to build | Depends on |
|---|---|---|---|
| **B0** | §0, §8 p0 | ✅ Already done — Laravel + PostgreSQL + packages + auth | — |
| **B1** | §8 p1, p1a, p1b, p1c | 🟡 Foundation: multi-tenancy (done), enums (done), exceptions (done), VOs (done), service interfaces (done), migrations (done), models (done), seeders (done), factories (in progress). Need: finish + commit Phase 1c. | — |
| **B2** | §8 p2 | 🟡 Auth & Roles: 7-role seeder rewritten (uncommitted). Need: commit, update tests to reflect 7 roles. Admin/user seed data. Rep `/app` access + non-rep `/admin` access. | B1 |
| **B3** | §8 p3 (narrowed) | 🔴 Admin Panel: Filament Resources for Companies, Users, Products, Customers (with GPS), Routes, Stock (import). Skip: Suppliers, Batches, GIT, Purchasing, Alarms (Phase B9 covers separately), Proformas (B8) | B2 |
| **B4** | §8 p4 (narrowed) | 🔴 Rep PWA: Home screen (today's visits, quick actions), Customer add (pending), Stock search (live lookup). Mobile-first with bottom nav. | B3 |
| **B5** | §8 p5 | 🔴 Visits + GPS: Rep sees daily assignments → picks customer → GPS geofence check → Confirmed Arrival button → Visit report form. | B3, B4 |
| **B6** | §8 p6 (narrowed) | 🔴 Quotation + Pricing: Rep requests quote → Manager sees Requested queue → Sets price + ± range → Rep negotiates within range. PricingService implemented. | B3, B5 |
| **B7** | §8 p7 (narrowed) | 🔴 Proforma: Rep creates proforma from confirmed quotation → Floor price enforced (REQ-INV-3) → Bank details auto-included (REQ-INV-4) → No conversion to invoice (beta only). | B6 |
| **B8** | §8 p10 (narrowed) | 🔴 Purchase Offers: Rep submits purchase offer (supplier + product + price) → Sales reviews → Purchasing reviews. Simple: sequential, either can reject. | B3 |
| **B9** | §8 p13 (narrowed) | 🔴 Alarms: Out-of-stock request → alarm broadcast to Finance + Sales Manager + Executive. Complaint → alarm to Sales Manager. AlarmService implemented. Dashboard grouped by type/severity. | B3, B5 |
| **B10** | §8 p10 + p16 (narrowed) | 🔴 Reports: Visit reports list, Quotation reports list, Proforma invoices list viewable by Finance/Management. | B3, B5, B6, B7 |

### What is SKIPPED for beta (roadmap, not beta)

| v1 Phase | What | Why |
|---|---|---|
| 8 (full) | Van stock sales, atomic invoicing, PDF | Not in voice messages |
| 9 | Collections, returns, cash box | Not in voice messages |
| 10 (full) | Supplier quotation comparison, Purchase Orders | Only the rep purchase OFFER is in beta |
| 11 | Goods in transit, landed cost | Not in voice messages |
| 12 | Batch tracking, COA | Not in voice messages |
| 14 | Egypt ETA e-invoicing | Not in voice messages |
| 15 | Inter-company (v2) | Deferred |
| 17 | Data migration from Odoo | Post-beta |
| 18 | PWA polish | Post-beta (works as web app) |
| 19 | Seed data | Post-beta |

---

## 4. Blocking Issues & Risks

### ⛔ Blockers (must resolve before building)

| # | Question | Blocks | Client deadline | Assumed default |
|---|---|---|---|---|
| Q1 | Pricing math contradiction (1000 ±100 vs start 1200) | Proforma enforcement | Wed/Thu | Beta: enforce floor only. Ceiling deferred to Q1 resolution. |
| Q2 | Proforma upper limit? | Pricing engine | Wed/Thu | Beta: no upper limit. Floor only per REQ-INV-3. |
| Q3 | Geofence exact radius + out-of-range behavior | Visit flow | Wed/Thu | Beta: 1.5 km, warn + allow manual confirm (flagged). Client must sign off. |
| Q4 | Stock import file format sample | Stock import | Immediate | Must request sample from client before implementing. |

### ▫ Non-blocking (can proceed)

| # | Question | Risk | Default |
|---|---|---|---|
| Q5 | Purchase review: sequential or parallel? | Moderate | Beta: sequential (Sales → Purchasing), either can reject |
| Q6 | Complaint lifecycle | Low | Beta: open → in_progress → resolved/closed |
| Q7 | Customer rejection path | Low | Beta: record stays with rejection_reason visible to rep |
| Q8 | Quotation "reports" definition | Low | Beta: display quotation documents, not analytical |
| Q9 | Visit report content | Low | Beta: free text only per §4.19 schema |
| Q10 | Beta demo priority order | Low | Per §9 of PRD: visits → quotes → proforma → alarms |

---

## 5. Daily Checkpoint Plan (Mon July 13 → Sat July 18)

### Monday July 13 (today)

| Task | Status | Owner |
|---|---|---|
| **Commit Phase 1c** (models, factories, seeder, tests — what's uncommitted) | 🟡 In progress | OpenCode |
| Fix CompanyIsolationTest factory | 🟡 | OpenCode |
| Fix RoleSeederTest (7 roles) | 🟡 | OpenCode |
| Ensure all existing tests pass (24 tests) | 🟡 | OpenCode |
| **Create BETA Build Guide** (this document) | ✅ Done | OpenCode |
| Send Q4 (stock format sample) to client | 🔴 | Ahmed (human) |

### Tuesday July 14

| Task | Status | Owner |
|---|---|---|
| **Phase B2** — Rewrite RoleSeeder commit + auth verification | 🔴 | OpenCode |
| **Phase B3** — Admin Filament Resources: Companies, Users (with role assignment), Products, Customers (GPS map), Routes, Stock (view + import) | 🔴 | OpenCode |
| Send Q1–Q3 (pricing, geofence) to client for Wed/Thu response | 🔴 | Ahmed (human) |
| Demo check: can admin log in, create product, create customer with GPS, view stock? | 🔴 | OpenCode |

### Wednesday July 15 (client expects progress answer)

| Task | Status | Owner |
|---|---|---|
| **Phase B4** — Rep PWA: Home screen (greeting + visit count + quick actions), Customer add form (pending), Stock search (live query) | 🔴 | OpenCode |
| **Phase B5** — Visit flow: Today's assignments list → GPS geofence → Confirmed Arrival → Visit report submit | 🔴 | OpenCode |
| Receive client answers to Q1–Q4 (pricing, geofence, stock format) | ⬜ | Ahmed (human) |
| **Progress demo prep:** Show admin panel + rep app with visit flow + stock search | 🔴 | Ahmed + OpenCode |
| Iterate on stock import if sample file received | 🔴 | OpenCode |

### Thursday July 16

| Task | Status | Owner |
|---|---|---|
| **Phase B6** — Quotation + Pricing: Rep request → Manager Requested queue → Set price + range → Rep negotiates | 🔴 | OpenCode |
| **Phase B7** — Proforma: Rep creates proforma → Floor enforced → Bank details auto → PDF | 🔴 | OpenCode |
| Apply Q1/Q2 answers if received (ceiling enforcement, pricing math) | ⬜ | OpenCode |

### Friday July 17

| Task | Status | Owner |
|---|---|---|
| **Phase B8** — Purchase offers: Rep submits → Sales review → Purchasing review | 🔴 | OpenCode |
| **Phase B9** — Alarms: Out-of-stock request + complaint → alarm broadcast → Manager dashboard | 🔴 | OpenCode |
| **Phase B10** — Reports: Visit/quotation/proforma lists viewable by Finance/Management | 🔴 | OpenCode |
| End-to-end smoke test: full rep day (log in → visit → check stock → quote → proforma → report) | 🔴 | OpenCode |

### Saturday July 18 (delivery)

| Task | Status | Owner |
|---|---|---|
| Final bug fixes | 🔴 | OpenCode |
| Seed demo data (1 company, 3 routes, ~10 customers with GPS, ~10 products, 3 reps, sample visits/quotations/proformas, sample stock) | 🔴 | OpenCode |
| README with credentials and demo flow | 🔴 | OpenCode |
| Package for client demo | 🔴 | OpenCode |
| **Client delivery** | 🔴 | Ahmed + OpenCode |

---

## 6. Task Breakdown (detail)

### Phase B1 — Finish Foundation (today, 2–4 hours)

- [ ] Fix CustomerFactory to use `Route::factory()` (not `route_id => 1`)
- [ ] Fix ProductFactory to use `Company::factory()` + `ProductCategory::factory()`
- [ ] Fix CompanyIsolationTest (factory issues resolved)
- [ ] Update RoleSeederTest to assert 7 roles (already rewritten, just need to commit)
- [ ] Verify 24+ tests pass
- [ ] **Commit: `feat: phase 1c — models, factories, seeders, tests`**

### Phase B2 — Auth & Roles (today, 1 hour — depends on B1)

- [ ] Commit RoleSeeder rewrite (7 roles with ~50 permissions)
- [ ] Commit DatabaseSeeder (1 company + 7 role users)
- [ ] Ensure Filament `/admin` accessible to non-rep roles
- [ ] Ensure `/app` accessible to rep role only
- [ ] Update AdminLoginTest to use `admin` role (not `hr_admin`)
- [ ] Verify all auth tests pass
- [ ] **Commit: `fix: phase 2 — correct 7 roles, 50 permissions, admin seed`**

### Phase B3 — Admin Panel (Tuesday, 4–6 hours — depends on B2)

**Companies Resource:**
- [ ] List page: name_ar, name_en, abbr, tax_number, vat_percent, is_active
- [ ] Create/Edit form: all fields from §4.1
- [ ] Leaflet map on address (if GPS fields added)

**Users Resource:**
- [ ] List page: name, email, employee_code, roles, is_active
- [ ] Create/Edit form: company_id, name, email, phone, employee_code, password, is_active, roles
- [ ] Auto-create van warehouse + cash box on rep creation

**Products Resource:**
- [ ] List page: sku, name_ar, name_en, unit, price, cost, vat_applicable, is_active
- [ ] Create/Edit form: all fields from §4.5
- [ ] Cost price visible to accounts/admin only (Gate check)

**Customers Resource:**
- [ ] List page: code, name_ar, name_en, phone, route, status
- [ ] Create/Edit form: all fields from §4.14
- [ ] Leaflet location picker for latitude/longitude
- [ ] Status filter: pending/approved/rejected
- [ ] Approval action for manager

**Routes Resource:**
- [ ] List page: name_ar, name_en, region, is_active
- [ ] Create/Edit form with customer + user assignment (BelongsToMany)

**Stock Resource:**
- [ ] List page: warehouse, product, quantity, batch (if applicable)
- [ ] Stock import page: CSV upload (SKU, quantity, batch optional)
- [ ] Stock export: Excel download

### Phase B4 — Rep PWA (Wednesday, 4–6 hours — depends on B3)

**Home Screen:**
- [ ] Greeting with rep name
- [ ] Tiles: Today's visits count, Pending quotations, Pending customers, Cash box (read-only for beta)
- [ ] Quick actions: Start Work, Stock Search, Add Customer, Log Complaint

**Customer Add:**
- [ ] Form: name_ar, name_en, phone, address, GPS (auto-detected from browser)
- [ ] Status = pending, added_by = current rep
- [ ] Success message: "Customer submitted for approval"

**Stock Search:**
- [ ] Search bar (by SKU or name)
- [ ] Results: product info + warehouse stock quantity
- [ ] Must work mid-conversation (fast, mobile-friendly)

### Phase B5 — Visit Flow (Wednesday, 4–6 hours — depends on B3 + B4)

**Start Work:**
- [ ] Rep taps "Start Work" → work_session created with GPS

**Today's Visits:**
- [ ] Sorted cards: customer name, code, address, visit sequence
- [ ] Pending/Completed/Missed status badges

**Visit Execution:**
- [ ] Tap visit → GPS check against customer coordinates
- [ ] Within 1.5 km → "Confirmed Arrived ✅" button appears
- [ ] Outside → warning with distance → allow manual confirm (flagged)
- [ ] Save checkin_latitude, checkin_longitude, checkin_at, arrival_confirmed, arrival_confirmed_at

**Visit Report:**
- [ ] After arrival, rep fills: summary, customer_feedback, action_taken, follow_up_needed
- [ ] Submit → visit status = closed → visit_report saved

### Phase B6 — Quotation Chain (Thursday, 4–6 hours — depends on B5)

**Rep Side:**
- [ ] "Request Price" action during visit
- [ ] Select product + quantity → submit quotation request
- [ ] See status of own quotation requests

**Manager Side (Filament):**
- [ ] "Requested" queue — list of pending quotation requests
- [ ] Open → see customer, product, requested quantity
- [ ] Set: approved price + manager_plus + manager_minus → "Priced" status
- [ ] Give rep range: rep_plus, rep_minus (≤ manager range due to nested delegation, but beta: equal)

**Rep Negotiation:**
- [ ] See manager's price + range
- [ ] Price slider/input: starts at upper end, never below floor
- [ ] "Confirm Price" → quotation status = confirmed

### Phase B7 — Proforma (Thursday, 2–4 hours — depends on B6)

**Rep Side:**
- [ ] "Create Proforma" from confirmed quotation
- [ ] System checks: price ≥ floor (REQ-INV-3)
- [ ] Auto-populates: customer info, product, quantity, price, bank details (REQ-INV-4)
- [ ] Proforma number auto-generated (PF-GPC-2026-00001)
- [ ] Status: sent, can be viewed

**Admin/Manager/Finance View:**
- [ ] Proforma list with status filter
- [ ] View proforma details + items

### Phase B8 — Purchase Offers (Friday, 2–4 hours — depends on B3)

**Rep Side:**
- [ ] "Purchase Offer" action
- [ ] Form: supplier name, product, quantity, offered price, currency (EGP only for beta)

**Sales Management Review (Filament):**
- [ ] Pending offers list
- [ ] Review → approve/reject → review_notes

**Purchasing Review (Filament):**
- [ ] Offers approved by Sales
- [ ] Review → approve/reject → review_notes

### Phase B9 — Alarms (Friday, 2–4 hours — depends on B3 + B5)

**Out-of-Stock Alarm:**
- [ ] Rep: "Flag Urgent" button on out-of-stock request
- [ ] System creates Alarm record (type=out_of_stock_request, severity=critical)
- [ ] Alarm visible to: Finance, Sales Manager, Executive

**Complaint Alarm:**
- [ ] Rep: "Log Complaint" → customer, complaint_type, description
- [ ] System creates Complaint record + Alarm (type=customer_complaint, severity=critical)
- [ ] Alarm visible to: Sales Manager

**Alarm Dashboard (Filament):**
- [ ] Grouped by type/severity
- [ ] Sales Manager: acknowledge → resolve actions
- [ ] Colors: red (critical), yellow (warning), blue (info)

### Phase B10 — Reports (Friday, 1–2 hours — depends on B5, B6, B7)

**Viewable by Finance/Management/Sales Manager:**
- [ ] Visit Reports list (filterable by rep, date, customer)
- [ ] Quotation Reports (all quotations with status/price)
- [ ] Proforma Invoices list (all proformas, filterable)

---

## 7. Architecture Constraints for Beta

These are non-negotiable even in beta:

1. **Multi-tenancy** — `BelongsToCompany` trait on all models with `company_id`. Active via middleware.
2. **Service layer** — No controllers touching Eloquent directly for business writes.
3. **Domain exceptions** — Use exception hierarchy, bilingual error messages.
4. **bcmath money** — All money arithmetic via `Money` value object.
5. **Transactions** — All writes to multiple tables in `DB::transaction()`.
6. **Bilingual AR/EN** — Every UI string via `trans()`, every error via key.
7. **RTL** — `dir="rtl"` when Arabic, `dir="ltr"` when English.
8. **Policies** — Every Filament resource gated by Policy.
9. **Tests** — Every business rule has a test.
10. **No `.env` secrets** — All sensitive values in `.env`, never in code.

---

## 8. Technical Stack (same as v1, from §2)

| Layer | Choice | Reason |
|---|---|---|
| Backend | Laravel 13 + PHP 8.3 | ✅ Installed |
| Admin | Filament 4 | ✅ Installed |
| Rep UI | Livewire 3 + Tailwind 3 + Alpine.js | ✅ Installed |
| Database | PostgreSQL 16 | ✅ Configured |
| Auth | Spatie Permission | ✅ Installed, 7 roles seeded |
| PDF | mpdf/mpdf | ✅ Installed (replaced dompdf) |
| QR | simplesoftwareio/simple-qrcode | ✅ Installed |
| Maps | Leaflet + OpenStreetMap | ⚠️ Not yet installed (Phase B3) |
| Excel | spatie/simple-excel | ✅ Installed |
| Queue | Database driver | ✅ Configured |

---

*This document is the beta build plan. Execute phases B1→B10 in order. Commit after each phase. Do not build anything outside beta scope.*
