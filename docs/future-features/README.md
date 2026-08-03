# Future Features — Phase Plans

Gap analysis against the [Field Command UX Specification](../FIELD_COMMAND_SPEC.md).

## Phase Overview

| Phase       | Focus                  | Duration  | Scope                                                                                  | File                                                                   |
| ----------- | ---------------------- | --------- | -------------------------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| **Phase 1** | Close P0 Gaps          | 2-3 weeks | End-shift, geofence, order review, customer statement, task detail, notifications, MFA | [phase-1-close-p0-gaps.md](phase-1-close-p0-gaps.md)                   |
| **Phase 2** | Approval System        | 2-3 weeks | Approval inbox, approval detail, mobile approvals, workflow builder, rep status        | [phase-2-approval-system.md](phase-2-approval-system.md)               |
| **Phase 3** | Operational Visibility | 2-3 weeks | Live ops list, rep profile, customer detail tabs, route map, active shift              | [phase-3-operational-visibility.md](phase-3-operational-visibility.md) |
| **Phase 4** | Advanced Features      | 4+ weeks  | Form builder, route calendar, territory management, platform admin, scheduled reports  | [phase-4-advanced-features.md](phase-4-advanced-features.md)           |

## Cumulative Coverage

| Phase   | Added Screens | Running Total | Spec Coverage |
| ------- | ------------- | ------------- | ------------- |
| Current | —             | ~47 screens   | ~45%          |
| Phase 1 | +7            | ~54 screens   | ~52%          |
| Phase 2 | +5            | ~59 screens   | ~56%          |
| Phase 3 | +5            | ~64 screens   | ~61%          |
| Phase 4 | +6            | ~70 screens   | ~67%          |

## Dependency Graph

```
Phase 1 ──→ Phase 2 ──→ Phase 3 ──→ Phase 4
   │            │            │            │
   ├─ MFA       ├─ Inbox     ├─ LiveOps   ├─ FormBuilder
   ├─ Geofence  ├─ Detail    ├─ RepProfile├─ RouteCalendar
   ├─ EndShift  ├─ Mobile    ├─ CustDetail├─ Territory
   ├─ OrderRev  ├─ Workflow  ├─ RouteMap  ├─ PlatformAdmin
   ├─ Statement └─ RepStatus └─ ActiveShift└─ ScheduledRpt
   ├─ TaskDetail
   └─ NotifFilter
```

## How to Use These Plans

1. **Pick a phase** based on business priority
2. **Start with Milestone 1** of that phase
3. **Each milestone** has: task ID, files to modify, tests to write, acceptance criteria
4. **Run `make verify`** after each milestone
5. **Deploy to staging** after each phase completes
