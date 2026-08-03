# Phase 4: Advanced Features

## PRD

### Problem

Jawla lacks configurable checklists for visits/tasks, a form builder for dynamic data collection, a route calendar for planning, territory management with map boundaries, platform admin for multi-tenant operations, and scheduled reports. These are P1-P2 features that complete the Field Command spec.

### Users

- **Admin** (web): Needs form builder, territory management, scheduled reports
- **Sales Manager** (web): Needs route calendar for planning, configurable checklists
- **Platform Super Admin**: Needs tenant management, health dashboard, support access
- **Sales Rep** (mobile): Needs configurable checklists on visit/task forms

### Outcomes

1. Admin can create configurable checklists with conditional logic
2. Admin can build custom forms with drag-and-drop
3. Managers can plan routes via calendar view
4. Admin can manage territories on a map
5. Platform admin can manage tenants, subscriptions, health
6. Reports can be scheduled and auto-delivered

### Non-Goals

- AI-assisted features (future)
- Advanced fraud detection (future)
- ERP integrations beyond webhooks (future)
- Bluetooth printing (future)

---

## SPEC

### 1. Configurable Checklists (M-18, W-52)

**Actor:** Admin
**Precondition:** User has `admin` role

**Form Builder Screen:**

```
┌──────────────────────────────────────────────────────────────────┐
│ Form Builder: Visit Checklist - Zone A                           │
├──────────────────────────────────────────────────────────────────┤
│ Name: [Visit Checklist - Zone A         ]                        │
│ Type: [Visit ▾]                                                  │
│ Applies to: [All customers ▾] or [Specific group ▾]              │
│                                                                  │
│ ── Fields ──                                                     │
│ ┌──────────────────────────────────────────────────────────────┐ │
│ │ 1. [Product Display Photo ▾] [Photo] [Required ✓]          │ │
│ │    Condition: [If product available ▾]                       │ │
│ ├──────────────────────────────────────────────────────────────┤ │
│ │ 2. [Stock Level ▾] [Number] [Required ✓]                    │ │
│ │    Validation: [Min: 0] [Max: 9999]                         │ │
│ ├──────────────────────────────────────────────────────────────┤ │
│ │ 3. [Competitor Products Seen ▾] [Yes/No]                    │ │
│ │    Condition: [Always]                                       │ │
│ ├──────────────────────────────────────────────────────────────┤ │
│ │ 4. [Competitor Details ▾] [Long Text]                       │ │
│ │    Condition: [If Q3 = Yes]                                  │ │
│ ├──────────────────────────────────────────────────────────────┤ │
│ │ 5. [Customer Satisfaction ▾] [Rating 1-5] [Required ✓]     │ │
│ │    Condition: [Always]                                       │ │
│ └──────────────────────────────────────────────────────────────┘ │
│ [+ Add Field]                                                    │
│                                                                  │
│ [Preview] [Save] [Publish]                                       │
└──────────────────────────────────────────────────────────────────┘
```

**Field types:**

| Type           | Input                   | Validation        |
| -------------- | ----------------------- | ----------------- |
| Short Text     | Text input              | Max length        |
| Long Text      | Textarea                | Max length        |
| Number         | Number input            | Min/Max           |
| Currency       | Number input + currency | Min/Max           |
| Date           | Date picker             | —                 |
| Time           | Time picker             | —                 |
| Single Select  | Dropdown                | Options list      |
| Multi Select   | Checkboxes              | Options list      |
| Yes/No         | Toggle                  | —                 |
| Rating         | Star rating             | Max stars         |
| Photo          | Camera capture          | Required/optional |
| File           | File upload             | Types, size limit |
| Product Select | Product search          | Category filter   |
| Contact Select | Contact search          | —                 |

**Conditional logic:**

- Each field can have a condition: "Show when [field] [equals/not equals] [value]"
- Conditions are evaluated client-side (Alpine.js)
- Nested conditions supported (AND only, no OR for MVP)

**Data Model:**

```php
Schema::create('form_definitions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained();
    $table->string('name');
    $table->string('type'); // visit, task, custom
    $table->json('conditions'); // {applies_to: "all"|"group", group_id: null}
    $table->boolean('is_published')->default(false);
    $table->timestamps();
});

Schema::create('form_fields', function (Blueprint $table) {
    $table->id();
    $table->foreignId('form_definition_id')->cascadeOnDelete();
    $table->unsignedSmallInteger('sort_order');
    $table->string('label');
    $table->string('type'); // short_text, long_text, number, etc.
    $table->json('config'); // {required, min, max, options, etc}
    $table->json('condition')->nullable(); // {field_id, operator, value}
    $table->timestamps();
});

Schema::create('form_submissions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained();
    $table->foreignId('form_definition_id')->constrained();
    $table->morphs('submittable'); // visit, task, etc.
    $table->foreignId('user_id')->constrained();
    $table->json('data'); // {field_id: value, ...}
    $table->timestamps();
});
```

**Mobile rendering:**

- Form fields rendered dynamically in VisitFlow/TaskDetail
- Conditional fields shown/hidden via Alpine.js
- Photo capture via existing `PhotoCapture` component
- Data stored in `form_submissions` table

---

### 2. Route Calendar (W-13)

**Actor:** Sales Manager
**Precondition:** User has `routes.manage` permission

**Screen:**

```
┌──────────────────────────────────────────────────────────────────┐
│ Route Calendar                    [Day] [Week] [Month]          │
├──────────────────────────────────────────────────────────────────┤
│         │ Mon 04    │ Tue 05    │ Wed 06    │ Thu 07    │ Fri 08│
│─────────┼───────────┼───────────┼───────────┼───────────┼───────│
│ Ahmed   │ ▪ Ahmed T │ ▪ Sara S  │ ▪ Khaled  │           │       │
│         │ ▪ Khaled  │ ▪ Ahmed T │ ▪ Mona H  │           │       │
│         │ ▪ Mona H  │ ▪ Omar Z  │           │           │       │
│─────────┼───────────┼───────────┼───────────┼───────────┼───────│
│ Sara    │ ▪ Ali Co  │ ▪ Fatima  │ ▪ Hassan  │           │       │
│         │ ▪ Omar Z  │ ▪ Ali Co  │ ▪ Ali Co  │           │       │
│─────────┼───────────┼───────────┼───────────┼───────────┼───────│
│ Omar    │ ▪ Mona H  │ ▪ Khaled  │ ▪ Ahmed T │           │       │
│         │ ▪ Hassan  │ ▪ Mona H  │ ▪ Sara S  │           │       │
└──────────────────────────────────────────────────────────────────┘
│ [Create Visit] [Copy Route] [Apply Template] [Bulk Assign]      │
```

**Views:**

- Day: single rep, timeline view with time slots
- Week: grid with reps as rows, days as columns
- Month: calendar with visit dots per day

**Interactions:**

- Click cell → create visit or view existing
- Drag to reschedule (week/month view)
- "Copy Route" → duplicate today's assignments to another date
- "Apply Template" → load from route template
- "Bulk Assign" → assign multiple customers to multiple reps

**Warnings:**

- Rep unavailable (on leave)
- Customer closed ( holiday)
- Route overload (>10 stops)
- Duplicate visit (same customer same day)

**Data:**

- Uses existing `daily_visit_assignments` table
- New `route_templates` table for saved templates

---

### 3. Territory Management (W-12)

**Actor:** Admin
**Precondition:** User has `admin` role

**Screen:**

```
┌──────────────────────────────────────────────────────────────────┐
│ Territory Management                                             │
├──────────────────────────────────────────────────────────────────┤
│ ┌──────────────────────────────┬────────────────────────────────┐│
│ │                              │ Territory: Cairo-A             ││
│ │    [Map with territories]    │ Rep: Ahmed Ibrahim             ││
│ │                              │ Customers: 25                  ││
│ │    ┌─────────┐               │ Routes: Cairo-A                ││
│ │    │ Cairo-A │               │                                ││
│ │    └─────────┘               │ [Edit] [Delete] [Reassign]    ││
│ │         ┌──────┐             │                                ││
│ │         │Cairo-B│            │                                ││
│ │         └──────┘             │                                ││
│ │                              │                                ││
│ └──────────────────────────────┴────────────────────────────────┘│
│                                                                  │
│ [+ Create Territory] [Import Boundaries] [Detect Overlaps]       │
└──────────────────────────────────────────────────────────────────┘
```

**Map features:**

- Leaflet map with territory polygons
- Click territory → select and show details
- Draw new territory (polygon tool)
- Import GeoJSON boundaries
- Detect overlapping territories
- Show customer count per territory
- Show rep assignment per territory

**Data Model:**

```php
Schema::table('territories', function (Blueprint $table) {
    $table->json('boundary')->nullable()->after('name_en'); // GeoJSON polygon
    $table->foreignId('assigned_rep_id')->nullable()->constrained('users')->after('boundary');
    $table->string('color', 7)->nullable()->after('assigned_rep_id'); // hex color for map
});
```

**Behavior:**

- Draw polygon → store as GeoJSON in `territories.boundary`
- Assign rep → create `customer_assignments` for customers within boundary
- Detect overlaps → check polygon intersections (postGIS or manual bbox check)

---

### 4. Platform Administration (P-01 to P-06)

**Actor:** Platform Super Admin
**Precondition:** User has `super_admin` role

**Note:** This is a separate Filament panel (`platform`) or a dedicated admin section.

#### P-01: Tenant List

```
┌──────────────────────────────────────────────────────────────────┐
│ Tenants                                                          │
├──────────────────────────────────────────────────────────────────┤
│ ┌─ Name ──────── Plan ── Users ── Status ── Storage ── Last ──┐ │
│ │ GPC Corp       Pro     15      Active   2.3 GB    2h ago   │ │
│ │ Sara Trading   Basic   5       Active   800 MB    1d ago   │ │
│ │ Hassan Ltd     Pro     22      Active   4.1 GB    30m ago  │ │
│ │ Omar & Sons    Trial   3       Active   120 MB    3d ago   │ │
│ └─────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
```

#### P-04: Platform Health

```
┌──────────────────────────────────────────────────────────────────┐
│ Platform Health                        Last check: 2 min ago     │
├──────────────────────────────────────────────────────────────────┤
│ ┌─ Metric ──────────── Status ── Value ──────────────────────┐  │
│ │ API Availability    🟢        99.97% (30d)                 │  │
│ │ Sync Success        🟢        98.5% (24h)                  │  │
│ │ Queue Depth         🟢        12 pending                   │  │
│ │ Database            🟢        45ms avg query time           │  │
│ │ Object Storage      🟢        12.5 GB used                  │  │
│ │ Error Rate          🟡        0.3% (threshold: 0.5%)       │  │
│ │ Push Notifications  🟢        99.2% delivery                │  │
│ └──────────────────────────────────────────────────────────────┘  │
│                                                                  │
│ [View Logs] [View Metrics History] [Export Report]               │
└──────────────────────────────────────────────────────────────────┘
```

#### P-05: Support Access

```
┌──────────────────────────────────────────────────────────────────┐
│ Support Access Request                                           │
├──────────────────────────────────────────────────────────────────┤
│ Tenant: [GPC Corp ▾]                                             │
│ Ticket: [#SUP-0045]                                               │
│ Reason: [Investigating sync issue for user Ahmed  ]              │
│ Scope: [Read-only ▾]                                              │
│ Duration: [4 hours ▾]                                             │
│ Approver: [Platform Admin ▾]                                      │
│                                                                  │
│ [Request Access]                                                  │
│                                                                  │
│ ── Active Access ──                                               │
│ GPC Corp · Read-only · Expires in 2h · Admin: Ahmed              │
│ [Revoke]                                                          │
└──────────────────────────────────────────────────────────────────┘
```

**Data Model (new tables):**

```php
Schema::create('platform_tenants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained();
    $table->string('plan'); // trial, basic, pro, enterprise
    $table->json('features');
    $table->integer('max_users');
    $table->bigInteger('storage_limit_mb');
    $table->timestamp('trial_ends_at')->nullable();
    $table->string('status'); // active, suspended, expired
    $table->timestamps();
});

Schema::create('support_access_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained();
    $table->foreignId('requested_by')->constrained('users');
    $table->foreignId('approved_by')->nullable()->constrained('users');
    $table->string('ticket_number');
    $table->text('reason');
    $table->string('scope'); // read_only, full
    $table->integer('duration_hours');
    $table->timestamp('expires_at');
    $table->string('status'); // pending, active, revoked, expired
    $table->timestamps();
});
```

---

### 5. Scheduled Reports (W-41)

**Actor:** Sales Manager, Admin
**Precondition:** User has `reports.view` permission

**Screen:**

```
┌──────────────────────────────────────────────────────────────────┐
│ Scheduled Reports                      [+ Create Schedule]      │
├──────────────────────────────────────────────────────────────────┤
│ ┌─ Report ────── Recipients ── Frequency ── Next ── Status ──┐ │
│ │ Sales Daily    Ahmed, Sara   Daily 8am    Tomorrow Active   │ │
│ │ Visit Weekly   All managers  Weekly Mon    05 Aug   Active   │ │
│ │ Inventory      Omar          Monthly 1st   01 Sep   Active   │ │
│ └──────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
```

**Schedule Builder:**

```
┌──────────────────────────────────────────────────────────────────┐
│ Schedule: Sales Daily Report                                     │
├──────────────────────────────────────────────────────────────────┤
│ Report: [Sales Summary ▾]                                        │
│ Saved Filters: [Last 24 hours ▾]                                 │
│ Format: [PDF ▾]                                                   │
│                                                                  │
│ Recipients:                                                      │
│ ☑ Ahmed Ibrahim (ahmed@jawla.test)                               │
│ ☑ Sara Hassan (sara@jawla.test)                                  │
│ ☐ Omar Zaki (omar@jawla.test)                                    │
│                                                                  │
│ Frequency: [Daily ▾]                                              │
│ Time: [08:00]                                                     │
│ Timezone: [Africa/Cairo ▾]                                        │
│                                                                  │
│ [Save] [Run Now] [Delete]                                         │
└──────────────────────────────────────────────────────────────────┘
```

**Implementation:**

- Use Laravel Scheduler (`schedule:run` every minute)
- New `scheduled_reports` table stores schedule config
- New `ScheduledReportJob` generates report + emails PDF
- Notification channel: email (MVP), push notification (Phase 5)

---

## USER_JOURNEYS

### Journey 1: Admin Creates Visit Checklist

1. Admin opens Form Builder
2. Creates "Visit Checklist - Zone A"
3. Adds fields: Photo (required), Stock Level (number, required), Competitor Seen (yes/no)
4. Adds conditional: "Competitor Details" shows when "Competitor Seen" = Yes
5. Publishes form
6. Next visit to Zone A customer shows checklist in VisitFlow
7. Rep fills checklist, submits with visit

### Journey 2: Manager Plans Weekly Route

1. Manager opens Route Calendar (Week view)
2. Sees Ahmed has 3 visits on Monday
3. Drags "Khaled Corp" from Wednesday to Tuesday
4. Calendar updates, assignment moved
5. Copies Monday's route to next Monday
6. Applies "Standard Route" template to fill gaps

### Journey 3: Admin Manages Territories

1. Admin opens Territory Management
2. Draws polygon for "New Cairo" area on map
3. Names territory "Cairo-C"
4. Assigns rep "Omar" to territory
5. System auto-assigns 15 customers within polygon to Omar
6. Detects overlap with "Cairo-A" → warns, asks to resolve

### Journey 4: Platform Admin Manages Tenants

1. Platform admin opens Tenant List
2. Sees GPC Corp on Pro plan, 15 users
3. Sees trial expiring for Omar & Sons in 3 days
4. Sends support access request for GPC Corp sync issue
5. Approves own request (self-approval for read-only)
6. Investigates issue in GPC Corp database
7. Revokes access after resolution

### Journey 5: Manager Schedules Reports

1. Manager opens Scheduled Reports
2. Creates "Daily Sales" schedule
3. Selects Sales Summary report, last 24 hours
4. Adds recipients: Ahmed, Sara
5. Sets daily at 8:00 AM Cairo time
6. Saves schedule
7. Next morning: PDF emailed to recipients automatically

---

## ARCHITECTURE

### Decisions

| Decision             | Choice                              | Rationale                                     | Reversible |
| -------------------- | ----------------------------------- | --------------------------------------------- | ---------- |
| Form builder storage | JSON columns for config/conditions  | Flexible, no EAV complexity                   | Yes        |
| Form rendering       | Dynamic Blade component + Alpine.js | No page reload, conditional logic client-side | Yes        |
| Route calendar       | FullCalendar.js or custom grid      | FullCalendar is mature, good drag-drop        | Yes        |
| Territory polygons   | GeoJSON in JSON column              | Simple storage, Leaflet can render            | Yes        |
| Platform admin       | Separate Filament panel             | Different auth, different permissions         | Yes        |
| Scheduled reports    | Laravel Scheduler + Job             | Native, no external service                   | Yes        |
| Report delivery      | Email with PDF attachment           | Universal, no push dependency                 | Yes        |

### New Packages

| Package                     | Purpose                     | Cost       | Lock-in                            |
| --------------------------- | --------------------------- | ---------- | ---------------------------------- |
| `fullcalendar/fullcalendar` | Route calendar (JS)         | Free (MIT) | Low — can replace with custom grid |
| `milon/barcode`             | QR codes for support access | Free (MIT) | Low                                |

No paid packages required.

---

## TASKS

### Milestone 1: Form Builder (5 days)

| #    | Task                                                             | Files                                                  | Tests                  | Status  |
| ---- | ---------------------------------------------------------------- | ------------------------------------------------------ | ---------------------- | ------- |
| 1.1  | Migration: `form_definitions`, `form_fields`, `form_submissions` | `database/migrations/`                                 | Migrations run         | Pending |
| 1.2  | Models: `FormDefinition`, `FormField`, `FormSubmission`          | `app/Models/`                                          | Relationships work     | Pending |
| 1.3  | Filament Resource: `FormDefinitionResource`                      | `app/Filament/Resources/`                              | CRUD works             | Pending |
| 1.4  | Builder UI: field list with drag-and-drop reorder                | `app/Filament/Resources/`                              | Fields reorder         | Pending |
| 1.5  | Field type configs: required, min/max, options                   | `app/Filament/Resources/`                              | Configs save           | Pending |
| 1.6  | Conditional logic editor                                         | `app/Filament/Resources/`                              | Conditions save        | Pending |
| 1.7  | Form preview in admin                                            | `app/Filament/Resources/`                              | Preview renders        | Pending |
| 1.8  | Mobile: dynamic form renderer component                          | `app/Livewire/App/FormRenderer.php`                    | Form renders on mobile | Pending |
| 1.9  | Mobile: conditional field show/hide                              | `resources/views/livewire/app/form-renderer.blade.php` | Conditions evaluate    | Pending |
| 1.10 | Wire into VisitFlow                                              | `app/Livewire/App/VisitFlow.php`                       | Checklist shows        | Pending |
| 1.11 | Wire into TaskDetail                                             | `app/Livewire/App/TaskDetail.php`                      | Checklist shows        | Pending |
| 1.12 | Pest test: form builder + rendering                              | `tests/Feature/FormBuilderTest.php`                    | Full lifecycle tested  | Pending |

### Milestone 2: Route Calendar (4 days)

| #    | Task                                       | Files                                                   | Tests                      | Status  |
| ---- | ------------------------------------------ | ------------------------------------------------------- | -------------------------- | ------- |
| 2.1  | Migration: `route_templates` table         | `database/migrations/`                                  | Migration runs             | Pending |
| 2.2  | Model: `RouteTemplate`                     | `app/Models/`                                           | Relationships work         | Pending |
| 2.3  | New Livewire component `RouteCalendar`     | `app/Livewire/App/RouteCalendar.php`                    | Component renders          | Pending |
| 2.4  | Blade: week view grid with reps × days     | `resources/views/livewire/app/route-calendar.blade.php` | Grid renders               | Pending |
| 2.5  | Day view: single rep timeline              | `resources/views/livewire/app/route-calendar.blade.php` | Timeline renders           | Pending |
| 2.6  | Month view: calendar dots                  | `resources/views/livewire/app/route-calendar.blade.php` | Calendar renders           | Pending |
| 2.7  | Click cell → create/view visit             | `app/Livewire/App/RouteCalendar.php`                    | Create works               | Pending |
| 2.8  | Drag to reschedule                         | `resources/views/livewire/app/route-calendar.blade.php` | Drag works                 | Pending |
| 2.9  | "Copy Route" feature                       | `app/Livewire/App/RouteCalendar.php`                    | Copy works                 | Pending |
| 2.10 | "Apply Template" feature                   | `app/Livewire/App/RouteCalendar.php`                    | Template loads             | Pending |
| 2.11 | Warnings: overload, duplicate, unavailable | `app/Livewire/App/RouteCalendar.php`                    | Warnings show              | Pending |
| 2.12 | Add to admin panel nav                     | `app/Providers/Filament/AdminPanelProvider.php`         | Nav visible                | Pending |
| 2.13 | Pest test: route calendar                  | `tests/Feature/RouteCalendarTest.php`                   | All views + actions tested | Pending |

### Milestone 3: Territory Management (3 days)

| #    | Task                                                                 | Files                               | Tests                 | Status  |
| ---- | -------------------------------------------------------------------- | ----------------------------------- | --------------------- | ------- |
| 3.1  | Migration: add `boundary`, `assigned_rep_id`, `color` to territories | `database/migrations/`              | Migration runs        | Pending |
| 3.2  | Filament Resource update: `TerritoryResource` with map               | `app/Filament/Resources/`           | Map renders           | Pending |
| 3.3  | Leaflet draw tool for polygon creation                               | `resources/views/`                  | Polygon draws         | Pending |
| 3.4  | Store boundary as GeoJSON                                            | `app/Filament/Resources/`           | GeoJSON saves         | Pending |
| 3.5  | Render existing territories as polygons                              | `resources/views/`                  | Polygons show         | Pending |
| 3.6  | Assign rep to territory                                              | `app/Filament/Resources/`           | Assignment saves      | Pending |
| 3.7  | Auto-assign customers within polygon                                 | `app/Services/TerritoryService.php` | Customers assigned    | Pending |
| 3.8  | Overlap detection                                                    | `app/Services/TerritoryService.php` | Overlaps warned       | Pending |
| 3.9  | Import GeoJSON boundaries                                            | `app/Filament/Resources/`           | Import works          | Pending |
| 3.10 | Pest test: territory CRUD + assignment                               | `tests/Feature/TerritoryTest.php`   | Full lifecycle tested | Pending |

### Milestone 4: Platform Admin (5 days)

| #    | Task                                                     | Files                                     | Tests                        | Status  |
| ---- | -------------------------------------------------------- | ----------------------------------------- | ---------------------------- | ------- |
| 4.1  | Migration: `platform_tenants`, `support_access_requests` | `database/migrations/`                    | Migrations run               | Pending |
| 4.2  | Models: `PlatformTenant`, `SupportAccessRequest`         | `app/Models/`                             | Relationships work           | Pending |
| 4.3  | New Filament panel: `platform`                           | `app/Providers/PlatformPanelProvider.php` | Panel boots                  | Pending |
| 4.4  | Tenant Resource: list, create, edit                      | `app/Filament/Resources/`                 | CRUD works                   | Pending |
| 4.5  | Tenant detail: usage stats, health                       | `app/Filament/Resources/`                 | Stats show                   | Pending |
| 4.6  | Platform Health widget                                   | `app/Filament/Widgets/`                   | Metrics display              | Pending |
| 4.7  | Support Access Request flow                              | `app/Filament/Resources/`                 | Request/approve/revoke works | Pending |
| 4.8  | Security Events dashboard                                | `app/Filament/Resources/`                 | Events listed                | Pending |
| 4.9  | Route: `/platform` with auth middleware                  | `routes/web.php`                          | Route accessible             | Pending |
| 4.10 | Role: `super_admin` for platform panel                   | `app/Providers/PlatformPanelProvider.php` | Auth works                   | Pending |
| 4.11 | Pest test: platform admin                                | `tests/Feature/PlatformAdminTest.php`     | All flows tested             | Pending |

### Milestone 5: Scheduled Reports (2 days)

| #   | Task                                             | Files                                   | Tests                 | Status  |
| --- | ------------------------------------------------ | --------------------------------------- | --------------------- | ------- |
| 5.1 | Migration: `scheduled_reports` table             | `database/migrations/`                  | Migration runs        | Pending |
| 5.2 | Model: `ScheduledReport`                         | `app/Models/`                           | Relationships work    | Pending |
| 5.3 | Filament Resource: `ScheduledReportResource`     | `app/Filament/Resources/`               | CRUD works            | Pending |
| 5.4 | Schedule builder: report, recipients, frequency  | `app/Filament/Resources/`               | Builder works         | Pending |
| 5.5 | Job: `ScheduledReportJob` generates PDF + emails | `app/Jobs/`                             | Job runs              | Pending |
| 5.6 | Register in Laravel Scheduler                    | `routes/console.php`                    | Scheduler runs job    | Pending |
| 5.7 | "Run Now" button                                 | `app/Filament/Resources/`               | Immediate run works   | Pending |
| 5.8 | Pest test: scheduled reports                     | `tests/Feature/ScheduledReportTest.php` | Schedule + run tested | Pending |

### Milestone 6: Integration (1 day)

| #   | Task                                       | Files              | Tests              | Status  |
| --- | ------------------------------------------ | ------------------ | ------------------ | ------- |
| 6.1 | Update QA test script with all new screens | `jawla_full_qa.py` | New screens tested | Pending |
| 6.2 | Add nav items for new pages                | Layout files       | Nav complete       | Pending |
| 6.3 | Run `make verify`                          | Terminal           | All tests pass     | Pending |
| 6.4 | Deploy to staging                          | `railway up`       | Staging works      | Pending |

---

## RISKS

| Risk                                  | Impact                               | Mitigation                                              |
| ------------------------------------- | ------------------------------------ | ------------------------------------------------------- |
| Form builder complexity               | Could become full form platform      | Keep to 14 field types, no custom JS                    |
| Calendar drag-drop on mobile          | Poor touch experience                | Mobile uses create modal, not drag                      |
| Territory polygon performance         | Slow rendering with many territories | Limit to 50 territories, cluster markers                |
| Platform admin security               | Different auth surface               | Separate panel, separate middleware, no shared sessions |
| Scheduled report email deliverability | Reports land in spam                 | Use transactional email service (SES), verify SPF/DKIM  |
| GeoJSON import complexity             | Various boundary formats             | Support GeoJSON only, no Shapefile                      |
| FullCalendar JS weight                | ~200KB additional                    | Lazy-load only when calendar page active                |

---

```yaml
plan_result:
  scope:
    [
      form-builder,
      route-calendar,
      territory-management,
      platform-admin,
      scheduled-reports,
    ]
  non_goals:
    [
      ai-features,
      fraud-detection,
      erp-integrations,
      bluetooth-printing,
      parallel-approval,
      delegation,
    ]
  acceptance_criteria_count: 58
  architecture_decisions:
    [
      json-form-config,
      fullcalendar,
      gejson-territories,
      separate-platform-panel,
      laravel-scheduler,
    ]
  milestones:
    [
      form-builder,
      route-calendar,
      territory-management,
      platform-admin,
      scheduled-reports,
      integration,
    ]
  critical_path:
    [
      form-builder-migration → form-builder-resource → mobile-form-renderer → visitflow-integration,
    ]
  approval_gates:
    [after-milestone-1-form-builder, after-milestone-4-platform-admin]
  risks:
    [
      form-complexity,
      calendar-mobile,
      territory-performance,
      platform-security,
      email-deliverability,
      geojson-import,
      calendar-weight,
    ]
  documents_written: [this-plan]
  next_vertical_slice: Milestone 1 — Form Builder Migration + Models
  recommended_next_skill: v-implementation-strategist
```
