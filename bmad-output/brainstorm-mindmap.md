# Brainstorm: UI Control Audit Mind Map — Rep PWA Pages

> **Scope**: All 13 Rep PWA pages — existing controls vs missing controls that SHOULD exist.
> **Technique**: Mind Mapping (existing → missing, organized by page)
> **Cross-cutting gaps** listed at the end.

---

## 1. Home

### Existing Controls
- Logo image (brand identity)
- Welcome heading with user name
- Visit count subtitle
- Avatar circle (first letter of name)
- Pending visits stat card
- Completed visits stat card
- "Today's Plan" section heading
- Empty-state block (SVG + "No visits" message)
- Clickable visit cards (customer name, status badge, address, maps link)
- Status badges per visit (pending/done/missed)
- Google Maps directions link per visit
- Task cards (title, note, customer, due date)
- "Done" completion button per task
- "Start Work" full-width primary CTA
- Tab bar (Home / Customers / Stock / More)

### Missing Controls
- **End Work / Clock-out button** — "Start Work" exists but no symmetric "End Work" action to close the workday and trigger daily summary
- **Skeleton loading state** — No placeholder cards while `$todayVisits` and `$openTasks` load; page shows blank on slow connections
- **Pull-to-refresh** — List pages benefit from swipe-down refresh; rep could pull to get latest visit/task updates
- **Badge count on tab bar** — No unread notifications or pending action count on any tab icon
- **Notification bell / center** — No way to see system notifications (task assignments, manager messages, low-stock alerts)
- **Today's summary card** — No at-a-glance summary (total expected revenue, completed vs remaining visits, mileage)
- **Real-time indicator** — No connection status dot or offline queue count
- **Recent activity feed** — No mini-log of last actions taken today
- **Quick actions FAB** — No floating button for fastest access to Create Invoice / Log Expense from Home
- **Date picker** — Cannot view or plan visits for other days; locked to "today"
- **Route map view** — No map visualization of today's planned route
- **Task badge / count** — Open tasks are listed but no count indicator on section heading
- **Swipe actions on visit cards** — No swipe-left to mark visited / swipe-right to skip
- **Draft auto-save indicator** — No indicator showing visit report drafts saved offline
- **Error boundary / retry** — If visit/task fetch fails, no retry mechanism shown

---

## 2. Visit Flow

### Existing Controls
- Page header with customer name and address
- Offline banner (conditional warning when offline)
- 3-step stepper (Scheduled → Arrived → Report)
- Step dots with numbered/checkmark indicators
- Connector lines between steps
- Customer info card (name, address, distance)
- In-range / out-of-range status text with distance
- GPS error message card
- Out-of-range warning card with "Confirm Anyway" button
- Arrived confirmation card (green checkmark)
- Waiting for GPS card
- Summary textarea (required)
- Customer feedback textarea (optional)
- Action taken textarea (optional)
- Follow-up checkbox toggle
- Follow-up note textarea (conditional)
- Signature canvas (340×140, touch/mouse drawing)
- "Clear" button for signature
- "Submit Report" button with loading state
- Done success screen with "Back Home" link

### Missing Controls
- **Skeleton loading state** — No placeholder while GPS fix is acquired or visit data loads
- **Confirmation modal before submit** — Report submission is final and financial; should show summary + "Are you sure?" with exact consequences
- **Photo capture** — No camera button to attach visit photos (proof of presence, product display photos, shelf photos)
- **Voice note / dictation** — No audio input for summary; rep may be driving and unable to type
- **GPS accuracy indicator** — Shows distance but not GPS signal strength or accuracy radius
- **Retry GPS button** — If GPS fails, no explicit "Retry" action (only "Confirm Anyway")
- **Undo submit** — After report is submitted, no time-window to undo or edit
- **Progress indicator during submit** — Only `wire:loading` disables button; no progress bar or step indicator
- **Pull-to-refresh** — No way to refresh visit data if status changed server-side
- **Error boundary / retry** — GPS failure shows message but no structured error boundary with retry
- **Offline queue indicator** — Offline banner exists but no queue count or "will sync when online" message
- **Draft auto-save indicator** — localStorage draft exists but no visual indicator that draft is saved
- **Timestamp display** — No display of when arrival was confirmed or report was submitted
- **Cancel / go back confirmation** — No prompt before navigating away with unsaved changes
- **Previous visit history** — No link to see past visits to this customer
- **Customer balance card** — No display of customer's outstanding balance during visit
- **Haptic feedback** — No vibration on GPS confirm, submit success, or error
- **Map preview** — Shows address text but no embedded mini-map of customer location

---

## 3. Customers

### Existing Controls
- Page header with icon
- Live search input (name/phone/code)
- "Add Customer" primary button link
- Customer cards (name, code, phone, address)
- Google Maps directions link per customer
- Empty state card ("No customers found")
- Tab bar

### Missing Controls
- **Pull-to-refresh** — Swipe-down to refresh customer list
- **Infinite scroll / pagination UX** — Has `paginate(30)` but no visible "Load More" or infinite scroll indicator
- **Sort controls** — No way to sort by name, code, last visit, balance
- **Filter controls** — No filter by status (active/inactive), by route, by last visit date
- **Swipe actions** — No swipe-left to quick-call or swipe-right to navigate
- **Long-press context menu** — No long-press for quick actions (call, WhatsApp, view history, directions)
- **Customer balance display** — Cards show name/code/phone/address but NOT outstanding balance
- **Last visit date** — No indicator of when customer was last visited
- **Status badges** — No visual indicator of customer status (active, inactive, new, VIP)
- **Empty search state** — Shows generic "No customers found" but no suggestion to add customer
- **Search with suggestions / autocomplete** — Live search works but no dropdown suggestions
- **Badge count** — No count of total customers shown
- **Batch select / bulk actions** — No multi-select for bulk operations (e.g., assign route)
- **Offline indicator per customer** — No indicator if customer data may be stale
- **Quick actions FAB** — No floating button; must scroll to "Add Customer" button at top

---

## 4. Add Customer

### Existing Controls
- Page header with icon
- Success toast message
- Form with submit binding
- Name (Arabic) input with validation
- Name (English) input with validation
- Phone input (tel, inputmode=tel) with validation
- Address textarea
- Latitude input (auto-filled via geolocation)
- Longitude input (auto-filled via geolocation)
- Submit button with loading state
- Tab bar

### Missing Controls
- **Skeleton loading state** — No placeholder while GPS auto-fill is resolving
- **GPS accuracy indicator** — Coordinates auto-fill but no indication of accuracy or "re-fetch GPS" button
- **Map preview / pin drop** — No visual map to verify or manually adjust GPS coordinates
- **Phone format validation feedback** — No real-time format hint (e.g., "+966 5X XXX XXXX")
- **Duplicate check** — No warning if customer with same phone/name already exists
- **Confirmation modal** — No "Confirm adding customer?" before submission
- **Draft auto-save indicator** — No indicator if form data is saved locally
- **Cancel without save prompt** — No warning when navigating away with unsaved form data
- **Photo capture** — No ability to attach a business card or storefront photo
- **Notes / additional info field** — No free-text field for extra customer context
- **Route assignment** — No way to assign customer to a delivery route during creation
- **Success screen (not just toast)** — Success is a toast that disappears; should be a persistent success screen with actions (View Customer / Add Another)
- **Undo** — No undo after customer creation within a time window
- **Currency / payment terms** — No fields for default payment terms or currency preference
- **Haptic feedback** — No vibration on successful creation

---

## 5. Stock Search

### Existing Controls
- Page header with icon
- Live search input (300ms debounce, SKU/name)
- Prompt card ("Search for a product" with icon)
- No results card
- Product cards (name, SKU, price)
- Stock breakdown per warehouse (color-coded: green > 5, amber ≤ 5)
- "Out of stock" text
- Tab bar

### Missing Controls
- **Pull-to-refresh** — Swipe-down to refresh stock data
- **Infinite scroll / load more** — Results capped at `limit(20)` with no way to see more
- **Sort controls** — No sort by name, SKU, price, or stock quantity
- **Filter controls** — No filter by category, warehouse, or in-stock-only toggle
- **Search suggestions / autocomplete** — No dropdown suggestions as user types
- **Barcode / QR scanner** — No camera-based barcode scan for quick product lookup
- **Stock history** — No way to see recent stock movements for a product
- **Unit of measure display** — No indication of unit (piece, case, kg)
- **Price history** — Only shows current price; no historical price context
- **Min/max stock levels** — No visual indicator of whether stock is below reorder point
- **Offline indicator** — No indicator if stock data may be stale (fetched when online)
- **Skeleton loading state** — No placeholder cards during search
- **Empty state illustration** — "No results" is text-only; no illustration or suggestion
- **Batch select** — No way to select multiple products for a bulk action (e.g., add all to cart)
- **Share / export** — No way to share stock list or export to PDF/CSV

---

## 6. Sales Flow

### Existing Controls
- Page header with icon
- 2-step stepper (Cart → Done)
- Error message card
- Customer search input (live, 300ms debounce)
- Customer result buttons (name + phone)
- Selected customer display with "Change" button
- Product search input (live, 300ms debounce)
- Product result buttons (name, SKU, price)
- Cart section heading with count
- Cart item cards (name, SKU, quantity, price, line total)
- Remove item (X) button per cart item
- Quantity input (number, decimal, min=0.001)
- Price input (number, decimal, min=0)
- Line total display
- Submit button (conditional on customer + cart)
- Success screen with checkmark
- "View PDF" link
- "New Invoice" link
- "Home" link
- Tab bar

### Missing Controls
- **Confirmation modal before submit** — Invoice creation is a financial commitment; MUST show order summary + "Confirm Invoice?" with exact amounts
- **Van stock validation / warning** — No check if van has enough stock for ordered quantities; should show warning or block if insufficient
- **Discount / line-item discount input** — No way to apply discounts to items or the overall invoice
- **Tax display / toggle** — No tax calculation or tax-inclusive/exclusive toggle
- **Customer balance display** — No indication of customer's outstanding balance when selecting them
- **Credit limit warning** — No warning if invoice would exceed customer's credit limit
- **Skeleton loading state** — No placeholder while customer/product search results load
- **Search suggestions / autocomplete** — No dropdown suggestions
- **Pull-to-refresh** — No refresh for product prices or stock data
- **Undo remove item** — No undo toast when removing a cart item
- **Draft auto-save indicator** — No indicator that cart is saved locally
- **Quantity keypad** — No custom number pad for faster quantity entry on mobile
- **Swipe to remove** — No swipe-left on cart item to remove
- **Empty cart illustration** — Empty cart state not explicitly handled with illustration
- **Delivery date / notes** — No field for delivery date or special instructions
- **Route assignment** — No way to assign invoice to a delivery route
- **Invoice preview** — No preview before final submission
- **Offline queue indicator** — If offline, should queue and show pending count
- **Haptic feedback** — No vibration on add-to-cart, submit success, or error
- **Error boundary / retry** — If submit fails, only shows error message; no retry button

---

## 7. Quotation Flow

### Existing Controls
- Page header with icon
- Error message card
- Success message card
- Pending quotations list with heading
- Quotation item buttons (product name, customer, quantity, base price)
- Empty state card
- Detail card (product, customer, quantity, base price, price range floor/ceiling)
- Negotiated price input (number, decimal)
- "Confirm Price" button
- "Create Proforma" button
- "Back" button (to list)
- Proforma success card (proforma number + total)
- WhatsApp share link (green styled, external link)
- "Back" button (from proforma success)
- Tab bar
- Pagination (paginate(20))

### Missing Controls
- **Confirmation modal before confirm price** — Price confirmation is binding; should show "Confirm price X for Y?" 
- **Confirmation modal before create proforma** — Proforma creation should have confirmation with summary
- **Search / filter on quotation list** — No way to search or filter by customer, product, date, or status
- **Sort controls** — No sort by date, price, customer
- **Pull-to-refresh** — No swipe-down to refresh quotation list
- **Status badges** — No visual indicator of quotation status (pending, priced, proforma created, expired)
- **Date display** — No indication of when quotation was created or when it expires
- **Price history / negotiation log** — No history of price changes or negotiation messages
- **Reject / decline button** — No way to reject or decline a quotation
- **Counter-offer** — Only one price input; no way to propose a counter-offer
- **Bulk actions** — No multi-select for batch pricing or batch proforma creation
- **Skeleton loading state** — No placeholder during list load
- **Empty state illustration** — Text-only empty state
- **WhatsApp message preview** — No preview of the WhatsApp message before sending
- **Share alternatives** — WhatsApp only; no SMS, email, or PDF share option
- **Copy-to-clipboard** — No quick copy of quotation details
- **Undo price confirm** — No time-window to undo price confirmation
- **Expiry countdown** — No timer or badge showing days until quotation expires
- **Haptic feedback** — No vibration on confirm or create

---

## 8. Collect Payment

### Existing Controls
- Page header with icon
- Success screen with checkmark
- "View Receipt" link (PDF)
- "Collect Another" reset button
- Customer dropdown select
- Invoice dropdown select (shows invoice number + remaining amount)
- Amount input (number, decimal, auto-filled)
- Payment method select (Cash / Cheque / Transfer / Other)
- Notes textarea (optional)
- Submit button with loading state

### Missing Controls
- **Confirmation modal before submit** — Financial action; MUST show "Collect X from Y via Z?" with exact amount
- **Customer balance display** — No display of total outstanding balance when customer is selected
- **Partial payment indicator** — No visual when collected amount < remaining amount
- **Tab bar** — Missing entirely; rep has no bottom navigation on this page
- **Searchable dropdown** — `<select>` with potentially many customers; no search/filter
- **Receipt photo capture** — No camera to photograph cheque or cash receipt
- **Date picker** — No way to backdate or future-date a payment
- **Multi-invoice payment** — Can only pay one invoice at a time; no batch payment
- **Undo payment** — No time-window to undo accidental payment
- **Skeleton loading state** — No placeholder while customer/invoice lists load
- **Pull-to-refresh** — No refresh for invoice data
- **Offline queue indicator** — No indicator if payment will be queued
- **Error boundary / retry** — If submit fails, no retry mechanism
- **Running total** — No running total if collecting from multiple invoices
- **Payment receipt preview** — No preview before finalizing
- **Haptic feedback** — No vibration on successful payment
- **Last payment reference** — No display of recent payments for this customer

---

## 9. Log Return

### Existing Controls
- Page header with icon
- Success screen with checkmark
- "Log Return" reset button (from success)
- Customer dropdown select
- Reason textarea (optional)
- Items card ("Items Returned" heading)
- Per-item product dropdown select (with stock count in parens)
- Per-item quantity input (number, decimal)
- Per-item unit price input (number, decimal)
- "Remove" item button (when > 1 items)
- "Add Item" full-width outline button
- Submit button with loading state

### Missing Controls
- **Confirmation modal before submit** — Returns affect stock and finances; must show "Return X items from Y?" with summary
- **Tab bar** — Missing entirely; no bottom navigation
- **Searchable dropdown** — Product select with potentially many items; no search
- **Searchable customer dropdown** — No search within customer select
- **Photo capture** — No camera to photograph returned items (condition proof)
- **Invoice link** — No way to link return to specific original invoice
- **Return reason dropdown** — Free text only; should have structured reasons (damaged, expired, wrong item, etc.) as selectable options
- **Stock impact preview** — No preview showing how return affects stock levels
- **Undo return** — No time-window to undo
- **Skeleton loading state** — No placeholder while customer/product lists load
- **Pull-to-refresh** — No refresh for product stock data
- **Offline queue indicator** — No indicator if return will be queued
- **Error boundary / retry** — If submit fails, no retry mechanism
- **Draft auto-save indicator** — No indicator if return form is saved locally
- **Batch add from invoice** — No way to auto-populate items from a specific invoice
- **Haptic feedback** — No vibration on submit success

---

## 10. Log Expense

### Existing Controls
- Page header with icon
- Cash box balance banner (amber card with `$cashBoxBalance`)
- Success screen with checkmark
- "Log Expense" reset button
- Category select (Fuel / Maintenance / Food / Other)
- Amount input (number, decimal)
- Note textarea (optional)
- Submit button with loading state

### Missing Controls
- **Confirmation modal before submit** — Expense affects cash box; must confirm "Log X expense under Y?"
- **Tab bar** — Missing entirely; no bottom navigation
- **Expense receipt photo** — No camera to photograph receipt/invoice
- **Date picker** — No way to backdate expenses
- **Custom category input** — Only 4 fixed categories; no "Other" text input for custom category
- **Recurring expense flag** — No way to mark as recurring (e.g., daily fuel)
- **Cash box warning** — Balance shown but no warning when expense would exceed balance
- **Undo expense** — No time-window to undo
- **Skeleton loading state** — No placeholder while cash balance loads
- **Pull-to-refresh** — No refresh for cash balance
- **Expense history** — No view of today's logged expenses
- **Offline queue indicator** — No indicator if expense will be queued
- **Error boundary / retry** — If submit fails, no retry mechanism
- **Running total** — No running total of today's expenses
- **Budget indicator** — No visual of daily/weekly budget remaining
- **Haptic feedback** — No vibration on submit success

---

## 11. Log Complaint

### Existing Controls
- Page header with icon
- Success toast message
- Customer dropdown select
- Complaint type select (Non-conforming materials / Delivery issue / Quality issue / Pricing issue / Other)
- Description textarea (required, 3 rows, with validation)
- Submit button with loading state
- Tab bar

### Missing Controls
- **Confirmation modal before submit** — Complaints are recorded permanently; should confirm "Log complaint for X?"
- **Photo / file attachment** — No camera or file upload to attach evidence (damaged product photos, delivery issues)
- **Voice note** — No audio input for quick description
- **Success screen (not just toast)** — Success is a toast; should be persistent screen with "View Complaint" / "Log Another"
- **Priority / severity selector** — No way to indicate urgency
- **Searchable customer dropdown** — No search within customer select
- **Complaint history** — No view of past complaints for this customer
- **Status tracking** — No way to see complaint resolution status
- **Skeleton loading state** — No placeholder while customer list loads
- **Draft auto-save indicator** — No indicator if complaint is saved locally
- **Undo complaint** — No time-window to undo
- **Offline queue indicator** — No indicator if complaint will be queued
- **Error boundary / retry** — If submit fails, no retry mechanism
- **Haptic feedback** — No vibration on submit success

---

## 12. Purchase Offer

### Existing Controls
- Page header with icon
- Success toast message
- Product dropdown select (limit 50)
- Supplier dropdown select (optional, limit 50)
- Quantity input (number, decimal)
- Offered price input (number, decimal)
- Payment terms textarea (optional)
- Submit button with loading state
- Tab bar

### Missing Controls
- **Confirmation modal before submit** — Purchase offers should be confirmed: "Submit offer for X units of Y at Z?"
- **Currency selector** — PHP class has `$currency` property but no UI control; hardcoded to EGP
- **Photo attachment** — No camera to photograph supplier quote or product sample
- **Product search / autocomplete** — Dropdown with 50 items; no search or filter
- **Supplier search / autocomplete** — Dropdown with 50 items; no search or filter
- **Current market price display** — No reference price shown when selecting product
- **Quantity unit selector** — No unit of measure (piece, case, kg, ton)
- **Delivery date estimate** — No field for expected delivery date
- **Multiple items** — Can only submit one product per offer; no multi-item form
- **Draft auto-save indicator** — No indicator if offer is saved locally
- **Success screen (not just toast)** — Toast disappears; should be persistent with actions
- **Offer history** — No view of past purchase offers
- **Skeleton loading state** — No placeholder while product/supplier lists load
- **Pull-to-refresh** — No refresh for product/supplier data
- **Offline queue indicator** — No indicator if offer will be queued
- **Error boundary / retry** — If submit fails, no retry mechanism
- **Haptic feedback** — No vibration on submit success

---

## 13. More

### Existing Controls
- User avatar circle (first letter)
- User name display
- User email display
- "Sales" section heading
- "Create Invoice" nav item (green icon, label, description, chevron)
- "Quotations" nav item (blue icon)
- "Log Return" nav item (amber icon)
- "Finance" section heading
- "Collect Payment" nav item (emerald icon)
- "Log Expense" nav item (red icon)
- "Other" section heading
- "Add Customer" nav item (purple icon)
- "Log Complaint" nav item (orange icon)
- "Purchase Offer" nav item (teal icon)
- Logout form (POST with CSRF)
- Logout button with icon
- Tab bar

### Missing Controls
- **Logout confirmation modal** — No "Are you sure you want to logout?" prompt
- **User role / permissions badge** — No display of user role (rep, supervisor, admin)
- **Settings / preferences** — No link to language toggle, notification settings, theme
- **Language switcher** — Bilingual app but no visible toggle on More page
- **Version info** — No app version or build number displayed
- **Help / support** — No link to help docs, FAQ, or support contact
- **Today's stats summary** — No mini-summary of day's performance
- **Offline data status** — No indicator of offline queue size or last sync time
- **Profile editing** — User info displayed but no edit capability
- **Notification preferences** — No way to manage notification settings
- **Skeleton loading state** — No placeholder while user data loads
- **Dark mode toggle** — No theme switching option
- **Haptic feedback** — No vibration on logout

---

# Cross-Cutting Gaps (All Pages)

| Gap | Affected Pages | Impact |
|-----|---------------|--------|
| **Confirmation modals: ZERO usage** | All pages with submit actions (Visit, Sales, Payment, Return, Expense, Complaint, Quotation, Purchase) | Financial and data-integrity risk; accidental submissions with no way to catch errors |
| **Tab bar missing** | Visit Flow, Collect Payment, Log Return, Log Expense | Rep gets stuck on page with no bottom nav; must use browser back |
| **Skeleton loading states: ZERO usage** | All pages | Poor perceived performance on slow mobile networks |
| **Pull-to-refresh: ZERO usage** | All list pages (Home, Customers, Stock, Quotations) | Rep cannot refresh stale data without full page reload |
| **Photo capture: ZERO usage** | Visit, Complaint, Return, Expense, Purchase Offer, Collect Payment | Critical for proof-of-presence, evidence, and receipts |
| **Offline queue indicator: ZERO usage** | All pages | Rep doesn't know if actions are queued or lost |
| **Haptic feedback: ZERO usage** | All pages | Mobile UX best practice; confirms actions without looking |
| **Error boundary / retry: ZERO usage** | All pages | Failed operations show messages but no recovery path |
| **Draft auto-save indicator: ZERO usage** | Visit Flow, Sales Flow | Rep doesn't know if work-in-progress is preserved |
| **Undo / time-window reversal: ZERO usage** | All pages | No safety net for accidental submissions |
| **Searchable dropdowns** | Collect Payment (customers), Log Return (products), Purchase Offer (products/suppliers) | Native `<select>` is unusable with 50+ items on mobile |
| **Empty state illustrations** | Customers, Stock, Quotations | Text-only empty states are forgettable; illustrations improve guidance |
| **Customer balance display** | Customers, Collect Payment, Sales Flow, Visit Flow | Rep cannot make informed decisions without knowing what customer owes |
| **Share / export** | Stock, Quotations | No way to share lists externally |
| **Date pickers** | Home (visit planning), Log Expense, Log Return | Cannot backdate or plan ahead |
