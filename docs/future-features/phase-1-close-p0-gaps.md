# Phase 1: Close P0 Gaps

## PRD

### Problem

Jawla is missing several P0 features from the Field Command spec that block production readiness for a full field-sales operation. Reps cannot properly end shifts with validation, have no geofence awareness during check-in, lack order review steps, and have no MFA.

### Users

- **Sales Rep** (mobile): Needs end-shift summary, geofence-aware check-in, order review, customer statement
- **Sales Manager** (web): Needs reps to follow structured workflows before submission
- **Admin** (web): Needs MFA for security compliance

### Outcomes

1. Reps get a validated end-of-shift summary with pending-item warnings
2. Check-in shows geofence state (inside/near/outside/far) with appropriate behavior
3. Orders have a review step before submission
4. Reps can view customer account statements
5. Task detail shows full approval history and timeline
6. Notifications are filterable by category
7. TOTP-based MFA is available for all users

### Non-Goals

- Configurable checklists (Phase 4)
- Approval workflow builder (Phase 2)
- Route maps (Phase 3)
- Platform admin (Phase 4)

### Success Measures

- 100% of shift endings go through the summary screen
- Geofence warnings appear for 100% of out-of-area check-ins
- Order review step reduces post-submission edits by >50%
- MFA enabled for all admin/sales_manager roles

---

## SPEC

### 1. End-Shift Summary (M-46)

**Actor:** Sales Rep, on-shift
**Precondition:** Shift is active (`work_sessions.ended_at IS NULL`)

**Screen:**

```
┌─────────────────────────┐
│  End Shift Summary      │
├─────────────────────────┤
│ Shift Duration: 6h 32m  │
│ Distance: 47.3 km       │
│                         │
│ ✓ Visits: 8 completed  │
│ ⚠ Visits: 1 missed    │
│ 📦 Orders: 3 created   │
│ 💰 Collections: 2      │
│ 🔄 Returns: 0          │
│ 💸 Expenses: 1         │
│                         │
│ ⚠ Unsynchronized: 2    │
│ ⚠ Pending approval: 1  │
│                         │
│ ┌─────────────────────┐ │
│ │ [End Shift]         │ │
│ └─────────────────────┘ │
│ ┌─────────────────────┐ │
│ │ [Sync & End]        │ │
│ └─────────────────────┘ │
│ ┌─────────────────────┐ │
│ │ [Cancel]            │ │
│ └─────────────────────┘ │
└─────────────────────────┘
```

**Behavior:**

- On mount: query `work_sessions`, `visits`, `sales_orders`, `payments`, `return_requests`, `expenses` for current user + active session
- Count unsynced items from IndexedDB outbox
- Count pending approval items (`approval_requests` where status=pending)
- "End Shift" button disabled if unsynced financial transactions exist
- "Sync & End" triggers sync queue drain, then ends shift
- Validation warnings:
  - Active visit open → block end shift, show "Complete open visit first"
  - Unsynchronized financial transaction → warn, require sync first
  - Incomplete stock count → warn
  - Draft collection → warn
- On confirm: set `work_sessions.ended_at = now()`, stop `LocationTracker`, create daily summary record

**Data changes:**

- `work_sessions.ended_at` set to current timestamp
- `location_pings` no longer collected

**Permissions:** `rep` role, own shift only

**Loading/Empty:**

- Loading: skeleton cards
- Empty state: "No active shift" with link to Home

**Error:** If shift already ended by another device, show "Shift was ended on [device]" and redirect Home

---

### 2. Geofence States on Check-In (M-16, M-08)

**Actor:** Sales Rep
**Precondition:** Active shift, navigating to customer

**States:**

| State                | Distance from customer GPS         | Behavior                                                              |
| -------------------- | ---------------------------------- | --------------------------------------------------------------------- |
| Inside allowed area  | ≤ `geofence_radius` (default 200m) | Normal check-in, no warning                                           |
| Near allowed area    | 200m–500m                          | Warning banner: "You are {distance} from customer. Check-in allowed." |
| Outside allowed area | 500m–2km                           | Required reason field + warning                                       |
| Far outside area     | >2km                               | Supervisor approval required (blocks check-in until approved)         |

**Screen additions to VisitFlow step 1:**

```
┌─────────────────────────────────┐
│ 📍 Check-In: Customer Name     │
├─────────────────────────────────┤
│ Distance: 847m from outlet      │
│ Accuracy: ±12m                  │
│                                 │
│ ⚠️ You are outside the          │
│    expected area (847m)         │
│                                 │
│ Reason for out-of-area visit:   │
│ ┌─────────────────────────────┐ │
│ │ [dropdown: customer不在,    │ │
│ │  rescheduled, special visit]│ │
│ └─────────────────────────────┘ │
│                                 │
│ ┌─────────────────────────────┐ │
│ │ [Confirm Check-In]          │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

**Behavior:**

- On mount: get device GPS, calculate haversine distance to `customers.latitude/longitude`
- If `customer_locations` has primary location, use that instead
- If no customer GPS → show warning "Customer location not set" with option to capture
- Distance calculation: haversine formula in JS (already exists in codebase)
- "Far outside" (>2km): show "Request Exception" button, dispatches `AlarmService` for manager approval
- Store geofence state on `visits` record: `arrival_distance_m`, `arrival_geofence_state`

**Data changes:**

- `visits.arrival_distance_m` (new column, nullable decimal)
- `visits.arrival_geofence_state` (new column, nullable enum: inside/near/outside/far)

**Migration:**

```php
Schema::table('visits', function (Blueprint $table) {
    $table->decimal('arrival_distance_m', 8, 2)->nullable()->after('checkout_at');
    $table->string('arrival_geofence_state', 10)->nullable()->after('arrival_distance_m');
});
```

**Permissions:** `rep` role

---

### 3. Order Review & Submission (M-26)

**Actor:** Sales Rep
**Precondition:** Order builder has items

**Screen:** New step between SalesFlow cart and submission

```
┌─────────────────────────────────┐
│ Order Review                    │
├─────────────────────────────────┤
│ Customer: Ahmed Trading Co.     │
│ Outlet: Main Branch             │
│ Date: 2026-08-03                │
│                                 │
│ ┌─ Items ─────────────────────┐ │
│ │ Product     Qty  Price  Tot │ │
│ │ Widget A    10   25.00 250  │ │
│ │ Widget B     5   50.00 250  │ │
│ │ Gadget C    20   12.50 250  │ │
│ ├─────────────────────────────┤ │
│ │ Subtotal:         750.00    │ │
│ │ VAT (14%):        105.00    │ │
│ │ Total:            855.00    │ │
│ └─────────────────────────────┘ │
│                                 │
│ ⚠ Credit remaining: 1,200.00   │
│ ⚠ Low stock: Widget B (3 left) │
│                                 │
│ Notes: [_____________]          │
│                                 │
│ ┌─────────────────────────────┐ │
│ │ [Submit Order]              │ │
│ └─────────────────────────────┘ │
│ ┌─────────────────────────────┐ │
│ │ [Edit / Back]               │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

**Behavior:**

- Summary shows all items, pricing, taxes
- Credit warning: if `total > customers.balance + customers.credit_limit` → red warning
- Stock warning: if any line item qty > available stock → yellow warning
- Duplicate warning: if same customer has order in last 24h → show "Similar order exists: #INV-00123"
- Submit creates invoice via `InvoiceService`
- After submit: show success with invoice number + "PDF" button

**Permissions:** `rep` role, `orders.create` permission

---

### 4. Customer Account Statement (M-33)

**Actor:** Sales Rep
**Precondition:** Customer selected

**Screen:**

```
┌─────────────────────────────────┐
│ Account Statement: Ahmed Trading│
├─────────────────────────────────┤
│ Opening Balance:    5,000.00    │
│ Total Invoices:    12,500.00    │
│ Total Payments:     8,200.00    │
│ Credit Notes:         500.00    │
│ ─────────────────────────────── │
│ Outstanding:        3,800.00    │
│                                 │
│ ┌─ Transactions ──────────────┐ │
│ │ 01 Aug INV-001    2,500.00  │ │
│ │ 02 Aug PAY-003   -1,000.00  │ │
│ │ 02 Aug PAY-004     -500.00  │ │
│ │ 03 Aug INV-002    1,800.00  │ │
│ └─────────────────────────────┘ │
│                                 │
│ ┌─────────────────────────────┐ │
│ │ [Download PDF]              │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

**Behavior:**

- Query `invoices`, `payments`, `credit_notes` for customer
- Chronological list with running balance
- PDF generation via existing `PdfService`
- Date range filter (default: last 90 days)

**Data:** Read-only, no mutations

**Permissions:** `rep` role, own assigned customers only

---

### 5. Task Detail with Approval History (M-21)

**Actor:** Sales Rep or Manager
**Precondition:** Task exists

**Screen:**

```
┌─────────────────────────────────┐
│ Task: Market Survey - Zone A    │
├─────────────────────────────────┤
│ Status: In Progress             │
│ Priority: High                  │
│ Due: 2026-08-05                 │
│ Assigned by: Manager Ahmed      │
│                                 │
│ ── Checklist ──                 │
│ ✓ Visit 5 customers            │
│ ☐ Take photos of displays      │
│ ☐ Submit competitor prices     │
│                                 │
│ ── Activity ──                  │
│ 03 Aug 10:00  Task created      │
│ 03 Aug 10:05  Accepted by rep   │
│ 03 Aug 11:30  Checklist 1 done  │
│ 03 Aug 14:00  Submitted         │
│ 03 Aug 15:00  Approved by Mgr   │
│                                 │
│ ── Evidence ──                  │
│ [photo1.jpg] [photo2.jpg]       │
│                                 │
│ ┌─────────────────────────────┐ │
│ │ [Submit for Approval]       │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

**Behavior:**

- Tabs: Overview | Checklist | Evidence | Activity
- Activity timeline: query `activities` table filtered by task
- Approval history: query `approval_requests` + `approval_steps` for this task
- Submit: validates checklist completion, uploads evidence, sets status=submitted

**Permissions:** `rep` role for own tasks, `tasks.approve` for managers

---

### 6. Notification Category Filtering (M-41)

**Actor:** Sales Rep
**Precondition:** Notifications exist

**Screen:** Add category tabs to existing Notifications component

```
┌─────────────────────────────────┐
│ Notifications        [Mark All] │
├─────────────────────────────────┤
│ [All] [Assignments] [Approvals]│
│ [Orders] [Stock] [System]      │
├─────────────────────────────────┤
│ 📋 New task assigned: Survey   │
│    2 min ago                    │
│                                 │
│ ✅ Order #INV-001 approved     │
│    1 hour ago                   │
│                                 │
│ ⚠ Low stock: Widget B         │
│    3 hours ago                  │
└─────────────────────────────────┘
```

**Behavior:**

- Add `category` column to `notifications` table (or use `data->category` JSON field)
- Filter by category via query parameter
- "Mark All" clears current filter's unread
- Categories: assignment, approval, order, stock, system, sync

**Migration:**

```php
// No new column needed - use existing data JSON field
// Filter via: whereJsonContains('data->category', $category)
```

**Permissions:** `rep` role, own notifications

---

### 7. TOTP Multi-Factor Authentication (M-04, IAM-002)

**Actor:** Any user
**Precondition:** User wants to enable MFA

**Admin Screen (W-43 additions):**

- User list gets "MFA" column (Enabled/Disabled)
- User edit gets "Enable MFA" toggle
- When enabled: show QR code + recovery codes

**Login Flow:**

```
Step 1: Email + Password → success
Step 2: MFA screen → enter 6-digit TOTP code
Step 3: Dashboard
```

**Implementation:**

- Use `pragmarx/google2fa-laravel` package (Laravel Google2FA)
- Store `google2fa_secret` on users table (encrypted)
- Store recovery codes (hashed) on separate `mfa_recovery_codes` table
- Middleware: `mfa.verify` checks session for MFA completion

**Recovery:**

- 10 single-use recovery codes generated on MFA setup
- Each code hashed and stored with `used` flag
- "Use recovery code" link on MFA screen

**Data changes:**

```php
Schema::table('users', function (Blueprint $table) {
    $table->text('google2fa_secret')->nullable()->after('remember_token');
    $table->boolean('mfa_enabled')->default(false)->after('google2fa_secret');
});

Schema::create('mfa_recovery_codes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('code_hash');
    $table->boolean('used')->default(false);
    $table->nullableTimestamps('used_at');
});
```

**Permissions:** Any authenticated user can enable MFA on own account. Admin can force-enable for roles via bulk action.

---

## USER_JOURNEYS

### Journey 1: End of Day

1. Rep finishes last visit
2. Taps "End Shift" from Home or Active Shift screen
3. Sees summary: 8 visits, 3 orders, 2 collections, 1 expense
4. Sees warning: "2 items not synchronized"
5. Taps "Sync & End"
6. Sync drain runs (5-10 seconds)
7. Shift ends, tracking stops
8. Home shows "Shift ended" state

### Journey 2: Out-of-Area Check-In

1. Rep navigates to customer, 847m away
2. Opens VisitFlow, system detects distance
3. Warning: "You are 847m from customer"
4. Reason dropdown appears
5. Rep selects "Customer relocated"
6. Confirms check-in
7. Visit recorded with `arrival_geofence_state: outside`

### Journey 3: Order Review

1. Rep adds 3 products to cart in SalesFlow
2. Taps "Review Order"
3. Sees summary: items, totals, credit warning
4. Notices low stock warning on Widget B
5. Decides to reduce quantity
6. Taps "Edit" → back to cart
7. Adjusts Widget B from 5 to 3
8. Reviews again → submits
9. Sees success: "Order #INV-0045 created"

### Journey 4: MFA Setup

1. User goes to Profile → Security
2. Taps "Enable Two-Factor Authentication"
3. Sees QR code + secret key
4. Scans with Google Authenticator
5. Enters 6-digit code to verify
6. Sees 10 recovery codes, prompted to save
7. Next login: password → TOTP code → dashboard

---

## ARCHITECTURE

### Decisions

| Decision               | Choice                            | Rationale                                     | Reversible             |
| ---------------------- | --------------------------------- | --------------------------------------------- | ---------------------- |
| Geofence calculation   | Client-side haversine             | No API call needed, instant feedback          | Yes                    |
| MFA package            | `pragmarx/google2fa-laravel`      | Mature, TOTP standard, Laravel-native         | Yes                    |
| MFA secret storage     | Encrypted column on users         | Simple, no extra table for secret             | Yes (migration needed) |
| Recovery codes         | Separate table, hashed            | Security: codes never stored plaintext        | Yes                    |
| End-shift validation   | Service layer in `SessionService` | Consistent with existing pattern              | Yes                    |
| Order review           | New Livewire step in SalesFlow    | No new page needed, reuse existing flow       | Yes                    |
| Customer statement     | New Livewire component            | Read-only, no service needed                  | Yes                    |
| Notification filtering | JSON field query                  | No schema change, uses existing `data` column | Yes                    |

### Boundaries

- **Trust boundary:** MFA secret generation happens server-side only
- **Trust boundary:** Geofence reason is logged but not validated server-side (rep could lie)
- **Irreversible:** Shift end cannot be undone (by design per spec)
- **External calls:** None in this phase

---

## TASKS

### Milestone 1: Geofence States (2 days)

| #   | Task                                                                    | Files                                                                                 | Tests                                   | Status  |
| --- | ----------------------------------------------------------------------- | ------------------------------------------------------------------------------------- | --------------------------------------- | ------- |
| 1.1 | Migration: add `arrival_distance_m`, `arrival_geofence_state` to visits | `database/migrations/`                                                                | Migration runs clean                    | Pending |
| 1.2 | Update `VisitFlow` Livewire: add distance calculation on mount          | `app/Livewire/App/VisitFlow.php`, `resources/views/livewire/app/visit-flow.blade.php` | Distance shows on check-in              | Pending |
| 1.3 | Add geofence state enum logic (inside/near/outside/far)                 | `app/Livewire/App/VisitFlow.php`                                                      | 4 distance ranges produce correct state | Pending |
| 1.4 | Add reason dropdown for outside/far states                              | `resources/views/livewire/app/visit-flow.blade.php`                                   | Reason field visible when outside       | Pending |
| 1.5 | Store distance + state on check-in                                      | `app/Livewire/App/VisitFlow.php`                                                      | Visits table has correct values         | Pending |
| 1.6 | Pest test: geofence states                                              | `tests/Feature/GeofenceTest.php`                                                      | All 4 states tested                     | Pending |

### Milestone 2: End-Shift Summary (2 days)

| #   | Task                                                               | Files                                                                               | Tests                              | Status  |
| --- | ------------------------------------------------------------------ | ----------------------------------------------------------------------------------- | ---------------------------------- | ------- |
| 2.1 | New Livewire component `EndShift`                                  | `app/Livewire/App/EndShift.php`, `resources/views/livewire/app/end-shift.blade.php` | Component renders                  | Pending |
| 2.2 | Query logic: visits, orders, collections, expenses, unsynced count | `app/Livewire/App/EndShift.php`                                                     | Counts match expected              | Pending |
| 2.3 | Validation: block on active visit, unsynced financials             | `app/Livewire/App/EndShift.php`                                                     | Blocks correctly                   | Pending |
| 2.4 | End-shift action: close session, stop tracking                     | `app/Services/SessionService.php`                                                   | Work session ended_at set          | Pending |
| 2.5 | Wire "End Shift" button on Home/Active Shift                       | `resources/views/livewire/app/home.blade.php`                                       | Button navigates to EndShift       | Pending |
| 2.6 | Add route `GET /app/end-shift`                                     | `routes/web.php`                                                                    | Route accessible                   | Pending |
| 2.7 | Pest test: end shift flows                                         | `tests/Feature/EndShiftTest.php`                                                    | Happy path + all validation blocks | Pending |

### Milestone 3: Order Review (1 day)

| #   | Task                                        | Files                                               | Tests                          | Status  |
| --- | ------------------------------------------- | --------------------------------------------------- | ------------------------------ | ------- |
| 3.1 | Add review step to `SalesFlow` component    | `app/Livewire/App/SalesFlow.php`                    | Step shows after cart          | Pending |
| 3.2 | Build review blade: items, totals, warnings | `resources/views/livewire/app/sales-flow.blade.php` | Renders correctly              | Pending |
| 3.3 | Credit warning logic                        | `app/Livewire/App/SalesFlow.php`                    | Warning when over limit        | Pending |
| 3.4 | Low stock warning logic                     | `app/Livewire/App/SalesFlow.php`                    | Warning when qty > stock       | Pending |
| 3.5 | Duplicate order warning                     | `app/Livewire/App/SalesFlow.php`                    | Warning for orders in last 24h | Pending |
| 3.6 | Pest test: order review                     | `tests/Feature/OrderReviewTest.php`                 | All warnings + submission      | Pending |

### Milestone 4: Customer Statement (1 day)

| #   | Task                                                  | Files                                                                                                 | Tests                       | Status  |
| --- | ----------------------------------------------------- | ----------------------------------------------------------------------------------------------------- | --------------------------- | ------- |
| 4.1 | New Livewire component `CustomerStatement`            | `app/Livewire/App/CustomerStatement.php`, `resources/views/livewire/app/customer-statement.blade.php` | Component renders           | Pending |
| 4.2 | Query invoices + payments + credit_notes for customer | `app/Livewire/App/CustomerStatement.php`                                                              | Correct transactions listed | Pending |
| 4.3 | Date range filter                                     | `app/Livewire/App/CustomerStatement.php`                                                              | Filter works                | Pending |
| 4.4 | PDF download via existing PdfService                  | `app/Livewire/App/CustomerStatement.php`                                                              | PDF generates               | Pending |
| 4.5 | Wire from customer list/detail                        | `resources/views/livewire/app/customers.blade.php`                                                    | "Statement" button visible  | Pending |
| 4.6 | Add route `GET /app/customers/{customer}/statement`   | `routes/web.php`                                                                                      | Route accessible            | Pending |
| 4.7 | Pest test: customer statement                         | `tests/Feature/CustomerStatementTest.php`                                                             | Transactions + PDF          | Pending |

### Milestone 5: Task Detail (1 day)

| #   | Task                                                       | Files                                                                                   | Tests                     | Status  |
| --- | ---------------------------------------------------------- | --------------------------------------------------------------------------------------- | ------------------------- | ------- |
| 5.1 | New Livewire component `TaskDetail`                        | `app/Livewire/App/TaskDetail.php`, `resources/views/livewire/app/task-detail.blade.php` | Component renders         | Pending |
| 5.2 | Tabs: Overview, Checklist, Evidence, Activity              | `resources/views/livewire/app/task-detail.blade.php`                                    | Tabs switch content       | Pending |
| 5.3 | Activity timeline from `activities` table                  | `app/Livewire/App/TaskDetail.php`                                                       | Timeline shows events     | Pending |
| 5.4 | Approval history from `approval_requests`/`approval_steps` | `app/Livewire/App/TaskDetail.php`                                                       | Approval steps shown      | Pending |
| 5.5 | Update existing `Tasks` component to link to detail        | `resources/views/livewire/app/tasks.blade.php`                                          | Task cards link to detail | Pending |
| 5.6 | Add route `GET /app/tasks/{task}`                          | `routes/web.php`                                                                        | Route accessible          | Pending |
| 5.7 | Pest test: task detail                                     | `tests/Feature/TaskDetailTest.php`                                                      | Tabs + data display       | Pending |

### Milestone 6: Notification Filtering (0.5 day)

| #   | Task                                                 | Files                                                  | Tests                  | Status  |
| --- | ---------------------------------------------------- | ------------------------------------------------------ | ---------------------- | ------- |
| 6.1 | Add category tabs to Notifications blade             | `resources/views/livewire/app/notifications.blade.php` | Tabs render            | Pending |
| 6.2 | Add category filter logic to Notifications component | `app/Livewire/App/Notifications.php`                   | Filter works           | Pending |
| 6.3 | Ensure notification dispatchers set `data->category` | Check all `Notification::send` calls                   | Category field present | Pending |
| 6.4 | Pest test: notification filtering                    | `tests/Feature/NotificationFilterTest.php`             | Category filter works  | Pending |

### Milestone 7: MFA (3 days)

| #   | Task                                                                              | Files                                     | Tests                     | Status  |
| --- | --------------------------------------------------------------------------------- | ----------------------------------------- | ------------------------- | ------- |
| 7.1 | Migration: `google2fa_secret`, `mfa_enabled` on users; `mfa_recovery_codes` table | `database/migrations/`                    | Migrations run clean      | Pending |
| 7.2 | Install `pragmarx/google2fa-laravel`                                              | `composer.json`                           | Package installed         | Pending |
| 7.3 | MFA service: generate secret, verify code, generate recovery codes                | `app/Services/MfaService.php`             | Unit tests pass           | Pending |
| 7.4 | Middleware: `mfa.verify` redirect to MFA screen if not verified                   | `app/Http/Middleware/VerifyMfa.php`       | Redirects correctly       | Pending |
| 7.5 | MFA setup Livewire: show QR, verify code, show recovery codes                     | `app/Livewire/App/MfaSetup.php`, view     | Setup flow works          | Pending |
| 7.6 | MFA verify Livewire: enter code or recovery code                                  | `app/Livewire/App/MfaVerify.php`, view    | Login flow complete       | Pending |
| 7.7 | Admin: MFA column in UserResource, bulk enable                                    | `app/Filament/Resources/UserResource.php` | Admin can manage MFA      | Pending |
| 7.8 | Register middleware in kernel                                                     | `bootstrap/app.php`                       | Middleware active         | Pending |
| 7.9 | Pest test: MFA flows                                                              | `tests/Feature/MfaTest.php`               | Setup + verify + recovery | Pending |

### Milestone 8: Integration (1 day)

| #   | Task                                      | Files                                              | Tests              | Status  |
| --- | ----------------------------------------- | -------------------------------------------------- | ------------------ | ------- |
| 8.1 | Add End Shift to bottom nav / Home screen | Layout files                                       | Button accessible  | Pending |
| 8.2 | Add "Statement" to customer actions       | `resources/views/livewire/app/customers.blade.php` | Action visible     | Pending |
| 8.3 | Update QA test script with new screens    | `jawla_full_qa.py`                                 | New screens tested | Pending |
| 8.4 | Run `make verify`                         | Terminal                                           | All tests pass     | Pending |
| 8.5 | Deploy to staging                         | `railway up`                                       | Staging works      | Pending |

---

## RISKS

| Risk                                               | Impact                                         | Mitigation                                 |
| -------------------------------------------------- | ---------------------------------------------- | ------------------------------------------ |
| Geofence GPS accuracy on low-cost devices          | Rep may be legitimately near but GPS shows far | Allow 100m tolerance buffer, show accuracy |
| MFA recovery codes lost                            | Account locked out                             | Allow admin reset MFA via UserResource     |
| End-shift blocks on unsynced data                  | Rep cannot leave if offline                    | Allow "End with pending sync" with warning |
| Customer statement PDF generation slow             | Poor UX on slow connection                     | Cache PDF, show loading spinner            |
| Notification category not set on old notifications | Filter shows empty for legacy                  | Default "system" category for null         |

---

```yaml
plan_result:
  scope:
    [
      end-shift,
      geofence,
      order-review,
      customer-statement,
      task-detail,
      notification-filter,
      mfa,
    ]
  non_goals:
    [configurable-checklists, approval-builder, route-maps, platform-admin]
  acceptance_criteria_count: 42
  architecture_decisions:
    [
      client-side-haversine,
      google2fa-package,
      encrypted-secret-storage,
      json-category-filter,
    ]
  milestones:
    [
      geofence,
      end-shift,
      order-review,
      customer-statement,
      task-detail,
      notification-filter,
      mfa,
      integration,
    ]
  critical_path:
    [mfa-package-install → mfa-service → mfa-middleware → mfa-screens]
  approval_gates: [after-milestone-7-mfa, after-milestone-8-integration]
  risks:
    [
      gps-accuracy,
      mfa-lockout,
      unsynced-shift-block,
      pdf-slow,
      legacy-notifications,
    ]
  documents_written: [this-plan]
  next_vertical_slice: Milestone 1 — Geofence States
  recommended_next_skill: v-implementation-strategist
```
