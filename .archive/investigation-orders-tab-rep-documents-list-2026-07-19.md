# Investigation Case File: orders-tab-rep-documents-list

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — gap M5 from `investigation-missing-ui-elements-2026-07-19.md`
**Severity:** Degraded UX / Missing must-have functionality (violates REQ-CMP-4, REQ-RPT)
**Status:** Open
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-orders-tab-rep-documents-list-2026-07-19.md`

---

## Summary

**One-sentence description:**
The rep PWA bottom tab bar is missing the **Orders** tab (spec requires Home · Visits · Customers · Orders · More). Reps have no dedicated page to view their own issued proformas and invoices with PDF/WhatsApp actions — documents are only visible in the Filament admin panel, not from the rep app.

**Expected behavior:** Per PRD v1.1 REQ-CMP-4 and REQ-RPT:

- `/app/orders` route exists and renders an `Orders` Livewire page
- Orders page shows all proformas and invoices issued by the current rep with: document type (proforma/invoice), document number, customer name, date, amount, status badge, actions (View PDF, Share WhatsApp, Convert proforma to invoice)
- Tab bar shows 5 tabs: Home · Visits · Customers · **Orders** · More
- Filter by document type (all/proformas/invoices), status, date range
- Skeleton loading, empty state, pull-to-refresh

**Actual behavior:**

- Tab bar (`resources/views/components/tab-bar.blade.php`) has the Orders tab markup but the page component is a stub
- Route `/app/orders` exists in `routes/web.php:72` but `App\Livewire\App\Orders` component is minimal
- `resources/views/livewire/app/orders.blade.php` exists but is a stub showing only `no_orders_yet` message
- Proformas and invoices exist in the database (`proforma_invoices`, `invoices` tables) with `user_id` column (issuing rep)
- PDF generation exists (`PdfController::proforma`, `PdfController::invoice`)
- WhatsApp share links exist on proforma success screen in `QuotationFlow`

**User / business impact:** Reps cannot see their own issued documents, share PDFs with customers, or track invoice status from the rep app. This is a daily blocker for reps who need to reference or re-share documents during follow-up visits.

---

## Symptom Details

**Trigger conditions:** Structural — always present; confirmed by static inventory.

**Environments affected:**

- [x] Production
- [x] Staging
- [x] Development / local

**First observed:** 2026-07-19 (UI-completeness audit, Evidence 3)
**Frequency:** Constant (code-level absence)
**Reproducible:** Yes

**Reproduction steps:**

1. Log in as rep who has created proformas/invoices
2. Navigate to `/app/orders` — renders minimal placeholder
3. No list of documents, no PDF links, no WhatsApp share
4. Tab bar shows Orders tab but it leads to an empty stub

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed, reproducible, or corroborated by 2+ independent sources.
> - **[B] Probable** — single source, code-read inference, or intermittent.
> - **[C] Speculative** — pattern match or untested hypothesis only.

### Evidence Item 1: Orders page is a stub

**Grade:** [A]
**Source:** `app/Livewire/App/Orders.php`, `resources/views/livewire/app/orders.blade.php`
**Verbatim excerpt:**

```php
// app/Livewire/App/Orders.php
namespace App\Livewire\App;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Orders extends Component
{
    public function render()
    {
        return view('livewire.app.orders');
    }
}
```

```blade
<!-- resources/views/livewire/app/orders.blade.php -->
<div>
    <div class="main-content">
        <x-page-header :title="__('app.orders')" />
        <div class="page-body">
            <p>{{ __('app.no_orders_yet') }}</p>
        </div>
    </div>
    <x-tab-bar active="orders" />
</div>
```

**Description:** The `Orders` component has zero data fetching, no query logic, no filters, no document rendering. It's a completely empty placeholder.

**Implications:** Full implementation needed: query proformas + invoices for current rep, paginate, render document cards with actions.

---

### Evidence Item 2: Proforma and invoice tables exist with user_id

**Grade:** [A]
**Source:** `app/Models/ProformaInvoice.php`, `app/Models/Invoice.php`, `database/migrations/*create_proforma_invoices_table.php`, `database/migrations/*create_invoices_table.php`
**Description:** Both tables have `user_id` column (the issuing rep), `customer_id`, amounts, dates, statuses. Proforma statuses: `draft`, `submitted`, `cancelled`, `amended`, `partially_paid`, `paid`, `sent`, `converted_to_invoice`. Invoice statuses: `draft`, `submitted`, `cancelled`, `partially_paid`, `paid`.

**Implications:** No schema changes needed. Rich data available for list display.

---

### Evidence Item 3: PDF generation exists for both proformas and invoices

**Grade:** [A]
**Source:** `app/Http/Controllers/App/PdfController.php`, `routes/web.php:85-86`
**Verbatim excerpt:**

```php
// routes/web.php
Route::get('/pdf/proforma/{proforma}', [PdfController::class, 'proforma'])->name('pdf.proforma');
Route::get('/pdf/invoice/{invoice}', [PdfController::class, 'invoice'])->name('pdf.invoice');
```

**Description:** PDF routes exist and are accessible from the rep app (inside `ensure.rep` middleware group). The `Orders` page can link directly to these routes.

**Implications:** No PDF work needed — just link to existing routes.

---

### Evidence Item 4: WhatsApp share exists on proforma success screen

**Grade:** [A]
**Source:** `resources/views/livewire/app/quotation-flow.blade.php`
**Verbatim excerpt:**

```blade
<a href="https://wa.me/?text={{ urlencode('Proforma: ' . $proforma->proforma_number . ' Total: ' . $proforma->total) }}"
   target="_blank" class="btn btn-success">
    {{ __('app.share_whatsapp') }}
</a>
```

**Description:** WhatsApp share link pattern exists. Can be replicated on each document card in the Orders page.

**Implications:** WhatsApp share is a simple `wa.me` link with document summary. Can be reused directly.

---

### Evidence Item 5: Proforma conversion to invoice exists in QuotationFlow

**Grade:** [A]
**Source:** `app/Livewire/App/QuotationFlow.php`
**Description:** The `createProforma()` method converts a quotation to a proforma. The reverse (proforma → invoice) is handled by the Sales Flow. The Orders page should show "Convert to Invoice" action for proformas that are in `submitted` or `amended` status.

**Implications:** "Convert to Invoice" button on proforma cards should navigate to `/app/sell?proforma={id}` or trigger conversion directly.

---

### Evidence Item 6: Translation keys for document statuses exist

**Grade:** [A]
**Source:** `lang/en/app.php`, `lang/ar/app.php`
**Keys present:** `status_draft`, `status_submitted`, `status_cancelled`, `status_amended`, `status_partially_paid`, `status_paid`, `status_sent`, `status_converted_to_invoice`, `invoices`, `proformas`, `invoice_number`
**Missing keys:** `all_documents`, `filter_by_type`, `view_pdf`, `share_whatsapp`, `convert_to_invoice`, `no_orders_yet`

**Implications:** Most translations exist. Need a few new keys for filter UI and actions.

---

### Evidence Summary

| #   | Title                                | Grade | Source                       | Key Implication             |
| --- | ------------------------------------ | ----- | ---------------------------- | --------------------------- |
| 1   | Orders page is empty stub            | A     | Orders.php, orders.blade.php | Full implementation needed  |
| 2   | Proforma/invoice tables have user_id | A     | Models, migrations           | Query by current rep        |
| 3   | PDF routes exist                     | A     | PdfController.php, web.php   | Just link to routes         |
| 4   | WhatsApp share pattern exists        | A     | quotation-flow.blade.php     | Reuse wa.me link pattern    |
| 5   | Proforma conversion exists           | A     | QuotationFlow.php            | "Convert to Invoice" action |
| 6   | Status translations exist            | A     | lang/*/app.php               | Few new keys needed         |

---

## Hypotheses

### Hypothesis 1 — Orders page was stubbed when tab bar was created but never fleshed out [Plausibility: High]

**Statement:** The tab bar was created with all 5 tabs per REQ-CMP-4, the route was registered, a stub component was created — but the actual document list was never implemented. Same pattern as M4 (Visits tab).

**Supporting evidence:**

- Evidence 1 [A] — Orders component is empty stub
- Route exists in web.php — someone wired it
- Same pattern as Visits tab (also a stub)

**Contradicting evidence:** None.

**Verification step:** `git log --oneline -- app/Livewire/App/Orders.php` — check creation date and if any content was ever added.

---

### Hypothesis 2 — Document list was deferred because proforma-to-invoice conversion flow wasn't stable [Plausibility: Medium]

**Statement:** The Orders page needs "Convert to Invoice" action on proformas. If the conversion flow wasn't tested end-to-end, the Orders page was deferred to avoid exposing a broken action.

**Supporting evidence:**

- `QuotationFlow::createProforma()` is the conversion path
- Proforma statuses include `converted_to_invoice` — conversion is tracked
- But the Orders page would need to trigger conversion from a different component

**Contradicting evidence:** The conversion flow exists in QuotationFlow; it works.

**Verification step:** Check if proforma-to-invoice conversion is fully tested.

---

### Hypothesis 3 — "Orders" is ambiguous — the team may have been unsure what to show [Plausibility: Low]

**Statement:** "Orders" could mean proformas, invoices, both, or just invoices. The PRD says "cross-role visibility of reports/quotations/proformas" (REQ-RPT) but doesn't specify the rep-side document list.

**Supporting evidence:**

- PRD REQ-RPT-1: "cross-role visibility" — not specific about rep-side
- PRD REQ-CMP-4: "Orders" tab — but what counts as an "order"?

**Contradicting evidence:** The tab bar already labels it "Orders" (plural); proformas + invoices is the natural interpretation for a field sales app.

**Verification step:** Confirm with owner: Orders = proformas + invoices, or just invoices?

---

## Suspected Components

### Component: Orders Livewire Component (`app/Livewire/App/Orders.php`)

| Attribute      | Detail                                                                         |
| -------------- | ------------------------------------------------------------------------------ |
| Type           | UI module (Livewire component)                                                 |
| File / path    | `app/Livewire/App/Orders.php`                                                  |
| Responsibility | Fetch, filter, paginate, and render all proformas and invoices for current rep |
| Confidence     | High (grade-A — stub exists)                                                   |

**Why suspected:** Evidence 1 — this is the component that must be fully implemented.

**Blast radius:**

- New properties: `typeFilter` (all/proformas/invoices), `statusFilter`, `dateFrom`, `dateTo`, `search`
- Query: union of `ProformaInvoice::where('user_id', auth()->id())` + `Invoice::where('user_id', auth()->id())`
- Must use `x-ds-skeleton`, `x-ds-empty`, pull-to-refresh
- Tests: feature test for filters, pagination, document rendering

---

### Component: Orders Blade View (`resources/views/livewire/app/orders.blade.php`)

| Attribute      | Detail                                                        |
| -------------- | ------------------------------------------------------------- |
| Type           | UI view                                                       |
| File / path    | `resources/views/livewire/app/orders.blade.php`               |
| Responsibility | Render document list with filter bar, document cards, actions |
| Confidence     | High                                                          |

**Why suspected:** Evidence 1 — current view is empty stub.

**Blast radius:**

- Filter bar: type (All/Proformas/Invoices), status select, date range, search
- Document card: type icon (receipt/invoice), document number, customer name, date, amount, status badge
- Actions per card: View PDF (`/pdf/proforma/{id}` or `/pdf/invoice/{id}`), Share WhatsApp (`wa.me` link), Convert to Invoice (for proformas)
- Skeleton loading, empty state with `x-ds-empty`

---

### Component: Tab Bar + All Rep Pages

| Attribute      | Detail                                                                               |
| -------------- | ------------------------------------------------------------------------------------ |
| Type           | Cross-cutting                                                                        |
| Responsibility | Add `<x-tab-bar active="orders" />` to Orders page and ensure all pages have tab bar |

**Blast radius:** Same as M4 cross-cutting fix.

---

### Component: Translations

| Attribute      | Detail                                      |
| -------------- | ------------------------------------------- |
| Type           | Localization                                |
| Responsibility | New keys for document filter UI and actions |

**Blast radius:** Add: `all_documents`, `filter_by_type`, `view_pdf`, `share_whatsapp`, `convert_to_invoice`.

---

## Related Requirements

| Requirement                                                       | Source      | Status                                          |
| ----------------------------------------------------------------- | ----------- | ----------------------------------------------- |
| REQ-CMP-4 bottom tabs (Home · Visits · Customers · Orders · More) | PRD v1.1 §2 | **Violated** (Orders page is stub)              |
| REQ-RPT-1 cross-role visibility of proformas/invoices             | PRD v1.1 §1 | **Violated** (rep cannot see own documents)     |
| REQ-CMP-7 WhatsApp share of proforma/invoice PDF                  | PRD v1.1 §2 | At Risk (pattern exists but not on Orders page) |

---

## Recommended Action

**Planning Response:** Option A — Create a Fix Story

### Option A — Create a Fix Story

| Field                     | Value                                                                                                                                                                                                                                                                  |
| ------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Epic                      | Issue backlog #4 (B3 visits/orders tabs)                                                                                                                                                                                                                               |
| Story title               | Orders tab + rep documents list with PDF/WhatsApp actions                                                                                                                                                                                                              |
| As a                      | Sales rep                                                                                                                                                                                                                                                              |
| I want                    | A dedicated Orders tab showing all my issued proformas and invoices with PDF view and WhatsApp share                                                                                                                                                                   |
| So that                   | I can reference, re-share, and manage my documents without asking the admin                                                                                                                                                                                            |
| Suggested AC 1            | Bottom tab bar shows 5 tabs in correct order. `<x-tab-bar active="orders" />` included on `/app/orders` page.                                                                                                                                                          |
| Suggested AC 2            | `/app/orders` renders a paginated list of all proformas and invoices issued by the current rep. Each document card shows: type icon (proforma/invoice), document number, customer name, date, total amount, status badge (color-coded per status).                     |
| Suggested AC 3            | Filter bar: document type (All/Proformas/Invoices), status multi-select, date range picker, search input (document number/customer name).                                                                                                                              |
| Suggested AC 4            | Actions per document: "View PDF" (link to `/pdf/proforma/{id}` or `/pdf/invoice/{id}`), "Share via WhatsApp" (wa.me link with document summary), "Convert to Invoice" (for proformas in submitted/amended status, navigates to Sales Flow).                            |
| Suggested AC 5            | Skeleton loading, empty state with `x-ds-empty` (icon: document-stack, message: "No documents yet — create your first invoice"), pull-to-refresh.                                                                                                                      |
| Suggested AC 6            | All strings bilingual AR/EN, RTL-correct. Status badges use semantic colors.                                                                                                                                                                                           |
| Suggested AC 7            | Feature tests: filter by type/status/date, pagination, PDF link correctness, WhatsApp share link format, convert action navigation.                                                                                                                                    |
| Suspected files / modules | `app/Livewire/App/Orders.php`, `resources/views/livewire/app/orders.blade.php`, `app/Models/ProformaInvoice.php`, `app/Models/Invoice.php`, `app/Http/Controllers/App/PdfController.php`, `lang/en/app.php`, `lang/ar/app.php`, `tests/Feature/Rep/OrdersListTest.php` |
| Investigation reference   | `bmad-output/investigation-orders-tab-rep-documents-list-2026-07-19.md`                                                                                                                                                                                                |

---

## Open Questions

1. **Document count:** How many documents per rep typically? If >100, need infinite scroll or aggressive pagination. If <50, simple pagination suffices.

2. **Convert to Invoice flow:** Should "Convert to Invoice" navigate to `/app/sell` with pre-filled cart, or trigger conversion server-side and show the resulting invoice? The current Sales Flow has no "import from proforma" path.

3. **Proforma expiry:** Should expired proformas be shown on the Orders page? If so, show a visual indicator (grayed out, "Expired" badge)?

4. **Payment status on invoice cards:** Should invoice cards show partial payment progress (amount paid vs total)? The `payments` table has `invoice_id` — can calculate.

---

## Update History

| Version | Date       | Summary                         |
| ------- | ---------- | ------------------------------- |
| 1.0     | 2026-07-19 | Initial investigation case file |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
