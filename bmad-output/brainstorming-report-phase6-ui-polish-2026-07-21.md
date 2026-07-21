# Brainstorming Session Report — Phase 6 UI Polish

**Date:** 2026-07-21
**BMAD Track:** standard (3 techniques, inline — no subagents)
**Intent:** Create
**Topic:** "Think about all tasks in Phase 6" — the UI-polish phase of the
gap-closure plan (`plans/whimsical-squishing-cosmos.md`).

---

## Session objective

Systematically think through every Phase 6 task, grounded in the **current**
codebase rather than the source review docs — because a concurrent effort has
been actively fixing these blades and CSS since the review was written.

**Sources:** `docs/REP_UI_REVIEW.md` (20 issues, 2026-07-20),
`docs/UI_UX_GAP_BRAINSTORMING_REPORT.md` (cross-cutting gaps, 2026-07-19), and a
live audit of `resources/css/app.css`, `resources/views/components/ds/*`, and the
rep blades.

**Constraints carried in:** bilingual AR/EN + RTL from day one; money/stock only
via Services in transactions; reversal is a compensating transaction (never
`delete()`); no new packages without approval; rep blades are **contended** with
a concurrent effort.

---

## Headline insight (reframes the whole phase)

**Phase 6 is ~85% already delivered.** The review doc is substantially stale. A
live audit shows the top-priority items are done:

| Review item (priority) | Status in current code | Evidence |
|---|---|---|
| #2 Modal scrim (P1) | ✅ Done | `dialog::backdrop { background: rgba(0,0,0,.5) }` + `modalFadeIn` (`app.css:1261`) |
| #14 Form-row collapse (P2) | ✅ Done | `@media (max-width:359px)` (`app.css:1370`) |
| #8 Dark mode (P2, was deferred to v1.1) | ✅ Added | `@media (prefers-color-scheme: dark)` (`app.css:1399`) — **but unverified** |
| #1 Icon library (P2) | ✅ Mostly | `blade-ui-kit/blade-heroicons ^2.5` installed; tab-bar/page-header no longer inline `<svg>` |
| #13 Responsive breakpoints (P2) | ✅ Done | `max-width:359px`, `min-width:768px` |
| #15 Landscape handling (P2) | ✅ Done | `@media (orientation:landscape) and (max-height:500px)` |
| #4 Notification bell 44px (P3) | ✅ Done | `.notification-fab { width:44px;height:44px }` (`app.css:1291`) |
| #11 Focus mgmt on success screens (P3) | ✅ Done | `tabindex="-1"` + `$el.focus()` in sales-flow & visit-flow done/queued screens |
| #19 RTL `text-end` (P3) | ✅ Done | no `text-right`/`text-left` left in rep blades |
| #20 Scroll-to-top on steps (P3) | ✅ Done | present in sales-flow & visit-flow |

So "do Phase 6" is **not** a big build. It's a **small remaining set + a QA/verify pass on what shipped**.

---

## Technique 1 — Mind Mapping (organize all Phase 6 tasks)

```
Phase 6 UI Polish
├── A. DONE — verify & close (no build)
│   ├── Modal scrim ✅
│   ├── Responsive breakpoints (<360 / 768 / landscape) ✅
│   ├── Icon library migration (heroicons) ✅ (verify stragglers)
│   ├── Notification bell 44px ✅
│   ├── Focus mgmt on success screens ✅
│   ├── RTL text-end, scroll-to-top ✅
│   └── Token :root/@theme dedup ~ (verify one source)
├── B. GENUINELY REMAINING (build)
│   ├── B1. Undo toast on the 8 mutating pages   [biggest — H impact / M-L feas]
│   ├── B2. Dark-mode QA pass (shipped untested)  [H risk / M feas]
│   ├── B3. Pull-to-refresh on list pages         [M impact / M feas]
│   ├── B4. Font-size scaling (tab labels 0.7→0.75rem; badge px→rem) [L/L]
│   ├── B5. Skeleton aria-hidden verify (#12)      [L/S]
│   └── B6. Signature-canvas DPR/responsive sizing (#10/#18) [M a11y / M]
├── C. DEV-EX / QA AID (optional)
│   └── C1. Authenticated style-guide route (G3) — all component states × AR/EN × light/dark
└── D. EXPLICITLY OUT (v1.1+)
    └── Onboarding walkthrough, push notifications, route-optimization map
```

---

## Technique 2 — SCAMPER (variations for the remaining items)

**S — Substitute:** Replace per-page bespoke undo with **one global "action
toast"** listening to a single Livewire event `action-completed {label, type, id}`;
each page dispatches it instead of hand-rolling a toast. Substitute any residual
inline `<svg>` with `<x-heroicon-*>`.

**C — Combine:** Merge the undo toast with the **offline sync-status** feedback
already built — one unified "activity toast": online → "Sale saved ✓ [Undo]";
offline → "Queued ✓ [Discard]". Combine the style-guide route with a
light/dark × AR/EN matrix so dark-mode QA and RTL QA happen in one place.

**A — Adapt:** Adapt Gmail "Undo Send": act immediately, offer a short undo
window. Adapt the **existing outbox `discard()`** as the pre-sync undo and
**`ReversalService`** as the post-sync undo — no new domain logic.

**M — Modify/Magnify / Minify:** Make the undo window a company setting (30–60s).
**Minify dark-mode risk** by shipping it behind an explicit user toggle
(opt-in) rather than auto `prefers-color-scheme`, until contrast is QA'd.

**P — Put to other use:** The `<x-ds.toast>` component already exists but is used
only in `profile.blade.php` — put it to use for undo. Put the style-guide route
to use as a visual-regression baseline for screenshots.

**E — Eliminate:** Eliminate `:root` token duplication (single source in
`@theme`). Eliminate hardcoded `px` font sizes that don't scale.

**R — Reverse/Rearrange:** Reverse the interaction model for **reversible**
actions — act-then-undo (faster field flow) — while **keeping the confirm modal
for irreversible money actions**. The two patterns coexist by action class.

---

## Technique 3 — Reverse Brainstorming ("how could Phase 6 make it worse?")

1. **Clobber the concurrent effort.** Editing contended rep blades mid-refactor
   → merge loss. *(Highest practical risk.)*
2. **Dark mode ships broken.** It was added but the review deferred it to v1.1 —
   an **unverified surface**: low-contrast text, invisible signature canvas on
   dark, status colors failing WCAG, RTL + dark interaction bugs.
3. **Undo that deletes.** An undo wired to `delete()` instead of
   `ReversalService` violates the "reversal is a compensating transaction, never
   delete" rule → corrupts the financial audit trail.
4. **Undo races the sync flush.** Undoing an offline-queued sale while the outbox
   is flushing → double-cancel or orphaned outbox row.
5. **Pull-to-refresh vs. `overscroll-behavior:contain` + PWA** → janky scroll,
   accidental refresh losing in-progress form state.
6. **Token dedup breakage.** Removing `:root` while something still references it
   → app-wide theme break.
7. **Icon migration regressions.** Stroke-width/size drift across every screen.

**What this tells us:** the remaining *build* is small, but the biggest **risk**
is already in the tree — **untested dark mode** and a **semantically wrong undo**.
Verification outranks new building.

---

## Organized ideas (Impact × Feasibility)

| Task | Impact | Feasibility | Bucket |
|---|---|---|---|
| B2. Dark-mode QA pass (contrast, canvas, RTL, status colors) | High | Medium | **Do first — de-risk what shipped** |
| B1. Undo toast (global event + discard/reversal branch) | High | Medium | Do — highest-value new feature |
| A. Verify-and-close the ~9 done items | Med (confidence) | High | Quick pass |
| B3. Pull-to-refresh on list pages | Med | Medium | Optional |
| B6. Signature-canvas DPR sizing | Med (a11y) | Medium | Optional |
| C1. Style-guide route | Med (dev-ex) | Small | Optional, enables B2 QA |
| B4/B5. Font scaling, skeleton aria | Low | Small | Batch micro-fixes |
| Token dedup (E) | Low (maint) | Small | Batch micro-fixes |

---

## Top insights (actionable)

1. **Phase 6 is a verify-and-finish, not a build.** ~85% shipped; treat the
   review doc as historical and work from the live audit above.
2. **Dark mode is the sleeper risk.** It's live but untested and was explicitly
   out of scope for beta — QA it or gate it behind an opt-in toggle before it
   reaches reps in the field.
3. **Undo must reuse existing machinery** — outbox `discard()` before sync,
   `ReversalService` after — delivered as one global action-toast, never a
   `delete()`.
4. **Sequence around the concurrent effort.** The remaining work is CSS + a
   shared toast component + one style-guide route — mostly **new files / CSS
   blocks**, low collision. Do the rep-blade touches (undo dispatch, pull-to-
   refresh) only once their churn settles or by taking short exclusive ownership.
5. **A style-guide route pays for itself** as the QA surface for dark mode, RTL,
   and every component state in one place.

## Risks

- Contended rep-blade collisions (coordination).
- Untested dark mode reaching production.
- Undo semantics vs. immutable-reversal rule and the offline outbox.
- Pull-to-refresh vs. existing overscroll/PWA behavior.
- Cosmetic regressions from icon/token cleanup.

## Recommended next steps

1. **Decision needed (see decision-log):** is rep dark mode **in** for this
   release (then QA it) or **out** (gate behind opt-in / revert the media query)?
2. If Phase 6 proceeds: order = **B2 dark-mode QA → B1 undo toast → A verify-and-
   close → C1 style-guide → batch micro-fixes**, deferring rep-blade edits until
   the concurrent effort settles.
3. Hand off to **bmad-epics-and-stories** to turn B1 (undo) and B2 (dark-mode QA)
   into stories; the rest is a single "UI polish micro-fixes" story.
