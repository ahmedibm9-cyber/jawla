# Jawla (جولة) — Field Sales CRM/ERP · Production Build Guide
# Company: اللدائن العالمية (Global Plastic Company (GPC))
# Client: عمرو حكيم (Amr) — System Admin
# Current system: Odoo + Excel

> **This document is the single source of truth for building the system.**
> It is written for an AI coding agent (Claude Code) to execute directly.
> Every technical decision has already been made. Do **not** ask the owner
> to choose frameworks, libraries, or architecture — build what is specified
> here. Work through the phases **in order**. Do not skip ahead.

---

## 0. How to read and use this guide (instructions for Claude Code)

1. **Build phase by phase, top to bottom.** Each phase has a *Goal*, *Tasks*, and a *Definition of Done*. Do not start a phase until the previous one's Definition of Done is met.
2. **Commit after every phase** with a clear message (e.g. `feat: phase 3 admin panel`).
3. **Never break these two hard rules** (detailed in §7):
   - A sale can never reduce van stock below zero.
   - A sale must be wrapped in a database transaction (invoice + stock + cash box all succeed or all roll back).
4. **The UI must be bilingual (Arabic + English) with full RTL support** from day one. Arabic is the primary language. Invoices are bilingual AR/EN.
5. When a phase is done, print a short summary of what was built and how to test it manually, then continue.
6. If a pinned package version genuinely conflicts, fall back to the nearest stable minor version and note it — do not stop to ask.

---

## 1. What we are building (plain language)

A **field sales + distribution ERP** for a plastics/trading company (شركة اللدائن العالمية / Global Plastic Company — GPC). Currently operates in Egypt with Saudi Arabia expansion planned. They buy primarily from international suppliers (90% import), sell locally, and need to track everything from goods-in-transit on a ship to a field rep visiting a customer.

### The full workflow (end to end):

1. **Sales manager assigns daily tasks.** Each morning, the sales manager assigns each rep a set of visits for the day (e.g., 5 specific customers to visit). The rep sees their assigned tasks for the day.

2. **Rep starts the day.** The rep checks in, sees their assigned visits, and heads out.

3. **Rep visits customers.** At each customer, the rep's GPS is auto-verified against the stored customer location (geofence of 1-2 km radius). When the rep is within range, they can confirm arrival — this proves the visit actually happened. Rep submits a structured visit report after each visit.

4. **Price quotation workflow.** If the customer asks for a price, the rep submits a price quotation request. The sales manager reviews it, sets the base price, and gives the rep a plus/minus range. The rep negotiates with the customer within that range.

5. **Proforma invoice.** Once the customer agrees on price, the rep creates and sends a proforma invoice directly — no need to go through Accounts, because the price is already manager-approved. The system enforces that the rep stays within their allowed price range.

6. **Purchase requests.** If a supplier offers a good deal on a material, the rep submits a purchase request. This is seen by both Sales Management and Purchasing Management. Sales may veto items that sell slowly; Purchasing determines the best buy price.

7. **Out-of-stock alerts.** If a customer wants a product that's out of stock, the rep flags an urgent request. This creates a red alarm visible to the sales manager, finance/accounts, and the executive (فيور).

8. **CRM / Complaints.** Any customer complaint (e.g., received non-conforming materials) is flagged as an alarm. The sales manager is responsible for responding.

9. **Warehouse daily import.** The warehouse keeper imports the stock report from an external system every day in a specific format. Reps can see real-time availability.

10. **New customer addition by rep.** During a visit, a rep may discover a new customer. The rep adds the customer's data, but the customer is **pending** until the sales manager approves.

11. **Goods in transit.** Products on order from international suppliers (on a ship, in transit) must be visible in the system before they arrive at the warehouse.

12. **Landed cost.** Shipping, customs, clearance, and freight costs are added to the product cost price.

13. **Batch/Lot tracking with Certificate of Analysis (COA).** Products are tracked by batch/lot number. Some batches have a COA document attached. Expiry dates are tracked.

14. **Supplier quotation comparison.** Purchasing team compares offers from multiple suppliers before buying.

15. **Inter-company transactions.** Egypt entity sells to Saudi entity and vice versa (planned v2).

16. **Multi-currency.** Buys in USD, CNY (Yuan). Sells in EGP. Purchase orders in supplier currency, all financial reports in EGP.

### Company profile:
| Detail | Value |
|---|---|
| Company name | شركة اللدائن العالمية (Global Plastic Company — GPC) |
| Legal entities | Egypt (active). Saudi Arabia (planned v2) |
| Tax registration | Configurable per company entity (Egypt: 618-549-994) |
| Branches | Yes, separate commercial registrations (details TBD) |
| Products | Wide range: PP, PE, PVC, PET, PS polymers + chemicals (ethylene glycol, ethyl acetate, caustic soda, etc.) |
| Packaging | Bags, Jumbo bags, Barrels, Drums, Tanks, ISO Tanks |
| Unit | Ton (طن) — buy and sell |
| Suppliers | 90% international import, 10% local |
| Current system | Odoo + Excel |
| System admin | Amr (عمرو حكيم) — 01020909207 |
| Expected users | ~10 |
| Budget | To be determined |
| E-invoicing Egypt | Registered with ETA |
| E-invoicing Saudi | Planned v2 (ZATCA) |

---

## 2. The locked tech stack (do not substitute)

| Layer | Choice | Version | Why it's fixed |
|---|---|---|---|
| Language | PHP | 8.3 | Required by the framework |
| Backend framework | **Laravel** | 13.x | Most stable, battle-tested, huge ecosystem |
| Admin panel | **Filament** | 4.x | Complete enterprise admin UI (tables, forms, charts, roles) as configuration, not hand-written code |
| Rep app interactivity | **Livewire** | 3.x | Reactive UI in PHP — no separate JavaScript app, no API layer |
| Lightweight JS | **Alpine.js** | 3.x | Ships with Livewire; used only for tiny front-end interactions |
| CSS | **Tailwind CSS** | 3.x | Utility CSS; RTL-friendly |
| Database | **PostgreSQL** | 16 | Relational data + real transactions (essential for sales/stock/cash integrity) |
| Auth | Laravel built-in + **Sanctum** | latest | Sessions for web, tokens if a native app is added later |
| Roles & permissions | **spatie/laravel-permission** | latest | Admin / sales manager / purchasing / accounts / warehouse keeper / executive (فيور) / rep |
| Maps & GPS | **Leaflet** + **OpenStreetMap** | latest | Free, **no API key, no billing** |
| PDF invoices | **mpdf/mpdf** | latest | Native RTL support for bilingual AR/EN invoices |
| QR codes | **simplesoftwareio/simple-qrcode** | latest | Invoice QR (ZATCA compliant later) |
| Excel export/import | **spatie/simple-excel** + Laravel native Export | latest | One-click report export + stock import |
| Background jobs | Laravel Queues (database driver) | built-in | Report generation, notifications, alerts |
| Realtime (optional) | **Laravel Reverb** | built-in | Live dashboard updates |

**Design system decision:** Filament's component library (Tailwind) for admin, Tailwind + Livewire for the rep app. One codebase, RTL natively supported.

---

## 3. Visual identity & UI rules (keep it minimal)

- **Primary color:** Teal green `#4DB848` and steel blue `#2C6FB4` (GPC corporate colors). Neutral grays and white backgrounds.
- **Alarm/alert color:** Red indicators on the side for urgent requests, out-of-stock notifications, and customer complaints.
- **Minimal by default:** Clean white cards, large readable type, generous spacing. No gradients, no heavy shadows, no decorative clutter. Fast, outdoor-friendly.
- **Typography:** system UI font stack + **"Noto Kufi Arabic"** from Google Fonts for Arabic (matches GPC brand). English: Montserrat Bold for headings, Open Sans for body.
- **Direction:** default `dir="rtl"`, `lang="ar"`. EN/AR language switch that flips direction.
- **Icons:** Heroicons (ships with Filament) for admin; same set for the rep app.
- **Rep app is mobile-first:** everything for a phone screen. Big tap targets (min 44px), bottom-anchored action buttons, card-based lists.
- **Dark mode:** enable Filament's built-in dark mode (helps in sunlight).
- **Currency display:** show amounts in EGP. Purchase orders in USD/CNY shown with exchange rate to EGP.

---

## 4. Database schema (build exactly this)

Use Laravel migrations. All tables use `bigIncrements` IDs unless noted. All money stored as `decimal(12,2)`. All tables have `timestamps`. Use foreign keys with appropriate `onDelete` behavior. Add a soft-delete (`deleted_at`) to `customers`, `products`, `invoices`, and `users`.

### 4.1 `companies`
The business(es) the system serves — **Global Plastic Company (GPC)** has its primary entity in Egypt. Saudi Arabia entity planned for v2.
`id, name_ar, name_en, legal_entity (string), parent_company (string default 'Global Plastic Company (GPC)'), abbr (string), tax_number, commercial_registration_number (السجل التجاري), address, phone, logo_path, currency (string default 'EGP'), vat_percent (decimal 5,2), bank_name, bank_account, bank_iban, is_active`
> Egypt entity is the primary v1 company. Saudi entity planned v2. `abbr` used in naming series (e.g. 'GPC'). VAT: Egypt 14%.

### 4.2 `users`
Reps, sales managers, accounts, purchasing, warehouse keepers, executives, admins.
`id, company_id (FK), name, email (unique), phone, password, employee_code (unique), is_active, remember_token`

### 4.3 `warehouses`
Main warehouse(s) and each rep's mobile van stock.
`id, company_id (FK), name_ar, name_en, type (enum: 'main','van'), user_id (FK nullable — set when type='van', the owning rep), is_active`

### 4.4 `product_categories`
`id, company_id (FK), name_ar, name_en, sort_order`

### 4.5 `products`
`id, company_id (FK), category_id (FK), sku (unique), name_ar, name_en, packaging_type (enum: 'bag','jumbo_bag','barrel','drum','tank','iso_tank','other'), unit (enum: 'ton','kg','piece','box','carton'), price (decimal 12,2 — base selling price set by Accounts), cost (decimal 12,2 — base cost), vat_applicable (bool), track_batch (bool), track_expiry (bool), has_variants (bool default false), variant_of (FK self nullable), is_bundle (bool default false), max_discount (decimal 5,2 nullable), valuation_method (enum: 'fifo','moving_average','standard' default 'moving_average'), image_path, is_active`

### 4.6 `batches` (التشغيلات / الباتشات)
Batch/lot tracking for products. Each batch has an optional COA.
`id, product_id (FK), batch_number (string), manufacture_date (date nullable), expiry_date (date nullable), coa_file_path (string nullable — Certificate of Analysis PDF), supplier_id (FK nullable), received_date (date), is_active`
> Some products need batch tracking, some don't (per `products.track_batch`).

### 4.7 `stocks`
Current quantity of a product (optionally per batch) in a specific warehouse.
`id, warehouse_id (FK), product_id (FK), batch_id (FK nullable — null if product doesn't use batch tracking), quantity (decimal 12,3 — supports fractional tons),`
Unique index on `(warehouse_id, product_id, batch_id)` — batch_id nullable creates a partial unique.

### 4.8 `stock_movements` (audit log — never edited, only appended)
Every change to stock, ever.
`id, warehouse_id (FK), product_id (FK), batch_id (FK nullable), quantity_change (decimal 12,3), valuation_rate (decimal 12,2 nullable — cost at time of movement), reason (enum: 'sale','return','transfer_in','transfer_out','adjustment','initial','import','purchase','landed_cost','transit_in','transit_out','inter_company'), reference_type (string), reference_id (bigint), user_id (FK), posting_date (date), created_at`

### 4.9 `goods_in_transit` (البضاعة في الطريق)
International shipments coming from suppliers (on ship, in customs). This is the sole tracking mechanism — no separate transit warehouse.
`id, company_id (FK), purchase_order_id (FK nullable), supplier_id (FK), shipment_number (string unique), status (enum: 'in_transit','at_customs','cleared','received'), estimated_arrival_date (date nullable), shipping_cost (decimal 12,2), customs_cost (decimal 12,2), clearance_cost (decimal 12,2), freight_cost (decimal 12,2), posting_date (date), cancelled_at (datetime nullable), cancelled_by (FK users nullable), created_at`
> When goods arrive at warehouse, they move from `goods_in_transit` to `stocks` with `stock_movements.reason='transit_in'`. The landed costs are added to the product cost.

### 4.10 `goods_in_transit_items`
`id, goods_in_transit_id (FK), product_id (FK), batch_id (FK nullable), quantity (decimal 12,3), unit_price (decimal 12,2 — purchase price in foreign currency), currency (enum: 'USD','CNY','EUR')`

### 4.11 `landed_costs` (تكلفة الشحن والجمارك)
Additional costs added to a purchase that affect product cost. Applies to both international and local purchases.
`id, goods_in_transit_id (FK nullable for international), purchase_order_id (FK nullable for local), cost_type (enum: 'shipping','customs','clearance','freight','insurance','duty','port_charges','other'), amount (decimal 12,2), notes`

### 4.12 `routes` (خطوط السير)
`id, company_id (FK), name_ar, name_en, region, is_active`

### 4.13 `route_user` (which reps are assigned to which routes — pivot)
`id, route_id (FK), user_id (FK)`

### 4.14 `customers`
`id, company_id (FK), route_id (FK), code (unique within company), name_ar, name_en, phone (unique within company), address, latitude (decimal 10,7 nullable), longitude (decimal 10,7 nullable), customer_group_id (FK nullable), territory_id (FK nullable), price_list_id (FK nullable), account_manager_id (FK users nullable), credit_limit (default 0), balance (default 0), is_active, added_by (FK to users nullable), status (enum: 'pending','approved','rejected' default 'approved'), approved_by (FK to users nullable), approved_at (nullable), rejection_reason (nullable)`
> `phone` unique within company enables Rule 5 duplicate check.
> `balance` = how much this customer currently owes (positive = owes us).

### 4.15 `suppliers`
`id, company_id (FK), code (unique within company), name_ar, name_en, type (enum: 'local','international'), contact_person, phone, email, address, payment_terms (text), is_active`
> 90% of suppliers are international.

### 4.16 `work_sessions` (rep's daily check-in/check-out)
`id, user_id (FK), started_at, ended_at (nullable), start_latitude, start_longitude`

### 4.17 `daily_visit_assignments` (tasks assigned by sales manager each day)
`id, company_id (FK), user_id (FK — the rep), customer_id (FK), visit_date (date), status (enum: 'pending','completed','missed'), sort_order (integer — visit sequence), assigned_by (FK to users — the sales manager), created_at`
> Unique constraint on `(user_id, customer_id, visit_date)`.
> Default: 5 assigned visits per rep per day.

### 4.18 `visits`
`id, user_id (FK), customer_id (FK), work_session_id (FK), daily_visit_assignment_id (FK nullable), purpose (enum: 'sale','collection','return','survey','other','custom_visit'), status (enum: 'open','closed'), checkin_latitude, checkin_longitude, checkout_at (nullable), arrival_confirmed (boolean), arrival_confirmed_at (datetime nullable)`

### 4.19 `visit_reports` (structured report submitted after each visit)
`id, visit_id (FK), summary (text), customer_feedback (text nullable), action_taken (text nullable), follow_up_needed (boolean), follow_up_note (text nullable), submitted_at`

### 4.20 `price_quotation_requests` (طلب عرض سعر)
`id, company_id (FK), customer_id (FK), user_id (FK), visit_id (FK nullable), product_id (FK), quantity_requested (decimal 12,3), status (enum: 'requested','priced','confirmed','cancelled' — default 'requested'), requested_at`

### 4.21 `price_quotations` (عروض الأسعار)
`id, price_quotation_request_id (FK), base_price (decimal 12,2), manager_plus (decimal 12,2), manager_minus (decimal 12,2), rep_plus (decimal 12,2), rep_minus (decimal 12,2), priced_by (FK to users), priced_at, valid_until (date nullable)`
> Manager receives ±X from Accounts. Manager gives rep ±Y sub-range. System enforces rep_plus <= manager_plus and rep_minus <= manager_minus.

### 4.22 `proforma_invoices` (الفاتورة المبدئية)
`id, company_id (FK), customer_id (FK), user_id (FK), visit_id (FK nullable), price_quotation_id (FK nullable), proforma_number (string unique), subtotal, vat_amount, total, company_bank_account_id (FK nullable), status (enum: 'draft','sent','converted_to_invoice','cancelled'), notes (text nullable), posting_date (date), cancelled_at (datetime nullable), cancelled_by (FK users nullable), created_at`

### 4.23 `proforma_invoice_items`
`id, proforma_invoice_id (FK), product_id (FK), quantity (decimal 12,3), unit_price, line_total`

### 4.24 `invoices`
`id, company_id (FK), customer_id (FK), user_id (FK), visit_id (FK nullable), proforma_invoice_id (FK nullable), invoice_number (string unique), status (enum: 'draft','submitted','cancelled','amended'), subtotal, vat_amount, total, paid_amount, remaining_amount, eta_qr (text nullable), zatca_qr (text nullable), posting_date (date), issued_at, cancelled_at (datetime nullable), cancelled_by (FK users nullable), amended_from (FK self nullable)`
> Egypt ETA e-invoicing: must generate compliant QR code. Saudi ZATCA: to be implemented when phase 2 is clarified.

### 4.25 `invoice_items`
`id, invoice_id (FK), product_id (FK), batch_id (FK nullable), quantity (decimal 12,3), unit_price, line_total`

### 4.26 `tax_templates`
`id, company_id (FK), name, type (enum: 'selling','buying'), is_default (bool), is_active`
> One template for v1: "Standard VAT 14%" for Egypt. Saudi VAT 15% template deferred to v2.

### 4.27 `tax_template_lines`
`id, tax_template_id (FK), description, charge_type (enum: 'on_net_total','on_previous_row_amount','actual_amount'), rate (decimal 5,2), included_in_rate (bool default false), row_id (int nullable)`

### 4.28 `invoice_taxes`
`id, invoice_id (FK), tax_template_line_id (FK nullable), description, rate (decimal 5,2), amount (decimal 12,2), included_in_rate (bool)`
> Auto-populated from the invoice's selected tax template. Enables tax breakdown on PDF and e-invoicing reports.

### 4.29 `company_bank_accounts`
`id, company_id (FK), bank_name, account_name, account_number, iban, swift, currency (string default 'EGP'), is_default`
> Proforma invoices pull bank details from here.

### 4.30 `payments` (collections / التحصيلات)
`id, company_id (FK), customer_id (FK), user_id (FK), invoice_id (FK nullable), visit_id (FK nullable), amount, mode_of_payment_id (FK), exchange_rate (decimal 12,6 nullable), base_amount (decimal 12,2 nullable), collected_at, notes, cancelled_at (datetime nullable), cancelled_by (FK users nullable), posting_date (date)`

### 4.31 `modes_of_payment` (طرق الدفع)
`id, company_id (FK), name (string), type (enum: 'cash','cheque','bank_transfer','lc','credit_card','other'), is_active`
> Master table — add methods without migrations. LC, TT, cash, cheque, bank transfer all supported.

### 4.32 `returns`
`id, company_id (FK), customer_id (FK), user_id (FK), visit_id (FK nullable), return_number (string unique), total, reason (text nullable), status (enum: 'draft','submitted','cancelled'), posting_date (date), returned_at, cancelled_at (datetime nullable), cancelled_by (FK users nullable)`

### 4.33 `return_items`
`id, return_id (FK), product_id (FK), batch_id (FK nullable), quantity (decimal 12,3), unit_price, line_total`

### 4.34 `expenses` (rep field expenses)
`id, company_id (FK), user_id (FK), work_session_id (FK nullable), category (enum: 'fuel','maintenance','food','other'), amount, note, spent_at`

### 4.35 `cash_boxes` (each rep's running cash on hand)
`id, user_id (FK), balance (default 0)`

### 4.36 `purchase_requests` (طلب شراء من المندوب)
When a rep finds a supplier offering a material at a price, they submit this.
`id, company_id (FK), user_id (FK), supplier_id (FK nullable), product_id (FK), quantity (decimal 12,3), offered_price (decimal 12,2), currency (string default 'EGP'), payment_terms (text nullable), status (enum: 'pending','reviewed_by_sales','approved','rejected'), reviewed_by (FK to users nullable), review_notes (text nullable), created_at`

### 4.37 `purchase_orders` (أوامر الشراء)
When purchasing decides to buy.
`id, company_id (FK), supplier_id (FK), order_number (string unique), status (enum: 'draft','sent','confirmed','partial','received','cancelled'), order_date, expected_delivery_date (date nullable), payment_terms, currency (string default 'EGP'), subtotal, shipping_cost, total, notes`

### 4.38 `purchase_order_items`
`id, purchase_order_id (FK), product_id (FK), quantity (decimal 12,3), unit_price (in PO currency), line_total, received_quantity (decimal 12,3 default 0)`

### 4.39 `supplier_quotations` (عروض أسعار الموردين)
Comparison of multiple supplier offers before purchase.
`id, purchase_request_id (FK nullable), company_id (FK), supplier_id (FK), product_id (FK), quantity (decimal 12,3), unit_price, currency, payment_terms, delivery_time_days (integer), valid_until (date), status (enum: 'pending','accepted','rejected'), reviewed_by (FK to users), created_at`
> Purchasing team compares offers side-by-side.

### 4.40 `alarms` (الإنذارات / التنبيهات)
`id, company_id (FK), type (enum: 'out_of_stock_request','customer_complaint','new_customer_pending','price_quotation_requested','purchase_request','goods_in_transit_delayed','batch_expiring'), reference_type (string), reference_id (bigint), title (string), description (text), severity (enum: 'info','warning','critical'), is_read (boolean), read_by (FK to users nullable), read_at (datetime nullable), created_at`

### 4.41 `out_of_stock_requests` (طلب عاجل لمادة غير متوفرة)
`id, company_id (FK), user_id (FK), customer_id (FK), product_id (FK), quantity_requested (decimal 12,3), notes (text), status (enum: 'open','fulfilled','cancelled'), created_at`

### 4.42 `complaints` (شكاوى العملاء / CRM)
`id, company_id (FK), customer_id (FK), user_id (FK), visit_id (FK nullable), complaint_type (enum: 'non_conforming_materials','delivery_issue','quality_issue','pricing_issue','other'), description (text), status (enum: 'open','in_progress','resolved','closed'), assigned_to (FK to users nullable), resolution (text nullable), resolved_at (datetime nullable), created_at`

### 4.43 `warehouse_import_logs` (سجل استيراد المخزون)
`id, warehouse_id (FK), imported_by (FK to users), file_name (string), rows_imported (integer), imported_at`

### 4.44 `van_transfers` (van-to-van)
`id, company_id (FK), from_user_id (FK), to_user_id (FK), status (enum: 'pending','accepted','rejected'), created_at`
plus `van_transfer_items`: `id, van_transfer_id (FK), product_id (FK), batch_id (FK nullable), quantity (decimal 12,3)`

### 4.45 `data_migrations` (سجل ترحيل البيانات من Odoo)
Log of data imported from Odoo/Excel during system setup.
`id, table_name (string), rows_migrated (integer), migrated_by (FK to users), source (enum: 'odoo_api','excel','manual'), migrated_at`

**ERD summary (relationships):**
- A `company` has many users, warehouses, routes, customers, suppliers, products.
- A `user` (rep) has one van `warehouse`, one `cash_box`, many `routes` (pivot), many `visits`, `invoices`, `payments`, daily visit assignments.
- A `customer` has many `visits`, `invoices`, `payments`, `returns`, `complaints`.
- A `supplier` has many `purchase_orders`, `supplier_quotations`, `goods_in_transit`.
- A `product` has many `batches` (optional), `stocks` per warehouse+batch.
- An `invoice` belongs to a company entity.
- A `goods_in_transit` shipment moves to warehouse stock when cleared + landed costs applied.
- Every stock change writes a `stock_movements` row.

---

## 5. User roles & permissions (spatie)

| Role | Can do | Cannot do |
|---|---|---|
| **admin** (الأستاذ عمرو) | Everything: manage companies, users, roles, products, prices, routes, suppliers; view all reports; reconcile cash; approve/cancel invoices; adjust stock; data migration from Odoo; add any user type | — |
| **sales_manager** (مدير المبيعات) | Assign daily visit tasks to reps; approve/reject new customers added by reps; set price ± ranges for reps; review & price quotation requests; respond to alarms (out-of-stock, complaints); view all reps' activity & live reports; approve pending invoices | Change base prices (set by Accounts); manage users; delete data; see product cost price |
| **accounts** (الحسابات / المالية) | Set base product prices; view financial reports (visit reports, quotation reports, proforma invoices, invoices, payments); view alarms (out-of-stock, complaints); manage landed costs; Egypt ETA e-invoicing; view cost prices | Change prices set by other accounts; manage users; edit routes; create sales |
| **purchasing** (إدارة المشتريات) | View purchase requests from reps; manage supplier quotations (compare offers); create purchase orders; track goods in transit; manage landed costs; compare supplier prices | Create sales; manage users; set sales prices |
| **warehouse_keeper** (أمين المستودع) | Import daily stock report (specific format); view warehouse stock levels; export stock reports; manage stock import/export; record goods receipt from transit; manage batches/COA upload; view expiry dates | Change prices; manage users; create sales |
| **executive** (فيور — Mohamed Taha) | Read-only view of all reports; view alarms and alerts; monitor rep activity; view inter-company transactions | Create/edit/delete any data; change prices; approve anything |
| **rep** (مندوب) | See only their assigned daily visits & customers; check in/out; open visits (GPS arrival confirm); submit visit reports; request price quotations; negotiate within given price range; create proforma invoices within their allowed range; sell from own van stock; collect cash; record returns; log expenses; add new customers (pending approval); submit purchase requests for supplier deals; flag out-of-stock urgent requests; view live stock & goods in transit availability | See other reps' data; change prices outside allowed range; edit routes; adjust stock directly; approve customers; **see product cost price** (hidden from sales) |

**User types key:**
- **Mr. Amr** → admin (full access, adds all user types)
- **Mohamed Taha** → executive (view-only + alarm visibility)
- **Sales manager** → assigns visits, approves customers, sets price ranges, responds to alarms
- **Accounts** → sets base prices, manages landed costs, e-invoicing compliance
- **Purchasing** → supplier comparisons, purchase orders, goods in transit
- **Warehouse keeper** → daily stock import, batch tracking, COA upload
- **Rep** → field sales activities

Seed product categories: Polymers (PP, HDPE, LDPE, LLDPE, PS, PVC, PET), Chemicals (glycols, acetates, acids, amines, caustic), Additives, Packaging materials.

Seed: one admin (`admin@jawla.test` / `password`), one sales manager, one accounts, one purchasing, one warehouse keeper, one executive, three reps.

---

## 6. Feature modules (what each part does)

### Admin (Filament panel at `/admin`):

1. **Companies** — CRUD for each legal entity. Egypt company (EGP base, VAT 14%, ETA registered). Saudi company (planned v2). Each with own tax number, commercial registration, bank accounts, logo.

2. **Users & roles** — CRUD for all user types (7 roles). On rep creation: auto-create van warehouse + cash box. Cost price hidden from sales roles automatically.

3. **Products & categories** — CRUD. Product fields: packaging type (bag/jumbo bag/barrel/tank), unit (ton/kg), batch tracking flag, expiry tracking flag. Base price set by Accounts. Cost price visible only to accounts + admin + purchasing.

4. **Batch/Lot tracking** — CRUD for batches per product. Upload COA (Certificate of Analysis) PDF per batch. Track expiry dates. View stock per batch. Alarms when batches approach expiry.

5. **Price management (Accounts)** — Accounts sets the base price and cost price for each product. Sales manager and reps only see selling price, never cost.

6. **Suppliers** — CRUD. Type: local vs international. Payment terms, contact info. 90% international.

7. **Warehouses & stock** — View stock per warehouse. **Adjust stock**, **Load van**, **Import stock** (warehouse keeper only — CSV format). Stock view per batch. Expiry tracking. Low stock alerts.

8. **Goods in transit** — Track international shipments from supplier to arrival. Status: in_transit → at_customs → cleared → received. Record shipping/customs/clearance/freight costs. When received → auto-create stock movement and update stock + cost price.

9. **Landed cost** — Add shipping, customs, clearance, insurance, freight costs to goods_in_transit. System distributes across products in shipment proportionally. Updates product cost price.

10. **Daily visit assignments** — Sales manager assigns customers to reps per day. Default 5 per rep.

11. **Customer approval queue** — Pending customers added by reps. Approve/reject. Duplicate check against existing customers.

12. **Routes** — CRUD, assign customers and reps.

13. **Customers** — CRUD, Leaflet location picker. Credit limit (though no credit sales currently — future). Filter by status.

14. **Price quotation requests** — List with status filtering. Manager opens, sets base price + ± range.

15. **Proforma invoices** — List/filter/view. Created by reps. Accounts and management can view.

16. **Invoices** — List/filter, confirm/cancel, PDF download (bilingual AR/EN). Egypt ETA QR code. Saudi ZATCA QR (deferred v2). Can be created from proforma.

17. **Supplier quotations comparison** — Side-by-side comparison of supplier offers. Purchasing team reviews pricing, payment terms, delivery time. Accept/reject.

18. **Purchase orders** — CRUD. Created from accepted supplier quotations or manually. Multi-currency (USD, CNY, EUR). Track partial receipts.

19. **Purchase requests** — List from reps. Visible to sales (veto power) + purchasing (buy price).

20. **Alarms dashboard** — Grouped by type, severity-colored:
    - Out-of-stock urgent requests (🔴)
    - Customer complaints (🔴)
    - New customer pending (🟡)
    - Price quotation requested (🟡)
    - Purchase request submitted (🟢)
    - Goods in transit delayed (🔴 — past estimated arrival)
    - Batch expiring soon (🟡)
    Visible to: sales manager (all), accounts (out-of-stock + complaints), executive (read-only).

21. **Complaints / CRM** — Full complaint lifecycle. Assignment to sales manager. Resolution tracking.

22. **Data migration (from Odoo)** — Import wizards for: customer lists, supplier lists, products, open invoices, stock balances, batch numbers. Start with opening balance from a specific date.

23. **Reports & dashboard** — Daily/monthly sales, visits, collections, returns, expenses, top products, per-rep productivity. Visit reports overview. Stock expiry report. Goods in transit report. Inter-company transaction report. Excel export.

### Rep PWA (at `/app`):

1. **Home** — greeting, "Start work" / "End work". Tiles: assigned visits count, pending quotations, pending new customers. Cash box balance. Stock availability search (checks main warehouse + goods in transit).

2. **Start work** → sees today's assigned visits → picks first → GPS → `work_session` created.

3. **Today's assigned visits** — Ordered cards (customer name, code, address, sequence).

4. **Add new customer** — name, phone, address, GPS, notes → pending approval.

5. **Visit flow with GPS geofencing:**
   - Auto-check within 1 km radius → "Confirmed Arrived ✅"
   - Outside → warning, manual "location mismatch"
   - Submit structured visit report

6. **Visit report** — summary, customer feedback, action taken, follow-up needed.

7. **Price quotation request** — product, quantity → manager sets price + ± range.

8. **Negotiate & confirm** — negotiate within range, mark confirmed.

9. **Create proforma invoice** — from confirmed quotation. System enforces ± range. Includes company bank info. Sequential number. Can convert to real invoice later.

10. **Sell** — van stock grid + main warehouse stock + goods in transit availability. Atomic sale.

11. **Check stock availability** — search any product → main warehouse qty, van qty, goods in transit qty, batch numbers + expiry dates.

12. **Out-of-stock urgent request** → creates alarm for manager + accounts + executive.

13. **Purchase request** → supplier name, product, qty, offered price, currency → submits to sales + purchasing.

14. **Customer complaint** → type, description → critical alarm for manager.

15. **Collect** — amount, method (cash/cheque/transfer), link invoice → updates cash box + customer balance.

16. **Return** — pick products, batch, qty → increases van stock, reduces customer balance.

17. **Expenses** — fuel/maintenance/food/other decreases cash box.

18. **End day** — session summary: sales, collections, returns, cash box, visits completed vs assigned.

---

## 7. Business rules (must be enforced in code, not just UI)

1. **No negative van stock.** Check `stocks.quantity >= requested`. Block with bilingual error. Enforce at service layer.

2. **Atomic sales.** `DB::transaction()`: invoice + items + stock decrement + movements + customer balance. Rollback on any failure.

3. **GPS geofencing for arrival.** Distance check against customer coordinates. Configurable radius (default 1 km). Auto-confirm or warn + flag mismatch.

4. **Price range enforcement (multi-level).**
   - Accounts sets `base_price`. Cost price hidden from sales roles.
   - Sales manager gets ± range from Accounts.
   - Manager gives rep sub-range (rep_plus <= manager_plus, rep_minus <= manager_minus).
   - Proforma unit price must be within `[base_price - rep_minus, base_price + rep_plus]`.
   - Manager's confirmed price within `[base_price - manager_minus, base_price + manager_plus]`.
   - System blocks everything outside these ranges.

5. **Customer approval workflow.**
   - Admin-added customers: active immediately.
   - Rep-added customers: `status='pending'`. Cannot transact.
   - Manager approves or rejects with reason.
   - Duplicate check (name/phone) against existing customers.

6. **Proforma invoice rules.**
   - Only within rep's allowed price range.
   - Includes company bank account info.
   - Sequential number, server-side, never editable.
   - Convertible to real invoice (stock deducted at conversion).

7. **Collections** → increase cash_box, decrease customer balance, update invoice paid_amount.

8. **Returns** → increase van stock (+movements), decrease customer balance.

9. **Expenses** → decrease cash_box.

10. **Route & visit integrity.** Rep can only visit assigned customers. Exception: new customer (pending) or "custom visit" (flagged).

11. **Invoice numbers** sequential per company.

12. **Stock only changed through movements** → `StockService` centralizes all stock operations.

13. **Warehouse import updates stock** via CSV (SKU, quantity, batch optional). Logs import.

14. **Goods in transit → warehouse.** When goods arrive: create stock movement from transit to main warehouse. Apply landed costs. Distribute proportionally. Update product cost price.

15. **Landed cost distribution.** Shipping/customs/clearance/freight costs are distributed across products in the shipment by quantity ratio. Applies to both international (GIT) and local (PO) purchases.

16. **Batch tracking.** If `products.track_batch=true`, all stock movements require batch_id. Sales invoices, purchase orders, transfers all track batch. COA PDF attached to batch.

17. **Expiry date alerts.** Auto-generate alarm when batch expiry_date is within 30 days.

18. **Inter-company transactions (v2).** Egypt entity selling to Saudi entity: create invoice in EGP, simple cross-entity entry with VAT handling per company.

19. **Multi-currency.** Base currency is EGP for both entities. Purchase orders in foreign currency (USD/CNY). Convert at transaction rate (store rate in PO).

20. **Egypt ETA e-invoicing.** Invoice PDF includes compliant QR code (encoded JSON: seller name, tax number, time, total, tax total). Format per ETA specifications.

21. **Cost price hidden from sales roles.** Product cost field never exposed to rep, sales manager roles in UI, API, exports.

22. **Alarm generation is automatic.** Triggers:
    - Out-of-stock request → critical alarm
    - Complaint → critical alarm
    - New customer pending → warning alarm
    - Price quotation requested → warning alarm
    - Purchase request submitted → info alarm
    - Goods in transit past ETA → critical alarm
    - Batch expiry within 30 days → warning alarm

23. **Sales manager is alarm responder.** Critical alarms assigned to manager by default.

24. **Money math:**
    - `line_total = quantity × unit_price`
    - `subtotal = Σ line_total`
    - `vat_amount = subtotal × (company.vat_percent / 100)` (only VAT-applicable products)
    - `total = subtotal + vat_amount`
    - `remaining_amount = total − paid_amount`

---

## 8. Build phases (execute in this exact order)

### Phase 0 — Project setup
**Goal:** Running empty Laravel app with the stack installed.
**Tasks:**
- `composer create-project laravel/laravel jawla` (Laravel 13.x).
- Configure PostgreSQL in `.env`. Create database `jawla`.
- Install Filament v4 (`composer require filament/filament:"^4.0"` → `php artisan filament:install --panels`), create `admin` panel.
- Install `spatie/laravel-permission`, `mpdf/mpdf`, `simplesoftwareio/simple-qrcode`, `spatie/simple-excel`.
- Set up Tailwind + Arabic font (Cairo/IBM Plex Sans Arabic). Default locale `ar`, add `en`. RTL.
- Create `/app` route group for rep PWA (Livewire).
**Definition of Done:** `php artisan serve` runs; `/admin` login in Arabic RTL; `/app` shows placeholder.

### Phase 1 — Database & models
**Goal:** All tables and Eloquent models.
**Tasks:** Create all migrations from §4 (45 core tables). Models with relationships, casts, fillables. `StockService`. `spatie` permission tables. Seed all 7 roles.
**Definition of Done:** `php artisan migrate:fresh` clean. Tinker can create company → user → product → stock with relationships.

### Phase 2 — Auth & roles
**Goal:** 7 roles with proper access control.
**Tasks:** Filament admin auth. Restrict `/admin` to non-rep roles. Restrict `/app` to reps. EN/AR switcher.
**Definition of Done:** Each role logs in and sees permitted navigation. Rep cannot open `/admin`.

### Phase 3 — Admin panel core
**Goal:** Master data management.
**Tasks:**
- Companies (one entity: Egypt EGP base — tax number, bank accounts, VAT%). Saudi entity deferred v2.
- Users (all 7 roles, auto-create van+cash for reps).
- Product Categories + Products (packaging types, batch/expiry flags, unit=ton).
- Suppliers (local vs international).
- Price management (Accounts sets base+cost, cost hidden from sales roles).
- Routes + Customers (Leaflet location).
- Warehouses + Stock (adjust, load van, import CSV). Stock view per batch.
- **Definition of Done:** Admin creates two companies, products, suppliers, customers, loads stock. Accounts sets prices. Cost hidden from sales manager view.

### Phase 4 — Rep PWA shell
**Goal:** Rep daily workflow start.
**Tasks:** Home screen, Start Work (assigned visits), Today's visits list, Add new customer button. Mobile-first. Alarm bell icon.
**Definition of Done:** Rep logs in, sees assigned visits, starts work.

### Phase 5 — Visit flow with GPS
**Goal:** Visit with arrival confirmation.
**Tasks:** GPS geofence check (1 km), auto-confirm or warn, visit report form, "Add operation" menu. End visit.
**Definition of Done:** Rep visits customer, GPS confirms, submits report.

### Phase 6 — Price quotation & pricing chain
**Goal:** Accounts → Manager → Rep pricing.
**Tasks:** Price quotation request, manager sets price + ± ranges, enforce boundaries. Cost price never shown to rep/manager.
**Definition of Done:** Rep requests price, manager sets range, rep negotiates within range.

### Phase 7 — Proforma invoice
**Goal:** Rep creates proforma invoices.
**Tasks:** Create from quotation, validate ± range, bank info from company, sequential number, convert to real invoice later.
**Definition of Done:** Rep creates proforma within price range. Blocked outside range.

### Phase 8 — Sales & invoicing
**Goal:** Field invoice creation.
**Tasks:** Van stock grid + warehouse + transit availability. Atomic transaction. Bilingual AR/EN PDF. Egypt ETA QR code. Batch tracking on invoice items.
**Definition of Done:** Rep sells with batch selection, invoice PDF with QR, stock deducted.

### Phase 9 — Collections, returns & cash box
**Goal:** Payments and returns.
**Tasks:** Collection (methods: cash/cheque/transfer/LC). Return with batch tracking. Cash box.
**Definition of Done:** Rep collects, returns. Cash box + balances reconcile.

### Phase 10 — Purchase requests & supplier management
**Goal:** Rep purchase requests + supplier comparison.
**Tasks:** Purchase request from rep (EGP, USD/CNY for international). Supplier quotations comparison (side-by-side). Purchase orders. Partial receipts.
**Definition of Done:** Rep submits purchase request. Purchasing compares 3 supplier offers, creates PO.

### Phase 11 — Goods in transit & landed cost
**Goal:** International shipments and cost distribution.
**Tasks:** Goods in transit tracking (status: in_transit → at_customs → cleared → received). Record shipping/customs/clearance/freight costs. Distribute landed cost across products proportionally. Auto-update stock + cost price on receipt. Transit warehouse type.
**Definition of Done:** Create shipment from supplier. Add landed costs. Receive into warehouse → stock updates + cost price updated correctly.

### Phase 12 — Batch tracking, COA & expiry
**Goal:** Full batch lifecycle.
**Tasks:** Batch creation per product. COA PDF upload. Stock per batch view. Expiry date tracking. Auto-alarm for batches expiring within 30 days. Batch selection in sales, purchases, transfers, returns.
**Definition of Done:** Product with batch tracking is received, sold, returned with batch selection. COA viewable. Expiry alarm triggers.

### Phase 13 — Alarms & notifications
**Goal:** Automatic alerts and response.
**Tasks:** All 7 alarm triggers implemented. Alarm dashboard grouped by type/severity. Sales manager response workflow (acknowledge → assign → resolve). Red indicators.
**Definition of Done:** Rep flags out-of-stock → red alarm for manager + accounts + executive. Manager acknowledges.

### Phase 14 — Egypt ETA e-invoicing compliance
**Goal:** Compliant invoices for Egypt entity.
**Tasks:** Bilingual AR/EN invoice PDF. Egypt ETA QR code generation (encoded JSON). Sequential invoice numbering per entity.
**Definition of Done:** Invoice PDF with valid ETA QR code scans correctly with any ETA-compliant scanner.

### Phase 15 — Inter-company transactions (deferred v2)
**Goal:** Egypt ↔ Saudi trading.
**Tasks:** Inter-company invoice workflow (one entity sells to another). EGP base with per-company VAT handling. Separate reporting.
**Definition of Done:** Egypt entity creates invoice to Saudi entity. Both sides see correct amounts with company-specific VAT.

### Phase 16 — Admin reports, CRM & dashboard
**Goal:** Full visibility.
**Tasks:** Dashboard widgets: sales, visits, collections, returns, expenses, top products, per-rep productivity. Alarm counts. Visit reports. Complaints status. Goods in transit report. Stock expiry report. Inter-company report. Excel export. Leaflet visit map.
**Definition of Done:** Admin sees all numbers, exports to Excel, drills into reports.

### Phase 17 — Data migration from Odoo
**Goal:** Migrate existing data from Odoo + Excel.
**Tasks:** Import wizards for: customer lists, supplier lists, products (incl. polymers + chemicals), open invoices, stock balances, batch numbers. Opening balance from specific date. Migration log table.
**Definition of Done:** All data imported from Odoo/Excel. Opening balances verified against source.

### Phase 18 — PWA polish
**Goal:** Installable app + shell.
**Tasks:** `manifest.json`, service worker, standalone display.
**Definition of Done:** "Add to Home Screen" works. Shell loads offline.

### Phase 19 — Seed data & final test pass
**Goal:** Demo-ready system.
**Tasks:** Seeder with: 2 companies (Egypt + Saudi deferred), 3 routes, ~15 Arabic customers, 25+ products covering polymers (PP, HDPE, LDPE, LLDPE, PS, PVC, PET) and chemicals (ethylene glycol, ethyl acetate, caustic soda, MEA/DEA, etc.), appropriate categories, packaging types (bags/barrels/tanks/jumbo bags), 3 suppliers (2 international like SABIC/Borouge, 1 local), 3 reps with loaded vans, batches with COA and expiry dates, sample visits, price quotations, proforma invoices, invoices, goods in transit shipment, landed costs, complaints, alarms. All 7 roles seeded. README with credentials and test flows.
**Definition of Done:** `php artisan migrate:fresh --seed` fully explorable demo.

---

## 9. Environment & setup commands (reference)

```bash
# 1. Create project
composer create-project laravel/laravel jawla
cd jawla

# 2. Database: set these in .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=jawla
# DB_USERNAME=postgres
# DB_PASSWORD=secret
# APP_LOCALE=ar
# APP_FALLBACK_LOCALE=en

# 3. Core packages
composer require filament/filament:"^4.0"
php artisan filament:install --panels
composer require spatie/laravel-permission
composer require mpdf/mpdf
composer require simplesoftwareio/simple-qrcode
composer require spatie/simple-excel

# 4. Frontend
npm install
npm run build

# 5. Migrate + seed
php artisan migrate --seed

# 6. Create first admin (or via seeder)
php artisan make:filament-user

# 7. Run
php artisan serve
npm run dev
```

**Queues:** `QUEUE_CONNECTION=database`, run `php artisan queue:work`.

---

## 10. Definition of "production ready" (v1 scope)

- All 19 phases complete with Definitions of Done met.
- All hard rules verified:
  - No negative stock (deliberate over-sell test blocked)
  - Atomic sales (forced rollback test passed)
  - Price outside rep's range blocked
  - GPS geofencing arrival confirmed
  - Customer approval workflow works
  - Cost price hidden from all sales roles
  - Batch tracking on all relevant transactions
  - Landed cost correctly distributed
  - Egypt ETA QR code valid
  - Goods in transit → warehouse flow works
  - Inter-company transaction flow works
  - Alarm generation on all triggers
- Bilingual AR/EN with RTL throughout.
- Data migrated from Odoo + Excel.
- Seeded demo runs from single command.
- README with credentials and test flows.
- Deployed on VPS with PostgreSQL + daily backups.

**Deferred to v2:** Saudi Arabia entity + inter-company transactions, full offline data sync, Bluetooth thermal printing, WhatsApp invoice sending, AI route optimization, shelf image recognition, Saudi ZATCA phase 2 integration, automated supplier purchase order via EDI, real-time tracking integration (ship container GPS).

---

## 11. Patterns stolen from ERPNext (15 years of battle-tested design)

ERPNext has been developed since 2011 with 600+ contributors and 60k+ commits. Below are the architecture patterns, data models, and workflows we **steal** to avoid reinventing the wheel. Use these as reference when building Jawla — they represent thousands of person-hours of design decisions.

### 11.1 Document State Machine (Draft → Submitted → Cancelled)

Every business document in ERPNext follows a strict state machine. Steal this for **all** transactional documents:

```
Draft → Submitted → Cancelled
                 ↓
            Amended (correction of submitted doc)
```

**Implementation in Jawla:**
- Add a `status` trait/trait to all transaction models
- States: `draft`, `submitted`, `cancelled`, `amended`
- Only "Submitted" documents affect stock, accounting, balances
- "Cancelled" documents create reverse entries (e.g., cancelled sale reverses stock movement)
- "Amended" creates a corrected copy linked to the original
- Use Laravel enums + a `DocumentStatus` cast

**Tables to apply this to:** invoices, proforma_invoices, purchase_orders, returns, delivery_notes, material_requests, goods_in_transit, payment_entries

### 11.2 Naming Series (Document Numbering)

ERPNext uses configurable naming series. Steal this pattern instead of hardcoding auto-increment:

**Pattern:** `PREFIX-SEPARATOR-SERIES`
Examples: `ACC-SINV-2026-00001`, `SAL-ORD-.YYYY.-.#####`

**Implementation in Jawla:**
```php
// naming_series table
id, name (string), prefix (string), series_format (string), 
current_number (bigint), pad_length (int default 5), 
company_id (FK nullable), is_active

// NamingSeriesService::generate('sales_invoice', $company)
// Returns: 'INV-2026-00042'
```

**Default series for Jawla:**
| Document | Series |
|---|---|
| Sales Invoice | `INV-{company_abbr}-{YYYY}-{#####}` |
| Proforma Invoice | `PF-{company_abbr}-{YYYY}-{#####}` |
| Purchase Order | `PO-{company_abbr}-{YYYY}-{#####}` |
| Return | `RET-{company_abbr}-{YYYY}-{#####}` |
| Delivery Note | `DN-{company_abbr}-{YYYY}-{#####}` |
| Material Request | `MR-{company_abbr}-{YYYY}-{#####}` |
| Goods in Transit | `GIT-{company_abbr}-{YYYY}-{#####}` |
| Quotation | `QTN-{company_abbr}-{YYYY}-{#####}` |
| Payment Entry | `PAY-{company_abbr}-{YYYY}-{#####}` |

### 11.3 Child Tables Pattern (One Document → Many Lines)

Every ERPNext transaction has a header + child table for line items. Steal this as a Laravel pattern:

**Pattern:**
```php
// Every transaction has:
class Invoice extends Model {
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
    public function taxes(): HasMany { return $this->hasMany(InvoiceTax::class); }
    public function payments(): HasMany { return $this->hasMany(InvoicePayment::class); }
}

// Child items always reference the parent
class InvoiceItem extends Model {
    public function invoice(): BelongsTo { ... }
    public function product(): BelongsTo { ... }
    public function batch(): BelongsTo { ... }
}
```

**All Jawla documents should use this pattern:**
- Invoice → InvoiceItems, InvoiceTaxes, InvoicePayments
- Proforma → ProformaItems
- Purchase Order → POItems
- Delivery Note → DNItems
- Quotation → QuotationItems
- Goods in Transit → GITItems

**CRITICAL:** All child operations must happen inside the same DB transaction as the parent document submission.

### 11.4 Fulfillment Tracking Percentages

ERPNext tracks fulfillment on every order document. Steal these computed fields on Sales Orders and Purchase Orders:

```php
// On Sales Order model:
per_delivered    // = (sum of delivered_qty) / (sum of qty) * 100
per_billed       // = billed_amt / total * 100
per_picked       // = sum of picked_qty / sum of qty * 100
delivery_status  // Computed: Not Delivered | Partially Delivered | Delivered
billing_status   // Computed: Not Billed | Partially Billed | Fully Billed

// On Purchase Order model:
per_received     // = received_qty / ordered_qty * 100
per_billed       // = billed_amt / total * 100
```

**Implementation:** Use Laravel computed attributes / accessors that aggregate from child items. This enables real-time status widgets without scheduled jobs.

### 11.5 The "Against" Reference Pattern (Chain Traceability)

ERPNext links every transaction line back to its source. This creates a full audit chain:

```
Quotation → Sales Order → Delivery Note/Pick List → Sales Invoice
                ↓
         Material Request → Purchase Order → Purchase Receipt → Purchase Invoice
```

**Implementation:** Every child item table has nullable FK fields pointing to the source document line:

| Table | Source Link |
|---|---|
| `sales_order_items` | `quotation_item_id` (nullable) |
| `delivery_note_items` | `sales_order_item_id`, `pick_list_item_id` (nullable) |
| `invoice_items` | `delivery_note_item_id`, `sales_order_item_id` (nullable) |
| `purchase_order_items` | `material_request_item_id` (nullable) |
| `purchase_receipt_items` | `purchase_order_item_id` (nullable) |
| `pick_list_items` | `sales_order_item_id` (nullable) |

**Why steal this:** One query can trace any product's full journey: "This invoice line → came from this delivery note → which picked from this batch → which was received from this PO → which was requested in this MR."

### 11.6 Dynamic Link Pattern (Polymorphic References)

ERPNext uses `reference_type` (varchar: DocType name) + `reference_name` (dynamic link). In Laravel, this is a polymorphic relationship:

```php
// StockMovement belongs to any document via morph
class StockMovement extends Model {
    public function reference(): MorphTo { return $this->morphTo(); }
}

// Usage: $movement->reference // Returns Invoice, Return, DeliveryNote, etc.
```

**Where to use this in Jawla:**
- `stock_movements.reference` → morphs to Invoice, Return, PurchaseReceipt, GoodsInTransit, Adjustment
- `alarms.reference` → morphs to OutOfStockRequest, Complaint, PurchaseRequest
- `payments.reference` → morphs to Invoice (or null for on-account)
- `attachments.reference` → morphs to any document (photos, PDFs, COA)

### 11.7 Price List Architecture

ERPNext's price list system is simple but powerful. Steal it:

```php
price_lists: id, company_id, name, type (selling|buying), is_default, is_active

product_prices: id, product_id, price_list_id, price (decimal 12,2), 
                uom, min_quantity (for tiered pricing), 
                customer_id (nullable — customer-specific override),
                valid_from (date), valid_upto (date), is_active
```

**Behavior:**
- Each transaction picks a Price List (e.g., "Wholesale", "Retail", "Export")
- System auto-fills item rates from the matching `product_prices` entry
- Customer-specific price: if `customer_id` is set, this price only applies to that customer
- Tier pricing: `min_quantity` enables volume discounts (qty >= 10 → different price)
- Date validity: `valid_from`/`valid_upto` for seasonal pricing
- For Jawla: start with 3 default price lists (Wholesale, Retail, Distributor), users can add more

### 11.8 Customer Groups & Territories (Hierarchical Trees)

ERPNext uses Nested Set trees for customer grouping and territory assignment. Steal this:

```php
customer_groups: id, name_ar, name_en, parent_id (nullable FK self), 
                 lft, rgt (nested set), company_id, is_active

territories: id, name_ar, name_en, parent_id (nullable FK self), 
             lft, rgt (nested set), company_id, is_active
```

**Default tree for Jawla (customer_groups):**
```
All Customers
├── Commercial (تجاري)
│   ├── Factory (مصنع)
│   ├── Pharmacy (صيدلية)
│   └── Hospital (مستشفى)
└── Retail (تجزئة)
    ├── Wholesaler (تاجر جملة)
    ├── Distributor (موزع)
    └── Direct (مباشر)
```

**Usage:** Filter reports by territory, target promotions by customer group, assign reps to territories.

### 11.9 Payment Terms & Installment Schedules

ERPNext allows defining payment terms (e.g., 50% on order, 50% on delivery) that auto-generate schedules on invoices:

```php
payment_terms_templates: id, name, company_id, is_active

payment_terms_template_lines: id, template_id, description, 
    percentage (decimal 5,2), 
    due_date_based_on (enum: 'day_month', 'day_of_month', 'last_day_of_month'),
    credit_days, credit_months

// Generated on the invoice:
payment_schedules: id, invoice_id, payment_term (string), 
    due_date, percentage, amount, paid_amount, status (pending|paid|overdue)
```

**For Jawla:** Simple version first — just `due_date` on invoices. Add payment terms template when the client asks for installment plans.

### 11.10 Tax Templates (Sales Taxes and Charges)

ERPNext's tax system uses templates linked to transactions:

```php
tax_templates: id, name, type (selling|buying), company_id, is_default, is_active

tax_template_lines: id, template_id, account_head (string), 
    charge_type (enum: 'on_net_total','on_previous_row_amount','actual_amount'),
    rate (decimal 5,2), description, 
    included_in_rate (boolean — tax included in item price),
    row_id (int — for on_previous_row types)
```

**How it works:**
- Each transaction picks a Tax Template
- System auto-calculates taxes based on `charge_type`:
  - `on_net_total`: rate% × subtotal
  - `on_previous_row_amount`: rate% × previous tax amount (compounding)
  - `actual_amount`: fixed amount
- `included_in_rate = true` → tax is embedded in item price (inclusive pricing)
- `included_in_rate = false` → tax is added on top (exclusive pricing)

**For Jawla:** Two templates minimum — "Standard VAT 14%" for Egypt, "Standard VAT 15%" for Saudi. Each has one line: charge_type=on_net_total, rate=14 or 15, description="Value Added Tax".

### 11.11 Barcode Scanning Everywhere

ERPNext has `scan_barcode` fields at the top of every transaction form. For field sales, this is critical:

```php
product_barcodes: id, product_id, barcode (string unique), 
    barcode_type (enum: 'EAN','UPC','GS1','QR','custom'), is_default
```

**Behavior:**
- User scans a barcode → system looks up `product_barcodes.barcode`
- Resolves to product → auto-fills item in current transaction
- Multiple barcodes per product (different packaging sizes)
- `is_default` flag for the primary barcode

**For Jawla:** Add a `scan_barcode` Livewire component that emits events. On the rep PWA, use the camera to scan. On web admin, allow manual barcode input.

### 11.12 Scan Mode for Warehouse Operations

ERPNext has `scan_mode` and `prompt_qty` on Pick Lists. This is a dedicated UI mode for warehouse scanning workflows:

```php
// On PickList:
scan_mode (boolean) — when on, the UI shows a single barcode input
prompt_qty (boolean) — after scanning, prompt to enter quantity
```

**Behavior:**
1. User enables `scan_mode`
2. UI collapses to a single large barcode input (mobile-friendly)
3. User scans item → item added to list
4. If `prompt_qty`: after scan, show qty input
5. If not: auto-add qty=1

**For Jawla:** Add this to the rep PWA for van stock taking and delivery confirmation. One big scan field, mobile-optimized.

### 11.13 Communication Log (Timeline per Document)

ERPNext has a timeline on every document showing all communications, status changes, and notes:

```php
// Polymorphic communications table
communications: id, reference_type (string), reference_id (bigint),
    type (enum: 'note','email','call','sms','whatsapp','system'),
    subject, content (text), sender_id (FK users), 
    is_internal (boolean — only visible to internal users),
    created_at
```

**For Jawla:** Add a Livewire `timeline` component on every document detail page (invoice, customer, visit, complaint). Show status changes, notes, calls. The rep app should show the timeline for each customer during a visit.

### 11.14 Lost Reason Tracking

ERPNext tracks why quotations and opportunities are lost:

```php
lost_reasons: id, reason (string), is_active
// Seed: "Budget too high", "Competitor won", "Not a decision maker", 
//        "No response", "Timing not right", "Product not suitable"

// Pivot on quotations:
quotation_lost_reasons: quotation_id, lost_reason_id
```

**For Jawla:** Track why a price quotation didn't convert. This data drives sales coaching.

### 11.15 Product Bundles (Kits / Combo Deals)

ERPNext expands bundle items into their components on transactions:

```php
product_bundles: id, product_id (FK — the kit parent), 
    description, is_active, company_id

bundle_items: id, bundle_id, component_product_id (FK), 
    quantity (decimal 12,3)
```

**Behavior:**
- Parent product has `is_bundle = true`, not a stock item
- On transaction: parent is added, system auto-expands into `packed_items`
- Stock deducted from component items
- Pricing set on the bundle parent separately from component costs

**For Jawla:** Useful for promotional kits, sample packs, combo discounts.

### 11.16 Product Variants

ERPNext supports variants with different attributes (size, color, etc.):

```php
products: // add
    has_variants (boolean),
    variant_of (FK self nullable),
    variant_attributes (JSON — {'Color': 'Red', 'Size': 'XL'})

product_variant_attributes: id, product_id, attribute (string), value (string)
```

**For Jawla:** Not a priority for plastics trading. Skip for v1, but the `has_variants` and `variant_of` booleans are cheap to add to the schema for future use.

### 11.17 Material Request → Purchase Order Flow

ERPNext's full procurement flow:

```
Material Request (rep requests stock)
    → Supplier Quotations (compare offers)
    → Purchase Order (selected supplier)
    → Purchase Receipt (goods arrive)
    → Purchase Invoice (supplier bill)
```

**Tables to steal:**
```php
material_requests: id, company_id, requested_by (FK users), 
    required_by_date, status (draft|submitted|partially_ordered|ordered|received|cancelled)

material_request_items: id, material_request_id, product_id, 
    quantity_requested, quantity_ordered (auto-tracked), quantity_received,
    warehouse_id, supplier_id (nullable — suggested supplier)

supplier_quotations: id, company_id, supplier_id, product_id, 
    quantity, unit_price, currency, payment_terms, delivery_time_days,
    valid_until, status (pending|accepted|rejected), reviewed_by (FK)
```

**Behavior:** Multiple supplier quotations can be compared side-by-side for the same product. Purchasing team reviews pricing, payment terms, delivery time. Accepted quotation → creates Purchase Order.

### 11.18 Delivery Note + Partial Fulfillment

ERPNext's delivery note supports partial deliveries against a sales order:

```php
sales_order_items:
    quantity_ordered (decimal 12,3),
    quantity_delivered (decimal 12,3 — auto-summed from delivery notes),
    quantity_returned (decimal 12,3)

delivery_notes:
    id, sales_order_id (FK nullable), customer_id, 
    status (draft|submitted|cancelled),
    is_return (boolean), return_against_delivery_note_id (FK nullable),
    transporter, driver_name, vehicle_number, lr_number

delivery_note_items:
    id, delivery_note_id, sales_order_item_id (FK nullable),
    product_id, batch_id (nullable), quantity,
    rate, amount
```

**For Jawla:** The rep's van stock IS the delivery. But for the main warehouse → customer direct shipping, this pattern handles partial orders correctly.

### 11.19 Packing Slip (Weight/Package Tracking)

```php
packing_slips: id, delivery_note_id, 
    from_case_no (int), to_case_no (int),
    net_weight (decimal 12,3), gross_weight (decimal 12,3), 
    weight_uom (string), status (draft|submitted)

packing_slip_items: id, packing_slip_id, product_id, 
    batch_id (nullable), quantity, net_weight
```

**For Jawla:** Useful when shipping physical goods with weight tracking. The `from_case_no`/`to_case_no` range pattern is clever — one slip represents multiple cases.

### 11.20 Sales Team & Commission Tracking

```php
// On each sales transaction (invoice, order):
sales_team: id, document_type (string), document_id (bigint),
    sales_person_id (FK users), contribution_percentage (decimal 5,2),
    allocated_amount (decimal 12,2), commission_rate (decimal 5,2),
    commission_amount (decimal 12,2)
```

**For Jawla:** Track which rep/team member contributed to each sale. The contribution % splits commission. For a field sales app, this enables rep performance reporting.

### 11.21 Quality Inspection

```php
quality_inspections: id, product_id, batch_id (nullable), 
    document_type (string), document_id (bigint),
    inspection_type (incoming|outgoing|in_process),
    inspector_id (FK users), inspection_date, 
    sample_size (int), status (pending|passed|failed),
    notes (text)

inspection_readings: id, quality_inspection_id,
    parameter (string), value (string), min_value, max_value,
    status (pass|fail)
```

**For Jawla:** The client mentioned COA (Certificate of Analysis) per batch. This inspection table tracks incoming quality checks from suppliers. Attach COA PDF to batch record.

### 11.22 Tax Withholding (TDS/TCS)

ERPNext handles tax deduction at source. For Egypt/Saudi compliance:
```php
tax_withholding_categories: id, name, type (tds|tcs),
    basis (gross|net), threshold_amount, rate
    
// Applied on invoices where supplier/customer exceeds threshold
// Auto-calculates and deducts at payment time
```

**For Jawla:** Egyptian and Saudi tax authorities may require withholding on certain transactions. Add the table structure in v1, flag it as "future use."

### 11.23 Egypt ETA E-Invoicing QR Code Format

ERPNext generates ETA-compliant QR codes on invoices. The QR code encodes this JSON:
```json
{
  "seller_name": "Company Name AR",
  "tax_number": "{{ company.tax_number }}",
  "timestamp": "2026-07-12T14:30:00Z",
  "total": 1000.00,
  "tax_total": 140.00
}
```

**Implementation (already in Phase 14):**
- JSON encoded with Base64
- Rendered as QR on invoice PDF
- ETA validation: https://invoicing.eta.gov.eg/validator

### 11.24 Saudi ZATCA E-Invoicing (deferred v2)

For Saudi entity, Phase 1 requires:
- QR code on invoices (same format as Egypt but EGP currency)
- Arabic + English invoice
- Sequential invoice numbers

**Phase 2 (deferred to v2):** Requires real-time API integration with ZATCA. Use the `zatca_qr` field on invoices now to store the code, even if not yet submitted to ZATCA.

### 11.25 Lead Management

From ERPNext's CRM module, steal the lead pipeline:

```php
leads: id, company_id, 
    salutation, first_name, last_name, 
    company_name (string — prospect organization),
    email, mobile, phone, whatsapp,
    lead_owner_id (FK users), status (open|replied|converted|qualified|lost),
    source (enum: 'referral','walk_in','call_in','campaign','other'),
    territory_id (FK nullable), customer_group_id (FK nullable),
    converted_customer_id (FK nullable),
    qualification_status (unqualified|in_process|qualified),
    notes (text), created_at
```

**For Jawla:** Reps can capture leads during field visits. A lead converts to a customer when the first sale happens. Track lead source to measure which activities generate business.

### 11.26 Opportunity Pipeline

```php
opportunities: id, company_id, lead_id (FK nullable),
    customer_id (FK nullable), 
    opportunity_owner_id (FK users),
    sales_stage_id (FK — prospecting|qualification|proposal|negotiation|closed_won|lost),
    probability (int 0-100), expected_closing_date,
    amount (decimal 12,2),
    competitor_ids (JSON — which competitors involved),
    lost_reason_ids (JSON — if lost), notes
```

**For Jawla:** When a rep submits a price quotation, it creates an opportunity. Track the pipeline from quotation → negotiation → won/lost. Sales manager uses this for forecasting.

### 11.27 Campaign / UTM Tracking

```php
campaigns: id, name, description, 
    start_date, end_date, budget, is_active

// On leads and opportunities:
utm_source, utm_medium, utm_campaign, utm_content
```

**For Jawla:** Not urgent, but the `utm_*` fields on customers/leads cost nothing and help measure which activities generate business.

### 11.28 Document Attachment Pattern

ERPNext attaches files to any document. Steal:

```php
// Polymorphic attachments
attachments: id, reference_type, reference_id,
    file_name, file_path, file_size, mime_type,
    attached_by (FK users), attached_at,
    is_private (boolean)
```

**For Jawla:** Use Laravel's media library (spatie/laravel-medialibrary) instead of reinventing. Attachments needed: COA per batch, invoice PDFs, customer photos, visit photos, complaint evidence.

### 11.29 Reorder Levels (Inventory Alerts)

```php
product_reorder_levels: id, product_id, warehouse_id,
    reorder_level (decimal 12,3 — minimum before alert),
    reorder_quantity (decimal 12,3 — suggested order qty),
    material_request_type (purchase|transfer)
```

**Behavior:** System checks stock against reorder levels. If below threshold, auto-generates an alarm or material request suggestion.

### 11.30 Document Amendment/Correction

ERPNext allows correcting submitted documents:

```php
// On every submittable document:
amended_from (FK self nullable — link to original)

// When amending:
// 1. Original is cancelled (reverse entries created)
// 2. New draft copy created with "Amended" status
// 3. All values copied from original, user modifies what's wrong
// 4. New copy submitted
```

**For Jawla:** Implement this for invoices. If an invoice is submitted with wrong items/prices, the flow is: Cancel → Create amended copy → Submit correction. Never edit a submitted document directly.

### 11.31 Controller Pattern (Service Layer)

ERPNext uses controllers (not models) for business logic:

```
// Instead of $invoice->save() in a controller...
// Use a dedicated service class:

InvoiceService::submit($data);   // Validates → creates → submits → posts GL entries
InvoiceService::cancel($invoice); // Creates reverse entries → cancels
InvoiceService::amend($invoice);  // Cancels original → creates draft copy
```

**For Jawla:**
```php
// Service classes for every business document:
StockService          — All stock movements (already planned)
InvoiceService        — Create, submit, cancel, amend invoices
PaymentService        — Record payments, update balances  
PricingService        — Calculate prices with ± ranges
VisitService          — Open, confirm, close visits
AlarmService          — Generate and dispatch alarms
DocumentNumberService — Generate sequential numbers
LandedCostService     — Distribute landed costs across items
QuotationService      — Create, price, confirm quotations
ProformaService       — Create, validate, convert proformas
```

### 11.32 Single Doctrine of "Posting Date"

ERPNext separates `creation` (when the record was created) from `posting_date` (when the transaction is considered to have occurred). This is critical for period-end reporting:

```php
// On every transaction table:
posting_date (date — the business date, may be backdated)
posting_time (time nullable)
// vs. Laravel's built-in:
created_at (datetime — system timestamp, never changed)
```

**For Jawla:** Add `posting_date` to invoices, payments, returns, stock movements, goods_in_transit. Reports filter by `posting_date`, not `created_at`.

### 11.33 Company Abbreviation

ERPNext uses `abbr` on Company for naming series and display:

```php
companies: // add
    abbr (string — e.g., 'GPE' for Global Plastic Company Egypt, 'GPS' for Global Plastic Company Saudi)
```

Used in: invoice numbers, document series, account names.

### 11.34 Address & Contact Separation

ERPNext separates Address (location data) from Contact (person data) from Customer (business entity). Each can be linked:

```php
customer_addresses: id, customer_id, 
    address_type (billing|shipping|both),
    address_line_1, address_line_2, city, state, 
    country, postal_code, latitude, longitude,
    is_primary (boolean)

customer_contacts: id, customer_id,
    salutation, first_name, last_name,
    email, mobile, phone, whatsapp,
    designation (job title), department,
    is_primary (boolean)
```

**For Jawla:** A customer may have multiple locations (billing vs shipping) and multiple contacts (procurement manager, finance manager). Separating these now saves pain later.

### 11.35 Mode of Payment

ERPNext defines payment methods as masters:

```php
modes_of_payment: id, name (enum: 'cash','cheque','bank_transfer','lc', 'credit_card'),
    type (bank|cash|general), is_active

// On payments table:
mode_of_payment_id (FK)  // instead of a hardcoded enum
```

**For Jawla:** The client mentioned all payment methods (LC, TT, advance, credit). Making this a master table allows adding methods without migrations.

### 11.36 Bank Account Master

```php
company_bank_accounts: id, company_id,
    bank_name, bank_address, 
    account_name, account_number, iban, swift,
    currency (string default 'EGP'), is_default
    
customer_bank_accounts: id, customer_id, 
    bank_name, account_name, account_number, iban
```

**For Jawla:** Proforma invoices include company bank details (from `company_bank_accounts`). For future: customer bank accounts for supplier payments.

### 11.37 Currency Exchange Rate Storage

```php
currency_exchange_rates: id, from_currency, to_currency,
    rate (decimal 12,6), date, 
    source (manual|api_auto)
```

**For Jawla:** When recording a purchase in USD, store the exchange rate used so reporting in EGP is accurate. Simple version: store `exchange_rate` directly on the PO rather than a separate table.

### 11.38 User Permissions Based on Document Owner

ERPNext restricts visibility based on document owner. For Jawla, implement:

```php
// On transactions:
owner_id (FK users — who created/owns this document)
// Rep can only see their own documents
// Sales manager can see their team's documents
// Admin can see everything
```

This is already implicit in the codebase via `user_id` on most tables, but make it explicit with a permission middleware/filter.

### 11.39 Accounting Dimensions Pattern

ERPNext has Cost Center and Project as built-in accounting dimensions, plus custom ones. For Jawla:

```php
// On every financial transaction:
cost_center_id (FK nullable)
project_id (FK nullable)
// Future: custom dimensions via JSON or polymorphic table
```

For the logistics/trading focus of Jawla, cost centers track profitability by route, territory, or customer group.

### 11.40 Currency Handling Pattern

Steal ERPNext's dual-currency approach:

```php
// On multi-currency documents:
currency (string — the transaction currency)
exchange_rate (decimal 12,6 — rate to company base currency)
base_total (decimal 12,2 — total converted to company currency)

// On line items:
// All item lines store both:
unit_price (in transaction currency)
base_unit_price (in company currency, auto-calculated)
```

**For Jawla:**
- Both entities: base currency = EGP, rate = 1
- Purchase orders in USD: store exchange_rate on PO, base amounts in EGP
- Reports always show: transaction amount + EGP base amount

### 11.41 Report Pattern: Summary + Drill-Down

ERPNext reports follow a consistent pattern:
1. **Summary cards** at top (total sales, count, averages)
2. **Chart** (line/bar/pie by time period or dimension)
3. **Table** with sortable columns and pagination
4. **Drill-down** click a row → see detail

**For Jawla:** Every report follows this pattern. Use Filament's built-in widgets + tables. Chart: use Filament's Chart widget (ApexCharts under the hood).

### 11.42 Global Search Pattern

ERPNext has a universal search bar (Ctrl+G) that searches across all document types. For Jawla:

```php
// GlobalSearch service
// Searches: customers, products, invoices, users, suppliers
// Returns grouped results by type
// Filament has built-in global search — configure it to search:
// - Customer name, code, phone
// - Product name, SKU, barcode
// - Invoice number
// - User name, employee code
```

### 11.43 Print Format Templating

ERPNext uses Jinja templates for print formats. For Jawla:
- Use Laravel's Blade for PDF templates
- One template per document type (invoice, proforma, delivery note, quotation)
- Templates are bilingual AR/EN
- Company logo + address + tax number in header
- Bank details in footer (for proforma/invoice)

### 11.44 Soft Delete Pattern

ERPNext doesn't soft-delete transactions. Instead, it **cancels** them (status = Cancelled). This preserves the audit trail. Only reference data (customers, products) is soft-deleted.

**For Jawla:** Follow the same rule:
- **Transactions** (invoices, payments, returns, POs, quotations): never delete, only cancel
- **Master data** (customers, products, suppliers): soft delete with `deleted_at`
- Add a `cancelled_at` and `cancelled_by` to all transaction tables

### 11.45 Stock Valuation Method

ERPNext supports multiple valuation methods per item. For Jawla's plastic trading:

```
valuation_method (enum: 'fifo','moving_average','standard')
```

- **FIFO** (default for most items): Cost = oldest batch's cost
- **Moving Average**: Cost = total value / total quantity
- **Standard**: Fixed cost, updated periodically

**For Jawla:** Start with Moving Average (simplest for trading). Store the current average cost on each product and update on every purchase receipt + landed cost allocation.

### 11.46 Summary of All Tables to Steal

Below is the complete list of tables our Jawla app needs, combining the original schema with everything stolen from ERPNext. **New tables stolen from ERPNext are marked with [STEAL]:**

```
Master Data:
  companies              [UPDATED: added abbr, bank fields]
  users                  [same]
  product_categories     [same]
  products               [UPDATED: added has_variants, variant_of, track_batch, track_expiry, has_serial_no, valuation_method, packaging_type, is_bundle, max_discount]
  product_barcodes       [STEAL]
  product_variants       [STEAL — deferred]
  product_bundles        [STEAL]
  bundle_items           [STEAL]
  product_prices         [STEAL — replaces simple price field]
  price_lists            [STEAL]
  product_reorder_levels [STEAL]
  product_suppliers      [STEAL]
  batches                [already in schema]
  serial_numbers         [STEAL]
  
CRM & Sales:
  leads                  [STEAL]
  opportunities          [STEAL]
  sales_stages           [STEAL]
  opportunity_lost_reasons [STEAL]
  campaigns              [STEAL — deferred]
  quotations             [STEAL]
  quotation_items        [STEAL]
  quotation_lost_reasons [STEAL]
  
Customers & Territories:
  customers              [UPDATED: added customer_group_id, territory_id, price_list_id, account_manager_id]
  customer_groups        [STEAL]
  territories            [STEAL]
  customer_addresses     [STEAL]
  customer_contacts      [STEAL]
  customer_credit_limits [already in schema concept]
  customer_sales_people  [STEAL]
  suppliers              [already in schema]
  supplier_quotations    [STEAL]
  
Transactions:
  sales_orders           [STEAL — deferred v2]
  sales_order_items      [STEAL — deferred v2]
  delivery_notes         [STEAL — deferred v2]
  delivery_note_items    [STEAL — deferred v2]
  packing_slips          [STEAL — deferred v2]
  packing_slip_items     [STEAL — deferred v2]
  pick_lists             [STEAL — deferred v2]
  pick_list_items        [STEAL — deferred v2]
  material_requests      [STEAL — deferred v2]
  material_request_items [STEAL — deferred v2]
  purchase_orders        [already in schema]
  purchase_order_items   [already in schema]
  purchase_receipts      [STEAL — add when local PO receipts needed]
  
Field Sales:
  routes                 [same]
  route_user             [same]
  work_sessions          [same]
  daily_visit_assignments [same]
  visits                 [same]
  visit_reports          [same]
  cash_boxes             [same]
  expenses               [same]
  
Financial:
  invoices               [UPDATED: added posting_date, cancelled_at/by, amended_from]
  invoice_items          [same]
  invoice_taxes          [already in schema]
  proforma_invoices      [UPDATED: added posting_date, bank_account_id]
  proforma_invoice_items [same]
  tax_templates          [already in schema]
  tax_template_lines     [already in schema]
  payments               [UPDATED: mode_of_payment_id FK]
  returns                [UPDATED: added posting_date, cancelled_at/by]
  return_items           [same]
  payment_schedules      [STEAL — add when installment plans needed]
  modes_of_payment       [already in schema]
  company_bank_accounts  [already in schema]
  currency_exchange_rates [store on document — single rate per PO]
  
Supply Chain:
  goods_in_transit       [already in schema]
  goods_in_transit_items [already in schema]
  landed_costs           [already in schema]
  warehouses             [same]
  stocks                 [UPDATED: batch_id, decimal qty]
  stock_movements        [UPDATED: added valuation_rate]
  
Alerts & CRM:
  alarms                 [same]
  out_of_stock_requests  [same]
  complaints             [same]
  communications         [STEAL]
  attachments            [STEAL — or use spatie/media-library]
  
Quality:
  quality_inspections    [STEAL]
  inspection_readings    [STEAL]
  
Infrastructure:
  naming_series          [STEAL]
  data_migrations        [same]
  warehouse_import_logs  [same]
  van_transfers          [same]
  sales_team             [STEAL]
```

**Total tables: ~75** (including STEALs). Of these, ~50 are core for v1 (including tax, bank, modes_of_payment), ~25 are "add when needed" or deferred v2.

---

## 12. Permissions Catalog (all roles, all actions)

Every permission below maps to a Spatie `Permission` record. The pattern follows `{resource}.{action}`. Permissions are grouped by role and urgency.

### 12.1 URGENT — must ship in v1

**Admin (عمرو):** `full_access` — gates everything. Single permission, no granularity needed.

**Sales Manager:**
| Permission | Resource | What it unlocks |
|---|---|---|
| `visit_assignments.manage` | Daily visit assignments | Create/edit/assign daily visits for any rep |
| `customers.approve` | Customer approval queue | Approve/reject pending customers added by reps |
| `customers.view_all` | Customer list | See all customers, not just own |
| `pricing.set_range` | Price quotations | Set base price + ± range for rep quoting |
| `alarms.view_all` | Alarm dashboard | See all alarms grouped by type/severity |
| `alarms.respond` | Alarm response | Acknowledge, assign, resolve alarms |
| `complaints.manage` | Complaints/CRM | Full complaint lifecycle (assign, resolve) |
| `reports.sales` | Sales reports | Daily/monthly sales, per-rep productivity |
| `reports.visits` | Visit reports | View all visit reports |
| `reports.view` | General reports | Dashboard access, widgets |
| `invoices.view_all` | Invoices | See all invoices (not just own) |
| `invoices.approve` | Invoice approval | Approve/confirm pending invoices |
| `purchase_requests.veto` | Purchase requests | Veto power on rep purchase requests |
| `van_transfers.approve` | Van transfers | Approve cross-rep stock transfers |

**Accounts/مالية:**
| Permission | Resource | What it unlocks |
|---|---|---|
| `products.manage_prices` | Products | Set base selling price |
| `products.view_cost` | Product cost | See cost price (all other roles besides admin cannot) |
| `products.manage_cost` | Product cost price | Edit cost price |
| `goods_in_transit.manage_landed_cost` | Landed costs | Add shipping/customs/clearance costs |
| `invoices.view_all` | Invoices | View all invoices |
| `invoices.cancel` | Invoice cancellation | Cancel submitted invoices |
| `payments.view_all` | Payments | See all collections |
| `reports.financial` | Financial reports | Revenue, VAT, aging reports |
| `customers.view_all` | Customers | View all customers for financial context |
| `tax_templates.manage` | Tax templates | Manage VAT/tax configurations |

**Purchasing/مشتريات:**
| Permission | Resource | What it unlocks |
|---|---|---|
| `purchase_requests.view_all` | Purchase requests | See all rep purchase requests |
| `supplier_quotations.manage` | Supplier quotations | Create, compare, accept/reject offers |
| `purchase_orders.manage` | Purchase orders | Create, submit, cancel POs |
| `goods_in_transit.manage` | Goods in transit | Track shipment lifecycle |
| `goods_in_transit.receive` | Goods receipt | Receive into warehouse, apply landed costs |
| `stock.view` | Stock | View warehouse stock levels |
| `suppliers.manage` | Suppliers | CRUD supplier master data |
| `reports.purchasing` | Purchasing reports | PO status, supplier performance |

**Warehouse Keeper/أمين المستودع:**
| Permission | Resource | What it unlocks |
|---|---|---|
| `stock.import` | Stock import | Import daily stock report CSV |
| `stock.adjust` | Stock adjustment | Manual stock corrections (with reason) |
| `stock.export` | Stock export | Export to Excel |
| `stock.view` | Stock | View all warehouse/van/transit stock |
| `batches.manage` | Batch tracking | Create batches, upload COA PDF |
| `goods_in_transit.receive` | Goods receipt | Receive transit → warehouse |
| `reports.stock` | Stock reports | Expiry report, low stock report |

**Executive/فيور (Mohamed Taha):**
| Permission | Resource | What it unlocks |
|---|---|---|
| `alarms.view` | Alarm dashboard | Read-only alarm list |
| `reports.dashboard_view` | Dashboard | Read-only dashboard widgets |
| `reports.sales_view` | Sales reports | Read-only sales figures |
| `reports.visits_view` | Visit reports | Read-only visit activity |
| `reports.stock_view` | Stock reports | Read-only stock levels |
| `reports.intercompany_view` | Inter-company | Read-only cross-entity trades |

**Rep/مندوب:**
| Permission | Resource | What it unlocks |
|---|---|---|
| `sessions.manage` | Work sessions | Start/end work day |
| `visits.view_assigned` | Daily visits | See own assigned visits for the day |
| `visits.execute` | Visit flow | Confirm arrival (GPS), submit visit report |
| `visits.custom` | Custom visit | Visit unassigned customer (flagged) |
| `customers.add` | Add customer | Create new customer (pending status) |
| `customers.view_own` | Customers | See only customers they added or visited |
| `products.view` | Products | See product list, cost price hidden |
| `products.view_stock` | Stock availability | Search main warehouse + transit stock |
| `pricing.request` | Price quotation | Request price from manager |
| `pricing.negotiate` | Negotiate price | Sell within assigned ± range |
| `proformas.create` | Proforma invoice | Create proforma from quotation |
| `invoices.create` | Sales invoice | Create invoice from proforma or direct |
| `invoices.view_own` | My invoices | See own invoices only |
| `payments.collect` | Collections | Record cash/cheque/transfer payments |
| `returns.create` | Returns | Process customer returns (increases van stock) |
| `expenses.log` | Expenses | Record fuel/maintenance/food/other |
| `purchase_requests.submit` | Purchase request | Submit supplier deal requests |
| `alarms.flag_out_of_stock` | Out-of-stock request | Flag urgent product need |
| `complaints.submit` | Complaints | Log customer complaints |
| `cashbox.view` | Cash box | See own cash balance |
| `van_transfers.request` | Van transfer | Request stock from another rep |

### 12.2 RECOMMENDED — add in v1.1

| Permission | Resource | Rationale |
|---|---|---|
| `exports.excel` | All resources | Download any list as Excel |
| `invoices.amend` | Invoice amendment | Create amended copy after cancellation |
| `reports.expiry` | Expiry report | Dedicated batch expiry report |
| `reports.intercompany` | Inter-company report | Cross-entity transaction report |
| `communications.view` | Communication timeline | See notes/calls on documents |
| `communications.add` | Communication timeline | Add internal notes to documents |
| `audit.movements` | Stock movements | View full stock audit log |
| `products.barcode` | Barcode scanning | Scan-to-add in transactions |
| `warehouse_import.logs` | Import logs | View import history |

### 12.3 NICE TO HAVE — add when requested

| Permission | Rationale |
|---|---|
| `data_migration.import` | Odoo migration wizard (one-time use) |
| `users.manage_roles` | Role/permission assignment delegation |
| `reports.custom_builder` | Custom report builder |
| `products.bulk_price_update` | Mass price changes |
| `customers.bulk_import` | Mass customer CSV import |
| `quality_inspections.manage` | Quality checks per batch |
| `campaigns.manage` | Marketing campaign tracking |
| `leads.manage` | Lead pipeline management |
| `recycling_bin.view` | View soft-deleted records |
| `settings.system` | System-wide configuration |
| `backup.view` | Backup status (read-only) |

---

*End of guide. Begin at Phase 0.*
