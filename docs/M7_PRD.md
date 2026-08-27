# Milestone 7: Competitor Gap Closure — PRD

**Date:** 2026-08-24  
**Source:** `COMPETITOR_GAP_ANALYSIS.md` (Bricks Rep live exploration)  
**Status:** Draft

---

## Problem Statement

Jawla has strong transactional capabilities (invoicing, payments, returns, stock management, ZATCA e-invoicing) but lacks daily workflow features that Bricks Rep provides: Calendar, Todos, Performance Dashboard, Agenda, Tickets, and Requests. This gap makes Bricks Rep more attractive for reps/managers who prioritize daily workflow over financial depth.

## Users

### Primary Actors

| Actor             | Role                         | Needs                                                                                                  |
| ----------------- | ---------------------------- | ------------------------------------------------------------------------------------------------------ |
| **Field Rep**     | Daily user, visits customers | Calendar to see schedule, Todos to track tasks, Agenda for daily workflow, non-planned visit recording |
| **Sales Manager** | Oversees team performance    | Performance dashboard with metrics, tickets for support, requests for approvals                        |
| **Admin**         | System configuration         | Manage all entities, assign tickets, review requests                                                   |

### Secondary Actors

| Actor                | Role               | Needs                              |
| -------------------- | ------------------ | ---------------------------------- |
| **Dispatcher**       | Plans routes       | Calendar view of team schedules    |
| **Customer Service** | Handles complaints | Tickets system for tracking issues |

## Outcomes

### Must Have (M7.1-M7.3)

1. **Calendar View** — Reps see visits/todos/tasks on a monthly calendar
2. **Todos System** — Reps create/complete daily tasks
3. **Performance Dashboard** — Managers see coverage, frequency, call rate, plan achievement

### Should Have (M7.4-M7.7)

4. **Agenda View** — Daily timeline combining all activities
5. **Tickets System** — Support ticket tracking with status workflow
6. **Requests System** — Approval workflow for manager sign-off
7. **Non-Planned Visits** — Record visits outside planned route

### Nice to Have (M7.8-M7.10)

8. **Contacts Views** — Table/kanban/grid toggle with export
9. **Calls Tracking** — Log phone calls against customers
10. **Customers Summary** — Analytics dashboard for customer data

## Scope

### In Scope

- Rep PWA pages for all 10 features
- Filament admin resources for all new entities
- New database tables with proper migrations
- Arabic RTL + English LTR support
- Offline queuing for new entities (visits, todos, tickets, requests, calls)
- Integration with existing visit/invoice/payment data

### Out of Scope (Non-Goals)

- **Redesigning existing pages** — Only add new pages/features
- **Changing financial logic** — Invoicing, payments, returns stay as-is
- **Mobile app** — PWA only, no native app changes
- **Third-party integrations** — No new external services
- **AI/ML features** — No predictive analytics in M7
- **Customer portal** — No customer-facing changes
- **New roles/permissions** — Use existing RBAC, no new role types

## Success Measures

| Metric                          | Target                         | Measurement            |
| ------------------------------- | ------------------------------ | ---------------------- |
| Calendar adoption               | 80% of reps use weekly         | Usage analytics        |
| Todo completion rate            | 70% of created todos completed | Database metric        |
| Performance dashboard load time | <3 seconds                     | Performance monitoring |
| Ticket resolution time          | <24 hours average              | Ticket timestamps      |
| Request approval time           | <8 hours average               | Request timestamps     |
| Non-planned visit ratio         | <20% of total visits           | Visit analytics        |
| Export usage                    | 50% of managers export monthly | Feature tracking       |

## Architecture Decisions

### Decision 1: Single PWA for All Features

**Choice**: Add all features to existing Rep PWA (`/app`)  
**Alternatives**: Separate apps, admin-only features  
**Rationale**: Consistent UX, single codebase, offline support works automatically  
**Reversibility**: High — can split later if needed

### Decision 2: Computed Metrics vs Pre-aggregated

**Choice**: Compute performance metrics on-demand from existing data  
**Alternatives**: Pre-aggregate in background jobs, cache tables  
**Rationale**: Simpler implementation, always fresh data, existing data volume manageable  
**Reversibility**: Medium — can add caching layer later if performance degrades

### Decision 3: Simple Status Workflows

**Choice**: Enum-based status with manual transitions  
**Alternatives**: Workflow engine, state machine package  
**Rationale**: Tickets/requests have simple flows, existing approval engine covers complex cases  
**Reversibility**: High — can migrate to workflow engine later

### Decision 4: No New Dependencies

**Choice**: Use existing Laravel/Filament/Livewire stack  
**Alternatives**: Chart.js library, calendar library  
**Rationale**: Minimize lock-in, existing stack sufficient, CSS grid for calendar  
**Reversibility**: High — no new packages to remove

## Dependencies

### Internal

- Milestone 1 complete (users, customers, visits)
- Milestone 2 partial (invoices for performance metrics)
- Milestone 3 partial (payments for performance metrics)

### External

- None (no new third-party services)

## Risks

| Risk                                         | Impact | Mitigation                                                  |
| -------------------------------------------- | ------ | ----------------------------------------------------------- |
| Performance metrics slow with large datasets | Medium | Add caching layer if needed                                 |
| Calendar RTL week start (Sunday)             | Low    | Test with Arabic users                                      |
| Offline sync for new entities                | Medium | Follow existing visit sync pattern                          |
| Status workflow complexity                   | Low    | Keep simple, use existing approval engine for complex cases |

## Approval Gate

Before starting M7 implementation:

- [ ] PRD reviewed and approved by product owner
- [ ] DATA_MODEL.md reviewed by backend lead
- [ ] SPEC.md reviewed by frontend lead
- [ ] RISKS.md reviewed by tech lead
