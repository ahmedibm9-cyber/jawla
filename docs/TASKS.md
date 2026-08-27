# Jawla (جولة) - Implementation Tasks

## Overview

This document outlines the implementation tasks for the Jawla system, organized into vertical milestones that deliver complete user-visible results. Each task includes dependencies, ownership information, verification methods, and current status.

**Last updated**: 2026-08-27

---

## Milestone 1: Core Field Operations Foundation

_Delivers basic representative daily workflow with visit management_

### Tasks:

#### 1.1 Project Setup and Core Infrastructure

- **Status**: Completed
- **Evidence**: Laravel 13 project, Dockerized Nginx+PHP-FPM, PostgreSQL 16, Redis, Railway deployment

#### 1.2 Company and User Management

- **Status**: Completed
- **Evidence**: Company CRUD, User model with roles (Spatie), Filament auth, 5-role scheme, password reset

#### 1.3 Customer Management Basic

- **Status**: Completed
- **Evidence**: Customer model, CustomerFactory, CustomerController, bilingual AR/EN names, search by name/code/phone

#### 1.4 Product Management Basic

- **Status**: Completed
- **Evidence**: Product model, ProductCategory, ProductPrice, barcode support, Filament resources

#### 1.5 Visit Management Core

- **Status**: Completed
- **Evidence**: Visit model, VisitReportService, GPS check-in/check-out, visit status transitions, audit trail, DailyVisitAssignmentService with approval workflow

#### 1.6 Basic Dashboard and Navigation

- **Status**: Completed
- **Evidence**: Home page with daily metrics, tab bar navigation, offline indicator, profile/settings pages

#### 1.7 Data Synchronization Foundation

- **Status**: Completed
- **Evidence**: SyncService, SyncReceipt (idempotency), SyncHandlerRegistry, 10+ sync handlers, offline queue, conflict detection

### Milestone 1 Status: **COMPLETE** ✅

---

## Milestone 2: Sales and Order Processing

_Delivers basic sales order creation and processing capabilities_

### Tasks:

#### 2.1 Price Lists and Product Pricing

- **Status**: Completed
- **Evidence**: PricingService, PriceList model, ProductPrice model, range-based and override pricing

#### 2.2 Sales Order Creation

- **Status**: Completed
- **Evidence**: SalesOrderService, SalesOrder model, SalesFlow Livewire component, cart-based order creation

#### 2.3 Order Approval Workflow

- **Status**: Completed
- **Evidence**: ApprovalService, ApprovalRequest/ApprovalStep models, multi-step approval with thresholds

#### 2.4 Order Fulfillment and Invoicing

- **Status**: Completed
- **Evidence**: InvoiceService, InvoiceCalculationService, Invoice model with items/taxes, stock deduction on fulfillment, customer balance updates

#### 2.5 Basic Sales Dashboard and Reports

- **Status**: Completed
- **Evidence**: ReportsPage (Filament), dashboard widgets (VisitsTodayWidget, SalesTodayWidget, etc.), PDF export

### Milestone 2 Status: **COMPLETE** ✅

---

## Milestone 3: Financial Management and Collections

_Delivers payment processing, collections, and financial reporting capabilities_

### Tasks:

#### 3.1 Payment Processing Core

- **Status**: Completed
- **Evidence**: PaymentService, Payment model, cash/card/bank payments, payment reversal, payment allocation to invoices, overpayment as customer credit

#### 3.2 Cash Management

- **Status**: Completed
- **Evidence**: CashBox model, CashReconciliationService, cash receipts/disbursements, variance detection, lockForUpdate on financial rows

#### 3.3 Collections Management

- **Status**: Completed
- **Evidence**: CollectionSubmissionService, CollectionSubmission model, payment follow-up workflows

#### 3.4 Financial Reports and Statements

- **Status**: Completed
- **Evidence**: ReportsPage (Filament), LedgerReconciliationService, customer statements, PDF/CSV export

#### 3.5 Expense Management

- **Status**: Completed
- **Evidence**: ExpenseService, Expense model, expense recording with categories, expense cancellation with cashbox restoration

### Milestone 3 Status: **COMPLETE** ✅

---

## Milestone 4: Inventory Management and Stock Control

_Delivers warehouse and vehicle stock management capabilities_

### Tasks:

#### 4.1 Warehouse and Location Management

- **Status**: Completed
- **Evidence**: Warehouse model (types: main, van, transit), Filament warehouse resources

#### 4.2 Stock Management Core

- **Status**: Completed
- **Evidence**: StockService (sole authority for mutations), Stock model, stock_movements audit trail, no negative van stock guard

#### 4.3 Batch and Lot Tracking

- **Status**: Completed
- **Evidence**: BatchService, Batch model, FEFO/FIFO enforcement, expiration tracking, expiring/expired batch queries

#### 4.4 Stock Counting and Adjustments

- **Status**: Completed
- **Evidence**: StockCountService, StockCountSession model, physical count workflow, variance detection

#### 4.5 Vehicle Stock Management

- **Status**: Completed
- **Evidence**: VanTransferService, VanTransfer model, full lifecycle (create→ship→receive), lockForUpdate on participants

#### 4.6 Inventory Reports and Alerts

- **Status**: Completed
- **Evidence**: OutOfStockService, low stock alerts, alarm system, stock movement reports

### Milestone 4 Status: **COMPLETE** ✅

---

## Milestone 5: Advanced Features and Integration

_Delivers advanced features, integration capabilities, and system refinements_

### Tasks:

#### 5.1 Approval Workflow Engine Enhancement

- **Status**: Completed
- **Evidence**: ApprovalService, ApprovalRequest/ApprovalStep models, WorkflowApproverResolver, multi-step approval, daily visit assignment approval

#### 5.2 Offline Synchronization Enhancement

- **Status**: Completed
- **Evidence**: SyncService with idempotency (SyncReceipt), 10+ sync handlers via container tagging, conflict detection, priority queuing

#### 5.3 Integration Framework

- **Status**: Completed
- **Evidence**: REST API (Sanctum tokens, ApiTokenService), WebhookService, webhook endpoints/deliveries, API resources (CustomerResource, ProductResource)

#### 5.4 Reporting and Analytics Enhancement

- **Status**: Completed
- **Evidence**: ReportsPage with date range filtering, performance metrics, rep performance widget, PDF/CSV export

#### 5.5 Notification System Enhancement

- **Status**: Completed
- **Evidence**: PushService, RepNotification model, push subscriptions, in-app notifications, WhatsApp integration pattern

#### 5.6 Security Hardening and Compliance

- **Status**: Completed
- **Evidence**: Security headers middleware, CSP, Sentry integration, AuditObserver, lockForUpdate on financial rows, input validation, bcrypt rounds

#### 5.7 Performance Optimization and Scaling

- **Status**: Completed
- **Evidence**: Redis (session/cache/queue), Dockerized Nginx+PHP-FPM, 2 replicas on Railway, Railway scaling config

#### 5.8 Localization and Accessibility Completion

- **Status**: Completed
- **Evidence**: Full Arabic RTL support, English LTR, bilingual blade components, lang files (573+ keys each AR/EN), WCAG basics (skip links, aria labels, keyboard nav)

### Milestone 5 Status: **COMPLETE** ✅

---

## Milestone 6: Production Readiness and Launch

_Delivers production-ready system with monitoring, documentation, and launch preparation_

### Tasks:

#### 6.1 Production Infrastructure and Deployment

- **Status**: Completed
- **Evidence**: Railway deployment (Dockerized), CI/CD (GitHub Actions), health endpoint (/up), monitoring (Sentry), backup procedures documented

#### 6.2 Documentation and Knowledge Transfer

- **Status**: Partial
- **Remaining**: User manuals, administrator guides, training materials. Architecture docs, deployment docs, and API docs exist.

#### 6.3 Security and Compliance Audit

- **Status**: Completed
- **Evidence**: 4-agent security audit (36 findings: 0 CRITICAL, 8 HIGH, 11 MEDIUM, 17 LOW — all fixed), OWASP ZAP DAST in CI

#### 6.4 User Acceptance Testing and Feedback

- **Status**: Ready
- **Note**: UAT checklist created (docs/UAT_CHECKLIST.md). Requires real users to execute. Demo mode seeded for evaluation.

#### 6.5 Performance and Load Testing

- **Status**: Ready
- **Note**: Load testing guide created (docs/LOAD_TESTING_GUIDE.md) with k6 scripts and success criteria. Client-side performance tests exist (CDP throttling).

#### 6.6 Launch Preparation and Go/No-Go Decision

- **Status**: Ready
- **Note**: Launch checklist created (docs/LAUNCH_CHECKLIST.md). App deployed at https://jawla.up.railway.app. All pre-launch items documented.

### Milestone 6 Status: **READY FOR LAUNCH** (6.1 ✅, 6.2 partial, 6.3 ✅, 6.4-6.6 Ready)

---

## Milestone 7: Competitor Gap Closure (Bricks Rep Parity)

_Closes all gaps identified in competitor analysis against Bricks Rep_

### Tasks:

#### 7.1 Calendar View

- **Status**: Completed
- **Evidence**: Calendar Livewire component, monthly grid, visit/todo indicators, navigation

#### 7.2 Todos/Tasks System

- **Status**: Completed
- **Evidence**: TodoService, Todo model, Livewire Todos component, Filament TodoResource, bilingual translations

#### 7.3 Performance Dashboard

- **Status**: Completed
- **Evidence**: PerformanceDashboard with Chart.js (visit trend bar chart, revenue trend line chart, todo completion doughnut), PerformanceDataController API endpoint

#### 7.4 Agenda View

- **Status**: Completed
- **Evidence**: Agenda Livewire component, daily view with planned visits/recorded visits/todos

#### 7.5 Tickets System

- **Status**: Completed
- **Evidence**: TicketService, Ticket model, Livewire Tickets component with kanban drag-and-drop (SortableJS), status workflow, Filament TicketResource

#### 7.6 Requests System

- **Status**: Completed
- **Evidence**: RequestService, Request model, Livewire Requests component, approval workflow, Filament RequestResource

#### 7.7 Non-Planned Visit Recording

- **Status**: Completed
- **Evidence**: is_out_of_route column on visits, Agenda page "Record Non-planned Visit" button

#### 7.8 Contacts Views Enhancement

- **Status**: Completed
- **Evidence**: CustomerSummaryReport with sortable table, status filter, CSV export, metrics cards

#### 7.9 Calls Tracking

- **Status**: Completed
- **Evidence**: CallService, Call model, Livewire LogCall/CallHistory components, Filament CallResource

#### 7.10 Customers Summary Report

- **Status**: Completed
- **Evidence**: CustomerSummaryReport Livewire component with metrics (total/active/new/overdue), sortable table, CSV export, status filter

### Milestone 7 Status: **COMPLETE** ✅

---

## Summary

| Milestone                  | Status      | Notes                                             |
| -------------------------- | ----------- | ------------------------------------------------- |
| M1: Core Field Operations  | ✅ Complete |                                                   |
| M2: Sales & Orders         | ✅ Complete |                                                   |
| M3: Financial Management   | ✅ Complete |                                                   |
| M4: Inventory & Stock      | ✅ Complete |                                                   |
| M5: Advanced Features      | ✅ Complete |                                                   |
| M6: Production Readiness   | ✅ Ready    | UAT/load test/launch checklists created, deployed |
| M7: Competitor Gap Closure | ✅ Complete |                                                   |

**Overall**: M1-M7 all complete or ready. App deployed at https://jawla.up.railway.app. UAT, load testing, and launch require stakeholder execution.
