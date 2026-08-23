# RISKS: Visit Approval, Batch Assignment, Auto-Status & ROI Tracking

## Product Risks

| Risk                                                         | Impact            | Mitigation                                                     |
| ------------------------------------------------------------ | ----------------- | -------------------------------------------------------------- |
| Managers reject all plans, reps have nothing to do           | Reps idle         | Add "rejected" notification so rep knows to check with manager |
| Bulk assign creates too many assignments for one day         | Rep overwhelmed   | No guard needed — admin controls this, same as Bricks Rep      |
| ROI shows negative for new reps (low revenue, some expenses) | Misleading metric | Show "N/A" when expenses = 0, badge color handles negatives    |

## Security Risks

| Risk                                | Impact                | Mitigation                                                        |
| ----------------------------------- | --------------------- | ----------------------------------------------------------------- |
| Rep sees unapproved assignments     | Premature execution   | Filter in Home.php query — `where('status', 'approved')`          |
| Non-manager approves visit plan     | Unauthorized action   | Permission gate: `visit_plans.approve` checked in Filament action |
| Approval bypass via direct API call | Workflow circumvented | Service layer enforces status transitions, not just UI            |

## Migration Risks

| Risk                                                 | Impact            | Mitigation                                                              |
| ---------------------------------------------------- | ----------------- | ----------------------------------------------------------------------- |
| Enum migration fails on existing data                | Deploy blocked    | Map old values to new in same migration: pending→draft, missed→rejected |
| `approved_by` FK references deleted user             | Query error       | Nullable FK, set null on delete                                         |
| Assignment status stuck in `pending` after migration | Silent data issue | Default all existing `pending` to `draft` — safe, admin can resubmit    |

## Delivery Risks

| Risk                                                         | Impact                  | Mitigation                                                                       |
| ------------------------------------------------------------ | ----------------------- | -------------------------------------------------------------------------------- |
| ApprovalService has undocumented edge cases                  | Approval flow breaks    | Write Pest tests covering submit/approve/reject before implementation            |
| WorkflowApproverResolver can't find approver for visit_plans | Submit throws exception | Use `allowCompanyFallback: true` as fallback                                     |
| ROI widget slow on large datasets                            | Dashboard lag           | Index on `invoices.user_id + posting_date` and `expenses.user_id + posting_date` |
