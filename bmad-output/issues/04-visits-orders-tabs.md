# Rep app: add Visits and Orders tabs + list pages (REQ-CMP-4, REQ-VST-3, REQ-RPT)

## Overview

The bottom tab bar renders Home · Customers · Stock · More; the spec requires Home · **Visits** · Customers · **Orders** · More. There is no rep-facing visits list (visits reachable only from Home's today cards) and no documents list (a rep cannot re-open yesterday's proforma/invoice or reshare a PDF).

## Scope

**Included:** two new Livewire pages + routes, tab bar update, pagination, empty states, PDF/WhatsApp actions on Orders.
**Excluded:** editing documents (immutable), full reports suite (v1.0).

## Technical Requirements

- `Visits` page: rep's own assignments grouped by date (today first), status chips, paginated (`simplePaginate(25)`), links into `VisitFlow`; tenant + ownership scoped.
- `Orders` page: rep's own proformas + invoices, newest first, status chip, remaining amount, actions: View PDF, WhatsApp share; paginated; eager-load customer.
- Tab bar: 5 tabs with translated labels, active states, `aria-label`s; verify safe-area padding still holds with 5 items at 320px width.

## Implementation Plan

1. `app/Livewire/App/Visits.php` + blade; route `/app/visits`.
2. `app/Livewire/App/Orders.php` + blade; route `/app/orders`.
3. Update `resources/views/components/tab-bar.blade.php` (Stock moves into More, or keep as a 6th entry — decide with owner; default: Stock stays, More absorbs nothing — 5 tabs: Home·Visits·Customers·Orders·More, Stock accessible from Home/More).
4. Tests: ownership/tenant scoping (rep A cannot list rep B's docs), pagination, empty states.

## Acceptance Criteria

- [ ] Five spec'd tabs render correctly AR/EN RTL at 320–430px
- [ ] Rep sees only own visits/documents; cross-tenant test passes
- [ ] PDF + WhatsApp actions work from Orders
- [ ] Empty states with next-step action on both pages

## Priority

Score 8.0.

## Dependencies

- **Blocks:** Beta walkthrough navigation; **Blocked by:** #1

## Implementation Size

- **Estimated effort:** Medium (2 days)
