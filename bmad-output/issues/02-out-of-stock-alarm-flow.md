# Out-of-stock flagging flow: rep request → tri-role alarm broadcast (REQ-ALM-1…4)

## Overview

The client's marquee AM4 feature has **no UI**: a rep must be able to flag a material as out of stock, and Finance + Manager + Executive must all receive a red critical alarm identifying "Rep X / Material Y". Today only the `OutOfStockRequest` model and `AlarmService` exist; walkthrough steps 18–19 of the Definition of Beta Done are impossible. Spec: PRD v1.1 REQ-ALM-1…4, Master Plan B6-02.

## Scope

**Included:** rep-side flag action + request form, service wiring to `AlarmService`, tri-role broadcast, admin alarm visibility with acknowledge/resolve, duplicate-open-request guard, bilingual UI.
**Excluded:** push notifications (v1.1), the rep notifications bell (issue #5), complaint flows (already exist).

## Technical Requirements

- Entry point: an "غير متوفر / Flag out of stock" button on each product card in `StockSearch` (`resources/views/livewire/app/stock-search.blade.php` — the current static "Out of stock" text at line 43 becomes actionable).
- Form fields: product (prefilled), requested quantity, optional customer/visit link, note. Server-side validation; company + ownership scoping.
- New `OutOfStockService` (or method on `AlarmService`): inside one transaction, create `OutOfStockRequest` + one alarm with recipients resolved by role (finance, sales_manager, executive/system_viewer) **within the same company**; idempotent for retried submissions (unique open request per rep+product).
- Alarm payload must render "Rep {name} / {product name}" and severity=critical (red — reserved color).
- Recipients see unread count + red indicator in Filament (`AlarmResource` already exists — add badge + acknowledge/resolve actions with permission checks).
- Duplicate open request for same rep+product → bilingual validation error, no second alarm.

## Implementation Plan

1. `app/Services/OutOfStockService.php` — `raise(User $rep, array $data): OutOfStockRequest` in `DB::transaction()`.
2. `app/Livewire/App/FlagOutOfStock.php` + blade (or modal within StockSearch) — route `/app/out-of-stock/{product}`.
3. Recipient resolution: `User::role(['finance','sales_manager','system_viewer'])->where('company_id', $companyId)` → `alarm_reads` rows.
4. Filament: navigation badge on `AlarmResource` = open critical alarms count.
5. Tests: `tests/Feature/Alarm/OutOfStockBroadcastTest.php` — exact three-role broadcast, cross-company leak rejection, duplicate/retry idempotency, unauthorized role 403.

## Acceptance Criteria

- [ ] Rep flags Material 952 from stock search; Finance, Manager, Executive each see the critical alarm; no other company sees it
- [ ] Duplicate submission creates no second alarm
- [ ] Acknowledge/resolve is permission-controlled and audited
- [ ] All states bilingual AR/EN, red reserved for the alarm
- [ ] Feature tests above pass

## Priority

Score 12.5 — client's emphatic ask; blocks the Beta Done walkthrough.

## Dependencies

- **Blocks:** Beta walkthrough certification; #5 (bell shows these alarms)
- **Blocked by:** #1 (green suite)

## Implementation Size

- **Estimated effort:** Medium (2–3 days)
