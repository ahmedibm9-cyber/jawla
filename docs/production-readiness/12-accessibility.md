# Accessibility

## Verdict

**FAIL/PARTIAL; WCAG 2.2 AA is not demonstrated.**

## Positive implementation evidence

- Root layout sets language and direction for Arabic/English.
- Skip link exists.
- Modal uses native `<dialog>`.
- Global focus styling and reduced-motion behavior exist.
- Base buttons use a 44px target.
- RTL is an explicit design requirement and has static smoke coverage.

## Confirmed contrast gaps

Approximate static calculations:

| Combination | Ratio | AA normal text |
|---|---:|---|
| muted `#6b7fa3` on white | 4.05:1 | Fail |
| warning `#d97706` on `#fef3c7` | 2.86:1 | Fail |
| danger badge combination | 3.95:1 | Fail |
| info badge combination | 4.24:1 | Fail |

Small muted text and badge text are used throughout forms/status UI. Dark-mode status combinations also require systematic validation.

## Missing journey evidence

No axe, Lighthouse, pa11y, screen-reader, keyboard-completion, 200% zoom/reflow, high-contrast, target-size, or supported-device accessibility report was found. The full admin browser walkthrough is skipped.

## Required matrix

- Arabic RTL and English LTR.
- Admin and rep roles.
- Login, day start, customer/product selection, sale, payment, return, expense, queue conflict, confirmation/reversal, reports, maps, upload, and errors.
- Keyboard-only operation, visible focus, logical order, escape/focus return.
- NVDA on Windows and TalkBack on supported Android device; owner may add VoiceOver if iOS is supported.
- 200% zoom, 320 CSS px reflow, text spacing, high contrast/forced colors, reduced motion.
- Labels, names/roles/values, errors/status announcements, table semantics, dialog focus trapping, non-color status.
- 24×24 minimum WCAG 2.2 target-size criterion and preferred 44×44 application target.

Accessibility is a launch gate if WCAG AA is contractual or publicly claimed; otherwise confirmed blockers still require product acceptance and remediation before broad commercial use.

