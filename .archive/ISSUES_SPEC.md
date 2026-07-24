# Issues to Create — From Diagnosis Report

> **⚠️ ARCHIVED: 2026-07-24 — All 18 issues are CLOSED.**
> See `ISSUES_ARCHIVE.md` (root) for the definitive status of every documented issue.
> Every P0/P1 bug was fixed and verified in subsequent commits. The 🟡 items in
> the verification sweep are low-severity micro-optimizations, not release blockers.
> This file is kept for traceability only — do not treat as an active task list.

> Generated from `bmad-output/diagnosis-report.md`
> Create these as GitHub issues for tracking.

---

## CRITICAL (P0)

### Issue 1: Stock Race Condition — Lost Updates

**Labels:** `bug`, `critical`, `data-integrity`
**File:** `app/Services/StockService.php:78-94`

**Description:**
`Stock::firstOrNew()` uses a plain SELECT without `FOR UPDATE`. Two concurrent transactions reading the same stock quantity can both pass the balance check, but only one decrement is saved. This allows stock to go negative silently.

**Fix:** Use `Stock::where(...)->lockForUpdate()->first()` or atomic SQL `$stock->increment('quantity', $qty)`.

**Acceptance Criteria:**

- [ ] Stock decrement uses atomic operation or row-level locking
- [ ] Concurrent sales of same product cannot both succeed if stock is insufficient
- [ ] Test added for concurrent stock access scenario

---

### Issue 2: Visit Report Signature Path Never Stored in Database

**Labels:** `bug`, `critical`, `data-loss`
**File:** `app/Livewire/App/VisitFlow.php:111-126`

**Description:**
Signature image is saved to disk via `Storage::disk('private')->put()` but the `$path` is never included in `VisitReport::create()`. The signature file becomes orphaned — it exists on disk but can never be retrieved. PDF generation tries to read `$invoice->visit?->report?->signature_path` which is always null.

**Fix:** Add `'signature_path' => $path` to `VisitReport::create()` and add `signature_path` to the model's `$fillable`.

**Acceptance Criteria:**

- [ ] `signature_path` is saved to database when signature is captured
- [ ] PDF generation can retrieve and display the signature
- [ ] Test added for signature path persistence

---

### Issue 3: N+1 Query on Home Page Tasks

**Labels:** `bug`, `performance`, `critical`
**File:** `app/Livewire/App/Home.php:90`

**Description:**
`openTasks` query loads Task records without eager loading the `customer` relationship. Blade template accesses `$task->customer->name_ar` in a foreach loop, triggering up to 10 extra SQL queries per page load.

**Fix:** Add `->with('customer')` to the `openTasks` query.

**Acceptance Criteria:**

- [ ] `openTasks` query uses `->with('customer')`
- [ ] No N+1 queries on home page (verify with Laravel Debugbar or query log)

---

## HIGH (P1)

### Issue 4: Invoice Cancellation Decrements Wrong Amount

**Labels:** `bug`, `high`, `financial`
**File:** `app/Services/InvoiceService.php:176`

**Description:**
When cancelling a partially-paid invoice, `customer.balance` is decremented by the full `invoice->total` instead of the unpaid amount. For a 100 invoice with 60 paid, cancellation decrements 100 instead of 40, leaving the customer with a -60 balance.

**Fix:** Decrement by `($invoice->total - $invoice->paid_amount)` instead of `$invoice->total`.

**Acceptance Criteria:**

- [ ] Invoice cancellation only reverses the unpaid portion
- [ ] Customer balance is correct after cancelling partially-paid invoice
- [ ] Test added for partial payment + cancellation scenario

---

### Issue 5: Amended Invoice Reuses Original Number

**Labels:** `bug`, `high`, `data-integrity`
**File:** `app/Services/InvoiceService.php:191`

**Description:**
When an invoice is amended (cancelled + new draft created), the draft is created with the same `invoice_number` as the cancelled invoice. This breaks the uniqueness constraint and sequential numbering scheme.

**Fix:** Generate a new invoice number via `$this->numbers->generate('sales_invoice', $company->id)` for the amended draft.

**Acceptance Criteria:**

- [ ] Amended invoice gets a new, unique number
- [ ] Original cancelled invoice retains its number
- [ ] Sequential numbering is not broken

---

### Issue 6: Payment Status Update Race Condition

**Labels:** `bug`, `high`, `concurrency`
**File:** `app/Services/PaymentService.php:37-44`

**Description:**
After atomic `increment()`/`decrement()` on `paid_amount`/`remaining_amount`, the status check reads stale PHP model attributes. Two concurrent payments can both set wrong status.

**Fix:** Read fresh values from DB after atomic operations before making status decision.

**Acceptance Criteria:**

- [ ] Payment status is determined from fresh DB values, not stale model attributes
- [ ] Concurrent payments for same invoice result in correct final status

---

### Issue 7: PDF Routes Missing Company Authorization

**Labels:** `bug`, `high`, `security`
**File:** `app/Http/Controllers/App/PdfController.php` / `routes/web.php:82-84`

**Description:**
PDF routes use route model binding but never verify the resolved model belongs to the authenticated user's company. A rep could access any company's PDFs by guessing numeric IDs.

**Fix:** Verify `$model->company_id === auth()->user()->company_id` in the controller, or ensure `SetActiveCompanyContext` middleware runs.

**Acceptance Criteria:**

- [ ] PDF routes verify company ownership
- [ ] Rep cannot access another company's PDFs
- [ ] Test added for cross-company PDF access attempt

---

## MEDIUM (P2)

### Issue 8: Customer Balance Decrement Skipped When Balance is 0

**Labels:** `bug`, `medium`, `financial`
**File:** `app/Services/PaymentService.php:48-49`

**Description:**
`if ((float) $customer->balance > 0)` prevents balance decrement when balance is exactly 0. Payments against zero-balance customers don't update balance.

**Fix:** Remove the `> 0` condition or use `max(0, balance - amount)`.

---

### Issue 9: User Data Not Escaped in PDF HTML

**Labels:** `bug`, `medium`, `security`
**File:** `app/Services/PdfService.php:89+`

**Description:**
User-controlled data (names, addresses, notes) is interpolated into HTML without `e()` escaping. Malformed data can break PDF layout.

**Fix:** Wrap all user-data interpolations in `e()`.

---

### Issue 10: Invoice Created Without visit_id

**Labels:** `bug`, `medium`, `audit-trail`
**File:** `app/Livewire/App/SalesFlow.php:179`

**Description:**
`SalesFlow` does not pass `visit_id` when creating invoices. Invoices created during field visits cannot be traced back.

**Fix:** Add `visitId` property and pass it in the create call.

---

### Issue 11: NumberSequenceService Ignores series_format

**Labels:** `bug`, `medium`, `dead-code`
**File:** `app/Services/NumberSequenceService.php:43`

**Description:**
`series_format` column is stored but never used. Format is hardcoded.

**Fix:** Parse `series_format` or remove the unused column.

---

### Issue 12: Unbounded Queries Violate AGENTS.md

**Labels:** `bug`, `medium`, `performance`
**File:** `app/Livewire/App/CollectPayment.php:81`, `LogReturn.php:76`

**Description:**
`->get()` without `->limit()` loads all active customers/products. Violates "Pagination on every list" rule.

**Fix:** Add `->limit(100)` or convert to searchable autocomplete.

---

## LOW (P3)

### Issue 13: LogReturn Missing against_invoice_id

**Labels:** `bug`, `low`, `feature-gap`

### Issue 14: PricingService N+1 on company

**Labels:** `bug`, `low`, `performance`

### Issue 15: SalesFlow No Index Bounds Check

**Labels:** `bug`, `low`, `edge-case`

### Issue 16: PaymentService Invoice-Customer Validation

**Labels:** `bug`, `low`, `validation`

### Issue 17: PDF N+1 — item.product in loop

**Labels:** `bug`, `low`, `performance`

### Issue 18: Stock Ops in Loop (3 queries/item)

**Labels:** `bug`, `low`, `performance`
