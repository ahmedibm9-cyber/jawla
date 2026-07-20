# Jawla Brand Guidelines v1.0

> Last updated: 2026-07-20
> Status: Active

## Quick Reference

| Element       | Value                            |
| ------------- | -------------------------------- |
| Primary Color | `#059669` (Emerald 600)          |
| Accent Color  | `#D97706` (Amber 600)            |
| Primary Font  | IBM Plex Sans Arabic             |
| Voice         | Professional, Clear, Trustworthy |
| Tagline EN    | "Your route to smarter sales"    |
| Tagline AR    | "طريقك نحو مبيعات أذكى"          |

---

## 1. Brand Story

**Jawla (جولة)** means "tour" or "trip" in Arabic — the daily journey every field sales rep takes. The brand represents the intersection of tradition (the Arabic word for a salesperson's daily rounds) and modern technology (GPS, real-time data, smart routes).

**Brand promise:** Every jawla counts. Jawla gives reps the tools to sell smarter, collect faster, and never miss an opportunity on the road.

**Brand personality:**

- **Trustworthy** — handles money and stock with precision
- **Energetic** — field sales is fast-paced, the brand matches
- **Approachable** — reps use it all day, it should feel friendly
- **Professional** — admins and finance rely on it for decisions

---

## 2. Color Palette

### Primary — Emerald Green

The brand's core color. Represents growth, prosperity, and the Egyptian landscape. Used for CTAs, active states, and the logo mark.

| Token         | Hex       | RGB         | Usage                                |
| ------------- | --------- | ----------- | ------------------------------------ |
| `emerald-50`  | `#ECFDF5` | 236,253,245 | Light backgrounds, icon containers   |
| `emerald-100` | `#D1FAE5` | 209,250,229 | Subtle highlights                    |
| `emerald-200` | `#A7F3D0` | 167,243,208 | Light borders                        |
| `emerald-400` | `#34D399` | 52,211,153  | Secondary actions                    |
| `emerald-500` | `#10B981` | 16,185,129  | Hover states                         |
| `emerald-600` | `#059669` | 5,150,105   | **Primary — CTAs, active nav, logo** |
| `emerald-700` | `#047857` | 4,120,87    | Active/pressed states                |
| `emerald-800` | `#065F46` | 6,95,70     | Dark mode primary                    |
| `emerald-900` | `#064E3B` | 6,78,59     | Dark mode text                       |

### Accent — Warm Amber

Used sparingly for waypoints, badges, notifications, and the route origin dot. Adds warmth and Egyptian character without competing with green.

| Token       | Hex       | RGB         | Usage                                   |
| ----------- | --------- | ----------- | --------------------------------------- |
| `amber-50`  | `#FFFBEB` | 255,251,235 | Warm backgrounds                        |
| `amber-100` | `#FEF3C7` | 254,243,199 | Subtle warm highlights                  |
| `amber-400` | `#FBBF24` | 251,191,36  | Warning states                          |
| `amber-500` | `#F59E0B` | 245,158,11  | Badges, notifications                   |
| `amber-600` | `#D97706` | 217,119,6   | **Accent — origin dot, key highlights** |
| `amber-700` | `#B45309` | 180,83,9    | Accent hover                            |

### Neutral — Cool Gray

Clean, modern foundation. Never pure black — use `slate-900` for text.

| Token       | Hex       | RGB         | Usage                   |
| ----------- | --------- | ----------- | ----------------------- |
| `slate-50`  | `#F8FAFC` | 248,250,252 | Page background         |
| `slate-100` | `#F1F5F9` | 241,245,249 | Card backgrounds        |
| `slate-200` | `#E2E8F0` | 226,232,240 | Borders, dividers       |
| `slate-300` | `#CBD5E1` | 203,213,225 | Disabled borders        |
| `slate-400` | `#94A3B8` | 148,163,184 | Placeholder text, icons |
| `slate-500` | `#64748B` | 100,116,139 | Secondary text          |
| `slate-600` | `#475569` | 71,85,105   | Body text (on white)    |
| `slate-700` | `#334155` | 51,65,85    | Headings (on white)     |
| `slate-800` | `#1E293B` | 30,41,59    | Primary text            |
| `slate-900` | `#0F172A` | 15,23,42    | Logo text, H1           |

### Semantic Colors

| State   | Hex       | Usage                               |
| ------- | --------- | ----------------------------------- |
| Success | `#059669` | Completed visits, positive balances |
| Warning | `#D97706` | Pending approvals, low stock alerts |
| Danger  | `#DC2626` | OOS alarms, destructive actions     |
| Info    | `#2563EB` | Informational messages, links       |

### 60/30/10 Rule

| Ratio | Color                           | Percentage                  |
| ----- | ------------------------------- | --------------------------- |
| 60%   | Neutral (slate-50, white)       | Backgrounds, whitespace     |
| 30%   | Primary (slate-800, slate-600)  | Text, structure             |
| 10%   | Accent (emerald-600, amber-600) | CTAs, active states, badges |

---

## 3. Typography

### Font Stack

```css
--font-primary: "IBM Plex Sans Arabic", system-ui, -apple-system, sans-serif;
--font-mono: "IBM Plex Mono", "Courier New", monospace;
```

IBM Plex Sans Arabic is chosen because:

- Excellent Arabic and Latin rendering in one font
- Professional, tech-forward feel
- Clear at small sizes (14px body on mobile)
- Free and open source

### Type Scale

| Element    | Desktop                        | Mobile     | Weight | Line Height | Letter Spacing |
| ---------- | ------------------------------ | ---------- | ------ | ----------- | -------------- |
| H1         | `clamp(1.5rem, 4vw, 2.25rem)`  | `1.5rem`   | 700    | 1.2         | -0.025em       |
| H2         | `clamp(1.25rem, 3vw, 1.75rem)` | `1.25rem`  | 600    | 1.25        | -0.02em        |
| H3         | `1.25rem`                      | `1.125rem` | 600    | 1.3         | -0.015em       |
| H4         | `1.125rem`                     | `1rem`     | 600    | 1.35        | -0.01em        |
| Body       | `1rem`                         | `1rem`     | 400    | 1.6         | 0              |
| Body Small | `0.875rem`                     | `0.875rem` | 400    | 1.5         | 0              |
| Caption    | `0.75rem`                      | `0.75rem`  | 400    | 1.4         | 0              |

### Rules

- One H1 per page
- Body text: `slate-700` on white, `slate-200` on dark
- Muted text: `slate-500`
- Never use pure black (`#000`) for text

---

## 4. Logo

### Versions

| Variant         | File             | Use Case                             |
| --------------- | ---------------- | ------------------------------------ |
| Full (dark bg)  | `logo.svg`       | Default — headers, light backgrounds |
| Full (light bg) | `logo-light.svg` | Dark backgrounds, dark mode          |
| Icon only       | `logo-icon.svg`  | Favicons, app icons, small spaces    |

### Logo Mark Explanation

The mark is a **route path with waypoints** — a curved line connecting three dots:

- **Amber dot (bottom-left):** The starting point — the warehouse, the rep's base
- **Green dot (top):** The journey — the jawla, the daily route
- **Green dot with white center (right):** The destination — the customer, the sale

This captures the essence of Jawla: a journey from base to customer with purpose.

### Clear Space

Minimum clear space = height of the logo mark (the tallest point of the route curve, ~32px). No text, images, or edges within this zone.

### Minimum Size

| Context             | Minimum Width |
| ------------------- | ------------- |
| Digital — Full Logo | 120px         |
| Digital — Icon      | 24px          |
| Print — Full Logo   | 35mm          |
| Print — Icon        | 10mm          |

### Don'ts

- Don't rotate or skew
- Don't change colors outside approved palette
- Don't add shadows, glows, or effects
- Don't crop or modify proportions
- Don't place on busy photographic backgrounds without overlay

---

## 5. Spacing & Layout

### Spacing Scale (Tailwind)

| Token | Value | Usage                     |
| ----- | ----- | ------------------------- |
| `1`   | 4px   | Tight gaps (icon to text) |
| `2`   | 8px   | Compact elements          |
| `3`   | 12px  | Default gaps              |
| `4`   | 16px  | Standard spacing          |
| `5`   | 20px  | Card padding              |
| `6`   | 24px  | Section gaps              |
| `8`   | 32px  | Large gaps                |
| `10`  | 40px  | Section dividers          |
| `12`  | 48px  | Major sections            |

### Border Radius

| Element      | Radius | Tailwind       |
| ------------ | ------ | -------------- |
| Buttons      | 8px    | `rounded-lg`   |
| Cards        | 12px   | `rounded-xl`   |
| Inputs       | 8px    | `rounded-lg`   |
| Modals       | 16px   | `rounded-2xl`  |
| Avatars      | 9999px | `rounded-full` |
| Badges/Pills | 9999px | `rounded-full` |

### Shadows

| Level | Tailwind    | Usage               |
| ----- | ----------- | ------------------- |
| sm    | `shadow-sm` | Cards at rest       |
| md    | `shadow-md` | Dropdowns, popovers |
| lg    | `shadow-lg` | Modals              |
| xl    | `shadow-xl` | Floating elements   |

---

## 6. Component Design

### Buttons

| Type          | Background    | Text          | Border        | Radius       |
| ------------- | ------------- | ------------- | ------------- | ------------ |
| Primary       | `emerald-600` | white         | none          | `rounded-lg` |
| Primary Hover | `emerald-500` | white         | none          | `rounded-lg` |
| Secondary     | white         | `emerald-600` | `emerald-200` | `rounded-lg` |
| Danger        | `red-600`     | white         | none          | `rounded-lg` |
| Ghost         | transparent   | `slate-600`   | none          | `rounded-lg` |

### Touch Targets

- Minimum: 44px × 44px (WCAG 2.5.5)
- Mobile: bottom-anchored primary buttons
- Spacing between touch targets: ≥ 8px

### Confirmation Modals

Every destructive or financial action requires:

1. Modal with clear title
2. Bilingual description of exact consequence
3. Confirm/Cancel buttons
4. Confirm button uses danger styling for destructive actions

---

## 7. Voice & Tone

### Voice Attributes

| Trait        | We Are                  | We Are Not               |
| ------------ | ----------------------- | ------------------------ |
| Professional | Expert, knowledgeable   | Stuffy, corporate jargon |
| Clear        | Direct, concise         | Vague, wordy             |
| Trustworthy  | Reliable, precise       | Uncertain, vague         |
| Energetic    | Dynamic, forward-moving | Hyperactive, chaotic     |

### Tone by Context

| Context             | Tone                   | Example                                                             |
| ------------------- | ---------------------- | ------------------------------------------------------------------- |
| Error messages      | Calm, solution-focused | "Stock insufficient for this order. Available: 12 units."           |
| Success states      | Brief, celebratory     | "Sale recorded. Invoice #1042 generated."                           |
| Empty states        | Helpful, bilingual     | "No visits today. Check with your manager for route assignments."   |
| Destructive actions | Serious, clear         | "This will reverse Invoice #1038 and restore 5 units to van stock." |

### Bilingual Rules

- Arabic is the primary language (RTL)
- English is secondary (LTR)
- All UI text must be bilingual
- Arabic text should be natural, not translated-from-English
- Numbers use Western Arabic numerals (1,2,3) consistently

---

## 8. Dark Mode (Filament Admin)

| Element        | Light                | Dark                  |
| -------------- | -------------------- | --------------------- |
| Background     | `slate-50` (#F8FAFC) | `slate-900` (#0F172A) |
| Surface/Cards  | white                | `slate-800` (#1E293B) |
| Text Primary   | `slate-800`          | `slate-100`           |
| Text Secondary | `slate-500`          | `slate-400`           |
| Borders        | `slate-200`          | `slate-700`           |
| Primary CTA    | `emerald-600`        | `emerald-500`         |

---

## 9. Accessibility

### Contrast Requirements (WCAG 2.1 AA)

| Combination            | Ratio  | Pass            |
| ---------------------- | ------ | --------------- |
| `slate-800` on white   | 14.5:1 | AAA             |
| `slate-600` on white   | 5.7:1  | AA              |
| `emerald-600` on white | 4.6:1  | AA              |
| white on `emerald-600` | 4.6:1  | AA              |
| `amber-600` on white   | 4.4:1  | AA (large text) |

### Requirements

- All interactive elements: focus ring visible (`ring-2 ring-emerald-500 ring-offset-2`)
- Skip navigation link on every page
- ARIA labels on all icon-only buttons
- Skeleton loaders on every list — never blank screens
- Empty states with friendly bilingual next action

---

## 10. Iconography

- Style: Outlined, 24px base grid
- Stroke: 1.5px consistent
- Corner radius: 2px
- Color: `slate-600` (default), `emerald-600` (active/selected)
- Use Heroicons or Lucide (consistent with Filament)

---

## Changelog

| Version | Date       | Changes                                                                |
| ------- | ---------- | ---------------------------------------------------------------------- |
| 1.0     | 2026-07-20 | Initial brand guidelines — logo, colors, typography, voice, components |
