# Investigation: Null Property Access in PendingQuotationsWidget

**Version:** 1.0  
**Date:** 2026-07-23  
**Status:** ready-for-dev  
**Severity:** Critical — 500 error crashes dashboard for all users

---

## Symptom Summary

| Field        | Value                                                                |
| ------------ | -------------------------------------------------------------------- |
| **Error**    | `Attempt to read property "company_id" on null`                      |
| **Location** | `app\Filament\Widgets\PendingQuotationsWidget.php:17`                |
| **Request**  | `POST /livewire/update` (Livewire AJAX)                              |
| **Page**     | Admin dashboard (`/admin/dashboard`)                                 |
| **When**     | Every time session expires or Auth::user() returns null              |
| **Scope**    | All admin panel users — any widget that accesses `$user->company_id` |
| **Impact**   | 500 Internal Server Error — dashboard crashes, no data loads         |

---

## Evidence

### Grade A — Confirmed

| #   | Evidence                                                                                                                     | Source                                                                                                                                                                                                    |
| --- | ---------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| A1  | Stack trace shows `PendingQuotationsWidget.php:17` as the crash point                                                        | errors.txt line 11                                                                                                                                                                                        |
| A2  | Line 14: `$user = Auth::user()` — can return null when session expires                                                       | PendingQuotationsWidget.php:14                                                                                                                                                                            |
| A3  | Line 17: `$user->company_id` — accesses property on potentially null object                                                  | PendingQuotationsWidget.php:17                                                                                                                                                                            |
| A4  | ALL 8 dashboard widgets use identical pattern: `$user = Auth::user()` then `$user->company_id` without null check            | PendingQuotationsWidget.php, LowStockAlertWidget.php, CollectionRateWidget.php, OutstandingBalanceWidget.php, RepPerformanceWidget.php, VisitsTodayWidget.php, SalesTodayWidget.php, OpenAlarmsWidget.php |
| A5  | `company_id` in users table is NOT nullable (migration line 13: `$table->foreignId('company_id')->constrained('companies')`) | 2026_01_01_000002_create_users_table.php:13                                                                                                                                                               |
| A6  | `SetActiveCompanyContext` middleware already handles null user: `if ($request->user() !== null)`                             | SetActiveCompanyContext.php:16                                                                                                                                                                            |

### Grade B — Probable

| #   | Evidence                                                                                                                                             | Source              |
| --- | ---------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------- |
| B1  | Livewire update request (`POST /livewire/update`) fires after page load — if session expired between page render and AJAX, Auth::user() returns null | errors.txt line 94  |
| B2  | Session query visible in DB logs: `select * from "sessions" where "id" = '...'` — session exists but user may be null                                | errors.txt line 129 |
| B3  | Referer shows `/admin/dashboard` — user was on dashboard when the AJAX fired                                                                         | errors.txt line 112 |

### Grade C — Speculative

| #   | Evidence                                                                                                         | Source               |
| --- | ---------------------------------------------------------------------------------------------------------------- | -------------------- |
| C1  | User report about "trying to run admin and rep in different browsers" (note #16) — could cause session conflicts | notes.txt line 16    |
| C2  | `executive` role user may have edge-case auth behavior (note #17)                                                | notes.txt line 17-28 |

---

## Hypotheses

### H1 — Session expiry during Livewire update (Plausibility: HIGH)

**Hypothesis:** The user's session expired (or Auth::user() returned null for another auth reason) between the initial page load and a subsequent Livewire AJAX update, causing `Auth::user()` to return null.

**Supporting evidence:**

- A1: Stack trace confirms crash at line 17
- A2: `Auth::user()` returns null when unauthenticated
- A3: No null guard before property access
- B1: Livewire update requests are async and can fire after session expires
- B2: Session query shows DB hit but user may not be resolved

**Contradicting evidence:**

- A5: `company_id` is NOT nullable in DB, so if user exists, company_id always exists

**Verification step:** Add `ray($user)` or `logger()` before line 17 to confirm Auth::user() returns null in production. Alternatively, check logs for `Illuminate\Auth\AuthenticationException` preceding the 500.

---

### H2 — Race condition: widget renders before user is fully authenticated (Plausibility: MEDIUM)

**Hypothesis:** During initial dashboard load, Livewire hydrates the widget before the session middleware has fully resolved the user, causing a transient null Auth::user().

**Supporting evidence:**

- B1: Livewire update is the specific trigger
- A6: `SetActiveCompanyContext` already guards against null — suggests this pattern was anticipated

**Contradicting evidence:**

- The auth middleware (`FilamentAuthenticate`) should prevent unauthenticated access entirely

**Verification step:** Check if `FilamentAuthenticate` middleware runs on Livewire update requests. If it does, this hypothesis is unlikely.

---

### H3 — Cookie/session corruption from multi-browser usage (Plausibility: LOW)

**Hypothesis:** User opened admin and rep panels in different browsers simultaneously (note #16), causing session cookie conflicts that result in Auth::user() returning null.

**Supporting evidence:**

- C1: User explicitly reported trying this

**Contradicting evidence:**

- B2: Session DB query succeeded — the session exists
- Laravel sessions are per-domain, not per-browser

**Verification step:** Check if the issue reproduces when only one browser tab is open.

---

## Suspected Components

| Component                           | Evidence                                  | Blast Radius                     | Confidence       |
| ----------------------------------- | ----------------------------------------- | -------------------------------- | ---------------- |
| **All 8 dashboard widgets**         | A4: All use identical null-unsafe pattern | All crash with same error        | HIGH             |
| **PendingQuotationsWidget**         | A1, A3: Direct crash site                 | This specific widget             | HIGH (confirmed) |
| **Livewire update mechanism**       | B1: Trigger context                       | Any async Livewire component     | MEDIUM           |
| **FilamentAuthenticate middleware** | B3: Should block unauthenticated          | If bypassed, all panels affected | MEDIUM           |

---

## Recommended Fix Strategy

### Primary: Add null guard to all 8 widgets

Replace the pattern:

```php
$user = Auth::user();
// ...
PriceQuotationRequest::where('company_id', $user->company_id)
```

With:

```php
$user = Auth::user();
if (! $user) {
    return []; // Widget renders empty — no crash
}
// ...
PriceQuotationRequest::where('company_id', $user->company_id)
```

**Files to modify (all 8 widgets):**

1. `app/Filament/Widgets/PendingQuotationsWidget.php`
2. `app/Filament/Widgets/LowStockAlertWidget.php`
3. `app/Filament/Widgets/CollectionRateWidget.php`
4. `app/Filament/Widgets/OutstandingBalanceWidget.php`
5. `app/Filament/Widgets/RepPerformanceWidget.php`
6. `app/Filament/Widgets/VisitsTodayWidget.php`
7. `app/Filament/Widgets/SalesTodayWidget.php`
8. `app/Filament/Widgets/OpenAlarmsWidget.php`

### Alternative: Extract to base trait

Create a shared trait or override `getStats()` in a base widget class that handles the null guard once.

---

## Decision Log Entry

```
## Investigation: Null Property Access in PendingQuotationsWidget — 2026-07-23
- Symptom: 500 error "Attempt to read property company_id on null" on admin dashboard
- Primary hypothesis: Session expiry during Livewire AJAX update
- Primary suspected component: All 8 dashboard widgets (null-unsafe Auth::user() pattern)
- Case file: bmad-output/investigation-null-company-id-widget-crash-2026-07-23.md
- Recommended response: Option A — create fix story for null guard across all widgets
```
