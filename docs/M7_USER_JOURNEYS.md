# Milestone 7: Competitor Gap Closure — User Journeys

**Date:** 2026-08-24  
**Status:** Draft

---

## Journey 1: Rep Daily Workflow with Calendar

**Actor**: Field Rep  
**Goal**: See today's schedule and plan the day

### Happy Path

1. Rep opens PWA → lands on Home
2. Taps "Calendar" in navigation
3. Sees current month with dot indicators on days with visits/todos
4. Taps today's date → day detail expands below
5. Sees:
   - 3 planned visits (blue dots)
   - 2 todos (orange dots)
6. Taps first visit → navigates to customer visit page
7. Completes visit → returns to Calendar
8. Sees visit now shows green checkmark

### Edge Cases

- **No visits for month**: Calendar renders empty, "No items for this month" message
- **More than 3 items on a day**: Shows "+N" badge instead of dots
- **Network offline**: Shows cached calendar data, retry button
- **Future month selected**: Calendar navigates but shows "No data" for months without assignments

### Acceptance Evidence

- [ ] Calendar renders with correct month/year
- [ ] Dot indicators show on correct days
- [ ] Day detail expands on tap
- [ ] Visit detail navigation works
- [ ] Empty state message appears

---

## Journey 2: Rep Creates and Completes Todo

**Actor**: Field Rep  
**Goal**: Track a task that needs to be done today

### Happy Path

1. Rep opens PWA → navigates to Todos
2. Sees "New" tab active, empty list
3. Taps "Add Todo"
4. Fills form:
   - Title: "Follow up with Mohammed about order"
   - Priority: High
   - Due Date: Today
5. Taps "Save"
6. Todo appears in "New" tab
7. Later, taps checkbox next to todo
8. Confirmation: "Mark as done?"
9. Taps "Yes"
10. Todo moves to "Done" tab

### Edge Cases

- **Title too long**: Validation error "Title must be 255 characters or less"
- **Past due date**: Warning "Due date is in the past" but allows save
- **No todos**: Empty state "No todos yet, create one!"
- **Network offline**: Todo queued, syncs when online

### Acceptance Evidence

- [ ] Todo created successfully
- [ ] Appears in "New" tab immediately
- [ ] Completion moves to "Done" tab
- [ ] Validation errors show correctly
- [ ] Empty state appears

---

## Journey 3: Manager Reviews Performance Dashboard

**Actor**: Sales Manager  
**Goal**: Check team performance for the month

### Happy Path

1. Manager opens PWA → navigates to Performance
2. Sees "Overview" tab active
3. Period shows current month
4. Sees metric cards:
   - Coverage: 85%
   - Frequency: 4.2
   - Call Rate: 0.8
   - Plan Achievement: 72%
5. Taps "Analysis" tab → sees trend charts
6. Taps "Daily" tab → sees day-by-day breakdown
7. Taps "Export CSV" → downloads file
8. Taps "Detailed" tab → sees per-rep breakdown

### Edge Cases

- **No data for period**: "No data available for this period" message
- **Single rep team**: Member filter shows only that rep
- **Export fails**: "Export failed, please try again" error
- **Large dataset**: Dashboard loads in <3 seconds

### Acceptance Evidence

- [ ] All 5 tabs render
- [ ] Metrics calculate correctly
- [ ] Period navigation works
- [ ] Member filter works
- [ ] CSV export downloads
- [ ] Load time <3 seconds

---

## Journey 4: Rep Records Non-Planned Visit

**Actor**: Field Rep  
**Goal**: Record a visit that wasn't in the schedule

### Happy Path

1. Rep is on Agenda page for today
2. Taps "Record Non-planned Visit" floating button
3. Quick form appears
4. Searches for customer "Ahmed Trading"
5. Selects customer
6. Selects purpose: "Follow-up"
7. Adds notes: "Discussed new product line"
8. Taps "Save"
9. Visit created with is_out_of_route = true
10. Appears in "Recorded Visits" section on Agenda
11. Also appears on Calendar for today

### Edge Cases

- **Customer not found**: "No customers match your search"
- **GPS unavailable**: Warning "Location not available" but allows save
- **No active work session**: "Please start your shift first"
- **Network offline**: Visit queued, syncs when online

### Acceptance Evidence

- [ ] Button visible on Agenda
- [ ] Customer search works
- [ ] Visit created with correct flags
- [ ] Appears in Agenda
- [ ] Appears on Calendar
- [ ] GPS captured

---

## Journey 5: Rep Creates Support Ticket

**Actor**: Field Rep  
**Goal**: Report an issue with a customer or product

### Happy Path

1. Rep navigates to Tickets
2. Taps "Add New Ticket"
3. Fills form:
   - Title: "Product delivery delay"
   - Description: "Customer hasn't received order from last week"
   - Customer: "Ahmed Trading"
   - Priority: High
4. Taps "Save"
5. Ticket created with status "جديد" (New)
6. Appears in Kanban view under "New" column

### Edge Cases

- **No description**: Validation error "Description is required"
- **Customer not linked**: Ticket created without customer association
- **Network offline**: Ticket queued, syncs when online

### Acceptance Evidence

- [ ] Ticket created successfully
- [ ] Status is "جديد"
- [ ] Appears in list/kanban
- [ ] Validation errors show correctly

---

## Journey 6: Manager Approves Request

**Actor**: Sales Manager  
**Goal**: Review and approve a rep's request

### Happy Path

1. Manager navigates to Requests
2. Sees pending requests in "New" column
3. Taps request "Discount approval for Ahmed Trading"
4. Reviews details:
   - Type: Discount
   - Title: "10% discount for bulk order"
   - Description: "Customer ordering 500+ units"
5. Taps "Approve"
6. Adds notes: "Approved for bulk order"
7. Status changes to "تم الموافقة" (Approved)
8. Request moves to "Approved" column

### Edge Cases

- **No pending requests**: Empty state "No pending requests"
- **Reject instead**: Taps "Reject", must provide reason
- **Already approved**: Cannot re-approve
- **Network offline**: Approval queued, syncs when online

### Acceptance Evidence

- [ ] Request visible to manager
- [ ] Approval works
- [ ] Rejection requires reason
- [ ] Status transitions correctly
- [ ] Audit trail created

---

## Journey 7: Rep Logs Phone Call

**Actor**: Field Rep  
**Goal**: Record a phone call with a customer

### Happy Path

1. Rep navigates to customer "Ahmed Trading"
2. Taps "Log Call"
3. Form appears:
   - Contact: "Mohammed Ahmed" (selected from dropdown)
   - Duration: Timer starts automatically
   - Direction: Outbound
   - Outcome: Reached
   - Notes: "Discussed payment schedule"
4. Taps "Save"
5. Call logged, appears in customer's call history

### Edge Cases

- **No contacts**: "No contacts available for this customer"
- **Timer not stopped**: Duration defaults to manual input
- **Network offline**: Call queued, syncs when online

### Acceptance Evidence

- [ ] Form appears on customer page
- [ ] Contact dropdown populated
- [ ] Duration recorded
- [ ] Call appears in history
- [ ] Performance dashboard call rate updates

---

## Journey 8: Manager Exports Contacts

**Actor**: Sales Manager  
**Goal**: Get a list of all customers for offline use

### Happy Path

1. Manager navigates to Contacts
2. Sees table view with all customers
3. Applies filter: Status = Active
4. Taps "Export Data"
5. CSV downloads with columns:
   - Name, Status, Area, Address, Mobile, Class
6. Opens in Excel

### Edge Cases

- **No customers**: Empty state "No customers to export"
- **Export fails**: "Export failed, please try again"
- **Large dataset**: Export takes time, shows loading indicator

### Acceptance Evidence

- [ ] Export button visible
- [ ] CSV downloads correctly
- [ ] Columns match specification
- [ ] Filtered data exported

---

## Journey 9: Manager Views Customer Summary

**Actor**: Sales Manager  
**Goal**: See customer analytics and identify issues

### Happy Path

1. Manager navigates to Reports → Customers
2. Sees metrics cards:
   - Total customers: 150
   - Active: 120, Inactive: 30
   - New this month: 8
   - Overdue payments: 5
3. Sees customer table sorted by last visit date
4. Taps column header "Total Orders" → sorts by orders
5. Filters by area "Riyadh"
6. Taps "Export" → downloads CSV

### Edge Cases

- **No data**: "No customer data available"
- **Filter returns empty**: "No customers match your filters"
- **Export fails**: Error message with retry

### Acceptance Evidence

- [ ] Metrics calculate correctly
- [ ] Table shows correct data
- [ ] Sorting works
- [ ] Filtering works
- [ ] Export downloads

---

## Journey 10: Admin Manages Tickets (Filament)

**Actor**: Admin  
**Goal**: Assign and track support tickets

### Happy Path

1. Admin opens Filament admin panel
2. Navigates to Tickets resource
3. Sees all tickets with filters
4. Taps ticket "Product delivery delay"
5. Assigns to "Sales Manager Ahmed"
6. Changes status to "قيد التنفيذ" (In Progress)
7. Saves → audit trail entry created

### Edge Cases

- **No tickets**: Empty table
- **Invalid assignment**: "User not found" error
- **Status transition invalid**: Error message

### Acceptance Evidence

- [ ] Tickets list shows all
- [ ] Assignment works
- [ ] Status change works
- [ ] Audit trail created
- [ ] RTL support in admin

---

## Cross-Journey Concerns

### Offline Behavior

All create/update operations queue to `sync_queue` when offline:

- Todos: create, complete
- Tickets: create, status change
- Requests: create
- Calls: create
- Non-planned visits: create

Sync follows existing visit sync pattern with:

- Dependency ordering
- Idempotency keys
- Conflict resolution

### RTL/LTR Switching

All journeys must work in both:

- Arabic RTL: week starts Sunday, text right-aligned, numbers Eastern Arabic
- English LTR: week starts Monday, text left-aligned, numbers Western Arabic

### Performance

- Calendar: <1s load for month view
- Performance dashboard: <3s load
- Todos/Tickets/Requests: <1s for list load
- Calls: <500ms for log operation
