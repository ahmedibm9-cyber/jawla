# Diagnosis Report: Bugs & Performance Issues

> **⚠️ ARCHIVED: 2026-07-24 — All 30 findings are CLOSED.**
> Every bug and performance issue listed below was fixed in subsequent phases.
> See `ISSUES_ARCHIVE.md` (root) for the definitive status.

**Date:** 2026-07-18
**Scope:** Full codebase audit — Livewire components, Services, Models, Routes
**Technique:** Systematic code review with hypothesis-driven analysis

---

## Summary

| Category        | Critical | High | Medium | Low | Total  |
| --------------- | -------- | ---- | ------ | --- | ------ |
| **Bugs**        | 1        | 4    | 5      | 5   | 15     |
| **Performance** | 1        | 0    | 6      | 8   | 15     |
| **Total**       | 2        | 4    | 11     | 13  | **30** |

---

## CRITICAL Bugs

### BUG 1: Stock Race Condition — Lost Updates

**File:** `app/Services/StockService.php:78-94`
**Impact:** Two concurrent sales of the same product can both read the same stock quantity, both pass the balance check, but only one decrement is saved. Stock goes negative silently.

```
Stock::firstOrNew([...], ['quantity' => 0]);  // plain SELECT, no FOR UPDATE
$newQty = $stock->quantity + $qty;             // PHP reads snapshot
$stock->quantity = $newQty;
$stock->save();                                // overwrites concurrent change
```

**Fix:** Use `Stock::where(...)->lockForUpdate()->first()` or atomic SQL `$stock->increment('quantity', $qty)`.

---

### PERF 1: N+1 Query on Home Page Tasks

**File:** `app/Livewire/App/Home.php:90` / `resources/views/livewire/app/home.blade.php:78`
**Impact:** Up to 10 extra SQL queries per page load.

```
'openTasks' => Task::query()->...->get(),  // NO ->with('customer')
```

Blade accesses `$task->customer->name_ar` in loop.

**Fix:** Add `->with('customer')` to the query.

---

## HIGH Bugs

### BUG 2: Invoice Cancellation Decrements Wrong Amount

**File:** `app/Services/InvoiceService.php:176`
**Impact:** Cancelling a partially-paid invoice decrements customer balance by the full total, not the unpaid amount. Customer ends up with negative balance.

```php
$invoice->customer->decrement('balance', (float) $invoice->total);
// Should be: $invoice->total - $invoice->paid_amount
```

### BUG 3: Visit Report Signature Path Never Stored

**File:** `app/Livewire/App/VisitFlow.php:111-126`
**Impact:** Signature image saved to disk but path never written to database. PDF generation can never retrieve the signature.

```php
Storage::disk('private')->put($path, $imgData);
VisitReport::create([...]);  // $path NOT included
```

### BUG 4: Amended Invoice Reuses Original Number

**File:** `app/Services/InvoiceService.php:191`
**Impact:** Two invoices (cancelled + draft) share the same number, breaking uniqueness and sequential numbering.

```php
'invoice_number' => $invoice->invoice_number,  // should generate new
```

### BUG 5: Payment Status Update Race Condition

**File:** `app/Services/PaymentService.php:37-44`
**Impact:** Two concurrent payments can both see stale `remaining_amount` and set wrong status.

---

## MEDIUM Bugs

| #   | File:Line                                    | Issue                                                     |
| --- | -------------------------------------------- | --------------------------------------------------------- |
| 6   | `PaymentService.php:48-49`                   | Customer balance decrement skipped when balance is 0      |
| 7   | `PdfService.php:89+`                         | User data interpolated into HTML without `e()` escaping   |
| 8   | `PdfController.php` / `routes/web.php:82-84` | PDF routes have no company-scoped authorization           |
| 9   | `SalesFlow.php:179`                          | Invoice created without `visit_id` — audit trail broken   |
| 10  | `NumberSequenceService.php:43`               | `series_format` column is dead data — format is hardcoded |

---

## LOW Bugs

| #   | File:Line                      | Issue                                                 |
| --- | ------------------------------ | ----------------------------------------------------- |
| 11  | `LogReturn.php:53`             | `against_invoice_id` never passed — returns orphaned  |
| 12  | `PricingService.php:31`        | `User::with('roles')` missing `company` — N+1         |
| 13  | `SalesFlow.php:87-103`         | No index bounds check in `updateQty`/`updatePrice`    |
| 14  | `PaymentService.php:36`        | Invoice-customer ownership not validated              |
| 15  | `NumberSequenceService.php:15` | Naming series creation edge case with company context |

---

## Performance Issues

### HIGH

| #   | File:Line     | Issue                       | Impact                 |
| --- | ------------- | --------------------------- | ---------------------- |
| 1   | `Home.php:90` | `Task.customer` N+1 in loop | Up to 10 extra queries |

### MEDIUM

| #   | File:Line                                   | Issue                                           | Impact                  |
| --- | ------------------------------------------- | ----------------------------------------------- | ----------------------- |
| 2   | `SalesFlow.php:127`                         | `user.company` lazy-loaded on every cart recalc | 1 query per interaction |
| 3   | `PdfService.php:141`                        | `item.product` N+1 in PDF item loop             | N queries per PDF       |
| 4   | `PdfService.php:34`                         | `invoice.visit.report` 2-level lazy chain       | 2 queries per PDF       |
| 5   | `InvoiceService.php:88,131,161`             | Stock ops in loop (3 queries/item)              | 30 queries for 10 items |
| 6   | `CollectPayment.php:81`, `LogReturn.php:76` | Unbounded `->get()` queries                     | Violates AGENTS.md rule |

### LOW

| #   | File:Line                      | Issue                                   |
| --- | ------------------------------ | --------------------------------------- |
| 7   | `PdfService.php:105`           | `payment.invoice` lazy load             |
| 8   | `ReturnService.php:39,83`      | Stock ops in loop                       |
| 9   | `VanTransferService.php:52,88` | Stock transfer in loop (6 queries/item) |
| 10  | `AlarmService.php:28`          | `firstOrCreate` in loop over recipients |
| 11  | `InvoiceService.php:131`       | `$invoice->items` not preloaded         |
| 12  | `InvoiceService.php:176`       | `$invoice->customer` lazy in cancel     |
| 13  | `ReturnService.php:66`         | `$return->customer` lazy                |
| 14  | `NumberSequenceService.php:41` | Redundant Company query                 |

---

## Recommended Fix Priority

### Immediate (blocks beta)

1. **BUG 1** — Stock race condition → use `lockForUpdate()` or atomic increment
2. **BUG 3** — Signature path not stored → add to `VisitReport::create()`
3. **PERF 1** — Home N+1 → add `->with('customer')`

### Before production

4. **BUG 2** — Wrong cancel amount → decrement by unpaid amount only
5. **BUG 4** — Reused invoice numbers → generate new number
6. **BUG 5** — Payment status race → read fresh values after atomic ops
7. **BUG 8** — PDF auth → add company_id check
8. **PERF 3-4** — PDF N+1 → eager load `items.product`, `visit.report`

### Nice to have

9. **BUG 6-7, 9-10** — Medium bugs
10. **PERF 5-6** — Batch stock ops, limit unbounded queries
