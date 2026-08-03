# Phase 2: Approval System

## PRD

### Problem

Jawla has a basic `ApprovalService` and `approval_requests`/`approval_steps` tables, but no centralized UI for managers to review pending submissions. Reps submit tasks, orders, collections, and returns but managers must hunt through individual resource lists to find what needs approval. The spec requires a centralized approval inbox (W-19), approval detail (W-20), mobile approval queue (M-49), and a visual approval workflow builder (W-21).

### Users

- **Sales Manager** (web): Needs one place to see all pending approvals, take action quickly
- **Operations Manager** (web): Needs to approve/reject with reason, see submission evidence
- **Sales Rep** (mobile): Needs to see approval status on submitted items, receive rejection reasons
- **Admin** (web): Needs to configure approval workflows per transaction type

### Outcomes

1. Centralized approval inbox shows all pending items across types
2. Approval detail shows submission data + evidence + approval history in split-panel
3. Mobile approval queue for managers on the go
4. Visual workflow builder for configuring approval chains
5. Reps see approval status on their submitted items
6. Rejection always requires a written reason

### Non-Goals

- Parallel approval (Phase 3+)
- Delegation (Phase 3+)
- Escalation (Phase 3+)
- Auto-approve rules (Phase 3+)

---

## SPEC

### 1. Approval Inbox (W-19)

**Actor:** Sales Manager, Operations Manager
**Precondition:** User has `approvals.view` permission

**Screen:**

```
┌──────────────────────────────────────────────────────────┐
│ Approval Inbox                    Filters: [All ▾] [Type ▾]│
├──────────────────────────────────────────────────────────┤
│ ┌─ Type ─── Ref ────── Submitter ── Value ── SLA ── ──┐ │
│ │ ☐ Order   #SO-0045  Rep Ahmed    855.00  2h ago  →  │ │
│ │ ☐ Return  #RR-0012  Rep Sara     320.00  5h ago  ⚠  │ │
│ │ ☐ Task    #TSK-008  Rep Omar     —       1d ago  🔴  │ │
│ │ ☐ Collect #CS-0034  Rep Ahmed    1,200   30m ago     │ │
│ │ ☐ Expense #EXP-007  Rep Sara     150.00  3h ago      │ │
│ └──────────────────────────────────────────────────────┘ │
│                                                          │
│ [Approve Selected] [Reject Selected] [View Detail]       │
│                                                          │
│ Showing 1-5 of 12                    [< 1 2 3 >]        │
└──────────────────────────────────────────────────────────┘
```

**Columns:**

| Column    | Source                                    | Notes                                  |
| --------- | ----------------------------------------- | -------------------------------------- |
| Type      | `approval_requests.approvable_type`       | Icon + label                           |
| Reference | `approvable.invoice_number` etc           | Document number                        |
| Submitter | `users.name` via `submitted_by`           | Rep name                               |
| Value     | `approvable.total` or `approvable.amount` | Currency formatted                     |
| SLA       | `submitted_at` relative time              | Color: green <1h, yellow 1-4h, red >4h |
| Status    | `approval_requests.status`                | Pending badge                          |

**Filters:**

- Type: All, Orders, Returns, Tasks, Collections, Expenses
- Status: All, Pending, Submitted, Overdue (>24h)
- Submitter: dropdown of reps
- Date range

**Bulk actions:**

- Approve selected (simple approvals only)
- Reject selected (requires reason modal)

**Behavior:**

- Paginated, 20 per page
- Real-time via `wire:poll.60s`
- Click row → navigate to approval detail
- Checkbox for bulk actions

**Permissions:** `approvals.view`, `approvals.approve`

---

### 2. Approval Detail (W-20)

**Actor:** Sales Manager
**Precondition:** Pending approval exists

**Screen (split-panel):**

```
┌─────────────────────────────┬─────────────────────────────┐
│ LEFT: Submission Data       │ RIGHT: Approval Workflow    │
├─────────────────────────────┼─────────────────────────────┤
│                             │                             │
│ Order #SO-0045              │ Workflow: Sales Approval    │
│ Customer: Ahmed Trading     │                             │
│ Submitted: 2026-08-03 14:00 │ Step 1: Sales Manager      │
│                             │ Status: ⏳ Waiting          │
│ ── Items ──                 │ You are here                │
│ Widget A    ×10   250.00   │                             │
│ Widget B    ×5    250.00   │ Step 2: Finance             │
│ Gadget C    ×20   250.00   │ Status: ⏳ Waiting          │
│ ──────────────────────────  │                             │
│ Subtotal:     750.00       │ ── History ──               │
│ VAT:          105.00       │ 03 Aug 14:00 Submitted      │
│ Total:        855.00       │                             │
│                             │ ── Comments ──              │
│ ── Evidence ──              │ [Add comment...]            │
│ [photo1.jpg] [photo2.jpg]   │                             │
│                             │                             │
│ ── Customer ──              │                             │
│ Ahmed Trading Co.           │                             │
│ Balance: 3,800.00           │                             │
│ Credit Limit: 5,000.00     │                             │
│                             │                             │
├─────────────────────────────┤                             │
│ [Approve] [Reject] [Request Changes] [Escalate]          │
└─────────────────────────────┴─────────────────────────────┘
```

**Behavior:**

- Left panel: full submission data with photos/evidence
- Right panel: workflow steps, history, comments
- Approve: sets `approval_steps.status = approved`, advances workflow
- Reject: opens reason modal (required), sets status=rejected, notifies rep
- Request Changes: opens reason modal + checklist of what to fix
- Each action creates audit entry in `activities` table

**Permissions:** `approvals.approve`, must be assigned approver in workflow step

---

### 3. Mobile Approval Queue (M-49, M-50)

**Actor:** Sales Manager (mobile)
**Precondition:** User has `approvals.approve` permission

**Screen:**

```
┌─────────────────────────────────┐
│ Approvals (3 pending)           │
├─────────────────────────────────┤
│ ┌─────────────────────────────┐ │
│ │ 📦 Order #SO-0045          │ │
│ │ Ahmed Trading · 855.00     │ │
│ │ Rep Ahmed · 2h ago         │ │
│ │ [Approve] [Reject] [View]  │ │
│ └─────────────────────────────┘ │
│ ┌─────────────────────────────┐ │
│ │ 🔄 Return #RR-0012         │ │
│ │ Sara Supplies · 320.00     │ │
│ │ Rep Sara · 5h ago ⚠        │ │
│ │ [Approve] [Reject] [View]  │ │
│ └─────────────────────────────┘ │
│ ┌─────────────────────────────┐ │
│ │ 📋 Task #TSK-008           │ │
│ │ Market Survey · Zone A      │ │
│ │ Rep Omar · 1d ago 🔴       │ │
│ │ [Approve] [Reject] [View]  │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

**Behavior:**

- Quick approve: tap "Approve" → confirmation dialog → done
- Quick reject: tap "Reject" → reason modal (required) → done
- "View" → full detail page
- Pull-to-refresh
- Push notification on new pending approval

**New Livewire Components:**

- `ApprovalQueue` — list of pending approvals
- `ApprovalDetailMobile` — full detail view

**New Routes:**

- `GET /app/approvals` → `ApprovalQueue`
- `GET /app/approvals/{approval}` → `ApprovalDetailMobile`

---

### 4. Approval Workflow Builder (W-21)

**Actor:** Admin
**Precondition:** User has `admin` role

**Screen:**

```
┌──────────────────────────────────────────────────────────┐
│ Approval Workflows                    [+ Create Workflow]│
├──────────────────────────────────────────────────────────┤
│ ┌─ Name ──────── Type ────── Steps ── Status ──────── ┐  │
│ │ Sales Order  Order        2       Active           │  │
│ │ High Value   Order >5000  3       Active           │  │
│ │ Collection   Collection   1       Active           │  │
│ │ Return       Return       2       Active           │  │
│ └─────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘
```

**Workflow Builder Screen:**

```
┌──────────────────────────────────────────────────────────┐
│ Edit Workflow: Sales Order Approval                      │
├──────────────────────────────────────────────────────────┤
│ Name: [Sales Order Approval          ]                   │
│ Type: [Sales Order           ▾]                          │
│ Condition: [All orders      ▾]                           │
│            [Amount > [0    ] ▾]                          │
│                                                          │
│ ── Steps ──                                              │
│ ┌──────────────────────────────────────────────────────┐ │
│ │ Step 1: [Sales Manager    ▾]                         │ │
│ │ Type: [Approve ▾]                                    │ │
│ ├──────────────────────────────────────────────────────┤ │
│ │ Step 2: [Finance User     ▾]  (if amount > 1000)    │ │
│ │ Type: [Approve ▾]                                    │ │
│ └──────────────────────────────────────────────────────┘ │
│ [+ Add Step]                                             │
│                                                          │
│ [Save] [Delete] [Test with sample]                       │
└──────────────────────────────────────────────────────────┘
```

**Data Model:**

```php
Schema::create('approval_workflows', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained();
    $table->string('name');
    $table->string('type'); // order, return, collection, task, expense
    $table->json('conditions')->nullable(); // {min_amount: 0, max_amount: null}
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('approval_workflow_steps', function (Blueprint $table) {
    $table->id();
    $table->foreignId('approval_workflow_id')->cascadeOnDelete();
    $table->unsignedSmallInteger('sequence');
    $table->foreignId('approver_role_id')->constrained('roles');
    $table->string('condition')->nullable(); // "amount > 1000"
    $table->timestamps();
});
```

**Behavior:**

- CRUD for workflows + steps
- "Test with sample" shows which steps would trigger for a given input
- When approval is submitted: `ApprovalService` resolves workflow, creates `approval_requests` + `approval_steps` per workflow
- Existing hardcoded workflow logic replaced by database-driven workflow

---

### 5. Rep-Facing Approval Status

**Actor:** Sales Rep
**Precondition:** Rep has submitted items

**Changes to existing screens:**

- `Orders` list: add "Approval" column showing status badge
- `Tasks` detail: show approval history in Activity tab
- `CollectPayment`: show approval status after submit
- `LogReturn`: show approval status after submit

**Badge colors:**

| Status            | Color  | Label             |
| ----------------- | ------ | ----------------- |
| Pending           | Gray   | Pending           |
| In Review         | Blue   | In Review         |
| Approved          | Green  | Approved          |
| Rejected          | Red    | Rejected          |
| Changes Requested | Yellow | Changes Requested |

---

## USER_JOURNEYS

### Journey 1: Manager Approves Order

1. Manager opens Approval Inbox
2. Sees 5 pending items, 1 overdue
3. Clicks Order #SO-0045
4. Reviews items, total, credit status
5. Sees evidence photos
6. Taps "Approve"
7. Confirmation: "Approve order #SO-0045 for 855.00?"
8. Confirms → order status changes to approved
9. Rep receives notification: "Your order #SO-0045 was approved"

### Journey 2: Manager Rejects with Reason

1. Manager opens Return #RR-0012
2. Reviews return items, photos
3. Notices condition doesn't match photos
4. Taps "Reject"
5. Reason modal: "Product condition does not match claimed damage"
6. Confirms → return status changes to rejected
7. Rep receives notification with rejection reason
8. Rep sees "Changes Requested" on return detail

### Journey 3: Admin Configures Workflow

1. Admin opens Approval Workflows
2. Creates new workflow: "High-Value Orders"
3. Condition: Order amount > 5,000
4. Step 1: Sales Manager approves
5. Step 2: Finance User approves
6. Saves workflow
7. Next order > 5,000 goes through 2-step approval

### Journey 4: Rep Sees Rejection

1. Rep opens Orders list
2. Sees Order #SO-0045 with "Rejected" badge
3. Opens detail, sees rejection reason in Activity
4. Sees manager comment: "Price doesn't match agreement"
5. Rep can create new order or contact manager

---

## ARCHITECTURE

### Decisions

| Decision          | Choice                                       | Rationale                                          | Reversible |
| ----------------- | -------------------------------------------- | -------------------------------------------------- | ---------- |
| Approval inbox    | New Livewire page, not Filament resource     | Needs custom layout (split-panel), mobile-first    | Yes        |
| Workflow builder  | Database-driven `approval_workflows` + steps | Replaces hardcoded logic, configurable per tenant  | Yes        |
| Mobile approvals  | Separate Livewire components in PWA          | Different UX from web, needs to work offline-aware | Yes        |
| Real-time updates | `wire:poll.60s` on inbox                     | No WebSocket needed, acceptable latency            | Yes        |
| Rejection reason  | Required string field in modal               | Spec requires written reason for every rejection   | Yes        |
| Approval chaining | Sequential only (parallel in Phase 3)        | Simpler MVP, covers 80% of cases                   | Yes        |

### Data Flow

```
Rep submits → SyncController → SyncHandler → creates record
  → ApprovalService::createApprovalRequest()
    → resolves workflow (approval_workflows + steps)
    → creates approval_requests + approval_steps
    → sends notification to approver

Manager approves → ApprovalController::approve()
  → updates approval_step.status
  → checks if all steps complete
  → if complete: updates approval_request.status = approved
    → notifies submitter
    → triggers downstream (order→warehouse, etc.)

Manager rejects → ApprovalController::reject()
  → requires reason
  → updates approval_step.status = rejected
  → updates approval_request.status = rejected
  → notifies submitter with reason
```

### Permissions

| Permission           | Role                           | Scope                 |
| -------------------- | ------------------------------ | --------------------- |
| `approvals.view`     | sales_manager, accounts, admin | See inbox             |
| `approvals.approve`  | sales_manager, accounts, admin | Approve/reject        |
| `approvals.manage`   | admin                          | Create/edit workflows |
| `approvals.escalate` | sales_manager, admin           | Escalate (Phase 3)    |

---

## TASKS

### Milestone 1: Approval Inbox (2 days)

| #   | Task                                               | Files                                                   | Tests                        | Status  |
| --- | -------------------------------------------------- | ------------------------------------------------------- | ---------------------------- | ------- |
| 1.1 | New Livewire component `ApprovalInbox`             | `app/Livewire/App/ApprovalInbox.php`                    | Component renders            | Pending |
| 1.2 | Blade: table with type, ref, submitter, value, SLA | `resources/views/livewire/app/approval-inbox.blade.php` | Table renders                | Pending |
| 1.3 | Filter by type, status, submitter, date            | `app/Livewire/App/ApprovalInbox.php`                    | Filters work                 | Pending |
| 1.4 | Pagination (20 per page)                           | `app/Livewire/App/ApprovalInbox.php`                    | Pagination works             | Pending |
| 1.5 | Bulk approve/reject actions                        | `app/Livewire/App/ApprovalInbox.php`                    | Actions work                 | Pending |
| 1.6 | Reject reason modal                                | `resources/views/livewire/app/approval-inbox.blade.php` | Modal shows, reason required | Pending |
| 1.7 | Add route `GET /admin/approvals`                   | `routes/web.php`                                        | Route accessible             | Pending |
| 1.8 | Register in admin panel nav                        | `app/Providers/Filament/AdminPanelProvider.php`         | Nav item visible             | Pending |
| 1.9 | Pest test: inbox CRUD                              | `tests/Feature/ApprovalInboxTest.php`                   | All flows tested             | Pending |

### Milestone 2: Approval Detail (2 days)

| #    | Task                                        | Files                                                    | Tests                 | Status  |
| ---- | ------------------------------------------- | -------------------------------------------------------- | --------------------- | ------- |
| 2.1  | New Livewire component `ApprovalDetail`     | `app/Livewire/App/ApprovalDetail.php`                    | Component renders     | Pending |
| 2.2  | Blade: split-panel layout                   | `resources/views/livewire/app/approval-detail.blade.php` | Layout renders        | Pending |
| 2.3  | Left panel: submission data + evidence      | `resources/views/livewire/app/approval-detail.blade.php` | Data shows            | Pending |
| 2.4  | Right panel: workflow steps + history       | `resources/views/livewire/app/approval-detail.blade.php` | Steps show            | Pending |
| 2.5  | Approve action with confirmation            | `app/Livewire/App/ApprovalDetail.php`                    | Approval works        | Pending |
| 2.6  | Reject action with reason modal             | `app/Livewire/App/ApprovalDetail.php`                    | Rejection works       | Pending |
| 2.7  | Request changes action                      | `app/Livewire/App/ApprovalDetail.php`                    | Changes request works | Pending |
| 2.8  | Comments system                             | `app/Livewire/App/ApprovalDetail.php`                    | Comments add/list     | Pending |
| 2.9  | Add route `GET /admin/approvals/{approval}` | `routes/web.php`                                         | Route accessible      | Pending |
| 2.10 | Pest test: approval detail                  | `tests/Feature/ApprovalDetailTest.php`                   | All actions tested    | Pending |

### Milestone 3: Mobile Approvals (2 days)

| #   | Task                                                       | Files                                                   | Tests             | Status  |
| --- | ---------------------------------------------------------- | ------------------------------------------------------- | ----------------- | ------- |
| 3.1 | New Livewire component `ApprovalQueue`                     | `app/Livewire/App/ApprovalQueue.php`                    | Component renders | Pending |
| 3.2 | Blade: card list with quick approve/reject                 | `resources/views/livewire/app/approval-queue.blade.php` | Cards render      | Pending |
| 3.3 | Quick approve with confirmation dialog                     | `app/Livewire/App/ApprovalQueue.php`                    | Approve works     | Pending |
| 3.4 | Quick reject with reason modal                             | `app/Livewire/App/ApprovalQueue.php`                    | Reject works      | Pending |
| 3.5 | New Livewire component `ApprovalDetailMobile`              | `app/Livewire/App/ApprovalDetailMobile.php`             | Detail renders    | Pending |
| 3.6 | Add routes `GET /app/approvals`, `GET /app/approvals/{id}` | `routes/web.php`                                        | Routes accessible | Pending |
| 3.7 | Add to bottom nav or More page                             | Layout files                                            | Nav item visible  | Pending |
| 3.8 | Push notification on new pending approval                  | `app/Services/PushService.php`                          | Notification sent | Pending |
| 3.9 | Pest test: mobile approvals                                | `tests/Feature/MobileApprovalTest.php`                  | All flows tested  | Pending |

### Milestone 4: Workflow Builder (3 days)

| #   | Task                                                       | Files                                                 | Tests                     | Status  |
| --- | ---------------------------------------------------------- | ----------------------------------------------------- | ------------------------- | ------- |
| 4.1 | Migration: `approval_workflows`, `approval_workflow_steps` | `database/migrations/`                                | Migrations run            | Pending |
| 4.2 | Models: `ApprovalWorkflow`, `ApprovalWorkflowStep`         | `app/Models/`                                         | Relationships work        | Pending |
| 4.3 | Filament Resource: `ApprovalWorkflowResource`              | `app/Filament/Resources/ApprovalWorkflowResource.php` | CRUD works                | Pending |
| 4.4 | Builder UI: steps with role selector + conditions          | `app/Filament/Resources/ApprovalWorkflowResource.php` | Builder renders           | Pending |
| 4.5 | Update `ApprovalService` to use workflow config            | `app/Services/ApprovalService.php`                    | Resolves workflow from DB | Pending |
| 4.6 | Seed default workflows for existing types                  | `database/seeders/ApprovalWorkflowSeeder.php`         | Defaults created          | Pending |
| 4.7 | "Test with sample" feature                                 | `app/Filament/Resources/ApprovalWorkflowResource.php` | Shows matching steps      | Pending |
| 4.8 | Pest test: workflow CRUD + resolution                      | `tests/Feature/ApprovalWorkflowTest.php`              | Full lifecycle tested     | Pending |

### Milestone 5: Rep-Facing Status (1 day)

| #   | Task                                           | Files                                                    | Tests             | Status  |
| --- | ---------------------------------------------- | -------------------------------------------------------- | ----------------- | ------- |
| 5.1 | Add approval status badge to Orders list       | `resources/views/livewire/app/orders.blade.php`          | Badge shows       | Pending |
| 5.2 | Add approval status to Tasks detail            | `resources/views/livewire/app/task-detail.blade.php`     | Status shows      | Pending |
| 5.3 | Add approval status to collection after submit | `resources/views/livewire/app/collect-payment.blade.php` | Status shows      | Pending |
| 5.4 | Add approval status to return after submit     | `resources/views/livewire/app/log-return.blade.php`      | Status shows      | Pending |
| 5.5 | Pest test: status badges                       | `tests/Feature/ApprovalStatusTest.php`                   | All badges render | Pending |

### Milestone 6: Integration (1 day)

| #   | Task                                      | Files              | Tests              | Status  |
| --- | ----------------------------------------- | ------------------ | ------------------ | ------- |
| 6.1 | Update QA test script with approval flows | `jawla_full_qa.py` | New screens tested | Pending |
| 6.2 | Run `make verify`                         | Terminal           | All tests pass     | Pending |
| 6.3 | Deploy to staging                         | `railway up`       | Staging works      | Pending |
| 6.4 | Test approval flow end-to-end on staging  | Manual             | Flow complete      | Pending |

---

## RISKS

| Risk                                     | Impact                                                 | Mitigation                                                   |
| ---------------------------------------- | ------------------------------------------------------ | ------------------------------------------------------------ |
| Workflow builder complexity              | Could spiral into full BPM engine                      | Keep to sequential steps only, no parallel/conditional logic |
| Existing hardcoded approvals conflict    | New workflow logic may break existing approvals        | Feature-flag: if no workflow configured, use existing logic  |
| Mobile approval without reason on reject | Rep doesn't know what to fix                           | Enforce reason on every reject/reject_with_changes           |
| Poll-based updates delayed               | Manager sees stale data                                | Acceptable for MVP; upgrade to Echo/WebSocket later          |
| Migration from hardcoded to DB workflows | Existing pending approvals may not match new workflows | Create "legacy" workflow that matches existing behavior      |

---

```yaml
plan_result:
  scope:
    [
      approval-inbox,
      approval-detail,
      mobile-approvals,
      workflow-builder,
      rep-status,
    ]
  non_goals:
    [parallel-approval, delegation, escalation, auto-approve, echo-websocket]
  acceptance_criteria_count: 38
  architecture_decisions:
    [
      database-driven-workflows,
      sequential-only,
      wire-poll-realtime,
      required-rejection-reason,
    ]
  milestones:
    [
      approval-inbox,
      approval-detail,
      mobile-approvals,
      workflow-builder,
      rep-status,
      integration,
    ]
  critical_path: [migration → models → approval-service-update → inbox → detail]
  approval_gates:
    [after-milestone-4-workflow-builder, after-milestone-6-integration]
  risks:
    [
      workflow-complexity,
      hardcoded-conflict,
      mobile-reject-reason,
      poll-staleness,
      migration-legacy,
    ]
  documents_written: [this-plan]
  next_vertical_slice: Milestone 1 — Approval Inbox
  recommended_next_skill: v-implementation-strategist
```
