# Jawla — User Acceptance Testing Checklist

## Pre-UAT Setup

- [ ] Production deployment verified healthy (`GET /up` returns 200)
- [ ] Demo data seeded (JAWLA_MODE=demo)
- [ ] Test accounts created:
  - Admin: `admin@jawla.test` / password: `12356789`
  - Manager: `manager@jawla.test` / password: `12356789`
  - Rep: `rep@jawla.test` / password: `12356789`
  - Warehouse: `warehouse@jawla.test` / password: `12356789`
  - Accounts: `accounts@jawla.test` / password: `12356789`
- [ ] PWA installed on test device (Chrome → Install App)
- [ ] GPS enabled on mobile device

---

## UAT Test Cases

### 1. Authentication & Access Control

| #   | Test Case                 | Expected Result                   | Pass/Fail |
| --- | ------------------------- | --------------------------------- | --------- |
| 1.1 | Login as admin            | Redirects to Filament admin panel |           |
| 1.2 | Login as rep              | Redirects to /app (PWA)           |           |
| 1.3 | Login with wrong password | Shows error message               |           |
| 1.4 | Access /app as admin      | Redirects to admin panel          |           |
| 1.5 | Access /admin as rep      | Access denied                     |           |
| 1.6 | Language switch (AR/EN)   | UI switches correctly             |           |

### 2. Visit Management

| #   | Test Case                            | Expected Result                         | Pass/Fail |
| --- | ------------------------------------ | --------------------------------------- | --------- |
| 2.1 | View today's visits on home page     | Visits listed with customer names       |           |
| 2.2 | Check-in to a visit                  | GPS captured, status updates            |           |
| 2.3 | Complete a visit report              | Report saved with summary               |           |
| 2.4 | View visit history                   | Past visits listed correctly            |           |
| 2.5 | Record non-planned visit from agenda | Visit created with is_out_of_route flag |           |

### 3. Sales & Invoicing

| #   | Test Case                           | Expected Result                    | Pass/Fail |
| --- | ----------------------------------- | ---------------------------------- | --------- |
| 3.1 | Create invoice from customer page   | Invoice created with correct items |           |
| 3.2 | Apply pricing (product + quantity)  | Correct price calculated           |           |
| 3.3 | Invoice deducts van stock           | Van stock decreases                |           |
| 3.4 | Invoice increments customer balance | Customer balance increases         |           |
| 3.5 | Cancel invoice                      | Stock restored, balance reversed   |           |

### 4. Payments & Collections

| #   | Test Case                           | Expected Result                    | Pass/Fail |
| --- | ----------------------------------- | ---------------------------------- | --------- |
| 4.1 | Collect cash payment                | Payment recorded, cashbox credited |           |
| 4.2 | Payment reduces invoice remaining   | Invoice status updates             |           |
| 4.3 | Overpayment creates customer credit | Credit amount recorded             |           |
| 4.4 | Cash reconciliation                 | Variance calculated correctly      |           |

### 5. Inventory

| #   | Test Case                   | Expected Result                   | Pass/Fail |
| --- | --------------------------- | --------------------------------- | --------- |
| 5.1 | View van stock levels       | Current stock shown               |           |
| 5.2 | Stock transfer between vans | Stock moves correctly             |           |
| 5.3 | Stock count session         | Count recorded, variance flagged  |           |
| 5.4 | Low stock alert triggered   | Alarm raised when below threshold |           |

### 6. M7 Features

| #    | Test Case                   | Expected Result                     | Pass/Fail |
| ---- | --------------------------- | ----------------------------------- | --------- |
| 6.1  | Create a todo               | Todo appears in list                |           |
| 6.2  | Complete a todo             | Moves to "Done" tab                 |           |
| 6.3  | Create a ticket             | Ticket created with status "New"    |           |
| 6.4  | Drag ticket in kanban view  | Status updates on drop              |           |
| 6.5  | Create a request            | Request submitted for approval      |           |
| 6.6  | Approve a request (manager) | Status changes to Approved          |           |
| 6.7  | Log a phone call            | Call recorded with duration         |           |
| 6.8  | View calendar               | Visits/todos shown on correct dates |           |
| 6.9  | View performance dashboard  | Charts render with data             |           |
| 6.10 | View customer summary       | Metrics and table display           |           |
| 6.11 | Export customer CSV         | CSV downloads correctly             |           |

### 7. Offline & Sync

| #   | Test Case                  | Expected Result               | Pass/Fail |
| --- | -------------------------- | ----------------------------- | --------- |
| 7.1 | Go offline, create invoice | Queued in sync queue          |           |
| 7.2 | Come back online           | Sync completes, invoice saved |           |
| 7.3 | View sync queue            | Pending/failed items shown    |           |

### 8. PWA & Mobile

| #   | Test Case               | Expected Result                   | Pass/Fail |
| --- | ----------------------- | --------------------------------- | --------- |
| 8.1 | Install PWA from Chrome | App icon appears on home screen   |           |
| 8.2 | Open PWA                | Correct logo and theme shown      |           |
| 8.3 | Tab bar navigation      | All tabs work correctly           |           |
| 8.4 | RTL layout              | Arabic text renders right-to-left |           |
| 8.5 | Dark mode toggle        | Theme switches correctly          |           |

---

## UAT Sign-Off

| Role          | Name | Date | Signature |
| ------------- | ---- | ---- | --------- |
| Product Owner |      |      |           |
| QA Lead       |      |      |           |
| Dev Lead      |      |      |           |

**Overall Result**: ☐ PASS ☐ CONDITIONAL PASS ☐ FAIL

**Notes**:
