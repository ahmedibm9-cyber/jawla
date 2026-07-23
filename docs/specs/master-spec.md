# Jawla (جولة) — Master Specification (BRD + SRS + FRS)

**Version:** 1.0  
**Status:** Final Draft  
**Applicable to:** v1 (Egypt only)  
**Client:** Global Plastic Company (GPC) / شركة اللدائن العالمية  
**Client Rep:** عمرو حكيم (Amr) — 01020909207  
**Executive (فيور):** Mohamed Taha

---

## Part I — BRD (Business Requirements Document)

### 1.1 Business Context

**GPC** is a plastics and chemicals trading company operating primarily in Egypt with ~10 employees. They buy polymers (PP, PE, PVC, PET, PS) and industrial chemicals (ethylene glycol, ethyl acetate, caustic soda, MEA/DEA, etc.) from international suppliers (90% import) and sell locally to manufacturers and intermediaries.

Current systems: **Odoo** (accounting/inventory) + **Excel** (field sales tracking). Pain points:

- No field mobility — reps use paper/phone, data entered into Odoo later
- No real-time stock visibility for reps
- Manual price approval chain (WhatsApp/phone → delays)
- No GPS visit verification
- Separate systems don't reconcile

### 1.2 Business Objectives (SMART)

| Objective                         | Metric                                         | Target                     | Timeline            |
| --------------------------------- | ---------------------------------------------- | -------------------------- | ------------------- |
| Eliminate double data entry       | Field data entered once at source              | 100% of field transactions | v1 launch + 30 days |
| Reduce quotation-to-invoice cycle | Time from rep request to confirmed price       | ≤ 2 hours                  | v1 launch + 60 days |
| Visit compliance                  | % of planned visits with GPS-confirmed arrival | ≥ 90%                      | v1 launch + 90 days |
| Stock accuracy                    | Discrepancy between system and physical count  | ≤ 1%                       | v1 launch + 90 days |
| Single source of truth            | All transactions in Jawla, Odoo retired        | 100%                       | v1 launch + 60 days |

### 1.3 Stakeholders

| Stakeholder            | Role               | Key Concern                                         |
| ---------------------- | ------------------ | --------------------------------------------------- |
| Amr (عمرو حكيم)        | Admin/System Owner | Full system control, data migration from Odoo       |
| Mohamed Taha           | Executive (فيور)   | Read-only visibility into sales, alarms             |
| Sales Manager          | Operations         | Daily visit planning, price control, team oversight |
| Accounts (3-4 users)   | Finance            | Base pricing, landed costs, e-invoicing compliance  |
| Purchasing (1-2 users) | Procurement        | Supplier comparisons, PO management, GIT tracking   |
| Warehouse Keeper       | Inventory          | Daily stock import, batch tracking, goods receipt   |
| Reps (3 users)         | Field Sales        | Visit execution, quotations, invoicing, collections |

### 1.4 Scope — In Scope (v1)

- Field sales rep PWA (mobile web) for visit management, quoting, invoicing, collections, returns
- Admin panel (Filament) for master data, reporting, configuration
- Multi-level pricing (Accounts → Manager → Rep)
- Batch/lot tracking with COA
- Goods-in-transit with landed cost
- Egypt ETA e-invoicing (bilingual AR/EN PDF + QR)
- 7 user roles with granular permissions
- Alarm/notification system (7 triggers)
- Data migration from Odoo (CSV/Excel)
- GPS geofencing for visit verification

### 1.5 Scope — Out of Scope (v1, deferred to v2)

- Saudi Arabia legal entity
- Inter-company transactions
- ZATCA (Saudi e-invoicing)
- Full offline data sync
- Bluetooth thermal printing
- WhatsApp integration
- AI route optimization
- Shelf image recognition

### 1.6 Assumptions

1. All sales are prepaid — no credit terms needed in v1
2. Single currency EGP for sales; POs in USD/CNY
3. Reps have smartphones with GPS + mobile data
4. Odoo data exportable as CSV/Excel
5. Warehouse stock imported daily from external system

---

## Part II — SRS (System Requirements Specification)

### 2.1 Functional Requirements — By Module

#### FR-01: Company Management

- System supports multiple legal entities (schema ready, Egypt only in v1)
- Each company has: name (AR/EN), legal entity type, abbreviated code, tax number, CR number, address, phone, logo, default currency, VAT percentage
- Naming series per company for all documents

#### FR-02: User Management

- 7 roles: admin, sales_manager, accounts, purchasing, warehouse_keeper, executive, rep
- Creating a rep auto-creates van warehouse + cash box
- Roles control sidebar navigation visibility
- Cost price never exposed to sales roles at any layer

#### FR-03: Product Management

- Products belong to categories (AR/EN names)
- Product fields: SKU, names (AR/EN), packaging type, unit, price, cost, VAT flag, batch tracking flag, expiry tracking flag, variants, bundle flag, max discount, valuation method
- Cost price visible only to accounts + admin + purchasing
- Products can be batch-tracked or not (per-product toggle)

#### FR-04: Customer Management

- Customers have: code (unique per company), names (AR/EN), phone (unique), address, GPS coordinates, group, territory, price list, status
- Rep-added customers start as `pending`; admin-added customers are active immediately
- Sales manager approves/rejects pending customers
- Duplicate check on name/phone before creation

#### FR-05: Route & Visit Planning

- Routes belong to a company, have AR/EN names
- Reps assigned to routes (many-to-many)
- Sales manager assigns daily visits (customer + rep + date + sort order)
- Default 5 visits per rep per day
- Rep can create "custom visit" for unassigned customers (flagged)

#### FR-06: Work Session & GPS Visit

- Rep starts day with GPS check-in → creates work session
- Today's assigned visits shown as ordered cards
- GPS geofence (1 km radius) auto-confirms arrival at customer location
- Outside radius → warning + manual confirm option
- Visit report: summary, feedback, action taken, follow-up flag

#### FR-07: Pricing Chain

- Accounts sets base price + cost price (cost hidden from sales roles)
- Sales manager receives ± range from Accounts
- Manager gives rep a sub-range (rep_plus ≤ manager_plus, rep_minus ≤ manager_minus)
- Rep requests quotation → manager sets price within their range
- Rep negotiates final price within their sub-range
- System blocks prices outside permitted range

#### FR-08: Proforma Invoice

- Created from confirmed quotation (or standalone)
- Unit price validated against rep's allowed range
- Includes company bank details
- Sequential number via naming series
- Status: draft → sent → converted_to_invoice → cancelled
- No stock deducted at proforma stage

#### FR-09: Sales Invoice (Field)

- Atomic DB transaction: invoice + items + stock deduction + stock movements + customer balance update
- Batch selection required for batch-tracked products
- Validates van stock ≥ requested qty for each line
- Bilingual AR/EN PDF with Egypt ETA QR code
- Sequential numbering per company
- Status: draft → submitted → cancelled → amended

#### FR-10: Collections

- Record payment: amount, mode (cash/cheque/transfer/LC), linked invoice (optional)
- Increases rep's cash box balance
- Decreases customer balance
- Updates invoice paid_amount

#### FR-11: Product Returns

- Process return: select product + batch + quantity
- Increases van stock (stock_movement reason='return')
- Decreases customer balance

#### FR-12: Field Expenses

- Rep logs expense: category (fuel/maintenance/food/other), amount, note
- Decreases cash box balance

#### FR-13: Purchase Requests

- Rep submits: supplier, product, qty, offered price, currency, payment terms
- Sales manager can veto (slow-moving items)
- Purchasing reviews and creates supplier quotations

#### FR-14: Supplier Quotations

- Purchasing collects quotes from multiple suppliers
- Side-by-side comparison: price, currency, payment terms, delivery days
- Accept one → auto-reject others
- Accepted quotation → creates Purchase Order

#### FR-15: Purchase Orders

- Multi-currency (USD/CNY/EGP)
- Sequential numbering via naming series
- Partial receipt tracking (received_qty on PO items)

#### FR-16: Goods in Transit

- Track international shipments: in_transit → at_customs → cleared → received
- Record shipping/customs/clearance/freight/duty/port_charges costs
- Landed costs distributed proportionally across products by quantity
- On receipt: stock movement to main warehouse + cost price update (moving average)

#### FR-17: Batch Tracking & COA

- Batches linked to products with batch number + dates
- COA PDF upload per batch
- Stock view per batch with qty + expiry
- Auto-alarm for batches expiring within 30 days

#### FR-18: Alarms

- 7 auto-trigger types (OOS, complaint, new customer, price quote, purchase request, GIT delay, batch expiry)
- Severity: critical/ warning/ info
- Dashboard grouped by severity with color coding
- Sales manager response: acknowledge → assign → resolve

#### FR-19: Egypt ETA E-Invoicing

- Invoice PDF: bilingual AR/EN with company logo + info + bank details
- QR code: JSON {seller_name, tax_number, timestamp, total, tax_total} → Base64 → QR on PDF
- Sequential invoice numbers per company

#### FR-20: Reports & Dashboard

- Sales dashboard: total sales, top products, per-rep ranking, conversion rate
- Visit dashboard: planned vs actual, missed rate, avg duration
- Financial: revenue trend, VAT collected
- Stock: low stock, expiry timeline, GIT ETA
- Alarm dashboard by severity and type
- Visit map (Leaflet): GPS pins color-coded by status
- Excel export on all tables (spatie/simple-excel)

### 2.2 Non-Functional Requirements

| ID     | Category      | Requirement                                                    |
| ------ | ------------- | -------------------------------------------------------------- |
| NFR-01 | Performance   | Invoice creation ≤ 2 seconds including PDF generation          |
| NFR-02 | Performance   | Dashboard page load ≤ 3 seconds                                |
| NFR-03 | Performance   | Stock search response ≤ 1 second                               |
| NFR-04 | Availability  | 99.5% uptime during business hours (Sun-Thu 9am-5pm)           |
| NFR-05 | Security      | Cost price never transmitted to client for sales roles         |
| NFR-06 | Security      | All transactions logged in stock_movements (append-only audit) |
| NFR-07 | Usability     | Rep PWA operable with one hand on a phone                      |
| NFR-08 | Usability     | Arabic primary language with full RTL                          |
| NFR-09 | Scalability   | Supports ≤ 50 users, ≤ 100k transactions/month                 |
| NFR-10 | Compatibility | Works on Chrome for Android (primary), Safari iOS (secondary)  |

### 2.3 Data Requirements

- **Database:** PostgreSQL 16
- **Storage:** File storage for COA PDFs, product images, invoice PDFs
- **Backup:** Daily database backup, file backup every 6 hours
- **Data retention:** Stock movements never deleted. Soft-delete on customers, products, invoices, users.
- **Migration:** All data from Odoo importable via CSV/Excel wizards

### 2.4 Interface Requirements

- **External:** None (no third-party API integrations in v1)
- **Internal:**
  - Admin Panel: Filament 4 (`/admin`)
  - Rep PWA: Livewire 3 (`/app`)
  - Both share same Laravel backend + database

### 2.5 Hardware Requirements

- **Server:** VPS, 4 GB RAM, 2 vCPU, 50 GB SSD
- **Client (admin):** Desktop/laptop, modern browser
- **Client (rep):** Smartphone, Android/iOS, Chrome/Safari, GPS enabled

---

## Part III — FRS (Functional Requirements Specification)

### 3.1 Use Case: Daily Rep Workflow

```
Actor: Rep
Precondition: Authenticated, active, within work hours
Flow:
  1. Rep opens /app → sees Home screen with "Start Work" button
  2. Clicks "Start Work" → GPS captured → work_session created → redirected to visit list
  3. Today's assigned visits shown as cards (customer name, code, address, sequence)
  4. Rep taps a customer → navigates to customer location
  5. System detects arrival within 1 km → auto-confirms with ✅
  6. If outside 1 km → shows warning + "Manual Confirm" button
  7. Submit visit report (summary required, feedback/action/follow-up optional)
  8. Operations menu: Sell, Collect, Return, Quote, Proforma, Complaint, OOS
  9. Rep performs operations (see sub-flows)
  10. "End Visit" → checkout_at recorded → back to visit list
  11. "End Day" → work_session closes → daily summary shown
Postcondition: Work session closed. All operations recorded.
```

### 3.2 Use Case: Price Quotation → Invoice

```
Actors: Rep, Sales Manager, Accounts
Precondition: Customer visit open or confirmed quotation needed
Flow:
  1. Rep selects product + qty → submits price_quotation_request (status='requested')
  2. Alarm created for sales manager
  3. Manager opens request → sees base_price from Accounts
  4. Manager sets: base_price final, manager_plus, manager_minus, rep_plus, rep_minus
  5. System validates rep_plus ≤ manager_plus, rep_minus ≤ manager_minus
  6. Status → 'priced'
  7. Rep sees allowed price range (rep_minus to rep_plus from base_price)
  8. Rep enters final price → system validates within range
  9. If valid → status='confirmed'
  10. Rep creates proforma from confirmed quotation → includes bank info → status='draft'
  11. Proforma sent to customer (status='sent')
  12. Rep converts proforma to invoice → stock deducted → PDF generated → status='submitted'
Postcondition: Customer invoiced. Stock adjusted. QR generated.
```

### 3.3 Use Case: Atomic Sale Transaction

```
Actor: System (internal)
Precondition: All validation passes (stock, price, customer status)

DB::transaction():
  Step 1: INSERT invoice (status='submitted', totals calculated)
  Step 2: INSERT invoice_items (each with product_id, batch_id, qty, unit_price)
  Step 3: FOR EACH item:
            - UPDATE stocks SET quantity = quantity - qty
              WHERE warehouse_id = rep.van_id AND product_id = item.product_id
              AND (batch_id = item.batch_id OR batch_id IS NULL)
            - IF affected_rows = 0 OR new quantity < 0 → ROLLBACK
            - INSERT stock_movements (reason='sale', quantity_change = -qty)
  Step 4: UPDATE customers SET balance = balance + invoice.total
  Step 5: Generate invoice PDF with QR → save to storage
  COMMIT

Postcondition: Invoice exists. Stock reduced. Balance updated. PDF stored.
```

### 3.4 Use Case: Goods in Transit → Stock

```
Actors: Purchasing (create), Warehouse Keeper (receive)
Flow:
  1. Purchasing creates GIT shipment: supplier, items, costs, ETA
  2. Status = 'in_transit'
  3. As shipment progresses: status updated → at_customs → cleared
  4. Past ETA without 'received' status → critical alarm
  5. On arrival:
     a. Warehouse keeper sets status = 'received'
     b. FOR EACH GIT item:
        - INSERT stock_movement (reason='transit_in', quantity_change = +qty)
        - UPDATE stocks SET quantity = quantity + qty
     c. Distribute landed costs proportionally:
        - total_landed_cost = Σ landed_costs.amount
        - FOR EACH item:
            proportion = item.qty / SUM(all_items.qty)
            allocated_cost = total_landed_cost * proportion
            item.unit_price += allocated_cost / item.qty
     d. Update product cost price (moving average):
        new_cost = (current_cost * current_qty_on_hand + shipment_unit_cost * shipment_qty)
                   / (current_qty_on_hand + shipment_qty)
Postcondition: Stock updated. Cost price recalculated. Landed costs recorded.
```

### 3.5 Use Case: Alarm Generation

```
Triggers (all system-generated, no manual creation):

| Trigger | Condition | Severity | Visible To |
|---|---|---|---|
| Out of Stock Request | Rep creates OOS request | Critical | Admin, Sales Mgr, Accounts, Executive |
| Complaint | Rep/Customer logs complaint | Critical | Admin, Sales Mgr, Executive |
| New Customer Pending | Rep adds customer | Warning | Admin, Sales Mgr |
| Price Quotation Requested | Rep submits quotation request | Warning | Admin, Sales Mgr |
| Purchase Request Submitted | Rep submits purchase request | Info | Admin, Sales Mgr, Purchasing |
| GIT Past ETA | now() > GIT.estimated_arrival AND status != 'received' | Critical | Admin, Purchasing |
| Batch Expiring | expiry_date ≤ now() + 30 days | Warning | Admin, Warehouse Keeper |

Each alarm: type, reference (morph), title, description, severity, is_read, read_by, read_at.
```

### 3.6 State Machines

```
Invoice:       draft → submitted → cancelled
                          ↓
                      amended (linked to original via amended_from FK)

Proforma:      draft → sent → converted_to_invoice → cancelled

Purchase Order: draft → sent → confirmed → partial → received → cancelled

GIT:           in_transit → at_customs → cleared → received

Customer:      pending → approved
                        → rejected

Complaint:     open → in_progress → resolved → closed

Visit:         open → closed
```

### 3.7 Calculation Rules

```
line_total = quantity × unit_price
subtotal = Σ line_total (across all items)
vat_amount = Σ (line_total × company.vat_percent / 100) — only for vat_applicable products
total = subtotal + vat_amount
remaining_amount = total - paid_amount

Cash box: balance = Σ collections - Σ expenses - Σ returns (negative)
Customer balance: balance = Σ invoice.total - Σ payments
Landed cost distribution: allocated_cost = total_cost × (item_qty / total_qty)
Cost price (moving average): (current_cost × current_qty + new_cost × new_qty) / (current_qty + new_qty)
```

### 3.8 Validation Matrix

| Rule                                  | Enforced At   | Error Message                                                           |
| ------------------------------------- | ------------- | ----------------------------------------------------------------------- |
| Van stock ≥ sale qty                  | Service layer | "الرصيد غير كافٍ / Insufficient stock"                                  |
| Price within rep range                | Service layer | "السعر خارج النطاق المسموح / Price outside allowed range"               |
| Pending customer cannot transact      | Service layer | "العميل في انتظار الموافقة / Customer pending approval"                 |
| Batch required for tracked products   | Service layer | "يرجى تحديد رقم التشغيلة / Batch number required"                       |
| Unique phone per company              | Database + UI | "رقم الهاتف موجود مسبقاً / Phone already exists"                        |
| Proforma can't convert if cancelled   | Service layer | "لا يمكن تحويل فاتورة مبدئية ملغية / Cannot convert cancelled proforma" |
| Unique naming series per doc          | Service layer | "رقم المستند مكرر / Duplicate document number"                          |
| Rep_plus ≤ manager_plus               | Service layer | "نطاق المندوب يتجاوز نطاق المدير / Rep range exceeds manager range"     |
| Rep can only see own van + cash       | Service layer | "ليس لديك صلاحية / Unauthorized"                                        |
| Approve/reject only pending customers | Service layer | "العميل ليس في حالة انتظار / Customer is not pending"                   |

---

## Appendix: Document Index

| Document                                 | Location                                              |
| ---------------------------------------- | ----------------------------------------------------- |
| Build Guide (single source of truth)     | `Jawla_Build_Guide_v1_Reference.md`                   |
| Domain Glossary                          | `CONTEXT.md`                                          |
| ADR: Single Currency EGP                 | `docs/adr/0001-single-currency-egp.md`                |
| ADR: Proforma → Invoice (no Sales Order) | `docs/adr/0002-proforma-to-invoice-no-sales-order.md` |
| Permission Matrix                        | `docs/specs/permission-matrix.md`                     |
| Development Backlog                      | `docs/specs/backlog.md`                               |
| ERD                                      | `docs/specs/erd.md`                                   |
| BPMN Workflows                           | `docs/specs/bpmn.md`                                  |
| KPI Catalog                              | `docs/specs/kpi-catalog.md`                           |
| UI Specification                         | `docs/specs/ui-spec.md`                               |
| OpenAPI Contract                         | `docs/specs/openapi.md`                               |
