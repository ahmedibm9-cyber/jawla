# Investigation Case File: rep-native-select-to-autocomplete

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — REP-4 gap from `investigation-missing-ui-elements-2026-07-19.md` v2.0
**Severity:** Degraded UX / Spec violation (D5 accepted decision unimplemented)
**Status:** Open
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-rep-native-select-to-autocomplete-2026-07-19.md`

---

## Summary

**One-sentence description of the issue:**
Four REP pages use native `<select>` dropdowns for customer/product/supplier selection with 50-100 items, violating the accepted D5 decision (2026-07-18) to replace native selects with searchable autocomplete for 50+ item lists on mobile touch screens.

**Expected behavior:** Per D5: all customer/product/supplier dropdowns with 50+ items use a searchable autocomplete component (type-to-filter, keyboard navigable, mobile-friendly, RTL-aware).

**Actual behavior:** Four REP pages use native `<select>` elements:

- `collect-payment.blade.php` — customer + invoice dropdowns (50-100 customers)
- `log-return.blade.php` — customer + product dropdowns (50-100 each)
- `log-complaint.blade.php` — customer dropdown (50-100 customers)
- `submit-purchase-offer.blade.php` — product + supplier dropdowns (50-100 each)

**User / business impact:** On mobile touch screens, native selects with 50+ items are unusable — tiny tap targets, no search, excessive scrolling. Reps in the field cannot efficiently select customers/products. D5 was explicitly accepted to fix this exact problem.

---

## Symptom Details

**Trigger conditions:** Structural — always present when these pages load with company data (50+ customers/products/suppliers).

**Environments affected:**

- [x] Production
- [x] Staging
- [x] Development / local

**First observed:** 2026-07-19 (UI-completeness audit, Evidence REP-4)
**Frequency:** Constant (code-level absence of autocomplete component)
**Reproducible:** Yes

**Reproduction steps:**

1. Log in as rep at a company with 50+ active customers
2. Navigate to `/app/collect-payment`
3. Tap customer dropdown — observe native `<select>` with 50+ options, no search
4. Repeat for `/app/returns` (customer + product), `/app/complaints` (customer), `/app/purchase-offer` (product + supplier)

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed, reproducible.
> - **[B] Probable** — code-read inference.
> - **[C] Speculative** — pattern match or untested hypothesis only.

### Evidence Item 1: D5 Decision Accepted But Unimplemented

**Grade:** [A]
**Source:** `bmad-output/decision-log.md` (2026-07-18), D5 row
**Description:** Decision D5 explicitly accepted: "Replace native `<select>` with searchable autocomplete — 50+ item selects are unusable on mobile touch screens." Status: Accepted.

**Verbatim excerpt:**

```
| D5 | Replace native `<select>` with searchable autocomplete | 50+ item selects are unusable on mobile touch screens | Accepted |
```

**Implications:** The decision exists and was accepted, but the autocomplete component was never created and the four pages were never migrated.

---

### Evidence Item 2: Four REP Pages Using Native `<select>` with 50-100 Items

**Grade:** [A]
**Source:** Code review of `resources/views/livewire/app/*.blade.php`

**Pages and dropdowns:**

| Page                              | Dropdown(s)                                          | Approx. Items                     | Code Location                                                                                        |
| --------------------------------- | ---------------------------------------------------- | --------------------------------- | ---------------------------------------------------------------------------------------------------- |
| `collect-payment.blade.php`       | Customer (line 22-27), Invoice (line 33-38)          | 50-100 customers, 0-50 invoices   | `select wire:model="customer_id"`, `select wire:model="invoice_id"`                                  |
| `log-return.blade.php`            | Customer (line 21-27), Product per item (line 41-46) | 50-100 customers, 50-100 products | `select wire:model="customer_id"`, `select wire:model="items.{{ $i }}.product_id"`                   |
| `log-complaint.blade.php`         | Customer (line 14-22)                                | 50-100 customers                  | `select id="customer_id" wire:model="customer_id"`                                                   |
| `submit-purchase-offer.blade.php` | Product (line 14-20), Supplier (line 24-31)          | 50-100 products, 10-50 suppliers  | `select id="product_id" wire:model="product_id"`, `select id="supplier_id" wire:model="supplier_id"` |

**Implications:** All four pages violate D5. The dropdowns are populated via Livewire queries limited to 50-100 items per company, putting them squarely in the "50+ items = unusable on mobile" category D5 was meant to address.

---

### Evidence Item 3: No Autocomplete Component Exists in Codebase

**Grade:** [A]
**Source:** `grep -rn "autocomplete\|typeahead\|searchable.*select" resources/views/ app/` → 0 matches for a reusable component

**Description:** No Blade component, Livewire component, or Alpine.js component implementing searchable autocomplete exists in the project. The DS components (`resources/views/components/ds/`) include only: `button, card, empty, modal, skeleton, tooltip`.

**Implications:** Fixing D5 requires building a new reusable autocomplete component first, then migrating the four pages.

---

### Evidence Item 4: Modern Pattern Exists Elsewhere But Not Autocomplete

**Grade:** [B]
**Source:** `resources/views/livewire/app/notifications.blade.php` uses `__()` translation keys and `x-ds.*` components, but no autocomplete pattern exists there either.

**Description:** The notifications page (built later) follows the modern translation/DS pattern but doesn't have a dropdown with 50+ items to demonstrate autocomplete.

**Implications:** The team knows the modern pattern but hasn't built the autocomplete component D5 requires.

---

### Evidence Summary

| #   | Title                                          | Grade | Source                  | Key Implication                       |
| --- | ---------------------------------------------- | ----- | ----------------------- | ------------------------------------- |
| 1   | D5 decision accepted                           | A     | decision-log.md         | Decision exists, migration never done |
| 2   | Four pages use native select with 50-100 items | A     | Blade templates         | Direct D5 violation on 4 pages        |
| 3   | No autocomplete component exists               | A     | Codebase grep           | Component must be built first         |
| 4   | Modern pattern exists elsewhere                | B     | notifications.blade.php | Team capable, just not applied here   |

---

## Hypotheses

### Hypothesis 1 — Autocomplete component was never built; D5 accepted but implementation deferred [Plausibility: High]

**Statement:** The D5 decision was accepted in the UI control module audit, but no one created the reusable autocomplete component, and the four pages were never revisited.

**Supporting evidence:**

- D5 status "Accepted" in decision-log [A]
- Zero autocomplete components in codebase [A]
- Four pages still use native `<select>` [A]

**Contradicting evidence:** None identified.

**Verification step (for the dev agent):**

- Search git log for any commits mentioning "autocomplete", "typeahead", or "searchable select" after 2026-07-18
- Check if any PR/commit references D5 implementation

---

### Hypothesis 2 — Team waited for a design-system autocomplete component that never landed [Plausibility: Medium]

**Statement:** The team expected a `x-ds.autocomplete` component to be added to the DS kit (like `x-ds.modal`, `x-ds.skeleton`), but it was never prioritized.

**Supporting evidence:**

- DS kit has 6 components but no autocomplete [A]
- Other DS components used consistently on newer pages (notifications, visits, orders) [A]
- D6 accepted same audit: "Use existing `x-ds-modal`, `x-ds-skeleton`, `x-ds-empty`" [A]

**Contradicting evidence:**

- No DS autocomplete in design docs or component directory
- D5 didn't specify "use DS component" — just "replace with searchable autocomplete"

**Verification step (for the dev agent):**

- Check design system docs (`docs/DESIGN_SYSTEM.md` or similar) for autocomplete spec
- Ask owner if DS autocomplete was planned

---

### Hypothesis 3 — Complexity of Livewire + Alpine autocomplete deferred to "later" [Plausibility: Medium]

**Statement:** Building a reusable autocomplete that works with Livewire wire:model, debounced search, keyboard navigation, and RTL is non-trivial, so it was silently deprioritized.

**Supporting evidence:**

- Livewire + Alpine autocomplete requires: debounced server search, result caching, keyboard nav (arrows, enter, escape), RTL support, click-outside-to-close, accessibility (ARIA)
- D3 (service worker) and D7 (photo capture) also deferred to later despite being accepted

**Contradicting evidence:**

- D5 was in the "UI Control Module Audit" with other items that WERE implemented (D1 modals, D2 tab bar, D3 SW, D4 cart recalc, D6 DS components, D7 photo, D8 undo)

**Verification step (for the dev agent):**

- Estimate effort: ~2-3 days for a solid Livewire autocomplete component
- Compare to actual velocity on other accepted D* items

---

## Suspected Components

### Component: New Autocomplete Component (to be created)

| Attribute              | Detail                                                                                                                                                                |
| ---------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Type                   | Livewire + Alpine.js reusable component                                                                                                                               |
| File / path            | `app/Livewire/Components/Autocomplete.php` + `resources/views/livewire/components/autocomplete.blade.php` (or Blade component `components/ds/autocomplete.blade.php`) |
| Responsibility         | Searchable, debounced, keyboard-navigable, RTL-aware dropdown replacement for 50+ item selects                                                                        |
| Confidence             | High (grade-A evidence of absence)                                                                                                                                    |
| Architecture reference | PRD v1.1 §2 REQ-CMP-5, REQ-CMP-9; Amendment §5 Design Standards                                                                                                       |

**Why suspected:** Evidence 3 — no autocomplete component exists; D5 explicitly requires it.

**Blast radius:**

- New component file(s)
- Migration of 4 pages × ~2 dropdowns each = ~8 dropdown replacements
- Translation keys for "Search…", "No results", loading states
- Must work with existing Livewire patterns (`wire:model`, `wire:loading`, validation)
- RTL support mandatory
- Accessibility: ARIA combobox pattern, keyboard navigation, click-outside

---

### Component: Four REP Pages (Migration Targets)

| Attribute              | Detail                                                                                                            |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------- |
| Type                   | UI views (batch migration)                                                                                        |
| File / path            | `collect-payment.blade.php`, `log-return.blade.php`, `log-complaint.blade.php`, `submit-purchase-offer.blade.php` |
| Responsibility         | Replace native `<select>` with new autocomplete component                                                         |
| Confidence             | High (grade-A evidence of violation)                                                                              |
| Architecture reference | routes/web.php:79, 82, 78, 84                                                                                     |

**Why suspected:** Evidence 2 — all four use native `<select>` with 50-100 items.

**Blast radius:**

- Blade view changes only (no Livewire component logic changes expected)
- Each dropdown: replace `<select>` with `<x-ds.autocomplete>` (or similar)
- Ensure `wire:model` binding works with new component
- Test RTL, mobile touch, keyboard nav, accessibility

---

## Related Requirements

| Requirement                                             | Type     | Source                       | Status                                 |
| ------------------------------------------------------- | -------- | ---------------------------- | -------------------------------------- |
| D5 — Replace native select with searchable autocomplete | Decision | decision-log.md (2026-07-18) | **Violated**                           |
| REQ-CMP-5 — Standard UI states everywhere               | NFR      | PRD v1.1 §2                  | **Violated** (no autocomplete)         |
| REQ-CMP-9 — Rep-app search on customers and products    | FR       | PRD v1.1 §2                  | **Violated** (no searchable dropdowns) |
| Amendment §5 — Design standards binding for beta        | Standard | Amendment                    | **Violated** (D5 unimplemented)        |

---

## Recommended Action

**Planning Response:** Option A — Create a Fix Story (root cause scoped: build autocomplete component + migrate 4 pages)

### Option A — Create a Fix Story

| Field                                | Value                                                                                                                                                                     |
| ------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Epic                                 | D5 completion / Mobile UX compliance                                                                                                                                      |
| Story title                          | Build searchable autocomplete component + migrate 4 REP pages                                                                                                             |
| As a                                 | Sales rep                                                                                                                                                                 |
| I want                               | Searchable dropdowns for customer/product/supplier selection                                                                                                              |
| So that                              | I can quickly find and select items on mobile without scrolling 50+ options                                                                                               |
| Suggested AC 1                       | Reusable autocomplete component created: debounced search (300ms), keyboard nav (arrows/enter/escape), click-outside-close, RTL, ARIA combobox                            |
| Suggested AC 2                       | All 4 pages migrated: collect-payment (2 dropdowns), log-return (2), log-complaint (1), submit-purchase-offer (2) — 8 dropdowns total                                     |
| Suggested AC 3                       | Works with Livewire `wire:model`, validation, `wire:loading` states                                                                                                       |
| Suggested AC 4                       | Mobile touch test: select 50th item in <3 taps; RTL test: Arabic labels render correctly                                                                                  |
| Suggested AC 5                       | Accessibility: ARIA combobox pattern, screen reader announces results count                                                                                               |
| Suspected files / modules            | New: `app/Livewire/Components/Autocomplete.php`, `resources/views/livewire/components/autocomplete.blade.php`; Modified: 4 Blade views in `resources/views/livewire/app/` |
| Verification steps (from hypotheses) | H1: Confirm D5 scope with owner; H2: Check design docs for autocomplete spec; H3: Estimate effort vs D1-D8 velocity                                                       |
| Investigation reference              | `bmad-output/investigation-rep-native-select-to-autocomplete-2026-07-19.md`                                                                                               |

---

## Open Questions

1. **Component API design:** Blade component (`<x-ds.autocomplete>`) or Livewire component (`<livewire:autocomplete>`)? Livewire component allows server-side search but adds overhead; Blade + Alpine allows client-side filtering of pre-loaded options. For 50-100 items, client-side filtering is fast enough and simpler. Which approach preferred?

2. **Search scope:** Debounced server search (new request per keystroke) vs. pre-load all options + client-side filter? For 50-100 items, pre-load is fine. For 1000+, server search needed. What's the expected max?

3. **Pre-load strategy:** Current pages load options via Livewire in `render()` (customers, products, suppliers). Should autocomplete pre-load all options on page mount, or fetch on focus? Pre-load = faster first search but more initial data.

4. **Invoice dropdown on collect-payment:** Depends on selected customer (dynamic). Autocomplete must support dependent dropdowns (clear invoice when customer changes).

5. **CI gate:** Add `grep -r "native-select\|<select[^>]*wire:model" resources/views/livewire/app/ && exit 1` to prevent regression?

6. **Design spec:** Any existing autocomplete design in Figma/design docs, or build to standard patterns (Tailwind + Heroicons)?

---

## Update History

| Version | Date       | Summary of Changes              |
| ------- | ---------- | ------------------------------- |
| 1.0     | 2026-07-19 | Initial investigation case file |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
