# Investigation Case File: visit-stepper-visual-state-machine

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — gap M8 from `investigation-missing-ui-elements-2026-07-19.md`
**Severity:** Degraded UX / Incomplete spec implementation (REQ-CMP-2, B3-03)
**Status:** Open
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-visit-stepper-visual-state-machine-2026-07-19.md`

---

## Summary

**One-sentence description:**
The Visit Flow has a 3-step stepper (Scheduled → Arrived → Report) but the visual state machine is incomplete: the stepper doesn't show all D-02 states (Declined, GPS Denied), doesn't have proper accessibility attributes (aria-current, aria-label), doesn't show step completion status visually, and the state transitions are handled by raw `$step` property instead of a proper state machine.

**Expected behavior:** Per REQ-CMP-2 (competitor-derived, Must-Have) and B3-03:

- Stepper shows a **visible state machine**: Scheduled → Arrived (GPS) → Report → Done
- Each step has a visual state: completed (checkmark + green), current (number + blue), upcoming (number + gray)
- New D-02 states: **Declined** (red terminal), **GPS Denied** (red terminal)
- Transitions are animated (CSS transition on step width/background)
- Accessible: `aria-label` on each step, `aria-current="step"` on active step, screen reader announcements on state change
- Step connector lines fill with color as steps complete

**Actual behavior (current code):**

- Stepper renders 3 step dots with connector lines
- Step states: only "current" is styled (blue), others are gray
- No "completed" visual state (checkmark icon when done)
- No Declined/GPS Denied states (D-02 compliance)
- No accessibility attributes on step elements
- State managed by raw `$step` string property ('arrival', 'report', 'done')
- Connector lines don't fill dynamically

**User / business impact:** The Visit Flow is the primary screen reps interact with 5-10 times per day. An incomplete stepper makes the visit lifecycle unclear to the rep and fails the accessibility requirement (WCAG 2.2 §1.3.1, §4.1.2). The D-02 states (M3) cannot be displayed in the stepper without extending it.

---

## Symptom Details

**Trigger conditions:** Structural — every visit check-in attempt.

**Environments affected:**

- [x] Production
- [x] Staging
- [x] Development / local

**First observed:** 2026-07-19 (UI-completeness audit, Evidence 8)
**Frequency:** Constant (code-level incomplete)
**Reproducible:** Yes

**Reproduction steps:**

1. Log in as rep with assigned visit
2. Navigate to `/app/visit/{visit}`
3. Observe stepper: 3 dots, only current is blue, no checkmarks on completed steps
4. No Declined/GPS Denied visual states
5. Inspect HTML: no `aria-label` on step elements, no `aria-current`

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed, reproducible, or corroborated by 2+ independent sources.
> - **[B] Probable** — single source, code-read inference, or intermittent.
> - **[C] Speculative** — pattern match or untested hypothesis only.

### Evidence Item 1: Stepper renders 3 dots with minimal styling

**Grade:** [A]
**Source:** `resources/views/livewire/app/visit-flow.blade.php:15-35`
**Verbatim excerpt:**

```blade
<!-- visit-flow.blade.php stepper -->
<div class="flex items-center justify-between mb-6 px-2">
    <!-- Step 1: Scheduled -->
    <div class="flex flex-col items-center">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                    {{ $step === 'arrival' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500' }}">
            1
        </div>
        <span class="text-xs mt-1">{{ __('app.scheduled') }}</span>
    </div>
    <!-- Connector -->
    <div class="flex-1 h-0.5 mx-2 {{ $step !== 'arrival' ? 'bg-blue-600' : 'bg-gray-200' }}"></div>
    <!-- Step 2: Arrived -->
    <div class="flex flex-col items-center">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                    {{ $step === 'report' || $step === 'done' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500' }}">
            2
        </div>
        <span class="text-xs mt-1">{{ __('app.arrived') }}</span>
    </div>
    <!-- Connector -->
    <div class="flex-1 h-0.5 mx-2 {{ $step === 'done' ? 'bg-blue-600' : 'bg-gray-200' }}"></div>
    <!-- Step 3: Report -->
    <div class="flex flex-col items-center">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                    {{ $step === 'done' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500' }}">
            3
        </div>
        <span class="text-xs mt-1">{{ __('app.report') }}</span>
    </div>
</div>
```

**Description:** The stepper uses inline Tailwind classes with conditional `$step` comparisons. Steps show blue when "current" and gray otherwise. No checkmark icons for completed steps. No accessibility attributes.

**Implications:** Must be refactored to a proper state machine with: completed (green + checkmark), current (blue + number), upcoming (gray + number), and new D-02 states (Declined, GPS Denied).

---

### Evidence Item 2: Step states are raw strings, not an enum

**Grade:** [A]
**Source:** `app/Livewire/App/VisitFlow.php`
**Description:** The `$step` property is a string: `'arrival'`, `'report'`, `'done'`. No enum class, no state machine pattern. D-02 states (declined, gps_denied) would need to be added as new string values.

**Implications:** Create a proper `VisitStep` enum with: `Scheduled`, `Arrived`, `Report`, `Done`, `Declined`, `GpsDenied`. The stepper reads from this enum for visual state.

---

### Evidence Item 3: No accessibility attributes on stepper

**Grade:** [A]
**Source:** `visit-flow.blade.php:15-35`
**Description:** Step elements have no `aria-label`, no `aria-current="step"`, no `role="list"` on the container, no `role="listitem"` on steps. Screen readers cannot interpret the stepper.

**Implications:** Must add: `role="list"` on container, `role="listitem"` on each step, `aria-label` with step description, `aria-current="step"` on active step, `aria-disabled` on future steps.

---

### Evidence Item 4: Connector lines don't animate

**Grade:** [A]
**Source:** `visit-flow.blade.php` connector divs
**Description:** Connector lines switch between `bg-gray-200` and `bg-blue-600` based on `$step` value. No CSS transition for smooth fill animation.

**Implications:** Add `transition-all duration-300` to connector divs for smooth color transition.

---

### Evidence Item 5: No "Done" state with checkmark

**Grade:** [A]
**Source:** `visit-flow.blade.php:30-33` (step 3)
**Description:** When `$step === 'done'`, step 3 shows blue with number "3". No checkmark icon to indicate completion. The success screen replaces the entire stepper — no visual confirmation of completed steps.

**Implications:** Completed steps should show a checkmark icon (heroicon-o-check) instead of the step number. This is a standard stepper pattern.

---

### Evidence Item 6: Visit statuses map to stepper states but are incomplete

**Grade:** [A]
**Source:** `database/migrations/*create_visits_table.php`, `app/Models/Visit.php`
**Visit statuses:** `scheduled`, `arrived`, `reported`, `cancelled`
**D-02 additions (from M3 investigation):** `declined`, `gps_denied` (via `arrival_flag`)

**Implications:** Stepper must map visit status to visual state:

- `scheduled` → Step 1 (Scheduled) active
- `arrived` → Step 2 (Arrived) active, Step 1 completed
- `reported` → Step 3 (Report) completed, Steps 1-2 completed
- `declined` → Step 1 active, Declined terminal state (red)
- `gps_denied` → Step 1 active, GPS Denied terminal state (red)
- `cancelled` → All steps gray, Cancelled badge

---

### Evidence Summary

| #   | Title                                       | Grade | Source                     | Key Implication           |
| --- | ------------------------------------------- | ----- | -------------------------- | ------------------------- |
| 1   | Stepper renders 3 dots with minimal styling | A     | visit-flow.blade.php:15-35 | Refactor to state machine |
| 2   | Step states are raw strings                 | A     | VisitFlow.php              | Create VisitStep enum     |
| 3   | No accessibility attributes                 | A     | visit-flow.blade.php       | Add ARIA labels           |
| 4   | Connector lines don't animate               | A     | visit-flow.blade.php       | Add CSS transitions       |
| 5   | No checkmark on completed steps             | A     | visit-flow.blade.php       | Add heroicon check        |
| 6   | Visit statuses incomplete                   | A     | Visit.php, migration       | Add D-02 states           |

---

## Hypotheses

### Hypothesis 1 — The stepper was built as a minimal visual indicator, not a full state machine [Plausibility: High]

**Statement:** The developer implemented the simplest possible 3-step indicator (dots + numbers) without planning for accessibility, animation, or additional states. The REQ-CMP-2 requirement says "visible state machine" but the implementation is just a visual cue.

**Supporting evidence:**

- Evidence 1, 3, 4 [A] — minimal implementation with no accessibility, no animation
- No enum or state machine pattern — raw string comparison

**Contradicting evidence:** None.

**Verification step:** Check git blame — was this built quickly as part of a larger feature?

---

### Hypothesis 2 — D-02 states (Declined, GPS Denied) weren't added because M3 wasn't done [Plausibility: Medium]

**Statement:** The stepper doesn't show Declined/GPS Denied because the geofence blocking dialogs (M3) aren't implemented yet. The stepper was built for the current 3 states only.

**Supporting evidence:**

- M3 investigation confirms D-02 states are not written to `arrival_flag`
- Stepper can't show states that don't exist in the data model yet

**Contradicting evidence:** The stepper could show visual states based on client-side `$step` property even without DB persistence.

**Verification step:** Check if `arrival_flag` values are referenced anywhere in VisitFlow.php.

---

### Hypothesis 3 — Accessibility was deferred as a nice-to-have [Plausibility: Medium]

**Statement:** ARIA attributes and screen reader support were known requirements but deferred during development.

**Supporting evidence:**

- `x-tab-bar` has `aria-label="Bottom navigation"` — accessibility was considered for some components
- But stepper has zero ARIA attributes

**Contradicting evidence:** The accessibility skill (a11y) exists in the project's skill set — accessibility is a known concern.

**Verification step:** Check if a11y was part of B0 scope or deferred.

---

## Suspected Components

### Component: Visit Flow Blade View (`resources/views/livewire/app/visit-flow.blade.php`)

| Attribute      | Detail                                                      |
| -------------- | ----------------------------------------------------------- |
| Type           | UI view                                                     |
| File / path    | `resources/views/livewire/app/visit-flow.blade.php:15-35`   |
| Responsibility | Render stepper with proper states, accessibility, animation |
| Confidence     | High (grade-A)                                              |

**Why suspected:** Evidence 1, 3, 4, 5 — all visual/accessibility issues are here.

**Blast radius:**

- Refactor stepper to read from a `VisitStep` enum or state array
- Add checkmark icons for completed steps
- Add CSS transitions on connectors
- Add ARIA attributes
- Support Declined/GPS Denied states from M3

---

### Component: VisitFlow Livewire Component (`app/Livewire/App/VisitFlow.php`)

| Attribute      | Detail                                            |
| -------------- | ------------------------------------------------- |
| Type           | UI module                                         |
| File / path    | `app/Livewire/App/VisitFlow.php`                  |
| Responsibility | Manage step state transitions, write arrival_flag |
| Confidence     | High                                              |

**Why suspected:** Evidence 2, 6 — raw string state must become enum-based.

**Blast radius:**

- Create `app/Enums/VisitStep.php` enum
- Replace `$step` string property with `VisitStep` enum
- Write `arrival_flag` on state transitions (M3 dependency)

---

### Component: Visit Model (`app/Models/Visit.php`)

| Attribute      | Detail                                 |
| -------------- | -------------------------------------- |
| Type           | Domain model                           |
| File / path    | `app/Models/Visit.php`                 |
| Responsibility | Visit status enum with all D-02 states |
| Confidence     | High                                   |

**Why suspected:** Evidence 6 — status enum needs extension.

**Blast radius:**

- Add `declined`, `gps_denied` to status enum (or use `arrival_flag` as source of truth)
- Add `VisitStep` enum for UI state

---

### Component: Translations (`lang/en/app.php`, `lang/ar/app.php`)

| Attribute      | Detail                          |
| -------------- | ------------------------------- |
| Type           | Localization                    |
| Responsibility | Step labels, state descriptions |

**Blast radius:** Add: `step_scheduled`, `step_arrived`, `step_report`, `step_done`, `step_declined`, `step_gps_denied`, `visit_completed`, `visit_declined`, `visit_gps_denied`.

---

## Related Requirements

| Requirement                                        | Source              | Status                                      |
| -------------------------------------------------- | ------------------- | ------------------------------------------- |
| REQ-CMP-2 visit stepper UI — visible state machine | PRD v1.1 §2         | **Incomplete** (3 states, no D-02, no a11y) |
| REQ-CMP-10 / D-02 geofence states                  | Decision register   | At Risk (depends on M3)                     |
| B0-02 standard UI states                           | PRD v1.1 §2         | At Risk                                     |
| WCAG 2.2 §1.3.1, §4.1.2 — accessibility            | Accessibility skill | **Violated**                                |

---

## Recommended Action

**Planning Response:** Option B — Update an existing backlog story (M3 depends on this; should be batched)

**Rationale:** The stepper visual state machine (M8) is a prerequisite for D-02 states (M3). They should be planned together: M3 provides the blocking dialogs + arrival_flag writes, M8 provides the stepper that reflects all states.

**Recommended next skill:** `/bmad-planning-orchestrator:bmad-epics-and-stories` — create a single story covering both M3 (geofence blocking) and M8 (stepper state machine) as they are tightly coupled.

---

## Open Questions

1. **Enum vs. string:** Should `VisitStep` be a PHP enum or continue as a string? Enums provide type safety and IDE support but require PHP 8.1+ (project uses 8.3 — fine).

2. **Animation library:** Should the stepper use CSS transitions (lightweight) or a JS animation library like GSAP? CSS transitions are sufficient for this use case.

3. **Stepper height on mobile:** The current stepper is compact. With 5 states (Scheduled, Arrived, Report, Done, Declined/GPS Denied), should it be a horizontal scrollable stepper or a vertical stepper on narrow screens?

4. **Screen reader announcements:** When the step changes (e.g., GPS confirmed → Arrived), should there be an `aria-live="polite"` region announcing the state change? Yes — WCAG 4.1.3 requires status messages.

---

## Update History

| Version | Date       | Summary                         |
| ------- | ---------- | ------------------------------- |
| 1.0     | 2026-07-19 | Initial investigation case file |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
