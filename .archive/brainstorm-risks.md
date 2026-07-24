# Reverse Brainstorming: UI Failure Analysis — Rep PWA Pages

> **Technique**: Reverse Brainstorming — "What would make this UI fail for a field rep on the road?"
> **Scope**: All 13 Rep PWA pages, ranked by criticality.
> **Method**: For each page, identify realistic failure scenarios, their root cause, impact, and a specific fix.
> **Ranked from most critical (financial/data-integrity risk) to least critical (UX polish).**

---

## CRITICAL — Financial or Data-Integrity Failures

These failures can cause wrong money, wrong stock, or unrecoverable data loss.

---

### 6. Sales Flow

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 1 | **Rep accidentally submits invoice with wrong customer or wrong quantities.** Rep taps Submit while scrolling on a bumpy road — wrong customer was selected, or quantity was accidentally changed by 10x. Invoice is created, stock decremented, customer balance updated. There is no confirmation step and no undo. | No confirmation modal before submit. No undo capability. `InvoiceService::create()` processes inside a transaction but the UI never pauses to confirm. | **Critical** — Every rep creates invoices daily. One wrong invoice = wrong stock + wrong customer balance + wrong revenue report. | Add a confirmation modal showing: customer name, items with quantities/prices, total amount, payment terms. "Confirm Invoice?" with explicit consequence text. |
| 2 | **Rep creates invoice for a customer who has exceeded their credit limit.** No warning is shown — the rep doesn't know the customer's outstanding balance or credit limit. Invoice goes through, customer goes deeper into debt. | No customer balance/credit limit display on the Sales Flow page. `$recalcCart()` is a no-op (line 110 of `SalesFlow.php`). | **Critical** — Affects every credit sale. Cumulative risk across many invoices per day. | Show a customer summary card after selection: outstanding balance, credit limit, remaining credit. Block or warn (with manager override) if invoice would exceed credit limit. |
| 3 | **Rep tries to sell items not in van stock — sale goes through, stock goes negative.** The product list shows items from all warehouses. Rep doesn't know what's actually in the van. Order is created, stock decremented below zero in the system. | No van stock validation on the Sales Flow page. Product search shows all products regardless of van availability. No quantity check against `stocks.quantity`. | **Critical** — Negative stock breaks inventory accuracy for every product touched. Happens every time rep sells something they don't physically have. | Validate cart quantities against van stock at submit time. Show van stock count on each product card. Block or warn if ordered quantity exceeds available van stock. |
| 4 | **Rep edits price to 0 or a tiny amount by mistake.** Price input is editable with no floor. On a bumpy road, rep could accidentally clear and retype a wrong number. Zero-price invoice is created. | No price floor validation in `SalesFlow`. The `$recalcCart()` method is empty. No confirmation modal. | **High** — Zero-price invoices cause revenue leakage and require admin cleanup. | Add minimum price validation (at least cost price). Show confirmation modal with total amount highlighted. Flag unusually low prices with a visual warning. |

---

### 8. Collect Payment

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 5 | **Rep collects the wrong amount or from the wrong customer.** Rep selects wrong customer from dropdown (no search, no name verification), or types wrong amount. Payment is recorded, cashbox updated, invoice balance reduced. No undo in the UI. | No confirmation modal. No searchable customer dropdown (native `<select>` with many customers is hard to use on mobile). `PaymentService::cancel()` exists in the backend but is not exposed in the UI. | **Critical** — Wrong payment = wrong cashbox balance + wrong invoice balance. Every payment affects three entities (payment, invoice, cashbox). | Confirmation modal: "Collect {amount} from {customer_name} via {method} for Invoice #{number}?" Show customer's full name and remaining balance. Expose a 60-second undo toast after submission. |
| 6 | **Rep accidentally collects payment for the wrong invoice.** Customer has multiple unpaid invoices. Rep selects the wrong one from the dropdown. Payment is applied to the wrong invoice. | Invoice dropdown is a native `<select>` with no search or visual distinction between invoices. No confirmation step. | **High** — Wrong invoice payment creates accounting confusion and requires admin correction. | Show invoice list with customer name, invoice date, total, and remaining amount for each. Confirmation modal before submit. |
| 7 | **Double payment — rep taps submit twice.** Submit button is not disabled during submission (relies on `wire:loading` which is a CSS toggle, not atomic). On slow connection, rep might tap twice. | No button disable on click. No idempotency check. `wire:loading` hides/shows but the second tap could fire before the first completes. | **High** — Double payment means double credit to customer. Requires admin reversal. | Disable button on first tap (Alpine `x-bind:disabled`). Add idempotency token to prevent duplicate submissions. |

---

### 9. Log Return

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 8 | **Rep logs a return for items that were never actually sold to this customer.** No invoice link exists. Rep manually selects products and customer, but there's no verification that this customer ever purchased these items. Return is processed, stock goes up, customer balance goes down. | No invoice-based item picker. No link between returns and original invoices. Return is a free-form selection. | **Critical** — Fraudulent or mistaken returns inflate stock and reduce customer balances incorrectly. | Show "Select Invoice" picker that lists the customer's past invoices. Auto-populate items from the selected invoice. Confirmation modal showing items, quantities, and financial impact. |
| 9 | **Rep accidentally returns 100 units instead of 10.** Quantity input accepts any decimal. On a bumpy road or with fat-finger input, quantity could be wrong. No confirmation step. | No quantity sanity check. No confirmation modal. No "stock impact preview" showing the effect on van stock. | **High** — Wrong return quantity inflates van stock and deflates customer balance. | Show stock impact preview (current van stock → new van stock after return). Confirmation modal with item details. Warn if return quantity exceeds original invoice quantity. |

---

### 10. Log Expense

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 10 | **Rep logs an expense that exceeds the cashbox balance.** Cashbox balance is displayed but there's no validation that the expense amount is feasible. Expense is processed, cashbox goes negative. | No cashbox balance check in `ExpenseService::log()`. The balance display is informational only — no `min:0.01` comparison against remaining balance. | **High** — Negative cashbox breaks daily reconciliation. | Validate expense amount against cashbox balance at submit time. Warn or block if expense would make cashbox negative. Show running total of today's expenses alongside balance. |
| 11 | **Rep accidentally logs a large expense under "Other" instead of "Fuel".** Category is a 4-item dropdown, but "Other" is a catch-all. If rep is in a hurry, they pick the wrong category. No confirmation. | No confirmation modal. No "Other" sub-category text input. Categories are too coarse. | **Medium** — Wrong category skews expense reports. Common for daily fuel expenses. | Add confirmation modal. Allow custom text input for "Other" category. Add quick-preset buttons for common expenses (e.g., "Fuel 50 EGP"). |

---

### 7. Quotation Flow

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 12 | **Rep confirms a price that's below the floor.** Manual check exists (lines 70-77, 90-97 of `QuotationFlow.php`) but only catches exact floor violations. If floor is updated server-side while rep is offline, rep sees stale data and confirms a wrong price. | No real-time floor validation. No confirmation modal. Price floor check is server-side only — stale data risk on slow/offline connections. | **High** — Below-floor pricing loses margin on the sale. | Confirmation modal: "Confirm price {X} for {product}? (Floor: {Y}, Ceiling: {Z})". Re-validate floor at submit time server-side with fresh data. |
| 13 | **Rep accidentally creates a proforma invoice for the wrong quotation.** No confirmation step. The "Create Proforma" button fires immediately. Proforma is generated, WhatsApp link is shown — but it's for the wrong quotation. | No confirmation modal. Multi-step flow (list → detail → proforma) but no confirmation at the critical "Create Proforma" step. | **High** — Wrong proforma sent to customer causes confusion and requires manual correction. | Confirmation modal: "Create proforma for {product} at {price} for {customer}?" Show summary of negotiated price vs. original. |

---

### 2. Visit Flow

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 14 | **Rep submits visit report with incomplete or wrong summary.** No confirmation modal. Submit fires directly. The report is permanent — visit status changes to "done". No undo time window. | No confirmation modal before submit. No undo capability. `DB::transaction()` wraps the write but provides no user-facing recovery. | **High** — Wrong visit reports affect route analytics, customer follow-ups, and rep performance tracking. | Confirmation modal: "Submit visit report for {customer}?" with summary of all fields. Show a 30-second undo window after submission. |
| 15 | **Rep navigates away with unsaved report text.** Draft saves to localStorage every 3 seconds, but there's no "unsaved changes" warning. If rep accidentally taps Back, all typed text is lost. | No "beforeunload" or Livewire `beforeunload` handler. Draft auto-saves to localStorage but no visual indicator that draft exists, and no navigation guard. | **Medium** — Rep loses 5–10 minutes of typed report text. Happens on every accidental navigation. | Add a navigation guard: "You have unsaved changes. Leave page?" Show draft auto-save indicator (checkmark + timestamp). |
| 16 | **GPS fails, rep is stuck.** GPS error shows a message card but the only options are "Confirm Anyway" (out-of-range) or... nothing. If GPS completely fails (no position at all), rep cannot proceed to the report step. | No "Retry GPS" button. No fallback to manual location entry. The 3-step stepper has no bypass for GPS failure. | **Medium** — Rep cannot complete visit report if GPS is broken. Blocks the entire visit flow. | Add "Retry GPS" button. Add manual location entry as fallback. Allow rep to proceed with a note explaining GPS failure. |

---

## HIGH — Navigation / Workflow Failures

These failures cause the rep to get stuck, lose context, or be unable to complete their workday efficiently.

---

### Pages Missing Tab Bar: Collect Payment, Log Return, Log Expense

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 17 | **Rep finishes logging a return and is stuck with no way to navigate.** Tab bar is missing from Collect Payment, Log Return, and Log Expense pages. Rep must use browser back button or manually type a URL. On iOS Safari, the back gesture may close the PWA. | `<x-tab-bar>` component is not included in `collect-payment.blade.php`, `log-return.blade.php`, `log-expense.blade.php`. | **High** — 3 out of 13 pages have no navigation. Rep gets stuck after completing an action on these pages. | Add `<x-tab-bar active="more">` to all three pages. The More tab is the logical home for these secondary actions. |

---

### 1. Home

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 18 | **Rep cannot end their workday.** "Start Work" button exists but there is no "End Work" / clock-out button. Rep must either keep the app open all day or just close the browser. WorkSession never gets a proper end time. | No `endWork()` method in `Home.php`. No clock-out button in `home.blade.php`. WorkSession is created but never closed. | **High** — Every rep, every day. WorkSession duration tracking is broken. Manager cannot see when rep clocked out. | Add "End Work" button that calls a `endWork()` method to set `ended_at` on the WorkSession. Show it conditionally when work has started. |
| 19 | **Rep accidentally completes a task they didn't mean to.** "Done" button fires `completeTask()` directly on click with no confirmation. On a bumpy road or accidental tap, task is marked complete. | No confirmation modal. `completeTask()` at line 47 of `Home.php` does a direct `Task::update()`. No undo. | **Medium** — Tasks may be accidentally completed, requiring admin to reopen. | Add confirmation: "Mark task as done?" or use swipe-to-complete. Add undo capability. |

---

### 3. Customers

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 20 | **Rep cannot find a specific customer on a long list.** Search works, but there are no sort or filter controls. If rep doesn't remember the exact name, they must scroll through 30-at-a-time pages. | No sort controls. No filter controls. Pagination exists but no infinite scroll or "Load More" indicator. | **High** — Every rep, multiple times per day. Finding customers quickly is core to the job. | Add sort controls (name, last visit, balance). Add filter controls (status, route). Add pull-to-refresh. Add infinite scroll or visible "Load More" button. |

---

### 4. Add Customer

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 21 | **Rep creates a duplicate customer.** No duplicate check exists. Rep may not remember if a customer already exists, especially if another rep added them. Customer is created with a new code. | No duplicate check on phone or name. `AddCustomer.php` does a simple `Customer::create()` at line 40. | **High** — Duplicates cause split order history, wrong balances, and confusion. | Check for existing customer with same phone or similar name before creation. Show warning: "A customer with this phone already exists: {name}. Still create?" |
| 22 | **Rep fills out the form, navigates away, and loses all data.** No "unsaved changes" warning. No draft auto-save. Form fields are cleared on navigation. | No navigation guard. No localStorage draft saving (unlike Visit Flow which has it). | **Medium** — Rep wastes 3–5 minutes re-entering data. | Add navigation guard. Add localStorage auto-save with draft indicator. |

---

### 13. More

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 23 | **Rep accidentally logs out.** Logout button is a plain form POST with no confirmation. One tap and the session is destroyed. On iOS, this is especially dangerous because the PWA closes. | No confirmation modal. Raw `<form>` POST at lines 119-125 of `more.blade.php`. | **High** — Every rep, any time. Accidental logout loses all in-progress work (unsent invoices, unsubmitted reports). | Add confirmation modal: "Log out? Any unsaved work will be lost." or use slide-to-logout control. |

---

### 5. Stock Search

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 24 | **Rep searches for a product but doesn't realize results are capped at 20.** If there are 100 products matching, rep only sees the first 20 with no "Load More" or indication that more exist. Rep may think a product doesn't exist. | `->limit(20)` at line 28 of `StockSearch.php` with no pagination or "Load More" mechanism. | **High** — Rep cannot find products beyond the first 20 results. May incorrectly tell a customer a product is unavailable. | Add infinite scroll or "Show More" button. Add result count display ("Showing 20 of 47 results"). |

---

### 11. Log Complaint

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 25 | **Rep cannot attach photo evidence to a complaint.** Customer shows damaged product. Rep has no way to photograph it. Complaint is text-only. Without photo evidence, the complaint is hard to investigate. | No photo/file attachment capability in `LogComplaint.php` or `log-complaint.blade.php`. | **High** — Complaints without visual evidence are harder to resolve. Field reps encounter physical evidence daily. | Add camera button for photo attachment. Store photo path on the Complaint model. Allow multiple attachments. |

---

## MEDIUM — Performance and Reliability Failures

These failures cause slowness, stale data, or poor perceived performance.

---

### All Pages — Skeleton Loading

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 26 | **Rep sees a blank white screen for 2–5 seconds on slow mobile networks.** Every page loads data from the server with no skeleton loading placeholders. On 3G or weak signal, the page appears broken. | No skeleton loading states on any page. `<x-ds-skeleton>` component exists but is unused by any rep page. | **Medium** — Every page, every load. Reps in rural areas or basements with poor signal see blank screens. | Add skeleton loading placeholders to all pages. Use the existing `<x-ds-skeleton>` component. Show skeleton cards that match the layout of actual content. |

---

### All Pages — Pull-to-Refresh

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| 27 | **Rep sees stale data and doesn't know it.** Visit assignments, product prices, stock levels, customer balances — all can change server-side while rep is working. No way to refresh without full page reload. | No pull-to-refresh on any list page. No real-time updates or polling. | **Medium** — Every list page. Rep may sell a product at a stale price or visit a customer whose assignment changed. | Add pull-to-refresh gesture to all list pages (Home, Customers, Stock, Quotations). |

---

### All Pages — Offline Indicator

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| **28** | **Rep thinks they're online but data is stale, or thinks they're offline but are actually online.** Only Visit Flow has an offline banner. All other pages show no connection status. Service worker is not registered (no JS to call `navigator.serviceWorker.register()`). | No offline indicator on most pages. Service worker exists but has no registration code. No network status awareness. | **Medium** — Rep makes decisions based on stale or no data without realizing it. | Add a persistent connection status indicator (green dot = online, red dot = offline with queue count). Register the service worker. |

---

### Visit Flow — Offline Draft Sync

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| **29** | **Rep writes a visit report offline, comes back online, but the draft is never synced.** localStorage draft saves every 3 seconds, but there is no sync-back mechanism. The draft is purely client-side. If the rep doesn't manually submit, the draft is lost on cache clear. | Draft saves to localStorage (lines 36-45 of `visit-flow.blade.php`) but no sync mechanism exists. No `ServiceWorker` background sync. No automatic submission when back online. | **Medium** — Every offline visit report. Rep thinks their work is saved but it's only in the browser. | Implement offline queue: save visit data to IndexedDB, sync when back online. Show queue count. Auto-submit queued reports when connection is restored. |

---

### Collect Payment — Double Limit Bug

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| **30** | **Rep cannot see all their customers when collecting payment.** `CollectPayment.php` has a double `->limit()` call at lines 94-95: `->limit(200)->limit(100)`. The second limit overwrites the first, capping results at 100 instead of the intended 200. | Bug: `->limit(200)->limit(100)` — the second call replaces the first. | **Medium** — Reps with more than 100 customers cannot see or select the remaining ones. | Fix the double limit: remove one of the two `->limit()` calls. Use the intended value. |

---

### 12. Purchase Offer

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| **31** | **Rep cannot find their product or supplier in the dropdown.** Both product and supplier selects are capped at 50 items (`->limit(50)` at lines 66, 70 of `SubmitPurchaseOffer.php`). No search or filter within the dropdown. If the product isn't in the first 50, rep is stuck. | Native `<select>` with 50-item limit. No search. No autocomplete. | **Medium** — Reps with large product catalogs cannot submit offers for products beyond the first 50. | Replace with searchable autocomplete. Increase or remove the limit. Add search filtering. |

---

## LOW — UX Polish and Quality-of-Life Failures

These failures cause annoyance, friction, or suboptimal experience but don't cause data loss.

---

### All Pages — Haptic Feedback

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| **32** | **Rep isn't sure if their tap registered.** On a phone, there's no physical click. Rep taps Submit but gets no tactile feedback. May tap again (causing double submission) or wonder if the action worked. | No `navigator.vibrate()` calls anywhere in the PWA. | **Low** — Every action on every page. Minor UX friction. | Add haptic feedback on: submit success, error, GPS confirm, item add/remove. Use `navigator.vibrate()` for supported devices. |

---

### All Pages — Empty State Illustrations

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| **33** | **Rep sees "No customers found" and doesn't know what to do next.** Empty states are text-only. No illustration, no suggestion to add a customer or try a different search. | No illustrations in empty states. `<x-ds-empty>` component exists but is unused. | **Low** — Only when list is empty. Minor guidance improvement. | Use `<x-ds-empty>` component with illustration and actionable suggestion. |

---

### Pages Missing Success Screens

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| **34** | **Rep submits a complaint or purchase offer and the success message disappears before they can read it.** Log Complaint and Purchase Offer show a toast that auto-dismisses. Rep may not see it on a bright screen. | Toast-style success (auto-dismiss) instead of persistent success screen. | **Low** — Only on submission. Rep may not be sure the action completed. | Replace toast with persistent success screen (matching Sales/Return/Payment pattern) with actions: "View {entity} / Log Another / Home". |

---

### More Page — Missing Settings

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| **35** | **Rep wants to switch language but cannot find the toggle.** App is bilingual Arabic/English but there's no visible language switcher on the More page or anywhere in the PWA. | No language toggle control anywhere in the rep UI. | **Low** — Only when rep wants to switch language. May affect non-Arabic-speaking reps. | Add language toggle to More page or a settings sheet. |

---

### All Pages — Date Pickers

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| **36** | **Rep wants to log an expense from yesterday but can't backdate it.** Date is hardcoded to today. If rep forgot to log a fuel receipt yesterday, they can't fix it. | No date picker on Log Expense. No date picker on Log Return. Home page is locked to today's visits. | **Low** — Occasional. Affects reps who fall behind on logging. | Add optional date picker (defaulting to today, allowing backdate within 1–3 days). |

---

### 4. Add Customer — Success Screen

| # | Failure Scenario | Root Cause | Impact | Fix |
|---|---|---|---|---|
| **37** | **Rep adds a customer and doesn't know the next step.** Success is a toast that disappears. Rep doesn't know the customer's code, doesn't have a "View Customer" button, and has to navigate manually. | Toast-only success at line 10 of `add-customer.blade.php`. No persistent success screen. | **Low** — Only on customer creation. | Replace toast with success screen showing customer code, name, and actions: "View Customer / Add Another / Navigate to Customer". |

---

# Cross-Cutting Failure Patterns

These patterns affect multiple pages and represent systemic gaps in the PWA.

| Pattern | Affected Pages | Failure Scenario | Systemic Root Cause | Fix |
|---|---|---|---|---|
| **ZERO confirmation modals** | Visit, Sales, Payment, Return, Expense, Complaint, Quotation, Purchase Offer (8 pages) | Rep accidentally submits any financial or data-integrity action with no way to catch errors | `<x-ds-modal>` exists but is never used. No confirmation flow in any component. | Implement a shared confirmation modal pattern. Use it for every submit action that affects finances, stock, or customer data. |
| **ZERO undo capability** | All 8 mutating pages | After submission, there is no time window to reverse the action | Services have `cancel()` methods (Payment, Expense, Return, Invoice) but none are exposed in the rep UI. | Add a 30–60 second undo toast after every financial submission. Wire it to the existing service `cancel()` methods. |
| **Tab bar missing** | Collect Payment, Log Return, Log Expense | Rep is stuck on the page after completing an action | `<x-tab-bar>` not included in 3 of 13 page views. | Add `<x-tab-bar active="more">` to all three pages. |
| **No skeleton loading** | All 13 pages | Blank white screen on slow connections | `<x-ds-skeleton>` component exists but is unused. | Wire up the existing skeleton component on every page that fetches data. |
| **No pull-to-refresh** | Home, Customers, Stock, Quotations | Stale data with no refresh option | No refresh gesture on any list page. | Add pull-to-refresh to all list pages. |
| **No photo capture** | Visit, Complaint, Return, Expense, Purchase Offer, Collect Payment (6 pages) | Rep cannot provide visual evidence for physical-world actions | No camera integration anywhere in the PWA. | Add camera button using `<input type="file" accept="image/*" capture="environment">` to relevant pages. |
| **No offline queue** | All mutating pages | Actions performed offline are lost | Service worker caches pages but has no data sync. VisitFlow draft saves to localStorage but never syncs back. | Implement IndexedDB-based offline queue with background sync when online. |
| **Service worker not registered** | Entire PWA | PWA is not truly installable/usable offline | `sw.js` exists but no JavaScript registers it. `manifest.json` is linked but SW registration is missing. | Add SW registration code to the base layout. |
| **Searchable dropdowns** | Collect Payment (customers), Log Return (products), Purchase Offer (products + suppliers), Quotation Flow | Native `<select>` is unusable with 50+ items on mobile | No searchable autocomplete component exists. | Build or adopt a searchable dropdown component. Use it on all pages with long `<select>` lists. |

---

# Priority Summary

| Priority | Count | Pages | Core Issue |
|---|---|---|---|
| **CRITICAL** | 13 | Sales (4), Payment (3), Return (2), Expense (2), Quotation (2), Visit (1 — counted under High) | Financial errors with no confirmation or undo |
| **HIGH** | 10 | Home (2), Payment tab bar (1), Return tab bar (1), Expense tab bar (1), Customers (1), Add Customer (2), More (1), Stock (1), Complaint (1) | Navigation dead-ends, missing critical data, no evidence capture |
| **MEDIUM** | 6 | All (skeleton, pull-to-refresh, offline), Visit (draft sync), Payment (bug), Purchase Offer (dropdown) | Performance, stale data, bugs |
| **LOW** | 6 | All (haptic, empty states), Complaint/Purchase (success screen), More (settings), Expense/Return (date picker), Add Customer (success screen) | UX polish |

**Total failure scenarios identified: 37** across 13 pages.
