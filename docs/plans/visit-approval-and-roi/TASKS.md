# TASKS: Visit Approval, Batch Assignment, Auto-Status & ROI Tracking

## Milestone 1: Auto-Status Transition (smallest, immediate value)

### Task 1.1: Add `completed` to DailyVisitAssignment status enum

- **Files:** `database/migrations/xxxx_alter_daily_visit_assignment_status.php` (new migration)
- **What:** Add `completed` to the enum. Migrate existing `completed` values (currently doesn't exist, but enum needs updating). Migrate `missed` → `rejected`.
- **Depends:** None
- **Verify:** `php artisan migrate` succeeds, existing data preserved

### Task 1.2: Hook auto-transition in VisitReportService

- **Files:** `app/Services/VisitReportService.php`
- **What:** After line 43 (`$visit->update(['status' => 'closed'])`), add `$visit->dailyVisitAssignment?->update(['status' => 'completed']);`
- **Depends:** 1.1
- **Verify:** Write a Pest test: create assignment (status=approved) → create visit linked to it → submit visit report → assert assignment status = completed

---

## Milestone 2: Visit Plan Approval Workflow

### Task 2.1: Schema changes for approval

- **Files:** new migration
- **What:** Add `submitted_at`, `approved_at`, `approved_by` columns to `daily_visit_assignments`. Update enum: `pending` → `draft`, add `pending_approval`, `approved`, `rejected`.
- **Depends:** 1.1 (enum already has `completed`)
- **Verify:** `php artisan migrate` succeeds

### Task 2.2: Add approval relationships to DailyVisitAssignment model

- **Files:** `app/Models/DailyVisitAssignment.php`
- **What:** Add `approvals()` MorphMany and `latestApproval()` MorphOne (exact pattern from SalesOrder)
- **Depends:** 2.1
- **Verify:** Tinker: `$assignment->approvals` returns empty collection

### Task 2.3: Create DailyVisitAssignmentService

- **Files:** `app/Services/DailyVisitAssignmentService.php` (new)
- **What:** Methods: `submit()`, `approve()`, `reject()` following SalesOrder pattern exactly. Use `WorkflowApproverResolver` with permission `visit_plans.approve`.
- **Depends:** 2.2
- **Verify:** Write Pest tests for submit/approve/reject flow

### Task 2.4: Update Filament resource with approval actions

- **Files:** `app/Filament/Resources/DailyVisitAssignmentResource.php`
- **What:** Add approve/reject table actions. Eager-load `latestApproval.steps`. Add status filter.
- **Depends:** 2.3
- **Verify:** Manual test: create assignment → submit → approve → check status = approved

### Task 2.5: Filter rep-side Home to approved only

- **Files:** `app/Livewire/App/Home.php`
- **What:** Add `->where('status', 'approved')` to the DailyVisitAssignment query
- **Depends:** 2.1
- **Verify:** Rep sees only approved assignments, not draft/pending

### Task 2.6: Add permission

- **Files:** seeder or permission migration
- **What:** Create `visit_plans.approve` permission. Assign to admin/manager roles.
- **Depends:** None
- **Verify:** Permission exists in database

---

## Milestone 3: Batch Visit Assignment

### Task 3.1: Bulk assign action

- **Files:** `app/Filament/Resources/DailyVisitAssignmentResource.php`
- **What:** Add `BulkAction::make('bulk_assign')` with form (rep, date, customers). Create assignments with status `draft`. Skip duplicates.
- **Depends:** 2.1
- **Verify:** Select 5 customers → bulk assign → 5 records created with status draft

### Task 3.2: Route-based assignment action

- **Files:** `app/Filament/Resources/DailyVisitAssignmentResource.php`
- **What:** Add `Action::make('assign_from_route')` with form (rep, date, route). Auto-fill customers from route.
- **Depends:** 3.1
- **Verify:** Select route with 10 customers → 10 assignments created

---

## Milestone 4: ROI Tracking

### Task 4.1: Create RoiService

- **Files:** `app/Services/RoiService.php` (new)
- **What:** `repRoi(userId, from, to)` method returning revenue, expenses, ROI %
- **Depends:** None
- **Verify:** Write Pest test with known revenue/expenses → assert ROI calculation

### Task 4.2: Create RepRoiWidget

- **Files:** `app/Filament/Widgets/RepRoiWidget.php` (new)
- **What:** Stats widget with period selector, table of rep name / revenue / expenses / ROI %
- **Depends:** 4.1
- **Verify:** Widget renders on dashboard with test data

---

## Critical Path

1.1 → 1.2 → 2.1 → 2.2 → 2.3 → 2.4 → 3.1 → 3.2
↘ 2.5
2.6 (parallel)
4.1 → 4.2 (parallel)

## Approval Gates

- After Milestone 1: verify auto-status works end-to-end
- After Milestone 2: verify approval workflow in admin + rep-side filtering
- After Milestone 3: verify bulk assign + route assign
- After Milestone 4: verify ROI widget

## Risks

- **Migration data loss:** Mitigated by mapping old enum values to new ones in same migration
- **Permission regression:** New permission `visit_plans.approve` must be added to existing roles
- **Rep sees wrong assignments:** Home filter must be tested thoroughly
