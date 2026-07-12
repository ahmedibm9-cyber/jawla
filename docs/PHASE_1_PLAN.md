# Phase 0 Fixes + Phase 1 Implementation Plan

**Created:** 2026-07-12
**Status:** Ready to execute
**Prerequisites:** User approved IBM Plex Sans Arabic as canonical font, separate fix commit, remove unauthorized tables, split into sub-commits

---

## Commit 1: `fix: phase 0 gaps`

**9 changes:**

1. `composer.json`: remove `barryvdh/laravel-dompdf` + `pxlrbt/filament-excel`, add `mpdf/mpdf` + `spatie/simple-excel` + `laravel/sanctum`
2. `config/app.php`: locale `'en'`→`'ar'`, timezone `UTC`→`Africa/Cairo`
3. `tailwind.config.js`: accent `#9B1C31`→`#4DB848` + add `#2C6FB4`
4. `AdminPanelProvider.php`: primary color → teal
5. `manifest.json`: theme_color → teal
6. New `app/Http/Middleware/SecurityHeaders.php`: HSTS, X-Frame-Options DENY, X-Content-Type-Options, Referrer-Policy, CSP
7. Register middleware in `bootstrap/app.php`
8. `phpunit.xml`: `sqlite`→`pgsql`, add PG env vars
9. `composer install && npm run build`

---

## Commit 2: `feat: phase 1a — architecture foundation`

No migrations, no models — just contracts and infrastructure.

### 2a. Multi-tenancy
- `app/Models/Concerns/BelongsToCompany` trait (global scope + auto-set on create)
- `app/Support/ActiveCompanyContext` singleton (set from user, admin bypass)
- `app/Http/Middleware/SetActiveCompanyContext` (runs after auth)
- Register alias + append to web middleware in `bootstrap/app.php`
- Bind singleton in `AppServiceProvider`

### 2b. Enums (fill all 10)
Each gets backed cases + `canTransitionTo()` where it's a state machine:
- `InvoiceStatus`: draft, submitted, cancelled, amended
- `WarehouseType`: main, van
- `VisitPurpose`: sale, collection, return, survey, other, custom_visit
- `VisitStatus`: open, closed
- `StockReason`: sale, return, transfer_in, transfer_out, adjustment, initial, purchase, landed_cost, transit_in, transit_out, inter_company
- `VanTransferStatus`: pending, accepted, shipped, received, rejected, cancelled
- `ExpenseCategory`: fuel, maintenance, food, other
- `PaymentMethod`: cash, cheque, bank_transfer, lc, credit_card, other
- `TaskStatus`: pending, completed, missed
- `Country`: EG, SA (already done)

### 2c. Domain exceptions
Base `App\Exceptions\Domain\DomainException` (abstract, `messageKey` + `replace` + `httpStatus`) + 10 subclasses. Refactor the 3 existing ones to extend base. Translation keys in `lang/ar/errors.php` + `lang/en/errors.php`.

Exceptions:
- `InsufficientStockException` (refactor existing)
- `PriceOutOfRangeException`
- `CustomerNotApprovedException`
- `GeofenceViolationException`
- `DuplicateCustomerException`
- `DocumentStateException`
- `ConcurrencyException`
- `ReversalNotAllowedException` (refactor existing)
- `RouteLockException` (refactor existing)
- `CreditLimitExceededException`

### 2d. Value objects
- `app/Support/Money` — bcmath, string operands, `add/mul/percent/toDecimal`
- `app/Support/GpsCoordinate` — haversine `metersTo/within`
- `app/Support/PriceRange` — `contains(Money)`

### 2e. Service interfaces + bindings
7 interfaces in `app/Services/Contracts/`:
- `StockService` — decrement, increment, transfer, balance, reconcile
- `InvoiceService` — create, submit, cancel, amend
- `PaymentService` — collect, cancel
- `PricingService` — priceForRep, rangeForRep
- `DocumentNumberService` — generate
- `LandedCostService` — distribute
- `AlarmService` — raise, acknowledge, resolve

Refactor existing `StockService` to implement the interface. Bind all in `AppServiceProvider`. Remove empty `QrStrategy`/`TaxStrategy` stubs (Phase 8+ work).

---

## Commit 3: `feat: phase 1b — complete database schema`

### 3a. Remove unauthorized tables
- Drop `sync_queue` migration + model
- Drop `cash_box_variance` migration + model
- Delete `Task` model (empty stub, no migration)

### 3b. Fix 16 existing migrations (new alter migrations, not editing old)

| Table | Key changes |
|---|---|
| companies | +7 columns (`abbr`, `legal_entity`, `parent_company`, `commercial_registration_number`, `bank_name`, `bank_account`, `bank_iban`) |
| products | +8 columns (`packaging_type`, `track_batch`, `track_expiry`, `has_variants`, `variant_of`, `is_bundle`, `max_discount`, `valuation_method`), fix unit enum (+`ton`, -`liter`/`gallon`) |
| stocks | quantity→decimal(12,3), +`batch_id`, -`stock_type`, fix unique (partial on batch_id null/not null) |
| stock_movements | quantity_change→decimal(12,3), +`company_id`/`batch_id`/`valuation_rate`/`posting_date`, expand reason enum |
| customers | +9 columns (`customer_group_id`, `territory_id`, `price_list_id`, `account_manager_id`, `added_by`, `status`, `approved_by`, `approved_at`, `rejection_reason`), fix unique constraints (within company) |
| invoices | -softDeletes, fix status enum (draft/submitted/cancelled/amended), +7 columns (`proforma_invoice_id`, `eta_qr`, `zatca_qr`, `posting_date`, `cancelled_at`, `cancelled_by`, `amended_from`) |
| invoice_items | quantity→decimal(12,3), +`batch_id` |
| returns | -softDeletes, fix status enum (draft/submitted/cancelled), +4 columns (`against_invoice_id`, `posting_date`, `cancelled_at`, `cancelled_by`) |
| return_items | quantity→decimal(12,3), +`batch_id` |
| payments | +6 columns (`mode_of_payment_id`, `exchange_rate`, `base_amount`, `posting_date`, `cancelled_at`, `cancelled_by`), replace method enum with FK |
| expenses | +`posting_date`, -`status` enum |
| van_transfers | expand status enum (+shipped/received/cancelled), +`in_transit_warehouse_id` |
| van_transfer_items | quantity→decimal(12,3), +`batch_id` |
| cash_boxes | +`company_id` |
| work_sessions | +`company_id` |
| visits | +3 columns (`daily_visit_assignment_id`, `arrival_confirmed`, `arrival_confirmed_at`), +`custom_visit` to purpose enum |

### 3c. Create 30 new migrations (dependency order)

1. `price_lists` (§11.7)
2. `product_prices` (§11.7)
3. `customer_groups` (§11.8)
4. `territories` (§11.8)
5. `naming_series` (§11.2)
6. `suppliers` (§4.15)
7. `batches` (§4.6)
8. `modes_of_payment` (§4.31)
9. `company_bank_accounts` (§4.29)
10. `tax_templates` (§4.26)
11. `tax_template_lines` (§4.27)
12. `daily_visit_assignments` (§4.17)
13. `visit_reports` (§4.19)
14. `price_quotation_requests` (§4.20)
15. `price_quotations` (§4.21)
16. `proforma_invoices` (§4.22)
17. `proforma_invoice_items` (§4.23)
18. `invoice_taxes` (§4.28)
19. `goods_in_transit` (§4.9)
20. `goods_in_transit_items` (§4.10)
21. `landed_costs` (§4.11)
22. `purchase_requests` (§4.36)
23. `purchase_orders` (§4.37)
24. `purchase_order_items` (§4.38)
25. `supplier_quotations` (§4.39)
26. `alarms` (§4.40)
27. `out_of_stock_requests` (§4.41)
28. `complaints` (§4.42)
29. `warehouse_import_logs` (§4.43)
30. `data_migrations` (§4.45)

Each with: FK indexes, composite `(company_id, ...)` indexes, partial unique on nullable `batch_id`, `CHECK (quantity >= 0)` on `stocks`.

---

## Commit 4: `feat: phase 1c — models, factories, seeders, tests`

### 4a. Models (fix 18 existing + create ~30 new)
- All with `company_id` → `use BelongsToCompany`
- All enums → cast to enum class
- All money → `decimal:2`
- All quantities → `decimal:3`
- All relationships per §4 ERD
- Explicit `$fillable` on every model
- Remove `SoftDeletes` from Invoice/ReturnRecord
- Add `SoftDeletes` to Supplier

New models to create:
PriceList, ProductPrice, CustomerGroup, Territory, NamingSeries, Supplier, Batch, ModeOfPayment, CompanyBankAccount, TaxTemplate, TaxTemplateLine, DailyVisitAssignment, VisitReport, PriceQuotationRequest, PriceQuotation, ProformaInvoice, ProformaInvoiceItem, InvoiceTax, GoodsInTransit, GoodsInTransitItem, LandedCost, PurchaseRequest, PurchaseOrder, PurchaseOrderItem, SupplierQuotation, Alarm, OutOfStockRequest, Complaint, WarehouseImportLog, DataMigration

### 4b. Factories
Create factory for every model (~46 total). Existing `CompanyFactory`/`UserFactory` kept and updated with new columns.

### 4c. RoleSeeder rewrite
- 7 roles per §5: admin, sales_manager, accounts, purchasing, warehouse_keeper, executive, rep
- ~50 permissions in `{resource}.{action}` format per §12
- `DatabaseSeeder`: 1 company + 7 users (one per role)

### 4d. Tests (Phase 1 gate)

| Test file | Verifies |
|---|---|
| `CompanyIsolationTest` | 2 companies, customer in each, login as A → count=1 |
| `ModelRelationshipsTest` | company→user→product→stock→movement chain |
| `StockServiceTest` | increment, decrement, insufficient stock throws `InsufficientStockException` |
| `RoleSeederTest` (rewrite) | 7 roles, key permissions per §12 |
| `MigrationFreshTest` | `migrate:fresh` on PostgreSQL, all 46+ tables present |
| `FactoryTest` | every factory creates valid record |

---

## DoD verification (before Phase 2)

```bash
php artisan migrate:fresh     # clean on PostgreSQL
php artisan tinker            # create company → user → product → stock → movement
vendor/bin/pest               # all tests pass
vendor/bin/pint --test        # clean
```

### DoD checklist
- [ ] `php artisan migrate:fresh` runs clean on PostgreSQL
- [ ] Tinker: create company → user → product → stock → stock_movement with relationships
- [ ] `CompanyIsolationTest` passes
- [ ] `StockServiceTest` passes (including insufficient stock exception)
- [ ] `RoleSeederTest` passes (7 roles, ~50 permissions)
- [ ] All 46+ tables exist with correct columns per §4
- [ ] All 46+ models exist with correct fillable/casts/relationships
- [ ] `BelongsToCompany` trait on all models with `company_id`
- [ ] All 10 enums filled with cases
- [ ] All 10 domain exceptions extend `DomainException`
- [ ] 7 service interfaces defined and bound
- [ ] 3 value objects implemented (Money, GpsCoordinate, PriceRange)
- [ ] `vendor/bin/pest` passes
- [ ] `vendor/bin/pint --test` passes
- [ ] `vendor/bin/phpstan analyse` passes (at configured level)

---

## Decisions recorded

| Decision | Choice | Date |
|---|---|---|
| Arabic font | IBM Plex Sans Arabic (stays as-is) | 2026-07-12 |
| Phase 0 fix commit | Separate commit before Phase 1 | 2026-07-12 |
| Unauthorized tables (sync_queue, cash_box_variance, Task) | Remove all three | 2026-07-12 |
| Phase 1 commit strategy | Split into 3 sub-commits (1a infrastructure, 1b schema, 1c models+tests) | 2026-07-12 |
