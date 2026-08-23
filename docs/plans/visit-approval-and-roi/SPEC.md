# SPEC: Visit Approval, Batch Assignment, Auto-Status & ROI Tracking

## 1. Visit Plan Approval Workflow

### 1.1 Status Machine

```
draft → pending_approval → approved
                         → rejected
```

- `draft`: initial state when admin creates assignment(s)
- `pending_approval`: after admin submits for approval
- `approved`: after manager approves — visible to rep
- `rejected`: after manager rejects — not visible to rep

### 1.2 DailyVisitAssignment Schema Changes

Add to `daily_visit_assignments` table:

- `status` enum: add `draft`, `pending_approval`, `approved`, `rejected` (replace current `pending`, `completed`, `missed`)
- `submitted_at` timestamp, nullable
- `approved_at` timestamp, nullable
- `approved_by` FK → users, nullable

**Migration strategy:** Rename existing values. `pending` → `draft` (safest default for existing data).

### 1.3 Model Changes (`DailyVisitAssignment`)

Add relationships:

```php
public function approvals(): MorphMany
{
    return $this->morphMany(ApprovalRequest::class, 'approvable');
}

public function latestApproval(): MorphOne
{
    return $this->morphOne(ApprovalRequest::class, 'approvable')->latestOfMany();
}
```

### 1.4 Service (`DailyVisitAssignmentService`)

Methods:

- `submit(DailyVisitAssignment $assignment, User $submitter)` — resolves approver via `WorkflowApproverResolver::forSubmitter($submitter, 'visit_plans.approve')`, calls `ApprovalService::submit()`, sets status to `pending_approval`, sets `submitted_at`
- `approve(ApprovalRequest $request, User $actor)` — calls `ApprovalService::approve()`, if request fully approved sets assignment status to `approved`, sets `approved_at` and `approved_by`
- `reject(ApprovalRequest $request, User $actor, string $reason)` — calls `ApprovalService::reject()`, sets assignment status to `rejected`

### 1.5 Filament Resource Changes

In `DailyVisitAssignmentResource`:

- Table: eager-load `latestApproval.steps`
- Add `approve` action (visible when status = `pending_approval` and user has `visit_plans.approve` permission)
- Add `reject` action with reason textarea (same visibility)
- Default filter: show only `approved` assignments (managers see all, with status column)

### 1.6 Rep-Side (Home)

Filter `DailyVisitAssignment` query to `status = 'approved'` only.

### 1.7 Acceptance Criteria

```
Given an admin creates a visit plan for rep Ahmed on 2026-08-25,
When the admin submits it for approval,
Then the assignment status becomes "pending_approval" and submitted_at is set.

Given the assignment is pending_approval,
When a manager with visit_plans.approve permission approves it,
Then the assignment status becomes "approved", approved_at and approved_by are set.

Given the assignment is approved,
When rep Ahmed opens /app,
Then the assignment appears in today's plan.

Given the assignment is rejected,
When rep Ahmed opens /app,
Then the assignment does NOT appear in today's plan.
```

---

## 2. Batch Visit Assignment

### 2.1 Bulk Action

In `DailyVisitAssignmentResource` table, add `BulkAction::make('bulk_assign')`:

- Form fields:
  - `user_id` — rep picker (required)
  - `visit_date` — date picker (required)
  - `customer_ids` — customer multi-select (required, searchable)
- On submit: create one `DailyVisitAssignment` per customer with `status = 'draft'`
- Skip duplicates (unique constraint on `user_id + customer_id + visit_date`)
- Show success notification with count created + skipped

### 2.2 Route-Based Assignment (alternative)

Add a separate `Action::make('assign_from_route')`:

- Form fields:
  - `user_id` — rep picker (required)
  - `visit_date` — date picker (required)
  - `route_id` — route picker (required)
- On submit: get all customers assigned to that route, create assignments
- Skip duplicates

### 2.3 Acceptance Criteria

```
Given admin selects 5 customers and clicks "Bulk Assign" with rep Ahmed and date 2026-08-25,
When the action completes,
Then 5 DailyVisitAssignment records exist with status "draft" for Ahmed on that date.

Given 2 of those 5 customers already have assignments for Ahmed on that date,
When bulk assign runs,
Then 3 new records are created and 2 are skipped, notification shows "3 created, 2 skipped".
```

---

## 3. Auto Status Transition

### 3.1 Change

In `VisitReportService::submit()`, after `$visit->update(['status' => 'closed'])` (line 43):

```php
$visit->dailyVisitAssignment?->update(['status' => 'completed']);
```

### 3.2 New Status Value

Add `completed` to the `DailyVisitAssignment` status enum (alongside `draft`, `pending_approval`, `approved`, `rejected`).

Final enum: `draft`, `pending_approval`, `approved`, `rejected`, `completed`

### 3.3 Acceptance Criteria

```
Given assignment "Ahmed visits StoreX" has status "approved",
When Ahmed submits a visit report for that visit,
Then the assignment status auto-changes to "completed".

Given assignment has no linked visit (status = "draft" or "rejected"),
When any visit report is submitted,
Then no status change occurs (null safe operator handles this).
```

---

## 4. ROI Tracking

### 4.1 Service (`RoiService`)

```php
public function repRoi(int $userId, Carbon $from, Carbon $to): array
{
    $revenue = Invoice::where('user_id', $userId)
        ->whereIn('status', ['paid', 'partially_paid'])
        ->whereBetween('posting_date', [$from, $to])
        ->sum('total');

    $expenses = Expense::where('user_id', $userId)
        ->whereNull('cancelled_at')
        ->whereBetween('posting_date', [$from, $to])
        ->sum('amount');

    $roi = $expenses > 0 ? round(($revenue - $expenses) / $expenses * 100, 1) : null;

    return [
        'revenue' => $revenue,
        'expenses' => $expenses,
        'roi' => $roi,
    ];
}
```

### 4.2 Widget (`RepRoiWidget`)

Filament stats widget on dashboard:

- Period selector: this month / last month / this quarter / custom
- Table: rep name, revenue, expenses, ROI %
- Sort by ROI % descending
- Color badge: green ≥ 50%, yellow ≥ 0%, red < 0%

### 4.3 Acceptance Criteria

```
Given rep Ahmed has 10000 EGP revenue and 2000 EGP expenses this month,
When the ROI widget loads,
Then Ahmed shows ROI of 400.0%.

Given rep Sara has 5000 EGP revenue and 0 EGP expenses this month,
When the ROI widget loads,
Then Sara shows ROI of "N/A" (no expenses = can't calculate).

Given the period selector is set to "Last Month",
When the widget loads,
Then only invoices and expenses from last month are included.
```

---

## Migration Strategy

1. Add new status values to enum (draft, pending_approval, approved, rejected, completed)
2. Migrate existing data: `pending` → `draft`, `completed` → `completed`, `missed` → `rejected`
3. Drop old enum values after migration
4. Add `submitted_at`, `approved_at`, `approved_by` columns
5. All changes are additive — no data loss

## Permission

- `visit_plans.approve` — new permission for approving visit plans
- Admin/manager roles get this permission by default
