# Jawla — Complete Client QA Test Checklist

> Every button, input, click, filter, export, and interactive element across all four roles.
> Test on **staging** first, then verify critical flows on **production**.
> Password for all test accounts: `123456789`

---

## Legend

| Symbol | Meaning                           |
| ------ | --------------------------------- |
| ✅     | Must pass before go-live          |
| ⚠️     | Nice-to-have, known limitation    |
| 🔒     | Requires specific permission/role |
| 📱     | PWA (mobile rep interface)        |
| 🖥️     | Admin panel (desktop)             |

---

## 1. LOGIN & AUTHENTICATION

### 1.1 Login Page (`/admin/login`)

- [ ] ✅ Email field accepts valid email
- [ ] ✅ Password field masks input (dots)
- [ ] ✅ Show/hide password toggle works
- [ ] ✅ "Login" button submits form
- [ ] ✅ Wrong password shows error message
- [ ] ✅ Empty email shows validation error
- [ ] ✅ Empty password shows validation error
- [ ] ✅ Non-existent email shows generic error (no user enumeration)
- [ ] ✅ Rate limiting: 5 failed attempts → temporary lockout
- [ ] ✅ Successful login redirects to `/admin/dashboard`
- [ ] ✅ Session cookie is set (httpOnly, secure in prod)
- [ ] ✅ Login page renders in Arabic (RTL) and English (LTR)
- [ ] ✅ Language toggle on login page switches locale

### 1.2 Session Management (`/admin/admin/sessions`)

- [ ] ✅ Active sessions list loads
- [ ] ✅ Current session is highlighted
- [ ] ✅ "Revoke session" button works with confirmation modal
- [ ] ✅ "Revoke all except current" works
- [ ] ✅ Revoked session immediately loses access
- [ ] ✅ Confirmation modal shows bilingual text

### 1.3 Logout

- [ ] ✅ Logout button in topbar works
- [ ] ✅ Session is destroyed (cannot go back)
- [ ] ✅ Redirects to login page

---

## 2. ADMIN PANEL — DASHBOARD

### 2.1 Main Dashboard (`/admin/dashboard`)

- [ ] ✅ Page loads without errors
- [ ] ✅ Dashboard widgets render (Visits Today, Sales Today, Rep Performance, Pending Quotations, Outstanding Balance, Open Alarms, Low Stock Alert)
- [ ] ✅ Widget cards display numbers/charts
- [ ] ✅ Drag-and-drop widget reordering works
- [ ] ✅ Widget visibility toggle (show/hide widgets)
- [ ] ✅ Widget order persists after page refresh
- [ ] ✅ Dashboard renders in Arabic (RTL)
- [ ] ✅ Dashboard renders in English (LTR)
- [ ] ✅ Numbers format correctly (currency = EGP)
- [ ] ✅ Charts render with data points

### 2.2 Dashboard Widgets

- [ ] ✅ Visits Today widget shows count
- [ ] ✅ Sales Today widget shows total
- [ ] ✅ Rep Performance widget shows bar/line chart
- [ ] ✅ Pending Quotations widget shows count
- [ ] ✅ Outstanding Balance widget shows total
- [ ] ✅ Open Alarms widget shows count with severity badge
- [ ] ✅ Low Stock Alert widget shows items below threshold

---

## 3. ADMIN PANEL — COMPANY MANAGEMENT

### 3.1 Users (`/admin/users`)

- [ ] ✅ Users list loads with columns: Name, Email, Phone, Employee Code, Role, Status
- [ ] ✅ Search by name
- [ ] ✅ Search by email
- [ ] ✅ Search by employee code
- [ ] ✅ Filter by role
- [ ] ✅ Filter by status (active/inactive)
- [ ] ✅ Sort by name (A-Z, Z-A)
- [ ] ✅ Sort by created date
- [ ] ✅ Pagination works (next/prev/page numbers)
- [ ] ✅ "Create" button opens form
- [ ] ✅ Edit button opens form with pre-filled data
- [ ] ✅ Delete button shows confirmation modal
- [ ] ✅ Bulk delete works (select multiple → delete)

### 3.2 Create/Edit User Form

- [ ] ✅ Name field (required)
- [ ] ✅ Email field (required, unique, email format validation)
- [ ] ✅ Phone field (tel format)
- [ ] ✅ Employee Code field (required, unique)
- [ ] ✅ Password field (required on create, optional on edit)
- [ ] ✅ Password hashing (never stored plain)
- [ ] ✅ Company select (required, searchable, preload)
- [ ] ✅ Primary Organization Unit select (searchable, preload)
- [ ] ✅ Role select (required)
- [ ] ✅ Status toggle (active/inactive)
- [ ] ✅ Save button submits and redirects to list
- [ ] ✅ Cancel button returns to list without saving
- [ ] ✅ Validation errors display inline

### 3.3 Companies (`/admin/companies`)

- [ ] ✅ Companies list loads
- [ ] ✅ Create company form works
- [ ] ✅ Edit company form works
- [ ] ✅ Delete company with confirmation

### 3.4 Organization Units (`/admin/organization-units`)

- [ ] ✅ List loads with hierarchy display
- [ ] ✅ Create organization unit
- [ ] ✅ Edit organization unit
- [ ] ✅ Delete with confirmation

---

## 4. ADMIN PANEL — SALES

### 4.1 Customers (`/admin/customers`)

- [ ] ✅ Customers list loads with columns: Name (AR), Name (EN), Code, Phone, Route, Status
- [ ] ✅ Search by name (Arabic)
- [ ] ✅ Search by name (English)
- [ ] ✅ Search by code
- [ ] ✅ Search by phone
- [ ] ✅ Filter by route
- [ ] ✅ Filter by status (pending/approved/rejected)
- [ ] ✅ Sort by name
- [ ] ✅ Sort by code
- [ ] ✅ Sort by created date
- [ ] ✅ Pagination works
- [ ] ✅ "Create" button opens form
- [ ] ✅ Edit button opens form
- [ ] ✅ Delete with confirmation modal

### 4.2 Create/Edit Customer Form

- [ ] ✅ Name (Arabic) field (required)
- [ ] ✅ Name (English) field (required)
- [ ] ✅ Code field (required, unique)
- [ ] ✅ Phone field (tel format)
- [ ] ✅ Address textarea
- [ ] ✅ Route select (searchable, preload)
- [ ] ✅ Status select (pending/approved/rejected)
- [ ] ✅ GPS Latitude field (numeric, -90 to 90)
- [ ] ✅ GPS Longitude field (numeric, -180 to 180)
- [ ] ✅ Map preview shows location
- [ ] ✅ Save button works
- [ ] ✅ Cancel returns to list

### 4.3 Routes (`/admin/routes`)

- [ ] ✅ Routes list loads
- [ ] ✅ Create route form: Name (AR), Name (EN), Region, Reps (multi-select), Active toggle
- [ ] ✅ Edit route form
- [ ] ✅ Delete with confirmation
- [ ] ✅ Assign multiple reps to route

### 4.4 Daily Visit Assignments (`/admin/daily-visit-assignments`)

- [ ] ✅ List loads with columns: Rep, Customer, Date, Status
- [ ] ✅ Create assignment form: Rep, Customer, Visit Date, Notes
- [ ] ✅ Edit assignment
- [ ] ✅ Delete with confirmation
- [ ] ✅ Filter by rep
- [ ] ✅ Filter by date range

### 4.5 Invoices (`/admin/invoices`)

- [ ] ✅ Invoices list loads with columns: Number, Customer, Total, Remaining, Status, ETA Status, Date
- [ ] ✅ Search by invoice number
- [ ] ✅ Search by customer name
- [ ] ✅ Filter by status (draft/submitted/partially_paid/paid/cancelled)
- [ ] ✅ Filter by ETA status (pending/submitted/rejected/accepted)
- [ ] ✅ Sort by date
- [ ] ✅ Sort by total
- [ ] ✅ Pagination works
- [ ] ✅ Edit button opens form (read-only fields: number, status, totals)
- [ ] ✅ Status badge colors correct (gray= draft, blue= submitted, yellow= partially_paid, green= paid, red= cancelled)

### 4.6 Sales Orders (`/admin/sales-orders`)

- [ ] ✅ List loads with columns: Number, Customer, Rep, Total, Status, Date
- [ ] ✅ Search by order number
- [ ] ✅ Search by customer name
- [ ] ✅ Filter by status
- [ ] ✅ Sort by date
- [ ] ✅ "Approve" action on submitted orders (requires confirmation modal)
- [ ] ✅ Confirmation modal shows bilingual description
- [ ] ✅ "Reject" action on submitted orders (requires reason textarea + confirmation)
- [ ] ✅ Rejection reason is saved and shown to rep
- [ ] ✅ Cannot create orders from admin (create button hidden)

### 4.7 Payments / Collections (`/admin/payments`)

- [ ] ✅ Payments list loads with columns: #, Customer, Invoice, Collected by, Amount, Method, Date, Status
- [ ] ✅ Search by customer name
- [ ] ✅ Search by invoice number
- [ ] ✅ Sort by date
- [ ] ✅ Sort by amount
- [ ] ✅ Status badge: Active (green) / Cancelled (red)
- [ ] ✅ "Cancel" action with confirmation modal
- [ ] ✅ Confirmation modal shows impact: "cancels payment, removes from cash box, restores outstanding balance"
- [ ] ✅ Cancelled payment status updates immediately

### 4.8 Collect Payment Page (`/admin/collect-payment`)

- [ ] ✅ Customer select loads (searchable, only active customers)
- [ ] ✅ Invoice select loads after customer selected (reactive)
- [ ] ✅ Invoice select shows only unpaid/partially-paid invoices
- [ ] ✅ Selecting invoice auto-fills remaining amount
- [ ] ✅ Amount field (numeric, required)
- [ ] ✅ Method select (cash/cheque/transfer/other)
- [ ] ✅ Notes textarea
- [ ] ✅ Submit button processes payment
- [ ] ✅ Success notification shows

### 4.9 Price Quotation Requests (`/admin/quotation-requests`)

- [ ] ✅ List loads with columns: Customer, Product, Rep, Qty, Status, Date
- [ ] ✅ Create form: Customer, Product, Rep, Qty, Status, Notes
- [ ] ✅ Edit form
- [ ] ✅ "Price" action (set price for request)
- [ ] ✅ "Confirm" action
- [ ] ✅ "Cancel" action with confirmation
- [ ] ✅ Notification sent to rep on status change

### 4.10 Proforma Invoices (`/admin/proforma-invoices`)

- [ ] ✅ List loads with columns: Number, Customer, Total, Status, Date
- [ ] ✅ Search by proforma number
- [ ] ✅ Filter by status (sent/converted_to_invoice/expired/cancelled)
- [ ] ✅ Edit form with pre-filled data
- [ ] ✅ "Convert to Invoice" action (if applicable)
- [ ] ✅ PDF export/download

### 4.11 Product Prices / Customer Price Overrides (`/admin/product-prices`)

- [ ] ✅ List loads with columns: Customer, Product, Price, Effective Date
- [ ] ✅ Create override: Customer, Product, Price, Effective Date
- [ ] ✅ Edit override
- [ ] ✅ Delete with confirmation
- [ ] ✅ Filter by customer
- [ ] ✅ Filter by product

### 4.12 Supplier Comparison (`/admin/supplier-comparison`)

- [ ] ✅ Page loads with grouped offers by product
- [ ] ✅ Side-by-side comparison displays correctly
- [ ] ✅ Price comparison shows lowest/most expensive
- [ ] ✅ Terms and expiry shown for each offer

### 4.13 Purchase Requests / Offers (`/admin/purchase-requests`)

- [ ] ✅ List loads with columns: Product, Supplier, Qty, Price, Status, Expiry
- [ ] ✅ Create form: Rep, Supplier, Product, Qty, Price, Currency, Expiry, Notes
- [ ] ✅ Edit form
- [ ] ✅ "Approve" action (creates Purchase Order)
- [ ] ✅ "Reject" action with reason
- [ ] ✅ Confirmation modal for approve/reject

### 4.14 Purchase Orders (`/admin/purchase-orders`)

- [ ] ✅ List loads (read-only, cannot create)
- [ ] ✅ Columns: Order Number, Status, Supplier, Date, Total
- [ ] ✅ View details shows line items
- [ ] ✅ Status badge colors correct
- [ ] ✅ Filter by status

---

## 5. ADMIN PANEL — INVENTORY

### 5.1 Products (`/admin/products`)

- [ ] ✅ Products list loads with columns: SKU, Barcode, Name (AR), Category, Unit, Price, Status
- [ ] ✅ Search by SKU
- [ ] ✅ Search by barcode
- [ ] ✅ Search by name (Arabic)
- [ ] ✅ Filter by category
- [ ] ✅ Filter by unit (ton/kg/piece/box/carton)
- [ ] ✅ Filter by active status
- [ ] ✅ Sort by name
- [ ] ✅ Sort by SKU
- [ ] ✅ Sort by price
- [ ] ✅ Pagination works
- [ ] ✅ "Create" button opens form
- [ ] ✅ Edit button opens form
- [ ] ✅ Delete with confirmation

### 5.2 Create/Edit Product Form

- [ ] ✅ SKU field (required, unique)
- [ ] ✅ Barcode field (optional, max 64 chars)
- [ ] ✅ Name (Arabic) field (required)
- [ ] ✅ Name (English) field (required)
- [ ] ✅ Category select (required, searchable, preload)
- [ ] ✅ Unit select (required: ton/kg/piece/box/carton)
- [ ] ✅ Packaging type select (bag/jumbo_bag/barrel/drum/tank/iso_tank/other)
- [ ] ✅ Track Batch toggle
- [ ] ✅ Track Expiry toggle
- [ ] ✅ VAT Applicable toggle (default: true)
- [ ] ✅ Selling Price field (numeric, required, visibility gated by permission)
- [ ] ✅ Cost Price field (numeric, required, visibility gated by permission)
- [ ] ✅ Max Discount field (numeric, optional)
- [ ] ✅ Valuation Method select (fifo/moving_average/standard)
- [ ] ✅ Active toggle (default: true)
- [ ] ✅ Save button works
- [ ] ✅ Cancel returns to list

### 5.3 Stock Balances (`/admin/stocks`)

- [ ] ✅ List loads with columns: Warehouse, Product, SKU, Batch, Quantity, Reserved, Available
- [ ] ✅ Search by product name
- [ ] ✅ Search by SKU
- [ ] ✅ Search by warehouse name
- [ ] ✅ Filter by warehouse
- [ ] ✅ Filter by product
- [ ] ✅ Sort by quantity
- [ ] ✅ Sort by warehouse
- [ ] ✅ Pagination works

### 5.4 Stock Import (`/admin/stock-import`)

- [ ] ✅ Page loads with warehouse select and file upload
- [ ] ✅ Warehouse select (only active warehouses for current company)
- [ ] ✅ File upload accepts CSV files
- [ ] ✅ File upload rejects non-CSV files
- [ ] ✅ File upload max size 2MB
- [ ] ✅ Helper text shows expected columns (sku, quantity, transit_quantity)
- [ ] ✅ "Preview" button runs preview
- [ ] ✅ Preview table shows parsed rows with status
- [ ] ✅ Error rows highlighted in preview
- [ ] ✅ "Confirm Import" button imports data
- [ ] ✅ Success notification shows count imported
- [ ] ✅ Import log saved to warehouse import history

### 5.5 Van Transfers (`/admin/van-transfers`)

- [ ] ✅ List loads with columns: #, From, To, Status, Items, Date
- [ ] ✅ Create form: From User, To User, In-Transit Warehouse
- [ ] ✅ Edit form
- [ ] ✅ "Receive" action (warehouse confirms receipt)
- [ ] ✅ "Cancel" action with confirmation
- [ ] ✅ Status badge colors correct
- [ ] ✅ Filter by status
- [ ] ✅ Sort by date

### 5.6 Goods in Transit (`/admin/goods-in-transit`)

- [ ] ✅ List loads with columns: Product, Quantity, From, To, Status, Date
- [ ] ✅ Create form: Product, Quantity, From Warehouse, To Warehouse, Notes
- [ ] ✅ Edit form
- [ ] ✅ "Receive" action (confirms arrival)
- [ ] ✅ "Cancel" action with confirmation
- [ ] ✅ Filter by status
- [ ] ✅ Sort by date

### 5.7 Batches (`/admin/batches`)

- [ ] ✅ List loads with columns: Batch Number, Product, Quantity, Expiry, Status
- [ ] ✅ Create batch form
- [ ] ✅ Edit batch form
- [ ] ✅ Filter by product
- [ ] ✅ Filter by expiry status

---

## 6. ADMIN PANEL — FINANCE

### 6.1 Expenses (`/admin/expenses`)

- [ ] ✅ List loads with columns: #, User, Category, Amount, Note, Date, Status
- [ ] ✅ Filter by category (fuel/maintenance/food/other)
- [ ] ✅ Sort by date
- [ ] ✅ Sort by amount
- [ ] ✅ "Cancel" action with confirmation modal
- [ ] ✅ Confirmation shows: "cancels expense, credits amount back to cash box"
- [ ] ✅ Cancelled expense status updates

### 6.2 Cash Reconciliations (`/admin/cash-reconciliations`)

- [ ] ✅ List loads with columns: Rep, Date, Expected, Counted, Variance, Status
- [ ] ✅ Cannot create from admin (created by reps via PWA)
- [ ] ✅ "Approve" action (admin reviews and approves)
- [ ] ✅ "Reject" action with reason
- [ ] ✅ Review Notes textarea (admin feedback)
- [ ] ✅ Confirmation modal for approve/reject
- [ ] ✅ Variance calculation displays correctly
- [ ] ✅ Status badge colors (pending= yellow, approved= green, rejected= red)

### 6.3 Sales Targets (`/admin/sales-targets`)

- [ ] ✅ List loads with columns: Rep, Period, Target, Attainment %, Status
- [ ] ✅ Create target: Rep, Period Start, Period End, Target Amount, Target Qty
- [ ] ✅ Edit target
- [ ] ✅ Delete with confirmation
- [ ] ✅ Attainment % calculates correctly
- [ ] ✅ Filter by rep
- [ ] ✅ Filter by period

---

## 7. ADMIN PANEL — COMPLAINTS & ALARMS

### 7.1 Complaints (`/admin/complaints`)

- [ ] ✅ List loads with columns: Customer, Rep, Type, Status, Date
- [ ] ✅ Create complaint: Customer, Rep, Type, Description, Photos
- [ ] ✅ Edit complaint
- [ ] ✅ Filter by type (non_conforming_materials/delivery_issue/quality_issue/pricing_issue/other)
- [ ] ✅ Filter by status
- [ ] ✅ Photo upload works (multiple photos)
- [ ] ✅ Photo preview displays

### 7.2 Alarms (`/admin/alarms`)

- [ ] ✅ List loads with columns: Type, Severity, Message, Status, Date
- [ ] ✅ Navigation badge shows unread count
- [ ] ✅ Badge color: red if critical alarms exist
- [ ] ✅ "Mark as read" action
- [ ] ✅ "Mark all as read" action
- [ ] ✅ Filter by severity (critical/warning/info)
- [ ] ✅ Filter by read status
- [ ] ✅ Sort by date
- [ ] ✅ Click alarm shows details

### 7.3 Return Requests (`/admin/return-requests`)

- [ ] ✅ List loads (read-only, created by reps)
- [ ] ✅ Columns: Number, Customer, Rep, Value, Status, Photos, Submitted
- [ ] ✅ "Approve" action (admin approves, stock unchanged until warehouse receipt)
- [ ] ✅ "Reject" action with reason
- [ ] ✅ Photo count displayed
- [ ] ✅ Click to view photos
- [ ] ✅ Confirmation modal for approve/reject

### 7.4 Return Records (`/admin/return-records`)

- [ ] ✅ List loads with columns: Number, Customer, User, Total, Status, Date
- [ ] ✅ "Cancel" action with confirmation
- [ ] ✅ Confirmation: "cancels return, reverses stock and customer balance"
- [ ] ✅ Status badge (draft= gray, submitted= green, cancelled= red)

---

## 8. ADMIN PANEL — REPORTS

### 8.1 Reports Page (`/admin/reports`)

- [ ] ✅ Page loads with tabs: Visit Reports, Quotations, Proformas
- [ ] ✅ Date range filter (From Date, To Date) works
- [ ] ✅ Visit Reports tab: paginated list of visit reports
- [ ] ✅ Quotations tab: paginated list of quotation requests
- [ ] ✅ Proformas tab: paginated list of proforma invoices
- [ ] ✅ Pagination works on each tab
- [ ] ✅ Export Visit Reports as CSV
- [ ] ✅ Export Quotations as CSV
- [ ] ✅ Export Proformas as CSV
- [ ] ✅ Date filter applies to all tabs
- [ ] ✅ Empty state shows when no data matches filter

---

## 9. ADMIN PANEL — MAPS & LIVE TRACKING

### 9.1 Rep Live Map (`/admin/rep-live-map`)

- [ ] ✅ Map loads with tiles (no CSP errors)
- [ ] ✅ Map container fills available height
- [ ] ✅ Map is interactive (pan, zoom, scroll)
- [ ] ✅ Rep markers show on map (if reps are active)
- [ ] ✅ Clicking rep marker shows popup with name, last seen, speed
- [ ] ✅ Auto-refresh updates positions (wire:poll)
- [ ] ✅ Empty state overlay shows when no pings
- [ ] ✅ Map renders in Arabic mode
- [ ] ✅ Map renders in English mode
- [ ] ✅ Map works on mobile viewport

### 9.2 Customer Map (`/admin/customer-map`)

- [ ] ✅ Map loads with tiles (no CSP errors)
- [ ] ✅ All customers plotted as blue markers
- [ ] ✅ Clicking customer marker shows popup with name, code, phone, route
- [ ] ✅ fitBounds zooms to show all markers
- [ ] ✅ Empty state when no customers with GPS
- [ ] ✅ Map is interactive (pan, zoom)
- [ ] ✅ Map works on mobile viewport

---

## 10. ADMIN PANEL — SETTINGS

### 10.1 API Tokens (`/admin/api-tokens`)

- [ ] ✅ Page loads with token list
- [ ] ✅ Token name input field
- [ ] ✅ Ability checkboxes (select scopes)
- [ ] ✅ "Create Token" button mints token
- [ ] ✅ Plaintext token shown ONCE after creation
- [ ] ✅ Copy token button works
- [ ] ✅ "Revoke" button with confirmation modal
- [ ] ✅ Revoked token immediately invalid
- [ ] ✅ Token list shows name, abilities, created date

### 10.2 Admin Preferences (`/admin/admin-preferences`)

- [ ] ✅ Page loads with sidebar section order
- [ ] ✅ Drag-and-drop to reorder sections
- [ ] ✅ Save button persists order
- [ ] ✅ Order applies on next page load
- [ ] ✅ New sections auto-appended (not hidden)

### 10.3 ETA E-Invoicing Settings (`/admin/eta-settings`)

- [ ] ✅ Page loads with config status
- [ ] ✅ Shows: enabled/disabled, environment, client_id presence
- [ ] ✅ Shows: certificate presence, private key presence
- [ ] ✅ Admin-only access enforced

### 10.4 Activity Log (`/admin/activity-log`)

- [ ] ✅ Page loads with paginated activity list
- [ ] ✅ Filter by activity type
- [ ] ✅ Each entry shows: user, action, model, timestamp
- [ ] ✅ Pagination works

---

## 11. ADMIN PANEL — WEBHOOKS

### 11.1 Webhook Endpoints (`/admin/webhook-endpoints`)

- [ ] ✅ List loads
- [ ] ✅ Create endpoint: URL, Secret, Events
- [ ] ✅ Edit endpoint
- [ ] ✅ Delete with confirmation
- [ ] ✅ Toggle active/inactive

### 11.2 Webhook Deliveries (`/admin/webhook-deliveries`)

- [ ] ✅ List loads with columns: Endpoint, Event, Status, Date
- [ ] ✅ Filter by status (pending/success/failed)
- [ ] ✅ Retry failed delivery

---

## 12. ADMIN PANEL — TASKS

### 12.1 Tasks (`/admin/tasks`)

- [ ] ✅ List loads with columns: Title, Assignee, Priority, Status, Due Date
- [ ] ✅ Create task: Title, Description, Assignee, Priority, Due Date
- [ ] ✅ Edit task
- [ ] ✅ "Complete" action
- [ ] ✅ "Cancel" action with confirmation
- [ ] ✅ Filter by status (pending/in_progress/completed/cancelled)
- [ ] ✅ Filter by priority (low/medium/high/critical)
- [ ] ✅ Filter by assignee
- [ ] ✅ Sort by due date
- [ ] ✅ Sort by priority

---

## 13. ADMIN PANEL — INSTALLATION & DEVICES

### 13.1 Installation Licenses (`/admin/installation-licenses`)

- [ ] ✅ List loads
- [ ] ✅ Create license form
- [ ] ✅ License status display

### 13.2 Devices (`/admin/devices`)

- [ ] ✅ List loads with columns: User, Device, Last Seen, Status
- [ ] ✅ Filter by status
- [ ] ✅ Sort by last seen

---

## 14. ADMIN PANEL — NAVIGATION & GLOBAL

### 14.1 Sidebar Navigation

- [ ] ✅ All menu groups expand/collapse
- [ ] ✅ All menu items navigate to correct pages
- [ ] ✅ Active page highlighted in sidebar
- [ ] ✅ Sidebar sections match user permissions (items they can't access are hidden)
- [ ] ✅ Sidebar order matches Admin Preferences setting
- [ ] ✅ Sidebar works in RTL mode (mirrored)
- [ ] ✅ Sidebar works on mobile (hamburger menu)

### 14.2 Topbar

- [ ] ✅ User avatar/name displays
- [ ] ✅ Language dropdown (English/Arabic) works
- [ ] ✅ Notification bell shows count
- [ ] ✅ Logout button works
- [ ] ✅ Breadcrumbs show current location

### 14.3 Pagination (All List Pages)

- [ ] ✅ "Next" page works
- [ ] ✅ "Previous" page works
- [ ] ✅ Page number links work
- [ ] ✅ "Per page" selector works (if available)
- [ ] ✅ Total count displays

### 14.4 Search & Filters (All List Pages)

- [ ] ✅ Search input clears on X click
- [ ] ✅ Filter dropdowns show options
- [ ] ✅ "Clear filters" button works
- [ ] ✅ Active filter badges display
- [ ] ✅ Combined search + filter works

### 14.5 Forms (All Create/Edit Forms)

- [ ] ✅ Required fields show asterisk
- [ ] ✅ Validation errors display inline
- [ ] ✅ "Save" button disabled during submission
- [ ] ✅ "Cancel" returns to list
- [ ] ✅ Form scrolls to first error on validation failure
- [ ] ✅ Select fields show "No results" for empty searches

### 14.6 Confirmation Modals (All Destructive Actions)

- [ ] ✅ Modal appears before delete/cancel/approve
- [ ] ✅ Modal shows bilingual description of consequence
- [ ] ✅ "Confirm" button executes action
- [ ] ✅ "Cancel" closes modal without action
- [ ] ✅ Modal closes on ESC key
- [ ] ✅ Modal closes on backdrop click

### 14.7 Notifications

- [ ] ✅ Success notification shows on save
- [ ] ✅ Error notification shows on failure
- [ ] ✅ Notification auto-dismisses after timeout
- [ ] ✅ Notification X button dismisses immediately

---

## 15. PWA — REP MOBILE INTERFACE

> Test on a mobile device or mobile viewport (375px width).
> Login as `rep@jawla.test` → redirects to `/app`

### 15.1 Home Page (`/app`)

- [ ] ✅ Page loads with today's summary
- [ ] ✅ Cash box balance displays
- [ ] ✅ Van stock count displays
- [ ] ✅ Today's assigned customers count displays
- [ ] ✅ Quick action buttons visible (Start Jawla, etc.)
- [ ] ✅ Bottom navigation bar works (Home/Customers/More)
- [ ] ✅ RTL layout correct
- [ ] ✅ All text in Arabic

### 15.2 Start Jawla (Day Start)

- [ ] ✅ "Start Jawla" button visible on home
- [ ] ✅ Click starts the day (creates jawla record)
- [ ] ✅ GPS location captured on start
- [ ] ✅ Button disappears after jawla started
- [ ] ✅ "End Jawla" button appears after start

### 15.3 Today's Customers (`/app/customers`)

- [ ] ✅ Customer list loads with assigned customers
- [ ] ✅ Each customer shows: Name, Code, Last Visit
- [ ] ✅ Search by customer name
- [ ] ✅ Tap customer → opens customer detail/visit flow
- [ ] ✅ Customer GPS location shows on map (if available)
- [ ] ✅ "Navigate to customer" opens device maps app

### 15.4 Customer Detail / Visit Flow

- [ ] ✅ Customer info displays (name, code, phone, address)
- [ ] ✅ "Start Visit" button works
- [ ] ✅ GPS check-in recorded on visit start
- [ ] ✅ Visit timer/elapsed time shows
- [ ] ✅ "End Visit" button works
- [ ] ✅ GPS check-out recorded on visit end

### 15.5 Sales Flow (`/app/sales`)

- [ ] ✅ Product list loads from van stock
- [ ] ✅ Search/filter products
- [ ] ✅ Tap product → quantity input
- [ ] ✅ Quantity input accepts numbers
- [ ] ✅ Price displays (respecting customer price overrides)
- [ ] ✅ VAT calculates correctly
- [ ] ✅ Line total calculates correctly
- [ ] ✅ "Add to cart" button works
- [ ] ✅ Cart shows items with quantities
- [ ] ✅ Edit cart item (change quantity)
- [ ] ✅ Remove cart item
- [ ] ✅ Cart total calculates correctly
- [ ] ✅ "Submit Order" button creates sales order
- [ ] ✅ Order confirmation shows

### 15.6 Create Sales Order (`/app/create-sales-order`)

- [ ] ✅ Customer pre-selected from visit
- [ ] ✅ Product search works
- [ ] ✅ Add products with quantities
- [ ] ✅ Discount field (if applicable)
- [ ] ✅ Total calculates with VAT
- [ ] ✅ "Submit" creates order
- [ ] ✅ Success confirmation shows

### 15.7 Quotation Flow (`/app/quotation`)

- [ ] ✅ Product search works
- [ ] ✅ Customer selected
- [ ] ✅ Quantity and price inputs
- [ ] ✅ "Submit Quotation" creates request
- [ ] ✅ Success confirmation

### 15.8 Collect Payment (`/app/collect-payment`)

- [ ] ✅ Invoice list shows unpaid invoices for customer
- [ ] ✅ Select invoice → remaining amount fills
- [ ] ✅ Amount input (can pay partial)
- [ ] ✅ Method select (cash/cheque/transfer/other)
- [ ] ✅ Notes textarea
- [ ] ✅ "Submit Payment" records payment
- [ ] ✅ Receipt confirmation shows

### 15.9 Log Expense (`/app/log-expense`)

- [ ] ✅ Category select (fuel/maintenance/food/other)
- [ ] ✅ Amount input (numeric)
- [ ] ✅ Notes textarea
- [ ] ✅ "Submit" records expense
- [ ] ✅ Success confirmation

### 15.10 Log Return (`/app/log-return`)

- [ ] ✅ Customer select
- [ ] ✅ Product select
- [ ] ✅ Quantity input
- [ ] ✅ Reason textarea
- [ ] ✅ Photo upload (camera or gallery)
- [ ] ✅ "Submit" creates return request
- [ ] ✅ Success confirmation

### 15.11 Log Complaint (`/app/log-complaint`)

- [ ] ✅ Customer select
- [ ] ✅ Complaint type select
- [ ] ✅ Description textarea
- [ ] ✅ Photo upload
- [ ] ✅ "Submit" creates complaint
- [ ] ✅ Success confirmation

### 15.12 Photo Capture (`/app/photo-capture`)

- [ ] ✅ Camera button triggers device camera
- [ ] ✅ Photo captures successfully
- [ ] ✅ Photo preview shows
- [ ] ✅ "Retake" button works
- [ ] ✅ "Use Photo" confirms
- [ ] ✅ Photo attached to visit/order
- [ ] ✅ Works with front and rear camera

### 15.13 Van Transfers (`/app/van-transfers`)

- [ ] ✅ Transfer list loads
- [ ] ✅ "Initiate Transfer" form works
- [ ] ✅ Select recipient user
- [ ] ✅ Add items with quantities
- [ ] ✅ "Submit Transfer" creates transfer
- [ ] ✅ "Receive Transfer" (when receiving)
- [ ] ✅ Quantity confirmation on receive

### 15.14 Stock Search (`/app/stock-search`)

- [ ] ✅ Search by product name
- [ ] ✅ Search by SKU
- [ ] ✅ Search by barcode
- [ ] ✅ Results show product + quantity in van
- [ ] ✅ Tap product shows details

### 15.15 Tasks (`/app/tasks`)

- [ ] ✅ Task list loads
- [ ] ✅ Each task shows: title, priority, due date
- [ ] ✅ "Complete" button works
- [ ] ✅ Priority badge colors correct

### 15.16 Orders (`/app/orders`)

- [ ] ✅ Order list loads (orders created by this rep)
- [ ] ✅ Each order shows: number, customer, total, status
- [ ] ✅ Tap order → view details
- [ ] ✅ Status badge colors correct
- [ ] ✅ Filter by status

### 15.17 Notifications (`/app/notifications`)

- [ ] ✅ Notification list loads
- [ ] ✅ Each notification shows: title, message, time
- [ ] ✅ Tap notification → navigates to relevant page
- [ ] ✅ "Mark as read" works
- [ ] ✅ "Mark all as read" works
- [ ] ✅ Unread badge count

### 15.18 Cash Reconciliation (`/app/cash-reconcile`)

- [ ] ✅ Expected amount displays
- [ ] ✅ Counted amount input
- [ ] ✅ Variance auto-calculates
- [ ] ✅ Notes textarea
- [ ] ✅ "Submit" creates reconciliation
- [ ] ✅ Success confirmation

### 15.19 Profile (`/app/profile`)

- [ ] ✅ User info displays (name, email, phone, role)
- [ ] ✅ Change password form works
- [ ] ✅ Current password required
- [ ] ✅ New password + confirmation match
- [ ] ✅ Save button works

### 15.20 Settings (`/app/settings`)

- [ ] ✅ Language toggle (AR/EN) works
- [ ] ✅ Notification preferences toggle
- [ ] ✅ Theme toggle (if available)
- [ ] ✅ About/version info displays

### 15.21 Sync Queue (`/app/sync`)

- [ ] ✅ Pending items list displays
- [ ] ✅ Sync button triggers sync
- [ ] ✅ Sync status updates (pending/synced/error)
- [ ] ✅ Retry failed items
- [ ] ✅ Offline indicator shows when no connection

### 15.22 Device Registration

- [ ] ✅ First visit triggers device registration
- [ ] ✅ Device name input
- [ ] ✅ Register button works
- [ ] ✅ Device appears in admin devices list

### 15.23 More Page (`/app/more`)

- [ ] ✅ Menu items display (Tasks, Orders, Returns, Complaints, Expenses, Stock Search, Van Transfers, Settings, Profile)
- [ ] ✅ Each item navigates to correct page
- [ ] ✅ Cash box balance displays
- [ ] ✅ Van stock count displays

---

## 16. PWA — OFFLINE & PWA FEATURES

### 16.1 Service Worker

- [ ] ✅ Service worker registers (`/sw.js`)
- [ ] ✅ Assets cached on first load
- [ ] ✅ Offline page shows when no connection
- [ ] ✅ Background sync queues requests when offline
- [ ] ✅ Queued requests sync when back online

### 16.2 PWA Install

- [ ] ✅ "Install App" prompt appears (on supported browsers)
- [ ] ✅ App installs to home screen
- [ ] ✅ App icon shows on home screen
- [ ] ✅ App opens in standalone mode (no browser chrome)

### 16.3 Offline Mode

- [ ] ✅ Visit/customer data available offline
- [ ] ✅ Sales can be created offline (queued)
- [ ] ✅ Payments can be recorded offline (queued)
- [ ] ✅ Photos can be taken offline (stored locally)
- [ ] ✅ Sync indicator shows pending items count
- [ ] ✅ Data syncs when connection restored

### 16.4 Push Notifications

- [ ] ✅ Push notification permission requested
- [ ] ✅ Notification received when new task assigned
- [ ] ✅ Notification received when order approved/rejected
- [ ] ✅ Tapping notification opens app to relevant page

---

## 17. CROSS-CUTTING CONCERNS

### 17.1 RTL/LTR Language Switching

- [ ] ✅ Arabic mode: all text in Arabic, layout RTL
- [ ] ✅ English mode: all text in English, layout LTR
- [ ] ✅ Language toggle works from any page
- [ ] ✅ Language persists across page loads
- [ ] ✅ Sidebar mirrors in RTL
- [ ] ✅ Tables mirror in RTL
- [ ] ✅ Forms mirror in RTL
- [ ] ✅ Maps work in both directions
- [ ] ✅ Numbers display correctly in both modes
- [ ] ✅ Dates display correctly in both modes
- [ ] ✅ Currency (EGP) displays correctly in both modes

### 17.2 Bilingual Content

- [ ] ✅ Customer names show Arabic and English
- [ ] ✅ Product names show Arabic and English
- [ ] ✅ Route names show Arabic and English
- [ ] ✅ Validation messages bilingual
- [ ] ✅ Confirmation modal text bilingual
- [ ] ✅ Notification text bilingual
- [ ] ✅ Error messages bilingual

### 17.3 Responsive Design

- [ ] ✅ Admin panel: works at 1920px (desktop)
- [ ] ✅ Admin panel: works at 1024px (tablet landscape)
- [ ] ✅ Admin panel: works at 768px (tablet portrait)
- [ ] ✅ PWA: works at 375px (mobile)
- [ ] ✅ PWA: works at 414px (large mobile)
- [ ] ✅ PWA: works at 768px (tablet)
- [ ] ✅ Sidebar collapses to hamburger on mobile
- [ ] ✅ Tables scroll horizontally on small screens
- [ ] ✅ Forms stack vertically on small screens
- [ ] ✅ Modals fit within viewport

### 17.4 Accessibility

- [ ] ✅ All form inputs have labels
- [ ] ✅ Buttons have accessible text
- [ ] ✅ Images have alt text
- [ ] ✅ Color contrast meets WCAG AA
- [ ] ✅ Keyboard navigation works
- [ ] ✅ Focus states visible
- [ ] ✅ Screen reader announces page changes

### 17.5 Security

- [ ] ✅ CSRF token present on all forms
- [ ] ✅ XSS: user input escaped in output
- [ ] ✅ SQL injection: parameterized queries used
- [ ] ✅ Passwords never logged
- [ ] ✅ API tokens never exposed after creation
- [ ] ✅ Session invalidated on logout
- [ ] ✅ Rate limiting on login (5/min)
- [ ] ✅ Rate limiting on POST routes (60/min)

### 17.6 Performance

- [ ] ✅ Dashboard loads < 3 seconds
- [ ] ✅ List pages load < 2 seconds
- [ ] ✅ Form submission < 2 seconds
- [ ] ✅ Map loads < 3 seconds
- [ ] ✅ PWA first paint < 2 seconds
- [ ] ✅ Images optimized (no oversized uploads)
- [ ] ✅ No N+1 queries (page loads don't slow with data growth)

### 17.7 Browser Compatibility

- [ ] ✅ Chrome (latest)
- [ ] ✅ Firefox (latest)
- [ ] ✅ Safari (latest)
- [ ] ✅ Edge (latest)
- [ ] ✅ Samsung Internet (Android)
- [ ] ✅ iOS Safari (PWA install)

---

## 18. PRINT & EXPORT

### 18.1 PDF Generation

- [ ] ✅ Invoice PDF generates correctly
- [ ] ✅ Proforma PDF generates correctly
- [ ] ✅ Report PDF generates correctly
- [ ] ✅ PDF shows company logo
- [ ] ✅ PDF shows bilingual content
- [ ] ✅ PDF shows VAT breakdown
- [ ] ✅ PDF shows totals correctly
- [ ] ✅ Arabic text renders in PDF (no missing glyphs)
- [ ] ✅ Signature embedded in PDF (if applicable)

### 18.2 CSV Export

- [ ] ✅ Visit reports export as CSV
- [ ] ✅ Quotation requests export as CSV
- [ ] ✅ Proforma invoices export as CSV
- [ ] ✅ CSV opens correctly in Excel
- [ ] ✅ CSV contains correct headers
- [ ] ✅ CSV data matches filtered view

### 18.3 Print

- [ ] ✅ Invoice print layout correct
- [ ] ✅ Print-friendly CSS applied
- [ ] ✅ Header/footer hidden in print
- [ ] ✅ Page breaks at correct positions

---

## 19. ERROR HANDLING

### 19.1 Validation Errors

- [ ] ✅ Required field empty → inline error message
- [ ] ✅ Invalid email format → inline error
- [ ] ✅ Duplicate unique field → inline error
- [ ] ✅ Numeric field with text → inline error
- [ ] ✅ Min/max value violations → inline error
- [ ] ✅ Form scrolls to first error
- [ ] ✅ Error message bilingual

### 19.2 404 Page

- [ ] ✅ Navigating to non-existent URL shows 404
- [ ] ✅ 404 page is styled (not raw error)
- [ ] ✅ 404 page has "Go Home" link

### 19.3 Server Errors

- [ ] ✅ 500 error shows styled error page (not stack trace)
- [ ] ✅ Error details logged (not exposed to user)
- [ ] ✅ Sentry captures error (if configured)

---

## 20. ENVIRONMENT-SPECIFIC

### 20.1 Staging (`jawla-staging-staging.up.railway.app`)

- [ ] ✅ All test accounts login successfully
- [ ] ✅ Demo data loads (if JAWLA_MODE=demo)
- [ ] ✅ No sensitive data in browser console
- [ ] ✅ No debug mode visible
- [ ] ✅ HTTPS enforced
- [ ] ✅ HSTS header present

### 20.2 Production (`jawla-production.up.railway.app`)

- [ ] ✅ Admin login works
- [ ] ✅ Dashboard loads
- [ ] ✅ Customers list loads
- [ ] ✅ Products list loads
- [ ] ✅ Invoices list loads
- [ ] ✅ No debug output
- [ ] ✅ No .env exposure
- [ ] ✅ No stack traces visible
- [ ] ✅ Sentry captures errors
- [ ] ✅ Backup system operational
- [ ] ✅ HTTPS enforced
- [ ] ✅ All security headers present

---

## QUICK REFERENCE — TEST ACCOUNTS

| Role          | Email                  | Password    | Access                   |
| ------------- | ---------------------- | ----------- | ------------------------ |
| Admin         | `admin@jawla.test`     | `123456789` | Full admin panel         |
| Sales Manager | `sales@jawla.test`     | `123456789` | Sales section + reports  |
| Sales Rep     | `rep@jawla.test`       | `123456789` | PWA mobile app at `/app` |
| Warehouse     | `warehouse@jawla.test` | `123456789` | Inventory section        |

---

## TOTAL: ~450+ test items

**Estimated testing time:** 8-12 hours for complete coverage
**Critical path (must-pass):** Login, Dashboard, Customers, Products, Invoices, PWA Start Jawla, Sales Flow, Collect Payment, Offline/Sync = ~2 hours
