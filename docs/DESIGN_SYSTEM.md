# Design system

> See also: `docs/BRAND_GUIDELINES.md` for the complete brand identity.

## 60/30/10 palette

- **60% neutral:** `#F8FAFC` (slate-50), `#FFFFFF` — backgrounds, whitespace
- **30% secondary:** `#0F172A` (slate-900), `#1E293B` (slate-800), `#475569` (slate-600) — text, structure
- **10% accent:** `#3d7a18` (Jawla Green) — primary actions, active states, CTAs only

## Brand colors

| Role          | Token         | Hex       | Usage                            |
| ------------- | ------------- | --------- | -------------------------------- |
| Primary       | `primary-500` | `#3d7a18` | CTAs, active nav, logo mark      |
| Primary hover | `primary-400` | `#7EC54E` | Hover states                     |
| Primary dark  | `primary-600` | `#5BA82E` | Pressed states                   |
| Accent        | `accent-600`  | `#D97706` | Waypoints, badges, notifications |
| Accent light  | `accent-400`  | `#FBBF24` | Warning badges                   |

## Logo variants

| Variant | File                 | When to use                            |
| ------- | -------------------- | -------------------------------------- |
| White J | `images/white-j.png` | Dark/black backgrounds, green hero bar |
| Green J | `images/green-j.png` | White/light backgrounds, login, errors |
| Black J | `images/black-j.png` | Filament admin header (white bg)       |

## Semantic

| State   | Hex       | Tailwind                      |
| ------- | --------- | ----------------------------- |
| Success | `#3d7a18` | `text-success` / `bg-success` |
| Warning | `#D97706` | `text-warning` / `bg-warning` |
| Danger  | `#DC2626` | `text-danger` / `bg-danger`   |
| Info    | `#2563EB` | `text-info` / `bg-info`       |

## Typography

- Font: IBM Plex Sans Arabic + system UI fallback.
- H1 `clamp(1.5rem, 4vw, 2.25rem)` · H2 `clamp(1.25rem, 3vw, 1.75rem)`
  · H3 `1.25rem` · body `1rem/1.6` · small `0.875rem`.
- One H1 per page.
- Body text: `slate-700` on white. Muted: `slate-500`.

## Component states (every interactive element defines all six)

`normal · hover · focus/selected · active · loading · disabled`.

## Buttons

| Type          | Background    | Text          | Border        | Radius       |
| ------------- | ------------- | ------------- | ------------- | ------------ |
| Primary       | `primary-500` | white         | none          | `rounded-lg` |
| Primary Hover | `primary-400` | white         | none          | `rounded-lg` |
| Secondary     | white         | `primary-500` | `primary-200` | `rounded-lg` |
| Danger        | `red-600`     | white         | none          | `rounded-lg` |
| Ghost         | transparent   | `slate-600`   | none          | `rounded-lg` |

## Required behaviors

- Confirmation modals for every destructive / financial action.
- Tooltips on all icon-only buttons and abbreviations.
- Skeleton loaders on every list — never a blank screen.
- Optimistic UI only on non-money toggles; money/stock never optimistic.
- Empty states with a friendly bilingual next action.
- Touch target ≥ 44 px; bottom-anchored primary buttons on mobile.
- Dark mode on in Filament.

## Spacing scale

| Token | Value | Usage                     |
| ----- | ----- | ------------------------- |
| 1     | 4px   | Tight gaps (icon to text) |
| 2     | 8px   | Compact elements          |
| 3     | 12px  | Default gaps              |
| 4     | 16px  | Standard spacing          |
| 5     | 20px  | Card padding              |
| 6     | 24px  | Section gaps              |
| 8     | 32px  | Large gaps                |

## Border radius

| Element        | Radius | Tailwind       |
| -------------- | ------ | -------------- |
| Buttons        | 8px    | `rounded-lg`   |
| Cards          | 12px   | `rounded-xl`   |
| Inputs         | 8px    | `rounded-lg`   |
| Modals         | 16px   | `rounded-2xl`  |
| Avatars/Badges | 9999px | `rounded-full` |

## Shadows

| Level | Tailwind            | Usage             |
| ----- | ------------------- | ----------------- |
| sm    | `shadow-card`       | Cards at rest     |
| md    | `shadow-card-hover` | Card hover        |
| lg    | `shadow-modal`      | Modals, dropdowns |

## Dark mode (Filament admin)

| Element        | Light         | Dark          |
| -------------- | ------------- | ------------- |
| Background     | `slate-50`    | `slate-900`   |
| Surface        | white         | `slate-800`   |
| Text primary   | `slate-800`   | `slate-100`   |
| Text secondary | `slate-500`   | `slate-400`   |
| Borders        | `slate-200`   | `slate-700`   |
| Primary CTA    | `primary-500` | `primary-400` |

## Accessibility

- All contrast ratios meet WCAG 2.1 AA (4.5:1 text, 3:1 UI)
- Focus ring: `ring-2 ring-primary-500 ring-offset-2`
- Skip navigation on every page
- ARIA labels on icon-only buttons
- Bilingual empty states with clear next action
