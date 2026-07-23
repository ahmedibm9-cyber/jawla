# Development Backlog

19 phases. Each phase = one epic. Each epic contains features → stories → tasks.

---

## Phase 0 — Project Foundation

**Epic:** P0 Project Scaffold

| Feature               | Story                                     | Tasks                                                                                                                             |
| --------------------- | ----------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| P0-F1 Laravel project | Create Laravel 13 project with PostgreSQL | `composer create-project laravel/laravel jawla`; Configure `.env` (DB=pgsql, APP_LOCALE=ar); Create `jawla` database              |
| P0-F2 Filament admin  | Install Filament 4 + admin panel          | `composer require filament/filament:"^4.0"`; `php artisan filament:install --panels`; Create `admin` panel with RTL layout        |
| P0-F3 Core deps       | Install auth, roles, PDF, QR, Excel       | `spatie/laravel-permission`; `mpdf/mpdf`; `simplesoftwareio/simple-qrcode`; `spatie/simple-excel`                                 |
| P0-F4 Frontend        | Tailwind + Arabic fonts + RTL             | Install Noto Kufi Arabic via Google Fonts; Set `dir="rtl"` in layout; Configure `tailwind.config.js` for RTL; Run `npm run build` |
| P0-F5 Rep PWA shell   | Livewire `/app` route group               | Create `routes/app.php` with Livewire auth middleware; Placeholder home page (Welcome, Jawla)                                     |
| P0-F6 Git init        | Initialize git                            | `git init`; `.gitignore` for Laravel defaults; Initial commit `feat: phase 0 project foundation`                                  |

**Definition of Done:** `php artisan serve` → `/admin` in Arabic RTL → `/app` shows placeholder.

---

## Phase 1 — Database & Models

**Epic:** P1 Schema Foundation

| Feature                    | Story                                                                                                                                                                       | Tasks                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| -------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| P1-F1 Companies            | Create `companies` migration + model                                                                                                                                        | Fields: name_ar, name_en, legal_entity, abbr, tax_number, cr_number, address, phone, logo_path, currency (default EGP), vat_percent, is_active. Seed: GPC Egypt entity.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| P1-F2 Users                | Create `users` migration + model (extend Laravel default)                                                                                                                   | Add: company_id FK, phone, employee_code, is_active. Spatie `HasRoles` trait.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| P1-F3 Products             | Create `product_categories`, `products` migrations + models                                                                                                                 | Categories: name_ar, name_en, sort_order. Products: company_id, category_id, sku, name_ar, name_en, packaging_type (bag/jumbo_bag/barrel/tank/drum/iso_tank/other), unit (ton/kg/piece/box/carton), price, cost, vat_applicable, track_batch, track_expiry, has_variants, variant_of, is_bundle, max_discount, valuation_method, image_path, is_active.                                                                                                                                                                                                                                                                                                                                   |
| P1-F4 Batches              | Create `batches` migration + model                                                                                                                                          | product_id, batch_number, manufacture_date, expiry_date, coa_file_path, supplier_id, received_date, is_active.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| P1-F5 Stock                | Create `warehouses`, `stocks`, `stock_movements` migrations + models                                                                                                        | Warehouses: company_id, name_ar, name_en, type (main/van), user_id (nullable for van), is_active. Stocks: warehouse_id, product_id, batch_id (nullable), quantity. Unique (warehouse_id, product_id, batch_id). StockMovements: warehouse_id, product_id, batch_id, quantity_change, valuation_rate, reason (enum), reference morphs, user_id, posting_date.                                                                                                                                                                                                                                                                                                                              |
| P1-F6 GIT + Landed Cost    | Create `goods_in_transit`, `goods_in_transit_items`, `landed_costs` migrations + models                                                                                     | GIT: company_id, purchase_order_id, supplier_id, shipment_number, status (in_transit/at_customs/cleared/received), estimated_arrival, shipping_cost, customs_cost, clearance_cost, freight_cost, posting_date, cancelled_at/by. GITItems: product_id, batch_id, qty, unit_price, currency (USD/CNY/EUR). LandedCosts: goods_in_transit_id or purchase_order_id, cost_type (shipping/customs/clearance/freight/insurance/duty/port_charges/other), amount, notes.                                                                                                                                                                                                                          |
| P1-F7 Routes + Customers   | Create `routes`, `route_user`, `customers` migrations + models                                                                                                              | Routes: company_id, name_ar, name_en, region, is_active. route_user: route_id, user_id pivot. Customers: company_id, route_id, code, name_ar, name_en, phone (unique per company), address, lat/lng, customer_group_id, territory_id, credit_limit, balance, is_active, added_by, status (pending/approved/rejected), approved_by, approved_at, rejection_reason. Soft deletes.                                                                                                                                                                                                                                                                                                           |
| P1-F8 Suppliers            | Create `suppliers` migration + model                                                                                                                                        | company_id, code, name_ar, name_en, type (local/international), contact_person, phone, email, address, payment_terms, is_active.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| P1-F9 Visit System         | Create `work_sessions`, `daily_visit_assignments`, `visits`, `visit_reports` migrations + models                                                                            | WorkSessions: user_id, started_at, ended_at, start_lat, start_lng. Assignments: company_id, user_id, customer_id, visit_date, status (pending/completed/missed), sort_order, assigned_by. Unique (user_id, customer_id, visit_date). Visits: user_id, customer_id, work_session_id, assignment_id nullable, purpose, status (open/closed), checkin_lat/lng, checkout_at, arrival_confirmed, arrived_at. VisitReports: visit_id, summary, customer_feedback, action_taken, follow_up_needed, follow_up_note, submitted_at.                                                                                                                                                                 |
| P1-F10 Pricing             | Create `price_quotation_requests`, `price_quotations` migrations + models                                                                                                   | Requests: company_id, customer_id, user_id, visit_id, product_id, qty_requested, status (requested/priced/confirmed/cancelled). Quotations: request_id, base_price, manager_plus, manager_minus, rep_plus, rep_minus, priced_by, priced_at, valid_until.                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| P1-F11 Proformas           | Create `proforma_invoices`, `proforma_invoice_items` migrations + models                                                                                                    | Proforma: company_id, customer_id, user_id, visit_id, quotation_id, proforma_number, subtotal, vat_amount, total, company_bank_account_id, status (draft/sent/converted_to_invoice/cancelled), notes, posting_date, cancelled_at/by. Items: proforma_id, product_id, qty, unit_price, line_total.                                                                                                                                                                                                                                                                                                                                                                                         |
| P1-F12 Invoices            | Create `invoices`, `invoice_items`, `invoice_taxes` migrations + models                                                                                                     | Invoices: company_id, customer_id, user_id, visit_id, proforma_id, invoice_number, status (draft/submitted/cancelled/amended), subtotal, vat_amount, total, paid_amount, remaining_amount, eta_qr, zatca_qr, tax_template_id, posting_date, issued_at, cancelled_at/by, amended_from. Items: invoice_id, product_id, batch_id, qty, unit_price, line_total. Taxes: invoice_id, tax_template_line_id, description, rate, amount, included_in_rate.                                                                                                                                                                                                                                         |
| P1-F13 Payments + Returns  | Create `payments`, `modes_of_payment`, `returns`, `return_items` migrations + models                                                                                        | Payments: company_id, customer_id, user_id, invoice_id, visit_id, amount, mode_of_payment_id, collected_at, notes, cancelled_at/by, posting_date. ModesOfPayment: company_id, name, type (cash/cheque/bank_transfer/lc/credit_card/other), is_active. Returns: company_id, customer_id, user_id, visit_id, return_number, total, reason, status (draft/submitted/cancelled), posting_date, returned_at, cancelled_at/by. Items: return_id, product_id, batch_id, qty, unit_price, line_total.                                                                                                                                                                                             |
| P1-F14 Expenses + Cash Box | Create `expenses`, `cash_boxes` migrations + models                                                                                                                         | Expenses: company_id, user_id, work_session_id, category (fuel/maintenance/food/other), amount, note, spent_at. CashBoxes: user_id, balance.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| P1-F15 Purchasing          | Create `purchase_requests`, `purchase_orders`, `purchase_order_items`, `supplier_quotations` migrations + models                                                            | PurchaseRequests: company_id, user_id, supplier_id, product_id, qty, offered_price, currency, payment_terms, status (pending/reviewed_by_sales/approved/rejected), reviewed_by, review_notes. PurchaseOrders: company_id, supplier_id, order_number, status (draft/sent/confirmed/partial/received/cancelled), order_date, expected_delivery, payment_terms, currency, subtotal, shipping_cost, total, notes. POItems: po_id, product_id, qty, unit_price, line_total, received_qty. SupplierQuotations: purchase_request_id, company_id, supplier_id, product_id, qty, unit_price, currency, payment_terms, delivery_days, valid_until, status (pending/accepted/rejected), reviewed_by. |
| P1-F16 Alarms + Complaints | Create `alarms`, `out_of_stock_requests`, `complaints` migrations + models                                                                                                  | Alarms: company_id, type (enum 7 types), reference morph, title, description, severity (info/warning/critical), is_read, read_by, read_at. OOSRequests: company_id, user_id, customer_id, product_id, qty_requested, notes, status (open/fulfilled/cancelled). Complaints: company_id, customer_id, user_id, visit_id, type, description, status (open/in_progress/resolved/closed), assigned_to, resolution, resolved_at.                                                                                                                                                                                                                                                                |
| P1-F17 Tax + Bank Mgt      | Create `tax_templates`, `tax_template_lines`, `company_bank_accounts` migrations + models                                                                                   | TaxTemplates: company_id, name, type (selling/buying), is_default, is_active. TaxTemplateLines: template_id, description, charge_type (on_net_total/on_previous_row_amount/actual_amount), rate, included_in_rate, row_id. CompanyBankAccounts: company_id, bank_name, account_name, account_number, iban, swift, currency (default EGP), is_default.                                                                                                                                                                                                                                                                                                                                     |
| P1-F18 Infrastructure      | Create `naming_series`, `van_transfers`, `van_transfer_items`, `data_migrations`, `warehouse_import_logs`, `quality_inspections`, `inspection_readings` migrations + models | NamingSeries: name, prefix, series_format, current_number, pad_length, company_id, is_active. VanTransfers: company_id, from_user_id, to_user_id, status (pending/accepted/rejected). Items: transfer_id, product_id, batch_id, qty. DataMigrations: table_name, rows_migrated, migrated_by, source (odoo_api/excel/manual). WarehouseImportLogs: warehouse_id, imported_by, file_name, rows_imported. QualityInspections: product_id, batch_id, doc_type, doc_id, inspection_type, inspector_id, date, sample_size, status (pending/passed/failed), notes. Readings: inspection_id, parameter, value, min_value, max_value, status (pass/fail).                                          |
| P1-F19 Customer extensions | Create `customer_groups`, `territories`, `customer_addresses`, `customer_contacts` migrations + models                                                                      | CustomerGroups: name_ar, name_en, parent_id, lft, rgt, company_id, is_active. Territories: same pattern. CustomerAddresses: customer_id, type (billing/shipping/both), address_line_1/2, city, state, country, postcode, lat/lng, is_primary. CustomerContacts: customer_id, salutation, first_name, last_name, email, mobile, phone, designation, is_primary.                                                                                                                                                                                                                                                                                                                            |
| P1-F20 Product extensions  | Create `product_barcodes`, `product_prices`, `price_lists`, `product_reorder_levels` migrations + models                                                                    | ProductBarcodes: product_id, barcode (unique), barcode_type, is_default. ProductPrices: product_id, price_list_id, price, uom, min_qty, customer_id, valid_from, valid_upto, is_active. PriceLists: company_id, name, type (selling/buying), is_default, is_active. ReorderLevels: product_id, warehouse_id, reorder_level, reorder_qty, material_request_type.                                                                                                                                                                                                                                                                                                                           |
| P1-F21 StockService        | Create `app/Services/StockService.php`                                                                                                                                      | Methods: `addStock()`, `removeStock()`, `transferStock()`, `getAvailableQty()`, `getBatchStock()`. All operations via `stock_movements` only. DB transaction wrapper.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| P1-F22 NamingSeriesService | Create `app/Services/NamingSeriesService.php`                                                                                                                               | Method: `generate($documentType, $company)`. Reads current_number, increments, returns formatted string.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| P1-F23 Permissions seeder  | Seed Spatie roles + permissions                                                                                                                                             | Create 7 roles. Create all 94 permissions. Assign to roles per matrix.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| P1-F24 Seed data           | Create `Database\Seeders\DatabaseSeeder.php`                                                                                                                                | Seed: 1 company (GPC Egypt), 1 admin user, 1 sales manager, 1 accounts, 1 purchasing, 1 warehouse keeper, 1 executive, 3 reps. 3 routes. 15 customers (mix approved/pending). 25+ products (polymers + chemicals). 3 suppliers (2 international play like SABIC/Borouge, 1 local).                                                                                                                                                                                                                                                                                                                                                                                                        |

**Definition of Done:** `php artisan migrate:fresh --seed` clean. Tinker can traverse company→user→product→stock relationships.

---

## Phase 2 — Auth & Roles

**Epic:** P2 Access Control

| Feature                     | Story                            | Tasks                                                                                                                                                                                                                                                                                                  |
| --------------------------- | -------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| P2-F1 Filament panel auth   | Configure admin panel auth       | Set Filament auth guard; Create login page with RTL Arabic. `/admin/login` in Arabic.                                                                                                                                                                                                                  |
| P2-F2 Role-based navigation | Restrict sidebar per role        | Sales manager sees: Customers, Visits, Quotations, Alarms, Reports. Accounts sees: Products, Invoices, Payments, Reports. Purchasing sees: Suppliers, GIT, POs, Quotations. WH Keeper sees: Stock, Batches, Warehouses. Executive sees: Dashboard, Reports, Alarms (read-only). Admin sees everything. |
| P2-F3 Rep route guard       | Restrict `/app` to rep role only | Middleware on `/app` routes: redirect non-reps to `/admin`. Redirect unauthenticated to login.                                                                                                                                                                                                         |
| P2-F4 Language switcher     | EN/AR toggle                     | Filament locale switcher. Store preference in session. Flip `dir` attribute.                                                                                                                                                                                                                           |

**Definition of Done:** Each role logs in → sees correct nav. Rep cannot open `/admin`. Admin can open both.

---

## Phase 3 — Admin Panel Core

**Epic:** P3 Master Data Management

| Feature                             | Story                                      | Tasks                                                                                                                                                                                                                                                                                                                                                  |
| ----------------------------------- | ------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| P3-F1 Companies CRUD                | Filament resource: Companies               | Form: name_ar, name_en, legal_entity, abbr, tax_number, cr_number, address, phone, logo (file upload), vat_percent, is_active. Table: sortable, searchable. Read-only after creation to prevent orphaned data.                                                                                                                                         |
| P3-F2 Users CRUD                    | Filament resource: Users                   | Form: name, email, phone, employee_code, password, company_id, role select. On role=rep: auto-create van warehouse + cash_box. Cost price visibility gated.                                                                                                                                                                                            |
| P3-F3 Products                      | Filament resource: Products + Categories   | Category form: name_ar, name_en, sort_order. Product form: sku, name_ar, name_en, packaging_type (select), unit (select), price (Accounts only), cost (Accounts only, hidden from sales roles via `hidden()` condition), vat_applicable (toggle), track_batch/track_expiry (toggles), valuation_method (select), is_active. Table: search by name/SKU. |
| P3-F4 Suppliers                     | Filament resource: Suppliers               | Form: code, name_ar, name_en, type (local/international), contact_person, phone, email, address, payment_terms, is_active.                                                                                                                                                                                                                             |
| P3-F5 Price management              | Filament resource with special permissions | Accounts sets base_price + cost_price. Cost_price field uses `hidden(fn () => !auth()->user()->hasPermission('products.view_cost'))`.                                                                                                                                                                                                                  |
| P3-F6 Routes                        | Filament resource: Routes                  | Form: name_ar, name_en, region. BelongsToMany users (reps assigned).                                                                                                                                                                                                                                                                                   |
| P3-F7 Customers                     | Filament resource: Customers               | Form with Leaflet location picker. Status filter (pending/approved/rejected). Approval workflow: pending customers show "Approve" / "Reject" actions. Duplicate check on name_ar + phone.                                                                                                                                                              |
| P3-F8 Warehouses + Stock            | Filament resource: Warehouses              | View stock per warehouse (table). Stock adjustment with reason. CSV stock import (warehouse keeper). Stock export to Excel.                                                                                                                                                                                                                            |
| P3-F9 Customer Groups + Territories | Filament resources                         | Hierarchical tree. Nested set (lft/rgt). Used for report filtering.                                                                                                                                                                                                                                                                                    |

**Definition of Done:** Admin creates companies, users, products, suppliers, customers, loads stock. Cost price hidden from sales manager view.

---

## Phase 4 — Rep PWA Shell

**Epic:** P4 Rep Mobile App

| Feature              | Story                             | Tasks                                                                                                                                                                                                                  |
| -------------------- | --------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| P4-F1 Login          | Rep login via `/app/login`        | Same credentials as admin panel. Redirect to `/app` after login.                                                                                                                                                       |
| P4-F2 Home           | Livewire component: `HomePage`    | Arabic greeting, "Start Work" / "End Work" buttons. Tiles: assigned visits count, pending quotations count, pending new customers count. Cash box balance. Stock search bar at top. Alarm bell icon with unread count. |
| P4-F3 Start work     | Livewire component: `StartWork`   | Button creates `work_session` with current timestamp + GPS lat/lng (browser geolocation API). Redirects to today's visit list.                                                                                         |
| P4-F4 Today's visits | Livewire component: `VisitList`   | Fetches `daily_visit_assignments` for today. Ordered cards: customer name, code, address, sequence number. "Start Visit" button on each card.                                                                          |
| P4-F5 Add customer   | Livewire component: `AddCustomer` | Form: name_ar, name_en, phone, address, GPS capture (browser geolocation), notes. Submits as `status='pending'`.                                                                                                       |

**Definition of Done:** Rep logs in, sees assigned visits, starts work, adds customer.

---

## Phase 5 — Visit Flow with GPS

**Epic:** P5 Field Visit Execution

| Feature                     | Story                                  | Tasks                                                                                                                                                                                                                                                                     |
| --------------------------- | -------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| P5-F1 GPS geofence check    | Livewire component: `VisitGeolocation` | On visit open: get current GPS position. Calculate haversine distance to customer's stored coordinates. If ≤1km → auto-confirm arrival. If >1km → warning + "Manual confirm" button. Records checkin_lat/lng + arrival_confirmed boolean.                                 |
| P5-F2 Visit report          | Livewire component: `VisitReport`      | Form: summary (required textarea), customer_feedback, action_taken, follow_up_needed (toggle), follow_up_note (shown if toggle on). Submit → creates `visit_reports` row.                                                                                                 |
| P5-F3 Visit operations menu | Livewire after visit report            | Buttons: "Sell" → create invoice, "Collect" → record payment, "Return" → process return, "Price Quotation" → request/negotiate price, "Proforma" → create proforma, "Complaint" → log complaint, "Out of Stock" → flag urgent, "Purchase Request" → submit supplier deal. |
| P5-F4 End visit             | Visit closure                          | Sets `visits.checkout_at`, marks assignment as completed. Returns to visit list.                                                                                                                                                                                          |
| P5-F5 End day               | `EndWork`                              | Closes `work_session`. Shows summary: visits completed vs assigned, total sales, collections, returns, expenses, cash box balance.                                                                                                                                        |

**Definition of Done:** Rep visits customer, GPS confirms, submits report.

---

## Phase 6 — Price Quotation & Pricing Chain

**Epic:** P6 Multi-level Pricing

| Feature                           | Story                                                           | Tasks                                                                                                                                                                    |
| --------------------------------- | --------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| P6-F1 Request quotation           | Rep selects product + qty → submits request                     | Creates `price_quotation_request` with status='requested'. Auto-generates alarm for sales manager.                                                                       |
| P6-F2 Manager sets price          | Filament action on quotation request                            | Manager sees request → sets base_price + manager_plus + manager_minus. Submits → status='priced'. Cost price never visible.                                              |
| P6-F3 Manager delegates rep range | Manager sets rep_plus ≤ manager_plus, rep_minus ≤ manager_minus | Validates: rep range is sub-range of manager range. Saves to `price_quotations`.                                                                                         |
| P6-F4 Rep negotiates              | Rep sees allowed range. Proposes final price within rep range.  | On "Negotiate": rep enters final price. System validates: final_price >= base_price - rep_minus AND final_price <= base_price + rep_plus. If valid → status='confirmed'. |
| P6-F5 Alarm on request            | Auto-create alarm                                               | Type='price_quotation_requested', severity='warning'.                                                                                                                    |

**Definition of Done:** Rep requests price, manager sets range, rep negotiates within range.

---

## Phase 7 — Proforma Invoice

**Epic:** P7 Proforma Creation

| Feature                  | Story                    | Tasks                                                                                                                                                                                          |
| ------------------------ | ------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| P7-F1 Create proforma    | From confirmed quotation | Livewire: select quotation → creates `proforma_invoice` with items. Validates unit_price within rep range. Auto-fills company_bank_account. Generates proforma_number via NamingSeriesService. |
| P7-F2 View proforma      | Proforma detail page     | Shows: customer info, line items, totals, bank details, QR placeholder.                                                                                                                        |
| P7-F3 Convert to invoice | Action on proforma       | Status becomes 'converted_to_invoice'. New invoice created from proforma data.                                                                                                                 |
| P7-F4 Cancel proforma    | Action on draft proforma | Sets status='cancelled'. Only if not yet converted to invoice.                                                                                                                                 |

**Definition of Done:** Rep creates proforma within price range. Blocked outside range.

---

## Phase 8 — Sales & Invoicing

**Epic:** P8 Field Invoice

| Feature               | Story                                                            | Tasks                                                                                                                                                                                          |
| --------------------- | ---------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| P8-F1 Stock grid      | Rep sees van stock + main warehouse + GIT availability           | Table: product name, batch (if tracked), van qty, main wh qty, GIT qty, expiry date. Searchable.                                                                                               |
| P8-F2 Create invoice  | Select items + quantities + batches                              | Validates van stock >= requested qty for each item. Creates invoice + items + stock_movements in DB transaction. Auto-number via NamingSeriesService.                                          |
| P8-F3 Atomic sale     | `DB::transaction()`                                              | Inside transaction: create invoice row → create invoice_items rows → decrement each product in van stock → create stock_movements for each → update customer balance. Rollback on any failure. |
| P8-F4 Batch selection | If product `track_batch=true`, show batch dropdown per line item | Dropdown shows batches with available qty in van. Only batches with qty>0 shown.                                                                                                               |
| P8-F5 Invoice PDF     | Generate bilingual AR/EN PDF                                     | Use mPDF. Template: company logo + info, customer info, invoice number + date, items table (AR/EN headers), subtotal, VAT, total, bank details, ETA QR code.                                   |
| P8-F6 ETA QR          | Generate QR on invoice PDF                                       | JSON: {seller_name, tax_number, timestamp, total, tax_total}. Base64 encoded QR on PDF.                                                                                                        |
| P8-F7 Invoice list    | Rep sees own invoices. Admin/manager sees all.                   | Filter by date, status, customer. Export to Excel.                                                                                                                                             |

**Definition of Done:** Rep sells with batch selection, atomic transaction, PDF with QR, stock deducted.

---

## Phase 9 — Collections, Returns & Cash Box

**Epic:** P9 Financial Ops

| Feature             | Story                        | Tasks                                                                                                                                                        |
| ------------------- | ---------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| P9-F1 Collection    | Record payment from customer | Form: amount, mode_of_payment (cash/cheque/transfer), invoice optional. Increases cash_box.balance. Decreases customer.balance. Updates invoice.paid_amount. |
| P9-F2 Return        | Process customer return      | Select product + batch + qty. Increases van stock (stock_movement reason='return'). Decreases customer balance.                                              |
| P9-F3 Expense       | Log field expense            | Form: category (fuel/maintenance/food/other), amount, note. Decreases cash_box.balance.                                                                      |
| P9-F4 Cash box view | Show current balance         | Rep sees own cash_box.balance. Transaction history (collections in, expenses out).                                                                           |

**Definition of Done:** Rep collects, returns, logs expense. Cash box + balances reconcile.

---

## Phase 10 — Purchase Requests & Supplier Management

**Epic:** P10 Procurement

| Feature                    | Story                                  | Tasks                                                                                                                                   |
| -------------------------- | -------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| P10-F1 Purchase request    | Rep submits supplier deal              | Form: supplier, product, qty, offered_price, currency, payment_terms. Creates alarm for purchasing + sales.                             |
| P10-F2 Sales veto          | Sales manager reviews requests         | Can set status='rejected' with reason for slow-moving products. Otherwise passes to purchasing.                                         |
| P10-F3 Supplier quotations | Purchasing creates comparison          | For a product, creates multiple quotation records (one per supplier). Side-by-side view: price, currency, payment_terms, delivery_days. |
| P10-F4 Accept quotation    | Purchasing accepts one                 | Status='accepted' on chosen quotation. Others auto-rejected. Creates purchase_order from accepted quotation.                            |
| P10-F5 Purchase order      | Create PO from quotation or manual     | Form: supplier, items (product+qty+unit_price+currency), expected_delivery, payment_terms, shipping_cost. Sequential numbering.         |
| P10-F6 Receive PO          | Mark items as partially/fully received | Updates purchase_order_items.received_quantity. If all received → PO status='received'.                                                 |

**Definition of Done:** Rep submits purchase request. Purchasing compares 3 offers, creates PO.

---

## Phase 11 — Goods in Transit & Landed Cost

**Epic:** P11 International Shipments

| Feature                         | Story                                       | Tasks                                                                                                                                                                                                                                                                                      |
| ------------------------------- | ------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| P11-F1 Create GIT shipment      | Purchasing creates international shipment   | Form: supplier, PO reference, shipment_number, estimated_arrival, shipping_cost, customs_cost, clearance_cost, freight_cost. Items: product, qty, unit_price, currency.                                                                                                                    |
| P11-F2 Update GIT status        | Status progression                          | in_transit → at_customs → cleared → received. Each status change logs timestamp. Past ETA + still in_transit → critical alarm.                                                                                                                                                             |
| P11-F3 Landed costs             | Add costs to GIT                            | cost_type (duty/port_charges/shipping/customs/clearance/freight/insurance/other), amount.                                                                                                                                                                                                  |
| P11-F4 Receive GIT              | Move goods to main warehouse                | On status='received': create stock_movements for each item (reason='transit_in'). Distribute landed costs across items proportionally by quantity. Update product cost price (moving average: (current_cost * current_qty + shipment_cost * shipment_qty) / (current_qty + shipment_qty)). |
| P11-F5 Transit stock visibility | Reps can see GIT quantities in stock search | GIT items with status != 'received' appear as "Expected {qty} tons (arriving {date})".                                                                                                                                                                                                     |

**Definition of Done:** Create shipment, add costs, receive → stock and cost price updated.

---

## Phase 12 — Batch Tracking, COA & Expiry

**Epic:** P12 Lot Traceability

| Feature                      | Story                                                             | Tasks                                                                                                                                 |
| ---------------------------- | ----------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| P12-F1 Create batch          | Warehouse keeper creates batch record                             | Form: product, batch_number, manufacture_date, expiry_date, COA PDF upload, supplier, received_date.                                  |
| P12-F2 COA upload            | Attach PDF to batch                                               | File upload → save to storage/batches/coa/. Link to batch.coa_file_path.                                                              |
| P12-F3 View stock per batch  | Admin/warehouse sees breakdown                                    | Table: batch_number, warehouse, qty, expiry_date, days_until_expiry.                                                                  |
| P12-F4 Expiry alarm          | Auto-alarm for batches ≤30 days from expiry                       | Cron / scheduler: nightly check batches where expiry_date <= now() + 30 days. Create alarm type='batch_expiring', severity='warning'. |
| P12-F5 Batch on transactions | Batch_id required for tracked products on invoice/PO/return items | Validates: if product.track_batch=true AND batch_id is null → block with error.                                                       |

**Definition of Done:** Product with batch tracking received, sold, returned with batch selection. COA viewable.

---

## Phase 13 — Alarms & Notifications

**Epic:** P13 Alert System

| Feature                    | Story                                      | Tasks                                                                                                                                                                                                       |
| -------------------------- | ------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| P13-F1 Alarm generators    | 7 trigger types implemented                | OutOfStockRequest → critical. Complaint → critical. NewCustomer pending → warning. PriceQuotation requested → warning. PurchaseRequest submitted → info. GIT past ETA → critical. Batch expiring → warning. |
| P13-F2 Alarm dashboard     | Admin panel: grouped by severity           | Critical alarms red, warning yellow, info gray. Filters by type, severity, date. Tabs: All / Critical / Warning / Info.                                                                                     |
| P13-F3 Alarm response flow | Manager actions                            | Acknowledge → status changes. Assign to user. Resolve with note.                                                                                                                                            |
| P13-F4 Alarm badge         | Bell icon in app + admin with unread count | Livewire poll every 30s. Red badge with count. Click → view alarms.                                                                                                                                         |

**Definition of Done:** Rep flags out-of-stock → red alarm for manager + accounts + executive. Manager acknowledges.

---

## Phase 14 — Egypt ETA E-Invoicing

**Epic:** P14 Tax Compliance

| Feature                                 | Story                        | Tasks                                                                                                                                                   |
| --------------------------------------- | ---------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| P14-F1 ETA QR generation                | QR code on every invoice PDF | Data: company.name_ar, company.tax_number, invoice.created_at (ISO8601), invoice.total, invoice.vat_amount. JSON → Base64 → QR image → embedded in PDF. |
| P14-F2 Bilingual PDF                    | AR/EN invoice template       | mPDF with RTL. Table headers in Arabic (الصنف, الكمية, السعر, الإجمالي) + English. Company logo. Bank details in footer.                                |
| P14-F3 Sequential numbering per company | Naming series for invoices   | Format: `INV-GPC-{YYYY}-{#####}`. Incremented per invoice creation.                                                                                     |

**Definition of Done:** Invoice PDF with valid ETA QR scans correctly.

---

## Phase 15 — Inter-Company (deferred v2)

Skipped for v1.

---

## Phase 16 — Reports & Dashboard

**Epic:** P16 Business Intelligence

| Feature                    | Story                     | Tasks                                                                                                                                          |
| -------------------------- | ------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| P16-F1 Sales dashboard     | Widgets + charts          | Total sales (day/week/month), top products bar chart, per-rep ranking, conversion rate (quotations → invoices). Filament widgets + ApexCharts. |
| P16-F2 Visit dashboard     | Visit analytics           | Planned vs actual visits, missed visit rate, avg visit duration, visit per rep pie.                                                            |
| P16-F3 Financial dashboard | Revenue, VAT, aging       | Revenue trend line, VAT collected, aging of receivables (though prepaid, still useful for returns).                                            |
| P16-F4 Stock dashboard     | Low stock, expiry         | Low stock alerts table, expiring batches table, GIT ETA timeline.                                                                              |
| P16-F5 Alarm dashboard     | Alarm stats               | Open alarms by severity, response time, most common types.                                                                                     |
| P16-F6 Excel export        | All tables exportable     | spatie/simple-excel integration. Download any filtered table.                                                                                  |
| P16-F7 Executive dashboard | Read-only aggregated view | Executive role sees same dashboards but no action buttons, no edit links.                                                                      |
| P16-F8 Leaflet visit map   | GPS pins on map           | Visits plotted on Leaflet map. Color-coded by status (green=arrived, red=missed).                                                              |

**Definition of Done:** Admin sees all numbers, exports Excel, drills into reports.

---

## Phase 17 — Data Migration from Odoo

**Epic:** P17 Data Import

| Feature                | Story                    | Tasks                                                                                                               |
| ---------------------- | ------------------------ | ------------------------------------------------------------------------------------------------------------------- |
| P17-F1 Customer import | CSV/Excel upload         | Columns: name_ar, name_en, phone, address, route, customer_group. Duplicate check on phone. Map to customers table. |
| P17-F2 Supplier import | Same pattern             | Map to suppliers table.                                                                                             |
| P17-F3 Product import  | SKU + names + categories | Map to products + categories. Create categories on-the-fly if not exist.                                            |
| P17-F4 Opening stock   | Batch import from date   | File: product, batch, warehouse, qty, cost. Creates initial stock_movements.                                        |
| P17-F5 Open invoices   | Import unpaid invoices   | If any (prepaid means unlikely, but possible credit notes).                                                         |
| P17-F6 Migration log   | Track what was imported  | DataMigrations table: rows_migrated, source, date.                                                                  |

**Definition of Done:** All data imported. Opening balances verified against Odoo.

---

## Phase 18 — PWA Polish

**Epic:** P18 Installable App

| Feature                            | Story                  | Tasks                                                                                           |
| ---------------------------------- | ---------------------- | ----------------------------------------------------------------------------------------------- |
| P18-F1 Manifest                    | `manifest.json`        | Name: "جولة - Jawla". Theme color: teal #4DB848. Icons (192x192, 512x512). Display: standalone. |
| P18-F2 Service worker              | Basic offline shell    | Cache app shell. Show offline indicator.                                                        |
| P18-F3 "Add to Home Screen" prompt | Browser install prompt | Deferred prompt pattern.                                                                        |

**Definition of Done:** "Add to Home Screen" works. Shell loads offline.

---

## Phase 19 — Seed Data & Final Test

**Epic:** P19 Demo-Ready

| Feature                   | Story                           | Tasks                                                                                                                                                                                                                                                                                                                                                     |
| ------------------------- | ------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| P19-F1 Full seeder        | All data for demo               | 1 company, 7 users (one per role), 3 routes, 15 customers (mix approved/pending), 25+ products (PP, HDPE, LDPE, LLDPE, PVC, PET, PS + chemicals), 3 suppliers (2 international like SABIC, 1 local), batches with COA + expiry dates, sample visits + quotations + proforma + invoices, goods in transit shipment with landed costs, complaints + alarms. |
| P19-F2 All 7 roles seeder | Permission + role assignment    | Each role assigned correct permissions per matrix. Default passwords for demo: `password`.                                                                                                                                                                                                                                                                |
| P19-F3 README             | Project README with credentials | English + Arabic. Login credentials table. Test flows: "how to run a visit", "how to create an invoice", "how to receive goods".                                                                                                                                                                                                                          |
| P19-F4 Final test pass    | Verify all hard rules           | Automated test: sell with insufficient stock → blocked. Create proforma outside price range → blocked. Pending customer invoice → blocked. Cost price hidden for sales role.                                                                                                                                                                              |

**Definition of Done:** `php artisan migrate:fresh --seed` → fully explorable demo with all features seeded.
