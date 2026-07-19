# UI standards sweep: apply DS components + fix accessibility/RTL findings

## Overview

The design-system kit (`x-ds-modal`, `x-ds-skeleton`, `x-ds-empty`, `x-ds-button`) exists but has **zero usages**; money actions use native `wire:confirm` browser dialogs instead of bilingual consequence-stating modals; and a guidelines review found recurring mechanical defects across the rep views.

## Scope

**Included:** the findings below + replacing native confirms on money/stock actions with `x-ds-modal`; skeleton loaders and `x-ds-empty` on every list; six-state `x-ds-button` implementation.
**Excluded:** visual redesign, admin Filament theming (dark mode = separate small task), new pages.

## Findings to fix (file:line)

- `layouts/app.blade.php:6` + `guest.blade.php:6` — theme-color mismatch with background
- `layouts/app.blade.php:32` — physical `margin-right` in RTL banner
- `layouts/app.blade.php:9` — static title; add per-page titles
- `guest.blade.php:11` — IBM Plex Sans Arabic declared but never loaded (add preload + font-display: swap)
- `home.blade.php:44` — clickable div → `<a>`; Space key unhandled
- `home.blade.php:82` — hardcoded date format → localized
- `home.blade.php:55`, `customers.blade.php:24` — `target="_blank"` without `rel="noopener"`
- `visit-flow.blade.php:97`, `sales-flow.blade.php:25` — `×` dismiss buttons missing aria-label
- `visit-flow.blade.php:144` — followUpNote textarea unlabeled
- `visit-flow.blade.php:36` — draft `setInterval` never cleared (leak on navigation)
- `visit-flow.blade.php:151` — signature canvas needs fallback/keyboard alternative note
- `sales-flow.blade.php:32,57,91,95` — labels not associated with inputs (add ids + for)
- `sales-flow.blade.php:45,64`, `quotation-flow.blade.php:25` — `text-left` → `text-start` (RTL)
- `add-customer.blade.php:62` — duplicate submit binding (wire:click + wire:submit)
- `add-customer.blade.php:11` — silent GPS denial; surface a warning state
- `quotation-flow.blade.php:59` — label missing `for`; `:64` two competing primary buttons
- `collect-payment.blade.php:44` — amount `min="0.01"`; focus success heading after submit
- money/stat numbers — `font-variant-numeric: tabular-nums`

## Acceptance Criteria

- [ ] Every money/stock action confirms via bilingual `x-ds-modal` stating the exact consequence
- [ ] Every list shows skeleton while loading and `x-ds-empty` with action when empty
- [ ] All findings above fixed; `grep -rn "text-left\|wire:confirm" resources/views/livewire` clean
- [ ] RTL smoke test passes at 320px

## Priority

Score 4.0 — mechanical, high polish-per-hour; do alongside feature issues.

## Dependencies

- **Blocks:** B0 gate, B8-03 visual QA; **Blocked by:** #1

## Implementation Size

- **Estimated effort:** Medium (2–3 days)
