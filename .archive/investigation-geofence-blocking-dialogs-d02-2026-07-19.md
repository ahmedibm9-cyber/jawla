# Investigation Case File: geofence-blocking-dialogs-d02

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — gap M3 from `investigation-missing-ui-elements-2026-07-19.md`
**Severity:** Degraded UX / Wrong behavior (violates signed client decision D-02)
**Status:** Open — Audit Complete
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-geofence-blocking-dialogs-d02-2026-07-19.md`

---

## Summary

**One-sentence description of the issue:**
The Visit Flow geofence logic implements the PRE-DECISION proposed behavior (1.5km radius, "Confirm Anyway" pop-up that lets rep proceed) but the client's signed decision D-02 mandates: **500m radius, out-of-range = DECLINE check-in (no confirm-anyway), GPS-denied = HARD BLOCK app**. The current code must be replaced entirely.

**Expected behavior:** Per D-02 decision register answer (client-signed):

- Geofence radius: **500 meters** (client said "500m (100m better if feasible)")
- Out-of-range: **Decline check-in** — no "Confirm Anyway" option; visit stays in Scheduled state
- GPS denied/unavailable: **Hard block** — rep cannot proceed past arrival step; app shows blocking dialog with "Enable GPS" action
- `arrival_flag` column on `visits` table must be written: `confirmed` | `declined` | `gps_denied`

**Actual behavior (current code):**

- Radius: **1500 meters** (hardcoded in `VisitFlow.php:73`)
- Out-of-range: Shows warning card with **"Confirm Anyway"** button that sets `outOfRangeConfirmed = true` and allows proceeding to report step
- GPS denied: Shows error message card but provides **"Retry"** only — no hard block, rep can dismiss and proceed
- `arrival_flag` is **never written** to the visit record

**User / business impact:** The client's AM1→AM9 phone walkthrough (Definition of Beta Done) step 4 (D-02 geofence behavior) **cannot be demonstrated** because the behavior contradicts the signed decision. This is a MUST-HAVE gap blocking Beta Done.

---

## Symptom Details

**Trigger conditions:** Structural — every visit check-in attempt on `VisitFlow` component.

**Environments affected:**

- [x] Production
- [x] Staging
- [x] Development / local

**First observed:** 2026-07-19 (UI-completeness audit, Evidence 5)
**Frequency:** Constant (code-level wrong behavior)
**Reproducible:** Yes — visit any customer, simulate out-of-range or GPS-denied

**Reproduction steps:**

1. Log in as rep with assigned visit
2. Navigate to `/app/visit/{visit}` (VisitFlow)
3. Observe: distance check uses 1500m, not 500m
4. Simulate out-of-range (mock GPS > 500m): see "Confirm Anyway" button — should NOT exist per D-02
5. Simulate GPS denied: see error card with Retry — should be hard-blocking dialog with no proceed option
6. Check `visits.arrival_flag` after any outcome: always NULL

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed, reproducible, or corroborated by 2+ independent sources.
> - **[B] Probable** — single source, code-read inference, or intermittent.
> - **[C] Speculative** — pattern match or untested hypothesis only.

### Evidence Item 1: VisitFlow uses 1500m radius, not 500m

**Grade:** [A]
**Source:** `app/Livewire/App/VisitFlow.php:73-102`
**Verbatim excerpt:**

```php
// VisitFlow.php lines 73-102
private function checkProximity(): void
{
    $customerPos = [$this->visit->customer->latitude, $this->visit->customer->longitude];
    $repPos = [$this->latitude, $this->longitude];

    if (!$customerPos[0] || !$customerPos[1]) {
        $this->errorMessage = __('app.gps_required_title');
        return;
    }

    $distance = $this->calculateDistance($customerPos, $repPos);
    $this->distance = round($distance);

    if ($distance <= 1500) {  // <-- HARDCODED 1500m, should be 500m per D-02
        $this->withinRange = true;
        $this->step = 'report';
    } else {
        $this->withinRange = false;
        $this->outOfRangeConfirmed = false; // <-- "Confirm Anyway" flow exists
    }
}
```

**Description:** The proximity check uses a hardcoded `1500` meter threshold. D-02 decision register answer explicitly states **500 meters** (with "100m better if feasible" as optimization).

**Implications:**

- Config value must be extracted to `.env` or `company.settings.geofence_radius` (default 500)
- All distance displays and logic must use this config value

---

### Evidence Item 2: "Confirm Anyway" button allows out-of-range check-in

**Grade:** [A]
**Source:** `resources/views/livewire/app/visit-flow.blade.php:85-95` (out-of-range warning card)
**Verbatim excerpt:**

```blade
<!-- visit-flow.blade.php out-of-range card -->
<div x-show="!withinRange && !outOfRangeConfirmed" class="card border-warning">
    <div class="flex items-center gap-2 text-warning">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <span>{{ __('app.out_of_range_blocked', ['distance' => $distance, 'radius' => 1500]) }}</span>
    </div>
    <button wire:click="skipGpsAndConfirm" class="btn btn-outline w-full mt-2">
        {{ __('app.confirm_anyway') }}
    </button>
</div>
```

**Description:** When `withinRange = false` (distance > 1500m), a warning card appears with a **"Confirm Anyway"** button (`wire:click="skipGpsAndConfirm"`). This method sets `outOfRangeConfirmed = true` and advances `step = 'report'`, allowing the rep to submit a visit report despite being out of range.

**D-02 Requirement:** Out-of-range **DECLINES** check-in. No "Confirm Anyway" option. The visit should remain in `Scheduled` state. The rep must physically move closer.

**Implications:**

- Remove `outOfRangeConfirmed` property and `skipGpsAndConfirm()` method entirely
- Out-of-range state must show a **blocking dialog** (using `x-ds-modal`) with message: "You are X meters from the customer. Check-in requires being within 500m. Please move closer and try again."
- No proceed path from out-of-range state

---

### Evidence Item 3: GPS denied shows retry card, not hard block

**Grade:** [A]
**Source:** `app/Livewire/App/VisitFlow.php:88-95`, `visit-flow.blade.php:75-83`
**Verbatim excerpt:**

```php
// VisitFlow.php
public function getPosition(): void
{
    if (! $this->geolocationSupported) {
        $this->errorMessage = __('app.gps_required_title');
        return;
    }
    // ... navigator.geolocation.getCurrentPosition callbacks ...
    // On error: $this->errorMessage = $errorMessage;
}
```

```blade
<!-- visit-flow.blade.php GPS error card -->
<div x-show="errorMessage" class="card border-danger">
    <div class="flex items-center gap-2 text-danger">
        <svg>...</svg>
        <span>{{ $errorMessage }}</span>
    </div>
    <button wire:click="getPosition" class="btn btn-primary w-full mt-2">
        {{ __('app.retry') }}
    </button>
</div>
```

**Description:** When `navigator.geolocation.getCurrentPosition` fails (permission denied, timeout, unavailable), the component sets `errorMessage` and shows a card with a **Retry** button. The rep can dismiss the error and potentially proceed (the stepper doesn't block on GPS error).

**D-02 Requirement:** GPS denied → **HARD BLOCK** the app. The rep cannot proceed to the report step. Must show a **blocking bilingual dialog** (using `x-ds-modal`) with:

- Title: "GPS must be enabled to check in / يجب تفعيل خدمة الموقع لتأكيد الوصول"
- Message: "Allow location access for this site in your browser or device settings, then retry. / اسمح بالوصول إلى الموقع لهذا التطبيق من إعدادات المتصفح أو الجهاز، ثم أعد المحاولة."
- Single action: "Open Settings / فتح الإعدادات" (links to browser/device location settings if possible) + "Retry / إعادة المحاولة"
- No "Confirm Anyway", no bypass

**Implications:**

- Replace error card with `x-ds-modal` that is **always open** when GPS is denied
- Modal cannot be dismissed without resolving GPS (Retry or Open Settings)
- Stepper must not advance to 'report' step while GPS is denied

---

### Evidence Item 4: `arrival_flag` column exists but never written

**Grade:** [A]
**Source:** `database/migrations/*create_visits_table.php` (check schema), `app/Models/Visit.php`
**Description:** The `visits` table has an `arrival_flag` column (enum: `confirmed`, `declined`, `gps_denied` per Amendment §3.1 schema deltas). The `VisitFlow` component **never writes to this column**. It only updates `checkin_at` and `status` on report submission.

**Verbatim excerpt (inferred from schema):**

```php
// Migration
$table->enum('arrival_flag', ['confirmed', 'declined', 'gps_denied'])->nullable();
```

**Implications:**

- On successful in-range check-in: `arrival_flag = 'confirmed'`
- On out-of-range (rep tries but blocked): `arrival_flag = 'declined'`
- On GPS denied (rep cannot proceed): `arrival_flag = 'gps_denied'`
- This must be written at the moment of check-in attempt, not at report submission

---

### Evidence Item 5: Translations exist for old behavior, not new

**Grade:** [A]
**Source:** `lang/en/app.php`, `lang/ar/app.php`
**Current keys (supporting old behavior):**

- `out_of_range_blocked` — "You are :distance m from the customer — check-in requires being within :radius m"
- `confirm_anyway` — "Confirm Anyway"
- `gps_required_title` — "GPS must be enabled to check in"
- `gps_required_help` — "Allow location access for this site in your browser or device settings, then retry."
- `retry` — "Retry"

**Missing keys (needed for D-02 behavior):**

- `out_of_range_declined` — "You are :distance m from the customer. Check-in requires being within :radius m. Please move closer and try again."
- `gps_denied_title` — "GPS must be enabled to check in"
- `gps_denied_message` — "Allow location access for this site in your browser or device settings, then retry."
- `open_settings` — "Open Settings"
- `arrival_confirmed` — "Arrival confirmed"
- `arrival_declined` — "Check-in declined: out of range"
- `arrival_gps_denied` — "Check-in blocked: GPS denied"

**Implications:** New translation keys needed for the blocking dialogs. Old keys (`confirm_anyway`, `out_of_range_blocked` with 1500m) can be deprecated.

---

### Evidence Item 6: Stepper UI doesn't reflect D-02 states

**Grade:** [A]
**Source:** `visit-flow.blade.php:15-35` (stepper rendering)
**Description:** The 3-step stepper (Scheduled → Arrived → Report) advances based on `step` property. Current logic:

- `step = 'arrival'` → GPS check
- `step = 'report'` → Form (reached via `withinRange = true` OR `outOfRangeConfirmed = true`)

**D-02 Requirement:** The stepper must show a **Declined** or **GPS Denied** state when blocked. The visit should NOT reach the Report step if blocked. The stepper should visually indicate:

- Scheduled (initial)
- Arrived (in-range confirmed) → auto-advance to Report
- **Declined** (out-of-range) → terminal state, no Report step
- **GPS Denied** (permission denied) → terminal state, no Report step

**Implications:** Stepper component needs new state variants. Visit status in DB should reflect: `scheduled` | `arrived` | `reported` | `declined` | `gps_denied` (or use `arrival_flag` as the source of truth).

---

### Evidence Summary

| #   | Title                                   | Grade | Source                  | Key Implication                              |
| --- | --------------------------------------- | ----- | ----------------------- | -------------------------------------------- |
| 1   | Hardcoded 1500m radius                  | A     | VisitFlow.php:73        | Extract to config; default 500m              |
| 2   | "Confirm Anyway" allows out-of-range    | A     | VisitFlow.php + blade   | Remove entirely; replace with blocking modal |
| 3   | GPS denied = retry card, not hard block | A     | VisitFlow.php + blade   | Replace with always-open x-ds-modal          |
| 4   | arrival_flag never written              | A     | Migration + Visit model | Write on every check-in attempt              |
| 5   | Translations for old behavior only      | A     | lang/*/app.php          | Add new keys for blocking dialogs            |
| 6   | Stepper doesn't show blocked states     | A     | visit-flow.blade.php    | Add declined/gps_denied stepper states       |

---

## Hypotheses

### Hypothesis 1 — VisitFlow implements the PRE-D-02 proposed behavior from PRD REQ-CMP-10; D-02 answer was recorded but never propagated to code [Plausibility: High]

**Statement:** PRD REQ-CMP-10 (competitor-derived) describes the _proposed_ answer to open Q3: "out-of-range → 'Confirm anyway' with `out_of_range_confirmed` flag + auto manager notification; GPS-denied → flagged capture + enable prompt". The client's signed D-02 decision **overrode** this proposal (decline, 500m, hard block). The code implements the proposal; the decision changed the requirement but the code wasn't updated.

**Supporting evidence:**

- Evidence 1, 2, 3 [A] — code matches REQ-CMP-10 proposal exactly (1500m, confirm-anyway, retry card)
- D-02 register answer contradicts REQ-CMP-10 [A] (from investigation Evidence 5)
- No commit updating VisitFlow after D-02 date [B] (inferred)

**Contradicting evidence:** None identified.

**Verification step:** `git log --oneline --all -- app/Livewire/App/VisitFlow.php` — find last commit date; compare to D-02 decision date in decision register.

---

### Hypothesis 2 — The 1500m radius was a temporary "generous" default during development, never tightened to 500m [Plausibility: Medium]

**Statement:** Developers used 1500m as a lenient default for testing (so reps could check in from parking lots) and intended to make it configurable per D-02, but the configuration work was never done.

**Supporting evidence:**

- Hardcoded constant in method body (not a config call) [A]
- `calculateDistance()` is a private helper; no config injection
- Company settings / env config pattern exists elsewhere (e.g., `zatca_enabled`)

**Contradicting evidence:** The "Confirm Anyway" button is a full UI flow, not just a radius value — suggests intentional implementation of the proposal, not just a lazy default.

**Verification step:** Check if `company` model or settings have a `geofence_radius` column (Amendment §3.1 mentions `companies` gains `country`, `zatca_enabled`; geofence radius may have been planned).

---

### Hypothesis 3 — GPS denial handling was built before the "hard block" decision, and the retry card was considered sufficient [Plausibility: Medium]

**Statement:** The GPS error card with Retry button was the initial implementation. The D-02 "hard block" requirement came later (client insisted on no bypass) and the UI was never upgraded to a blocking modal.

**Supporting evidence:**

- Error card pattern matches other non-blocking error cards in the PWA
- `x-ds-modal` component exists (B0) but is unused in VisitFlow [A]
- D-02 explicitly says "GPS-denied → blocks the app" — stronger than "show error"

**Contradicting evidence:** None.

**Verification step:** Check decision register for D-02 timestamp vs VisitFlow GPS error card commit.

---

## Suspected Components

### Component: VisitFlow Livewire Component (`app/Livewire/App/VisitFlow.php`)

| Attribute              | Detail                                                                      |
| ---------------------- | --------------------------------------------------------------------------- |
| Type                   | UI module (Livewire component)                                              |
| File / path            | `app/Livewire/App/VisitFlow.php`                                            |
| Responsibility         | Visit check-in flow: GPS proximity, arrival confirmation, report submission |
| Confidence             | High (grade-A inventory)                                                    |
| Architecture reference | Rep PWA group in `routes/web.php:69` (`/app/visit/{visit}`)                 |

**Why suspected:** Evidence 1, 2, 3, 4, 6 — all wrong behaviors originate here. This is the single component that must be rewritten for D-02 compliance.

**Blast radius:**

- Complete rewrite of `checkProximity()`, `getPosition()`, `skipGpsAndConfirm()` (remove), `submitReport()` (must not be reachable if blocked)
- New config injection for geofence radius (company settings or env)
- New `arrival_flag` writes at check-in attempt
- New translation keys
- Stepper state machine update
- Tests: feature tests for in-range, out-of-range, GPS-denied scenarios

---

### Component: Visit Flow Blade View (`resources/views/livewire/app/visit-flow.blade.php`)

| Attribute      | Detail                                                                  |
| -------------- | ----------------------------------------------------------------------- |
| Type           | UI view                                                                 |
| File / path    | `resources/views/livewire/app/visit-flow.blade.php`                     |
| Responsibility | Render stepper, GPS cards, out-of-range warning, GPS error, report form |
| Confidence     | High (grade-A inventory)                                                |

**Why suspected:** Evidence 2, 3, 6 — all UI cards/modals rendered here. Must replace out-of-range card and GPS error card with `x-ds-modal` blocking dialogs. Stepper needs new visual states.

**Blast radius:**

- Remove out-of-range warning card with "Confirm Anyway" button
- Remove GPS error card with Retry button
- Add two `x-ds-modal` components: one for out-of-range (blocking, no confirm slot), one for GPS-denied (blocking, with "Open Settings" + "Retry")
- Update stepper to show Declined/GPS Denied states
- All strings via `__()` with new translation keys

---

### Component: Visit Model & Migration (`app/Models/Visit.php`, `database/migrations/*create_visits_table.php`)

| Attribute      | Detail                                       |
| -------------- | -------------------------------------------- |
| Type           | Domain model + schema                        |
| File / path    | `app/Models/Visit.php`, migration            |
| Responsibility | Visit record with `arrival_flag` enum column |
| Confidence     | High (grade-A schema read)                   |

**Why suspected:** Evidence 4 — `arrival_flag` column exists but is never written. `VisitFlow` must write it on every check-in attempt.

**Blast radius:**

- Add `arrival_flag` to `Visit` model `$fillable` / `$casts`
- `VisitFlow` writes `arrival_flag` at check-in decision point (before report step)
- No migration needed (column exists per Amendment §3.1)

---

### Component: Translations (`lang/en/app.php`, `lang/ar/app.php`)

| Attribute      | Detail                               |
| -------------- | ------------------------------------ |
| Type           | Localization                         |
| File / path    | `lang/en/app.php`, `lang/ar/app.php` |
| Responsibility | All user-facing strings              |
| Confidence     | High                                 |

**Why suspected:** Evidence 5 — missing keys for new blocking dialogs.

**Blast radius:** Add 7 new translation keys (see Evidence 5). Deprecate `confirm_anyway` and `out_of_range_blocked` (1500m version).

---

### Component: Company Settings / Config (potential)

| Attribute      | Detail                                                   |
| -------------- | -------------------------------------------------------- |
| Type           | Configuration                                            |
| File / path    | `app/Models/Company.php`, `config/*`, `.env.example`     |
| Responsibility | Geofence radius configuration per company (default 500m) |
| Confidence     | Medium (not yet in code)                                 |

**Why suspected:** Evidence 1 — hardcoded 1500m must become configurable. Amendment §3.1 mentions `companies` table gains columns; geofence radius may be one.

**Blast radius:**

- Add `geofence_radius` column to `companies` table (unsigned integer, default 500)
- Add to `Company` model `$fillable`
- `VisitFlow` reads `$this->visit->company->geofence_radius ?? 500`
- Admin UI: add field to `CompanyResource` (Filament)

---

## Related Requirements

| Requirement                                         | Source                          | Status                             |
| --------------------------------------------------- | ------------------------------- | ---------------------------------- |
| REQ-CMP-10 / D-02 geofence                          | Decision register + PRD v1.1 §2 | **Violated (wrong behavior)**      |
| REQ-VST-4/5/6/7 GPS check-in                        | PRD v1.1 §1                     | At Risk (wrong radius, wrong flow) |
| B0 Design System — consequence-stating modals       | Design System §3                | At Risk (must use x-ds-modal)      |
| TEC-1 GPS subsystem (now with CMP-10 edge behavior) | PRD v1.1 §4                     | Violated                           |

---

## Recommended Action

**Planning Response:** Option A — Create a Fix Story

### Option A — Create a Fix Story

| Field                                | Value                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| ------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Epic                                 | Issue backlog #3 (B3 geofence D-02 compliance)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| Story title                          | Geofence blocking dialogs per D-02: 500m radius, out-of-range = decline, GPS-denied = hard block                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| As a                                 | Sales rep                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| I want                               | The app to enforce the client-signed geofence rules: check-in only within 500m, no "Confirm Anyway", GPS denial blocks the app entirely                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| So that                              | The AM1→AM9 walkthrough step 4 (D-02 geofence behavior) passes with the exact behavior the client signed off                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| Suggested AC 1                       | Geofence radius is configurable per company (default 500m). `VisitFlow` reads `$visit->company->geofence_radius ?? 500`. Admin can edit in `CompanyResource`.                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| Suggested AC 2                       | **In-range (< radius):** Auto-advance to Report step. Write `arrival_flag = 'confirmed'`, `checkin_at = now()`, `status = 'arrived'`. Stepper shows Arrived → Report.                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| Suggested AC 3                       | **Out-of-range (≥ radius):** Show `x-ds-modal` blocking dialog: title "Out of range / خارج النطاق", message "You are :distance m from the customer. Check-in requires being within :radius m. Please move closer and try again. / أنت على بعد :distance متر من العميل. يتطلب تأكيد الوصول التواجد ضمن :radius متر. يرجى الاقتراب والمحاولة مرة أخرى." Single "Retry / إعادة المحاولة" button that re-checks GPS. **No "Confirm Anyway"**. Write `arrival_flag = 'declined'` on attempt. Visit stays `scheduled`. Stepper shows Scheduled → **Declined** (terminal).                                                          |
| Suggested AC 4                       | **GPS denied/unavailable:** Show `x-ds-modal` blocking dialog (cannot dismiss): title "GPS must be enabled to check in / يجب تفعيل خدمة الموقع لتأكيد الوصول", message "Allow location access for this site in your browser or device settings, then retry. / اسمح بالوصول إلى الموقع لهذا التطبيق من إعدادات المتصفح أو الجهاز، ثم أعد المحاولة." Two buttons: "Open Settings / فتح الإعدادات" (opens `navigator.geolocation` settings if possible, else no-op) and "Retry / إعادة المحاولة". Write `arrival_flag = 'gps_denied'` on attempt. Visit stays `scheduled`. Stepper shows Scheduled → **GPS Denied** (terminal). |
| Suggested AC 5                       | All strings bilingual AR/EN, RTL-correct. Use existing `x-ds-modal` component (accessible, `aria-modal`, escape key handling).                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| Suggested AC 6                       | Feature tests cover: in-range success, out-of-range blocked (declined flag written), GPS-denied blocked (gps_denied flag written), radius config change, arrival_flag enum values.                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| Suspected files / modules            | `app/Livewire/App/VisitFlow.php`, `resources/views/livewire/app/visit-flow.blade.php`, `app/Models/Visit.php`, `app/Models/Company.php`, migration for `geofence_radius`, `lang/en/app.php`, `lang/ar/app.php`, `tests/Feature/Rep/GeofenceD02Test.php`                                                                                                                                                                                                                                                                                                                                                                      |
| Verification steps (from hypotheses) | H1: git history VisitFlow vs D-02 date; H2: check Company model for geofence_radius column                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| Investigation reference              | `bmad-output/investigation-geofence-blocking-dialogs-d02-2026-07-19.md`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |

> Proceed with `/bmad-planning-orchestrator:bmad-epics-and-stories` to compile the full story context object. Dev Notes in that story MUST cite this case file.

---

## Open Questions

1. **Config location:** Should geofence radius be per-company (in `companies` table) or global (`.env`)? Amendment §3.1 adds columns to `companies`; per-company makes sense for multi-tenant. Confirm with owner.

2. **"Open Settings" button:** On mobile browsers, there's no standard API to open device location settings. Options: (a) `window.open('app-settings:')` iOS deep link (unreliable), (b) show instruction text only, (c) link to browser site settings (`chrome://settings/content/location`). What's the accepted UX?

3. **Stepper terminal states:** Should Declined/GPS Denied be visit `status` values or only `arrival_flag`? Current statuses: `scheduled`, `arrived`, `reported`, `cancelled`. Adding `declined` and `gps_denied` to status enum vs. keeping status `scheduled` and using `arrival_flag` for distinction.

4. **Manager notification on declined:** D-02 says out-of-range = decline. Should a `declined` arrival_flag trigger an alarm to manager? (REQ-CMP-10 proposal had "auto manager notification" for confirm-anyway; D-02 removed confirm-anyway but didn't specify notification on decline.)

---

## Update History

| Version | Date       | Summary                         |
| ------- | ---------- | ------------------------------- |
| 1.0     | 2026-07-19 | Initial investigation case file |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
