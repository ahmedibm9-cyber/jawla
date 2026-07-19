# Investigation Case File: confirmation-modals-money-stock-actions

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — gap M7 from `investigation-missing-ui-elements-2026-07-19.md`
**Severity:** Degraded UX / Financial risk (13 CRITICAL failure scenarios trace to missing confirmations)
**Status:** Open
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-confirmation-modals-money-stock-actions-2026-07-19.md`

---

## Summary

**One-sentence description:**
The `x-ds-modal` component (consequence-stating, bilingual, accessible confirmation modal) exists in the design system but is used **zero times** across all 13 rep pages. Every financial/stock mutation (invoice creation, payment collection, return logging, expense logging, quotation price confirmation, purchase offer submission, visit report submission) fires directly with no confirmation step — using only native `wire:confirm` browser dialogs which show no consequence text, are easily dismissed, and have no RTL/bilingual support.

**Expected behavior:** Per Design System §3 and Master Plan rule: Every destructive or financial action must show a `x-ds-modal` confirmation dialog stating the exact consequence, bilingually, with the safe option (Cancel) as the default and the confirm button as the secondary action.

**Actual behavior:**

- **0 pages** use `x-ds-modal`
- **8 pages** use `wire:confirm` (native browser dialog): Sales Flow (line 126), Collect Payment (line 64), Log Return, Log Expense
- **3 pages** use no confirmation at all: Quotation confirm/create proforma, Purchase Offer, Visit report submit
- **1 page** uses `wire:confirm`: Home (task completion)
- 13 CRITICAL failure scenarios identified in Reverse Brainstorming directly trace to this gap

**User / business impact:** Every rep creates invoices, collects payments, and logs returns/expenses daily. One accidental tap = corrupted stock, wrong customer balance, wrong cashbox balance, revenue leakage. The backend services already support `cancel()` methods (PaymentService, ExpenseService, ReturnRecordService, InvoiceService) — the UI just doesn't expose them.

---

## Symptom Details

**Trigger conditions:** Structural — every financial/stock mutation on every rep page.

**Environments affected:**

- [x] Production
- [x] Staging
- [x] Development / local

**First observed:** 2026-07-19 (UI-completeness audit, Evidence 4)
**Frequency:** Constant (code-level absence)
**Reproducible:** Yes

**Reproduction steps:**

1. Log in as rep
2. Navigate to any page with a submit action (Sales, Payment, Return, Expense, etc.)
3. Observe: submit fires directly or shows `wire:confirm` native dialog
4. No `x-ds-modal` with consequence text, bilingual support, or accessible confirmation

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed, reproducible, or corroborated by 2+ independent sources.
> - **[B] Probable** — single source, code-read inference, or intermittent.
> - **[C] Speculative** — pattern match or untested hypothesis only.

### Evidence Item 1: DS modal component exists with full implementation

**Grade:** [A]
**Source:** `resources/views/components/ds/modal.blade.php`
**Verbatim excerpt:**

```blade
<div x-data="{ open: false }}" {{ $attributes->merge(['style' => 'overscroll-behavior:contain']) }}>
    <div x-on:click="open = true">{{ $trigger }}</div>

    <div x-show="open" x-cloak x-trap.noscroll="open"
         style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5);padding:16px"
         role="dialog" aria-modal="true" aria-label="{{ $title }}"
         x-on:keydown.escape.window="open = false">
        <div class="card" style="max-width:360px;width:100%;margin:0" x-on:click.outside="open = false">
            <h3 class="m-0 mb-2">{{ $title }}</h3>
            <p class="m-0 mb-4 text-text-secondary">{{ $message }}</p>
            <div class="flex gap-2">
                <button type="button" class="btn btn-outline flex-1" x-on:click="open = false">
                    {{ $cancelLabel ?? __('app.cancel') }}
                </button>
                <div class="flex-1" x-on:click="open = false">{{ $confirm }}</div>
            </div>
        </div>
    </div>
</div>
```

**Description:** The modal is fully implemented: Alpine.js state management, `x-trap.noscroll` for accessibility, `role="dialog"`, `aria-modal="true"`, `aria-label` from title, escape key dismissal, click-outside-to-close, trigger/confirm slots, bilingual title/message via props, cancel button with configurable label (defaults to `__('app.cancel')`).

**Implications:** Component is ready. Just needs to be applied to all 8 financial pages.

---

### Evidence Item 2: 8 pages use native wire:confirm or no confirmation

**Grade:** [A]
**Source:** `grep -rn "wire:confirm\|submit()" resources/views/livewire/app/`
**Pages with `wire:confirm`:**

1. `sales-flow.blade.php:126` — `wire:confirm="{{ __('app.confirm_invoice_title') }}"`
2. `collect-payment.blade.php:64` — `wire:confirm="{{ __('app.confirm_collect_title') }}"`
3. `log-return.blade.php` — `wire:confirm="{{ __('app.confirm_return_title') }}"`
4. `log-expense.blade.php` — `wire:confirm="{{ __('app.confirm_expense_title') }}"`
5. `home.blade.php` — `wire:confirm` on task completion button

**Pages with NO confirmation:**

1. `quotation-flow.blade.php` — "Confirm Price" and "Create Proforma" fire directly
2. `submit-purchase-offer.blade.php` — Submit fires directly
3. `visit-flow.blade.php` — "Submit Report" fires directly

**Description:** Native `wire:confirm` shows a browser-native dialog with only the title text (no message body, no RTL support, no consequence text). It cannot be styled, localized properly, or made accessible.

**Implications:** All 8 pages must replace `wire:confirm` with `<x-ds-modal>` that shows: title, consequence message (what will happen), cancel button, confirm button.

---

### Evidence Item 3: Confirmation translations already exist for most pages

**Grade:** [A]
**Source:** `lang/en/app.php`, `lang/ar/app.php`
**Keys present:**

- `confirm_collect_title` / `confirm_collect_msg`
- `confirm_expense_title` / `confirm_expense_msg`
- `confirm_return_title` / `confirm_return_msg`
- `confirm_invoice_title` / `confirm_invoice_msg`
- `cancel`, `confirm`

**Missing keys:**

- `confirm_price_title` / `confirm_price_msg` — quotation price confirmation
- `confirm_proforma_title` / `confirm_proforma_msg` — proforma creation
- `confirm_purchase_title` / `confirm_purchase_msg` — purchase offer
- `confirm_report_title` / `confirm_report_msg` — visit report submission

**Implications:** Most translations exist. Need 4 new title/msg pairs.

---

### Evidence Item 4: Backend cancel() methods exist for undo capability

**Grade:** [A]
**Source:** `grep -rn "cancel()" app/Services/`
**Services with cancel methods:**

- `PaymentService::cancel()` — reverses payment, restores invoice balance, reverses cashbox
- `ExpenseService::cancel()` — reverses expense, restores cashbox
- `ReturnRecordService::cancel()` — reverses return, restores stock, reverses customer balance
- `InvoiceService::cancel()` — cancels invoice, restores stock, reverses customer balance

**Description:** Every financial service has a `cancel()` method that performs the compensating transaction. None are exposed in the rep UI.

**Implications:** After confirmation modal shows and user confirms, show a 30-60 second undo toast with "Undo" button that calls the service's `cancel()` method. This is the second half of the fix.

---

### Evidence Item 5: No undo capability on any page

**Grade:** [A]
**Source:** All rep Livewire components — zero calls to `cancel()` methods
**Description:** After any financial submission, the success screen shows with no undo option. The rep must contact admin to reverse any mistake.

**Implications:** Undo toast is a separate concern from confirmation modals, but should be batched in the same epic.

---

### Evidence Summary

| #   | Title                                       | Grade | Source                         | Key Implication             |
| --- | ------------------------------------------- | ----- | ------------------------------ | --------------------------- |
| 1   | DS modal fully implemented, zero usage      | A     | ds/modal.blade.php, grep       | Component ready, just apply |
| 2   | 8 pages use wire:confirm or no confirmation | A     | grep wire:confirm, blade files | All 8 need replacement      |
| 3   | Confirmation translations mostly exist      | A     | lang/*/app.php                 | Need 4 new title/msg pairs  |
| 4   | Backend cancel() methods exist              | A     | grep cancel() app/Services     | Undo is feasible            |
| 5   | No undo on any page                         | A     | All components                 | Add undo toast to all 8     |

---

## Hypotheses

### Hypothesis 1 — The DS modal was built in B0 but never enforced by a phase gate [Plausibility: High]

**Statement:** B0 (Design System) created `x-ds-modal` as the mandated confirmation pattern. But no phase gate verified that all financial pages use it. Pages shipped with native `wire:confirm` because the gate didn't check for DS component usage.

**Supporting evidence:**

- Evidence 1 [A] — modal exists, zero usages
- Evidence 2 [A] — all pages use wire:confirm or no confirmation
- B0 is listed as prerequisite for all beta pages in Master Plan, but no grep gate exists

**Contradicting evidence:** None.

**Verification step:** Check CI pipeline — is there a grep gate for `wire:confirm`? (Almost certainly not.)

---

### Hypothesis 2 — Native wire:confirm was considered "good enough" during development [Plausibility: Medium]

**Statement:** Developers used `wire:confirm` as a quick confirmation mechanism and intended to upgrade to `x-ds-modal` later, but the upgrade was never prioritized.

**Supporting evidence:**

- `wire:confirm` shows the title text (e.g., "Create invoice?") — provides some confirmation
- But no consequence text ("An invoice of X will be created, quantities deducted...")

**Contradicting evidence:** The Design System explicitly mandates consequence-stating modals; wire:confirm doesn't meet this standard.

**Verification step:** Check git blame on wire:confirm additions — were they before or after x-ds-modal creation?

---

### Hypothesis 3 — The undo toast was deferred because service cancel() methods weren't all implemented [Plausibility: Medium]

**Statement:** The undo toast requires calling service `cancel()` methods. If not all services had cancel() at the time of page development, the undo was deferred globally.

**Supporting evidence:**

- Evidence 4 [A] — all 4 financial services now have cancel()
- But the undo was never implemented even after all cancel() methods landed

**Contradicting evidence:** The services were likely built together with their cancel methods.

**Verification step:** Check git history for when each service's cancel() was added.

---

## Suspected Components

### Component: Sales Flow (`resources/views/livewire/app/sales-flow.blade.php`, `app/Livewire/App/SalesFlow.php`)

| Attribute      | Detail                                                         |
| -------------- | -------------------------------------------------------------- |
| Type           | UI module                                                      |
| File / path    | `resources/views/livewire/app/sales-flow.blade.php:126`        |
| Responsibility | Invoice creation with stock deduction, customer balance update |
| Confidence     | High                                                           |

**Why suspected:** Evidence 2 — uses `wire:confirm` with `confirm_invoice_title` but no consequence message.

**Blast radius:** Replace `wire:confirm` with `x-ds-modal` showing: title, "An invoice of {total} will be created, quantities deducted from your van stock, and the customer balance updated." + cancel/confirm buttons. Add undo toast after success.

---

### Component: Collect Payment (`resources/views/livewire/app/collect-payment.blade.php`, `app/Livewire/App/CollectPayment.php`)

| Attribute      | Detail                                                        |
| -------------- | ------------------------------------------------------------- |
| Type           | UI module                                                     |
| File / path    | `resources/views/livewire/app/collect-payment.blade.php:64`   |
| Responsibility | Payment collection, cashbox update, invoice balance reduction |
| Confidence     | High                                                          |

**Why suspected:** Evidence 2 — uses `wire:confirm` with `confirm_collect_title` but no consequence message.

**Blast radius:** Replace `wire:confirm` with `x-ds-modal` showing: title, "The entered amount will be recorded as collected from the customer and added to your cash box." + cancel/confirm. Add undo toast after success (call `PaymentService::cancel()`).

---

### Component: Log Return (`resources/views/livewire/app/log-return.blade.php`, `app/Livewire/App/LogReturn.php`)

| Attribute      | Detail                                                        |
| -------------- | ------------------------------------------------------------- |
| Type           | UI module                                                     |
| File / path    | `resources/views/livewire/app/log-return.blade.php`           |
| Responsibility | Return logging, stock restoration, customer balance reduction |
| Confidence     | High                                                          |

**Why suspected:** Evidence 2 — uses `wire:confirm` with `confirm_return_title`.

**Blast radius:** Replace with `x-ds-modal`. Add undo toast (call `ReturnRecordService::cancel()`).

---

### Component: Log Expense (`resources/views/livewire/app/log-expense.blade.php`, `app/Livewire/App/LogExpense.php`)

| Attribute      | Detail                                               |
| -------------- | ---------------------------------------------------- |
| Type           | UI module                                            |
| File / path    | `resources/views/livewire/app/log-expense.blade.php` |
| Responsibility | Expense logging, cashbox deduction                   |
| Confidence     | High                                                 |

**Why suspected:** Evidence 2 — uses `wire:confirm` with `confirm_expense_title`.

**Blast radius:** Replace with `x-ds-modal`. Add undo toast (call `ExpenseService::cancel()`).

---

### Component: Quotation Flow (`resources/views/livewire/app/quotation-flow.blade.php`)

| Attribute      | Detail                                                  |
| -------------- | ------------------------------------------------------- |
| Type           | UI module                                               |
| File / path    | `resources/views/livewire/app/quotation-flow.blade.php` |
| Responsibility | Price confirmation, proforma creation                   |
| Confidence     | High                                                    |

**Why suspected:** Evidence 2 — no confirmation on "Confirm Price" or "Create Proforma".

**Blast radius:** Add `x-ds-modal` for both actions. Need new translation keys.

---

### Component: Visit Flow (`resources/views/livewire/app/visit-flow.blade.php`)

| Attribute      | Detail                                                 |
| -------------- | ------------------------------------------------------ |
| Type           | UI module                                              |
| File / path    | `resources/views/livewire/app/visit-flow.blade.php`    |
| Responsibility | Visit report submission (permanent, affects analytics) |
| Confidence     | High                                                   |

**Why suspected:** Evidence 2 — "Submit Report" fires directly with no confirmation.

**Blast radius:** Add `x-ds-modal` with summary of all report fields before submit.

---

### Component: Submit Purchase Offer (`resources/views/livewire/app/submit-purchase-offer.blade.php`)

| Attribute      | Detail                                                         |
| -------------- | -------------------------------------------------------------- |
| Type           | UI module                                                      |
| File / path    | `resources/views/livewire/app/submit-purchase-offer.blade.php` |
| Responsibility | Purchase offer submission                                      |
| Confidence     | High                                                           |

**Why suspected:** Evidence 2 — no confirmation.

**Blast radius:** Add `x-ds-modal` with summary of offer details.

---

### Component: Home (`resources/views/livewire/app/home.blade.php`)

| Attribute      | Detail                                        |
| -------------- | --------------------------------------------- |
| Type           | UI module                                     |
| File / path    | `resources/views/livewire/app/home.blade.php` |
| Responsibility | Task completion                               |
| Confidence     | Medium                                        |

**Why suspected:** Evidence 2 — uses `wire:confirm` on task completion.

**Blast radius:** Replace with `x-ds-modal`. Low priority (task completion is low-risk).

---

### Component: Translations (`lang/en/app.php`, `lang/ar/app.php`)

| Attribute      | Detail                               |
| -------------- | ------------------------------------ |
| Type           | Localization                         |
| Responsibility | New confirmation title/message pairs |

**Blast radius:** Add 4 new title/msg pairs (see Evidence 3). Update existing keys if consequence text is missing.

---

## Related Requirements

| Requirement                                                                                                                      | Source                         | Status                    |
| -------------------------------------------------------------------------------------------------------------------------------- | ------------------------------ | ------------------------- |
| Design System §3 — confirmation modals for every destructive/financial action                                                    | Design System, Master Plan     | **Violated** (zero usage) |
| CLAUDE.md — "Every destructive or financial action requires a confirmation modal that states the exact consequence, bilingually" | CLAUDE.md non-negotiable rules | **Violated**              |
| B0-01/B0-02 — standard UI states kit                                                                                             | PRD v1.1 §2                    | At Risk                   |

---

## Recommended Action

**Planning Response:** Option C — Escalate to planning first (cross-cutting issue spanning 8 pages + undo toast)

**Rationale:** This is a systemic gap touching 8 pages, 4 services, 2 translation files, and a shared component pattern. It should be planned as a single epic with a shared component approach, not 8 individual stories.

**Recommended next skill:** `/bmad-planning-orchestrator:bmad-architecture` (Update intent) — to design the shared confirmation modal pattern and undo toast architecture before cutting stories.

**If planning is skipped:** Option A — Create a single Fix Story covering all 8 pages with a shared `ConfirmAction` Livewire trait/component.

---

## Open Questions

1. **Shared component vs. inline modal:** Should each page embed its own `x-ds-modal` instance, or should there be a shared `ConfirmAction` Livewire trait that manages the modal state centrally? The trait approach reduces duplication but increases complexity.

2. **Undo toast duration:** 30 seconds? 60 seconds? What if the user navigates away during the undo window? Should the toast persist across navigation or be page-local?

3. **Undo implementation:** Should the undo button call a Livewire method on the same component (re-rendering the page) or should it be a full-page redirect to a "undo" route? Livewire method is simpler but couples the undo to the component lifecycle.

4. **Confirmation for LOW-risk actions:** Task completion (Home) — should this use x-ds-modal or is wire:confirm acceptable? CLAUDE.md says "every destructive or financial action" — task completion is neither. Wire:confirm may be acceptable here.

---

## Update History

| Version | Date       | Summary                         |
| ------- | ---------- | ------------------------------- |
| 1.0     | 2026-07-19 | Initial investigation case file |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
