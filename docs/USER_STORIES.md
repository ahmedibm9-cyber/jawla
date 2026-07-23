# Jawla — User Stories by Role & Epic

> Generated from codebase exploration · 2026-07-23

---

## Epic 1: Authentication & Session Management

### US-1.1 — Rep Login

**As a** sales representative
**I want to** log in with my email and password
**So that** I can access my sales dashboard and start my work day

**Acceptance Criteria:**

- Login form at `/app/login` accepts email + password
- Rate-limited to 5 attempts per minute per IP+email
- Validates credentials, `is_active`, and `rep` role
- On success: session regenerated, redirected to `/app` (Home)
- On failure: bilingual error message displayed
- Session cleared on logout (`Clear-Site-Data` header)

### US-1.2 — Admin Login

**As an** admin/manager
**I want to** log in to the Filament admin panel
**So that** I can manage company data, view reports, and approve requests

**Acceptance Criteria:**

- Login form at `/admin/login` with rate limiting (5 attempts)
- MFA support available
- Rep users redirected to `/app` if they try `/admin/login`
- Admin users cannot access `/app` (rep PWA)

### US-1.3 — Logout

**As a** user (any role)
**I want to** log out securely
**So that** my session is invalidated

**Acceptance Criteria:**

- POST `/app/logout` invalidates session
- CSRF token regenerated
- Browser storage cleared via `Clear-Site-Data`
- Redirected to login page

---

## Epic 2: Work Day Management

### US-2.1 — Start Work Day

**As a** sales representative
**I want to** start my work day with GPS tracking
**So that** my manager can see I'm on shift and track my location

**Acceptance Criteria:**

- "Start Work" button on Home dashboard
- Creates a `WorkSession` with current GPS coordinates
- Session stored in browser session for the day
- Background location pings start (rate-limited to 1/30 seconds)
- Pings silently dropped if no active WorkSession

### US-2.2 — View Today's Visit Schedule

**As a** sales representative
**I want to** see my assigned visits for today, sorted by priority
**So that** I know which customers to visit and in what order

**Acceptance Criteria:**

- Home dashboard shows `DailyVisitAssignment` records for today
- Sorted by `sort_order`
- Shows completed vs pending counts
- Each visit shows customer name, address, and status

### US-2.3 — Complete a Task

**As a** sales representative
**I want to** mark tasks as done from my dashboard
**So that** I track my daily progress

**Acceptance Criteria:**

- Tasks displayed on Home dashboard
- One-tap completion
- Status updates to `done`

---

## Epic 3: Visit Management

### US-3.1 — Start Visit (Check-in)

**As a** sales representative
**I want to** check in at a customer location with GPS verification
**So that** my visit is geofenced and auditable

**Acceptance Criteria:**

- Tap visit assignment → redirected to `VisitFlow`
- Client sends GPS coordinates
- Server calculates distance to customer location
- Geofence check: within configurable radius (company's `geofence_radius_m`)
- `confirmArrival()` re-validates GPS server-side (never trusts client)
- Sets `arrival_confirmed = true`, `checkin_at = now()`, `status = 'open'`
- Declined check-in attempts are logged

### US-3.2 — Submit Visit Report

**As a** sales representative
**I want to** submit a visit summary after completing a customer visit
**So that** my manager has visibility into what happened

**Acceptance Criteria:**

- Form with: summary (min 5 chars), customer feedback, action taken, follow-up notes
- Photo attachment support (up to 5MB, jpg/png/webp)
- Digital signature capture
- Submits via `VisitReportService::submit()`
- Visit status changes to `closed`
- Can be queued offline if no connectivity

### US-3.3 — View Visit History

**As a** sales representative
**I want to** see my past visits
**So that** I can review my activity

**Acceptance Criteria:**

- Paginated list of user's visits
- Shows customer name, date, status
- Accessible from navigation

### US-3.4 — View Real-Time Rep Map (Admin)

**As a** sales manager
**I want to** see all on-shift reps on a live map
**So that** I can monitor field activity

**Acceptance Criteria:**

- Leaflet map at `/admin/rep-live-map`
- Shows reps with active WorkSession in last 30 minutes
- Auto-refreshes every 30 seconds
- Shows rep name, last known position
- Access: admin, sales_manager, executive

---

## Epic 4: Sales & Invoicing

### US-4.1 — Create Invoice (Core Sale)

**As a** sales representative
**I want to** create an invoice for a customer by selecting products from my van stock
**So that** I can record a sale and deliver goods

**Acceptance Criteria:**

- Cart-based flow: search/select customer → add products → review → submit
- Product search by name, SKU, or barcode scan
- Cart shows: product name, qty, unit price, line total, VAT
- Qty adjustable (1-9999), price adjustable (floor 0.01)
- Subtotal + VAT calculated client-side using company's `vat_percent`
- On submit:
  - `InvoiceService::create()` runs in `DB::transaction`
  - Validates seller (locked forUpdate), products, customer
  - Finds rep's active van warehouse
  - Generates sequential invoice number
  - Creates `Invoice` (status=Submitted) + `InvoiceItems[]`
  - Decrements van stock per item (throws `InsufficientStockException` if negative)
  - Increments customer balance
- Thermal print payload generated for receipt
- Undo toast dispatched (can cancel within session)
- Bilingual error messages on failure

### US-4.2 — Scan Barcode

**As a** sales representative
**I want to** scan a product barcode to quickly add it to my cart
**So that** I can speed up the order process

**Acceptance Criteria:**

- Barcode scanner input field
- Resolves barcode to product via SKU lookup
- If product found: adds to cart (increments qty if already present)
- If not found: shows error

### US-4.3 — View Order History

**As a** sales representative
**I want to** see my past invoices, proformas, and purchase offers
**So that** I can track my sales activity

**Acceptance Criteria:**

- Tabbed view: Invoices | Proformas | Purchase Offers
- Paginated, filtered to current user
- Shows invoice number, customer, total, status, date

### US-4.4 — Generate Invoice PDF

**As an** admin/manager
**I want to** view and share invoice PDFs
**So that** I can send invoices to customers

**Acceptance Criteria:**

- PDF generation via `PdfController`
- WhatsApp share link
- Available from InvoiceResource in admin

### US-4.5 — View Invoice Register (Admin)

**As an** admin/manager
**I want to** see all invoices with status filters
**So that** I can monitor sales activity

**Acceptance Criteria:**

- Read-only table in `InvoiceResource`
- Columns: number, customer, status (color-coded), subtotal, VAT, total, paid, remaining, date
- Filters: status, date range, customer, rep
- Status colors: draft=gray, submitted=info, partially_paid=warning, paid=success, cancelled=danger

---

## Epic 5: Invoice Lifecycle & Status Management

### US-5.1 — Invoice Status Flow

**System:** Invoices follow this status lifecycle:

```
Submitted → PartiallyPaid → Paid
    ↓
Cancelled (compensating reversal)
    ↓
Amended (cancel + create new Draft)
```

### US-5.2 — Cancel Invoice

**As an** admin/manager
**I want to** cancel an invoice with full reversal
**So that** stock, balances, and records are correctly adjusted

**Acceptance Criteria:**

- `InvoiceService::cancel()` runs in `DB::transaction`
- Re-fetches invoice with `lockForUpdate()` (prevents double-cancel)
- Sets status=Cancelled, cancelled_at, cancelled_by
- Reverses stock: increments van warehouse for each item
- Reverses customer balance (unpaid portion only)
- Logs `invoice_reversed` activity
- Never deletes — compensating transaction only

### US-5.3 — Amend Invoice

**As an** admin/manager
**I want to** amend an invoice (cancel + create new draft)
**So that** I can correct errors while preserving audit trail

**Acceptance Criteria:**

- `InvoiceService::amend()` cancels original invoice
- Creates new Invoice with status=Draft, `amended_from=original.id`
- Copies all line items to new draft
- Original invoice remains visible with amended status

---

## Epic 6: Payment Collection

### US-6.1 — Collect Payment (Rep)

**As a** sales representative
**I want to** collect payment from a customer against their invoice
**So that** I can record cash/cheque received and update their balance

**Acceptance Criteria:**

- Form: select customer → select invoice (shows remaining_amount) → enter amount → select method → notes
- Invoice dropdown shows only: status IN (submitted, partially_paid) AND remaining_amount > 0
- Auto-fills amount with invoice's remaining_amount
- `PaymentService::collect()` runs in `DB::transaction`
- Creates Payment record
- If cash: increments CashBox balance
- Updates invoice: paid_amount += amount, remaining_amount -= amount
- Status → Paid (if remaining <= 0) or PartiallyPaid
- Decrements customer balance
- Thermal print receipt generated
- Undo toast dispatched

### US-6.2 — Cancel Payment

**As an** admin
**I want to** cancel a payment with full reversal
**So that** errors can be corrected

**Acceptance Criteria:**

- `PaymentService::cancel()` reverses: cashbox, invoice amounts, customer balance
- Sets cancelled_at, cancelled_by
- Activity logged

### US-6.3 — View Payment Register (Admin)

**As an** admin/manager
**I want to** see all payments with status
**So that** I can track collections

**Acceptance Criteria:**

- Read-only table with: customer, invoice, collector, amount, method, date, status
- Cancel action available

---

## Epic 7: Returns Management

### US-7.1 — Log Return (Rep)

**As a** sales representative
**I want to** record product returns from a customer
**So that** stock is restored and customer balance adjusted

**Acceptance Criteria:**

- Form: select customer → add items (product, qty, price) → enter reason → attach photos
- Dynamic item list (add/remove rows)
- `ReturnService::create()` runs in `DB::transaction`
- Increments van stock for returned items
- Decrements customer balance
- Creates ReturnRecord with sequential number
- Photo attachment support
- Undo toast dispatched
- Can be queued offline

### US-7.2 — Cancel Return (Admin)

**As an** admin
**I want to** cancel a return with full reversal
**So that** errors can be corrected

**Acceptance Criteria:**

- `ReturnService::cancel()` reverses stock + balance
- Activity logged

---

## Epic 8: Expense Management

### US-8.1 — Log Expense (Rep)

**As a** sales representative
**I want to** record expenses during my work day
**So that** they're tracked against my cash box

**Acceptance Criteria:**

- Categories: fuel, maintenance, food, other
- Form: category, amount, note (max 500 chars)
- **Guard:** amount must not exceed current cashbox balance
- `ExpenseService::log()` creates record with work_session_id
- Undo toast dispatched
- Can be queued offline

### US-8.2 — View Expense Register (Admin)

**As an** admin/manager
**I want to** see all expenses by rep and category
**So that** I can control costs

**Acceptance Criteria:**

- Read-only table with filters
- Cancel action available via `ExpenseService`

---

## Epic 9: Customer Management

### US-9.1 — Add New Customer (Rep)

**As a** sales representative
**I want to** submit a new customer for approval
**So that** I can start selling to them

**Acceptance Criteria:**

- Form: name_ar, name_en, phone, address, GPS coordinates
- Auto-generated code: `C-` + 6-char unique
- Created with status=pending, added_by=current user
- Alarm raised: type=`new_customer_pending`
- Admin/manager notified

### US-9.2 — Approve/Reject Customer (Admin)

**As an** admin/sales manager
**I want to** approve or reject pending customers
**So that** only legitimate customers enter the system

**Acceptance Criteria:**

- Approve: sets status=approved, approved_by, approved_at. Notifies rep.
- Reject: requires rejection_reason (textarea). Sets status=rejected, is_active=false. Notifies rep.
- Visibility: only when status=pending AND added_by != current user
- Confirmation modal required

### US-9.3 — View Customer Directory

**As a** sales representative
**I want to** search and view my customers
**So that** I can find contact info and addresses

**Acceptance Criteria:**

- Paginated search by name, code, phone (limit 30/page)
- Shows customer details

### US-9.4 — Manage Customers (Admin)

**As an** admin
**I want to** CRUD customers with geolocation
**So that** I have full customer data

**Acceptance Criteria:**

- Filament form with interactive Leaflet map for GPS
- Fields: names, code, phone, address, route, credit_limit, balance, status
- Filters by route, status, active

---

## Epic 10: Pricing & Quotations

### US-10.1 — Request Price Quotation

**As a** sales representative
**I want to** request a special price for a customer
**So that** I can negotiate deals outside standard pricing

**Acceptance Criteria:**

- Submit request with: customer, product, quantity
- Status: requested → priced → confirmed/cancelled

### US-10.2 — Set Quotation Price (Admin)

**As an** admin/accounts user
**I want to** set approved price ranges for quotation requests
**So that** reps can negotiate within bounds

**Acceptance Criteria:**

- "Set Price" action on `PriceQuotationRequestResource`
- Fields: base_price, manager_plus, manager_minus, rep_plus, rep_minus
- Creates `PriceQuotation` record
- Notifies rep of available range

### US-10.3 — Negotiate Price (Rep)

**As a** sales representative
**I want to** confirm a negotiated price within the approved range
**So that** I can finalize the deal

**Acceptance Criteria:**

- `QuotationFlow` shows floor (base - rep_minus) and ceiling (base + rep_plus)
- Price must be within floor/ceiling range
- Status → confirmed with negotiated_price

### US-10.4 — Create Proforma Invoice

**As a** sales representative
**I want to** generate a proforma invoice from a confirmed quotation
**So that** the customer has a formal offer

**Acceptance Criteria:**

- `QuotationFlow::createProforma()`
- Generates sequential proforma number
- Calculates VAT via `InvoiceCalculationService`
- Creates `ProformaInvoice` + `ProformaInvoiceItem` in `DB::transaction`
- Shows company bank account details
- Can be shared as PDF or via WhatsApp

---

## Epic 11: Stock & Inventory Management

### US-11.1 — View Van Stock

**As a** sales representative
**I want to** see what products are available in my van
**So that** I know what I can sell

**Acceptance Criteria:**

- `StockSearch` component: search by SKU/name (min 2 chars)
- Shows products with stock > 0, warehouse name, quantity
- Out-of-stock items flagged

### US-11.2 — Flag Out-of-Stock

**As a** sales representative
**I want to** flag a product as out of stock
**So that** the warehouse knows to restock my van

**Acceptance Criteria:**

- `StockSearch::submitFlag()` via `OutOfStockService`
- Raises alarm: type=`out_of_stock_request`
- Alarm visible in admin alarm register

### US-11.3 — View Stock Balances (Admin)

**As an** admin/warehouse keeper
**I want to** see stock levels across all warehouses
**So that** I can manage inventory

**Acceptance Criteria:**

- `StockResource`: read-only list with warehouse, product, SKU, batch, quantity
- Color-coded: low (<=20 main, <=10 van), out-of-stock (<=0)
- **Adjust action** (admin/warehouse_keeper): reconcile via StockService

### US-11.4 — Adjust Stock (Admin)

**As an** admin/warehouse keeper
**I want to** reconcile physical stock counts
**So that** system stock matches reality

**Acceptance Criteria:**

- Select warehouse + product → enter counted_quantity + reason
- `StockService::adjust()` creates StockMovement with reason=Adjustment

### US-11.5 — Import Stock (Admin)

**As an** admin
**I want to** bulk import stock via CSV
**So that** initial setup is fast

**Acceptance Criteria:**

- `StockImport` page: upload CSV, preview, apply
- Template download available
- Permission-gated: `stock.import`

---

## Epic 12: Van Transfers

### US-12.1 — Create Van Transfer (Admin)

**As an** admin
**I want to** create a stock transfer between a main warehouse and a rep's van
**So that** reps have inventory to sell

**Acceptance Criteria:**

- `VanTransferResource`: select from_user, to_user, items
- Creates transfer with status=pending

### US-12.2 — Ship Transfer (Admin)

**As an** admin/warehouse keeper
**I want to** ship a pending transfer
**So that** stock is deducted from the source warehouse

**Acceptance Criteria:**

- Status: pending → shipped
- Select source main warehouse
- Deducts stock from main warehouse

### US-12.3 — Receive Transfer (Rep)

**As a** sales representative
**I want to** receive stock into my van
**So that** I can sell the transferred products

**Acceptance Criteria:**

- `VanTransfers::receive()` — guard: transfer must be addressed to current user
- Status: shipped → received
- Adds stock to receiver's van warehouse

### US-12.4 — Reject/Cancel Transfer (Admin)

**As an** admin
**I want to** reject or cancel transfers
**So that** errors can be corrected

**Acceptance Criteria:**

- Reject: pending/accepted → rejected
- Cancel: pending → cancelled

---

## Epic 13: Purchase Orders

### US-13.1 — Submit Purchase Offer (Rep)

**As a** sales representative
**I want to** submit a purchase offer to a supplier
**So that** I can source products my customers need

**Acceptance Criteria:**

- `SubmitPurchaseOffer`: product, supplier, quantity, offered_price, currency (EGP/USD/EUR/SAR), payment_terms, expires_at
- Creates PurchaseRequest with status=pending
- Supports resubmission of rejected offers

### US-13.2 — Sales Approve Purchase Request

**As an** admin/sales manager
**I want to** review and approve purchase requests from reps
**So that** only valid purchases proceed

**Acceptance Criteria:**

- Status: pending → sales_approved (approve) or rejected_by_sales (reject)
- Visibility: status=pending && !expired
- Notification sent to rep

### US-13.3 — Purchasing Approve & Generate PO

**As an** admin/purchasing user
**I want to** finalize approved purchase requests into purchase orders
**So that** procurement is formalized

**Acceptance Criteria:**

- Status: sales_approved → creates PurchaseOrder
- PO has: order_number, supplier, items, total, status=draft
- Notification sent to rep

### US-13.4 — View Purchase Order Register (Admin)

**As an** admin/purchasing user
**So that** I can track all purchase orders

**Acceptance Criteria:**

- Read-only `PurchaseOrderResource`
- Statuses: draft, sent, confirmed, partial, received, cancelled

### US-13.5 — Compare Supplier Pricing

**As an** admin/purchasing user
**I want to** compare prices from different suppliers for the same product
**So that** I can choose the best deal

**Acceptance Criteria:**

- `SupplierComparison` page: groups open offers by product
- Shows supplier, price, currency side-by-side
- Sorted by price

---

## Epic 14: Goods in Transit

### US-14.1 — Track Incoming Shipments

**As an** admin/warehouse keeper
**I want to** track shipments from suppliers
**So that** I know when inventory will arrive

**Acceptance Criteria:**

- `GoodsInTransitResource`: shipment_number, supplier, status, ETA
- Statuses: in_transit → at_customs → cleared → received
- Landed costs: shipping, freight, customs, clearance

### US-14.2 — Receive Shipment

**As an** admin/warehouse keeper
**I want to** receive a shipment into a warehouse
**So that** stock is available for sale

**Acceptance Criteria:**

- Select target warehouse
- `GoodsInTransitService::receive()` adds items to warehouse stock
- Status → received

---

## Epic 15: Complaints & Alarms

### US-15.1 — Log Complaint (Rep)

**As a** sales representative
**I want to** report a customer complaint
**So that** the company can address quality/delivery issues

**Acceptance Criteria:**

- `LogComplaint`: customer, type (non_conforming/delivery/quality/pricing/other), description (min 5 chars)
- Photo attachment support
- Creates Complaint record + Alarm

### US-15.2 — Manage Complaints (Admin)

**As an** admin/manager
**I want to** view and resolve complaints
**So that** customer issues are addressed

**Acceptance Criteria:**

- `ComplaintResource`: list with status filters
- Statuses: open → in_progress → resolved → closed
- Resolution field for notes

### US-15.3 — View & Act on Alarms

**As an** admin/manager
**I want to** see a real-time alarm feed
**So that** I can respond to urgent issues

**Acceptance Criteria:**

- `AlarmResource`: list with severity badges (critical/warning/info)
- Types: out_of_stock, customer_complaint, new_customer_pending, price_quotation, purchase_request, goods_in_transit_delayed, batch_expiring
- Actions: Acknowledge (marks as read), Resolve (closes alarm)
- Resolving an out_of_stock alarm also resolves the linked OutOfStockRequest
- Navigation badge shows unread count

---

## Epic 16: Cash Management

### US-16.1 — Cash Reconciliation (Rep)

**As a** sales representative
**I want to** reconcile my cash box at the end of my shift
**So that** discrepancies are identified

**Acceptance Criteria:**

- `CashReconcile`: enter counted_amount + notes
- Shows expected balance from CashBox
- `CashReconciliationService::submit()` creates record with status=pending
- Shows variance (balanced / over / short)
- History: last 10 reconciliations

### US-16.2 — Approve/Flag Reconciliation (Admin)

**As an** admin/sales manager/accounts user
**I want to** review and approve or flag cash reconciliations
**So that** financial records are accurate

**Acceptance Criteria:**

- Approve: via `CashReconciliationService`
- Flag: requires reason
- Only pending records visible

---

## Epic 17: Sales Targets & Performance

### US-17.1 — Set Sales Targets (Admin)

**As an** admin/sales manager
**I want to** set sales targets for each rep by period
**So that** performance can be measured

**Acceptance Criteria:**

- `SalesTargetResource`: rep, period_start, period_end, target_amount
- Table shows actual sales (via `AttainmentService`) and attainment %

### US-17.2 — View Rep Performance (Admin)

**As a** admin/sales manager
**I want to** see today's rep performance metrics
**So that** I can manage team output

**Acceptance Criteria:**

- `RepPerformanceWidget`: total visits today, active reps vs total, total sales today
- Only visible to managers/admins/HR

---

## Epic 18: Reporting & Analytics

### US-18.1 — Dashboard KPIs

**As an** admin/manager
**I want to** see key metrics at a glance
**So that** I can make informed decisions

**Widgets:**

| Widget             | Shows                                        |
| ------------------ | -------------------------------------------- |
| SalesToday         | Today's total sales + invoice count          |
| VisitsToday        | Assigned/completed/pending visits            |
| OutstandingBalance | Unpaid invoices + 30-day overdue count       |
| LowStockAlert      | Main + van warehouse low/out-of-stock items  |
| CollectionRate     | Monthly collected vs invoiced %              |
| RepPerformance     | Active reps, total sales today               |
| OpenAlarms         | Unread alarms + critical count               |
| PendingQuotations  | Awaiting pricing + awaiting rep confirmation |

### US-18.2 — View Reports Page

**As an** admin/manager
**I want to** access detailed reports with date filters
**So that** I can analyze trends

**Acceptance Criteria:**

- `ReportsPage` with tabs: Visit Reports, Quotation Requests, Proforma Invoices, Invoices
- Date range filter on each tab

### US-18.3 — Activity Log with Reversal

**As an** admin/sales manager
**I want to** see a full audit trail and reverse transactions
**So that** I have accountability and error correction

**Acceptance Criteria:**

- `ActivityLog` page: list of all activities
- Reverse action for: invoice_created, invoice_submitted, payment_collected
- Uses `ReversalService` for compensating transactions

---

## Epic 19: Customer Maps & Geolocation

### US-19.1 — Customer Map (Admin)

**As a** admin/manager/executive
**I want to** see all geolocated customers on a map
**So that** I can understand geographic distribution

**Acceptance Criteria:**

- `CustomerMap` page: Leaflet map with customer markers
- Read-only view

---

## Epic 20: API & Tokens

### US-20.1 — Manage API Tokens (Admin)

**As an** admin
**I want to** create and manage Sanctum API tokens
**So that** external integrations can access the system

**Acceptance Criteria:**

- `ApiTokens` page: create tokens with scoped abilities (e.g., `read_products`, `read_customers`)
- Revoke tokens
- Rate-limited: 60/min per token

### US-20.2 — Read-Only API

**As an** external system
**I want to** query products and customers via API
**So that** I can integrate with other tools

**Acceptance Criteria:**

- `GET /api/v1/whoami` — token validation
- `GET /api/v1/products` — requires `read_products` ability
- `GET /api/v1/customers` — requires `read_customers` ability
- Auth: Sanctum + company context + rate limiting

---

## Epic 21: Offline & Sync

### US-21.1 — Offline Operation Queueing

**As a** sales representative
**I want to** create sales, payments, returns, and reports while offline
**So that** I'm not blocked by poor connectivity

**Acceptance Criteria:**

- All critical rep operations have `queueOffline()` methods
- Client queues to IndexedDB outbox
- Visual confirmation that operation was queued

### US-21.2 — Sync When Online

**System** replays queued operations when connectivity is restored

**Acceptance Criteria:**

- `POST /app/sync` with operations[]
- `SyncService` applies with exactly-once semantics (idempotency keys)
- Each handler (Sale, Payment, Return, VisitReport, Complaint, Expense) replays through same service layer
- Duplicate operations return cached result
- Rate-limited: 60/min per user

---

## Epic 22: Notifications

### US-22.1 — View Notifications (Rep)

**As a** sales representative
**I want to** see notifications about my activities
**So that** I'm informed of approvals, rejections, and updates

**Acceptance Criteria:**

- `Notifications` component: paginated list
- Auto-marks as read on mount
- New notification IDs highlighted

### US-22.2 — Customer Approval Notification

**System:** When admin approves/rejects a customer, the rep who added it receives a notification via `CustomerApprovalOutcome`.

### US-22.3 — Alarm Notifications

**System:** Alarms raised by out-of-stock flags, new customer submissions, etc. appear in the admin alarm register with badge count.

---

## Epic 23: Profile & Settings

### US-23.1 — Edit Profile

**As a** user (any role)
**I want to** update my name, email, phone, and password
**So that** my information is current

**Acceptance Criteria:**

- `ProfilePage`: edit name, email, phone
- Change password requires current password verification (Hash::check)

### US-23.2 — View Settings

**As a** user
**I want to** see my account and company info
**So that** I know my context

**Acceptance Criteria:**

- `SettingsPage`: shows user name, company name, current locale

---

## Epic 24: Tasks & Management

### US-24.1 — Create Tasks (Admin)

**As an** admin
**I want to** assign tasks to reps
**So that** I can track special assignments

**Acceptance Criteria:**

- `TaskResource`: assignee, customer, title, note, due_date, status (open/done)
- Tasks visible on rep's Home dashboard

### US-24.2 — Complete Tasks (Rep)

**As a** sales representative
**I want to** mark tasks as done from my dashboard
**So that** my manager sees my progress

**Acceptance Criteria:**

- One-tap completion on Home dashboard
- Status → done

---

## Summary: Story Count by Epic

| Epic                     | Stories | Key Roles              |
| ------------------------ | ------- | ---------------------- |
| 1. Auth & Session        | 3       | Rep, Admin             |
| 2. Work Day              | 3       | Rep                    |
| 3. Visit Management      | 4       | Rep, Manager           |
| 4. Sales & Invoicing     | 5       | Rep, Admin             |
| 5. Invoice Lifecycle     | 3       | Admin                  |
| 6. Payment Collection    | 3       | Rep, Admin             |
| 7. Returns               | 2       | Rep, Admin             |
| 8. Expense Management    | 2       | Rep, Admin             |
| 9. Customer Management   | 4       | Rep, Admin             |
| 10. Pricing & Quotations | 4       | Rep, Admin             |
| 11. Stock & Inventory    | 5       | Rep, Admin, Warehouse  |
| 12. Van Transfers        | 4       | Admin, Rep, Warehouse  |
| 13. Purchase Orders      | 5       | Rep, Admin, Purchasing |
| 14. Goods in Transit     | 2       | Admin, Warehouse       |
| 15. Complaints & Alarms  | 3       | Rep, Admin             |
| 16. Cash Management      | 2       | Rep, Admin             |
| 17. Sales Targets        | 2       | Admin                  |
| 18. Reporting            | 3       | Admin                  |
| 19. Maps                 | 1       | Admin                  |
| 20. API & Tokens         | 2       | Admin                  |
| 21. Offline & Sync       | 2       | System                 |
| 22. Notifications        | 3       | System, Rep            |
| 23. Profile & Settings   | 2       | All                    |
| 24. Tasks                | 2       | Admin, Rep             |
| **Total**                | **72**  |                        |
