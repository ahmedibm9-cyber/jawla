# REP Account UI Review

**Date**: 2026-07-20
**Scope**: Full REP PWA interface (`/app/*`) -- 21 Livewire components, layout, CSS, design system components
**Overall Score**: 7.5/10

---

## Summary Table

| Category           | Score | Notes                                      |
| ------------------ | ----- | ------------------------------------------ |
| Design Tokens      | 8/10  | Solid system, duplicated in `:root`        |
| Color & Contrast   | 7/10  | Light mode good, no dark mode              |
| Typography         | 9/10  | IBM Plex Arabic, proper sizing             |
| Icons              | 5/10  | Inline SVGs, no icon library               |
| Touch Targets      | 7/10  | Most pass, notification bell 40px          |
| Accessibility      | 7/10  | Good foundations, missing focus mgmt       |
| RTL Support        | 9/10  | Thorough, logical properties used          |
| Modal/Dialog       | 6/10  | No backdrop scrim                          |
| Layout & Spacing   | 8/10  | Consistent 8dp rhythm                      |
| Mobile/Responsive  | 6/10  | Phone-optimized, no breakpoints, no tablet |
| Interaction States | 8/10  | Hover/focus/active defined                 |
| Performance        | 8/10  | Skeletons, debounce, lazy loading          |

---

## What's Done Well

### Design System & Tokens
- Consistent `--clr-*` token system in `app.css` with semantic surfaces, text, status, and foreground tokens.
- 8dp spacing rhythm via Tailwind + custom properties.
- Proper `font-variant-numeric: tabular-nums` for numbers.
- `IBM Plex Sans Arabic` with proper RTL font-family override.

### Accessibility Foundations
- Skip link (`<a href="#main">`) at `app.blade.php:30`.
- `aria-current="page"` on active tab bar items.
- `aria-live="polite"` on notification badge and error messages.
- `aria-label` on all icon-only buttons.
- `role="dialog"` + `aria-modal="true"` on modals.
- `prefers-reduced-motion` respected for skeleton/toast animations (`app.css:441`).

### RTL Support
- `dir="rtl"` on `<html>` based on locale.
- CSS logical properties used in some places (`margin-inline`, `padding-inline-end`).
- Form select arrow repositioned for RTL (`app.css:1006`).

### Confirmation Modals
- Every destructive/financial action uses `<x-ds.modal>` with consequence-stating messages.
- Bilingual throughout.

### PWA Features
- Service worker registration, manifest link, `viewport-fit=cover`.
- Install banner with delayed prompt (30s).
- Offline draft saving in visit flow via `localStorage`.

### Mobile Basics
- `viewport-fit=cover` enables edge-to-edge rendering on notched devices.
- `env(safe-area-inset-bottom)` correctly applied to tab bar and main content padding.
- `touch-action: manipulation` on buttons and tab items prevents 300ms tap delay.
- `-webkit-tap-highlight-color: transparent` removes tap flash.
- `overscroll-behavior: contain` on `.main-content` prevents pull-to-refresh interference.
- `min-height: 44px` on all `.btn` elements meets iOS touch target minimum.
- All font sizes use `rem` units, which scale with user system preferences.

---

## Issues

### 1. Icons: Inline SVGs Instead of Icon Library (Medium)

**Location**: Every view -- `tab-bar.blade.php`, `more.blade.php`, `home.blade.php`, all page headers.

**Problem**: All icons are hand-written inline SVGs. This makes views verbose, hard to maintain, and prevents token-based theming. Stroke widths and sizing vary slightly across views.

**Recommendation**: Install a Blade-compatible Heroicons package (`blade-ui-kit/blade-heroicons`) and replace inline SVGs:
```blade
<x-heroicon-o-home class="w-6 h-6" />
```

---

### 2. Modal Scrim Missing (Medium)

**Location**: `components/ds/modal.blade.php:18`

**Problem**: The `<dialog>` has `background:transparent` and no `::backdrop` styling. Background content remains fully visible behind the modal, reducing focus on the action.

**Fix**: Add backdrop styling:
```css
dialog::backdrop {
  background: rgba(0, 0, 0, 0.5);
}
```

---

### 3. Color Token Duplication (Low-Medium)

**Location**: `app.css` lines 7-101 (`@theme`) vs 104-160 (`:root`)

**Problem**: Every token is defined **twice** -- once in `@theme {}` (Tailwind v4) and once in `:root {}` (base layer). This creates a maintenance risk where values could drift apart.

**Recommendation**: Remove the `:root {}` block. Tailwind v4's `@theme` already injects these as CSS custom properties.

---

### 4. Notification Bell Touch Target Below Minimum (Low)

**Location**: `app.blade.php:43`

**Problem**: The notification bell is `width:40px; height:40px`. The minimum touch target is **44x44pt**. It's 4px short.

**Fix**: Change to `width:44px; height:44px`.

---

### 5. Tab Bar Missing Explicit Min-Height (Low)

**Location**: `app.css:211`

**Problem**: Tab items have `padding: 8px 0 10px` with 24px icons. The total height is approximately 45px but not explicitly guaranteed.

**Recommendation**: Add `min-height: 44px` to `.tab-item` to guarantee the touch target.

---

### 6. Inline Styles in Layout (Low)

**Location**: `app.blade.php:41-43` (notification header), `app.blade.php:66-67` (PWA install banner)

**Problem**: The notification header and PWA install banner use inline `style=""` attributes instead of CSS classes. This bypasses the design token system and makes markup harder to read.

**Recommendation**: Extract to `.notification-fab` and `.pwa-install-banner` classes in `app.css`.

---

### 7. Hardcoded Colors in Views (Low)

**Location**:
- `more.blade.php:20` -- `style="color:var(--clr-accent)"`
- `more.blade.php:24` -- `style="color:var(--clr-accent-blue)"`
- `visit-flow.blade.php:134` -- `bg-green-50`, `text-green-700`
- `sales-flow.blade.php:44` -- `border-border`, `bg-white`

**Problem**: Some views use Tailwind utility classes (`bg-green-50`, `text-green-700`) alongside the token system, while others use inline `style` attributes. Neither approach is token-driven.

**Recommendation**: Create Tailwind utility mappings for status colors, or use the existing `--clr-success`, `--clr-danger` tokens consistently via `bg-[var(--clr-success)]` etc.

---

### 8. No Dark Mode Support (Medium)

**Location**: Entire app

**Problem**: The `<html>` tag has `style="color-scheme:light"` hardcoded. There is no dark mode variant for any component. All color values are light-mode only.

**Recommendation**: Add a `prefers-color-scheme: dark` media query that remaps the `--clr-*` tokens. The token system is already in place, so this is the lowest-effort path:
```css
@media (prefers-color-scheme: dark) {
  :root {
    --clr-surface: #1a1a1a;
    --clr-surface-alt: #262626;
    --clr-text-primary: #f5f5f5;
    --clr-text-secondary: #a3a3a3;
    /* ... */
  }
}
```

---

### 9. Tab Bar Z-Index Should Be Sticky (Low)

**Location**: `app.css:201`

**Problem**: Tab bar uses `z-index: var(--z-dropdown)` (50). As a fixed navigation element, it should use `--z-sticky` (100) to match its semantic role.

**Fix**: Change to `z-index: var(--z-sticky)`.

---

### 10. Signature Canvas Not Responsive (Low)

**Location**: `visit-flow.blade.php:177`

**Problem**: The signature canvas has `width="340" height="140"` as HTML attributes, but CSS says `class="... w-full"`. On screens narrower than 340px, the canvas is visually scaled but internal resolution stays 340px, causing coordinate misalignment between touch and drawing.

**Recommendation**: Set canvas dimensions dynamically in `x-init` based on rendered size:
```js
x-init="const r = $el.getBoundingClientRect(); $el.width = r.width; $el.height = 140; ctx = $el.getContext('2d'); ..."
```

---

### 11. Missing Focus Management on Success Screens (Low)

**Location**:
- `sales-flow.blade.php:202`
- `visit-flow.blade.php:209`

**Problem**: Success screens don't announce the state change to screen readers. The `collect-payment.blade.php:12` does this correctly with `tabindex="-1"` and `x-init="$nextTick(() => $el.focus())"`, but the sales flow and visit flow success screens don't.

**Fix**: Add `tabindex="-1"` and focus management:
```blade
<h3 class="success-title" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())">
```

---

### 12. Skeleton Not Hidden From Screen Readers (Low)

**Location**: `app.css:420-439`, `components/ds/skeleton.blade.php`

**Problem**: The skeleton shimmer animation has no accessible label. The `wire:loading.delay` containers have `aria-hidden="true"` which is correct, but the skeleton component itself should also ensure it's fully hidden from assistive technology.

**Recommendation**: Verify the `<x-ds.skeleton>` component renders with `aria-hidden="true"` on its root element.

---

## Mobile & Responsive Issues

### 13. Zero Responsive Breakpoints (Medium)

**Location**: `app.css` (entire file)

**Problem**: The CSS contains **no `@media` breakpoint queries** at all -- the only media query is `@media (prefers-reduced-motion: reduce)`. There are no adjustments for:
- Small phones (< 360px)
- Large phones (> 414px)
- Tablets (768px+)
- Landscape orientation

The `.main-content` has `max-width: 32rem` (512px) which caps content at phone width. On tablets and desktops, the app appears as a narrow strip with wasted space on both sides.

**Recommendation**: This is acceptable for a phone-only PWA, but add at minimum:
```css
/* Prevent cramped layout on very narrow screens */
@media (max-width: 359px) {
  .form-row { grid-template-columns: 1fr; }
  .home-stats { grid-template-columns: 1fr; }
}

/* Better use of space on tablets if reps use them */
@media (min-width: 768px) {
  .main-content { max-width: 40rem; }
}
```

---

### 14. Form Row Grid Never Collapses (Medium)

**Location**: `app.css:1030-1034`

**Problem**: `.form-row` uses `grid-template-columns: 1fr 1fr` with no responsive fallback. On screens narrower than 360px (e.g., iPhone SE), two side-by-side form inputs become cramped -- input text is hard to read and the keyboard takes most of the screen.

Used in: `sales-flow.blade.php:148` (quantity + price), `collect-payment.blade.php` forms.

**Recommendation**: Collapse to single column on narrow screens:
```css
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
@media (max-width: 359px) {
  .form-row { grid-template-columns: 1fr; }
}
```

---

### 15. No Landscape Orientation Handling (Low-Medium)

**Location**: Entire app

**Problem**: When a phone is rotated to landscape, the fixed tab bar (56px) and notification header (40px) consume ~96px of the ~375px viewport height, leaving only ~279px for content. The signature canvas (`visit-flow.blade.php:177`) is particularly affected -- it's 140px tall, consuming half the available space.

**Recommendation**: In landscape mode, consider:
- Reducing the tab bar to icon-only (no labels) to save vertical space
- Reducing the hero header height
- Making the signature canvas taller in landscape (it needs more width for signing)

```css
@media (orientation: landscape) and (max-height: 500px) {
  .tab-item span { display: none; } /* hide labels */
  .tab-item { padding: 6px 0; }
  .home-hero { padding: 16px 20px 20px; }
}
```

---

### 16. Hardcoded Font Sizes Don't Scale (Low)

**Location**: `app.css` -- multiple locations

**Problem**: Several font sizes use `rem` (good), but some use hardcoded `px` values:
- `.tab-item` font-size: `0.7rem` (`app.css:213`) -- scales, but very small
- `.badge` font-size: `0.75rem` (`app.css:351`)
- Notification badge: `font-size:11px` inline (`app.blade.php:47`) -- does NOT scale

The `0.7rem` tab label is only 11.2px at default size, which is below the recommended 12px minimum for mobile body text.

**Recommendation**:
- Increase `.tab-item` font-size to `0.75rem` (12px)
- Change notification badge from inline `11px` to `0.6875rem` so it scales with user preferences

---

### 17. Notification Bell Can Overlap Content (Low)

**Location**: `app.blade.php:41`

**Problem**: The notification bell is `position:sticky; top:0` with `z-index:40`. On the home page, the hero header starts immediately below it. The bell's container has `pointer-events:none` (good), but on very small screens the bell circle (40px) can visually overlap with the hero brand logo or title text.

**Recommendation**: Give the notification header a fixed height and ensure the hero/content starts below it, or move the bell into the hero header itself.

---

### 18. Signature Canvas Fixed Resolution (Low)

**Location**: `visit-flow.blade.php:177`

**Problem**: Already noted in issue #10, but from a mobile perspective specifically: the canvas is `width="340"` hardcoded. On a 375px-wide phone with 16px padding on each side, the available width is 343px -- the canvas barely fits. On a 320px screen (288px available), the canvas overflows or gets CSS-squished, breaking touch coordinate accuracy.

**Recommendation**: Dynamically size the canvas to match its container width, and use `devicePixelRatio` for crisp rendering:
```js
x-init="
  const r = $el.getBoundingClientRect();
  const dpr = window.devicePixelRatio || 1;
  $el.width = r.width * dpr;
  $el.height = 140 * dpr;
  $el.style.width = r.width + 'px';
  $el.style.height = '140px';
  ctx = $el.getContext('2d');
  ctx.scale(dpr, dpr);
  ctx.strokeStyle='#1f2937'; ctx.lineWidth=2; ctx.lineCap='round';
"
```

---

### 19. Cart Summary Card Alignment Inconsistency (Low)

**Location**: `sales-flow.blade.php:158-161`

**Problem**: The line total uses `text-right` class, but in RTL mode this should be `text-left` (or `text-end`). Using directional classes instead of logical properties means the alignment flips incorrectly in RTL.

**Recommendation**: Replace `text-right` with `text-end` (Tailwind v4 logical property) for proper RTL behavior.

---

### 20. No Scroll-to-Top on Step Transitions (Low)

**Location**: `visit-flow.blade.php`, `sales-flow.blade.php`

**Problem**: When transitioning between steps (e.g., cart to done, checkin to report), the page doesn't scroll to top. On longer forms, the user may be looking at the bottom of the previous step and see blank space when the next step renders.

**Recommendation**: Add `wire:transition.scroll` or `x-init="window.scrollTo(0,0)"` on step containers.

---

## Priority Order for Fixes

1. **Modal scrim** (#2) -- affects every financial/destructive action confirmation
2. **Form row collapse** (#14) -- breaks usability on small phones
3. **Dark mode** (#8) -- large surface area, token system makes it easy
4. **Icon library** (#1) -- biggest maintainability win
5. **Responsive breakpoints** (#13) -- foundation for all mobile fixes
6. **Landscape handling** (#15) -- affects reps using phone in landscape
7. **Token duplication** (#3) -- maintenance risk
8. **Notification bell touch target** (#4) -- quick fix
9. **Signature canvas sizing** (#18/#10) -- breaks on narrow screens
10. **Focus management on success screens** (#11) -- quick fix
11. **Tab bar z-index** (#9) -- quick fix
12. **Font size scaling** (#16) -- accessibility concern
13. **RTL text alignment** (#19) -- directional vs logical properties
14. **Scroll-to-top on transitions** (#20) -- UX polish
15. **Inline styles in layout** (#6) -- cleanup
16. **Hardcoded colors** (#7) -- cleanup
17. **Notification bell overlap** (#17) -- edge case
18. **Tab bar min-height** (#5) -- quick fix
19. **Skeleton accessibility** (#12) -- already partially handled
