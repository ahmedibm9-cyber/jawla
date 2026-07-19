# Investigation Case File: skeleton-loaders-empty-states

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — gap G1 from `investigation-missing-ui-elements-2026-07-19.md`
**Severity:** Degraded UX / Performance perception (B0-02, REQ-CMP-5)
**Status:** Open
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-skeleton-loaders-empty-states-2026-07-19.md`

---

## Summary

**One-sentence description:**
The design system components `x-ds-skeleton` and `x-ds-empty` are fully implemented but used on **zero** of the 13 rep pages. Every page shows a blank white screen during the Livewire round-trip (2-5s on 3G) and text-only empty states with no illustrations or actionable suggestions.

**Expected behavior:** Per B0-02 and REQ-CMP-5:

- Every list/table page uses `<x-ds-skeleton>` loading placeholders matching the content layout
- Every empty state uses `<x-ds-empty>` with icon, message, and next-step action
- Skeleton rows animate with pulse effect
- Empty states are friendly, bilingual, and guide the rep to the next action

**Actual behavior:**

- `x-ds-skeleton` component exists at `resources/views/components/ds/skeleton.blade.php` — renders animated skeleton rows
- `x-ds-empty` component exists at `resources/views/components/ds/empty.blade.php` — renders icon + message + action slot
- **Zero rep pages** use either component
- All pages show blank white screen during load
- Empty states are plain text (e.g., "No customers found", "No orders yet") with no icon or action

**User / business impact:** Reps on slow mobile networks (3G, rural areas, warehouses) see blank white screens for 2-5 seconds per page load, perceiving the app as broken. Empty states provide no guidance on what to do next.

---

## Symptom Details

**Trigger conditions:** Every page load on every rep page (structural).

**Environments affected:** All (code-level absence).

**First observed:** 2026-07-19 (UI-completeness audit, Evidence 4)
**Frequency:** Every page load, every session
**Reproducible:** Yes

**Reproduction steps:**

1. Log in as rep on slow connection (3G throttled)
2. Navigate to any page — observe blank white screen until data loads
3. Navigate to any empty page — observe plain text with no icon/action

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed, reproducible.
> - **[B] Probable** — code-read inference.

### Evidence Item 1: DS skeleton component exists, zero usage

**Grade:** [A]
**Source:** `resources/views/components/ds/skeleton.blade.php`, `grep -rl "x-ds-skeleton" resources/views/livewire/app/` → 0 files
**Description:** `x-ds-skeleton` accepts `height` prop, renders animated skeleton card with pulse effect. Used nowhere in rep pages. Only `notifications.blade.php` uses it (for the notifications list).

**Implications:** Apply to all 13 pages. Skeleton rows should match the actual content layout (card rows, list items, form fields).

---

### Evidence Item 2: DS empty component exists, zero usage

**Grade:** [A]
**Source:** `resources/views/components/ds/empty.blade.php`, `grep -rl "x-ds-empty" resources/views/livewire/app/` → 0 files (except notifications)
**Description:** `x-ds-empty` accepts `icon`, `message`, and `action` slot. Renders centered icon + message + action button. Only `notifications.blade.php` uses it. All other pages use plain text empty states.

**Implications:** Apply to all pages with lists: Home (no visits), Customers (no customers), Stock (no results), Quotations (no quotations), Orders (no orders), Returns (no returns), Expenses (no expenses).

---

### Evidence Item 3: Notifications page already uses both components

**Grade:** [A]
**Source:** `resources/views/livewire/app/notifications.blade.php`
**Verbatim excerpt:**

```blade
<div wire:loading.delay class="space-y-2 mb-3" aria-hidden="true">
    <x-ds-skeleton height="72px" />
    <x-ds-skeleton height="72px" />
</div>

@if($notifications->isEmpty())
    <x-ds.empty icon="heroicon-o-bell" :message="__('app.no_notifications')">
        <x-slot:action>
            <a href="/app" class="btn btn-primary no-underline">{{ __('app.back_home') }}</a>
        </x-slot:action>
    </x-ds.empty>
@endif
```

**Description:** Notifications page demonstrates the correct pattern: skeleton loaders inside `wire:loading.delay` wrapper, empty state with icon + message + action button. This pattern should be replicated on all pages.

**Implications:** The pattern is proven and working. Just needs to be applied systematically.

---

### Evidence Summary

| #   | Title                             | Grade | Source                      | Key Implication              |
| --- | --------------------------------- | ----- | --------------------------- | ---------------------------- |
| 1   | Skeleton exists, 0 usage          | A     | ds/skeleton.blade.php, grep | Apply to all 13 pages        |
| 2   | Empty state exists, 0 usage       | A     | ds/empty.blade.php, grep    | Apply to all list pages      |
| 3   | Notifications page proves pattern | A     | notifications.blade.php     | Replicate this exact pattern |

---

## Hypotheses

### Hypothesis 1 — B0 kit was built but no phase gate enforced usage [Plausibility: High]

**Statement:** The skeleton and empty state components were created during B0 (Design System phase) but no CI gate, review checklist, or phase completion check verified that every page uses them. Pages shipped without them.

**Supporting evidence:** Evidence 1, 2 [A] — components exist, zero usage; Evidence 3 [A] — only the most recently built page (notifications) uses them, suggesting the pattern was adopted late.

**Contradicting evidence:** None.

**Verification step:** Check CI pipeline for any grep gate on `x-ds-skeleton` or `x-ds-empty` usage.

---

### Hypothesis 2 — Pages were built before the DS components existed [Plausibility: Medium]

**Statement:** Most rep pages (Home, Visit Flow, Customers, etc.) were built in earlier phases before B0 created the skeleton/empty components. The components were never retrofitted.

**Supporting evidence:**

- Home page was likely built first (it's the landing page)
- Notifications page (most recent, uses components) confirms late adoption

**Contradicting evidence:** Some pages (Sales, Returns, Expenses) may have been built after B0.

**Verification step:** Check git log for page creation dates vs B0 component creation dates.

---

## Suspected Components

### Component: All 13 Rep Page Views (`resources/views/livewire/app/*.blade.php`)

| Attribute      | Detail                                           |
| -------------- | ------------------------------------------------ |
| Type           | UI views (batch)                                 |
| Responsibility | Add skeleton loading + empty states to all pages |
| Confidence     | High                                             |

**Why suspected:** Evidence 1, 2, 3 — all pages need the same pattern applied.

**Blast radius:**

- Add `<x-ds-skeleton>` inside `wire:loading.delay` wrapper on every page
- Replace text-only empty states with `<x-ds-empty>`
- No backend changes needed
- Pattern: copy from `notifications.blade.php`

---

## Related Requirements

| Requirement                                | Source                     | Status                    |
| ------------------------------------------ | -------------------------- | ------------------------- |
| B0-02 standard UI states (skeleton, empty) | Design System, PRD v1.1 §2 | **Violated** (zero usage) |
| REQ-CMP-5 standard UI states everywhere    | PRD v1.1 §2                | **Violated**              |

---

## Recommended Action

**Planning Response:** Option A — Create a single batch Fix Story (small effort, ~2 days)

| Field                   | Value                                                                                                                                                                                                                                     |
| ----------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Epic                    | Issue backlog #1 (B0 compliance sweep)                                                                                                                                                                                                    |
| Story title             | Apply skeleton loaders + empty states to all 13 rep pages                                                                                                                                                                                 |
| As a                    | Sales rep                                                                                                                                                                                                                                 |
| I want                  | Every page to show skeleton loading placeholders while data loads and friendly empty states with actions when lists are empty                                                                                                             |
| So that                 | I never see a blank white screen and always know what to do next                                                                                                                                                                          |
| Suggested AC 1          | All 13 rep pages use `<x-ds-skeleton>` inside `wire:loading.delay` with skeleton rows matching the content layout (e.g., 3 skeleton cards for visit list, 2 for task list).                                                               |
| Suggested AC 2          | All empty states use `<x-ds-empty>` with appropriate icon, bilingual message, and action button (e.g., Home: "No visits today" + "Start Work"; Customers: "No customers" + "Add Customer"; Stock: "No results" + "Try different search"). |
| Suggested AC 3          | Pattern matches `notifications.blade.php` exactly: skeleton inside `wire:loading.delay` wrapper, empty state inside `@forelse` empty branch.                                                                                              |
| Investigation reference | `bmad-output/investigation-skeleton-loaders-empty-states-2026-07-19.md`                                                                                                                                                                   |

---

## Open Questions

1. **Skeleton row count:** How many skeleton rows to show? Match expected content? Fixed 3 rows? Use the notifications page pattern (2 rows).

2. **Empty state icons:** Which heroicons for each page? Need a consistent icon mapping: Home→calendar, Customers→users, Stock→cube, Quotations→document-text, Orders→receipt, Returns→arrow-uturn-left, Expenses→currency-dollar, Complaints→exclamation-triangle.

---

## Update History

| Version | Date       | Summary                         |
| ------- | ---------- | ------------------------------- |
| 1.0     | 2026-07-19 | Initial investigation case file |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
