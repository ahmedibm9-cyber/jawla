# Milestone 7: Competitor Gap Closure — Task Breakdown

**Date:** 2026-08-24  
**Status:** Draft

---

## Execution Strategy

**Parallel tracks**: Backend (B) and Frontend (F) can run concurrently after database setup.  
**Vertical slices**: Each task delivers a complete, testable user journey.  
**Dependencies**: respect ordering within tracks; cross-track dependencies marked.

---

## Phase 1: Database Foundation (Sequential)

### Task 1.1: Create Migration Files

**Track**: Backend  
**Dependencies**: None  
**Effort**: 1 day

**Objective**: Create all 6 migration files for M7 tables.

**Files to create**:

- `database/migrations/xxxx_create_todos_table.php`
- `database/migrations/xxxx_create_tickets_table.php`
- `database/migrations/xxxx_create_ticket_status_history_table.php`
- `database/migrations/xxxx_create_requests_table.php`
- `database/migrations/xxxx_create_calls_table.php`
- `database/migrations/xxxx_add_is_out_of_route_to_visits_table.php`

**Acceptance Criteria**:

- [ ] All migrations run successfully
- [ ] Tables created with correct columns and types
- [ ] Indexes created correctly
- [ ] Foreign keys enforced
- [ ] `php artisan migrate` passes

**Verification**:

```bash
php artisan migrate
php artisan tinker --execute="echo Schema::getColumnType('todos', 'priority')"
```

---

### Task 1.2: Create Eloquent Models

**Track**: Backend  
**Dependencies**: 1.1  
**Effort**: 1 day

**Objective**: Create 4 new Eloquent models with relationships.

**Files to create**:

- `app/Models/Todo.php`
- `app/Models/Ticket.php`
- `app/Models/TicketStatusHistory.php`
- `app/Models/Request.php`
- `app/Models/Call.php`

**Acceptance Criteria**:

- [ ] All models created with correct fillable, casts, relationships
- [ ] Models use `HasUuid`, `SoftDeletes`, `BelongsToCompany` traits
- [ ] Relationship methods return correct types
- [ ] Helper methods (`complete()`, `transitionTo()`, `approve()`, `reject()`) work

**Verification**:

```bash
php artisan tinker
# Test each model can be instantiated
# Test relationships return correct query builders
```

---

### Task 1.3: Create Service Classes

**Track**: Backend  
**Dependencies**: 1.2  
**Effort**: 2 days

**Objective**: Create service classes for business logic.

**Files to create**:

- `app/Services/TodoService.php`
- `app/Services/TicketService.php`
- `app/Services/RequestService.php`
- `app/Services/CallService.php`
- `app/Services/PerformanceService.php`

**Services**:

#### TodoService

```php
class TodoService
{
    public function create(array $data): Todo;
    public function complete(Todo $todo): Todo;
    public function getForUser(User $user, array $filters): Collection;
}
```

#### TicketService

```php
class TicketService
{
    public function create(array $data): Ticket;
    public function transitionTo(Ticket $ticket, string $status, User $user, ?string $notes): Ticket;
    public function assign(Ticket $ticket, User $assignee): Ticket;
    public function getForCompany(Company $company, array $filters): Collection;
}
```

#### RequestService

```php
class RequestService
{
    public function create(array $data): Request;
    public function approve(Request $request, User $reviewer, ?string $notes): Request;
    public function reject(Request $request, User $reviewer, string $reason): Request;
    public function getForCompany(Company $company, array $filters): Collection;
}
```

#### CallService

```php
class CallService
{
    public function create(array $data): Call;
    public function getForCustomer(Customer $customer, int $limit): Collection;
}
```

#### PerformanceService

```php
class PerformanceService
{
    public function getOverview(string $period, ?string $userId): array;
    public function getAnalysis(string $period): array;
    public function getDaily(string $period, ?string $userId): Collection;
    public function getDetailed(string $period): Collection;
    public function getCoverage(string $period, ?string $userId): array;
}
```

**Acceptance Criteria**:

- [ ] All services created
- [ ] Services use `DB::transaction` for mutations
- [ ] Services dispatch events on state changes
- [ ] Services validate business rules

**Verification**:

```bash
php artisan tinker
# Test each service method with test data
```

---

## Phase 2: Backend API (Parallel with Frontend)

### Task 2.1: Todo API Endpoints

**Track**: Backend  
**Dependencies**: 1.3  
**Effort**: 1 day

**Objective**: Create Livewire components and routes for todos.

**Files to create**:

- `app/Livewire/TodoList.php`
- `app/Livewire/TodoForm.php`
- `resources/views/livewire/todo-list.blade.php`
- `resources/views/livewire/todo-form.blade.php`

**Routes**:

```
GET /app/todos → TodoList
GET /app/todos/create → TodoForm
POST /app/todos → TodoService@create
PUT /app/todos/{todo}/complete → TodoService@complete
```

**Acceptance Criteria**:

- [ ] Todo list page renders
- [ ] Create form renders
- [ ] Create todo works
- [ ] Complete todo works
- [ ] RTL layout correct
- [ ] Offline queuing works

**Verification**:

```bash
php artisan route:list --path=todos
# Test in browser with RTL enabled
```

---

### Task 2.2: Ticket API Endpoints

**Track**: Backend  
**Dependencies**: 1.3  
**Effort**: 1 day

**Objective**: Create Livewire components and routes for tickets.

**Files to create**:

- `app/Livewire/TicketList.php`
- `app/Livewire/TicketForm.php`
- `app/Livewire/TicketDetail.php`
- `resources/views/livewire/ticket-list.blade.php`
- `resources/views/livewire/ticket-form.blade.php`
- `resources/views/livewire/ticket-detail.blade.php`

**Routes**:

```
GET /app/tickets → TicketList
GET /app/tickets/create → TicketForm
POST /app/tickets → TicketService@create
GET /app/tickets/{ticket} → TicketDetail
PUT /app/tickets/{ticket}/status → TicketService@transitionTo
PUT /app/tickets/{ticket}/assign → TicketService@assign
```

**Acceptance Criteria**:

- [ ] Ticket list with table/kanban toggle renders
- [ ] Create form renders
- [ ] Status transitions work
- [ ] Assignment works
- [ ] RTL layout correct

---

### Task 2.3: Request API Endpoints

**Track**: Backend  
**Dependencies**: 1.3  
**Effort**: 1 day

**Objective**: Create Livewire components and routes for requests.

**Files to create**:

- `app/Livewire/RequestList.php`
- `app/Livewire/RequestForm.php`
- `app/Livewire/RequestDetail.php`
- `resources/views/livewire/request-list.blade.php`
- `resources/views/livewire/request-form.blade.php`
- `resources/views/livewire/request-detail.blade.php`

**Routes**:

```
GET /app/requests → RequestList
GET /app/requests/create → RequestForm
POST /app/requests → RequestService@create
GET /app/requests/{request} → RequestDetail
PUT /app/requests/{request}/approve → RequestService@approve
PUT /app/requests/{request}/reject → RequestService@reject
```

**Acceptance Criteria**:

- [ ] Request list with table/kanban toggle renders
- [ ] Create form renders
- [ ] Approve/reject works
- [ ] Rejection requires reason
- [ ] RTL layout correct

---

### Task 2.4: Call API Endpoints

**Track**: Backend  
**Dependencies**: 1.3  
**Effort**: 1 day

**Objective**: Create Livewire components and routes for calls.

**Files to create**:

- `app/Livewire/CallLog.php`
- `app/Livewire/CallHistory.php`
- `resources/views/livewire/call-log.blade.php`
- `resources/views/livewire/call-history.blade.php`

**Routes**:

```
POST /app/calls → CallService@create
GET /app/customers/{customer}/calls → CallHistory
```

**Acceptance Criteria**:

- [ ] Log call form renders on customer page
- [ ] Call logged successfully
- [ ] Call history shows on customer page
- [ ] Duration recorded correctly
- [ ] RTL layout correct

---

### Task 2.5: Performance Dashboard API

**Track**: Backend  
**Dependencies**: 1.3  
**Effort**: 2 days

**Objective**: Create Livewire components and routes for performance dashboard.

**Files to create**:

- `app/Livewire/Performance/Overview.php`
- `app/Livewire/Performance/Analysis.php`
- `app/Livewire/Performance/Daily.php`
- `app/Livewire/Performance/Detailed.php`
- `app/Livewire/Performance/Coverage.php`
- `resources/views/livewire/performance/overview.blade.php`
- `resources/views/livewire/performance/analysis.blade.php`
- `resources/views/livewire/performance/daily.blade.php`
- `resources/views/livewire/performance/detailed.blade.php`
- `resources/views/livewire/performance/coverage.blade.php`

**Routes**:

```
GET /app/performance → redirect to /app/performance/overview
GET /app/performance/overview → Overview
GET /app/performance/analysis → Analysis
GET /app/performance/daily → Daily
GET /app/performance/detailed → Detailed
GET /app/performance/coverage → Coverage
GET /app/performance/export/{type} → ExportController
```

**Acceptance Criteria**:

- [ ] All 5 tabs render
- [ ] Metrics calculate correctly
- [ ] Period navigation works
- [ ] Member filter works
- [ ] CSV export works
- [ ] RTL layout correct
- [ ] Load time <3 seconds

---

### Task 2.6: Calendar API

**Track**: Backend  
**Dependencies**: 1.3  
**Effort**: 1 day

**Objective**: Create Livewire component for calendar.

**Files to create**:

- `app/Livewire/Calendar.php`
- `resources/views/livewire/calendar.blade.php`

**Routes**：

```
GET /app/calendar → Calendar
```

**Acceptance Criteria**:

- [ ] Calendar renders correct month
- [ ] Visits appear on correct dates
- [ ] Todos appear on correct dates
- [ ] Tickets appear on correct dates
- [ ] Navigation works
- [ ] Day detail expands
- [ ] RTL: week starts Sunday

---

### Task 2.7: Agenda API

**Track**: Backend  
**Dependencies**: 2.6, 2.1  
**Effort**: 1 day

**Objective**: Create Livewire component for agenda.

**Files to create**:

- `app/Livewire/Agenda.php`
- `resources/views/livewire/agenda.blade.php`

**Routes**:

```
GET /app/agenda → Agenda
```

**Acceptance Criteria**:

- [ ] Agenda shows correct date's items
- [ ] Planned visits listed
- [ ] Recorded visits listed
- [ ] Todos listed
- [ ] Navigation works
- [ ] Non-planned visit button works

---

## Phase 3: Frontend (Parallel with Backend)

### Task 3.1: Todo UI Components

**Track**: Frontend  
**Dependencies**: None  
**Effort**: 1 day

**Objective**: Create Blade views for todos.

**Files to create**:

- `resources/views/livewire/todo-list.blade.php`
- `resources/views/livewire/todo-form.blade.php`

**Acceptance Criteria**:

- [ ] List view with tabs (New/Done)
- [ ] Create form with all fields
- [ ] Complete checkbox works
- [ ] Search and filter work
- [ ] RTL layout correct
- [ ] Empty states show

---

### Task 3.2: Ticket UI Components

**Track**: Frontend  
**Dependencies**: None  
**Effort**: 1 day

**Objective**: Create Blade views for tickets.

**Files to create**:

- `resources/views/livewire/ticket-list.blade.php`
- `resources/views/livewire/ticket-form.blade.php`
- `resources/views/livewire/ticket-detail.blade.php`

**Acceptance Criteria**:

- [ ] Table view renders
- [ ] Kanban view renders
- [ ] Toggle works
- [ ] Create form renders
- [ ] Status badges correct colors
- [ ] RTL layout correct

---

### Task 3.3: Request UI Components

**Track**: Frontend  
**Dependencies**: None  
**Effort**: 1 day

**Objective**: Create Blade views for requests.

**Files to create**:

- `resources/views/livewire/request-list.blade.php`
- `resources/views/livewire/request-form.blade.php`
- `resources/views/livewire/request-detail.blade.php`

**Acceptance Criteria**:

- [ ] Table view renders
- [ ] Kanban view renders
- [ ] Toggle works
- [ ] Create form renders
- [ ] Approve/reject buttons work
- [ ] RTL layout correct

---

### Task 3.4: Call UI Components

**Track**: Frontend  
**Dependencies**: None  
**Effort**: 0.5 days

**Objective**: Create Blade views for calls.

**Files to create**:

- `resources/views/livewire/call-log.blade.php`
- `resources/views/livewire/call-history.blade.php`

**Acceptance Criteria**:

- [ ] Log form renders on customer page
- [ ] History list renders
- [ ] Duration input works
- [ ] RTL layout correct

---

### Task 3.5: Performance Dashboard UI

**Track**: Frontend  
**Dependencies**: None  
**Effort**: 2 days

**Objective**: Create Blade views for performance dashboard.

**Files to create**:

- `resources/views/livewire/performance/overview.blade.php`
- `resources/views/livewire/performance/analysis.blade.php`
- `resources/views/livewire/performance/daily.blade.php`
- `resources/views/livewire/performance/detailed.blade.php`
- `resources/views/livewire/performance/coverage.blade.php`

**Acceptance Criteria**:

- [ ] Metric cards render with correct values
- [ ] Charts render (CSS-based or SVG)
- [ ] Tables render with correct columns
- [ ] Export button works
- [ ] RTL layout correct
- [ ] Period selector works
- [ ] Member filter works

---

### Task 3.6: Calendar UI

**Track**: Frontend  
**Dependencies**: None  
**Effort**: 1 day

**Objective**: Create Blade view for calendar.

**Files to create**:

- `resources/views/livewire/calendar.blade.php`

**Acceptance Criteria**:

- [ ] Calendar grid renders
- [ ] Day cells show dot indicators
- [ ] Day detail expands on tap
- [ ] Navigation buttons work
- [ ] RTL: week starts Sunday
- [ ] LTR: week starts Monday

---

### Task 3.7: Agenda UI

**Track**: Frontend  
**Dependencies**: None  
**Effort**: 1 day

**Objective**: Create Blade view for agenda.

**Files to create**:

- `resources/views/livewire/agenda.blade.php`

**Acceptance Criteria**:

- [ ] Date selector renders
- [ ] Planned visits section renders
- [ ] Recorded visits section renders
- [ ] Todos section renders
- [ ] Non-planned visit button renders
- [ ] RTL layout correct

---

## Phase 4: Admin Resources (Parallel)

### Task 4.1: Filament Todo Resource

**Track**: Backend  
**Dependencies**: 1.2  
**Effort**: 0.5 days

**Objective**: Create Filament resource for todo management.

**Files to create**:

- `app/Filament/Resources/TodoResource.php`
- `app/Filament/Resources/TodoResource/Pages/ListTodos.php`
- `app/Filament/Resources/TodoResource/Pages/CreateTodo.php`
- `app/Filament/Resources/TodoResource/Pages/EditTodo.php`

**Acceptance Criteria**:

- [ ] List page renders
- [ ] Create page renders
- [ ] Edit page renders
- [ ] Can CRUD todos
- [ ] RTL layout works

---

### Task 4.2: Filament Ticket Resource

**Track**: Backend  
**Dependencies**: 1.2  
**Effort**: 0.5 days

**Objective**: Create Filament resource for ticket management.

**Files to create**:

- `app/Filament/Resources/TicketResource.php`
- `app/Filament/Resources/TicketResource/Pages/ListTickets.php`
- `app/Filament/Resources/TicketResource/Pages/CreateTicket.php`
- `app/Filament/Resources/TicketResource/Pages/EditTicket.php`

**Acceptance Criteria**:

- [ ] List page renders
- [ ] Create page renders
- [ ] Edit page renders
- [ ] Can CRUD tickets
- [ ] Can assign tickets
- [ ] RTL layout works

---

### Task 4.3: Filament Request Resource

**Track**: Backend  
**Dependencies**: 1.2  
**Effort**: 0.5 days

**Objective**: Create Filament resource for request management.

**Files to create**:

- `app/Filament/Resources/RequestResource.php`
- `app/Filament/Resources/RequestResource/Pages/ListRequests.php`
- `app/Filament/Resources/RequestResource/Pages/CreateRequest.php`
- `app/Filament/Resources/RequestResource/Pages/EditRequest.php`

**Acceptance Criteria**:

- [ ] List page renders
- [ ] Create page renders
- [ ] Edit page renders
- [ ] Can CRUD requests
- [ ] Can approve/reject
- [ ] RTL layout works

---

### Task 4.4: Filament Call Resource

**Track**: Backend  
**Dependencies**: 1.2  
**Effort**: 0.5 days

**Objective**: Create Filament resource for call management.

**Files to create**:

- `app/Filament/Resources/CallResource.php`
- `app/Filament/Resources/CallResource/Pages/ListCalls.php`

**Acceptance Criteria**:

- [ ] List page renders
- [ ] Can filter calls
- [ ] Can export calls
- [ ] RTL layout works

---

## Phase 5: Integration & Testing

### Task 5.1: Navigation Updates

**Track**: Frontend  
**Dependencies**: All Phase 2 & 3  
**Effort**: 0.5 days

**Objective**: Add new pages to navigation.

**Files to modify**:

- `resources/views/layouts/app.blade.php` (or navigation component)

**Changes**:

- Add "Calendar" link
- Add "Todos" link
- Add "Tickets" link
- Add "Requests" link
- Add "Performance" link
- Add "Agenda" link

**Acceptance Criteria**:

- [ ] All new links visible in navigation
- [ ] Links work correctly
- [ ] RTL layout correct
- [ ] Active state shows correctly

---

### Task 5.2: Offline Sync Integration

**Track**: Backend  
**Dependencies**: All Phase 2  
**Effort**: 1 day

**Objective**: Integrate new entities with offline sync queue.

**Files to modify**:

- `app/Services/SyncService.php` (or existing sync mechanism)

**Changes**:

- Add todo sync handlers
- Add ticket sync handlers
- Add request sync handlers
- Add call sync handlers

**Acceptance Criteria**:

- [ ] Todos queue when offline
- [ ] Tickets queue when offline
- [ ] Requests queue when offline
- [ ] Calls queue when offline
- [ ] Sync works when back online
- [ ] Idempotency enforced

---

### Task 5.3: Permission Seeding

**Track**: Backend  
**Dependencies**: 1.2  
**Effort**: 0.5 days

**Objective**: Seed new permissions.

**Files to create/modify**:

- `database/seeders/PermissionSeeder.php`

**Permissions to add**:

```php
'todos.create', 'todos.view', 'todos.update', 'todos.complete', 'tickets.create',
'tickets.view', 'tickets.update', 'tickets.assign', 'tickets.close',
'requests.create', 'requests.view', 'requests.approve', 'requests.reject',
'calls.create', 'calls.view', 'performance.view', 'performance.export',
'customers.export',
```

**Acceptance Criteria**:

- [ ] Permissions seeded correctly
- [ ] Roles can be assigned permissions
- [ ] Permission checks work in controllers

---

### Task 5.4: Language Files

**Track**: Frontend  
**Dependencies**: None  
**Effort**: 1 day

**Objective**: Create language files for all new strings.

**Files to create**:

- `lang/en/m7.php`
- `lang/ar/m7.php`

**Acceptance Criteria**:

- [ ] All strings translated
- [ ] Arabic RTL strings correct
- [ ] Date formats correct (Hijri for Arabic)
- [ ] Number formats correct (Eastern Arabic for Arabic)

---

### Task 5.5: Feature Tests

**Track**: Backend  
**Dependencies**: All Phase 2  
**Effort**: 2 days

**Objective**: Write Pest tests for all new features.

**Files to create**:

- `tests/Feature/TodoTest.php`
- `tests/Feature/TicketTest.php`
- `tests/Feature/RequestTest.php`
- `tests/Feature/CallTest.php`
- `tests/Feature/PerformanceTest.php`
- `tests/Feature/CalendarTest.php`
- `tests/Feature/AgendaTest.php`

**Acceptance Criteria**:

- [ ] All tests pass
- [ ] Happy path covered
- [ ] Error paths covered
- [ ] Permission checks tested
- [ ] RTL rendering tested

---

### Task 5.6: Browser Tests (E2E)

**Track**: QA  
**Dependencies**: 5.5  
**Effort**: 1 day

**Objective**: Write Playwright tests for critical journeys.

**Files to create**:

- `tests/Browser/M7CalendarTest.php`
- `tests/Browser/M7TodosTest.php`
- `tests/Browser/M7PerformanceTest.php`

**Acceptance Criteria**:

- [ ] Calendar renders and navigates
- [ ] Todos can be created and completed
- [ ] Performance dashboard loads
- [ ] Tests run in CI

---

## Task Summary

| Task | Track    | Dependencies  | Effort   |
| ---- | -------- | ------------- | -------- |
| 1.1  | Backend  | —             | 1 day    |
| 1.2  | Backend  | 1.1           | 1 day    |
| 1.3  | Backend  | 1.2           | 2 days   |
| 2.1  | Backend  | 1.3           | 1 day    |
| 2.2  | Backend  | 1.3           | 1 day    |
| 2.3  | Backend  | 1.3           | 1 day    |
| 2.4  | Backend  | 1.3           | 1 day    |
| 2.5  | Backend  | 1.3           | 2 days   |
| 2.6  | Backend  | 1.3           | 1 day    |
| 2.7  | Backend  | 2.6, 2.1      | 1 day    |
| 3.1  | Frontend | —             | 1 day    |
| 3.2  | Frontend | —             | 1 day    |
| 3.3  | Frontend | —             | 1 day    |
| 3.4  | Frontend | —             | 0.5 days |
| 3.5  | Frontend | —             | 2 days   |
| 3.6  | Frontend | —             | 1 day    |
| 3.7  | Frontend | —             | 1 day    |
| 4.1  | Backend  | 1.2           | 0.5 days |
| 4.2  | Backend  | 1.2           | 0.5 days |
| 4.3  | Backend  | 1.2           | 0.5 days |
| 4.4  | Backend  | 1.2           | 0.5 days |
| 5.1  | Frontend | All Phase 2&3 | 0.5 days |
| 5.2  | Backend  | All Phase 2   | 1 day    |
| 5.3  | Backend  | 1.2           | 0.5 days |
| 5.4  | Frontend | —             | 1 day    |
| 5.5  | Backend  | All Phase 2   | 2 days   |
| 5.6  | QA       | 5.5           | 1 day    |

**Total**: ~23.5 days

---

## Critical Path

```
1.1 → 1.2 → 1.3 → 2.5 → 5.2 → 5.5 → 5.6
```

**Parallel tracks**:

- Backend: 2.1, 2.2, 2.3, 2.4, 2.6 can run after 1.3
- Frontend: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7 can start immediately
- Admin: 4.1, 4.2, 4.3, 4.4 can run after 1.2

---

## Approval Gates

| Gate | Criteria                 | Owner         |
| ---- | ------------------------ | ------------- |
| G1   | Database migrations pass | Backend Lead  |
| G2   | All API endpoints work   | Backend Lead  |
| G3   | All UI renders correctly | Frontend Lead |
| G4   | All tests pass           | QA Lead       |
| G5   | RTL verified             | Product Owner |
| G6   | Performance <3s          | Tech Lead     |
