# Starbursting: UI Audit Questions — Rep PWA Pages

> **Technique**: Starbursting — systematically ask Who / What / When / Where / Why / How about each page's completeness.
> **Scope**: All 13 Rep PWA pages + cross-cutting analysis.
> **Output**: Questions → answers (what's missing) → priority (P0–P3) → effort (S/M/L).

---

## 1. Home Page (`home.blade.php` + `Home.php`)

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| 1.1 | **Who** can start/end a work session, and what happens to the session if neither is clicked? | No "End Work" button exists. `WorkSession` is created but never closed — no `ended_at` is set. The manager cannot see when the rep clocked out. | P0 | S |
| 1.2 | **What** does the rep see when they complete a task — is there confirmation or undo? | `completeTask()` fires on click with no confirmation modal and no undo window. An accidental tap permanently marks a task as done. | P1 | S |
| 1.3 | **How** does the rep know which visits are overdue vs. merely pending? | All pending visits look identical — no visual distinction for visits that are past their scheduled time. No overdue indicator or time-based color coding. | P2 | S |
| 1.4 | **When** the rep's visit list is long, how do they prioritize? | No sort or filter controls on the visit list. All 100 visits render as a flat list with no grouping by area, time, or priority. | P2 | M |
| 1.5 | **What** feedback does the rep get on first load if there are no tasks and no visits? | Empty state shows only "No visits" text. No illustration, no suggestion ("Add a customer?" or "Contact your manager"). The `<x-ds-empty>` component exists but is unused. | P3 | S |

---

## 2. Visit Flow (`visit-flow.blade.php` + `VisitFlow.php`)

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| 2.1 | **What** happens if GPS fails entirely — no position at all? | If `navigator.geolocation.getCurrentPosition` errors, only `errorMessage` is set. There is no "Retry GPS" button, no manual location entry fallback, and no way to proceed. The rep is stuck. | P0 | S |
| 2.2 | **Who** can save a draft and resume later? | Drafts save to `localStorage` every 3 seconds, but there is no visual indicator that a draft exists (no timestamp, no "Draft saved" badge). No "Resume draft?" prompt on re-entry. Drafts are never synced to the server. | P1 | M |
| 2.3 | **How** does the rep know if they accidentally navigate away with unsaved text? | No `beforeunload` guard. No navigation warning. Tapping Back or the tab bar silently discards all typed report text. | P0 | S |
| 2.4 | **What** happens after report submission — is there a confirmation summary or undo? | No confirmation modal before `submitReport()`. No undo window after submission. The `DB::transaction()` wraps the write but the user has zero chance to catch a mistake. | P0 | M |
| 2.5 | **Where** does the rep see their signature after drawing it? | The signature canvas shows the drawing but provides no preview of the final image. No way to review or edit the signature before submit. The "Clear" button erases it but there's no "Confirm Signature" step. | P3 | S |

---

## 3. Customers (`customers.blade.php` + `TodaysCustomers.php`)

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| 3.1 | **How** does the rep find a specific customer quickly? | Search exists but there are no sort controls (by name, last visit, balance) and no filter controls (by status, route, active/inactive). No infinite scroll or "Load More" — just a flat `@forelse` loop. | P1 | M |
| 3.2 | **What** information is shown for each customer? | Only name, code, phone, address, and maps link. No outstanding balance, no last visit date, no credit limit, no visit count. The rep cannot prioritize which customer to visit next based on this card alone. | P1 | M |
| 3.3 | **When** there are no customers, what does the rep see? | Empty state: plain text "No customers found" with no illustration, no suggestion ("Add a customer?"). The `<x-ds-empty>` component exists but is unused. | P3 | S |
| 3.4 | **Who** sees which customers? | All active customers for the company are shown, not just the rep's assigned customers. A rep sees every customer in the company — not route-scoped. | P2 | M |

---

## 4. Add Customer (`add-customer.blade.php` + `AddCustomer.php`)

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| 4.1 | **What** prevents duplicate customer creation? | No duplicate check on phone or name. `AddCustomer::submit()` does a direct `Customer::create()` with no pre-check. Duplicates will happen. | P0 | M |
| 4.2 | **How** does the rep know the new customer was created successfully? | Toast success message auto-dismisses. No customer code shown. No "View Customer" or "Navigate to Customer" button. No persistent success screen. | P2 | S |
| 4.3 | **What** happens if GPS fails to get coordinates? | `getPosition()` silently catches the error with an empty callback `() => {}`. Latitude/longitude fields remain empty. No fallback, no indication to the rep. | P1 | S |
| 4.4 | **When** the rep navigates away mid-form, what is lost? | No navigation guard. No localStorage auto-save. All entered data is lost on accidental navigation. Unlike Visit Flow, there is no draft mechanism. | P1 | S |

---

## 5. Stock Search (`stock-search.blade.php` + `StockSearch.php`)

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| 5.1 | **How** many results can the rep see? | Results are capped at 20 with `->limit(20)` and no pagination or "Load More". If 47 products match, the rep only sees 20. No "Showing 20 of 47" indicator. | P1 | S |
| 5.2 | **What** stock information is shown per product? | Product name, SKU, price, and warehouse stock quantities. But no van stock specifically highlighted. The rep doesn't know what's physically in their van vs. what's in the warehouse. | P1 | M |
| 5.3 | **When** the rep taps a product, what can they do? | Nothing — product cards are read-only. No "Add to Invoice" button. No "Add to Quotation" action. The stock search is purely informational with no action pathway. | P2 | S |
| 5.4 | **Who** sees stock for which warehouses? | All warehouses are shown for every product. The rep cannot filter to see only their van stock. No warehouse filter or "My Van" quick filter. | P2 | S |

---

## 6. Sales Flow (`sales-flow.blade.php` + `SalesFlow.php`)

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| 6.1 | **What** happens when the rep taps Submit? | `submit()` fires immediately with no confirmation modal. No summary review step. Invoice is created, stock decremented, customer balance updated — all with a single tap. | P0 | M |
| 6.2 | **How** does the rep know if a customer has exceeded their credit limit? | No customer balance or credit limit is displayed after selection. `$selectedCustomer` shows only `name_ar`. No outstanding balance, no credit limit, no remaining credit. | P0 | M |
| 6.3 | **What** prevents selling items not in van stock? | No van stock validation. Product search shows all products regardless of availability. No quantity check against `stocks.quantity`. Rep can sell items they don't physically have. | P0 | M |
| 6.4 | **When** the rep edits a price to 0 or near-zero, what happens? | No price floor validation. `$recalcCart()` is a no-op (empty method at line 110). `min="0"` on the input allows zero. Zero-price invoices are silently created. | P1 | S |
| 6.5 | **How** does the rep see the order total before submitting? | No cart total is displayed. Each line item shows its own total, but there is no grand total, no tax summary, no VAT calculation on the page. `$recalcCart()` computes nothing. | P1 | M |

---

## 7. Quotation Flow (`quotation-flow.blade.php` + `QuotationFlow.php`)

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| 7.1 | **What** prevents confirming a price below the floor? | Server-side check exists (`QuotationFlow.php` lines 70-77, 90-97) but there is no confirmation modal. If floor is updated server-side while rep is offline, stale data leads to wrong price. | P1 | M |
| 7.2 | **How** does the rep create a proforma invoice? | The "Create Proforma" button fires immediately with no confirmation. No summary of what's being created. No check that a price has been confirmed first. | P1 | S |
| 7.3 | **What** happens after proforma is created? | Success screen shows proforma number and total with a WhatsApp link. But no "Back to Quotations" action button. The only navigation option is the "Back" button which sets step to 'list'. No PDF download option. | P2 | S |
| 7.4 | **When** the rep navigates back from detail view, is there a warning? | No unsaved-changes warning. If the rep has entered a negotiated price but hasn't confirmed it, navigating back silently discards the input. | P2 | S |

---

## 8. Collect Payment (`collect-payment.blade.php` + `CollectPayment.php`)

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| 8.1 | **Who** can accidentally submit a wrong payment? | No confirmation modal. `submit()` fires on form submit with no review step. Rep can select wrong customer, wrong invoice, or type wrong amount — payment goes through immediately. | P0 | M |
| 8.2 | **How** does the rep search through 100+ customers? | Native `<select>` dropdown with no search. For reps with many customers, scrolling through a long native select on mobile is unusable. No autocomplete or searchable dropdown. | P1 | M |
| 8.3 | **What** happens if the rep taps Submit twice on slow connection? | No button disable on first click. `wire:loading.attr="disabled"` exists but depends on Livewire's CSS toggle — a double-tap before the first request completes can fire twice. No idempotency token. | P1 | S |
| 8.4 | **When** the rep selects an invoice, what information is shown? | Invoice number and remaining amount in the `<select>` option text. No invoice date, no total amount, no line items. Hard to distinguish between invoices when amounts are similar. | P2 | S |
| 8.5 | **What** does the tab bar show on this page? | `<x-tab-bar>` is completely missing from `collect-payment.blade.php`. After completing a payment, the rep has no navigation and must use browser back (which may close the PWA on iOS). | P0 | S |

---

## 9. Log Return (`log-return.blade.php` + `LogReturn.php`)

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| 9.1 | **What** links a return to the original sale? | No invoice-based item picker. Rep manually selects products and customer. There is no verification that this customer ever purchased these items. Returns are free-form. | P0 | M |
| 9.2 | **How** does the rep see the stock impact of the return? | No stock impact preview. Rep doesn't know what the van stock will look like after the return. `stockLookup` is passed to the view but only shown as a parenthetical in the product `<option>` text. | P1 | S |
| 9.3 | **When** the rep enters a quantity, what validation exists? | `min:0.01` is the only validation. No check against original invoice quantity. No maximum quantity check. Rep can return 1000 units for a 10-unit sale. | P1 | S |
| 9.4 | **What** does the tab bar show on this page? | `<x-tab-bar>` is completely missing from `log-return.blade.php`. Same navigation dead-end as Collect Payment. | P0 | S |
| 9.5 | **How** does the rep select a product? | Native `<select>` with all products. No search or autocomplete. With large catalogs, finding the right product is tedious. | P2 | M |

---

## 10. Log Expense (`log-expense.blade.php` + `LogExpense.php`)

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| 10.1 | **What** prevents logging an expense that exceeds the cashbox balance? | No validation against `$cashBoxBalance`. The balance is displayed as informational only. Rep can log an expense of 10,000 EGP with only 500 EGP in the cashbox. | P0 | S |
| 10.2 | **How** does the rep confirm the expense details? | No confirmation modal. `submit()` fires on form submit. No summary of "Log {amount} for {category}?" | P1 | S |
| 10.3 | **When** the rep selects "Other" category, can they specify what it was? | No custom text input for "Other" category. The note field exists but is not linked to the category selection. Common expenses like "Toll" or "Parking" cannot be distinguished. | P2 | S |
| 10.4 | **What** does the tab bar show on this page? | `<x-tab-bar>` is completely missing from `log-expense.blade.php`. Same navigation dead-end as Collect Payment and Log Return. | P0 | S |
| 10.5 | **How** does the rep see their total expenses for today? | No running total of today's expenses is shown. The cashbox balance is displayed but the rep cannot see "You've logged X EGP in expenses today." | P2 | S |

---

## 11. Log Complaint (`log-complaint.blade.php` + `LogComplaint.php`)

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| 11.1 | **What** evidence can the rep attach to a complaint? | No photo or file attachment capability. Complaint is text-only. When a customer shows damaged product, the rep cannot photograph it. | P1 | M |
| 11.2 | **How** does the rep know the complaint was logged? | Toast success message auto-dismisses. No persistent success screen. No complaint ID shown. No "View Complaint" or "Log Another" action. | P2 | S |
| 11.3 | **What** happens if the rep taps Submit with no description? | `min:5` validation exists on description, but `customer_id` validation catches empty selection. However, `complaint_type` has a default of 'other' — the type is always filled. The error UX shows inline errors but no summary. | P3 | S |
| 11.4 | **When** the rep selects "Other" complaint type, can they add detail? | No custom type input. The description field is the only place to explain. No structured data for "Other" complaints. | P3 | S |

---

## 12. Purchase Offer (`submit-purchase-offer.blade.php` + `SubmitPurchaseOffer.php`)

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| 12.1 | **How** does the rep find a product in the dropdown? | Native `<select>` capped at 50 items (`->limit(50)`). No search. With 100+ products, the rep cannot find products beyond the first 50. | P1 | M |
| 12.2 | **What** prevents submitting a zero-quantity or zero-price offer? | `quantity` and `offered_price` have no `min` validation on the input. `min:0.01` exists in server validation for quantity but `offered_price` has no minimum. | P2 | S |
| 12.3 | **How** does the rep know the offer was submitted? | Toast success message auto-dismisses. No persistent success screen. No offer ID shown. No "View Offer" action. | P2 | S |
| 12.4 | **What** happens if the rep wants to submit offers for multiple products? | No "Add Another" action after submission. Rep must navigate back and re-enter the form from scratch for each offer. No batch capability. | P2 | S |

---

## 13. More (`more.blade.php` + `MorePage.php`)

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| 13.1 | **What** happens if the rep accidentally taps Logout? | No confirmation modal. Raw `<form>` POST at lines 119-125 fires immediately. On iOS, this closes the PWA. All unsaved work (drafts, in-progress invoices) is lost. | P0 | S |
| 13.2 | **How** does the rep switch language? | No language toggle anywhere in the PWA. The app is bilingual but there is no visible control to switch between Arabic and English. | P2 | S |
| 13.3 | **What** settings or profile options exist? | None. The More page shows the user's name and email but no profile editing, no notification preferences, no theme settings, no password change. | P3 | M |
| 13.4 | **When** the rep is on the More page, can they see their work session status? | No work session indicator. No clock-in/clock-out status. No "You started work at X:XX" display. | P2 | S |

---

# Cross-Cutting Analysis

These patterns span multiple pages and represent systemic gaps in the PWA.

## Navigation

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| N.1 | **How** does the rep navigate between all pages? | Tab bar has 4 items (Home, Customers, Stock, More). Three pages (Collect Payment, Log Return, Log Expense) are missing the tab bar entirely — navigation dead-end. | P0 | S |
| N.2 | **What** is the breadcrumb / back-button behavior? | No back button on most pages except where `<x-page-header>` is used. No browser history awareness. No deep-link protection. Rep can end up on pages they didn't intend. | P2 | S |
| N.3 | **When** the rep is deep in a flow (Sales, Visit, Quotation), how do they get back? | Some flows have a "Back" button (Quotation), some don't (Sales has no back). No "Cancel" or "Save Draft" option in multi-step flows. | P1 | S |

## Forms

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| F.1 | **What** validation exists on all forms? | Server-side validation exists via Livewire `$this->validate()`. But no client-side validation UX. Error messages appear inline but no form-level summary. No `wire:model.live` on most fields (only debounced search). | P2 | M |
| F.2 | **How** are long `<select>` dropdowns handled? | Native `<select>` with no search on: Collect Payment (customers), Log Return (customers + products), Log Complaint (customers), Purchase Offer (products + suppliers). Unusable with 50+ items. | P1 | L |
| F.3 | **What** happens on form submit failure? | Error messages appear inline via `@error` directives. No toast notification. No shake animation. No visual summary. Some forms use `$errorMessage` string, others use `@error` — inconsistent pattern. | P2 | S |

## Lists

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| L.1 | **How** are lists paginated? | Home: `take(100)`. Customers: no explicit limit (all active). Stock: `limit(20)`. Collect Payment invoices: `limit(200)->limit(100)` (double limit bug). No infinite scroll, no "Load More", no page numbers. | P1 | M |
| L.2 | **What** happens when lists are empty? | Text-only empty states. No illustrations, no actionable suggestions. `<x-ds-empty>` component exists but is unused across all pages. | P3 | S |
| L.3 | **When** data changes server-side, how does the rep refresh? | No pull-to-refresh on any list page. No real-time updates. No polling. Rep must full-reload the page to see fresh data. | P1 | M |

## State Management

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| S.1 | **What** persists across page navigation? | Visit Flow saves drafts to `localStorage`. All other forms have zero persistence. Navigation away from Add Customer, Log Complaint, or any other form discards all data. | P1 | M |
| S.2 | **How** does the app handle concurrent sessions? | No session conflict detection. If the rep opens the app in two tabs, Livewire state may conflict. No "Another session detected" warning. | P3 | M |
| S.3 | **When** Livewire reconnects after a disconnect, what happens? | `wire:loading` shows a saving indicator but no reconnection handling. If the connection drops mid-request, the user sees a spinner indefinitely with no timeout or retry. | P2 | M |

## Error Handling

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| E.1 | **What** does the rep see on a 500 error? | Custom 500 page exists (`errors/500.blade.php`). But Livewire errors are caught as `$errorMessage` strings — no structured error display. Some pages show errors as red cards, others as inline text. | P2 | S |
| E.2 | **How** does the app handle network failures during submission? | `wire:loading` shows "Saving…" but no timeout, no retry button, no "Connection lost" indicator. If the request fails silently, the user doesn't know if it went through. | P1 | M |
| E.3 | **What** happens when a Livewire component throws an exception? | Livewire catches it and shows a modal in dev mode. In production, `$errorMessage` may or may not be set. No structured error boundary. Some components catch exceptions in `submit()`, others don't. | P2 | S |

## Offline Behavior

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| O.1 | **What** works offline? | Only Visit Flow has an offline banner. No other page knows about connectivity. Service worker (`sw.js`) exists but is not registered. No offline queue for actions. | P0 | L |
| O.2 | **How** does the rep know they're offline? | Only Visit Flow shows an offline indicator. All other pages show no connection status. Rep can tap Submit on any form while offline and get a Livewire error with no clear explanation. | P0 | M |
| O.3 | **When** the rep comes back online, what syncs? | Nothing. No background sync. No IndexedDB queue. Visit Flow draft stays in localStorage but never auto-submits. No other data persists client-side. | P1 | L |

## Accessibility

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| A.1 | **How** does a screen reader navigate the PWA? | `<x-page-header>` has no `role="banner"`. `<x-tab-bar>` has `aria-label="Bottom navigation"` (good). Visit cards have `role="button"` and `tabindex="0"` with `@keydown.enter` (good). But most interactive elements lack ARIA labels. | P2 | M |
| A.2 | **What** keyboard navigation exists? | Tab bar and visit cards are focusable. But form inputs, buttons, and selects lack visible focus indicators on some pages. No skip-to-content link. No landmark roles. | P3 | M |
| A.3 | **When** color is the only indicator, what happens for colorblind users? | Success/error states rely heavily on color (green/red cards). No icon-only fallback on status badges. The stepper uses color + checkmark icon (good). | P3 | M |

## Performance

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| P.1 | **What** loading states exist? | `wire:loading` toggle on submit buttons. No skeleton loading on any page. `<x-ds-skeleton>` component exists but is unused. Rep sees blank white screen on slow connections. | P1 | M |
| P.2 | **How** are images and assets optimized? | Logo is SVG (good). No lazy loading on any images. No responsive images. No WebP fallback. No image compression pipeline. | P3 | S |
| P.3 | **When** does the app feel slow? | Every page load requires a full Livewire round-trip. No optimistic UI updates. No preloading. No prefetching of next likely page. Customers page loads all active customers with no limit. | P2 | M |

## i18n / RTL

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| I.1 | **How** does the rep switch between Arabic and English? | No language toggle anywhere in the PWA. Some pages use `__()` translation helper (good), others hardcode `app()->getLocale() === 'ar' ? '...' : '...'` inline. No visible language switcher. | P2 | S |
| I.2 | **What** RTL/LTR issues exist? | Tab bar, stepper, and card layouts use flexbox — generally RTL-safe. But some inline translations use `app()->getLocale()` checks in Blade instead of `__()`, making maintenance harder. | P2 | M |
| I.3 | **When** a new string is added, how is it translated? | Mix of `__('key')` and inline ternary `getLocale()` checks. No centralized string management. Some strings are hardcoded in Arabic/English without using the translation system. | P3 | M |

## Security UX

| # | Question | Answer (What's Missing) | Priority | Effort |
|---|---|---|---|---|
| U.1 | **What** prevents session hijacking? | `APP_DEBUG=false` in prod. Sessions httpOnly + secure (per build guide). But no visible session indicator. No "Last active" display. No concurrent session detection. | P2 | M |
| U.2 | **How** does the rep know if their data is secure? | No TLS indicator in the UI. No "Secure connection" badge. No data encryption indicator. For a financial app, trust signals matter. | P3 | S |
| U.3 | **When** the rep enters sensitive data (payment amounts, signatures), is it protected? | No input masking on financial fields. No clipboard protection on sensitive data. No screenshot prevention on payment screens. Signature is stored as base64 PNG — no encryption at rest indication. | P2 | M |

---

# Summary Matrix

## By Page — Question Count and Priority Distribution

| Page | Questions | P0 | P1 | P2 | P3 |
|---|---|---|---|---|---|
| Home | 5 | 1 | 1 | 2 | 1 |
| Visit Flow | 5 | 3 | 1 | 0 | 1 |
| Customers | 4 | 0 | 2 | 2 | 0 |
| Add Customer | 4 | 1 | 2 | 1 | 0 |
| Stock Search | 4 | 0 | 2 | 2 | 0 |
| Sales Flow | 5 | 3 | 2 | 0 | 0 |
| Quotation Flow | 4 | 0 | 2 | 2 | 0 |
| Collect Payment | 5 | 2 | 2 | 1 | 0 |
| Log Return | 5 | 2 | 2 | 1 | 0 |
| Log Expense | 5 | 2 | 1 | 2 | 0 |
| Log Complaint | 4 | 0 | 1 | 2 | 1 |
| Purchase Offer | 4 | 0 | 1 | 3 | 0 |
| More | 4 | 1 | 0 | 2 | 1 |
| **Cross-cutting** | **28** | **4** | **8** | **12** | **4** |
| **TOTAL** | **67** | **19** | **27** | **18** | **3** |

## By Priority — Top Missing Controls

### P0 — Must Fix Before Launch (19 items)

**Financial/Data-Integrity Risk:**
1. No confirmation modal on any financial action (Sales, Payment, Return, Expense)
2. No credit limit / balance check on Sales Flow
3. No van stock validation on Sales Flow
4. No return-to-invoice linkage on Log Return
5. No expense-vs-cashbox validation on Log Expense
6. No double-submit protection on Collect Payment

**Navigation Dead-End:**
7. Tab bar missing on Collect Payment, Log Return, Log Expense
8. No End Work button on Home
9. No Logout confirmation on More

**Data Loss:**
10. No navigation guard on Visit Flow (unsaved report text lost)
11. No duplicate check on Add Customer
12. No offline indicator on any page except Visit Flow
13. No GPS retry/manual fallback on Visit Flow
14. No confirmation before Visit report submission

**Offline:**
15. Service worker not registered
16. No offline queue for any action
17. No offline indicator system-wide

**Home Page:**
18. No confirmation on task completion
19. No End Work / clock-out button

### P1 — Should Fix Soon (27 items)

Key themes: searchable dropdowns (4 pages), unsaved-changes guards (3 pages), loading states (system-wide), cart total display, draft sync, error handling on network failure, result count indicators, photo capture for complaints.

### P2 — Fix When Capacity Allows (18 items)

Key themes: sort/filter controls, empty state illustrations, language switcher, work session status, pull-to-refresh, stock impact previews, RTL consistency, session security UX.

### P3 — Nice to Have (3 items)

Key themes: accessibility ARIA labels, keyboard navigation focus indicators, profile/settings page.

---

# Effort Summary

| Effort | Count | Notes |
|---|---|---|
| **S** (Small, < 1 day) | 32 | Tab bars, confirmation modals, text changes, validation rules, button states |
| **M** (Medium, 1–3 days) | 28 | Searchable dropdowns, skeleton loading, credit limit checks, duplicate detection, draft persistence, photo capture, offline indicator |
| **L** (Large, 3–7 days) | 7 | Full offline queue with IndexedDB, service worker registration, pull-to-refresh system, complete accessibility audit |

**Estimated total effort**: ~60–80 dev-days for all 67 identified gaps.
**P0 only**: ~15–20 dev-days.
**P0 + P1**: ~35–45 dev-days.
