# Investigation: UI Layout & Visual Quality Issues

**Date:** 2026-07-22
**Status:** ready-for-dev
**Severity:** Degraded UX + Cosmetic

---

## Symptom Summary

The Jawla rep PWA has three interconnected UI problems:

1. **Horizontal scroll** — Users report a horizontal scrollbar appearing on some screens. The CSS base layer (`app.css:106,118`) already sets `overflow-x: hidden` on `html` and `body`, so the root cause is likely a child element overflowing the viewport.
2. **Bottom bar / top bar height mismatch** — The notification header (top) and tab bar (bottom) are visually different sizes, creating an asymmetric frame.
3. **Generic / templated appearance** — The UI looks like a default mobile app template: uniform cards, standard green palette, no distinctive visual identity, no personality.

---

## Evidence

### E1 — Top bar taller than bottom bar (Grade: A — confirmed by CSS reading)

| Element                  | CSS                                      | Effective Height |
| ------------------------ | ---------------------------------------- | ---------------- |
| `.notification-header`   | `min-height: 56px`, `padding: 8px 12px`  | 56px minimum     |
| `.tab-bar` / `.tab-item` | `min-height: 48px`, `padding: 6px 0 8px` | ~48px minimum    |

**Gap:** 8px. The notification header is visibly taller. The notification FABs are 40x40px with 8px vertical padding = 56px. The tab items are 48px. This is a clear mismatch.

**File:** `resources/css/app.css:138-149` (tab-bar), `1366-1380` (notification-header)

### E2 — `.main-content` padding-bottom reserves 64px, but tab bar is ~56px (Grade: A)

```css
.main-content {
  padding-bottom: calc(64px + env(safe-area-inset-bottom, 0px)); /* line 210 */
}
```

The tab bar's actual visual height is ~48px + safe-area. The 64px reserve is generous but correct (gives breathing room). However, the notification header at the top has NO corresponding top padding — content starts right after the 56px header via `min-height` on the sticky element. This means the first content element sits directly below the header with no gap.

**File:** `resources/css/app.css:208-215`

### E3 — Horizontal scroll: `100vw` in action-toast (Grade: B — probable cause)

```css
.action-toast {
  width: calc(100vw - 32px); /* line 1552 */
}
```

`100vw` includes the scrollbar width on desktop browsers. On a narrow screen with a visible scrollbar, this causes a 15-17px overflow. The toast is `position: fixed` so it doesn't trigger a page scrollbar, but the visual overflow is visible.

**File:** `resources/css/app.css:1541-1562`

### E4 — Horizontal scroll: PWA install banner uses `left: 16px; right: 16px` (Grade: C — low risk)

The PWA banner uses `left: 16px; right: 16px` which is correct (doesn't overflow). However, if the page has `overflow-x: hidden` on body but a child with `position: fixed` and `width: 100vw`, the fixed element can still paint outside.

**File:** `resources/css/app.css:1426-1441`

### E5 — No `overflow-x` protection on `main-content` (Grade: B — probable cause)

```css
.main-content {
  max-width: 32rem; /* 512px */
  margin-inline: auto;
  /* No overflow-x: hidden */
}
```

If any child element (e.g., a table, a wide card, or a long unbreakable string) exceeds 512px, it will overflow the container. The body has `overflow-x: hidden` but `main-content` does not, so content can still visually overflow on wider screens.

**File:** `resources/css/app.css:208-215`

### E6 — Generic visual identity (Grade: A — confirmed by code reading)

The current design has these "default" characteristics:

- **Color:** Single brand green `#6db83b` with no accent variety. Status colors are standard red/amber/green. No gradient depth on surfaces.
- **Typography:** IBM Plex Sans Arabic is functional but used flatly — no size contrast beyond 3 levels, no weight contrast beyond 400/600/700, no letter-spacing play.
- **Cards:** Every card is identical: `border-radius: 12px`, `padding: 16px`, `box-shadow: var(--shadow-sm)`. No card variety (elevated, outlined, filled, glass).
- **Spacing:** Uniform 16px horizontal padding everywhere. No visual rhythm variation.
- **Animations:** Only two: `successPop` (scale bounce) and `modalFadeIn` (opacity fade). No entrance animations, no stagger effects, no scroll reveals.
- **Hero sections:** Both `home-hero` and `profile-hero` use the same `linear-gradient(135deg, brand, brand-dark)` with a single decorative circle. Identical treatment = templated feel.
- **No distinctive element:** There is no "signature" — no unique layout device, no custom illustration, no branded micro-interaction that says "this is Jawla."

**Files:** `resources/css/app.css` throughout

### E7 — Notification header z-index conflict (Grade: C — speculative)

```css
.notification-header {
  z-index: 40;
} /* line 1370 */
.tab-bar {
  z-index: var(--z-sticky);
} /* --z-sticky = 100 */
```

The notification header uses a hardcoded `z-index: 40` while the tab bar uses `var(--z-sticky)` which is `100`. This means the tab bar overlaps the notification header if they ever meet (e.g., on a very short viewport). The notification header should also use `var(--z-sticky)`.

**File:** `resources/css/app.css:1370`

### E8 — Tab bar active indicator uses `::after` pseudo-element (Grade: B)

The active tab indicator is a 24px wide, 3px tall bar positioned with `left: 50%; transform: translateX(-50%)`. In RTL mode, this still uses `left` which is incorrect — it should use `inset-inline-start` or have an RTL override. The RTL override is missing.

**File:** `resources/css/app.css:175-185`

### E9 — Form select background-position hardcoded to `left` (Grade: A — confirmed)

```css
.form-select {
  background-position: left 12px center; /* line 1008 */
  padding-left: 36px; /* line 1010 */
}
[dir="rtl"] .form-select {
  background-position: right 12px center; /* line 1014 */
  padding-left: 14px; /* line 1015 */
  padding-right: 36px; /* line 1016 */
}
```

RTL handling exists but uses separate rules. This is correct but verbose. The real issue: `padding-left: 14px` in RTL is wrong — it should be `padding-inline-start: 14px` to avoid asymmetric padding.

**File:** `resources/css/app.css:1005-1017`

### E10 — Dark mode border-color mismatch (Grade: B)

```css
@media (prefers-color-scheme: dark) {
  --color-border-light: #1e293b; /* line 1520 */
}
```

`--color-border-light` in dark mode is `#1e293b`, which is the same as `--color-surface-alt` in dark mode (`#1e293b`, line 1514). This means borders between dark-mode surfaces are invisible — they blend into the background.

**File:** `resources/css/app.css:1512-1521`

### E11 — Guest layout missing viewport-fit=cover (Grade: B)

```blade
<!-- app.blade.php -->
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

<!-- guest.blade.php -->
<meta name="viewport" content="width=device-width, initial-scale=1">
```

The guest layout (login page) is missing `viewport-fit=cover`, which means on iPhone X+ the login form will be inset by the safe area rather than extending edge-to-edge.

**File:** `resources/views/layouts/guest.blade.php:5`

### E12 — Inconsistent card patterns across pages (Grade: B)

- Home page: `.card` (generic) + `.visit-card` (custom) + `.home-stat-card` (custom)
- Customers: `.card` only
- Orders: `.card` only
- More: `.more-item` (custom) + `.profile-hero` (custom)
- Visit flow: `.card` only

Three different card patterns = visual inconsistency. The `visit-card` has a 4px status bar, the `more-item` has a 40px icon circle, and the generic `.card` has neither. This lack of a unified card system contributes to the "generic" feel.

**Files:** Multiple blade templates

### E13 — No `prefers-reduced-motion` support (Grade: A — confirmed)

The only animations are `successPop` and `modalFadeIn`. Neither has a `prefers-reduced-motion: reduce` fallback. While these are simple animations, the `successPop` uses `transform: scale()` which can cause motion sickness for some users.

**File:** `resources/css/app.css:1059-1078, 1357-1364`

### E14 — WhatsApp button uses inline style (Grade: B)

```blade
<a href="https://wa.me/?text={{ urlencode($shareText) }}" ...
   class="btn text-sm no-underline text-white" style="background:#25D366">
```

Hardcoded inline color instead of a design token. WhatsApp green `#25D366` should be a CSS variable.

**File:** `resources/views/livewire/app/orders.blade.php:127`

### E15 — `overflow: hidden` on `.more-item:focus-visible` is broken (Grade: A)

```css
.more-item:focus-visible {
  outline: none;
  box-shadow: 0 0 0 2px var(--color-brand);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-brand) 60%, transparent);
}
```

Two `box-shadow` declarations — the second overrides the first. This is a bug: the first `box-shadow` (solid brand color) is never applied. The `color-mix()` version is fine but the dead code should be removed.

**File:** `resources/css/app.css:806-810`

---

## Hypotheses

### H1 — Tab bar height mismatch is intentional but sloppy (Plausibility: HIGH)

The notification header uses `min-height: 56px` and the tab bar uses `min-height: 48px`. These were likely designed independently. The 8px gap creates visual asymmetry.

**Verification:** Compare with iOS Human Interface Guidelines (tab bar = 49pt, status bar area varies). The mismatch is a design inconsistency, not a functional bug.

### H2 — Horizontal scroll comes from `100vw` in action-toast or from wide content (Plausibility: MEDIUM)

The `overflow-x: hidden` on html/body should prevent visible scrollbars. However, `100vw` in `.action-toast` and any wide content can still cause the viewport to be wider than expected on some browsers.

**Verification:** Test on iOS Safari and Chrome mobile. The `100vw` issue is well-documented on WebKit.

### H3 — Generic look is systemic — no design system, just CSS variables (Plausibility: HIGH)

The app has CSS custom properties but no design _system_ — no spacing scale, no typography hierarchy, no card variants, no animation library, no distinctive visual device. Everything is "standard mobile app" patterns.

**Verification:** The frontend-design skill's self-simulation check would flag this entire design as "generic brief of the same category."

---

## Suspected Components

| Component                                      | Why Suspected                                                                                                                                                      | Blast Radius             | Confidence |
| ---------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------ | ---------- |
| `resources/css/app.css`                        | All spacing, sizing, and color tokens live here. Tab bar height, notification header height, overflow issues, dark mode borders — all defined in this single file. | Every page in the app    | HIGH       |
| `resources/views/layouts/app.blade.php`        | The HTML shell. Notification header markup lives here. Missing `<main>` padding-top for the sticky header.                                                         | Every authenticated page | HIGH       |
| `resources/views/components/tab-bar.blade.php` | Bottom navigation component. Height mismatch originates here.                                                                                                      | Every page with tab bar  | HIGH       |
| `resources/views/livewire/app/home.blade.php`  | Home page hero and stats — the "first impression" page. Generic gradient + circle = templated look.                                                                | Brand perception         | MEDIUM     |
| `resources/views/livewire/app/more.blade.php`  | Menu page with the most card variety. Inconsistent with other pages.                                                                                               | Navigation experience    | MEDIUM     |

---

## Recommended Response

**Option A — Create a fix story** covering:

1. **Fix tab bar / notification header height mismatch** — Align both to 56px (or 48px). Add `padding-top` to `.main-content` to clear the sticky notification header.
2. **Fix horizontal scroll** — Replace `100vw` with `100%` in `.action-toast`. Add `overflow-x: hidden` to `.main-content`. Add `overflow-x: hidden` to `.main-content` as a safety net.
3. **Fix dark mode border visibility** — Change `--color-border-light` in dark mode to be lighter than `--color-surface-alt`.
4. **Fix RTL tab indicator** — Use `inset-inline-start` instead of `left` for the active tab indicator.
5. **Fix duplicate box-shadow in `.more-item:focus-visible`** — Remove the dead first declaration.
6. **Add `prefers-reduced-motion`** — Wrap `successPop` and `modalFadeIn` in a media query.
7. **Fix guest layout viewport** — Add `viewport-fit=cover`.
8. **Fix form select RTL padding** — Use logical properties.
9. **Move WhatsApp color to a CSS variable** — `--color-whatsapp: #25D366`.
10. **Fix notification header z-index** — Use `var(--z-sticky)` instead of hardcoded `40`.

**For the "generic look" issue** — this requires a separate design pass (not a bug fix). The frontend-design skill should be used to create a distinctive visual identity. Key opportunities:

- Replace the identical gradient hero treatment with a unique layout device
- Add card variants (elevated, outlined, filled) to create visual hierarchy
- Introduce a secondary accent color or gradient
- Add entrance animations / stagger effects
- Create a distinctive home page layout (not just cards in a column)

---

## Files Touched

- `resources/css/app.css` — All CSS fixes
- `resources/views/layouts/app.blade.php` — No changes needed (structure is correct)
- `resources/views/layouts/guest.blade.php` — viewport-fit fix
- `resources/views/components/tab-bar.blade.php` — No changes needed
- `resources/views/livewire/app/orders.blade.php` — WhatsApp color variable

---

## Decision Log Entry

**Investigation: ui-issues — 2026-07-22**

- Symptom: Horizontal scroll, bar height mismatch, generic appearance
- Primary hypothesis: CSS token inconsistencies and missing design system identity
- Primary suspected component: `resources/css/app.css`
- Case file: `bmad-output/investigation-ui-issues-2026-07-22.md`
- Recommended response: Option A — create fix story for the 10 concrete bugs; separate design pass for visual identity
