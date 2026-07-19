# Investigation Case File: rep-ds-card-button-tooltip-adoption

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — REP-2 gap from `investigation-missing-ui-elements-2026-07-19.md` v2.0
**Severity:** Degraded UX / Design system inconsistency / Technical debt
**Status:** Open
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-rep-ds-card-button-tooltip-adoption-2026-07-19.md`

---

## Summary

**One-sentence description of the issue:**
Three design system components (`x-ds.card`, `x-ds.button`, `x-ds.tooltip`) exist in the codebase but have **zero usage** across all 16 REP Livewire pages. Pages use raw HTML (`class="card"`, `<button class="btn">`, no tooltips) instead, creating inconsistency and missing the loading-state wiring and accessibility benefits the DS components provide.

**Expected behavior:** Per D6 (accepted 2026-07-18) and B0-01/B0-02: all pages use the design system components for consistent styling, built-in loading states, and accessibility features.

**Actual behavior:**

- `x-ds.card` — 0/16 pages (all use raw `class="card"` or `class="card bg-..."`)
- `x-ds.button` — 0/16 pages (all use raw `<button class="btn btn-*">` or `<a class="btn btn-*">`)
- `x-ds.tooltip` — 0/16 pages (no tooltips anywhere in REP app)

**User / business impact:** Inconsistent visual styling across pages; buttons lack automatic loading-state wiring (`wire:loading.attr="disabled"`); no tooltip system for icon-only buttons (accessibility gap); maintenance burden of raw HTML vs centralized component updates.

---

## Symptom Details

**Trigger conditions:** Structural — all 16 REP pages use raw HTML instead of DS components.

**Environments affected:**

- [x] Production
- [x] Staging
- [x] Development / local

**First observed:** 2026-07-19 (UI-completeness audit, Evidence REP-2)
**Frequency:** Constant (code-level absence)
**Reproducible:** Yes

**Reproduction steps:**

1. Open any REP page (Home, Visits, Customers, Stock, Orders, Notifications, Quotations, Sales, Collect Payment, Returns, Expenses, Complaints, Add Customer, Purchase Offer, More, Visit Flow)
2. Inspect card elements — all use raw `class="card"` without `x-ds.card` wrapper
3. Inspect buttons — all use raw `<button class="btn btn-*">` without `x-ds.button`
4. Hover icon-only buttons (maps link, clear signature, remove item) — no tooltip appears

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed, reproducible.
> - **[B] Probable** — code-read inference.
> - **[C] Speculative** — pattern match or untested hypothesis only.

### Evidence Item 1: DS Components Exist But Have Zero Usage in REP App

**Grade:** [A]
**Source:** `grep -rn "x-ds\.card\|x-ds\.button\|x-ds\.tooltip" resources/views/livewire/app/` → 0 matches each

**DS components available (`resources/views/components/ds/`):**

| Component      | File                | Features                                                                                                                                               |
| -------------- | ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `x-ds.card`    | `card.blade.php`    | Props: `header`, `footer`; consistent `rounded-xl border border-border bg-surface shadow-sm`                                                           |
| `x-ds.button`  | `button.blade.php`  | Props: `variant`, `type`, `target`; auto `wire:loading.attr="disabled"` when `target` given; 6-state CSS (default/hover/focus/active/disabled/loading) |
| `x-ds.tooltip` | `tooltip.blade.php` | Props: `content`, `position`; hover/focus reveal; RTL-aware positioning                                                                                |

**Implications:** The components are built and working (used in admin/Filament pages or newer REP pages like notifications for skeleton/empty), but the REP app migration never happened for these three.

---

### Evidence Item 2: Raw HTML Patterns Used Instead Across All 16 Pages

**Grade:** [A]
**Source:** Code review of all 16 `resources/views/livewire/app/*.blade.php` files

**Card usage (raw `class="card"`):**

- All 16 pages use `class="card"` or `class="card bg-*"` directly
- Examples: `home.blade.php` (tasks), `visits.blade.php` (visit list), `orders.blade.php` (document cards), `sales-flow.blade.php` (cart items), etc.

**Button usage (raw `<button class="btn btn-*">`):**

- All 16 pages use raw button HTML
- Examples: `sales-flow.blade.php:127` submit button, `collect-payment.blade.php:65` trigger button, `log-return.blade.php:68` trigger, `log-expense.blade.php:47` trigger
- Modal trigger buttons manually wire `wire:loading.remove` / `wire:loading` spans
- Confirm buttons manually wire `wire:loading.attr="disabled"`

**Tooltip usage (none):**

- Icon-only buttons have no tooltip: maps links (home, customers), signature clear (visit-flow), remove item buttons (sales-flow, log-return), tab bar icons
- No `aria-label` or tooltip for icon-only actions on several buttons

**Implications:** Complete inconsistency — the DS kit was built but only `skeleton`, `empty`, `modal` were adopted. The three components providing the most DX value (loading states, consistent styling, tooltips) were skipped.

---

### Evidence Item 3: D6 Decision Accepted — "Use existing DS components"

**Grade:** [A]
**Source:** `bmad-output/decision-log.md` (2026-07-18), D6 row

**Verbatim excerpt:**

```
| D6 | Use existing `<x-ds-modal>`, `<x-ds-skeleton>`, `<x-ds-empty>` components | Design system components exist but are unused; raw HTML used instead | Accepted |
```

**Description:** D6 explicitly calls out the problem and accepts the fix, but only names `modal`, `skeleton`, `empty` — omitting `card`, `button`, `tooltip`. This suggests the decision was scoped to the three components that block the Beta walkthrough (M7 confirmation modals, G1 skeleton/empty), not a full DS adoption mandate.

**Implications:** D6 was a partial fix for the most critical gaps. The three remaining DS components fell through the cracks.

---

### Evidence Item 3: Notifications Page Shows Partial Adoption Pattern

**Grade:** [A]
**Source:** `resources/views/livewire/app/notifications.blade.php`

**Description:** Notifications page (built most recently) uses:

- ✅ `x-ds.skeleton` (loading)
- ✅ `x-ds.empty` (empty state)
- ❌ `x-ds.card` (uses raw `class="card"`)
- ❌ `x-ds.button` (uses raw `<button class="btn">`)
- ❌ `x-ds.tooltip` (none)

**Implications:** Even the newest page only adopted the three "critical" DS components. The team has not internalized full DS adoption as a standard.

---

### Evidence Item 4: Button Component Has Valuable Built-in Features Being Missed

**Grade:** [A]
**Source:** `resources/views/components/ds/button.blade.php`

**Features of `x-ds.button` not available with raw HTML:**

1. **Automatic loading state wiring:** `target` prop → auto adds `wire:loading.attr="disabled" wire:target="{{ $target }}"` + swaps slot content to "Saving…"
2. **Consistent 6-state CSS:** default, hover, focus-visible, active, disabled, loading — all from `.btn` CSS
3. **Variant system:** `primary`, `outline`, `danger`, etc. via `variant` prop
4. **Accessibility:** Proper `type` attribute, focus styles

**Raw HTML pattern on REP pages (e.g., `sales-flow.blade.php:127-131`):**

```blade
<button type="button" wire:click="submit" wire:loading.attr="disabled" class="btn btn-primary w-full">
    {{ __('app.confirm') }}
</button>
```

Repeated manually on every modal trigger and confirm button across 4 pages with modals + others.

**Implications:** Every button manually implements what `x-ds.button` does automatically. High maintenance burden, inconsistency risk.

---

### Evidence Summary

| #   | Title                                        | Grade | Source                  | Key Implication                            |
| --- | -------------------------------------------- | ----- | ----------------------- | ------------------------------------------ |
| 1   | DS card/button/tooltip exist, 0 usage in REP | A     | grep + component files  | Components built but not adopted           |
| 2   | All 16 pages use raw HTML patterns           | A     | 16 Blade files reviewed | Complete inconsistency                     |
| 3   | D6 accepted but scoped to 3/6 components     | A     | decision-log.md         | Partial fix, 3 components left             |
| 4   | Notifications page shows partial adoption    | A     | notifications.blade.php | Even newest page skips 3 components        |
| 5   | Button component has auto-loading wiring     | A     | ds/button.blade.php     | Manual wiring on every button is tech debt |

---

## Hypotheses

### Hypothesis 1 — D6 was scoped to "critical path" components only; card/button/tooltip deemed non-blocking [Plausibility: High]

**Statement:** The UI control module audit (D1-D8) prioritized components that block the Beta walkthrough: `modal` (M7 confirmations), `skeleton`/`empty` (G1 loading/empty). `card`, `button`, `tooltip` were considered "nice to have" and left for a later polish pass that never happened.

**Supporting evidence:**

- D6 text: "Design system components exist but are unused; raw HTML used instead" — general statement but only 3 components named [A]
- M7 (confirmation modals) and G1 (skeleton/empty) were must-have/good-to-have gaps; card/button/tooltip not in gap matrix [A]
- All D1-D8 items that block Beta Done were implemented; these three were not on the critical path [A]

**Contradicting evidence:**

- D6 rationale says "raw HTML used instead" generally — could imply all DS components
- D5 (autocomplete) also accepted but unimplemented — pattern of partial execution

**Verification step (for the dev agent):**

- Check if any meeting notes or audit artifacts explicitly scoped D6 to 3 components
- Ask owner: was full DS adoption intended, or just the 3 critical ones?

---

### Hypothesis 2 — Migration effort underestimated; raw HTML "works" so never prioritized [Plausibility: High]

**Statement:** The team saw that raw `class="card"` and `<button class="btn">` work functionally, so the migration to `x-ds.card`/`x-ds.button` was repeatedly deferred as "cosmetic" despite the DX and maintenance benefits.

**Supporting evidence:**

- Raw HTML is functionally correct — no user-facing bugs
- Migration is view-layer only (no backend changes) — easy to defer
- 16 pages × ~5-10 cards/buttons each = 80-160 component replacements — non-trivial effort
- D5 (autocomplete) also deferred — pattern of deferring view-layer polish

**Contradicting evidence:**

- `x-ds.button` provides automatic loading-state wiring that raw buttons manually implement — this IS functional, not just cosmetic
- `x-ds.tooltip` addresses accessibility gap for icon-only buttons — functional gap

**Verification step (for the dev agent):**

- Count manual `wire:loading.remove` / `wire:loading` spans across REP pages — each is a bug risk if missed
- Count icon-only buttons without `aria-label` or tooltip — accessibility violations

---

### Hypothesis 3 — No enforcement mechanism (CI gate, PR checklist) for DS component usage [Plausibility: Medium]

**Statement:** Unlike `wire:confirm` (which D6 implies should be replaced by `x-ds.modal`), there's no automated check that `class="card"` must be `x-ds.card` or `<button class="btn">` must be `x-ds.button`.

**Supporting evidence:**

- Decision D6 mentions "CI grep gate for `wire:confirm` on money actions" but not for DS component usage
- No ESLint/Blade lint rule for `x-ds.*` adoption
- New pages (notifications, visits, orders) adopted `skeleton`/`empty`/`modal` but not `card`/`button`/`tooltip` — no gate enforced it

**Contradicting evidence:**

- Team manually adopted the 3 critical components consistently — culture of manual adoption exists

**Verification step (for the dev agent):**

- Check CI config (`.github/workflows/`) for any component usage checks
- Search for "x-ds" in lint/prettier configs

---

## Suspected Components

### Component: DS Card Component (`x-ds.card`)

| Attribute              | Detail                                                                                                         |
| ---------------------- | -------------------------------------------------------------------------------------------------------------- |
| Type                   | Blade component                                                                                                |
| File / path            | `resources/views/components/ds/card.blade.php`                                                                 |
| Responsibility         | Consistent card styling (`rounded-xl border border-border bg-surface shadow-sm`), optional header/footer slots |
| Confidence             | High (grade-A evidence of existence + zero usage)                                                              |
| Architecture reference | PRD v1.1 §2 REQ-CMP-5; Amendment §5 Design Standards                                                           |

**Why suspected:** Evidence 1, 2 — component exists, 0 usage, all pages use raw `class="card"`.

**Blast radius:**

- Replace `class="card"` (and `class="card bg-*"`) with `<x-ds.card>` on ~80-100 card instances across 16 pages
- Header/footer slots where pages currently use nested divs for card headers
- No logic changes — pure view migration

---

### Component: DS Button Component (`x-ds.button`)

| Attribute              | Detail                                                                                                                                 |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| Type                   | Blade component                                                                                                                        |
| File / path            | `resources/views/components/ds/button.blade.php`                                                                                       |
| Responsibility         | Consistent button styling, automatic loading-state wiring (`wire:loading.attr="disabled"` + content swap), variant system, 6-state CSS |
| Confidence             | High (grade-A evidence of existence + zero usage + high-value features missed)                                                         |
| Architecture reference | PRD v1.1 §2 REQ-CMP-5; Amendment §5 Design Standards                                                                                   |

**Why suspected:** Evidence 1, 2, 4 — component exists with valuable auto-loading wiring, 0 usage, manual wiring repeated everywhere.

**Blast radius:**

- Replace ~50-80 raw button elements across 16 pages with `<x-ds.button>`
- Remove manual `wire:loading.remove`/`wire:loading` spans and `wire:loading.attr="disabled"` — handled by component via `target` prop
- Add `target="actionName"` to buttons that trigger Livewire actions
- Translation key for "Saving…" already exists (`app.saving`)

---

### Component: DS Tooltip Component (`x-ds.tooltip`)

| Attribute              | Detail                                                                          |
| ---------------------- | ------------------------------------------------------------------------------- |
| Type                   | Blade component                                                                 |
| File / path            | `resources/views/components/ds/tooltip.blade.php`                               |
| Responsibility         | Hover/focus tooltip for icon-only buttons, RTL-aware positioning, accessibility |
| Confidence             | High (grade-A evidence of existence + zero usage + accessibility gap)           |
| Architecture reference | PRD v1.1 §2 REQ-CMP-5; WCAG 2.1 AA (icon-only buttons need accessible names)    |

**Why suspected:** Evidence 1, 2 — component exists, 0 usage, multiple icon-only buttons lack accessible labels.

**Blast radius:**

- Wrap icon-only buttons: maps links (home, customers), signature clear (visit-flow), remove item buttons (sales-flow, log-return), tab bar icons
- Add `content` prop with translated tooltip text
- Improves WCAG compliance for 2.5.3 Label in Name, 1.3.5 Identify Input Purpose

---

### Component: 16 REP Page Views (Migration Targets)

| Attribute              | Detail                                            |
| ---------------------- | ------------------------------------------------- |
| Type                   | UI views (batch migration)                        |
| File / path            | All 16 `resources/views/livewire/app/*.blade.php` |
| Responsibility         | Migrate raw HTML → DS components                  |
| Confidence             | High (grade-A evidence of violation on all pages) |
| Architecture reference | routes/web.php:67-88                              |

**Why suspected:** Evidence 2 — every page uses raw HTML patterns.

**Blast radius:**

- Card migration: ~80-100 instances
- Button migration: ~50-80 instances + remove manual loading wiring
- Tooltip addition: ~15-20 icon-only actions
- Translation keys for tooltip content, button loading text (already exists)
- Testing: visual regression, loading states, RTL, accessibility

---

## Related Requirements

| Requirement                                           | Type          | Source                       | Status                                               |
| ----------------------------------------------------- | ------------- | ---------------------------- | ---------------------------------------------------- |
| D6 — Use existing DS components                       | Decision      | decision-log.md (2026-07-18) | **Partially Violated** (3/6 components)              |
| REQ-CMP-5 — Standard UI states everywhere             | NFR           | PRD v1.1 §2                  | **Violated** (inconsistent cards/buttons)            |
| Amendment §5 — Design standards binding for beta      | Standard      | Amendment                    | **Violated** (no tooltip, inconsistent buttons)      |
| WCAG 2.1 AA — Icon-only buttons need accessible names | Accessibility | WCAG                         | **Violated** (no tooltip/aria-label on icon buttons) |

---

## Recommended Action

**Planning Response:** Option A — Create a Fix Story (root cause scoped: migrate 16 pages to 3 DS components)

### Option A — Create a Fix Story

| Field                                | Value                                                                                                                                     |
| ------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------- |
| Epic                                 | Issue backlog #7 (UI states & accessibility sweep) / D6 completion                                                                        |
| Story title                          | Adopt DS card, button, tooltip components across all 16 REP pages                                                                         |
| As a                                 | Sales rep / Developer                                                                                                                     |
| I want                               | All REP pages to use the design system components                                                                                         |
| So that                              | Styling is consistent, buttons have automatic loading states, icon-only actions are accessible                                            |
| Suggested AC 1                       | All ~80-100 `class="card"` instances replaced with `<x-ds.card>` (header/footer slots where applicable)                                   |
| Suggested AC 2                       | All ~50-80 raw buttons replaced with `<x-ds.button>`; manual `wire:loading` spans removed; `target` prop used for automatic loading state |
| Suggested AC 3                       | All ~15-20 icon-only actions wrapped with `<x-ds.tooltip>` with translated content; WCAG 2.1 AA compliant                                 |
| Suggested AC 4                       | Visual regression test: all pages render identically (card borders, button states, tooltip positioning RTL/LTR)                           |
| Suggested AC 5                       | No raw `class="card"` or `<button class="btn">` in REP views (grep check in CI)                                                           |
| Suspected files / modules            | Modified: 16 Blade views in `resources/views/livewire/app/`                                                                               |
| Verification steps (from hypotheses) | H1: Confirm D6 scope with owner; H2: Count manual loading spans removed; H3: Add CI grep gate for DS component usage                      |
| Investigation reference              | `bmad-output/investigation-rep-ds-card-button-tooltip-adoption-2026-07-19.md`                                                             |

---

## Open Questions

1. **Migration strategy:** One big PR (16 files) or phased (4-5 pages per PR)? Phased reduces review burden but risks inconsistency during transition.

2. **Button `target` prop:** Requires knowing the Livewire action name for each button. Need to audit all buttons to map `wire:click="actionName"` → `target="actionName"`.

3. **Tooltip content translation:** New translation keys needed for ~15-20 tooltip messages. Follow existing `app.*` namespace pattern.

4. **CI gate:** Add `grep -r "class=\"card\"" resources/views/livewire/app/ && exit 1` (and similar for button) to CI to prevent regression?

5. **DS component updates:** If DS components change (e.g., card border radius), all 16 pages auto-update — this is the MAIN benefit. Confirm component APIs are stable.

---

## Update History

| Version | Date       | Summary of Changes              |
| ------- | ---------- | ------------------------------- |
| 1.0     | 2026-07-19 | Initial investigation case file |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
