# Jawla Repository Compliance & Progress Audit

> **⚠ HISTORICAL SNAPSHOT** — Generated 2026-07-12 (2 commits into development).
> Completion estimates (~8%) and all statistics reflect the codebase at that
> date. The system has since been fully built. For current state, see `README.md`
> and `docs/ARCHITECTURE_CURRENT.md`.

**Date:** 2026-07-12
**Auditor:** Principal Software Architect / Staff Laravel Engineer / ERP Consultant
**Guide:** `Jawla_Build_Guide_v1_Reference.md` (1,719 lines, the single source of truth)
**Repository:** `C:\projects\jawla` (2 commits: Phase 1 + Phase 2)
**Method:** Every file read. Every migration inspected. Every model, service, test, and config file examined. No guessing.

---

# Executive Summary

| Metric                                         | Value                                                                            |
| ---------------------------------------------- | -------------------------------------------------------------------------------- |
| Overall Completion                             | **~8%**                                                                          |
| Production Readiness                           | **0%**                                                                           |
| Critical Blockers                              | **12**                                                                           |
| Major Risks                                    | **18**                                                                           |
| Technical Debt Score                           | **3/10** (low debt because little code exists, but what exists is non-compliant) |
| Architecture Score                             | **4/10**                                                                         |
| Code Quality Score                             | **5/10**                                                                         |
| Maintainability Score                          | **4/10**                                                                         |
| ERP Compliance Score                           | **2/10**                                                                         |
| Likelihood of successful production deployment | **None — not a deployable system**                                               |

> **If this repository were handed to a senior engineering team today, would they approve it for production?**
>
> **No.** This is an early-stage scaffold with auth working and 27 of 45+ required migrations created. No business logic is implemented. No Filament resources exist. No stock, sales, invoicing, purchasing, or reporting functionality exists. The schema that does exist deviates from the guide in ~15 significant ways (wrong roles, wrong colors, wrong quantity types, missing columns, wrong enum values). The codebase follows `docs/ROLES_MATRIX.md` (5 roles) instead of the guide's §5 (7 roles) — a known cross-document conflict that was flagged in prior reviews but never resolved.

---

# Phase Progress

| Phase                         | Goal                                        | DoD                                                                          | Completion | Status             |
| ----------------------------- | ------------------------------------------- | ---------------------------------------------------------------------------- | ---------- | ------------------ |
| 0 — Project setup             | Running Laravel app with stack              | `artisan serve` runs; `/admin` login in Arabic RTL; `/app` shows placeholder | **70%**    | 🟡 Mostly Complete |
| 1 — Database & models         | All tables and models                       | `migrate:fresh` clean; Tinker can create company→user→product→stock          | **35%**    | 🟠 Partial         |
| 2 — Auth & roles              | 7 roles with access control                 | Each role logs in; rep cannot open `/admin`                                  | **50%**    | 🟠 Partial         |
| 3 — Admin panel core          | Master data management                      | Admin creates companies, products, customers, loads stock                    | **0%**     | 🔴 Not Started     |
| 4 — Rep PWA shell             | Rep daily workflow start                    | Rep sees assigned visits, starts work                                        | **5%**     | 🔴 Not Started     |
| 5 — Visit flow with GPS       | Visit with arrival confirmation             | GPS confirms, report submitted                                               | **0%**     | 🔴 Not Started     |
| 6 — Price quotation           | Accounts→Manager→Rep pricing                | Rep requests price, manager sets range                                       | **0%**     | 🔴 Not Started     |
| 7 — Proforma invoice          | Rep creates proforma invoices               | Proforma within price range                                                  | **0%**     | 🔴 Not Started     |
| 8 — Sales & invoicing         | Field invoice creation                      | Invoice PDF with QR, stock deducted                                          | **0%**     | 🔴 Not Started     |
| 9 — Collections & returns     | Payments and returns                        | Rep collects, returns, cash box reconciles                                   | **0%**     | 🔴 Not Started     |
| 10 — Purchase requests        | Rep purchase requests + supplier comparison | Purchasing compares offers, creates PO                                       | **0%**     | 🔴 Not Started     |
| 11 — Goods in transit         | International shipments + landed cost       | Shipment received, cost distributed                                          | **0%**     | 🔴 Not Started     |
| 12 — Batch tracking           | Full batch lifecycle                        | Batch received/sold/returned, COA, expiry alarm                              | **0%**     | 🔴 Not Started     |
| 13 — Alarms                   | Automatic alerts                            | Out-of-stock → red alarm                                                     | **0%**     | 🔴 Not Started     |
| 14 — Egypt ETA e-invoicing    | Compliant invoices                          | ETA QR scans correctly                                                       | **0%**     | 🔴 Not Started     |
| 15 — Inter-company (v2)       | Deferred                                    | N/A                                                                          | N/A        | N/A                |
| 16 — Reports & dashboard      | Full visibility                             | Admin exports to Excel                                                       | **0%**     | 🔴 Not Started     |
| 17 — Data migration from Odoo | Migrate existing data                       | Opening balances verified                                                    | **0%**     | 🔴 Not Started     |
| 18 — PWA polish               | Installable app                             | Add to Home Screen works                                                     | **5%**     | 🔴 Not Started     |
| 19 — Seed data                | Demo-ready system                           | `migrate:fresh --seed` fully explorable                                      | **5%**     | 🔴 Not Started     |

---

# Architecture Review

| Component                      | Guide Requirement                | Actual                                                                                                                                                                                 | Status                          |
| ------------------------------ | -------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------- |
| Laravel version                | 13.x                             | 13.19.0                                                                                                                                                                                | ✅                              |
| PHP version                    | 8.3                              | 8.3.32                                                                                                                                                                                 | ✅                              |
| PostgreSQL                     | 16                               | Configured in `.env.example`; CI uses PG 16; tests use SQLite `:memory:`                                                                                                               | ⚠️ Tests don't match production |
| Filament                       | 4.x                              | 4.x installed, admin panel at `/admin`                                                                                                                                                 | ✅                              |
| Livewire                       | 3.x                              | 3.x (bundled with Filament)                                                                                                                                                            | ✅                              |
| Tailwind                       | 3.x                              | Configured with custom colors                                                                                                                                                          | ✅ (wrong color — see below)    |
| Sanctum                        | latest                           | **NOT installed**                                                                                                                                                                      | 🔴                              |
| Spatie Permission              | latest                           | Installed                                                                                                                                                                              | ✅                              |
| mpdf/mpdf                      | latest (RTL PDFs)                | **NOT installed** — `barryvdh/laravel-dompdf` used instead                                                                                                                             | 🔴 Wrong package                |
| simplesoftwareio/simple-qrcode | latest                           | Installed                                                                                                                                                                              | ✅                              |
| spatie/simple-excel            | latest                           | **NOT installed**                                                                                                                                                                      | 🔴                              |
| Leaflet + OpenStreetMap        | latest                           | **NOT installed**                                                                                                                                                                      | 🔴                              |
| Laravel Reverb                 | built-in (optional)              | **NOT installed**                                                                                                                                                                      | ⚠️ Optional                     |
| Queue system                   | database driver                  | `QUEUE_CONNECTION=database` in `.env.example`                                                                                                                                          | ✅                              |
| RTL support                    | `dir="rtl"`, `lang="ar"` default | Layout file has `dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"`                                                                                                              | ✅                              |
| Localization                   | `APP_LOCALE=ar`, `en` fallback   | `.env.example` has `APP_LOCALE=ar`; `config/app.php` defaults to `en`                                                                                                                  | ⚠️ Mismatch                     |
| Mobile-first PWA               | manifest.json, service worker    | `manifest.json` exists; **no service worker**                                                                                                                                          | ⚠️ Partial                      |
| Folder structure               | Per guide §14 (H-13)             | `app/Enums`, `app/Exceptions/Business`, `app/Services/Contracts`, `app/Support` exist; **no `app/Values`, `app/Dto`, `app/Events`, `app/Listeners`, `app/Policies`** (only `.gitkeep`) | ⚠️ Partial                      |
| Service layer                  | Interfaces + implementations     | 8 service classes exist; **7 are empty stubs** with comments like `// Implemented in Phase 6`; 2 contract interfaces exist (`QrStrategy`, `TaxStrategy`) — **both empty**              | 🔴                              |
| Repository pattern             | Not required (YAGNI per review)  | Not used                                                                                                                                                                               | ✅                              |
| Domain separation              | Services own business logic      | Only `StockService` has logic; all others are stubs                                                                                                                                    | 🔴                              |
| Model relationships            | Per §4 ERD                       | Relationships defined on existing models; **18+ models missing entirely**                                                                                                              | 🟠                              |

## Architectural Violations

1. **Wrong PDF package:** `barryvdh/laravel-dompdf` installed instead of `mpdf/mpdf`. The guide specifies mpdf for "Native RTL support for bilingual AR/EN invoices." dompdf has poor RTL support. This will produce broken Arabic invoice PDFs.

2. **Unauthorized package:** `pxlrbt/filament-excel` installed. Not in §2's locked stack. CLAUDE.md says "Do not introduce new packages beyond §2 without asking."

3. **No multi-tenancy enforcement:** No `BelongsToCompany` trait, no global scope, no `ActiveCompanyContext`, no base model. Every query is unscoped. `company_id` exists on 14 tables but is decorative.

4. **No service provider bindings:** `AppServiceProvider::register()` is empty. No interface-to-implementation bindings. Services are not injectable via interfaces.

5. **Timezone UTC instead of Africa/Cairo:** `config/app.php` has `'timezone' => 'UTC'`. The guide's business rules depend on Egypt business dates for `posting_date` (§11.32). UTC timezone will produce wrong posting dates for evening sales.

---

# Database Audit

## Migration count: 27 created vs 45+ required

### Tables created (27)

| Table              | Guide §          | Status           | Notes                                                                                                                                                                                                                                                                                                            |
| ------------------ | ---------------- | ---------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| companies          | §4.1             | 🟠 Partial       | Missing: `legal_entity`, `parent_company`, `abbr`, `commercial_registration_number`, `bank_name`, `bank_account`, `bank_iban`                                                                                                                                                                                    |
| users              | §4.2             | ✅               | Schema correct. Soft-delete per guide.                                                                                                                                                                                                                                                                           |
| warehouses         | §4.3             | ✅               | Correct.                                                                                                                                                                                                                                                                                                         |
| product_categories | §4.4             | ✅               | Correct.                                                                                                                                                                                                                                                                                                         |
| products           | §4.5             | 🔴 Non-compliant | Missing: `packaging_type`, `track_batch`, `track_expiry`, `has_variants`, `variant_of`, `is_bundle`, `max_discount`, `valuation_method`. Unit enum is `piece/box/carton/kg/liter/gallon` — guide says `ton/kg/piece/box/carton`. No `ton` (the primary unit).                                                    |
| stocks             | §4.7             | 🔴 Non-compliant | `quantity` is `integer` — guide says `decimal(12,3)` for fractional tons. Has `stock_type` enum (`regular`/`returned_damaged`) — **not in guide**. Missing `batch_id` FK.                                                                                                                                        |
| stock_movements    | §4.8             | 🔴 Non-compliant | `quantity_change` is `integer` — guide says `decimal(12,3)`. Missing: `company_id`, `batch_id`, `valuation_rate`, `posting_date`. `reason` enum missing: `purchase`, `landed_cost`, `transit_in`, `transit_out`, `inter_company`.                                                                                |
| routes             | §4.12            | ✅               | Correct.                                                                                                                                                                                                                                                                                                         |
| route_user         | §4.13            | ✅               | Correct.                                                                                                                                                                                                                                                                                                         |
| customers          | §4.14            | 🔴 Non-compliant | Missing: `customer_group_id`, `territory_id`, `price_list_id`, `account_manager_id`, `added_by`, `status`, `approved_by`, `approved_at`, `rejection_reason`. `code` is globally unique — guide says "unique within company." `phone` is nullable — guide says "unique within company."                           |
| work_sessions      | §4.16            | 🟠 Partial       | Missing: `company_id`. Has `route_id` (not in guide's §4.16) and `end_latitude`/`end_longitude` (not in guide).                                                                                                                                                                                                  |
| visits             | §4.18            | 🟠 Partial       | Missing: `daily_visit_assignment_id`, `arrival_confirmed`, `arrival_confirmed_at`. Has `route_id` and `is_out_of_route` (not in guide §4.18). `purpose` enum missing `custom_visit`.                                                                                                                             |
| invoices           | §4.24            | 🔴 Non-compliant | Has `softDeletes()` — **contradicts §11.44** ("transactions never soft-deleted"). Status enum is `pending/confirmed/delivered/cancelled` — guide says `draft/submitted/cancelled/amended`. Missing: `proforma_invoice_id`, `eta_qr`, `zatca_qr`, `posting_date`, `cancelled_at`, `cancelled_by`, `amended_from`. |
| invoice_items      | §4.25            | 🟠 Partial       | `quantity` is `integer` — guide says `decimal(12,3)`. Missing: `batch_id`.                                                                                                                                                                                                                                       |
| payments           | §4.30            | 🟠 Partial       | Missing: `mode_of_payment_id`, `exchange_rate`, `base_amount`, `posting_date`, `cancelled_at`, `cancelled_by`. `method` enum is hardcoded (`cash/cheque/transfer/other`) — guide says use `mode_of_payment_id` FK to a master table.                                                                             |
| returns            | §4.32            | 🔴 Non-compliant | Has `softDeletes()` — **contradicts §11.44**. Status enum is `pending/confirmed/closed/rejected` — guide says `draft/submitted/cancelled`. Missing: `against_invoice_id`, `posting_date`, `cancelled_at`, `cancelled_by`.                                                                                        |
| return_items       | §4.33            | 🟠 Partial       | `quantity` is `integer` — guide says `decimal(12,3)`. Missing: `batch_id`.                                                                                                                                                                                                                                       |
| expenses           | §4.34            | 🟠 Partial       | Missing: `posting_date`. Has `status` enum (`pending/approved/rejected`) — **not in guide** §4.34.                                                                                                                                                                                                               |
| van_transfers      | §4.44            | 🟠 Partial       | Status enum is `pending/accepted/rejected` — guide's review recommends `pending/accepted/shipped/received/rejected/cancelled`. Missing: `in_transit_warehouse_id`.                                                                                                                                               |
| van_transfer_items | §4.44b           | 🟠 Partial       | `quantity` is `integer` — guide says `decimal(12,3)`. Missing: `batch_id`.                                                                                                                                                                                                                                       |
| cash_boxes         | §4.35            | 🟠 Partial       | Missing: `company_id`. Has `unique` on `user_id` — correct (one cash box per rep).                                                                                                                                                                                                                               |
| sync_queue         | **NOT IN GUIDE** | ⚠️               | Custom table for offline sync. Not in §4. Not harmful but unauthorized.                                                                                                                                                                                                                                          |
| audit_log          | §11.58 (review)  | 🟠 Partial       | Custom implementation, not using `spatie/laravel-activitylog` as the review recommends. Has `old_value`/`new_value` as `longText` — should be `jsonb`. Missing `properties` jsonb, `user_agent`.                                                                                                                 |
| cash_box_variance  | **NOT IN GUIDE** | ⚠️               | Custom table for cash box reconciliation. Not in §4 or §11.                                                                                                                                                                                                                                                      |
| cache              | Laravel default  | ✅               | Standard.                                                                                                                                                                                                                                                                                                        |
| permission_tables  | spatie           | ✅               | Standard.                                                                                                                                                                                                                                                                                                        |
| sessions           | Laravel default  | ✅               | Standard.                                                                                                                                                                                                                                                                                                        |

### Tables required by guide but NOT created (18+ missing)

| Table                      | Guide § | Phase needed | Impact                                   |
| -------------------------- | ------- | ------------ | ---------------------------------------- |
| `suppliers`                | §4.15   | Phase 3      | No supplier management                   |
| `batches`                  | §4.6    | Phase 12     | No batch/lot tracking, no COA, no expiry |
| `goods_in_transit`         | §4.9    | Phase 11     | No international shipment tracking       |
| `goods_in_transit_items`   | §4.10   | Phase 11     | —                                        |
| `landed_costs`             | §4.11   | Phase 11     | No landed cost distribution              |
| `daily_visit_assignments`  | §4.17   | Phase 4      | No daily visit assignment by manager     |
| `visit_reports`            | §4.19   | Phase 5      | No structured visit reports              |
| `price_quotation_requests` | §4.20   | Phase 6      | No price quotation workflow              |
| `price_quotations`         | §4.21   | Phase 6      | —                                        |
| `proforma_invoices`        | §4.22   | Phase 7      | No proforma invoices                     |
| `proforma_invoice_items`   | §4.23   | Phase 7      | —                                        |
| `tax_templates`            | §4.26   | Phase 8      | No tax template management               |
| `tax_template_lines`       | §4.27   | Phase 8      | —                                        |
| `invoice_taxes`            | §4.28   | Phase 8      | No tax breakdown on invoices             |
| `company_bank_accounts`    | §4.29   | Phase 7      | No bank account management               |
| `modes_of_payment`         | §4.31   | Phase 9      | No payment method master table           |
| `purchase_requests`        | §4.36   | Phase 10     | No rep purchase requests                 |
| `purchase_orders`          | §4.37   | Phase 10     | No purchase orders                       |
| `purchase_order_items`     | §4.38   | Phase 10     | —                                        |
| `supplier_quotations`      | §4.39   | Phase 10     | No supplier quotation comparison         |
| `alarms`                   | §4.40   | Phase 13     | No alarm system                          |
| `out_of_stock_requests`    | §4.41   | Phase 13     | —                                        |
| `complaints`               | §4.42   | Phase 13     | No CRM/complaints                        |
| `warehouse_import_logs`    | §4.43   | Phase 3      | No stock import logging                  |
| `data_migrations`          | §4.45   | Phase 17     | No Odoo migration log                    |
| `naming_series`            | §11.2   | Phase 8      | No sequential document numbering system  |

### Database design score: **3/10**

---

# Model Audit

## Existing models (21)

| Model           | Relationships                                                                            | Casts                                                               | Fillable | Soft Deletes         | Status                                                                         |
| --------------- | ---------------------------------------------------------------------------------------- | ------------------------------------------------------------------- | -------- | -------------------- | ------------------------------------------------------------------------------ |
| Company         | ✅ users, warehouses, routes, customers, products, productCategories, invoices, payments | ✅ vat_percent, is_active                                           | ✅       | ✅                   | 🟡 Missing fields in migration                                                 |
| User            | ✅ company, vanWarehouse, cashBox, routes, visits                                        | ✅ password (hashed), is_active                                     | ✅       | ✅                   | ✅ Good                                                                        |
| Warehouse       | ✅ company, user, stocks, stockMovements                                                 | ✅ is_active                                                        | ✅       | ❌                   | ✅ Correct                                                                     |
| Product         | ✅ company, category, stocks, invoiceItems, returnItems                                  | ✅ price, cost, vat_applicable, is_active                           | ✅       | ✅                   | 🔴 Missing fillable fields (packaging_type, track_batch, etc.)                 |
| ProductCategory | ✅ company, products                                                                     | ❌ None                                                             | ✅       | ❌                   | ✅ Correct                                                                     |
| Customer        | ✅ company, route, visits, invoices, payments, returns                                   | ✅ latitude, longitude, credit_limit, balance, is_active            | ✅       | ✅                   | 🔴 Missing fillable fields (status, approved_by, etc.)                         |
| Route           | ✅ company, users, customers, visits, workSessions                                       | ✅ is_active                                                        | ✅       | ❌                   | ✅ Correct                                                                     |
| Stock           | ✅ warehouse, product                                                                    | ✅ quantity (integer — should be decimal:3)                         | ✅       | ❌                   | 🔴 Wrong cast type, missing batch_id                                           |
| StockMovement   | ✅ warehouse, product, user                                                              | ✅ quantity_change (integer — should be decimal:3)                  | ✅       | ❌                   | 🔴 Wrong cast type, missing company_id, batch_id, valuation_rate, posting_date |
| WorkSession     | ✅ user, route, visits, expenses                                                         | ✅ datetime, decimal:7                                              | ✅       | ❌                   | 🟠 Missing company_id                                                          |
| Visit           | ✅ user, customer, route, workSession, invoices, payments, returns                       | ✅ datetime, decimal:7, boolean                                     | ✅       | ❌                   | 🟠 Missing fields from guide                                                   |
| Invoice         | ✅ company, customer, user, visit, items, payments                                       | ✅ datetime, decimal:2                                              | ✅       | ✅ (should NOT have) | 🔴 Soft-delete violates §11.44; wrong status enum                              |
| InvoiceItem     | ✅ invoice, product                                                                      | ✅ quantity (integer — should be decimal:3), unit_price, line_total | ✅       | ❌                   | 🟠 Wrong quantity type, missing batch_id                                       |
| Payment         | ✅ company, customer, user, invoice, visit                                               | ✅ amount, collected_at                                             | ✅       | ❌                   | 🟠 Missing mode_of_payment_id, posting_date                                    |
| ReturnRecord    | ✅ company, customer, user, visit, items                                                 | ✅ total, returned_at                                               | ✅       | ✅ (should NOT have) | 🔴 Soft-delete violates §11.44; wrong status enum                              |
| ReturnItem      | ✅ return, product                                                                       | ✅ quantity (integer — should be decimal:3), unit_price, line_total | ✅       | ❌                   | 🟠 Wrong quantity type, missing batch_id                                       |
| Expense         | ✅ company, user, workSession                                                            | ✅ amount, spent_at                                                 | ✅       | ❌                   | 🟠 Has status field not in guide                                               |
| VanTransfer     | ✅ company, fromUser, toUser, items                                                      | ✅ accepted_at                                                      | ✅       | ❌                   | 🟠 Missing shipped/received states                                             |
| VanTransferItem | ✅ vanTransfer, product                                                                  | ✅ quantity (integer — should be decimal:3)                         | ✅       | ❌                   | 🟠 Wrong quantity type, missing batch_id                                       |
| CashBox         | ✅ user                                                                                  | ✅ balance (decimal:2)                                              | ✅       | ❌                   | 🟠 Missing company_id                                                          |
| AuditLog        | ✅ user, company                                                                         | ❌ None (old_value/new_value should be jsonb)                       | ✅       | ❌                   | 🟠 Custom, not spatie/activitylog                                              |
| CashBoxVariance | ✅ user, company                                                                         | ✅ date, decimal:2, datetime                                        | ✅       | ❌                   | ⚠️ Not in guide                                                                |
| SyncQueue       | ✅ user                                                                                  | ✅ integer, datetime                                                | ✅       | ❌                   | ⚠️ Not in guide                                                                |
| Task            | **EMPTY STUB**                                                                           | ❌                                                                  | ❌       | ❌                   | 🔴 No fields, no relationships, no casts                                       |

## Missing models (18+)

No models exist for: Supplier, Batch, GoodsInTransit, GoodsInTransitItem, LandedCost, DailyVisitAssignment, VisitReport, PriceQuotationRequest, PriceQuotation, ProformaInvoice, ProformaInvoiceItem, TaxTemplate, TaxTemplateLine, InvoiceTax, CompanyBankAccount, ModeOfPayment, PurchaseRequest, PurchaseOrder, PurchaseOrderItem, SupplierQuotation, Alarm, OutOfStockRequest, Complaint, WarehouseImportLog, DataMigration, NamingSeries.

## Model issues

- **No `$casts` using enum classes:** 10 enum files exist but 9 are empty. Models use string comparisons for enum columns, not PHP backed enums.
- **No global scopes:** No company scope, no active scope, no default ordering.
- **No accessors/mutators:** No computed fields (e.g., `remaining_amount` accessor).
- **No traits:** No `BelongsToCompany`, no custom traits.
- **`Task` model is an empty shell** — no fields, no relationships, no casts.
- **`Money` and `Bilingual` support classes are empty stubs.**

---

# Business Rules Audit

| Rule                             | Guide § | Status             | Evidence                                                                                                                                                                                                                          |
| -------------------------------- | ------- | ------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| No negative van stock            | §7.1    | 🟠 Partial         | `StockService::recordMovement()` checks `$stock->quantity + $quantityChange < 0` but throws bare `Exception`, not `InsufficientStockException`. The exception class exists but is empty and unused. No DB-level CHECK constraint. |
| Atomic sales (DB::transaction)   | §7.2    | 🔴 Not implemented | `InvoiceService` is an empty stub. No sale flow exists.                                                                                                                                                                           |
| GPS geofencing                   | §7.3    | 🔴 Not implemented | No GPS validation code. `GpsService` doesn't exist.                                                                                                                                                                               |
| Price range enforcement          | §7.4    | 🔴 Not implemented | `PricingService` doesn't exist.                                                                                                                                                                                                   |
| Customer approval workflow       | §7.5    | 🔴 Not implemented | No `status` column on customers migration. No approval logic.                                                                                                                                                                     |
| Proforma invoice rules           | §7.6    | 🔴 Not implemented | No proforma tables or models.                                                                                                                                                                                                     |
| Collections → cash_box + balance | §7.7    | 🔴 Not implemented | `PaymentService` is empty stub.                                                                                                                                                                                                   |
| Returns → stock + balance        | §7.8    | 🔴 Not implemented | `ReturnService` is empty stub.                                                                                                                                                                                                    |
| Expenses → cash_box              | §7.9    | 🔴 Not implemented | `CashBoxService` is empty stub.                                                                                                                                                                                                   |
| Route & visit integrity          | §7.10   | 🔴 Not implemented | No route lock enforcement. `RouteLockException` exists but is empty and unused.                                                                                                                                                   |
| Invoice numbers sequential       | §7.11   | 🔴 Not implemented | `NumberSequenceService` is empty stub.                                                                                                                                                                                            |
| Stock only through StockService  | §7.12   | 🟡 Partial         | `StockService` exists and is the only path to update stock. But it's not enforced (no PHPStan rule, no model write protection).                                                                                                   |
| Warehouse import via CSV         | §7.13   | 🔴 Not implemented | No import functionality.                                                                                                                                                                                                          |
| Goods in transit → warehouse     | §7.14   | 🔴 Not implemented | No GIT tables.                                                                                                                                                                                                                    |
| Landed cost distribution         | §7.15   | 🔴 Not implemented | No landed cost tables.                                                                                                                                                                                                            |
| Batch tracking                   | §7.16   | 🔴 Not implemented | No batches table.                                                                                                                                                                                                                 |
| Expiry date alerts               | §7.17   | 🔴 Not implemented | No expiry tracking.                                                                                                                                                                                                               |
| Multi-currency                   | §7.19   | 🔴 Not implemented | No currency exchange.                                                                                                                                                                                                             |
| Egypt ETA e-invoicing            | §7.20   | 🔴 Not implemented | `InvoiceQrService` is empty stub.                                                                                                                                                                                                 |
| Cost price hidden from sales     | §7.21   | 🔴 Not implemented | No role-based field hiding.                                                                                                                                                                                                       |
| Alarm generation                 | §7.22   | 🔴 Not implemented | No alarm tables or service.                                                                                                                                                                                                       |
| Money math                       | §7.24   | 🔴 Not implemented | `Money` support class is empty stub. No bcmath usage.                                                                                                                                                                             |
| Reversal is compensating         | §11.44  | 🔴 Not implemented | `ReversalService` is empty stub.                                                                                                                                                                                                  |

---

# Feature Audit

## Admin Panel (Filament at `/admin`)

| Feature                        | Guide § | Status         | Evidence                                                             |
| ------------------------------ | ------- | -------------- | -------------------------------------------------------------------- |
| Companies CRUD                 | §6.1    | 🔴 Not Started | No Filament Resources exist. `app/Filament/Resources/.gitkeep` only. |
| Users & roles CRUD             | §6.2    | 🔴 Not Started | —                                                                    |
| Products & categories CRUD     | §6.3    | 🔴 Not Started | —                                                                    |
| Batch/Lot tracking             | §6.4    | 🔴 Not Started | —                                                                    |
| Price management               | §6.5    | 🔴 Not Started | —                                                                    |
| Suppliers CRUD                 | §6.6    | 🔴 Not Started | —                                                                    |
| Warehouses & stock             | §6.7    | 🔴 Not Started | —                                                                    |
| Goods in transit               | §6.8    | 🔴 Not Started | —                                                                    |
| Landed cost                    | §6.9    | 🔴 Not Started | —                                                                    |
| Daily visit assignments        | §6.10   | 🔴 Not Started | —                                                                    |
| Customer approval queue        | §6.11   | 🔴 Not Started | —                                                                    |
| Routes CRUD                    | §6.12   | 🔴 Not Started | —                                                                    |
| Customers CRUD                 | §6.13   | 🔴 Not Started | —                                                                    |
| Price quotation requests       | §6.14   | 🔴 Not Started | —                                                                    |
| Proforma invoices              | §6.15   | 🔴 Not Started | —                                                                    |
| Invoices                       | §6.16   | 🔴 Not Started | —                                                                    |
| Supplier quotations comparison | §6.17   | 🔴 Not Started | —                                                                    |
| Purchase orders                | §6.18   | 🔴 Not Started | —                                                                    |
| Purchase requests              | §6.19   | 🔴 Not Started | —                                                                    |
| Alarms dashboard               | §6.20   | 🔴 Not Started | —                                                                    |
| Complaints / CRM               | §6.21   | 🔴 Not Started | —                                                                    |
| Data migration from Odoo       | §6.22   | 🔴 Not Started | —                                                                    |
| Reports & dashboard            | §6.23   | 🔴 Not Started | —                                                                    |

**Admin panel: 0 of 23 features implemented.**

## Rep PWA (at `/app`)

| Feature                     | Guide §   | Status         | Evidence                                                                                                                                      |
| --------------------------- | --------- | -------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| Home screen                 | §6.rep.1  | 🟡 Partial     | `Home.php` Livewire component exists with a view showing "Welcome, {name}". No tiles, no assigned visits count, no cash box, no stock search. |
| Start work                  | §6.rep.2  | 🔴 Placeholder | `StartWork.php` is empty stub, no view file.                                                                                                  |
| Today's assigned visits     | §6.rep.3  | 🔴 Placeholder | `TodaysCustomers.php` is empty stub, no view file.                                                                                            |
| Add new customer            | §6.rep.4  | 🔴 Not Started | —                                                                                                                                             |
| Visit flow with GPS         | §6.rep.5  | 🔴 Placeholder | `Visit.php` is empty stub, no view file.                                                                                                      |
| Visit report                | §6.rep.6  | 🔴 Not Started | —                                                                                                                                             |
| Price quotation request     | §6.rep.7  | 🔴 Not Started | —                                                                                                                                             |
| Negotiate & confirm         | §6.rep.8  | 🔴 Not Started | —                                                                                                                                             |
| Create proforma invoice     | §6.rep.9  | 🔴 Not Started | —                                                                                                                                             |
| Sell                        | §6.rep.10 | 🔴 Placeholder | `Sell.php` is empty stub, no view file.                                                                                                       |
| Check stock availability    | §6.rep.11 | 🔴 Not Started | —                                                                                                                                             |
| Out-of-stock urgent request | §6.rep.12 | 🔴 Not Started | —                                                                                                                                             |
| Purchase request            | §6.rep.13 | 🔴 Not Started | —                                                                                                                                             |
| Customer complaint          | §6.rep.14 | 🔴 Not Started | —                                                                                                                                             |
| Collect                     | §6.rep.15 | 🔴 Placeholder | `Collect.php` is empty stub, no view file.                                                                                                    |
| Return                      | §6.rep.16 | 🔴 Placeholder | `ReturnFlow.php` is empty stub, no view file.                                                                                                 |
| Expenses                    | §6.rep.17 | 🔴 Placeholder | `Expense.php` is empty stub, no view file.                                                                                                    |
| End day                     | §6.rep.18 | 🔴 Placeholder | `EndDay.php` is empty stub, no view file.                                                                                                     |

**Rep PWA: 1 of 18 features partially implemented (Home shows a welcome message only).**

---

# UI Audit

| Criterion                | Guide §                    | Status | Evidence                                                                                                                                                                                                                                                         |
| ------------------------ | -------------------------- | ------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| RTL                      | §3                         | ✅     | `layouts/app.blade.php` has `dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"`                                                                                                                                                                            |
| Arabic                   | §3                         | ✅     | `lang/ar/` exists with app, auth, errors, validation files                                                                                                                                                                                                       |
| English                  | §3                         | ✅     | `lang/en/` exists                                                                                                                                                                                                                                                |
| Responsiveness           | §3                         | ⚠️     | Login page has `max-width:24rem;margin:2rem auto` — basic responsive. No mobile-first PWA layout.                                                                                                                                                                |
| Navigation               | §3                         | 🔴     | No bottom nav, no top bar with alarm bell. Layout is a minimal header with locale switch + logout.                                                                                                                                                               |
| Filament Resources       | §6                         | 🔴     | **Zero Filament Resources exist.** Only `.gitkeep`.                                                                                                                                                                                                              |
| Forms                    | —                          | 🔴     | No Filament forms.                                                                                                                                                                                                                                               |
| Tables                   | —                          | 🔴     | No Filament tables.                                                                                                                                                                                                                                              |
| Filters                  | —                          | 🔴     | No Filament filters.                                                                                                                                                                                                                                             |
| Charts                   | —                          | 🔴     | No charts.                                                                                                                                                                                                                                                       |
| Dark Mode                | §3                         | 🔴     | Not enabled in Filament panel config.                                                                                                                                                                                                                            |
| Accessibility            | §3                         | 🔴     | No ARIA, no 44px tap targets, no skeleton loaders.                                                                                                                                                                                                               |
| Design Guide Compliance  | §3 / docs/DESIGN_SYSTEM.md | 🔴     | **Brand color is `#9B1C31` (crimson)** in Filament panel, Tailwind config, manifest, and login view. Guide §3 says **teal `#4DB848` + steel blue `#2C6FB4`**. `docs/DESIGN_SYSTEM.md` says crimson. **Cross-document conflict — repo follows docs/, not guide.** |
| Error pages              | CLAUDE.md                  | ✅     | 403, 404, 419, 500 custom bilingual error pages exist.                                                                                                                                                                                                           |
| Design system components | docs/DESIGN_SYSTEM.md      | 🔴     | 6 Blade components exist (button, card, empty, modal, skeleton, tooltip) — **all are empty stubs** with `{{-- Design-system component stub --}}`.                                                                                                                |

---

# Security Audit

| Criterion                 | Guide / CLAUDE.md | Status | Evidence                                                                                                                                                                          |
| ------------------------- | ----------------- | ------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Authentication            | §2                | ✅     | Laravel built-in auth for admin (Filament) and rep (custom LoginController).                                                                                                      |
| Authorization             | §5/§12            | 🟠     | Role-based access works (rep blocked from `/admin`, non-rep blocked from `/app`). But only 5 roles, not 7. No Policies exist.                                                     |
| Policies                  | §5                | 🔴     | `app/Policies/.gitkeep` only. Zero policy classes.                                                                                                                                |
| Role permissions          | §12               | 🔴     | 5 roles with 13 permissions. Guide §12 requires 7 roles with ~50+ permissions. Permission names use hyphens (`manage-users-roles`) not the guide's dot notation (`users.manage`). |
| Mass assignment           | CLAUDE.md         | ✅     | All models use `$fillable`. No `$guarded = []`.                                                                                                                                   |
| Validation                | CLAUDE.md         | 🟡     | `LoginRequest` Form Request exists. No other Form Requests.                                                                                                                       |
| SQL Injection             | docs/SECURITY.md  | ✅     | Eloquent only. No raw SQL.                                                                                                                                                        |
| XSS                       | docs/SECURITY.md  | ✅     | Blade `{{ }}` escaping. No `{!! !!}` on user content found.                                                                                                                       |
| CSRF                      | docs/SECURITY.md  | ✅     | `@csrf` in login form.                                                                                                                                                            |
| Secrets                   | CLAUDE.md         | ✅     | No secrets in code. `.env.example` has placeholders.                                                                                                                              |
| Password hashing          | CLAUDE.md         | ✅     | `config/hashing.php` has `'driver' => 'argon2id'`.                                                                                                                                |
| Session secure            | CLAUDE.md         | ⚠️     | `SESSION_SECURE_COOKIE` not set in `.env.example`. `SESSION_ENCRYPT=false`. `http_only=true` ✅.                                                                                  |
| Rate limiting             | CLAUDE.md         | ✅     | Login rate limited (5/min per IP+email) in `AppServiceProvider`. Tests verify it.                                                                                                 |
| File uploads              | docs/SECURITY.md  | 🔴     | No file upload functionality.                                                                                                                                                     |
| Security headers          | docs/SECURITY.md  | 🔴     | `ForceHttps` middleware is empty stub. No CSP, HSTS, X-Content-Type-Options, X-Frame-Options middleware.                                                                          |
| `APP_DEBUG=false` in prod | CLAUDE.md         | ✅     | `.env.example` has `APP_DEBUG=true` (correct for example).                                                                                                                        |

## Critical Security Issues

1. **No multi-tenancy enforcement:** Any authenticated user can query any company's data via Eloquent. No global scope. This is a **data leakage** vulnerability.

2. **No Policies:** Authorization is role-level only (can access `/admin` or `/app`). No resource-level authorization (can user A edit customer B?). IDOR is possible.

3. **No security headers:** No CSP, no HSTS, no X-Frame-Options. `ForceHttps` is an empty class.

---

# Code Quality Audit

| Criterion               | Status | Evidence                                                                                                                                                                                                 |
| ----------------------- | ------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Folder organization     | 🟡     | Good: `app/Enums`, `app/Exceptions/Business`, `app/Services/Contracts`, `app/Support`. Missing: `app/Values`, `app/Dto`, `app/Events`, `app/Listeners`.                                                  |
| Naming consistency      | 🟡     | Models use PascalCase, methods use camelCase. `ReturnRecord` model maps to `returns` table (awkward but necessary — `Return` is a PHP keyword).                                                          |
| Code duplication        | ✅     | Little code exists to duplicate.                                                                                                                                                                         |
| SOLID principles        | 🔴     | `StockService` has 4 methods in one class — acceptable. But no interfaces (except 2 empty ones), no DI, no separation of concerns in services.                                                           |
| Dependency Injection    | 🔴     | No service is injected. `StockService` is called via `new` or direct instantiation. No bindings in `AppServiceProvider`.                                                                                 |
| Service Layer           | 🔴     | 8 of 9 services are empty stubs. `StockService` is the only one with logic.                                                                                                                              |
| Long methods            | ✅     | `StockService::recordMovement()` is ~25 lines — acceptable.                                                                                                                                              |
| God classes             | ✅     | No god classes (because no classes have enough code to be god classes).                                                                                                                                  |
| Magic numbers           | ⚠️     | `5` (rate limit) is hardcoded in `AppServiceProvider` rather than config.                                                                                                                                |
| Dead code               | ⚠️     | `Task` model is an empty stub — no migration, no usage. `ForceHttps` is an empty stub. `Money` and `Bilingual` are empty stubs. `DemoSeeder` is an empty stub. 7 of 8 Livewire components have no views. |
| Unused models           | 🔴     | `Task`, `SyncQueue`, `CashBoxVariance` have no Filament resources, no routes, no tests, no references in other classes.                                                                                  |
| Unused routes           | ✅     | All routes are used.                                                                                                                                                                                     |
| Comments                | 🟡     | Many `// Implemented in Phase X` comments. These are TODO comments, not documentation.                                                                                                                   |
| Documentation           | ✅     | `CONTRIBUTING.md`, `SECURITY.md`, `README.md`, `docs/*.md` all exist and are substantive.                                                                                                                |
| Overall maintainability | 🟠     | The code that exists is clean and follows Laravel conventions. But the empty stubs create a false impression of progress.                                                                                |

---

# Testing Audit

| Criterion                  | Status | Evidence                                                                                                                                                                                                                                    |
| -------------------------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Feature Tests              | 🟡     | 3 test files: `AdminLoginTest`, `RepLoginTest`, `LocaleSwitchTest`. All test auth + locale only.                                                                                                                                            |
| Unit Tests                 | 🔴     | `tests/Unit/Services/.gitkeep` and `tests/Unit/Support/.gitkeep` — no unit tests.                                                                                                                                                           |
| Integration Tests          | 🔴     | No integration tests (sale flow, return flow, stock movement).                                                                                                                                                                              |
| Factories                  | 🟡     | `CompanyFactory` and `UserFactory` exist and work. No factories for other 19 models.                                                                                                                                                        |
| Seeders                    | 🟡     | `RoleSeeder` works (tested). `DatabaseSeeder` seeds 1 company + 5 users. `DemoSeeder` is empty stub.                                                                                                                                        |
| Coverage                   | 🔴     | No coverage measurement. Only auth paths tested. 0% coverage of business logic.                                                                                                                                                             |
| Critical scenarios missing | 🔴     | No test for: negative stock rejection, atomic sale rollback, price range enforcement, GPS geofencing, customer approval, sequential numbering, batch tracking, landed cost, ETA QR, alarm generation, cost hiding, multi-tenancy isolation. |
| Production confidence      | 🔴     | Zero confidence. No business logic is tested.                                                                                                                                                                                               |
| Tests pass                 | ✅     | 14 tests, 14 passed, 37 assertions.                                                                                                                                                                                                         |
| Test DB                    | ⚠️     | Tests use SQLite `:memory:` (`phpunit.xml`). CI uses PostgreSQL 16. Production uses PostgreSQL. **Tests don't catch PostgreSQL-specific issues** (partial indexes, CHECK constraints, partitioning, jsonb).                                 |

---

# Production Readiness

| Criterion             | Status | Evidence                                                                                                                                                                                     |
| --------------------- | ------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Deployment readiness  | 🔴     | `scripts/deploy.sh` is a placeholder: `echo "Placeholder — not implemented."`                                                                                                                |
| Configuration         | 🟡     | `.env.example` exists with correct DB/queue/cache settings. `APP_LOCALE=ar` ✅. Timezone UTC (should be Africa/Cairo).                                                                       |
| Caching               | ✅     | `CACHE_STORE=database` ✅.                                                                                                                                                                   |
| Queues                | ✅     | `QUEUE_CONNECTION=database` ✅. No queue jobs defined.                                                                                                                                       |
| Logging               | ✅     | Default Laravel logging. No custom PII redaction.                                                                                                                                            |
| Performance           | 🔴     | No indexes beyond FK indexes. No composite indexes. No partitioning. No query budget tests.                                                                                                  |
| Error handling        | 🔴     | No custom exception rendering. Business exceptions extend `Exception` (not `DomainException`). No exception handler registration.                                                            |
| Database transactions | 🟡     | `StockService::promoteReturnedStock()` uses `DB::transaction()`. `StockService::recordMovement()` does NOT wrap in transaction (caller must wrap — but guide says service wraps internally). |
| Monitoring            | 🔴     | No Sentry, no health checks beyond `/up`.                                                                                                                                                    |
| Backup readiness      | 🔴     | `scripts/restore-backup.sh` exists but is likely a placeholder. `docs/BACKUP_RESTORE.md` describes the plan but no implementation.                                                           |
| Scalability           | 🔴     | No partitioning, no composite indexes, no cache strategy. Would fail at 100k+ rows.                                                                                                          |
| Maintainability       | 🟠     | Code is clean where it exists. But empty stubs and missing architecture make continuation risky.                                                                                             |

---

# Technical Debt

| #     | Description                                       | Severity     | Impact                                                                              | Effort | Fix                                                                             | Priority |
| ----- | ------------------------------------------------- | ------------ | ----------------------------------------------------------------------------------- | ------ | ------------------------------------------------------------------------------- | -------- |
| TD-1  | 5 roles instead of 7                              | **Critical** | Wrong RBAC, missing accounts/purchasing/executive roles, wrong permission names     | 4h     | Rewrite RoleSeeder per §5, update tests, update docs/ROLES_MATRIX.md            | P0       |
| TD-2  | Wrong brand color (crimson vs teal/blue)          | **High**     | Non-compliant UI, client brand mismatch                                             | 2h     | Update Filament panel, Tailwind config, manifest, all views                     | P0       |
| TD-3  | Integer quantities instead of decimal(12,3)       | **Critical** | Cannot handle fractional tons (the primary unit of trade)                           | 8h     | Alter all quantity columns, update all models/casts, update StockService        | P0       |
| TD-4  | Invoices have softDeletes (contradicts §11.44)    | **High**     | Audit trail can be hidden; wrong cancellation model                                 | 4h     | Remove softDeletes from invoices + returns migrations, add cancelled_at columns | P0       |
| TD-5  | Wrong invoice status enum                         | **High**     | Missing `draft`/`submitted`/`amended` states; has `pending`/`confirmed`/`delivered` | 4h     | Alter enum, update model, update tests                                          | P0       |
| TD-6  | No multi-tenancy enforcement                      | **Critical** | Cross-company data leakage                                                          | 8h     | Add BelongsToCompany trait, ActiveCompanyContext, middleware, global scope      | P0       |
| TD-7  | 18+ missing tables                                | **Critical** | Cannot implement Phases 3-19 without them                                           | 40h    | Create all missing migrations per §4 + §11                                      | P0       |
| TD-8  | No Filament Resources                             | **High**     | Admin panel has no UI                                                               | 60h    | Build all 23 admin resources per §6                                             | P1       |
| TD-9  | Empty service stubs                               | **Critical** | No business logic                                                                   | 40h    | Implement all 9 services with interfaces per §11.50                             | P0       |
| TD-10 | Wrong PDF package (dompdf vs mpdf)                | **High**     | Broken Arabic RTL invoices                                                          | 4h     | Replace barryvdh/laravel-dompdf with mpdf/mpdf                                  | P0       |
| TD-11 | Unauthorized packages (filament-excel)            | **Medium**   | Violates locked stack                                                               | 1h     | Remove pxlrbt/filament-excel or get approval                                    | P1       |
| TD-12 | Missing packages (Sanctum, simple-excel, Leaflet) | **Medium**   | Missing functionality                                                               | 2h     | Install per §2                                                                  | P1       |
| TD-13 | No Policies                                       | **High**     | No resource-level authorization (IDOR)                                              | 16h    | Create policies for all models per H-4                                          | P1       |
| TD-14 | Tests use SQLite, not PostgreSQL                  | **Medium**   | PG-specific features untested                                                       | 4h     | Update phpunit.xml to use PostgreSQL                                            | P1       |
| TD-15 | No domain events                                  | **Medium**   | Cross-cutting concerns will tangle                                                  | 8h     | Implement event system per C-8                                                  | P1       |
| TD-16 | Empty enums (9 of 10)                             | **Medium**   | Stringly-typed statuses                                                             | 4h     | Fill all enum classes with cases per C-10                                       | P1       |
| TD-17 | No security headers                               | **High**     | XSS, clickjacking, MITM risks                                                       | 4h     | Implement security headers middleware                                           | P1       |
| TD-18 | Timezone UTC instead of Africa/Cairo              | **Medium**   | Wrong posting dates                                                                 | 1h     | Change config/app.php timezone                                                  | P1       |

---

# Missing Features Checklist (by phase)

## Phase 0 (remaining 30%)

- [ ] Service worker for PWA
- [ ] Security headers middleware (CSP, HSTS, X-Frame-Options)
- [ ] Africa/Cairo timezone
- [ ] Correct brand colors (teal/blue, not crimson)

## Phase 1 (remaining 65%)

- [ ] 18+ missing table migrations
- [ ] Missing columns on existing tables (products, customers, companies, invoices, etc.)
- [ ] decimal(12,3) for all quantity columns
- [ ] batch_id on stocks, invoice_items, return_items
- [ ] company_id on stock_movements, cash_boxes, work_sessions
- [ ] posting_date on all transaction tables
- [ ] naming_series table
- [ ] All missing models with relationships
- [ ] Service interfaces with method signatures
- [ ] PHP enum classes with cases
- [ ] Multi-tenancy trait + scope + context
- [ ] Money value object with bcmath
- [ ] Domain exception hierarchy

## Phase 2 (remaining 50%)

- [ ] 7 roles (add admin, accounts, purchasing, executive)
- [ ] ~50 permissions in {resource}.{action} format
- [ ] Policies for all models
- [ ] Filament panel access control per role

## Phase 3 (0% complete)

- [ ] All 23 Filament Resources
- [ ] Companies CRUD
- [ ] Users CRUD (auto-create van + cash box for reps)
- [ ] Products & categories CRUD
- [ ] Suppliers CRUD
- [ ] Price management (Accounts)
- [ ] Routes + Customers CRUD (Leaflet)
- [ ] Warehouses & stock (adjust, load van, import CSV)

## Phase 4-19 (0% complete)

- [ ] All features listed in §8 phases 4-19

---

# Incorrect Implementations

| #   | Current                                                                      | Required (Guide)                                                                           | Why it matters                                                                             | Fix                                  |
| --- | ---------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------ | ------------------------------------ |
| 1   | 5 roles: system_viewer, hr_admin, sales_manager, warehouse_keeper, sales_rep | 7 roles: admin, sales_manager, accounts, purchasing, warehouse_keeper, executive, rep (§5) | Wrong RBAC; missing financial/purchasing/executive roles; permission names don't match §12 | Rewrite RoleSeeder per §5            |
| 2   | Brand color #9B1C31 (crimson)                                                | Teal #4DB848 + steel blue #2C6FB4 (§3)                                                     | Client brand mismatch                                                                      | Update all color references          |
| 3   | `integer` for all quantities                                                 | `decimal(12,3)` (§4 intro)                                                                 | Cannot handle fractional tons — the primary trading unit                                   | Alter all quantity columns           |
| 4   | Invoice status: pending/confirmed/delivered/cancelled                        | draft/submitted/cancelled/amended (§4.24)                                                  | Missing draft/submitted/amended states; wrong state machine                                | Alter enum                           |
| 5   | Return status: pending/confirmed/closed/rejected                             | draft/submitted/cancelled (§4.32)                                                          | Wrong state machine                                                                        | Alter enum                           |
| 6   | Invoice + Return have `softDeletes()`                                        | Transactions never soft-deleted (§11.44)                                                   | Audit trail can be hidden                                                                  | Remove softDeletes, add cancelled_at |
| 7   | Products unit enum: piece/box/carton/kg/liter/gallon                         | ton/kg/piece/box/carton (§4.5)                                                             | Missing `ton` — the primary unit                                                           | Alter enum                           |
| 8   | Payments use `method` enum (cash/cheque/transfer/other)                      | `mode_of_payment_id` FK to master table (§4.30)                                            | Hardcoded methods; can't add LC, TT, credit_card                                           | Add modes_of_payment table + FK      |
| 9   | `dompdf` for PDFs                                                            | `mpdf/mpdf` (§2)                                                                           | dompdf has poor RTL support                                                                | Replace package                      |
| 10  | Customers `code` is globally unique                                          | Unique within company (§4.14)                                                              | Cannot have same code in different companies                                               | Change to composite unique           |
| 11  | `stock_type` enum on stocks (regular/returned_damaged)                       | Not in guide                                                                               | Unauthorized column; adds complexity                                                       | Remove or justify with ADR           |
| 12  | `sync_queue` table                                                           | Not in guide                                                                               | Unauthorized table                                                                         | Remove or justify with ADR           |
| 13  | `cash_box_variance` table                                                    | Not in guide                                                                               | Unauthorized table                                                                         | Remove or justify with ADR           |
| 14  | StockService throws bare `Exception`                                         | Should throw `InsufficientStockException` (domain exception)                               | No bilingual error, no structured handling                                                 | Use domain exceptions                |
| 15  | Permission names use hyphens (`manage-users-roles`)                          | Guide uses dots (`users.manage`) (§12)                                                     | Inconsistent with §12 catalogue                                                            | Rename all permissions               |

---

# Progress Dashboard

| Area                 | Progress | Status                                                                                     |
| -------------------- | -------- | ------------------------------------------------------------------------------------------ |
| Architecture         | 45%      | 🟠 Partial — stack installed but wrong packages, no multi-tenancy, no service contracts    |
| Database             | 25%      | 🔴 Non-compliant — 27 of 45+ tables, major column gaps, wrong types, wrong enums           |
| Authentication       | 70%      | 🟡 Mostly — login works for both panels, rate limiting, role-based access; but wrong roles |
| Admin Panel          | 0%       | 🔴 Not Started — zero Filament Resources                                                   |
| Rep App              | 5%       | 🔴 Placeholder — Home shows welcome message; 7 stubs with no views                         |
| Stock                | 15%      | 🔴 Partial — StockService exists but wrong types, no batch, no transaction enforcement     |
| Purchasing           | 0%       | 🔴 Not Started                                                                             |
| CRM                  | 0%       | 🔴 Not Started                                                                             |
| Reports              | 0%       | 🔴 Not Started                                                                             |
| Security             | 30%      | 🔴 Partial — auth + rate limit + hashing; no policies, no headers, no multi-tenancy        |
| Testing              | 10%      | 🔴 Minimal — 14 auth tests only; 0 business logic tests                                    |
| Production Readiness | 0%       | 🔴 Not Ready                                                                               |

**Overall Completion: ~8%**

---

# Phase Recommendation

## Highest unfinished phase: Phase 1 (Database & models)

**Phase 1 is not complete.** Its DoD requires "All tables and Eloquent models" and "`migrate:fresh` clean." 18+ tables are missing, existing tables have wrong column types and missing columns, and 18+ models don't exist.

## What should be built next

**Priority: P0 — Fix Phase 1 before anything else.**

1. **Resolve the cross-document conflict** (5 vs 7 roles, crimson vs teal, dompdf vs mpdf). The repo currently follows `docs/` instead of the guide. Decide which wins and update the other. This is a 1-hour decision that affects every subsequent phase.

2. **Create all missing migrations** (18+ tables from §4 and §11). Fix existing migrations:
   - Change all `integer` quantities to `decimal(12,3)`.
   - Add missing columns to products, customers, companies, invoices, returns, stocks, stock_movements, visits, payments, expenses.
   - Remove `softDeletes()` from invoices and returns. Add `cancelled_at`/`cancelled_by`.
   - Fix invoice/return status enums.
   - Fix products unit enum (add `ton`).
   - Add `company_id` to stock_movements, cash_boxes, work_sessions.
   - Add `posting_date` to all transaction tables.
   - Add `batch_id` to stocks, invoice_items, return_items.

3. **Create all missing models** with relationships, casts, fillables.

4. **Implement multi-tenancy** (BelongsToCompany trait + ActiveCompanyContext + middleware + global scope).

5. **Define service interfaces** (StockService, InvoiceService, PaymentService, etc.) with method signatures per the review's C-5.

6. **Fill enum classes** with cases per C-10.

7. **Implement domain exception hierarchy** per C-6.

**Reason:** Every subsequent phase depends on a correct schema. Building Phase 3 (admin panel) on the current schema would require reworking it when the schema is fixed. Fix the foundation first.

**Dependencies:** None — this is the foundation.

**Estimated complexity:** 40-60 hours of AI agent work.

**Risk:** High — if the cross-document conflict is not resolved first, the AI may build 18 new migrations following the wrong spec (5 roles, wrong colors, wrong types) and have to redo them.

---

# Final Answer

> **If this repository were handed to a senior engineering team today, would they approve it for production?**

**No. Absolutely not.**

This is an early-stage scaffold. Auth works. 14 tests pass. But:

1. **No business logic exists.** 8 of 9 services are empty stubs. No sales, no stock movements, no invoicing, no payments, no returns, no purchasing, no alarms, no reports.

2. **The schema is 35% complete and non-compliant.** 18+ required tables are missing. Existing tables have wrong column types (integer instead of decimal for ton quantities), wrong enums (invoice status, unit types), missing columns (batch_id, posting_date, company_id on 3 tables), and wrong soft-delete behavior (invoices shouldn't be soft-deletable).

3. **The RBAC system is wrong.** 5 roles exist; the guide requires 7. Permission names don't match the §12 catalogue. No Policies exist.

4. **No multi-tenancy enforcement.** `company_id` is decorative. Any user can query any company's data.

5. **The UI brand is wrong.** Crimson instead of teal/blue.

6. **Zero Filament Resources.** The admin panel is an empty Filament installation with no resources, pages, or widgets.

7. **The rep PWA is 7 empty stubs.** Only the Home page renders (a welcome message). The other 7 Livewire components have no views and no logic.

8. **No production infrastructure.** Deploy script is a placeholder. No security headers. No monitoring. No backup implementation.

**The codebase is clean where it exists** — Laravel conventions are followed, the auth flow is well-tested, the folder structure is reasonable. The problem is that **what exists is non-compliant with the guide** (wrong roles, wrong colors, wrong types, wrong enums, wrong packages) and **what's missing is 92% of the system.**

The highest-priority action is resolving the cross-document conflict between the guide and `docs/`, then fixing Phase 1's schema before building anything on top of it.
