# Rep in-app notifications bell (quotation outcomes, customer approvals, complaint resolutions)

## Overview

The Amendment defers push to v1.1 explicitly because "in-app alarm bell + red indicators" cover the client's AM4 intent in beta — but no bell exists. Reps never learn a quotation was approved, a customer was approved/rejected (B2-05 requires notifying the submitting rep), or a complaint was resolved (B6-03) without asking a manager.

## Scope

**Included:** bell icon + unread badge in the rep header, paginated notifications page, read-state tracking, generation of notifications from the three existing flows.
**Excluded:** web push (v1.1), admin notifications (Filament handles), email.

## Technical Requirements

- Reuse `alarms`/`alarm_reads` or Laravel database notifications (choose one — prefer Laravel notifications to avoid overloading the alarm domain; no new package needed).
- Events: quotation approved/rejected → requesting rep; customer approved/rejected (with reason) → creating rep; complaint resolved → submitting rep; out-of-stock resolved (after #2) → flagging rep.
- Bell in `layouts/app.blade.php` header with unread count (`aria-live="polite"`), red dot only for critical.
- Notifications page `/app/notifications`, paginated, mark-read on open, bilingual.

## Acceptance Criteria

- [ ] Each of the four events produces exactly one notification to the correct rep, same company only
- [ ] Unread badge accurate; mark-read persists
- [ ] Bilingual, RTL-correct, accessible (button + aria-label)
- [ ] Feature tests for generation, scoping, and read state

## Priority

Score 8.0.

## Dependencies

- **Blocks:** nothing hard; **Blocked by:** #1; benefits from #2

## Implementation Size

- **Estimated effort:** Medium (2 days)
