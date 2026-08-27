# Milestone 7: Competitor Gap Closure — Plan Summary

**Date:** 2026-08-24  
**Status:** Ready for Review

---

## Executive Summary

Milestone 7 closes 10 feature gaps identified in competitor analysis (Bricks Rep), delivering 9 user-facing features plus admin resources. Total effort: ~32 days, parallelizable to ~16 days with 2 developers.

---

## Documentation Package

| Document        | Path                              | Status      |
| --------------- | --------------------------------- | ----------- |
| Gap Analysis    | `docs/COMPETITOR_GAP_ANALYSIS.md` | ✅ Complete |
| PRD             | `docs/M7_PRD.md`                  | ✅ Complete |
| Functional Spec | `docs/M7_SPEC.md`                 | ✅ Complete |
| User Journeys   | `docs/M7_USER_JOURNEYS.md`        | ✅ Complete |
| Data Model      | `docs/M7_DATA_MODEL.md`           | ✅ Complete |
| Task Breakdown  | `docs/M7_TASKS.md`                | ✅ Complete |
| Risk Register   | `docs/M7_RISKS.md`                | ✅ Complete |
| Updated Tasks   | `docs/TASKS.md`                   | ✅ Updated  |

---

## Feature Summary

### New PWA Features (9)

1. **Calendar View** — Monthly calendar with visit/todo/ticket indicators
2. **Todos** — Task management with priority and due dates
3. **Tickets** — Support tickets with status workflow (New → In Progress → Completed)
4. **Requests** — Manager approval workflow (New → Approved/Rejected)
5. **Calls** — Phone call logging with duration and outcome
6. **Non-Planned Visits** — Record out-of-route visits
7. **Performance Dashboard** — 5 tabs with metrics, analysis, daily, detailed, coverage
8. **Agenda** — Daily view of all activities
9. **Contact Export** — CSV export for customers

### New Admin Resources (4)

1. **Todo Resource** — CRUD todos in Filament
2. **Ticket Resource** — CRUD and assign tickets
3. **Request Resource** — Approve/reject requests
4. **Call Resource** — View and export calls

---

## Data Model

### New Tables (5)

| Table                   | Purpose           | Key Relationships                   |
| ----------------------- | ----------------- | ----------------------------------- |
| `todos`                 | Task management   | users, companies                    |
| `tickets`               | Support tickets   | users, customers, companies         |
| `ticket_status_history` | Audit trail       | tickets, users                      |
| `requests`              | Approval workflow | users, companies                    |
| `calls`                 | Phone call logs   | users, customers, customer_contacts |

### Modified Tables (1)

| Table    | Change                               |
| -------- | ------------------------------------ |
| `visits` | Add `is_out_of_route` boolean column |

---

## Task Breakdown

### Phase 1: Database Foundation (Sequential)

- Task 1.1: Create migration files (1 day)
- Task 1.2: Create Eloquent models (1 day)
- Task 1.3: Create service classes (2 days)

### Phase 2: Backend API (Parallel with Frontend)

- Task 2.1: Todo API (1 day)
- Task 2.2: Ticket API (1 day)
- Task 2.3: Request API (1 day)
- Task 2.4: Call API (1 day)
- Task 2.5: Performance Dashboard API (2 days)
- Task 2.6: Calendar API (1 day)
- Task 2.7: Agenda API (1 day)

### Phase 3: Frontend (Parallel with Backend)

- Task 3.1: Todo UI (1 day)
- Task 3.2: Ticket UI (1 day)
- Task 3.3: Request UI (1 day)
- Task 3.4: Call UI (0.5 days)
- Task 3.5: Performance Dashboard UI (2 days)
- Task 3.6: Calendar UI (1 day)
- Task 3.7: Agenda UI (1 day)

### Phase 4: Admin Resources (Parallel)

- Task 4.1: Filament Todo Resource (0.5 days)
- Task 4.2: Filament Ticket Resource (0.5 days)
- Task 4.3: Filament Request Resource (0.5 days)
- Task 4.4: Filament Call Resource (0.5 days)

### Phase 5: Integration & Testing

- Task 5.1: Navigation Updates (0.5 days)
- Task 5.2: Offline Sync Integration (1 day)
- Task 5.3: Permission Seeding (0.5 days)
- Task 5.4: Language Files (1 day)
- Task 5.5: Feature Tests (2 days)
- Task 5.6: Browser Tests (1 day)

---

## Critical Path

```
1.1 → 1.2 → 1.3 → 2.5 → 5.2 → 5.5 → 5.6
```

**Parallel tracks**:

- Backend: 2.1, 2.2, 2.3, 2.4, 2.6 can run after 1.3
- Frontend: 3.1-3.7 can start immediately
- Admin: 4.1-4.4 can run after 1.2

---

## Effort Summary

| Phase     | Days     | Parallel? |
| --------- | -------- | --------- |
| Phase 1   | 4        | No        |
| Phase 2   | 9        | Yes       |
| Phase 3   | 7.5      | Yes       |
| Phase 4   | 2        | Yes       |
| Phase 5   | 5        | Partial   |
| **Total** | **27.5** | —         |

**With 2 developers**: ~16 days  
**With 3 developers**: ~12 days

---

## Risk Summary

| Priority         | Count | Key Risks                                |
| ---------------- | ----- | ---------------------------------------- |
| High (Score ≥ 6) | 2     | Performance accuracy, Scope creep        |
| Medium (Score 4) | 6     | Calendar complexity, Security, Migration |
| Low (Score 2-3)  | 8     | Various                                  |

**Top Mitigations**:

1. Comprehensive test suite for metric calculations
2. Change control process for scope management
3. Security review of all new endpoints
4. Migration testing on production-like data

---

## Dependencies

### External Dependencies

- None (all features use existing stack)

### Internal Dependencies

- M7 depends on M1 (Core Architecture) completion
- M7 runs parallel with M3-M6

### Team Dependencies

- Backend Lead: Service implementation, API endpoints
- Frontend Lead: UI components, calendar, dashboard
- QA Lead: Test coverage, browser tests
- Security Lead: Permission review, security audit

---

## Success Criteria

### Functional

- [ ] Calendar shows visits, todos, tickets with dot indicators
- [ ] Todos can be created and completed
- [ ] Tickets flow through status workflow
- [ ] Requests can be approved/rejected
- [ ] Calls logged with duration and outcome
- [ ] Non-planned visits recorded
- [ ] Performance dashboard shows all 5 tabs with correct metrics
- [ ] Agenda shows daily view
- [ ] Contact export works

### Technical

- [ ] All tests pass
- [ ] RTL works everywhere
- [ ] Offline sync works for all new entities
- [ ] Performance <3s for dashboard
- [ ] No security vulnerabilities

### Business

- [ ] Feature parity with Bricks Rep achieved
- [ ] No new dependencies added
- [ ] Documentation updated

---

## Next Steps

1. **Review**: Product Owner reviews all documents
2. **Approve**: Stakeholders approve plan
3. **Assign**: Assign tasks to developers
4. **Execute**: Begin Phase 1 implementation
5. **Monitor**: Weekly progress reviews

---

## Open Questions

1. **Chart library**: Should we use Chart.js for performance dashboard, or CSS-based charts?
2. **Kanban drag-and-drop**: Should kanban view support drag-and-drop for status changes?
3. **Hijri calendar**: Should Arabic calendar show Hijri dates alongside Gregorian?
4. **Export format**: Should we support Excel (XLSX) in addition to CSV?

**Recommendation**: Defer these to implementation phase. Start with simplest working version.
