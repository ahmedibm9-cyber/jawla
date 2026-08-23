# DATA MODEL: Visit Approval, Batch Assignment, Auto-Status & ROI Tracking

## Schema Changes

### `daily_visit_assignments` table (ALTER)

| Column         | Type                 | Change                                                                                                   |
| -------------- | -------------------- | -------------------------------------------------------------------------------------------------------- |
| `status`       | enum                 | Replace values: `pending`→`draft`, `missed`→`rejected`. Add: `pending_approval`, `approved`, `completed` |
| `submitted_at` | timestamp, nullable  | NEW                                                                                                      |
| `approved_at`  | timestamp, nullable  | NEW                                                                                                      |
| `approved_by`  | FK → users, nullable | NEW (set null on delete)                                                                                 |

**Final enum:** `draft`, `pending_approval`, `approved`, `rejected`, `completed`

### Migration SQL

```sql
-- Map old values
UPDATE daily_visit_assignments SET status = 'draft' WHERE status = 'pending';
UPDATE daily_visit_assignments SET status = 'rejected' WHERE status = 'missed';

-- Alter enum
ALTER TABLE daily_visit_assignments MODIFY COLUMN status ENUM(
    'draft', 'pending_approval', 'approved', 'rejected', 'completed'
) DEFAULT 'draft';

-- Add columns
ALTER TABLE daily_visit_assignments ADD COLUMN submitted_at TIMESTAMP NULL AFTER status;
ALTER TABLE daily_visit_assignments ADD COLUMN approved_at TIMESTAMP NULL AFTER submitted_at;
ALTER TABLE daily_visit_assignments ADD COLUMN approved_by BIGINT UNSIGNED NULL AFTER approved_at;
ALTER TABLE daily_visit_assignments ADD CONSTRAINT fk_dva_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;
```

## Existing Tables Used (read-only)

### `approval_requests` (morph)

- `approvable_type` = `DailyVisitAssignment`
- `approvable_id` = assignment ID
- Status flow: pending → approved/rejected

### `approval_steps`

- Linked to approval_request
- One step per approver (or multi-step for complex workflows)

### `invoices` (read for ROI)

- `user_id` = rep
- `status` IN (`paid`, `partially_paid`)
- `posting_date` = period filter
- `total` = revenue amount

### `expenses` (read for ROI)

- `user_id` = rep
- `cancelled_at` IS NULL
- `posting_date` = period filter
- `amount` = expense amount

## Relationships

```
DailyVisitAssignment
    ├── approvals() → MorphMany(ApprovalRequest)
    ├── latestApproval() → MorphOne(ApprovalRequest)
    ├── approvedBy() → BelongsTo(User)
    └── (existing: user, customer, assignedBy)
```

## Indexes

No new indexes needed — existing indexes on `user_id`, `visit_date`, and unique constraint cover all query patterns.
