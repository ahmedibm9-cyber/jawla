# PERMISSIONS: Visit Approval, Batch Assignment, Auto-Status & ROI Tracking

## New Permission

| Permission            | Description                              | Roles          |
| --------------------- | ---------------------------------------- | -------------- |
| `visit_plans.approve` | Approve or reject visit plan submissions | admin, manager |

## Existing Permissions Used

| Permission                       | Used For                          |
| -------------------------------- | --------------------------------- |
| `daily_visit_assignments.create` | Create single or bulk assignments |
| `daily_visit_assignments.update` | Edit assignment details           |
| `daily_visit_assignments.delete` | Remove assignments                |

## Permission Checks

### Filament Resource Actions

- **Approve action:** visible when `$record->status === 'pending_approval'` AND `auth()->user()->can('visit_plans.approve')`
- **Reject action:** same visibility as approve
- **Bulk assign:** requires `daily_visit_assignments.create`
- **Route assign:** requires `daily_visit_assignments.create`

### Service Layer

- `DailyVisitAssignmentService::submit()` — no permission check (caller already authorized via Filament)
- `DailyVisitAssignmentService::approve()` — no explicit check (ApprovalService validates company access)
- `DailyVisitAssignmentService::reject()` — same as approve

### Rep-Side (PWA)

- `Home.php` — no permission change needed, filter is by status not role
- Rep sees only `approved` assignments regardless of their permissions

## Authorization Model

```
Admin → can create, submit, approve, reject, bulk assign, view ROI
Manager → can create, submit, approve, reject (if has visit_plans.approve)
Rep → can only see approved assignments, cannot approve
```
