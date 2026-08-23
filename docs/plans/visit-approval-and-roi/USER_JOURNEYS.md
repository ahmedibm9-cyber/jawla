# USER JOURNEYS: Visit Approval, Batch Assignment, Auto-Status & ROI Tracking

## Journey 1: Admin Creates and Submits Visit Plan

**Actor:** Admin/Manager
**Precondition:** Admin is logged into Filament, has visit_plans permission

1. Admin navigates to DailyVisitAssignment resource
2. Admin clicks "Create" or uses "Bulk Assign" action
3. Admin fills: rep, date, customers (one or many)
4. Admin saves → assignments created with status `draft`
5. Admin clicks "Submit for Approval" action on the assignment
6. System resolves approver via WorkflowApproverResolver
7. System creates ApprovalRequest + ApprovalStep
8. Assignment status changes to `pending_approval`
9. Notification sent to approver

**Edge cases:**

- No approver found → exception with clear message
- Duplicate assignment (same rep+customer+date) → skipped in bulk, error in single create
- Admin tries to submit already-submitted assignment → action not visible

---

## Journey 2: Manager Approves Visit Plan

**Actor:** Manager with `visit_plans.approve` permission
**Precondition:** Assignment is in `pending_approval` status

1. Manager sees assignment in list with "pending" badge
2. Manager clicks "Approve" action
3. Confirmation modal: "Approve visit plan for [rep] on [date]?"
4. Manager confirms
5. System calls ApprovalService::approve()
6. If single approver: Assignment status → `approved`, `approved_at` set, `approved_by` set
7. If multi-step: waits for all approvers
8. Rep now sees this assignment in their Home

**Edge cases:**

- Manager rejects → status → `rejected`, reason saved, rep doesn't see it
- Manager tries to approve already-approved → action not visible
- Two managers approve simultaneously → lockForUpdate prevents race

---

## Journey 3: Rep Executes Visit

**Actor:** Sales Rep
**Precondition:** At least one approved assignment exists for today

1. Rep opens /app (Home page)
2. Home shows today's approved assignments sorted by sort_order
3. Rep taps an assignment → VisitFlow opens
4. GPS check-in (geofence verified)
5. Rep fills visit report (summary, feedback, photos)
6. Rep submits report
7. VisitReportService::submit() closes visit
8. **Auto-transition:** assignment status → `completed`
9. Rep returns to Home, completed assignment no longer in pending list

**Edge cases:**

- Rep offline → report queued, syncs later, auto-transition happens on sync
- Assignment has no linked visit → null safe operator, no crash
- Multiple visits for same assignment → last report submission triggers transition

---

## Journey 4: Admin Views ROI

**Actor:** Admin/Manager
**Precondition:** Dashboard is loaded

1. Admin sees RepRoiWidget on dashboard
2. Default period: this month
3. Widget shows table: rep | revenue | expenses | ROI %
4. Admin changes period selector to "Last Month"
5. Widget recalculates and re-renders
6. Reps with 0 expenses show "N/A" for ROI
7. Negative ROI shows red badge, positive shows green

**Edge cases:**

- No invoices this period → revenue = 0, ROI = -100% (all expenses, no revenue)
- No expenses this period → ROI = N/A
- Rep with no activity → not shown in widget
