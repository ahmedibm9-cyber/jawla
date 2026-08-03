# Phase 3: Operational Visibility

## PRD

### Problem

Managers lack operational visibility into field activities. The web dashboard has a live map but no list view of reps, no detailed rep profiles, no tabbed customer detail, and no route map for reps. The spec requires: live operations list (W-05), representative profile tabs (W-07), customer detail tabs (W-10), route map (M-12), and active shift screen (M-10).

### Users

- **Sales Manager** (web): Needs to see all reps' status at a glance, drill into any rep's performance
- **Operations Manager** (web): Needs to see who's active, delayed, or offline
- **Sales Rep** (mobile): Needs route map, active shift status, distance/duration tracking
- **Admin** (web): Needs rep profile with full audit trail

### Outcomes

1. Live operations list shows all reps with real-time status
2. Rep profile has tabs: Overview, Assignments, Performance, Activity, Security
3. Customer detail has tabs: Overview, Visits, Orders, Collections, Tasks, Statement
4. Rep gets route map showing planned stops with navigation
5. Active shift screen shows duration, tracking, distance, outstanding items

### Non-Goals

- Territory management with map drawing (Phase 4)
- Route calendar with drag-reschedule (Phase 4)
- Route optimization (Phase 4)
- Supervisor mobile dashboard (Phase 4)

---

## SPEC

### 1. Live Operations List (W-05)

**Actor:** Sales Manager, Operations Manager
**Precondition:** User has `reports.view` permission

**Screen:**

```
┌──────────────────────────────────────────────────────────────────┐
│ Live Operations                              Last update: 30s ago│
├──────────────────────────────────────────────────────────────────┤
│ ┌─ Rep ────── Team ── Shift ── Location ── Customer ── Status ─┐│
│ │ Ahmed      Cairo   Active  14:32 ⚡     Ahmed Trading  Visit  ││
│ │ Sara       Cairo   Active  14:28 ⚡     Sara Supplies  Travel ││
│ │ Omar       Giza    Offline —           —              —       ││
│ │ Mona       Cairo   Active  14:30 ⚠     —              Delayed││
│ │ Khaled     Giza    Active  14:35 ⚡     Khaled Corp    Visit  ││
│ └───────────────────────────────────────────────────────────────┘│
│                                                                  │
│ [Open Map] [Open List] [Export]                                  │
│                                                                  │
│ Showing 1-5 of 12                        [< 1 2 3 >]            │
└──────────────────────────────────────────────────────────────────┘
```

**Columns:**

| Column   | Source                       | Notes                         |
| -------- | ---------------------------- | ----------------------------- |
| Rep      | `users.name`                 | Clickable → rep profile       |
| Team     | `organization_units.name`    | Via rep's org unit            |
| Shift    | `work_sessions` status       | Active/Paused/Offline         |
| Location | `location_pings.recorded_at` | Last ping time + battery icon |
| Customer | Current visit customer       | Via open `visits` record      |
| Status   | Computed                     | Visit/Travel/Delayed/Offline  |
| Accuracy | `location_pings.accuracy`    | ± meters                      |
| Battery  | Device metadata              | From last ping                |

**Status logic:**

| Condition                                                          | Status  |
| ------------------------------------------------------------------ | ------- |
| `work_sessions.ended_at IS NULL` + visit `status=open`             | Visit   |
| `work_sessions.ended_at IS NULL` + no open visit + last ping <5min | Travel  |
| `work_sessions.ended_at IS NULL` + last ping >15min                | Delayed |
| `work_sessions.ended_at IS NOT NULL` or no session today           | Offline |

**Filters:**

- Status: All, Active, Offline, Delayed
- Team: dropdown
- Shift: Active, Not started

**Behavior:**

- `wire:poll.30s` for real-time updates
- Click rep name → navigate to rep profile
- "Open Map" → switch to `RepLiveMap` view
- "Export" → CSV of current view

**Permissions:** `reports.view`

---

### 2. Representative Profile (W-07)

**Actor:** Sales Manager, Admin
**Precondition:** Rep user exists

**Screen (tabbed):**

```
┌──────────────────────────────────────────────────────────────────┐
│ Rep Profile: Ahmed Ibrahim                                       │
├──────────────────────────────────────────────────────────────────┤
│ [Overview] [Assignments] [Performance] [Activity] [Security]     │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│ ── Overview ──                                                   │
│ Name: Ahmed Ibrahim        Employee Code: REP-001                │
│ Role: Sales Rep            Supervisor: Manager Ahmed             │
│ Branch: Cairo              Team: Cairo Team A                    │
│ Vehicle: V-001             Device: iPhone 14 (approved)          │
│ Status: Active             On Shift: Yes (since 08:00)           │
│                                                                  │
│ ── Today's Summary ──                                            │
│ Visits: 5/8 planned    Orders: 3    Collections: 2              │
│ Distance: 32.5 km      Duration: 6h 15m                         │
│                                                                  │
│ ── Recent Activity ──                                            │
│ 14:32  Visit: Ahmed Trading (completed)                          │
│ 14:00  Order: #INV-0045 created                                  │
│ 13:30  Payment: 1,200 collected                                  │
│ 13:00  Travel to Ahmed Trading                                   │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

**Tab: Assignments**

```
┌─ Assigned Customers ──────────────────────────────────────────┐
│ Customer          Outlet          Route        Last Visit      │
│ Ahmed Trading     Main Branch     Cairo-A      02 Aug          │
│ Sara Supplies     Warehouse 2     Cairo-A      01 Aug          │
│ Khaled Corp       Downtown        Cairo-B      30 Jul          │
└───────────────────────────────────────────────────────────────┘
```

**Tab: Performance**

```
┌─ This Month ──────────────────────────────────────────────────┐
│ Sales:     45,000 / 60,000 target  (75%) [████████░░]        │
│ Collections: 32,000 / 40,000       (80%) [█████████░]        │
│ Visits:     142 / 180 planned      (79%) [████████░░]        │
│ Productive: 118 / 142 completed    (83%) [█████████░]        │
│ New Customers: 3 / 5 target        (60%) [██████░░░░]        │
│                                                                  │
│ ── Trend ──                                                      │
│ [Chart: daily sales + visits over last 30 days]                  │
└────────────────────────────────────────────────────────────────┘
```

**Tab: Activity**

```
┌─ Shifts This Week ─────────────────────────────────────────────┐
│ Date       Start   End     Visits  Orders  Distance            │
│ Mon 04 Aug 08:00   17:00   8      4       47 km               │
│ Tue 05 Aug 08:00   16:30   7      3       38 km               │
│ Wed 06 Aug 08:00   —       —      —       — (active)           │
└────────────────────────────────────────────────────────────────┘
```

**Tab: Security**

```
┌─ Devices ──────────────────────────────────────────────────────┐
│ Device          OS       Version  Last Active  Status           │
│ iPhone 14       iOS 17   2.1.0   14:35 today  Approved         │
│ iPad Air        iOS 16   2.0.5   01 Aug       Revoked          │
│                                                                  │
│ ── Sessions ──                                                   │
│ IP: 197.xx.xx.xx  Last: 14:35  Browser: Safari                  │
│                                                                  │
│ ── Login History ──                                              │
│ 03 Aug 08:00  197.xx.xx.xx  Success                             │
│ 02 Aug 22:00  10.xx.xx.xx   Failed (wrong password)             │
└────────────────────────────────────────────────────────────────┘
```

**Implementation:**

- New Livewire component `RepProfile` with Alpine.js tabs
- Each tab loads data on demand (not all at once)
- Performance tab uses `AttainmentService` + chart via Chart.js or Livewire chart

**Permissions:** `reports.view` for overview/performance, `users.manage` for security tab

---

### 3. Customer Detail Tabs (W-10)

**Actor:** Sales Manager, Admin
**Precondition:** Customer exists

**Screen (tabbed):**

```
┌──────────────────────────────────────────────────────────────────┐
│ Customer: Ahmed Trading Co. (CUST-001)                           │
├──────────────────────────────────────────────────────────────────┤
│ [Overview] [Outlets] [Visits] [Orders] [Collections] [Statement] │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│ ── Overview ──                                                   │
│ Name: Ahmed Trading Co.      Code: CUST-001                     │
│ Phone: +20 123 456 7890     Tax: 123-456-789                   │
│ Address: 123 Main St, Cairo                                   │
│ Group: Wholesale             Route: Cairo-A                     │
│ Assigned Rep: Ahmed Ibrahim                                     │
│                                                                  │
│ ── Financial ──                                                  │
│ Credit Limit: 50,000        Outstanding: 3,800                  │
│ Balance: 3,800              Last Payment: 02 Aug (1,000)        │
│ Payment Terms: Net 30       Since: Jan 2026                     │
│                                                                  │
│ ── Location ──                                                   │
│ [Map with customer pin]                                          │
│ Last Visit: 02 Aug (completed)                                   │
│ Next Visit: 05 Aug (planned)                                     │
│                                                                  │
│ ── Quick Actions ──                                              │
│ [Create Order] [Collect Payment] [Log Return] [Create Task]      │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

**Tab: Visits**

```
┌─ Visit History ─────────────────────────────────────────────────┐
│ Date       Rep       Duration  Outcome      Distance            │
│ 02 Aug     Ahmed     25 min    Productive   150m                │
│ 28 Jul     Ahmed     30 min    Productive   200m                │
│ 25 Jul     Sara      20 min    Survey       180m                │
└────────────────────────────────────────────────────────────────┘
```

**Tab: Orders**

```
┌─ Orders ────────────────────────────────────────────────────────┐
│ #INV-0045   02 Aug   855.00   Paid       [View] [PDF]          │
│ #INV-0038   28 Jul   1,200    Partial    [View] [PDF]          │
│ #INV-0031   25 Jul   450.00   Paid       [View] [PDF]          │
└────────────────────────────────────────────────────────────────┘
```

**Tab: Collections**

```
┌─ Collections ───────────────────────────────────────────────────┐
│ #CS-0034   02 Aug   1,000   Cash    Approved                   │
│ #CS-0028   28 Jul   500     Cheque  Pending                    │
└────────────────────────────────────────────────────────────────┘
```

**Tab: Statement**
Same as Phase 1 customer statement but with full date range + PDF export.

**Implementation:**

- New Livewire component `CustomerDetail` with Alpine.js tabs
- Each tab queries relevant tables
- Quick actions wire to existing Livewire components (SalesFlow, CollectPayment, etc.)

**Permissions:** `customers.view` for overview, `customers.edit` for edit

---

### 4. Route Map (M-12)

**Actor:** Sales Rep
**Precondition:** Active shift, daily visit assignments exist

**Screen:**

```
┌─────────────────────────────────┐
│ 🗺️ Route Map                    │
├─────────────────────────────────┤
│ ┌─────────────────────────────┐ │
│ │                             │ │
│ │    [3]                      │ │
│ │         [2]                 │ │
│ │              ●──You         │ │
│ │    [1]                      │ │
│ │         ★ (current)         │ │
│ │                             │ │
│ └─────────────────────────────┘ │
│                                 │
│ Legend:                          │
│ ● You    ★ Current              │
│ ①②③ Planned stops              │
│ ✅ Completed                    │
│                                 │
│ ┌─────────────────────────────┐ │
│ │ [Navigate to Next]          │ │
│ │ [List View]                 │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

**Map elements:**

- User position (blue dot, real-time via LocationTracker)
- Planned stops (numbered markers from `daily_visit_assignments.sort_order`)
- Current stop (star marker)
- Completed stops (green checkmark)
- Missed stops (red X)
- Route polyline connecting stops in order

**Behavior:**

- Load `daily_visit_assignments` for today + user
- Load customer lat/lng from `customers` or `customer_locations`
- Leaflet map with markers, polyline
- Auto-center on user position
- "Navigate to Next" → opens Google Maps / Waze with directions
- "List View" → switch back to `TodaysCustomers`
- `invalidateSize()` on mount (fix Leaflet container bug)

**Implementation:**

- New Livewire component `RouteMap`
- Leaflet loaded from `public/leaflet.js` + `public/leaflet.css` (self-hosted)
- Customer locations from DB (no external API)

**Permissions:** `rep` role, own route only

---

### 5. Active Shift Screen (M-10)

**Actor:** Sales Rep
**Precondition:** Shift is active

**Screen:**

```
┌─────────────────────────────────┐
│ 🕐 Active Shift                 │
├─────────────────────────────────┤
│ Duration: 6h 15m                │
│ Started: 08:00                  │
│                                 │
│ 📍 Tracking: Active             │
│ Last ping: 14:32 (30s ago)     │
│ Accuracy: ±12m                  │
│ Battery: 72%                    │
│ Connection: Online              │
│                                 │
│ 📏 Distance: 47.3 km           │
│                                 │
│ 📋 Today's Progress             │
│ Visits: 5/8 (63%)              │
│ Orders: 3                       │
│ Collections: 2                  │
│ Returns: 0                      │
│ Tasks: 1/2                      │
│                                 │
│ ⚠ Unsynchronized: 2 items      │
│ ⚠ Pending approval: 1          │
│                                 │
│ ┌─────────────────────────────┐ │
│ │ [Open Route]                │ │
│ └─────────────────────────────┘ │
│ ┌─────────────────────────────┐ │
│ │ [End Shift]                 │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

**Behavior:**

- Duration: calculated from `work_sessions.started_at` to now, live-updating
- Distance: sum of `location_pings` distance deltas (haversine between consecutive pings)
- Progress: query today's visits, orders, collections
- Unsynchronized: count from IndexedDB outbox
- "Open Route" → navigates to RouteMap
- "End Shift" → navigates to EndShift (Phase 1)

**Implementation:**

- New Livewire component `ActiveShift`
- Distance calculation via `LocationPingService::getDistanceToday()` (new method)
- Live updates via `wire:poll.30s`

**Permissions:** `rep` role, own shift only

---

## USER_JOURNEYS

### Journey 1: Manager Monitors Team

1. Manager opens Live Operations List
2. Sees 5 reps: 3 active, 1 delayed, 1 offline
3. Clicks "Mona" (delayed)
4. Opens rep profile → Activity tab
5. Sees last ping was 20 minutes ago
6. Calls Mona to check status
7. Mona resumes, status changes to "Travel"

### Journey 2: Manager Reviews Rep Performance

1. Manager opens Rep Profile for Ahmed
2. Clicks Performance tab
3. Sees sales at 75% of target, collections at 80%
4. Clicks Activity tab
5. Sees shift history with visit counts
6. Notes Ahmed has 3 productive visits short this week
7. Creates task for Ahmed: "Visit 3 more customers today"

### Journey 3: Manager Investigates Customer

1. Manager opens Customer Detail for Ahmed Trading
2. Clicks Orders tab
3. Sees 3 orders this month, all paid
4. Clicks Statement tab
5. Sees outstanding balance 3,800
6. Notes customer is healthy, no action needed

### Journey 4: Rep Navigates Route

1. Rep starts shift
2. Opens Route Map
3. Sees 8 planned stops on map
4. Current stop: Ahmed Trading (marked with star)
5. Taps "Navigate to Next"
6. Google Maps opens with directions
7. Arrives, checks in (geofence: inside)
8. Completes visit
9. Map updates: stop turns green, next stop becomes current

### Journey 5: Rep Checks Shift Status

1. Rep opens Active Shift screen
2. Sees duration: 6h 15m
3. Sees distance: 47.3 km
4. Sees 5/8 visits completed
5. Sees 2 unsynchronized items
6. Taps "Sync" → items sync
7. Unsynchronized count drops to 0

---

## ARCHITECTURE

### Decisions

| Decision              | Choice                                        | Rationale                                | Reversible |
| --------------------- | --------------------------------------------- | ---------------------------------------- | ---------- |
| Live ops list         | New Livewire component, not Filament resource | Custom real-time layout, not CRUD        | Yes        |
| Rep profile tabs      | Alpine.js x-data for tab switching            | No page reload, lightweight              | Yes        |
| Customer detail tabs  | Same pattern as rep profile                   | Consistency                              | Yes        |
| Route map             | Leaflet with existing self-hosted assets      | Already in codebase, no CDN dependency   | Yes        |
| Active shift distance | Haversine between consecutive pings           | No external API, server-side calculation | Yes        |
| Performance charts    | Chart.js via CDN or Livewire chart package    | Simple bar/line charts                   | Yes        |

### Data Queries

**Live ops list:**

```sql
SELECT u.name, ou.name as team,
  ws.started_at, ws.ended_at,
  lp.latitude, lp.longitude, lp.recorded_at, lp.accuracy,
  v.status as visit_status, c.name as customer_name
FROM users u
LEFT JOIN work_sessions ws ON ws.user_id = u.id AND ws.date = CURDATE()
LEFT JOIN location_pings lp ON lp.user_id = u.id AND lp.recorded_at = (
  SELECT MAX(recorded_at) FROM location_pings WHERE user_id = u.id AND date = CURDATE()
)
LEFT JOIN visits v ON v.user_id = u.id AND v.status = 'open'
LEFT JOIN customers c ON c.id = v.customer_id
LEFT JOIN organization_unit_user ouu ON ouu.user_id = u.id
LEFT JOIN organization_units ou ON ou.id = ouu.organization_unit_id
WHERE u.company_id = ? AND u.is_active = 1
```

**Rep performance:**

```sql
-- Sales
SELECT SUM(i.total) as sales_actual
FROM invoices i
WHERE i.user_id = ? AND i.posting_date BETWEEN ? AND ? AND i.status != 'cancelled'

-- Visits
SELECT COUNT(*) as total, SUM(CASE WHEN v.status = 'closed' THEN 1 ELSE 0 END) as completed
FROM visits v
WHERE v.user_id = ? AND v.checkin_at BETWEEN ? AND ?
```

---

## TASKS

### Milestone 1: Live Operations List (2 days)

| #    | Task                                                    | Files                                                  | Tests                   | Status  |
| ---- | ------------------------------------------------------- | ------------------------------------------------------ | ----------------------- | ------- |
| 1.1  | New Livewire component `LiveOpsList`                    | `app/Livewire/App/LiveOpsList.php`                     | Component renders       | Pending |
| 1.2  | Blade: table with real-time status                      | `resources/views/livewire/app/live-ops-list.blade.php` | Table renders           | Pending |
| 1.3  | Status computation logic (Visit/Travel/Delayed/Offline) | `app/Livewire/App/LiveOpsList.php`                     | All 4 statuses computed | Pending |
| 1.4  | Filters: status, team, shift                            | `app/Livewire/App/LiveOpsList.php`                     | Filters work            | Pending |
| 1.5  | Pagination (20 per page)                                | `app/Livewire/App/LiveOpsList.php`                     | Pagination works        | Pending |
| 1.6  | `wire:poll.30s` for real-time                           | `resources/views/livewire/app/live-ops-list.blade.php` | Updates automatically   | Pending |
| 1.7  | Link to rep profile                                     | `resources/views/livewire/app/live-ops-list.blade.php` | Click navigates         | Pending |
| 1.8  | "Open Map" button → RepLiveMap                          | `resources/views/livewire/app/live-ops-list.blade.php` | Navigates to map        | Pending |
| 1.9  | Export CSV                                              | `app/Livewire/App/LiveOpsList.php`                     | CSV downloads           | Pending |
| 1.10 | Add to admin panel nav                                  | `app/Providers/Filament/AdminPanelProvider.php`        | Nav item visible        | Pending |
| 1.11 | Pest test: live ops list                                | `tests/Feature/LiveOpsListTest.php`                    | All flows tested        | Pending |

### Milestone 2: Rep Profile (3 days)

| #    | Task                                                  | Files                                                | Tests               | Status  |
| ---- | ----------------------------------------------------- | ---------------------------------------------------- | ------------------- | ------- |
| 2.1  | New Livewire component `RepProfile`                   | `app/Livewire/App/RepProfile.php`                    | Component renders   | Pending |
| 2.2  | Blade: tabbed layout with Alpine.js                   | `resources/views/livewire/app/rep-profile.blade.php` | Tabs switch         | Pending |
| 2.3  | Overview tab: identity, role, today's summary         | `resources/views/livewire/app/rep-profile.blade.php` | Data shows          | Pending |
| 2.4  | Assignments tab: customer list with last visit        | `app/Livewire/App/RepProfile.php`                    | Customers listed    | Pending |
| 2.5  | Performance tab: targets vs actual with progress bars | `app/Livewire/App/RepProfile.php`                    | Metrics calculated  | Pending |
| 2.6  | Activity tab: shift history table                     | `app/Livewire/App/RepProfile.php`                    | Shifts listed       | Pending |
| 2.7  | Security tab: devices, sessions, login history        | `app/Livewire/App/RepProfile.php`                    | Security data shown | Pending |
| 2.8  | Performance chart (Chart.js)                          | `resources/views/livewire/app/rep-profile.blade.php` | Chart renders       | Pending |
| 2.9  | Add route `GET /admin/reps/{user}`                    | `routes/web.php`                                     | Route accessible    | Pending |
| 2.10 | Link from UserResource and LiveOpsList                | Filament + Livewire views                            | Links work          | Pending |
| 2.11 | Pest test: rep profile tabs                           | `tests/Feature/RepProfileTest.php`                   | All tabs tested     | Pending |

### Milestone 3: Customer Detail Tabs (2 days)

| #    | Task                                                  | Files                                                    | Tests              | Status  |
| ---- | ----------------------------------------------------- | -------------------------------------------------------- | ------------------ | ------- |
| 3.1  | New Livewire component `CustomerDetail`               | `app/Livewire/App/CustomerDetail.php`                    | Component renders  | Pending |
| 3.2  | Blade: tabbed layout                                  | `resources/views/livewire/app/customer-detail.blade.php` | Tabs switch        | Pending |
| 3.3  | Overview tab: identity, financial, location, actions  | `resources/views/livewire/app/customer-detail.blade.php` | Data shows         | Pending |
| 3.4  | Outlets tab: outlet list with contacts                | `app/Livewire/App/CustomerDetail.php`                    | Outlets listed     | Pending |
| 3.5  | Visits tab: visit history table                       | `app/Livewire/App/CustomerDetail.php`                    | Visits listed      | Pending |
| 3.6  | Orders tab: order list with status + PDF              | `app/Livewire/App/CustomerDetail.php`                    | Orders listed      | Pending |
| 3.7  | Collections tab: collection history                   | `app/Livewire/App/CustomerDetail.php`                    | Collections listed | Pending |
| 3.8  | Statement tab: full account statement (reuse Phase 1) | `app/Livewire/App/CustomerDetail.php`                    | Statement shows    | Pending |
| 3.9  | Quick actions: Order, Payment, Return, Task           | `resources/views/livewire/app/customer-detail.blade.php` | Actions navigate   | Pending |
| 3.10 | Add route `GET /admin/customers/{customer}`           | `routes/web.php`                                         | Route accessible   | Pending |
| 3.11 | Link from CustomerResource                            | Filament views                                           | Links work         | Pending |
| 3.12 | Pest test: customer detail tabs                       | `tests/Feature/CustomerDetailTest.php`                   | All tabs tested    | Pending |

### Milestone 4: Route Map (2 days)

| #    | Task                                          | Files                                              | Tests                | Status  |
| ---- | --------------------------------------------- | -------------------------------------------------- | -------------------- | ------- |
| 4.1  | New Livewire component `RouteMap`             | `app/Livewire/App/RouteMap.php`                    | Component renders    | Pending |
| 4.2  | Blade: Leaflet map container                  | `resources/views/livewire/app/route-map.blade.php` | Map loads            | Pending |
| 4.3  | Load today's assignments + customer locations | `app/Livewire/App/RouteMap.php`                    | Markers positioned   | Pending |
| 4.4  | Polyline connecting stops in order            | `resources/views/livewire/app/route-map.blade.php` | Route line visible   | Pending |
| 4.5  | User position marker (blue dot)               | `resources/views/livewire/app/route-map.blade.php` | Position shows       | Pending |
| 4.6  | Current stop star marker                      | `resources/views/livewire/app/route-map.blade.php` | Star shows           | Pending |
| 4.7  | Completed/missed markers                      | `resources/views/livewire/app/route-map.blade.php` | Markers correct      | Pending |
| 4.8  | "Navigate to Next" → Google Maps intent       | `resources/views/livewire/app/route-map.blade.php` | Opens maps app       | Pending |
| 4.9  | "List View" toggle                            | `resources/views/livewire/app/route-map.blade.php` | Switches view        | Pending |
| 4.10 | Add route `GET /app/route-map`                | `routes/web.php`                                   | Route accessible     | Pending |
| 4.11 | Wire from Home/ActiveShift                    | Livewire views                                     | Button visible       | Pending |
| 4.12 | Pest test: route map                          | `tests/Feature/RouteMapTest.php`                   | Markers + navigation | Pending |

### Milestone 5: Active Shift Screen (1 day)

| #    | Task                                         | Files                                                 | Tests             | Status  |
| ---- | -------------------------------------------- | ----------------------------------------------------- | ----------------- | ------- |
| 5.1  | New Livewire component `ActiveShift`         | `app/Livewire/App/ActiveShift.php`                    | Component renders | Pending |
| 5.2  | Blade: shift status display                  | `resources/views/livewire/app/active-shift.blade.php` | Layout renders    | Pending |
| 5.3  | Duration calculation (live)                  | `app/Livewire/App/ActiveShift.php`                    | Duration updates  | Pending |
| 5.4  | Distance calculation (haversine sum)         | `app/Services/LocationPingService.php`                | Distance correct  | Pending |
| 5.5  | Progress: visits, orders, collections, tasks | `app/Livewire/App/ActiveShift.php`                    | Counts correct    | Pending |
| 5.6  | Unsynchronized count                         | `app/Livewire/App/ActiveShift.php`                    | Count from outbox | Pending |
| 5.7  | Tracking status indicator                    | `resources/views/livewire/app/active-shift.blade.php` | Status shows      | Pending |
| 5.8  | `wire:poll.30s` for live updates             | `resources/views/livewire/app/active-shift.blade.php` | Updates live      | Pending |
| 5.9  | Add route `GET /app/active-shift`            | `routes/web.php`                                      | Route accessible  | Pending |
| 5.10 | Wire from Home screen                        | `resources/views/livewire/app/home.blade.php`         | Button visible    | Pending |
| 5.11 | Pest test: active shift                      | `tests/Feature/ActiveShiftTest.php`                   | All data correct  | Pending |

### Milestone 6: Integration (1 day)

| #   | Task                                   | Files              | Tests              | Status  |
| --- | -------------------------------------- | ------------------ | ------------------ | ------- |
| 6.1 | Update QA test script with new screens | `jawla_full_qa.py` | New screens tested | Pending |
| 6.2 | Add nav items for new pages            | Layout files       | Nav complete       | Pending |
| 6.3 | Run `make verify`                      | Terminal           | All tests pass     | Pending |
| 6.4 | Deploy to staging                      | `railway up`       | Staging works      | Pending |

---

## RISKS

| Risk                                     | Impact                           | Mitigation                                                |
| ---------------------------------------- | -------------------------------- | --------------------------------------------------------- |
| Live ops list performance with many reps | Slow queries on large datasets   | Index on `location_pings(user_id, recorded_at)`, paginate |
| Rep profile chart rendering              | Chart.js adds ~200KB             | Lazy-load chart only when Performance tab active          |
| Route map with many stops                | Cluttered markers                | Limit to today's assignments only (max ~20)               |
| Distance calculation accuracy            | GPS noise affects total          | Use minimum ping interval (30s), filter outliers          |
| Customer detail query complexity         | Multiple tabs = multiple queries | Load tabs on demand, not all at once                      |
| Alpine.js tab state on navigation        | Tab state lost on page change    | Store active tab in URL query parameter                   |

---

```yaml
plan_result:
  scope:
    [live-ops-list, rep-profile, customer-detail-tabs, route-map, active-shift]
  non_goals:
    [
      territory-management,
      route-calendar,
      route-optimization,
      supervisor-mobile-dashboard,
    ]
  acceptance_criteria_count: 52
  architecture_decisions:
    [
      alpine-tabs,
      leaflet-self-hosted,
      wire-poll-realtime,
      lazy-tab-loading,
      haversine-distance,
    ]
  milestones:
    [
      live-ops-list,
      rep-profile,
      customer-detail,
      route-map,
      active-shift,
      integration,
    ]
  critical_path: [live-ops-list → rep-profile → customer-detail]
  approval_gates:
    [after-milestone-2-rep-profile, after-milestone-5-active-shift]
  risks:
    [
      query-performance,
      chart-weight,
      marker-clutter,
      gps-noise,
      tab-complexity,
      state-persistence,
    ]
  documents_written: [this-plan]
  next_vertical_slice: Milestone 1 — Live Operations List
  recommended_next_skill: v-implementation-strategist
```
