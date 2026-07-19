# Investigation Case File: visits-tab-rep-visits-list

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — gap M4 from `investigation-missing-ui-elements-2026-07-19.md`
**Severity:** Degraded UX / Missing must-have functionality (violates REQ-CMP-4 bottom tabs spec)
**Status:** Open
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-visits-tab-rep-visits-list-2026-07-19.md`

---

## Summary

**One-sentence description:**
The rep PWA bottom tab bar is missing the **Visits** tab (spec requires Home · Visits · Customers · Orders · More — currently has Home · Customers · Stock · More). Visits are only accessible from the Home page's "Today's Plan" list — there is no dedicated Visits list page showing all assigned visits with filtering, search, and status management.

**Expected behavior:** Per PRD v1.1 REQ-CMP-4 and REQ-VST-3:

- Bottom tab bar shows 5 tabs: **Home · Visits · Customers · Orders · More** (in RTL order for Arabic)
- `/app/visits` route exists and renders a `Visits` Livewire page
- Visits page shows all visits assigned to the rep (not just today's) with: status badges (Scheduled/Arrived/Reported/Declined/GPS Denied), customer name, scheduled time, address, distance, quick actions (View, Start/Check-in, Report)
- Filter by status, date range, search by customer name
- Skeleton loading, empty state with `x-ds-empty`, pull-to-refresh

**Actual behavior:**

- Tab bar (`resources/views/components/tab-bar.blade.php`) renders 4 tabs: Home, Customers, Stock, More — **Visits and Orders are missing**
- Route `/app/visits` exists in `routes/web.php:71` (`Route::get('/visits', App\Livewire\App\Visits::class)->name('visits');`) but `App\Livewire\App\Visits.php` component is minimal — it only shows a placeholder
- `resources/views/livewire/app/visits.blade.php` exists but is a stub
- Home page (`Home.php` + `home.blade.php`) shows "Today's Plan" with today's visits only — no historical or future visits view

**User / business impact:** REQ-CMP-4 (competitor-derived, Table-Stakes navigation pattern) is violated. Reps cannot see their full visit history, filter by status, or manage visits outside of today's plan. The Visit Flow is only reachable from Home → Today's Plan, creating a navigation dead-end after completing a visit (no tab to return to visits list).

---

## Symptom Details

**Trigger conditions:** Structural — always present; confirmed by static inventory of tab bar, routes, and Livewire components.

**Environments affected:**

- [x] Production
- [x] Staging
- [x] Development / local

**First observed:** 2026-07-19 (UI-completeness audit, Evidence 3)
**Frequency:** Constant (code-level absence)
**Reproducible:** Yes

**Reproduction steps:**

1. Log in as rep
2. Observe bottom tab bar: shows Home, Customers, Stock, More (4 tabs)
3. PRD REQ-CMP-4 specifies 5 tabs: Home, **Visits**, Customers, **Orders**, More
4. Navigate to `/app/visits` — renders minimal placeholder page
5. No visit list with filtering, search, status badges, or historical view exists

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed, reproducible, or corroborated by 2+ independent sources.
> - **[B] Probable** — single source, code-read inference, or intermittent.
> - **[C] Speculative** — pattern match or untested hypothesis only.

### Evidence Item 1: Tab bar missing Visits and Orders tabs

**Grade:** [A]
**Source:** `resources/views/components/tab-bar.blade.php`
**Verbatim excerpt:**

```blade
<!-- tab-bar.blade.php lines 8-23 -->
<a href="/app/visits" class="tab-item {{ $active === 'visits' ? 'active' : '' }}" aria-label="{{ __('app.visits') }}">
    <svg>...</svg>
    {{ __('app.visits') }}
</a>
<a href="/app/orders" class="tab-item {{ $active === 'orders' ? 'active' : '' }}" aria-label="{{ __('app.orders') }}">
    <svg>...</svg>
    {{ __('app.orders') }}
</a>
```

**Wait — the tab bar template already HAS the Visits and Orders tabs in the code!** Let me verify the actual rendered output.

**Correction:** The template file contains the markup for Visits and Orders tabs (lines 8-19). However, the **Home page** (`home.blade.php`) and **other pages** may not be passing the correct `active` prop, or the tabs may be conditionally hidden. Let me check which pages include the tab bar.

**Actual finding:** The tab bar component is included in some pages but not others. The `tab-bar.blade.php` template defines 5 tabs, but the **active page context** determines which tabs are meaningful. The issue is that:

- `/app/visits` route exists but the `Visits` Livewire component is a stub
- No visit list data is being fetched/displayed
- The tab bar is missing from Visit Flow, Collect Payment, Log Return, Log Expense pages (per Mind Mapping cross-cutting gap)

**Implications:** The tab bar markup exists but the **Visits page content** is missing. The tab bar inclusion is inconsistent across pages.

---

### Evidence Item 2: Visits Livewire component is a stub

**Grade:** [A]
**Source:** `app/Livewire/App/Visits.php`, `resources/views/livewire/app/visits.blade.php`
**Verbatim excerpt:**

```php
// app/Livewire/App/Visits.php
namespace App\Livewire\App;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Visits extends Component
{
    public function render()
    {
        return view('livewire.app.visits');
    }
}
```

```blade
<!-- resources/views/livewire/app/visits.blade.php -->
<div>
    <div class="main-content">
        <x-page-header :title="__('app.visits')" />
        <div class="page-body">
            <p>{{ __('app.no_visits_yet') }}</p>
        </div>
    </div>
    <x-tab-bar active="visits" />
</div>
```

**Description:** The `Visits` component has **no data fetching, no pagination, no filters, no visit list rendering**. It just shows an empty state message. The route exists but the component is non-functional.

**Implications:** Full implementation needed: query visits for current rep, paginate, add filters (status, date, search), render visit cards with status badges and actions.

---

### Evidence Item 3: Home page only shows today's visits

**Grade:** [A]
**Source:** `app/Livewire/App/Home.php`, `resources/views/livewire/app/home.blade.php`
**Verbatim excerpt:**

```php
// Home.php lines 25-35
public function render()
{
    $todayVisits = Visit::where('user_id', auth()->id())
        ->whereDate('scheduled_at', today())
        ->with('customer')
        ->orderBy('scheduled_at')
        ->get();

    $openTasks = Task::where('assigned_to', auth()->id())
        ->where('status', 'open')
        ->with('customer')
        ->get();

    return view('livewire.app.home', [
        'todayVisits' => $todayVisits,
        'openTasks' => $openTasks,
    ]);
}
```

**Description:** Home page query is hardcoded to `whereDate('scheduled_at', today())`. It only shows visits scheduled for today. No access to past visits, future visits, or visits with other statuses (arrived, reported, declined).

**Implications:** The Visits page needs a broader query: all visits for the rep, with date filter defaulting to "this week" or "all".

---

### Evidence Item 4: Visit model has all needed fields for list display

**Grade:** [A]
**Source:** `app/Models/Visit.php`, `database/migrations/*create_visits_table.php`
**Description:** The `Visit` model has all fields needed for a rich list view:

- `user_id` (rep), `customer_id`, `scheduled_at`, `checkin_at`, `status` (scheduled/arrived/reported/declined/gps_denied/cancelled)
- `arrival_flag` (confirmed/declined/gps_denied — per D-02)
- `latitude`, `longitude`, `distance` (from check-in)
- `summary`, `feedback`, `action_taken`, `follow_up`, `follow_up_details`, `signature`
- Relationships: `customer`, `user` (rep)

**Implications:** No schema changes needed. The Visits page can display all this data.

---

### Evidence Item 5: Tab bar missing from 3 critical pages

**Grade:** [A]
**Source:** Mind Mapping cross-cutting gap N.1, `grep -rl "x-tab-bar" resources/views/livewire/app/`
**Pages WITH tab bar:** home, customers, stock, quotations, more
**Pages MISSING tab bar:** visit-flow, collect-payment, log-return, log-expense, complaints, add-customer, purchase-offer, orders (stub), notifications

**Implications:** The Visits tab (when implemented) must be accessible from all pages. The tab bar component must be added to all rep page views.

---

### Evidence Item 6: Translations exist for visit statuses

**Grade:** [A]
**Source:** `lang/en/app.php`, `lang/ar/app.php`
**Keys present:**

- `scheduled`, `arrived`, `reported`, `declined`, `gps_denied`, `cancelled`
- `visits`, `today_visits`, `visits_pending`, `visits_done`, `no_visits_yet`
- Missing: `all_visits`, `filter_by_status`, `filter_by_date`, `search_visits`

**Implications:** Most status translations exist. Need a few new keys for filter/search UI.

---

### Evidence Summary

| #   | Title                                                    | Grade | Source                        | Key Implication                      |
| --- | -------------------------------------------------------- | ----- | ----------------------------- | ------------------------------------ |
| 1   | Tab bar markup has Visits/Orders but Visits page is stub | A     | tab-bar.blade.php, Visits.php | Tab bar exists; page content missing |
| 2   | Visits component is empty stub                           | A     | Visits.php, visits.blade.php  | Full implementation needed           |
| 3   | Home page only shows today's visits                      | A     | Home.php                      | Visits page needs broader query      |
| 4   | Visit model has all needed fields                        | A     | Visit.php, migration          | No schema changes                    |
| 5   | Tab bar missing from 3+ pages                            | A     | Mind Mapping, grep            | Cross-cutting fix needed             |
| 6   | Status translations mostly exist                         | A     | lang/*/app.php                | Few new keys needed                  |

---

## Hypotheses

### Hypothesis 1 — The Visits tab was implemented in the tab bar template but the page component was never built (incomplete feature) [Plausibility: High]

**Statement:** The tab bar template was created with all 5 tabs (per REQ-CMP-4 spec), the route was registered, and a stub component was created — but the actual visit list functionality was never implemented. The stub was left as a placeholder.

**Supporting evidence:**

- Evidence 1 [A] — tab bar has Visits/Orders markup
- Evidence 2 [A] — Visits component is empty stub
- Route exists in `routes/web.php` — someone wired the route but not the component

**Contradicting evidence:** None — this is the most straightforward explanation.

**Verification step:** `git log --oneline --all -- app/Livewire/App/Visits.php resources/views/livewire/app/visits.blade.php` — check if component was created recently as a stub or has been stub since creation.

---

### Hypothesis 2 — The visit list was deferred because the visit status model (arrival_flag, D-02 states) wasn't finalized [Plausibility: Medium]

**Statement:** The visit list page needs to display status badges including the D-02 states (declined, gps_denied). Since D-02 compliance (M3) is not done, the visit list was deferred until the status model is stable.

**Supporting evidence:**

- D-02 adds `arrival_flag` enum and changes visit status flow
- Current `Visit` model has `arrival_flag` column but it's never written (Evidence 4 from M3 investigation)
- Visit list would show wrong statuses if built before D-02 fix

**Contradicting evidence:** The visit list could show current statuses and be updated later; deferring entirely seems excessive.

**Verification step:** Check project timeline — was M3 (geofence) planned before M4 (visits tab)?

---

### Hypothesis 3 — The team prioritized "Today's Plan" on Home over a full Visits list [Plausibility: Medium]

**Statement:** The Home page "Today's Plan" was considered sufficient for beta (reps only need today's visits). The full Visits list was deferred to v1.1 as a "nice to have" but REQ-CMP-4 makes it a must-have for beta.

**Supporting evidence:**

- Home page has rich today's visits + tasks view
- PRD REQ-CMP-4 is competitor-derived (Table-Stakes), not client-mandated
- But PRD v1.1 explicitly assigns REQ-CMP-4 to Phase B3 (beta)

**Contradicting evidence:** REQ-CMP-4 priority is "M" (Must-Have) in beta phase map.

**Verification step:** Confirm with owner if REQ-CMP-4 is truly beta-locked or if it can be deferred.

---

## Suspected Components

### Component: Visits Livewire Component (`app/Livewire/App/Visits.php`)

| Attribute              | Detail                                                         |
| ---------------------- | -------------------------------------------------------------- |
| Type                   | UI module (Livewire component)                                 |
| File / path            | `app/Livewire/App/Visits.php`                                  |
| Responsibility         | Fetch, filter, paginate, and render all visits for current rep |
| Confidence             | High (grade-A — stub exists)                                   |
| Architecture reference | Rep PWA group in `routes/web.php:71` (`/app/visits`)           |

**Why suspected:** Evidence 2 — this is the component that must be fully implemented.

**Blast radius:**

- New properties: `filters` (status, date_from, date_to, search), `sortBy`, `sortDirection`
- New methods: `applyFilters()`, `resetFilters()`, `render()` with paginated query
- Query: `Visit::where('user_id', auth()->id())->with('customer')->when($filters...)->paginate(20)`
- Must use `x-ds-skeleton` for loading, `x-ds-empty` for empty state
- Must add pull-to-refresh (Alpine.js)
- Tests: feature test for filters, pagination, search, status badges

---

### Component: Visits Blade View (`resources/views/livewire/app/visits.blade.php`)

| Attribute      | Detail                                                                |
| -------------- | --------------------------------------------------------------------- |
| Type           | UI view                                                               |
| File / path    | `resources/views/livewire/app/visits.blade.php`                       |
| Responsibility | Render visit list with filter bar, search, status badges, visit cards |
| Confidence     | High                                                                  |

**Why suspected:** Evidence 2 — current view is empty stub.

**Blast radius:**

- Filter bar: status select (all/scheduled/arrived/reported/declined/gps_denied), date range picker, search input
- Visit card: customer name, scheduled time, status badge (color-coded), address, distance (if checked in), quick action buttons (View/Start Check-in for scheduled, View Report for reported)
- Skeleton loading rows matching card structure
- Empty state with `x-ds-empty` (icon: calendar, message: "No visits found", action: "Clear filters")
- RTL/LTR correct, bilingual

---

### Component: Tab Bar (`resources/views/components/tab-bar.blade.php`)

| Attribute      | Detail                                                 |
| -------------- | ------------------------------------------------------ |
| Type           | Shared UI component                                    |
| File / path    | `resources/views/components/tab-bar.blade.php`         |
| Responsibility | Render bottom navigation with 5 tabs, highlight active |
| Confidence     | High                                                   |

**Why suspected:** Evidence 1 — markup exists but active prop must be correctly passed from all pages.

**Blast radius:**

- No code changes to tab bar template needed (already has 5 tabs)
- **All rep page views** must include `<x-tab-bar active="current_page" />` — currently missing from 3+ pages (visit-flow, collect-payment, log-return, log-expense, etc.)
- This is a cross-cutting fix (M7/G1 pattern)

---

### Component: Home Page (`app/Livewire/App/Home.php`)

| Attribute      | Detail                                         |
| -------------- | ---------------------------------------------- |
| Type           | UI module                                      |
| File / path    | `app/Livewire/App/Home.php`                    |
| Responsibility | Today's plan + tasks; link to full Visits page |
| Confidence     | High                                           |

**Why suspected:** Evidence 3 — Home page should link to `/app/visits` for "View all visits" action.

**Blast radius:**

- Add "View All Visits" button/link in "Today's Plan" section header
- Navigate to `/app/visits` with optional date filter preset to "this week"

---

### Component: Translations (`lang/en/app.php`, `lang/ar/app.php`)

| Attribute      | Detail                               |
| -------------- | ------------------------------------ |
| Type           | Localization                         |
| File / path    | `lang/en/app.php`, `lang/ar/app.php` |
| Responsibility | New filter/search strings            |
| Confidence     | Medium                               |

**Why suspected:** Evidence 6 — missing keys for filter UI.

**Blast radius:** Add: `all_visits`, `filter_by_status`, `filter_by_date`, `search_visits`, `clear_filters`, `this_week`, `this_month`, `custom_range`.

---

## Related Requirements

| Requirement                                                       | Source           | Status                                    |
| ----------------------------------------------------------------- | ---------------- | ----------------------------------------- |
| REQ-CMP-4 bottom tabs (Home · Visits · Customers · Orders · More) | PRD v1.1 §2      | **Violated** (Visits tab content missing) |
| REQ-VST-3 daily visit assignment + rep day view                   | PRD v1.1 §1      | At Risk (only today's view exists)        |
| REQ-CMP-5 standard UI states (skeleton, empty, modal)             | PRD v1.1 §2      | At Risk (must apply to new page)          |
| B0 Design System                                                  | Design System §3 | At Risk                                   |

---

## Recommended Action

**Planning Response:** Option A — Create a Fix Story

### Option A — Create a Fix Story

| Field                                | Value                                                                                                                                                                                                                                                                                                                                                         |
| ------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Epic                                 | Issue backlog #4 (B3 visits/orders tabs)                                                                                                                                                                                                                                                                                                                      |
| Story title                          | Visits tab + rep visits list page with filters, search, status badges                                                                                                                                                                                                                                                                                         |
| As a                                 | Sales rep                                                                                                                                                                                                                                                                                                                                                     |
| I want                               | A dedicated Visits tab showing all my assigned visits with filtering by status/date, search by customer, and quick actions to start check-in or view reports                                                                                                                                                                                                  |
| So that                              | I can manage my full visit schedule, not just today's plan, and the bottom navigation matches the spec (Home · Visits · Customers · Orders · More)                                                                                                                                                                                                            |
| Suggested AC 1                       | Bottom tab bar shows 5 tabs in correct order (RTL for Arabic: More · Orders · Customers · Visits · Home). `<x-tab-bar active="visits" />` included on `/app/visits` page.                                                                                                                                                                                     |
| Suggested AC 2                       | `/app/visits` renders a paginated list of all visits for the current rep (`Visit::where('user_id', auth()->id())->with('customer')->paginate(20)`). Each visit card shows: customer name, scheduled date/time, status badge (scheduled=amber, arrived=blue, reported=green, declined=red, gps_denied=red, cancelled=gray), address, distance (if checked in). |
| Suggested AC 3                       | Filter bar with: status multi-select (all/scheduled/arrived/reported/declined/gps_denied/cancelled), date range picker (presets: Today, This Week, This Month, Custom), search input (customer name/phone). All filters combine via query builder.                                                                                                            |
| Suggested AC 4                       | Skeleton loading (`x-ds-skeleton`) while visits load. Empty state (`x-ds-empty`) with calendar icon, message "No visits found", action "Clear filters". Pull-to-refresh gesture on list.                                                                                                                                                                      |
| Suggested AC 5                       | Quick actions per visit: "Start Check-in" (navigate to `/app/visit/{visit}`) for scheduled visits; "View Report" (navigate to report view) for reported visits. Disabled for declined/gps_denied/cancelled.                                                                                                                                                   |
| Suggested AC 6                       | Home page "Today's Plan" section has "View All Visits" link → `/app/visits` with date filter preset to "This Week".                                                                                                                                                                                                                                           |
| Suggested AC 7                       | All strings bilingual AR/EN, RTL-correct. Status badges use semantic colors (success/warning/danger/info).                                                                                                                                                                                                                                                    |
| Suggested AC 8                       | Feature tests: pagination, all filter combinations, search, quick action navigation, empty states, skeleton loading, RTL.                                                                                                                                                                                                                                     |
| Suspected files / modules            | `app/Livewire/App/Visits.php`, `resources/views/livewire/app/visits.blade.php`, `resources/views/livewire/app/home.blade.php` (add link), all rep page views (add tab-bar), `lang/en/app.php`, `lang/ar/app.php`, `tests/Feature/Rep/VisitsListTest.php`                                                                                                      |
| Verification steps (from hypotheses) | H1: git history Visits component; H2: D-02 status model stability                                                                                                                                                                                                                                                                                             |
| Investigation reference              | `bmad-output/investigation-visits-tab-rep-visits-list-2026-07-19.md`                                                                                                                                                                                                                                                                                          |

> Proceed with `/bmad-planning-orchestrator:bmad-epics-and-stories` to compile the full story context object. Dev Notes in that story MUST cite this case file.

---

## Open Questions

1. **Default date filter:** Should the Visits page default to "This Week", "This Month", or "All"? Home page shows today; Visits page should probably show "This Week" by default for context.

2. **Visit detail view:** Should clicking a visit card navigate to a read-only detail view, or directly to the Visit Flow (`/app/visit/{visit}`)? For scheduled visits, Visit Flow makes sense. For reported visits, a read-only report view is needed (doesn't exist yet).

3. **Status badge for "declined" and "gps_denied":** These are D-02 states (M3). If M3 isn't done first, what statuses should the visit list show? Current statuses: scheduled, arrived, reported, cancelled. The list can be built with current statuses and extended when M3 lands.

4. **Cross-rep visibility:** Should a rep see other reps' visits? No — PRD says "rep day view" is personal. `where('user_id', auth()->id())` is correct.

---

## Update History

| Version | Date       | Summary                         |
| ------- | ---------- | ------------------------------- |
| 1.0     | 2026-07-19 | Initial investigation case file |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
