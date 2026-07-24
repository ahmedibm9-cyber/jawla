# Story HOTFIX — Null Guard on Dashboard Widgets

**Status:** ready-for-dev
**Epic:** HOTFIX — Critical Bug Fix
**Investigation reference:** bmad-output/investigation-null-company-id-widget-crash-2026-07-23.md
**Estimated effort:** Small (~0.5 days)
**Labels:** hotfix, critical, null-safety, dashboard, widgets

---

## Story

**As a** system administrator
**I want** the dashboard to not crash when a session expires or Auth::user() returns null
**So that** users see an empty widget instead of a 500 Internal Server Error

---

## Acceptance Criteria

### AC 1: Null guard on Auth::user() in all 8 widgets

- Every dashboard widget checks `if (! $user)` after `$user = Auth::user()` and returns an empty array `[]` if null
- No widget crashes with "Attempt to read property on null"

### AC 2: Widget renders empty state

- When Auth::user() is null, the widget renders as a blank StatsOverviewWidget card (no stats, no error)
- No 500 error, no stack trace, no broken page

### AC 3: Existing behavior preserved

- When Auth::user() is valid, all widgets behave identically to before
- No visual changes, no logic changes, no regressions

---

## Technical Details

### Root Cause

All 8 dashboard widgets use `$user = Auth::user()` then immediately access `$user->company_id` without null checking. When the session expires between page render and a Livewire AJAX update, `Auth::user()` returns null, causing a 500 error.

### Files to Modify (8 widgets)

| File                                                | Line to guard |
| --------------------------------------------------- | ------------- |
| `app/Filament/Widgets/PendingQuotationsWidget.php`  | Line 17       |
| `app/Filament/Widgets/LowStockAlertWidget.php`      | Line 21       |
| `app/Filament/Widgets/CollectionRateWidget.php`     | Line 24       |
| `app/Filament/Widgets/OutstandingBalanceWidget.php` | Line 19       |
| `app/Filament/Widgets/RepPerformanceWidget.php`     | Line ~16      |
| `app/Filament/Widgets/VisitsTodayWidget.php`        | Line ~14      |
| `app/Filament/Widgets/SalesTodayWidget.php`         | Line ~14      |
| `app/Filament/Widgets/OpenAlarmsWidget.php`         | Line ~14      |

### Pattern to Apply

**Before:**

```php
protected function getStats(): array
{
    $user = Auth::user();
    $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

    $pending = PriceQuotationRequest::where('company_id', $user->company_id)
        ->where('status', 'requested')
        ->count();
    // ...
}
```

**After:**

```php
protected function getStats(): array
{
    $user = Auth::user();
    if (! $user) {
        return [];
    }

    $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

    $pending = PriceQuotationRequest::where('company_id', $user->company_id)
        ->where('status', 'requested')
        ->count();
    // ...
}
```

### Why `return []`?

- `StatsOverviewWidget::getStats()` expects an array of `Stat` objects
- Returning `[]` means Filament renders an empty widget card — no stats, no crash
- This is the standard Filament pattern for conditional widget rendering

---

## Dev Notes

- This is a **defensive fix**, not a root-cause fix. The root cause is session expiry during Livewire updates. A proper fix would also handle session renewal, but that's out of scope for this hotfix.
- The `company_id` column in `users` table is NOT nullable (migration: `$table->foreignId('company_id')->constrained('companies')`), so if the user exists, `company_id` always exists. The only way to hit null is when `Auth::user()` itself returns null.
- The `SetActiveCompanyContext` middleware already handles this pattern (`if ($request->user() !== null)`) — the widgets should follow the same defensive approach.

---

## Definition of Done

- [ ] All 8 widgets have null guard after `Auth::user()`
- [ ] Dashboard loads without 500 error when session expires
- [ ] All widgets render normally when user is authenticated
- [ ] No regressions in widget data or layout
