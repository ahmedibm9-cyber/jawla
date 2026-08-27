# Jawla (جولة) - Implementation Tasks

## Overview

This document outlines the implementation tasks for the Jawla system, organized into vertical milestones that deliver complete user-visible results. Each task includes dependencies, ownership information, verification methods, and current status.

## Implementation Approach

Tasks are organized using vertical slicing - each milestone delivers a complete, testable user journey rather than completing architectural layers incrementally. This approach ensures:

- Early value delivery
- Testable outcomes at each stage
- Reduced risk through incremental validation
- Clear progress visibility to stakeholders

---

## Milestone 1: Core Field Operations Foundation

_Delivers basic representative daily workflow with visit management_

### Tasks:

#### 1.1 Project Setup and Core Infrastructure

- **Description**: Initialize Laravel project, configure basic authentication, set up development environment
- **Dependencies**: None
- **Owner**: DevOps Engineer
- **Verification**:
  - Project builds successfully
  - Basic authentication works
  - Development environment accessible
- **Status**: Completed
- **Estimated Effort**: 3 days

#### 1.2 Company and User Management

- **Description**: Implement company creation, user registration, role assignment, and basic authentication
- **Dependencies**: 1.1
- **Owner**: Backend Developer
- **Verification**:
  - Company CRUD operations functional
  - User registration and login working
  - Role-based access control enforced
  - Password reset functionality working
- **Status**: Completed
- **Estimated Effort**: 5 days

#### 1.3 Customer Management Basic

- **Description**: Implement customer creation, viewing, and basic search functionality
- **Dependencies**: 1.2
- **Owner**: Backend Developer
- **Verification**:
  - Customer CRUD operations working
  - Search by name, code, phone functional
  - Arabic/English name display working
  - Data validation functioning correctly
- **Status**: Completed
- **Estimated Effort**: 4 days

#### 1.4 Product Management Basic

- **Description**: Implement product catalog creation, viewing, and basic search
- **Dependencies**: 1.2
- **Owner**: Backend Developer
- **Verification**:
  - Product CRUD operations working
  - Search by name, SKU, barcode functional
  - Category management working
  - Data validation functioning correctly
- **Status**: Completed
- **Estimated Effort**: 4 days

#### 1.5 Visit Management Core

- **Description**: Implement visit creation, check-in/check-out, and visit reporting
- **Dependencies**: 1.2, 1.3
- **Owner**: Full Stack Developer
- **Verification**:
  - Visit creation and scheduling working
  - GPS check-in/check-out functional (simulated)
  - Visit report creation with summary, feedback, signature
  - Visit status transitions working correctly
  - Audit trail entries created for all actions
- **Status**: In Progress
- **Estimated Effort**: 8 days
- **Remaining Work**:
  - Offline visit report queuing
  - Signature capture and storage
  - GPS accuracy and distance calculation

#### 1.6 Basic Dashboard and Navigation

- **Description**: Implement representative home screen with daily visits and basic navigation
- **Dependencies**: 1.2, 1.3, 1.5
- **Owner**: Frontend Developer
- **Verification**:
  - Home screen displays today's visits
  - Navigation between visits, customers, orders working
  - Offline indicator visible when disconnected
  - Daily progress metrics displayed
- **Status**: Not Started
- **Estimated Effort**: 3 days

#### 1.7 Data Synchronization Foundation

- **Description**: Implement basic offline detection and queuing mechanism
- **Dependencies**: 1.5
- **Owner**: Backend Developer
- **Verification**:
  - Online/offline detection working
  - Visit reports queued when offline
  - Queued operations sync when connection restored
  - Conflict detection for basic scenarios
- **Status**: Not Started
- **Estimated Effort**: 6 days

### Milestone 1 Acceptance Criteria:

- Representative can log in and view today's scheduled visits
- Representative can check-in to a visit using simulated GPS
- Representative can complete a visit report with summary and signature
- Visit report syncs when connection is restored
- All actions are recorded in audit trail
- Representative can view daily progress metrics

---

## Milestone 2: Sales and Order Processing

_Delivers basic sales order creation and processing capabilities_

### Tasks:

#### 2.1 Price Lists and Product Pricing

- **Description**: Implement price list management and product pricing functionality
- **Dependencies**: 1.4
- **Owner**: Backend Developer
- **Verification**:
  - Price list CRUD operations working
  - Product pricing assignment working
  - Price validation functioning
  - Price list priority rules working
- **Status**: Not Started
- **Estimated Effort**: 5 days

#### 2.2 Sales Order Creation

- **Description**: Implement sales order creation with product selection and pricing
- **Dependencies**: 1.3, 1.4, 2.1
- **Owner**: Full Stack Developer
- **Verification**:
  - Sales order creation from customer context
  - Product selection and quantity entry working
  - Price application and calculation correct
  - Order status transitions working
  - Inventory reservation functioning
- **Status**: Not Started
- **Estimated Effort**: 6 days

#### 2.3 Order Approval Workflow

- **Description**: Implement basic order approval workflow with thresholds
- **Dependencies**: 2.2
- **Owner**: Backend Developer
- **Verification**:
  - Order submission for approval working
  - Automatic approval for orders below threshold
  - Manual approval routing for orders above threshold
  - Approval notifications working
  - Audit trail of approval decisions
- **Status**: Not Started
- **Estimated Effort**: 5 days

#### 2.4 Order Fulfillment and Invoicing

- **Description**: Implement order fulfillment, invoicing, and payment processing
- **Dependencies**: 2.2, 2.3
- **Owner**: Full Stack Developer
- **Verification**:
  - Order fulfillment process working
  - Invoice generation from approved orders
  - Payment recording and application working
  - Inventory deduction on fulfillment
  - Customer balance updates working
- **Status**: Not Started
- **Estimated Effort**: 7 days

#### 2.5 Basic Sales Dashboard and Reports

- **Description**: Implement sales performance dashboard and basic reports
- **Dependencies**: 2.4
- **Owner**: Frontend Developer
- **Verification**:
  - Sales dashboard displays key metrics
  - Standard sales reports generatable
  - Report export to PDF/CSV working
  - Date range filtering functional
- **Status**: Not Started
- **Estimated Effort**: 4 days

### Milestone 2 Acceptance Criteria:

- Representative can create a sales order during a visit
- Order is automatically approved if below threshold
- Approved order generates invoice and reserves inventory
- Invoice can be paid and payment applied correctly
- Sales performance metrics visible on dashboard
- All financial transactions recorded in audit trail

---

## Milestone 3: Financial Management and Collections

_Delivers payment processing, collections, and financial reporting capabilities_

### Tasks:

#### 3.1 Payment Processing Core

- **Description**: Implement payment collection, processing, and application logic
- **Dependencies**: 2.4
- **Owner**: Backend Developer
- **Verification**:
  - Cash payment collection working
  - Card payment processing (simulated) working
  - Bank transfer payment recording working
  - Payment application to invoices working
  - Payment reversal functionality working
- **Status**: Not Started
- **Estimated Effort**: 8 days

#### 3.2 Cash Management

- **Description**: Implement cash box management and reconciliation
- **Dependencies**: 3.1
- **Owner**: Backend Developer
- **Verification**:
  - Cash box creation and funding working
  - Cash receipts recording working
  - Cash disbursements recording working
  - Cash reconciliation process working
  - Cash variance detection and reporting working
- **Status**: Not Started
- **Estimated Effort**: 5 days

#### 3.3 Collections Management

- **Description**: Implement collections management, dunning, and payment follow-up
- **Dependencies**: 3.1
- **Owner**: Backend Developer
- **Verification**:
  - Overdue invoice identification working
  - Collections worklist prioritization working
  - Payment promise tracking working
  - Dispute management functionality working
  - Collections performance reporting working
- **Status**: Not Started
- **Estimated Effort**: 6 days

#### 3.4 Financial Reports and Statements

- **Description**: Implement financial reporting, customer statements, and export capabilities
- **Dependencies**: 3.1, 3.2
- **Owner**: Backend Developer
- **Verification**:
  - Standard financial reports generatable
  - Customer statement generation working
  - Report export to PDF/CSV/Excel working
  - Scheduled report generation working
  - Financial dashboard displaying key metrics
- **Status**: Not Started
- **Estimated Effort**: 6 days

#### 3.5 Expense Management

- **Description**: Implement expense recording, approval, and reimbursement
- **Dependencies**: 3.1
- **Owner**: Backend Developer
- **Verification**:
  - Expense recording with category and amount working
  - Receipt attachment functionality working
  - Expense approval workflow working
  - Reimbursement processing working
  - Expense reporting and analytics working
- **Status**: Not Started
- **Estimated Effort**: 5 days

### Milestone 3 Acceptance Criteria:

- Representative can collect payments in the field (cash, card)
- Payments are correctly applied to invoices
- Customer balances updated appropriately
- Financial reports accurately reflect company financial position
- Expenses can be submitted, approved, and reimbursed
- All financial transactions recorded with full audit trail

---

## Milestone 4: Inventory Management and Stock Control

_Delivers warehouse and vehicle stock management capabilities_

### Tasks:

#### 4.1 Warehouse and Location Management

- **Description**: Implement warehouse creation, management, and location tracking
- **Dependencies**: 1.2
- **Owner**: Backend Developer
- **Verification**:
  - Warehouse CRUD operations working
  - Warehouse type classification working
  - Location management (address, GPS) working
  - Warehouse activation/deactivation working
- **Status**: Not Started
- **Estimated Effort**: 4 days

#### 4.2 Stock Management Core

- **Description**: Implement stock tracking, reservations, and basic movements
- **Dependencies**: 1.4, 4.1
- **Owner**: Backend Developer
- **Verification**:
  - Stock levels tracking working
  - Stock reservations on order approval working
  - Stock deduction on fulfillment working
  - Stock transfers between locations working
  - Available quantity calculation working
- **Status**: Not Started
- **Estimated Effort**: 6 days

#### 4.3 Batch and Lot Tracking

- **Description**: Implement batch/lot tracking and expiration management
- **Dependencies**: 4.2
- **Owner**: Backend Developer
- **Verification**:
  - Batch creation and tracking working
  - Expiration date management working
  - FEFO/FIFO enforcement working
  - Quality status tracking working
  - Expiration alerts and quarantine working
- **Status**: Not Started
- **Estimated Effort**: 5 days

#### 4.4 Stock Counting and Adjustments

- **Description**: Implement stock counting procedures and adjustment workflows
- **Dependencies**: 4.2
- **Owner**: Backend Developer
- **Verification**:
  - Physical stock count process working
  - Count variance detection and reporting working
  - Stock adjustment approval workflow working
  - Inventory reconciliation process working
  - Adjustment audit trail working
- **Status**: Not Started
- **Estimated Effort**: 5 days

#### 4.5 Vehicle Stock Management

- **Description**: Implement representative vehicle stock management
- **Dependencies**: 4.2, 1.5
- **Owner**: Full Stack Developer
- **Verification**:
  - Vehicle stock levels tracking working
  - Stock transfers to/from vehicle working
  - Vehicle stock usage in sales orders working
  - Vehicle stock reconciliation working
  - Vehicle-specific reports working
- **Status**: Not Started
- **Estimated Effort**: 4 days

#### 4.6 Inventory Reports and Alerts

- **Description**: Implement inventory reporting, low stock alerts, and turnover metrics
- **Dependencies**: 4.2, 4.3, 4.4
- **Owner**: Backend Developer
- **Verification**:
  - Standard inventory reports generatable
  - Low stock alerts working
  - Inventory turnover metrics calculated
  - Stock movement reports working
  - Export functionality working
- **Status**: Not Started
- **Estimated Effort**: 4 days

### Milestone 4 Acceptance Criteria:

- Stock levels accurately tracked across warehouse and vehicle locations
- Stock reservations work correctly with order approval process
- Stock transfers between locations functioning properly
- Stock counting and adjustment workflows operational
- Vehicle stock properly utilized in sales and replenished from warehouse
- Inventory reports accurately reflect stock levels and movements
- All inventory transactions recorded in audit trail

---

## Milestone 5: Advanced Features and Integration

_Delivers advanced features, integration capabilities, and system refinements_

### Tasks:

#### 5.1 Approval Workflow Engine Enhancement

- **Description**: Enhance approval engine with complex rules, escalation, and delegation
- **Dependencies**: 2.3, 3.1, 3.4, 4.4
- **Owner**: Backend Developer
- **Verification**:
  - Multi-step approval workflows working
  - Parallel and conditional approval paths working
  - Escalation based on time thresholds working
  - Delegation of approval authority working
  - Complex rule-based approvals working
- **Status**: Not Started
- **Estimated Effort**: 8 days

#### 5.2 Offline Synchronization Enhancement

- **Description**: Enhance offline sync with conflict resolution, priority queuing, and compression
- **Dependencies**: 1.7
- **Owner**: Backend Developer
- **Verification**:
  - Advanced conflict resolution strategies working
  - Priority-based queuing working
  - Data compression for efficient transmission working
  - Large file handling (attachments) working
  - Sync performance metrics and monitoring working
- **Status**: Not Started
- **Estimated Effort**: 6 days

#### 5.3 Integration Framework

- **Description**: Implement REST API, webhooks, and data exchange capabilities
- **Dependencies**: 2.4, 3.1, 4.2
- **Owner**: Backend Developer
- **Verification**:
  - REST API endpoints functional and secured
  - Webhook delivery and retry mechanism working
  - Data import/export (CSV/JSON) working
  - API key management working
  - Rate limiting and throttling working
- **Status**: Not Started
- **Estimated Effort**: 8 days

#### 5.4 Reporting and Analytics Enhancement

- **Description**: Enhance reporting with ad-hoc queries, scheduling, and analytics
- **Dependencies**: 2.5, 3.4, 4.6
- **Owner**: Backend Developer
- **Verification**:
  - Ad-hoc report builder working
  - Report scheduling and distribution working
  - Analytics dashboard with KPIs working
  - Data export in multiple formats working
  - Report caching and performance optimization working
- **Status**: Not Started
- **Estimated Effort**: 6 days

#### 5.5 Notification System Enhancement

- **Description**: Enhance notification system with multiple channels, preferences, and templates
- **Dependencies**: 1.5, 2.4, 3.1
- **Owner**: Full Stack Developer
- **Verification**:
  - Multiple delivery channels (in-app, email, SMS, push, WhatsApp) working
  - User preference management working
  - Notification template system working
  - Delivery tracking and reporting working
  - Spam and abuse prevention working
- **Status**: Not Started
- **Estimated Effort**: 6 days

#### 5.6 Security Hardening and Compliance

- **Description**: Implement security enhancements, audit improvements, and compliance features
- **Dependencies**: All previous milestones
- **Owner**: Security Engineer
- **Verification**:
  - Data encryption at rest and in transit working
  - Enhanced audit trail immutability working
  - Security monitoring and alerting working
  - Vulnerability scanning and penetration testing passed
  - Compliance with relevant regulations verified
- **Status**: Not Started
- **Estimated Effort**: 10 days

#### 5.7 Performance Optimization and Scaling

- **Description**: Optimize performance, implement caching strategies, and prepare for scaling
- **Dependencies**: All previous milestones
- **Owner**: DevOps Engineer
- **Verification**:
  - Response times within targets (<2s for cached, <4s for uncached)
  - Throughput meets requirements (1000+ concurrent users)
  - Caching strategies effective (hit ratios >80%)
  - Database query optimization working
  - Load testing successful
- **Status**: Not Started
- **Estimated Effort**: 8 days

#### 5.8 Localization and Accessibility Completion

- **Description**: Complete Arabic/English localization and accessibility compliance
- **Dependencies**: All UI-related milestones
- **Owner**: Frontend Developer
- **Verification**:
  - Full Arabic RTL support working
  - Complete English LTR support working
  - Localization of all dates, numbers, currencies working
  - Accessibility compliance (WCAG AA) verified
  - Screen reader compatibility working
  - Keyboard navigation working
- **Status**: Not Started
- **Estimated Effort**: 5 days

### Milestone 5 Acceptance Criteria:

- Complex approval workflows with escalation and delegation working
- Offline synchronization handles conflicts and large files efficiently
- REST API and webhooks functional and secured
- Advanced reporting and analytics capabilities delivered
- Notification system with multiple channels and preferences working
- Security enhancements implemented and verified
- Performance targets met and system scalable
- Full Arabic/English localization and accessibility compliance achieved

---

## Milestone 6: Production Readiness and Launch

_Delivers production-ready system with monitoring, documentation, and launch preparation_

### Tasks:

#### 6.1 Production Infrastructure and Deployment

- **Description**: Set up production infrastructure, deployment pipelines, and monitoring
- **Dependencies**: 5.7
- **Owner**: DevOps Engineer
- **Verification**:
  - Production infrastructure provisioned
  - Deployment pipeline automated and tested
  - Monitoring and alerting system working
  - Backup and recovery procedures tested
  - Health checks and status endpoints working
- **Status**: Not Started
- **Estimated Effort**: 5 days

#### 6.2 Documentation and Knowledge Transfer

- **Description**: Create user documentation, administrator guides, and operational runbooks
- **Dependencies**: All milestones
- **Owner**: Technical Writer
- **Verification**:
  - User manuals completed and reviewed
  - Administrator guides completed
  - Operational runbooks completed
  - API documentation completed
  - Training materials prepared
- **Status**: Not Started
- **Estimated Effort**: 5 days

#### 6.3 Security and Compliance Audit

- **Description**: Conduct final security audit, penetration testing, and compliance verification
- **Dependencies**: 5.6
- **Owner**: Security Engineer
- **Verification**:
  - Security audit completed and issues resolved
  - Penetration testing passed
  - Vulnerability scanning clean
  - Compliance with relevant standards verified
  - Audit logs and reports generated
- **Status**: Not Started
- **Estimated Effort**: 5 days

#### 6.4 User Acceptance Testing and Feedback

- **Description**: Conduct UAT with representative users and incorporate feedback
- **Dependencies**: All milestones
- **Owner**: Product Manager
- **Verification**:
  - UAT test cases defined and executed
  - Feedback collected and prioritized
  - Critical issues resolved
  - Usability metrics collected and analyzed
  - User satisfaction measured
- **Status**: Not Started
- **Estimated Effort**: 10 days

#### 6.5 Performance and Load Testing

- **Description**: Conduct performance testing, load testing, and capacity validation
- **Dependencies**: 5.7
- **Owner**: DevOps Engineer
- **Verification**:
  - Load testing meets requirements (1000+ concurrent users)
  - Stress testing identifies breaking points
  - Performance benchmarks established and met
  - Capacity planning documentation completed
  - Performance optimization recommendations implemented
- **Status**: Not Started
- **Estimated Effort**: 5 days

#### 6.6 Launch Preparation and Go/No-Go Decision

- **Description**: Final preparations, go/no-go decision, and launch execution
- **Dependencies**: 6.1, 6.2, 6.3, 6.4, 6.5
- **Owner**: Product Manager
- **Verification**:
  - All pre-launch checklists completed
  - Go/no-go decision made based on criteria
  - Launch execution coordinated
  - Post-launch monitoring established
  - Rollback procedures tested and ready
- **Status**: Not Started
- **Estimated Effort**: 3 days

### Milestone 6 Acceptance Criteria:

- Production infrastructure operational and monitored
- Documentation complete and accessible
- Security and compliance verified
- UAT feedback incorporated and critical issues resolved
- Performance targets met under load
- System ready for launch with go/no-go decision made

---

## Milestone 7: Competitor Gap Closure (Bricks Rep Parity)

_Closes all gaps identified in competitor analysis against Bricks Rep (gpccompany.bricks-rep.com)_

**Source:** `docs/COMPETITOR_GAP_ANALYSIS.md`  
**Date:** 2026-08-23

### Gap Summary

| Priority | Feature                             | Gap Severity | Jawla Advantage Preserved  |
| -------- | ----------------------------------- | ------------ | -------------------------- |
| HIGH     | Calendar view                       | Missing      | —                          |
| HIGH     | Todos/Tasks system                  | Missing      | —                          |
| HIGH     | Performance dashboard               | Basic only   | Invoicing, payments, stock |
| MEDIUM   | Agenda view                         | Missing      | —                          |
| MEDIUM   | Tickets system                      | Missing      | —                          |
| MEDIUM   | Requests system                     | Missing      | —                          |
| MEDIUM   | Non-planned visit recording         | Missing      | —                          |
| LOW      | Contacts views (kanban/grid/export) | Basic only   | —                          |
| LOW      | Calls tracking                      | Missing      | —                          |
| LOW      | Customers summary report            | Basic only   | —                          |

---

### 7.1 Calendar View

- **Description**: Add a monthly calendar page to the Rep PWA showing visits, todos, and tasks by date
- **Dependencies**: 1.5 (Visit Management), 1.6 (Dashboard)
- **Owner**: Frontend Developer
- **Spec**:
  - Monthly calendar grid (7 columns, 5-6 rows)
  - Prev/Next month navigation + Today button
  - Each day cell shows dot indicators for: visits (blue), todos (orange), tickets (red)
  - Tap a day → expands to show that day's items list
  - Filter by type (visits/todos/tickets)
  - Date range param in URL for deep-linking (`?from=2026-08-01&to=2026-08-31`)
  - RTL support: week starts Sunday (Arabic convention)
- **Data sources**:
  - `daily_visit_assignments` → scheduled_date
  - `todos` → due_date (new table, see 7.2)
  - `tickets` → created_at (new table, see 7.5)
- **Verification**:
  - Calendar renders with correct month/year
  - Visits appear on correct dates
  - Todos appear on correct dates
  - Navigation between months works
  - Today button returns to current month
  - RTL layout correct
  - Filter toggles work
- **Estimated Effort**: 3 days

### 7.2 Todos/Tasks System

- **Description**: Standalone todo/task management with New/Done states, integrated into daily agenda
- **Dependencies**: 1.2 (Users)
- **Owner**: Full Stack Developer
- **Spec**:
  - New `todos` table:
    - `id`, `company_id`, `user_id`, `title`, `description`, `priority` (low/medium/high), `status` (new/done), `due_date`, `completed_at`, `is_active`, timestamps
  - Rep PWA Todos page:
    - Two tabs: "New" / "Done"
    - Add new todo: title (required), description (optional), priority, due date
    - Complete: tap checkbox → status = done, completed_at = now
    - Filter by priority, date range
    - Search by title
  - Admin Filament resource: full CRUD for managing todos across all reps
  - Integration with Calendar (7.1) and Agenda (7.4)
- **Verification**:
  - Can create a todo with title, priority, due date
  - Todo appears in "New" tab
  - Can mark todo as done → moves to "Done" tab
  - Todos appear on Calendar on due_date
  - Todos appear in Agenda view
  - RTL support works
  - Filter and search work
- **Estimated Effort**: 4 days

### 7.3 Performance Dashboard

- **Description**: Rich performance metrics page with Overview, Analysis, Daily, Detailed, and Coverage tabs
- **Dependencies**: 1.5 (Visits), 2.4 (Invoicing), 3.1 (Payments)
- **Owner**: Frontend Developer + Backend Developer
- **Spec**:
  - New `performance_metrics` computed endpoint (or use existing visit/invoice/payment data)
  - **Overview tab**:
    - Period selector (month nav: prev/next)
    - Members filter (select rep or "all")
    - Metric cards: Coverage %, Frequency, Call rate, Plan achievement %
    - Summary: working days, total expenses, total visits, detailing count
  - **Analysis tab**:
    - Trend charts (line) for key metrics over time
    - Comparison: this month vs last month
  - **Daily tab**:
    - Day-by-day breakdown table
    - Columns: date, visits planned, visits completed, coverage %, orders count, amount
  - **Detailed tab**:
    - Per-rep breakdown with all metrics
    - Sortable by any metric
    - Export to CSV
  - **Coverage tab**:
    - Map view showing visited vs planned locations
    - Coverage heatmap by territory
  - **Metric calculations**:
    - Coverage = visits_completed / visits_planned × 100
    - Frequency = total_visits / working_days
    - Call rate = total_visits / total_customers_assigned
    - Plan achievement = orders_completed / orders_target × 100
  - **Backend endpoints**:
    - `GET /app/performance/overview?period=2026-08`
    - `GET /app/performance/analysis?period=2026-08`
    - `GET /app/performance/daily?period=2026-08`
    - `GET /app/performance/detailed?period=2026-08`
    - `GET /app/performance/coverage?period=2026-08`
- **Verification**:
  - All 5 tabs render correctly
  - Metrics calculate correctly from underlying data
  - Period navigation works
  - Member filter works
  - CSV export works
  - RTL support works
  - Charts render correctly
- **Estimated Effort**: 6 days

### 7.4 Agenda View

- **Description**: Daily agenda combining visits, todos, and tasks in a single timeline view
- **Dependencies**: 7.1 (Calendar), 7.2 (Todos), 1.5 (Visits)
- **Owner**: Frontend Developer
- **Spec**:
  - New page: `/app/agenda`
  - Date selector (prev/next day + today)
  - Sections:
    - **Planned Visits**: from `daily_visit_assignments` for selected date
    - **Recorded Visits**: completed visits for selected date
    - **Todos**: todos due on selected date
  - Each item shows: time, customer name (for visits), title (for todos), status badge
  - "Record Non-planned Visit" button (see 7.7)
  - Tap item → navigates to detail (visit report or todo edit)
  - RTL support
- **Verification**:
  - Agenda shows correct date's items
  - Planned visits listed with customer names
  - Completed visits shown with status
  - Todos due today shown
  - Navigation between days works
  - Non-planned visit button works
  - RTL layout correct
- **Estimated Effort**: 3 days

### 7.5 Tickets System

- **Description**: Support ticket creation and tracking with status workflow
- **Dependencies**: 1.2 (Users), 1.3 (Customers)
- **Owner**: Full Stack Developer
- **Spec**:
  - New `tickets` table:
    - `id`, `company_id`, `user_id` (creator), `customer_id` (optional), `title`, `description`, `status` (new/disabled/in_progress/completed/cancelled), `priority`, `assigned_to`, `resolved_at`, timestamps
  - Rep PWA Tickets page:
    - Table view + Kanban view toggle
    - Search by title, filter by status
    - Add New Ticket: title, description, customer (optional), priority
    - Status workflow: جديد → قيد التنفيذ → اكتمل (or ملغي / معطل)
    - Tap ticket → detail view with status change button
  - Admin Filament resource: full CRUD, assign tickets to reps/managers
  - Status history audit trail
- **Verification**:
  - Can create a ticket
  - Ticket appears in list
  - Can change status through workflow
  - Table/Kanban toggle works
  - Search and filter work
  - Admin can assign tickets
  - RTL support works
- **Estimated Effort**: 4 days

### 7.6 Requests System

- **Description**: Request/approval workflow for manager sign-off (e.g., discount requests, leave requests, price overrides)
- **Dependencies**: 1.2 (Users), 5.1 (Approval engine, or simple version)
- **Owner**: Full Stack Developer
- **Spec**:
  - New `requests` table:
    - `id`, `company_id`, `user_id` (requester), `type` (discount/leave/price_override/other), `title`, `description`, `status` (new/approved/rejected/done), `reviewed_by`, `reviewed_at`, `review_notes`, timestamps
  - Rep PWA Requests page:
    - Table view + Kanban view toggle
    - Search, filter by status/type
    - Add New Request: type, title, description
    - Status workflow: جديد → تم الموافقة → تم (or تم الرفض)
  - Manager approval interface:
    - View pending requests
    - Approve/reject with notes
  - Admin Filament resource: full CRUD
- **Verification**:
  - Can create a request
  - Request appears in list
  - Manager can approve/reject
  - Status changes correctly
  - Table/Kanban toggle works
  - RTL support works
- **Estimated Effort**: 4 days

### 7.7 Non-Planned Visit Recording

- **Description**: Allow reps to record visits outside the planned route from the Agenda page
- **Dependencies**: 1.5 (Visits), 7.4 (Agenda)
- **Owner**: Full Stack Developer
- **Spec**:
  - "Record Non-planned Visit" button on Agenda page
  - Quick form:
    - Customer (searchable dropdown)
    - Purpose (dropdown: sales, service, follow-up, other)
    - Notes (optional)
  - Creates visit record with:
    - `is_out_of_route = true`
    - `status = completed`
    - GPS coordinates captured at submission
    - `scheduled_date = today`
  - Non-planned visits appear in Agenda under "Recorded Visits"
  - Admin sees non-planned visits flagged differently in reports
- **Verification**:
  - Button visible on Agenda page
  - Can select customer and submit
  - Visit created with correct flags
  - Appears in Agenda and Calendar
  - GPS captured
  - RTL support works
- **Estimated Effort**: 2 days

### 7.8 Contacts Views Enhancement

- **Description**: Add table/kanban/grid view toggle and CSV export to Contacts page
- **Dependencies**: 1.3 (Customers)
- **Owner**: Frontend Developer
- **Spec**:
  - View toggle: Table (default) / Kanban (grouped by status) / Grid (card layout)
  - Search bar (already exists, enhance with debounce)
  - Filter by: status, area, class
  - Export button: downloads CSV with columns (name, status, area, address, mobile, class)
  - Kanban: columns = customer status (active/inactive/pending)
  - Grid: card per customer with name, phone, status badge
- **Verification**:
  - View toggle works (3 modes)
  - Search works
  - Filters work
  - CSV downloads correctly
  - Kanban groups by status
  - Grid shows cards
  - RTL support works
- **Estimated Effort**: 2 days

### 7.9 Calls Tracking

- **Description**: Log phone calls as separate entities linked to customers
- **Dependencies**: 1.3 (Customers)
- **Owner**: Backend Developer
- **Spec**:
  - New `calls` table:
    - `id`, `company_id`, `user_id`, `customer_id`, `contact_id`, `direction` (inbound/outbound), `duration_seconds`, `outcome` (reached/no_answer/busy/left_voicemail), `notes`, `called_at`, timestamps
  - Rep PWA: "Log Call" button on customer detail page
  - Quick form: contact, duration (auto-timer or manual), outcome, notes
  - Calls listed on customer detail page (recent calls)
  - Admin Filament resource: list/filter/export calls
  - Integration with Performance Dashboard (7.3) for call rate metric
- **Verification**:
  - Can log a call from customer page
  - Call appears in customer's call history
  - Duration recorded correctly
  - Outcome options work
  - Admin can view/filter calls
  - Call rate metric updates in Performance dashboard
  - RTL support works
- **Estimated Effort**: 2 days

### 7.10 Customers Summary Report

- **Description**: Dedicated customer analytics view with key metrics
- **Dependencies**: 1.3 (Customers), 1.5 (Visits), 7.9 (Calls)
- **Owner**: Frontend Developer
- **Spec**:
  - New page: `/app/reports/customers`
  - Metrics cards:
    - Total customers (active/inactive)
    - New customers this month
    - Visit frequency per customer
    - Top 10 customers by order value
    - Customers with overdue payments
  - Table: customer list with columns (name, last visit date, total orders, balance, status)
  - Sort by any column
  - Filter by status, area, date range
  - Export to CSV
- **Verification**:
  - Page renders with correct metrics
  - Table data is accurate
  - Sorting works
  - Filtering works
  - CSV export works
  - RTL support works
- **Estimated Effort**: 2 days

---

### Milestone 7 Acceptance Criteria:

- [ ] Calendar shows visits, todos, and tickets by date with navigation
- [ ] Todos can be created, completed, and filtered
- [ ] Performance dashboard shows all 5 tabs with correct metrics
- [ ] Agenda combines visits, todos, and non-planned visits in one view
- [ ] Tickets follow status workflow (جديد → قيد التنفيذ → اكتمل)
- [ ] Requests follow approval workflow (جديد → تم الموافقة → تم)
- [ ] Non-planned visits can be recorded from Agenda
- [ ] Contacts page has table/kanban/grid toggle and CSV export
- [ ] Calls can be logged against customers
- [ ] Customers summary shows analytics and export
- [ ] All pages support Arabic RTL + English LTR
- [ ] All new tables have proper indexes and foreign keys
- [ ] All new features have Filament admin resources

---

## Cross-Milestone Dependencies and Considerations

### Critical Path

The critical path through the milestones is:
1.1 → 1.2 → 1.3 → 1.4 → 1.5 → 1.6 → 1.7 →
2.1 → 2.2 → 2.3 → 2.4 → 2.5 →
3.1 → 3.2 → 3.3 → 3.4 → 3.5 →
4.1 → 4.2 → 4.3 → 4.4 → 4.5 → 4.6 →
5.1 → 5.2 → 5.3 → 5.4 → 5.5 → 5.6 → 5.7 → 5.8 →
6.1 → 6.2 → 6.3 → 6.4 → 6.5 → 6.6

**Milestone 7 can run in parallel** with Milestones 3-6 (depends only on Milestone 1 completion + parts of Milestone 2).

### Resource Allocation Guidelines

- **Backend Development**: Approximately 40% of effort
- **Frontend Development**: Approximately 25% of effort
- **DevOps/Infrastructure**: Approximately 15% of effort
- **Security**: Approximately 10% of effort
- **Product Management/UX**: Approximately 10% of effort

### Risk Management

- **Technical Risks**:
  - Offline synchronization complexity - mitigated by prototyping early
  - Financial transaction integrity - mitigated by extensive testing and audit trails
  - Performance at scale - mitigated by early performance testing and optimization
  - Security vulnerabilities - mitigated by security-first approach and regular testing
- **Schedule Risks**:
  - Scope creep - mitigated by strict adherence to vertical slices and milestones
  - Dependency delays - mitigated by identifying critical path and buffering
  - Integration complexity - mitigated by defining clear API contracts early
- **Quality Risks**:
  - Inadequate testing - mitigated by defining clear acceptance criteria for each task
  - Usability issues - mitigated by involving users early in the process
  - Performance degradation - mitigated by continuous performance monitoring

### Quality Gates

Each milestone must pass these quality gates before proceeding:

1. **Functional Completeness**: All acceptance criteria met
2. **Code Quality**: Code review passed, linting clean
3. **Testing**: Unit tests >80% coverage, feature tests passing
4. **Security**: No critical vulnerabilities identified
5. **Performance**: Meets defined benchmarks
6. **Usability**: Meets basic usability criteria
7. **Documentation**: Relevant documentation updated
8. **Deployability**: Deploys successfully to target environment

### Definition of Done for Tasks

A task is considered done when:

1. All acceptance criteria are met
2. Code is reviewed and merged to main branch
3. Unit tests are written and passing (>80% coverage for new code)
4. Feature tests cover the new functionality
5. Documentation is updated as needed
6. Security review passed (for security-sensitive tasks)
7. Performance benchmarks met (for performance-sensitive tasks)
8. Deployable to staging environment without issues
9. Rollback procedure tested and documented
10. Acceptance criteria verified by QA or product owner

## Estimated Timeline

Based on the effort estimates and dependencies:

- **Milestone 1**: ~4 weeks (Critical path: 1.1→1.2→1.3→1.4→1.5→1.6→1.7)
- **Milestone 2**: ~5 weeks (Critical path: 2.1→2.2→2.3→2.4→2.5)
- **Milestone 3**: ~6 weeks (Critical path: 3.1→3.2→3.3→3.4→3.5)
- **Milestone 4**: ~5 weeks (Critical path: 4.1→4.2→4.3→4.4→4.5→4.6)
- **Milestone 5**: ~8 weeks (Critical path: 5.1→5.2→5.3→5.4→5.5→5.6→5.7→5.8)
- **Milestone 6**: ~4 weeks (Critical path: 6.1→6.2→6.3→6.4→6.5→6.6)
- **Milestone 7**: ~4 weeks (can parallel with M3-M6, critical path: 7.2→7.4→7.7, 7.3→7.10)

**Total Estimated Duration**: ~32 weeks (approximately 8 months)

Note: Milestone 7 runs in parallel with Milestones 3-6, adding only ~4 weeks to the critical path (not 32 + 4).

Actual timeline may vary based on:

- Team size and experience
- Requirement clarity and stability
- External dependencies and integrations
- Organizational processes and approval cycles
- Unforeseen technical challenges

## Tracking and Reporting

Progress will be tracked using:

- Task completion status in project management system
- Milestone burndown charts
- Velocity tracking (story points per sprint)
- Defect density and resolution rates
- Code quality metrics (coverage, duplication, complexity)
- Security scan results
- Performance benchmark results

Regular reporting will include:

- Weekly status reports to stakeholders
- Milestone completion reports
- Burndown and velocity charts
- Risk register updates
- Quality metric trends
- Upcoming milestone previews

This task list provides a detailed roadmap for implementing the Jawla system, breaking down the work into manageable, testable increments that deliver clear value at each stage while maintaining architectural integrity and quality standards.
