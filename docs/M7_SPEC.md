# Milestone 7: Competitor Gap Closure — Functional Specification

**Date:** 2026-08-24  
**Status:** Draft

---

## 7.1 Calendar View

### Actors & Preconditions

- **Actor**: Field Rep, Sales Manager
- **Precondition**: User authenticated, has assigned visits/todos

### Behavior

#### Monthly Calendar Grid

- 7-column grid (Sunday → Saturday for Arabic, Monday → Sunday for English)
- 5-6 rows depending on month
- Each cell shows:
  - Day number
  - Dot indicators: blue (visits), orange (todos), red (tickets)
  - Count badge if >3 items

#### Navigation

- **Prev/Next month** buttons
- **Today** button returns to current month
- URL params: `?from=2026-08-01&to=2026-08-31`

#### Day Detail

- Tap day cell → expands below calendar
- Shows list of items for that day:
  - Visits: customer name, time, status badge
  - Todos: title, priority badge, status
  - Tickets: title, status badge
- Tap item → navigates to detail page

#### Filters

- Toggle: Visits / Todos / Tickets (all on by default)
- Filter persists in URL params

### States

| State     | Visual      | Action       |
| --------- | ----------- | ------------ |
| No items  | Empty cell  | No indicator |
| 1-3 items | Dots        | Show dots    |
| 4+ items  | Count badge | Show "+N"    |

### Validation

- Date range must be valid (from ≤ to)
- Month navigation stays within reasonable bounds (±2 years)

### Error States

- Network error → show cached data, retry button
- Empty state → "No items for this month"

### Acceptance Criteria

- [ ] Calendar renders correct month/year
- [ ] Visits appear on correct scheduled_date
- [ ] Todos appear on correct due_date
- [ ] Tickets appear on correct created_at date
- [ ] Prev/Next navigation works
- [ ] Today button returns to current month
- [ ] Day detail shows correct items
- [ ] Filters toggle correctly
- [ ] RTL: week starts Sunday
- [ ] LTR: week starts Monday
- [ ] URL params deep-link correctly

---

## 7.2 Todos/Tasks System

### Actors & Preconditions

- **Actor**: Field Rep (create/complete), Sales Manager (view all), Admin (CRUD)
- **Precondition**: User authenticated

### Behavior

#### Create Todo

1. Tap "Add Todo" button
2. Form appears:
   - Title (required, max 255 chars)
   - Description (optional, max 1000 chars)
   - Priority (dropdown: low/medium/high, default: medium)
   - Due Date (date picker, default: today)
3. Submit → todo created, appears in "New" tab

#### Complete Todo

1. Tap checkbox next to todo
2. Confirmation: "Mark as done?"
3. Confirm → status = done, completed_at = now
4. Moves to "Done" tab

#### Filter & Search

- **Tabs**: New / Done
- **Search**: By title (debounced, 300ms)
- **Filter**: By priority (low/medium/high), date range

#### Edit Todo (Admin/Manager only)

- Tap todo → detail view
- Edit fields
- Save → audit trail entry

### States

```
NEW → DONE
```

| Status | Color | Icon      |
| ------ | ----- | --------- |
| New    | Blue  | Circle    |
| Done   | Green | Checkmark |

### Validation

- Title: required, 1-255 chars
- Description: optional, 0-1000 chars
- Priority: must be low/medium/high
- Due Date: must be valid date, not in the past (warning, not block)

### Data Changes

```sql
INSERT INTO todos (id, company_id, user_id, title, description, priority, status, due_date, is_active, created_at, updated_at)
VALUES (UUID(), ?, ?, ?, ?, ?, 'new', ?, true, NOW(), NOW());

UPDATE todos SET status = 'done', completed_at = NOW(), updated_at = NOW() WHERE id = ?;
```

### Acceptance Criteria

- [ ] Can create todo with title, priority, due date
- [ ] Todo appears in "New" tab immediately
- [ ] Can mark todo as done → moves to "Done" tab
- [ ] Search filters by title
- [ ] Priority filter works
- [ ] Date range filter works
- [ ] RTL layout correct
- [ ] Admin can CRUD all todos
- [ ] Audit trail created on changes

---

## 7.3 Performance Dashboard

### Actors & Preconditions

- **Actor**: Sales Manager, Admin
- **Precondition**: User authenticated, has view_reports permission
- **Precondition**: Visit and invoice data exists for period

### Behavior

#### Overview Tab

- Period selector: month nav (prev/next)
- Members filter: select rep or "All"
- Metric cards:
  - **Coverage %**: visits_completed / visits_planned × 100
  - **Frequency**: total_visits / working_days
  - **Call Rate**: total_visits / total_customers_assigned
  - **Plan Achievement %**: orders_completed / orders_target × 100
- Summary row:
  - Working days
  - Total expenses
  - Total visits
  - Detailing count

#### Analysis Tab

- Trend charts (line) for key metrics over time
- Comparison: this month vs last month
- Metrics: Coverage, Frequency, Call Rate, Plan Achievement

#### Daily Tab

- Day-by-day breakdown table
- Columns: Date, Visits Planned, Visits Completed, Coverage %, Orders Count, Amount
- Sort by any column
- Export to CSV

#### Detailed Tab

- Per-rep breakdown with all metrics
- Sortable by any metric
- Export to CSV

#### Coverage Tab

- Map view (placeholder — no real map integration in M7)
- Table: Territory, Planned Visits, Completed Visits, Coverage %
- Coverage heatmap data (CSS-based, not real map)

### Metric Calculations

```sql
-- Coverage
SELECT
  COUNT(CASE WHEN v.status = 'completed' THEN 1 END) * 100.0 /
  NULLIF(COUNT(dva.id), 0) as coverage_pct
FROM daily_visit_assignments dva
LEFT JOIN visits v ON v.daily_visit_assignment_id = dva.id
WHERE dva.scheduled_date BETWEEN ? AND ?
  AND dva.user_id = ?;

-- Frequency
SELECT
  COUNT(v.id) * 1.0 / NULLIF(COUNT(DISTINCT v.actual_arrival_time::date), 0) as frequency
FROM visits v
WHERE v.user_id = ?
  AND v.actual_arrival_time BETWEEN ? AND ?;

-- Call Rate
SELECT
  COUNT(v.id) * 1.0 / NULLIF(COUNT(DISTINCT c.id), 0) as call_rate
FROM visits v
CROSS JOIN customers c
WHERE v.user_id = ?
  AND v.actual_arrival_time BETWEEN ? AND ?;

-- Plan Achievement
SELECT
  COUNT(CASE WHEN i.status IN ('issued', 'paid') THEN 1 END) * 100.0 /
  NULLIF(orders_target, 0) as plan_achievement_pct
FROM invoices i
WHERE i.user_id = ?
  AND i.issue_date BETWEEN ? AND ?;
```

### API Endpoints

```
GET /app/performance/overview?period=2026-08&user_id=all
GET /app/performance/analysis?period=2026-08
GET /app/performance/daily?period=2026-08&user_id=all
GET /app/performance/detailed?period=2026-08
GET /app/performance/coverage?period=2026-08&user_id=all
GET /app/performance/export/daily?period=2026-08&format=csv
GET /app/performance/export/detailed?period=2026-08&format=csv
```

### Validation

- Period must be valid YYYY-MM format
- User must have permission to view reports
- Date range must not exceed 12 months

### Error States

- No data for period → "No data available for this period"
- Permission denied → "You don't have permission to view this"
- Export failure → "Export failed, please try again"

### Acceptance Criteria

- [ ] All 5 tabs render correctly
- [ ] Metrics calculate correctly from underlying data
- [ ] Period navigation works
- [ ] Member filter works
- [ ] CSV export works for Daily and Detailed tabs
- [ ] RTL support works
- [ ] Charts render (CSS-based or simple SVG)
- [ ] Load time <3 seconds
- [ ] Empty state shows correctly

---

## 7.4 Agenda View

### Actors & Preconditions

- **Actor**: Field Rep
- **Precondition**: User authenticated, has active work session

### Behavior

#### Date Navigation

- Date selector: prev/next day + Today button
- Current date highlighted

#### Sections

1. **Planned Visits** (from `daily_visit_assignments`)
   - Customer name, scheduled time, status badge
   - Tap → visit detail

2. **Recorded Visits** (from `visits` where status = completed)
   - Customer name, actual time, duration
   - Tap → visit report

3. **Todos** (from `todos` where due_date = selected date)
   - Title, priority badge, status
   - Tap → todo detail

#### Non-Planned Visit Button

- Floating action button: "Record Non-planned Visit"
- Opens quick form (see 7.7)

### States

| Section         | Empty State                      |
| --------------- | -------------------------------- |
| Planned Visits  | "No planned visits for this day" |
| Recorded Visits | "No recorded visits yet"         |
| Todos           | "No todos for this day"          |

### Validation

- Date must be valid
- Work session must be active for non-planned visit recording

### Acceptance Criteria

- [ ] Agenda shows correct date's items
- [ ] Planned visits listed with customer names
- [ ] Completed visits shown with status
- [ ] Todos due today shown
- [ ] Navigation between days works
- [ ] Today button works
- [ ] Non-planned visit button visible
- [ ] RTL layout correct
- [ ] Empty states show correctly

---

## 7.5 Tickets System

### Actors & Preconditions

- **Actor**: Field Rep (create), Sales Manager (assign/close), Admin (full CRUD)
- **Precondition**: User authenticated

### Behavior

#### Create Ticket

1. Tap "Add New Ticket"
2. Form:
   - Title (required, max 255 chars)
   - Description (required, max 2000 chars)
   - Customer (optional, searchable dropdown)
   - Priority (low/medium/high, default: medium)
3. Submit → status = جديد (new)

#### Status Workflow

```
جديد (new) → قيد التنفيذ (in_progress) → اكتمل (completed)
    ↓              ↓
  ملغي (cancelled)  معطل (disabled)
```

| Status      | Arabic      | Color  | Allowed Transitions        |
| ----------- | ----------- | ------ | -------------------------- |
| New         | جديد        | Blue   | → In Progress, → Cancelled |
| In Progress | قيد التنفيذ | Yellow | → Completed, → Disabled    |
| Completed   | اكتمل       | Green  | — (terminal)               |
| Cancelled   | ملغي        | Gray   | — (terminal)               |
| Disabled    | معطل        | Red    | — (terminal)               |

#### Views

- **Table view**: Columns: Title, Customer, Status, Priority, Created, Actions
- **Kanban view**: Columns by status (New, In Progress, Completed)
- **Toggle button** to switch views

#### Search & Filter

- Search: by title
- Filter: by status, priority, date range

### States

See Status Workflow above.

### Validation

- Title: required, 1-255 chars
- Description: required, 1-2000 chars
- Customer: optional, must exist if provided
- Priority: must be low/medium/high
- Status transitions: only allowed per workflow

### Data Changes

```sql
INSERT INTO tickets (id, company_id, user_id, customer_id, title, description, status, priority, is_active, created_at, updated_at)
VALUES (UUID(), ?, ?, ?, ?, ?, 'new', ?, true, NOW(), NOW());

UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?;
-- Insert status history
INSERT INTO ticket_status_history (id, ticket_id, old_status, new_status, changed_by, changed_at)
VALUES (UUID(), ?, ?, ?, ?, NOW());
```

### Acceptance Criteria

- [ ] Can create ticket with title, description, priority
- [ ] Ticket appears in list immediately
- [ ] Can change status through workflow
- [ ] Table/Kanban toggle works
- [ ] Search and filter work
- [ ] Admin can assign tickets
- [ ] Status history tracked
- [ ] RTL support works
- [ ] Empty states show correctly

---

## 7.6 Requests System

### Actors & Preconditions

- **Actor**: Field Rep (create), Sales Manager (approve/reject), Admin (full CRUD)
- **Precondition**: User authenticated

### Behavior

#### Create Request

1. Tap "Add New Request"
2. Form:
   - Type (dropdown: discount/leave/price_override/other)
   - Title (required, max 255 chars)
   - Description (required, max 2000 chars)
3. Submit → status = جديد (new)

#### Status Workflow

```
جديد (new) → تم الموافقة (approved) → تم (done)
    ↓
  تم الرفض (rejected)
```

| Status   | Arabic      | Color  | Allowed Transitions    |
| -------- | ----------- | ------ | ---------------------- |
| New      | جديد        | Blue   | → Approved, → Rejected |
| Approved | تم الموافقة | Yellow | → Done                 |
| Rejected | تم الرفض    | Red    | — (terminal)           |
| Done     | تم          | Green  | — (terminal)           |

#### Manager Actions

- View pending requests
- Approve with notes (optional)
- Reject with reason (required)

#### Views

- **Table view**: Columns: Type, Title, Status, Created, Actions
- **Kanban view**: Columns by status (New, Approved, Done, Rejected)
- **Toggle button** to switch views

#### Search & Filter

- Search: by title
- Filter: by status, type, date range

### States

See Status Workflow above.

### Validation

- Type: must be discount/leave/price_override/other
- Title: required, 1-255 chars
- Description: required, 1-2000 chars
- Status transitions: only allowed per workflow
- Rejection reason: required when rejecting

### Data Changes

```sql
INSERT INTO requests (id, company_id, user_id, type, title, description, status, is_active, created_at, updated_at)
VALUES (UUID(), ?, ?, ?, ?, ?, 'new', true, NOW(), NOW());

UPDATE requests SET status = ?, reviewed_by = ?, reviewed_at = NOW(), review_notes = ?, updated_at = NOW() WHERE id = ?;
```

### Acceptance Criteria

- [ ] Can create request with type, title, description
- [ ] Request appears in list immediately
- [ ] Manager can approve/reject
- [ ] Rejection requires reason
- [ ] Table/Kanban toggle works
- [ ] Search and filter work
- [ ] RTL support works
- [ ] Empty states show correctly

---

## 7.7 Non-Planned Visit Recording

### Actors & Preconditions

- **Actor**: Field Rep
- **Precondition**: User authenticated, active work session

### Behavior

#### Quick Form

1. Tap "Record Non-planned Visit" on Agenda page
2. Form:
   - Customer (searchable dropdown, required)
   - Purpose (dropdown: sales/service/follow_up/other, default: other)
   - Notes (optional, max 500 chars)
3. Submit → creates visit record

#### Data Created

```sql
INSERT INTO visits (
  id, company_id, user_id, customer_id, work_session_id,
  purpose, status, is_out_of_route, scheduled_date,
  actual_arrival_time, checkin_latitude, checkin_longitude,
  notes, is_active, created_at, updated_at
) VALUES (
  UUID(), ?, ?, ?, ?,
  ?, 'completed', true, CURDATE(),
  NOW(), ?, ?,
  ?, true, NOW(), NOW()
);
```

### Validation

- Customer: required, must exist
- Purpose: must be sales/service/follow_up/other
- GPS: captured at submission (required)
- Work session: must be active

### Acceptance Criteria

- [ ] Button visible on Agenda page
- [ ] Can select customer and submit
- [ ] Visit created with is_out_of_route = true
- [ ] Visit created with status = completed
- [ ] GPS captured at submission
- [ ] Appears in Agenda under "Recorded Visits"
- [ ] Appears on Calendar
- [ ] RTL support works

---

## 7.8 Contacts Views Enhancement

### Actors & Preconditions

- **Actor**: Field Rep, Sales Manager, Admin
- **Precondition**: User authenticated

### Behavior

#### View Toggle

- **Table view** (default): Existing customer list
- **Kanban view**: Grouped by customer status (active/inactive/pending)
- **Grid view**: Card layout with name, phone, status badge

#### Search & Filter

- Search: by name, phone (debounced, 300ms)
- Filter: by status, area, class

#### Export

- "Export Data" button
- Downloads CSV with columns:
  - Name, Status, Area, Address, Mobile, Class

### Acceptance Criteria

- [ ] View toggle works (3 modes)
- [ ] Search works with debounce
- [ ] Filters work
- [ ] CSV downloads correctly
- [ ] Kanban groups by status
- [ ] Grid shows cards
- [ ] RTL support works

---

## 7.9 Calls Tracking

### Actors & Preconditions

- **Actor**: Field Rep (log), Sales Manager (view), Admin (full CRUD)
- **Precondition**: User authenticated, customer exists

### Behavior

#### Log Call

1. On customer detail page, tap "Log Call"
2. Form:
   - Contact (dropdown from customer contacts, required)
   - Duration (auto-timer or manual input in seconds)
   - Direction (inbound/outbound, default: outbound)
   - Outcome (reached/no_answer/busy/left_voicemail)
   - Notes (optional, max 500 chars)
3. Submit → call logged

#### Call History

- Listed on customer detail page (recent 10)
- Full list with search/filter

#### Admin Views

- Filament resource: list/filter/export calls
- Integration with Performance Dashboard for call rate metric

### States

| Outcome        | Description                 |
| -------------- | --------------------------- |
| Reached        | Customer answered and spoke |
| No Answer      | Phone rang but no answer    |
| Busy           | Line was busy               |
| Left Voicemail | Voicemail message left      |

### Validation

- Contact: required, must belong to customer
- Duration: required, must be >0 seconds
- Direction: must be inbound/outbound
- Outcome: must be reached/no_answer/busy/left_voicemail

### Acceptance Criteria

- [ ] Can log a call from customer page
- [ ] Call appears in customer's call history
- [ ] Duration recorded correctly
- [ ] Outcome options work
- [ ] Admin can view/filter calls
- [ ] Call rate metric updates in Performance dashboard
- [ ] RTL support works

---

## 7.10 Customers Summary Report

### Actors & Preconditions

- **Actor**: Sales Manager, Admin
- **Precondition**: User authenticated, has view_reports permission

### Behavior

#### Metrics Cards

- Total customers (active/inactive)
- New customers this month
- Visit frequency per customer
- Top 10 customers by order value
- Customers with overdue payments

#### Customer Table

- Columns: Name, Last Visit Date, Total Orders, Balance, Status
- Sort by any column
- Filter by status, area, date range

#### Export

- "Export" button → CSV download

### Validation

- Date range: must be valid
- Filter values: must be valid enum values

### Acceptance Criteria

- [ ] Page renders with correct metrics
- [ ] Table data is accurate
- [ ] Sorting works
- [ ] Filtering works
- [ ] CSV export works
- [ ] RTL support works
- [ ] Empty state shows correctly

---

## Cross-Cutting Concerns

### Localization

- All new strings in `lang/en/M7.php` and `lang/ar/M7.php`
- Dates: Arabic → Hijri display, English → Gregorian
- Numbers: Arabic → Eastern Arabic numerals, English → Western
- RTL layout for all new pages

### Offline Support

- Todos: queue create/complete operations
- Tickets: queue create/status change operations
- Requests: queue create operations
- Calls: queue create operations
- Non-planned visits: queue create operations
- Follow existing `sync_queue` pattern from visits

### Performance

- Calendar: load only current month data
- Performance dashboard: cache computed metrics for 5 minutes
- Todos/Tickets/Requests: paginate lists (20 per page)
- Calls: paginate history (10 per page on customer detail)

### Security

- All new routes behind auth middleware
- Company scoping on all new tables
- Permission checks:
  - `todos.create`, `todos.view`, `todos.complete`
  - `tickets.create`, `tickets.view`, `tickets.assign`, `tickets.close`
  - `requests.create`, `requests.view`, `requests.approve`
  - `calls.create`, `calls.view`
  - `performance.view`
  - `customers.export`
