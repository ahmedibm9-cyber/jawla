# Design system

## 60/30/10 palette
- 60% neutral: `#FFFFFF`, `#F5F5F4`.
- 30% secondary: `#1F2937` (text, nav).
- 10% accent: `#9B1C31` (crimson) — primary actions and active states only.

## Semantic
- success `#16A34A` · warning `#D97706` · danger `#DC2626` · info `#2563EB`.

## Typography
- Font: IBM Plex Sans Arabic + system UI fallback.
- H1 `clamp(1.5rem, 4vw, 2.25rem)` · H2 `clamp(1.25rem, 3vw, 1.75rem)`
  · H3 `1.25rem` · body `1rem/1.6` · small `0.875rem`.
- One H1 per page.

## Component states (every interactive element defines all six)
`normal · hover · focus/selected · active · loading · disabled`.

## Required behaviors
- Confirmation modals for every destructive / financial action.
- Tooltips on all icon-only buttons and abbreviations.
- Skeleton loaders on every list — never a blank screen.
- Optimistic UI only on non-money toggles; money/stock never optimistic.
- Empty states with a friendly bilingual next action.
- Touch target ≥ 44 px; bottom-anchored primary buttons on mobile.
- Dark mode on in Filament.
