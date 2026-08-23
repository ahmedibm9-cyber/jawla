# ARCHITECTURE: Visit Approval, Batch Assignment, Auto-Status & ROI Tracking

## Current Design

### Visit Planning Flow

```
Admin (Filament) → creates DailyVisitAssignment (status=pending)
                                    ↓
Rep (PWA Home) → sees assignment → taps → creates Visit → GPS check-in → submits report
                                    ↓
                              VisitReportService::submit()
                                    ↓
                              Visit status = closed
                              (assignment status unchanged — GAP)
```

### Approval Infrastructure (existing)

```
ApprovalService::submit($model, $submitter, [$approvers])
    → creates ApprovalRequest (status=pending)
    → creates ApprovalStep per approver
    → model status updated by calling service

ApprovalService::approve($request, $actor)
    → updates ApprovalStep status
    → if all steps approved → ApprovalRequest status = approved
    → calling service updates model status
```

Currently wired to: SalesOrder, Task, ReturnRequest, CollectionSubmission

### Expense Tracking (existing)

```
Rep logs expense → ExpenseService::log() → deducts from CashBox
Admin views expenses → ExpenseResource (read-only, can cancel)
```

## Proposed Design

### New Visit Planning Flow

```
Admin (Filament) → creates DailyVisitAssignment (status=draft)
       ↓ (bulk assign or route-based)
Admin → submits for approval → DailyVisitAssignmentService::submit()
    → ApprovalService::submit() → ApprovalRequest created
    → assignment status = pending_approval

Manager → approves → DailyVisitAssignmentService::approve()
    → ApprovalService::approve()
    → assignment status = approved

Rep (PWA Home) → sees only approved assignments
    → taps → creates Visit → GPS check-in → submits report
    → VisitReportService::submit()
    → assignment status = completed (auto)
```

### ROI Calculation

```
RoiService::repRoi(userId, from, to)
    → SUM(invoices.total) WHERE user_id AND status IN (paid, partially_paid)
    → SUM(expenses.amount) WHERE user_id AND cancelled_at IS NULL
    → ROI = (revenue - expenses) / expenses × 100
    → null if expenses = 0
```

## Decisions

| Decision         | Choice                                                   | Rationale                                                      |
| ---------------- | -------------------------------------------------------- | -------------------------------------------------------------- |
| Approval pattern | Reuse ApprovalService exactly                            | Proven pattern, 4 existing integrations, no new infrastructure |
| Status machine   | draft → pending_approval → approved/rejected + completed | Minimal states, covers all flows                               |
| Bulk assign      | Filament BulkAction                                      | Standard Filament pattern, no custom UI needed                 |
| ROI formula      | (revenue - expenses) / expenses                          | Simple, actionable, matches Bricks Rep's ROI concept           |
| Permission       | New `visit_plans.approve` permission                     | Follows existing permission model                              |
| Migration        | Add new enum values + map old ones                       | Non-destructive, reversible                                    |

## Alternatives Considered

| Alternative                      | Why not                                                        |
| -------------------------------- | -------------------------------------------------------------- |
| Route optimization (Google Maps) | Out of scope, Bricks Rep doesn't have it either                |
| Per-visit cost tracking          | Over-engineering — expenses are already per-rep, not per-visit |
| Rep-side visit planning          | Bricks Rep doesn't offer this, admin-only is sufficient        |
| Caching ROI calculations         | YAGNI — dashboard widget queries are fast enough               |
