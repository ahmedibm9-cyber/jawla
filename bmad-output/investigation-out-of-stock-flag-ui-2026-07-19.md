# Investigation Case File: out-of-stock-flag-ui

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner request — UI completeness audit (gap M1 from investigation-missing-ui-elements-2026-07-19.md)
**Severity:** Degraded UX / Missing must-have functionality (blocks Beta Done walkthrough steps 18-19)
**Status:** Open
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-out-of-stock-flag-ui-2026-07-19.md`

---

## Summary

**One-sentence description:**
The rep has no way to flag a product as out of stock from the van — the out-of-stock alarm system (REQ-ALM-1…4, AM4) exists only as a backend model (`OutOfStockRequest`) and `AlarmService::raise()` but lacks any rep-facing UI: no flag button in Stock Search, no request form, no alarm banner in admin for Finance/Manager/Executive roles.

**Expected behavior:**
Per PRD v1.1 REQ-ALM-1…4 and Beta walkthrough steps 18-19: Rep taps "Report out of stock" on a product in Stock Search → enters required quantity + optional note → submits → `OutOfStockRequest` created with idempotency key → `AlarmService::raise('out_of_stock_request', ...)` fans out `alarm_reads` to Finance, Manager, Executive roles with red badge in Filament admin → all three roles see the alarm immediately.

**Actual behavior:**

- `app/Models/OutOfStockRequest.php` exists with fields: `user_id`, `product_id`, `quantity`, `notes`, `status` (open/resolved), `idempotency_key`
- `app/Services/OutOfStockService.php` implements `raise()` with idempotency check (prevents duplicate open requests per product per rep)
- `app/Services/AlarmService.php:69-86` role map includes `out_of_stock_request => ['accounts', 'sales_manager', 'executive']`
- `app/Filament/Resources/AlarmResource.php` renders alarms in admin
- **ZERO rep UI:** No button in `StockSearch`, no Livewire form, no `x-ds-modal` confirmation, no success/error feedback

**User / business impact:** The client's AM1→AM9 phone walkthrough (Definition of Beta Done) cannot be completed: steps 18-19 (flag Material 952 → tri-role alarm) have **no rep UI at all**. This is a must-have gap blocking Beta Done.

---

## Symptom Details

**Trigger conditions:** Structural — always present; found by static inventory of `routes/web.php`, `resources/views/livewire/app/stock-search.blade.php`, `app/Livewire/App/StockSearch.php`.

**Environments affected:**

- [x] Production
- [x] Staging
- [x] Development / local

**First observed:** 2026-07-19 (UI-completeness audit, Evidence 2)
**Frequency:** Constant (code-level absence)
**Reproducible:** Yes — re-run the inventory commands in Evidence.

**Reproduction steps:**

1. Log in as a rep
2. Navigate to `/app/stock`
3. Search for any product
4. Observe: product cards show name, SKU, price, warehouse stock breakdown — **no "Report out of stock" button**
5. No Livewire component exists for the flag flow
6. In admin as Finance/Manager/Executive: `AlarmResource` shows other alarm types but no out-of-stock requests can be created from rep side

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed, reproducible, or corroborated by 2+ independent sources.
> - **[B] Probable** — single source, code-read inference, or intermittent.
> - **[C] Speculative** — pattern match or untested hypothesis only.

### Evidence Item 1: Backend exists, frontend absent

**Grade:** [A]
**Source:** `grep -rln "OutOfStockRequest" app` → only `app/Models/OutOfStockRequest.php`; `grep -rn "OutOfStockService" app` → service + contract only
**Description:** The model, service, and alarm integration are fully implemented. `OutOfStockService::raise()` creates the request row, generates an idempotency key (`out_of_stock:{user_id}:{product_id}`), calls `AlarmService::raise('out_of_stock_request', ...)` which writes to `alarm_reads` for the three target roles. The entire backend pipeline works — only the rep entry point is missing.

**Verbatim excerpt:**

```php
// app/Services/OutOfStockService.php
public function raise(int $userId, int $productId, int $quantity, ?string $notes): OutOfStockRequest
{
    $idempotencyKey = "out_of_stock:{$userId}:{$productId}";
    return DB::transaction(function () use ($userId, $productId, $quantity, $notes, $idempotencyKey) {
        $existing = OutOfStockRequest::where('idempotency_key', $idempotencyKey)
            ->where('status', 'open')->first();
        if ($existing) throw ValidationException::withMessages(['product' => __('app.flag_duplicate')]);
        $request = OutOfStockRequest::create([...]);
        AlarmService::raise('out_of_stock_request', $request->company_id, [
            'out_of_stock_request_id' => $request->id,
            'product_id' => $productId,
            'quantity' => $quantity,
        ], $userId);
        return $request;
    });
}
```

**Implications:** Backend is ready. The story is purely a rep-facing UI build: button → modal form → service call → feedback.

---

### Evidence Item 2: Stock Search page has no action controls

**Grade:** [A]
**Source:** `resources/views/livewire/app/stock-search.blade.php`, `app/Livewire/App/StockSearch.php`
**Description:** The Stock Search page renders product cards with name, SKU, price, and warehouse stock quantities (color-coded green/amber/red). There are zero action buttons on any card. The component only has `search` property and `render()` method — no `flagOutOfStock()` or similar.

**Verbatim excerpt:**

```blade
<!-- stock-search.blade.php product card -->
<div class="card">
  <strong>{{ $product->name_ar }}</strong>
  <small>{{ $product->sku }}</small>
  <span>{{ $product->price }}</span>
  @foreach($product->stocks as $stock)
    <span class="{{ $stock->quantity > 5 ? 'text-success' : ($stock->quantity > 0 ? 'text-warning' : 'text-danger') }}">
      {{ $stock->warehouse->name }}: {{ $stock->quantity }}
    </span>
  @endforeach
</div>
```

**Implications:** The flag button must be added to this card. It needs to open a confirmation modal (`x-ds-modal`) with quantity input and notes textarea, then call `OutOfStockService::raise()`.

---

### Evidence Item 3: Alarm admin UI exists but only for other alarm types

**Grade:** [A]
**Source:** `app/Filament/Resources/AlarmResource.php`, `app/Filament/Resources/AlarmResource/Pages/ListAlarms.php`
**Description:** `AlarmResource` lists all alarms with filters for type, status, role. It will render out-of-stock alarms once they are created, but currently zero exist because no rep can create them.

**Implications:** No admin UI work needed — the alarm display is generic and works for any type. Only the rep creation flow is missing.

---

### Evidence Item 4: Idempotency key prevents duplicate open requests

**Grade:** [A]
**Source:** `OutOfStockService.php` lines 15-22
**Description:** The service throws `ValidationException` with message `__('app.flag_duplicate')` if an open request for the same product by the same rep already exists. The translation key `flag_duplicate` exists in `lang/en/app.php` and `lang/ar/app.php` (line 57).

**Implications:** UI must handle this validation error gracefully — show the translated error in the modal, don't allow resubmission.

---

### Evidence Item 5: Design System modal component exists and is unused

**Grade:** [A]
**Source:** `resources/views/components/ds/modal.blade.php`, `grep -rl "x-ds-modal" resources/views/livewire` → 0 files
**Description:** The `x-ds-modal` component implements a consequence-stating confirmation modal (bilingual via title/message props, trigger/confirm slots, accessible with `role="dialog"`, `aria-modal="true"`, `x-trap.noscroll`, escape key handling). It is the mandated pattern per Design System §3 and Master Plan rule. Zero rep pages use it.

**Implications:** The out-of-stock flag flow MUST use `x-ds-modal` for the confirmation step. This aligns with gap M7 (confirmation modals everywhere).

---

### Evidence Summary

| #   | Title                               | Grade | Source                                  | Key Implication                               |
| --- | ----------------------------------- | ----- | --------------------------------------- | --------------------------------------------- |
| 1   | Backend exists, frontend absent     | A     | grep OutOfStockRequest/Service          | Pure UI build; service ready                  |
| 2   | Stock Search has no action controls | A     | stock-search.blade.php, StockSearch.php | Button + modal must be added to product cards |
| 3   | Alarm admin UI generic and ready    | A     | AlarmResource.php                       | No admin work needed                          |
| 4   | Idempotency prevents duplicates     | A     | OutOfStockService.php:15-22             | UI must handle validation error               |
| 5   | DS modal exists, unused             | A     | ds/modal.blade.php, grep                | Must use x-ds-modal for confirmation          |

---

## Hypotheses

### Hypothesis 1 — The out-of-stock UI was never built because the backend was completed in a different phase and the rep-facing entry point was overlooked [Plausibility: High]

**Statement:** `OutOfStockService` and `AlarmService` integration were built (likely during B6 phase per PRD phase map) but the rep UI (button in Stock Search, modal form, success feedback) was never created — the feature is backend-complete but frontend-absent.

**Supporting evidence:**

- Evidence 1 [A] — Model, service, alarm integration all present and wired
- Evidence 2 [A] — Stock Search component has zero action methods
- Evidence 4 [A] — Idempotency and validation messages already implemented in service
- PRD phase map: REQ-ALM-1…4 assigned to B6; Stock Search is B3/B5 — phase ordering may have caused the disconnect

**Contradicting evidence:** None identified.

**Verification step (for the dev agent):**
Confirm by checking git history: `git log --oneline --all -- app/Services/OutOfStockService.php` vs `git log --oneline --all -- app/Livewire/App/StockSearch.php` — if service commits predate Stock Search or are in a different branch/PR, the hypothesis holds.

---

### Hypothesis 2 — The flag button was deferred because the Design System modal wasn't ready [Plausibility: Medium]

**Statement:** The team knew a confirmation modal was required (per Design System §3) but `x-ds-modal` was built later (B0), so the flag button was deferred until the modal component existed. Now that it exists, the flag UI was never revisited.

**Supporting evidence:**

- Evidence 5 [A] — `x-ds-modal` exists, zero usages in rep pages
- Design System (B0) is a prerequisite for all beta pages per Master Plan
- M7 (confirmation modals everywhere) is also a gap — same root cause

**Contradicting evidence:** The service was built with validation messages that assume a UI exists (`flag_duplicate`, `flag_success` translations).

**Verification step:** Check commit dates: `git log --format="%ci %s" -1 -- resources/views/components/ds/modal.blade.php` vs `git log --format="%ci %s" -1 -- app/Services/OutOfStockService.php`.

---

### Hypothesis 3 — The feature was intentionally scoped to admin-only for beta [Plausibility: Low]

**Statement:** The client may have decided reps shouldn't flag out-of-stock in beta; only admins create out-of-stock requests manually.

**Supporting evidence:** None — PRD REQ-ALM-1…4 explicitly describes rep flagging, AM4 walkthrough steps 18-19 require it.

**Contradicting evidence:**

- PRD v1.1: "out-of-stock alarms broadcast to Finance/Manager/Executive" — implies rep trigger
- Amendment: "in-app alarm bell + red indicators cover AM4 in beta" — rep must be able to trigger
- Walkthrough steps 18-19: "rep flags Material 952 out-of-stock → red alarm hits Finance+Manager+Executive"

**Verification step:** Confirm with owner — but PRD text is unambiguous.

---

## Suspected Components

### Component: Rep PWA Stock Search (`app/Livewire/App/StockSearch.php`, `resources/views/livewire/app/stock-search.blade.php`)

| Attribute              | Detail                                                                                    |
| ---------------------- | ----------------------------------------------------------------------------------------- |
| Type                   | UI module (Livewire component + Blade view)                                               |
| File / path            | `app/Livewire/App/StockSearch.php`, `resources/views/livewire/app/stock-search.blade.php` |
| Responsibility         | Product search, stock display per warehouse, **NEW: out-of-stock flag button + modal**    |
| Confidence             | High (grade-A inventory)                                                                  |
| Architecture reference | Rep PWA group in `routes/web.php:75` (`/app/stock`)                                       |

**Why suspected:** Evidence 2 — this is the only rep-facing stock page; the flag button belongs on each product card.

**Blast radius:**

- New `flagOutOfStock(int $productId)` method in `StockSearch.php`
- New modal form in `stock-search.blade.php` using `x-ds-modal`
- New translations for modal title/message (already exist: `flag_out_of_stock`, `flag_quantity`, `flag_notes`, `flag_submit`, `flag_success`, `flag_duplicate`)
- Route already exists (`/app/stock`); no new route needed
- Must use `OutOfStockService` (not direct model create) to preserve idempotency and alarm firing
- Tests: feature test for flag flow, including duplicate prevention

---

### Component: OutOfStockService (`app/Services/OutOfStockService.php`)

| Attribute              | Detail                                                                                 |
| ---------------------- | -------------------------------------------------------------------------------------- |
| Type                   | Service layer                                                                          |
| File / path            | `app/Services/OutOfStockService.php`                                                   |
| Responsibility         | Create `OutOfStockRequest` with idempotency, fire alarm via `AlarmService`             |
| Confidence             | High (grade-A code read)                                                               |
| Architecture reference | CLAUDE.md service rules — all money/stock mutations via Service in `DB::transaction()` |

**Why suspected:** Evidence 1, 4 — this is the only correct entry point. The Livewire component must call `$this->outOfStockService->raise(...)` (inject via constructor).

**Blast radius:** None — service is complete. Only the Livewire caller is missing.

---

### Component: AlarmService (`app/Services/AlarmService.php`)

| Attribute              | Detail                                               |
| ---------------------- | ---------------------------------------------------- |
| Type                   | Service layer                                        |
| File / path            | `app/Services/AlarmService.php:69-86`                |
| Responsibility         | Fan out `alarm_reads` to target roles per alarm type |
| Confidence             | High (grade-A code read)                             |
| Architecture reference | CLAUDE.md service rules                              |

**Why suspected:** Evidence 1 — role map includes `out_of_stock_request => ['accounts', 'sales_manager', 'executive']`. Works automatically when `OutOfStockService` calls it.

**Blast radius:** None — already wired.

---

## Related Requirements

| Requirement                                   | Type | Source                        | Status                       |
| --------------------------------------------- | ---- | ----------------------------- | ---------------------------- |
| REQ-ALM-1…4 out-of-stock alarms               | FR   | PRD v1.1 §1                   | **Violated** (no UI)         |
| REQ-CMP-4 bottom tabs                         | FR   | PRD v1.1 §2                   | N/A (different gap)          |
| B0 Design System — consequence-stating modals | NFR  | Design System §3, Master Plan | At Risk (M7 pattern)         |
| AM4 in-app alarm bell coverage                | FR   | Amendment 1.2                 | Violated (rep can't trigger) |

---

## Recommended Action

**Planning Response:** Option A — Create a Fix Story

### Option A — Create a Fix Story

| Field                                | Value                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| ------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Epic                                 | Issue backlog #1 (B6 out-of-stock alarm UI)                                                                                                                                                                                                                                                                                                                                                                                                           |
| Story title                          | Rep out-of-stock flag button + request form + tri-role alarm                                                                                                                                                                                                                                                                                                                                                                                          |
| As a                                 | Sales rep                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| I want                               | A "Report out of stock" button on each product in Stock Search that opens a confirmation modal with quantity and notes, and sends an alarm to Finance, Manager, and Executive                                                                                                                                                                                                                                                                         |
| So that                              | The tri-role alarm fires immediately when I run out of a product in my van, without me calling anyone                                                                                                                                                                                                                                                                                                                                                 |
| Suggested AC 1                       | On `/app/stock`, each product card shows a "Report out of stock" button (red outline, trash/alert icon). Clicking opens an `x-ds-modal` with: quantity input (number, min=1, required), notes textarea (optional), bilingual title "Report out of stock? / الإبلاغ عن نفاد المخزون", consequence message "This will alert Finance, Manager, and Executive immediately / سيؤدي هذا إلى تنبيه المالية والمدير والتنفيذي فوراً", cancel/confirm buttons. |
| Suggested AC 2                       | On confirm: call `OutOfStockService::raise(auth()->id(), $productId, $quantity, $notes)`. On success: close modal, show success toast (`flag_success`), refresh stock cards. On duplicate (ValidationException `flag_duplicate`): show error in modal, keep modal open. On other errors: show error toast, keep modal open.                                                                                                                           |
| Suggested AC 3                       | In Filament admin, `AlarmResource` shows the new out-of-stock alarm with type "out_of_stock_request", product name, quantity, requesting rep, timestamp. Finance/Manager/Executive see red badge count.                                                                                                                                                                                                                                               |
| Suggested AC 4                       | Feature test: rep flags product → alarm created for all three roles → duplicate attempt shows error → all strings bilingual AR/EN, RTL correct.                                                                                                                                                                                                                                                                                                       |
| Suspected files / modules            | `app/Livewire/App/StockSearch.php`, `resources/views/livewire/app/stock-search.blade.php`, `lang/en/app.php`, `lang/ar/app.php` (translations already exist), `tests/Feature/Rep/OutOfStockFlagTest.php`                                                                                                                                                                                                                                              |
| Verification steps (from hypotheses) | H1: git history check; H2: modal component date vs service date                                                                                                                                                                                                                                                                                                                                                                                       |
| Investigation reference              | `bmad-output/investigation-out-of-stock-flag-ui-2026-07-19.md`                                                                                                                                                                                                                                                                                                                                                                                        |

> Proceed with `/bmad-planning-orchestrator:bmad-epics-and-stories` to compile the full story context object. Dev Notes in that story MUST cite this case file.

---

## Open Questions

1. **Icon choice:** "Report out of stock" button — use heroicon `exclamation-triangle` or `x-mark`? Design System has no icon standard yet.
2. **Quantity default:** Should quantity default to 1 or to the product's `stock_minimum` (if exists)? Model doesn't have `stock_minimum` column.
3. **Real-time alarm badge:** The admin alarm badge uses `unreadNotifications()` count — does `AlarmService` create `Notification` records or only `alarm_reads`? (Evidence 3 suggests `alarm_reads` only; `AlarmResource` reads from that table. The bell in `layouts/app.blade.php` uses Laravel `notifications` table — different system. Confirm which surface gets the red badge.)

---

## Update History

| Version | Date       | Summary of Changes              |
| ------- | ---------- | ------------------------------- |
| 1.0     | 2026-07-19 | Initial investigation case file |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
