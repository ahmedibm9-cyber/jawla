# GAP ANALYSIS: Test Coverage vs User Stories

> Generated 2026-07-23 | 60 test files analyzed | 72 user stories across 24 epics

---

## Summary

| Metric             | Count                                        |
| ------------------ | -------------------------------------------- |
| Total User Stories | 72                                           |
| Covered (full)     | 42                                           |
| Partially Covered  | 12                                           |
| Missing Coverage   | 18                                           |
| **Coverage %**     | **58% full / 75% partial+**                  |
| Test Files         | 60 (40 Feature, 13 Unit, 3 Browser, 4 infra) |

---

## Epic 1: Authentication & Session Management (3 stories)

| Story              | Status      | Test File(s)                                                           | Notes                                                                               |
| ------------------ | ----------- | ---------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| US-1.1 Rep Login   | **Covered** | `Feature/Auth/RepLoginTest.php`, `Feature/RepLoginLifecycleTest.php`   | Full lifecycle: rate limiting, credential validation, session redirect, role gating |
| US-1.2 Admin Login | **Covered** | `Feature/Auth/AdminLoginTest.php`, `Feature/RepLoginLifecycleTest.php` | Admin panel access, rep rejection, non-admin rejection                              |
| US-1.3 Logout      | **Covered** | `Feature/RepLoginLifecycleTest.php`                                    | Session invalidation, guest redirect after logout                                   |

---

## Epic 2: Work Day Management (3 stories)

| Story                              | Status      | Test File(s)                                                     | Notes                                               |
| ---------------------------------- | ----------- | ---------------------------------------------------------------- | --------------------------------------------------- |
| US-2.1 Start Work Day              | **Covered** | `Feature/AMEndToEndTest.php`, `Feature/LocationTrackingTest.php` | Work session creation, GPS tracking, location pings |
| US-2.2 View Today's Visit Schedule | **Covered** | `Feature/AMEndToEndTest.php`                                     | DailyVisitAssignment queries validated (AM1)        |
| US-2.3 Complete a Task             | **Missing** | --                                                               | No test for task completion on Home dashboard       |

---

## Epic 3: Visit Management (4 stories)

| Story                                 | Status      | Test File(s)                                                  | Notes                                                                             |
| ------------------------------------- | ----------- | ------------------------------------------------------------- | --------------------------------------------------------------------------------- |
| US-3.1 Start Visit (Check-in)         | **Covered** | `Feature/VisitGeofenceTest.php`, `Feature/AMEndToEndTest.php` | Geofence radius check, arrival confirmation, GPS validation, out-of-route logging |
| US-3.2 Submit Visit Report            | **Covered** | `Feature/AMEndToEndTest.php`                                  | VisitReport creation, status change to closed (AM4)                               |
| US-3.3 View Visit History             | **Covered** | `Feature/RepListsTest.php`                                    | Visits list with status filtering                                                 |
| US-3.4 View Real-Time Rep Map (Admin) | **Covered** | `Feature/LocationTrackingTest.php`                            | Live-map query, company scoping, latest-position per rep                          |

---

## Epic 4: Sales & Invoicing (5 stories)

| Story                                | Status      | Test File(s)                                                                                                   | Notes                                                                                         |
| ------------------------------------ | ----------- | -------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| US-4.1 Create Invoice (Core Sale)    | **Covered** | `Feature/InvoiceFlowTest.php`, `Feature/AMEndToEndTest.php`, `Unit/Services/InvoiceCalculationServiceTest.php` | Atomic stock+balance, oversell rejection, cross-company guard, VAT calculation, thermal print |
| US-4.2 Scan Barcode                  | **Covered** | `Feature/BarcodeScanTest.php`                                                                                  | Barcode-to-product lookup, SKU fallback, cart increment                                       |
| US-4.3 View Order History            | **Covered** | `Feature/RepListsTest.php`                                                                                     | Tabbed orders list (invoices, proformas)                                                      |
| US-4.4 Generate Invoice PDF          | **Covered** | `Feature/AMEndToEndTest.php`, `Feature/PdfQrCodeTest.php`                                                      | PDF generation, QR code embedding, multi-country QR strategies                                |
| US-4.5 View Invoice Register (Admin) | **Partial** | `Feature/Policies/ResourcePolicyTest.php`                                                                      | Policy access tested (admin/s/accounts can viewAny), but no list/filter/register page test    |

---

## Epic 5: Invoice Lifecycle & Status Management (3 stories)

| Story                      | Status      | Test File(s)                                                     | Notes                                                                                                            |
| -------------------------- | ----------- | ---------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| US-5.1 Invoice Status Flow | **Partial** | `Feature/InvoiceFlowTest.php`                                    | Status transitions tested implicitly (Submitted->Paid, Submitted->Cancelled), but no explicit state-machine test |
| US-5.2 Cancel Invoice      | **Covered** | `Feature/InvoiceFlowTest.php`, `Feature/ActionToastUndoTest.php` | Full reversal: stock, balance, lockForUpdate, activity log, undo toast                                           |
| US-5.3 Amend Invoice       | **Missing** | --                                                               | No test for amend (cancel + create new draft with amended_from link)                                             |

---

## Epic 6: Payment Collection (3 stories)

| Story                                | Status      | Test File(s)                                                                                        | Notes                                                                                                     |
| ------------------------------------ | ----------- | --------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| US-6.1 Collect Payment (Rep)         | **Covered** | `Unit/Services/PaymentServiceTest.php`, `Feature/InvoiceFlowTest.php`, `Feature/AMEndToEndTest.php` | Cash increment, invoice paid_amount update, status transition, customer balance decrement, cashbox credit |
| US-6.2 Cancel Payment                | **Covered** | `Unit/Services/PaymentServiceTest.php`                                                              | Full reversal: cashbox, invoice amounts, customer balance                                                 |
| US-6.3 View Payment Register (Admin) | **Missing** | --                                                                                                  | No admin payment register/list page test                                                                  |

---

## Epic 7: Returns Management (2 stories)

| Story                        | Status      | Test File(s)                          | Notes                                                   |
| ---------------------------- | ----------- | ------------------------------------- | ------------------------------------------------------- |
| US-7.1 Log Return (Rep)      | **Covered** | `Unit/Services/ReturnServiceTest.php` | Stock restoration, balance decrement, sequential number |
| US-7.2 Cancel Return (Admin) | **Missing** | --                                    | No return cancellation/reversal test                    |

---

## Epic 8: Expense Management (2 stories)

| Story                                | Status      | Test File(s)                           | Notes                                                       |
| ------------------------------------ | ----------- | -------------------------------------- | ----------------------------------------------------------- |
| US-8.1 Log Expense (Rep)             | **Covered** | `Unit/Services/ExpenseServiceTest.php` | Cashbox decrement, category validation, auto-create cashbox |
| US-8.2 View Expense Register (Admin) | **Missing** | --                                     | No admin expense register/list page test                    |

---

## Epic 9: Customer Management (4 stories)

| Story                                  | Status      | Test File(s)                       | Notes                                                        |
| -------------------------------------- | ----------- | ---------------------------------- | ------------------------------------------------------------ |
| US-9.1 Add New Customer (Rep)          | **Covered** | `Feature/AMEndToEndTest.php`       | Pending customer creation, alarm raised (AM5)                |
| US-9.2 Approve/Reject Customer (Admin) | **Covered** | `Feature/AMEndToEndTest.php`       | Approval flow, manager notification (AM5b)                   |
| US-9.3 View Customer Directory         | **Missing** | --                                 | No rep-side customer search/directory test                   |
| US-9.4 Manage Customers (Admin)        | **Partial** | `Feature/AdminFormsRenderTest.php` | CreateCustomer form renders, but no CRUD or Leaflet map test |

---

## Epic 10: Pricing & Quotations (4 stories)

| Story                               | Status      | Test File(s)                 | Notes                                                  |
| ----------------------------------- | ----------- | ---------------------------- | ------------------------------------------------------ |
| US-10.1 Request Price Quotation     | **Covered** | `Feature/AMEndToEndTest.php` | Request creation, status transition (AM6)              |
| US-10.2 Set Quotation Price (Admin) | **Covered** | `Feature/AMEndToEndTest.php` | PriceQuotation creation with ranges (AM6)              |
| US-10.3 Negotiate Price (Rep)       | **Covered** | `Feature/AMEndToEndTest.php` | Floor/ceiling validation, accept/reject (AM7)          |
| US-10.4 Create Proforma Invoice     | **Covered** | `Feature/AMEndToEndTest.php` | ProformaInvoice creation, VAT calc, bank details (AM7) |

---

## Epic 11: Stock & Inventory Management (5 stories)

| Story                               | Status      | Test File(s)                                                              | Notes                                                                                                  |
| ----------------------------------- | ----------- | ------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| US-11.1 View Van Stock              | **Partial** | `Unit/Services/StockServiceTest.php`                                      | Stock balance queries tested at service level; no StockSearch Livewire component test                  |
| US-11.2 Flag Out-of-Stock           | **Covered** | `Feature/Alarm/OutOfStockBroadcastTest.php`, `Feature/AMEndToEndTest.php` | OutOfStockRequest creation, alarm broadcast, severity routing                                          |
| US-11.3 View Stock Balances (Admin) | **Missing** | --                                                                        | No StockResource admin page test                                                                       |
| US-11.4 Adjust Stock (Admin)        | **Partial** | `Unit/Services/StockServiceTest.php`                                      | StockService increment/decrement tested; no StockAdjust action or StockMovement reason=Adjustment test |
| US-11.5 Import Stock (Admin)        | **Covered** | `Unit/Services/StockImportServiceTest.php`                                | CSV parsing, validation, stock movement creation                                                       |

---

## Epic 12: Van Transfers (4 stories)

| Story                                  | Status      | Test File(s)                                                                     | Notes                                                                           |
| -------------------------------------- | ----------- | -------------------------------------------------------------------------------- | ------------------------------------------------------------------------------- |
| US-12.1 Create Van Transfer (Admin)    | **Covered** | `Unit/Services/VanTransferServiceTest.php`                                       | Create with items, status=pending                                               |
| US-12.2 Ship Transfer (Admin)          | **Covered** | `Unit/Services/VanTransferServiceTest.php`                                       | Status pending->shipped, source warehouse deduction, InsufficientStockException |
| US-12.3 Receive Transfer (Rep)         | **Covered** | `Feature/VanTransferRepPageTest.php`, `Unit/Services/VanTransferServiceTest.php` | Stock increment to receiver van, guard on recipient                             |
| US-12.4 Reject/Cancel Transfer (Admin) | **Covered** | `Unit/Services/VanTransferServiceTest.php`                                       | Reject and cancel status transitions                                            |

---

## Epic 13: Purchase Orders (5 stories)

| Story                                        | Status      | Test File(s)                         | Notes                                              |
| -------------------------------------------- | ----------- | ------------------------------------ | -------------------------------------------------- |
| US-13.1 Submit Purchase Offer (Rep)          | **Covered** | `Feature/PurchaseDualReviewTest.php` | SubmitPurchaseOffer form, PurchaseRequest creation |
| US-13.2 Sales Approve Purchase Request       | **Covered** | `Feature/PurchaseDualReviewTest.php` | Sales manager approval/rejection, notification     |
| US-13.3 Purchasing Approve & Generate PO     | **Covered** | `Feature/PurchaseDualReviewTest.php` | Purchasing approval, PurchaseOrder generation      |
| US-13.4 View Purchase Order Register (Admin) | **Missing** | --                                   | No PurchaseOrderResource list page test            |
| US-13.5 Compare Supplier Pricing             | **Covered** | `Feature/SupplierComparisonTest.php` | Groups offers by product, sorted by price          |

---

## Epic 14: Goods in Transit (2 stories)

| Story                            | Status      | Test File(s)                     | Notes                                                    |
| -------------------------------- | ----------- | -------------------------------- | -------------------------------------------------------- |
| US-14.1 Track Incoming Shipments | **Covered** | `Feature/GoodsInTransitTest.php` | GoodsInTransit model, statuses, landed costs             |
| US-14.2 Receive Shipment         | **Covered** | `Feature/GoodsInTransitTest.php` | GoodsInTransitService::receive() adds stock to warehouse |

---

## Epic 15: Complaints & Alarms (3 stories)

| Story                             | Status      | Test File(s)                                                                  | Notes                                                          |
| --------------------------------- | ----------- | ----------------------------------------------------------------------------- | -------------------------------------------------------------- |
| US-15.1 Log Complaint (Rep)       | **Covered** | `Feature/AlarmBroadcastTest.php`, `Feature/AMEndToEndTest.php`                | ComplaintService::log(), alarm creation                        |
| US-15.2 Manage Complaints (Admin) | **Covered** | `Feature/AlarmBroadcastTest.php`, `Feature/AMEndToEndTest.php`                | ComplaintService::resolve(), status transitions                |
| US-15.3 View & Act on Alarms      | **Covered** | `Feature/AlarmBroadcastTest.php`, `Feature/Alarm/OutOfStockBroadcastTest.php` | AlarmService::raise(), AlarmRead distribution, severity badges |

---

## Epic 16: Cash Management (2 stories)

| Story                                       | Status      | Test File(s)                         | Notes                                                |
| ------------------------------------------- | ----------- | ------------------------------------ | ---------------------------------------------------- |
| US-16.1 Cash Reconciliation (Rep)           | **Covered** | `Feature/CashReconciliationTest.php` | Submit reconciliation, variance calculation, history |
| US-16.2 Approve/Flag Reconciliation (Admin) | **Covered** | `Feature/CashReconciliationTest.php` | Manager approve, flag with reason                    |

---

## Epic 17: Sales Targets & Performance (2 stories)

| Story                                | Status      | Test File(s)                  | Notes                                                             |
| ------------------------------------ | ----------- | ----------------------------- | ----------------------------------------------------------------- |
| US-17.1 Set Sales Targets (Admin)    | **Covered** | `Feature/SalesTargetTest.php` | CRUD targets, AttainmentService calculation                       |
| US-17.2 View Rep Performance (Admin) | **Partial** | `Feature/SalesTargetTest.php` | Attainment percentage tested; no RepPerformanceWidget render test |

---

## Epic 18: Reporting & Analytics (3 stories)

| Story                              | Status      | Test File(s)                  | Notes                                                                      |
| ---------------------------------- | ----------- | ----------------------------- | -------------------------------------------------------------------------- |
| US-18.1 Dashboard KPIs             | **Missing** | --                            | No test for any of the 8 dashboard widgets (SalesToday, VisitsToday, etc.) |
| US-18.2 View Reports Page          | **Covered** | `Feature/ReportsPageTest.php` | ReportsPage tabs, date range filtering                                     |
| US-18.3 Activity Log with Reversal | **Missing** | --                            | No ActivityLog page or ReversalService test                                |

---

## Epic 19: Customer Maps & Geolocation (1 story)

| Story                        | Status      | Test File(s)                  | Notes                                            |
| ---------------------------- | ----------- | ----------------------------- | ------------------------------------------------ |
| US-19.1 Customer Map (Admin) | **Covered** | `Feature/CustomerMapTest.php` | Access gating, Leaflet map data, company scoping |

---

## Epic 20: API & Tokens (2 stories)

| Story                             | Status      | Test File(s)                    | Notes                                                                  |
| --------------------------------- | ----------- | ------------------------------- | ---------------------------------------------------------------------- |
| US-20.1 Manage API Tokens (Admin) | **Covered** | `Feature/ApiTokensPageTest.php` | Access control, token creation with abilities, revocation              |
| US-20.2 Read-Only API             | **Covered** | `Feature/PublicApiV1Test.php`   | whoami, products, customers endpoints, ability gating, company scoping |

---

## Epic 21: Offline & Sync (2 stories)

| Story                              | Status      | Test File(s)                                                         | Notes                                                   |
| ---------------------------------- | ----------- | -------------------------------------------------------------------- | ------------------------------------------------------- |
| US-21.1 Offline Operation Queueing | **Covered** | `Feature/RepFlowOfflineUxTest.php`                                   | queueOffline() renders queued state for all 6 rep flows |
| US-21.2 Sync When Online           | **Covered** | `Feature/OfflineSyncHandlersTest.php`, `Feature/OfflineSyncTest.php` | Exactly-once, replay, retryability, all handler types   |

---

## Epic 22: Notifications (3 stories)

| Story                                  | Status      | Test File(s)                                                                    | Notes                                                   |
| -------------------------------------- | ----------- | ------------------------------------------------------------------------------- | ------------------------------------------------------- |
| US-22.1 View Notifications (Rep)       | **Covered** | `Feature/RepNotificationsTest.php`                                              | Notifications component, paginated list, auto-mark-read |
| US-22.2 Customer Approval Notification | **Covered** | `Feature/RepNotificationsTest.php`                                              | CustomerApprovalOutcome notification dispatched         |
| US-22.3 Alarm Notifications            | **Covered** | `Feature/RepNotificationsTest.php`, `Feature/Alarm/OutOfStockBroadcastTest.php` | Alarm notifications to manager, badge count             |

---

## Epic 23: Profile & Settings (2 stories)

| Story                 | Status      | Test File(s) | Notes                                              |
| --------------------- | ----------- | ------------ | -------------------------------------------------- |
| US-23.1 Edit Profile  | **Missing** | --           | No ProfilePage test (name, email, password change) |
| US-23.2 View Settings | **Missing** | --           | No SettingsPage test (user name, company, locale)  |

---

## Epic 24: Tasks & Management (2 stories)

| Story                        | Status      | Test File(s)                       | Notes                                                        |
| ---------------------------- | ----------- | ---------------------------------- | ------------------------------------------------------------ |
| US-24.1 Create Tasks (Admin) | **Partial** | `Feature/AdminFormsRenderTest.php` | TaskResource create form renders; no CRUD or assignment test |
| US-24.2 Complete Tasks (Rep) | **Missing** | --                                 | No task completion test on Home dashboard                    |

---

## Critical Flow Gap Summary

These are **high-priority** missing tests for financial/critical business flows:

| Priority | Missing Story                        | Risk                                                                                                                                         | Recommendation                                           |
| -------- | ------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| **P0**   | US-5.3 Amend Invoice                 | Invoice amendment creates new draft linked to original. No test proves `amended_from` link, stock re-deduction, or audit trail preservation. | Write `InvoiceService::amend()` unit test + Feature test |
| **P0**   | US-7.2 Cancel Return                 | Return cancellation reverses stock + balance. No compensating-transaction test exists.                                                       | Write `ReturnService::cancel()` unit test                |
| **P0**   | US-6.3 View Payment Register         | No admin register page test for payment oversight.                                                                                           | Write PaymentResource list page test                     |
| **P1**   | US-18.1 Dashboard KPIs               | 8 widgets (SalesToday, VisitsToday, OutstandingBalance, etc.) untested. Core admin visibility gap.                                           | Write widget unit tests or feature tests                 |
| **P1**   | US-18.3 Activity Log + Reversal      | ReversalService is the safety net for financial errors. No test proves reversal works end-to-end.                                            | Write `ReversalService` feature test                     |
| **P1**   | US-4.5 View Invoice Register         | Admin invoice register is the primary sales oversight surface. Policy tested but not page rendering/data.                                    | Write InvoiceResource list page test                     |
| **P1**   | US-11.3 View Stock Balances          | Admin stock view is core warehouse management. No StockResource test.                                                                        | Write StockResource list page test                       |
| **P2**   | US-23.1 Edit Profile                 | Password change requires Hash::check verification. Security-sensitive, untested.                                                             | Write ProfilePage feature test                           |
| **P2**   | US-23.2 View Settings                | Basic settings page, low risk but untested.                                                                                                  | Write SettingsPage feature test                          |
| **P2**   | US-9.3 View Customer Directory       | Rep customer search/directory. Core rep UX, untested at UI level.                                                                            | Write customer search Livewire test                      |
| **P2**   | US-8.2 View Expense Register         | Admin expense oversight. No register page test.                                                                                              | Write ExpenseResource list page test                     |
| **P2**   | US-13.4 View Purchase Order Register | Admin PO tracking. No register page test.                                                                                                    | Write PurchaseOrderResource list page test               |
| **P3**   | US-2.3 / US-24.2 Complete Tasks      | Task creation (admin) and completion (rep) untested.                                                                                         | Write TaskResource + task Livewire test                  |
| **P3**   | US-9.4 Manage Customers (Admin)      | CRUD + Leaflet map. Only render smoke test exists.                                                                                           | Write CustomerResource CRUD test                         |
| **P3**   | US-24.1 Create Tasks (Admin)         | Only form render tested. No data flow test.                                                                                                  | Write TaskResource CRUD test                             |

---

## Cross-Cutting Tests (Not Tied to a Single Story)

These test files validate infrastructure/quality concerns:

| Test File                                     | What It Validates                        |
| --------------------------------------------- | ---------------------------------------- |
| `Feature/Tenancy/CompanyIsolationTest.php`    | Company A cannot see Company B data      |
| `Feature/LocaleDirectionTest.php`             | Arabic=RTL, English=LTR rendering        |
| `Feature/Auth/LocaleSwitchTest.php`           | Locale session switching                 |
| `Feature/Policies/ResourcePolicyTest.php`     | Role-based authorization for 7 resources |
| `Feature/Gates/ProductGatesTest.php`          | Product management gates per role        |
| `Feature/AdminFormsRenderTest.php`            | All Filament admin create forms render   |
| `Unit/SentryScrubberTest.php`                 | Sensitive data scrubbed from Sentry      |
| `Unit/Services/NumberSequenceServiceTest.php` | Sequential document numbering            |
| `Feature/PhotoCaptureTest.php`                | Photo upload, validation, storage        |
| `Feature/PhotoDiskConfigTest.php`             | Photo storage disk config                |
| `Feature/PhotoWireInTest.php`                 | Photo attachment to complaints/returns   |
| `Feature/EtaEInvoicingTest.php`               | Egypt e-invoicing (ETA) compliance       |
| `Feature/HttpEtaClientTest.php`               | ETA HTTP transport layer                 |
| `Unit/Services/InvoiceQrServiceTest.php`      | QR strategy resolution per country       |
| `Unit/Services/EgyptQrStrategyTest.php`       | Egypt QR format                          |
| `Unit/Services/ZatcaPhase1StrategyTest.php`   | ZATCA Phase 1 QR generation              |
| `Unit/Services/ZatcaPhase2StrategyTest.php`   | ZATCA Phase 2 TLV generation             |
| `Unit/Support/ThermalPrintFormatterTest.php`  | Thermal print payload formatting         |

---

## Browser / E2E Tests

| Test File                            | Coverage                                                                                          |
| ------------------------------------ | ------------------------------------------------------------------------------------------------- |
| `Browser/SmokeTest.php`              | Admin login page loads without JS errors                                                          |
| `Browser/RepFlowBrowserTest.php`     | Rep home, payment autocomplete, return flow, complaint flow, purchase offer, stock search         |
| `Browser/FullDayWalkthroughTest.php` | Full rep day: login -> home -> visit -> report -> stock -> quotation -> purchase -> notifications |

---

## Coverage Heatmap by Epic

```
Epic 1  Auth & Session      ████████████████████ 100%  (3/3)
Epic 2  Work Day             ███████████████░░░░░  67%  (2/3)   - US-2.3 missing
Epic 3  Visit Management     ████████████████████ 100%  (4/4)
Epic 4  Sales & Invoicing    █████████████████░░░  80%  (4/5)   - US-4.5 partial
Epic 5  Invoice Lifecycle    ████████████░░░░░░░░  67%  (2/3)   - US-5.3 missing
Epic 6  Payment Collection   ███████████████░░░░░  67%  (2/3)   - US-6.3 missing
Epic 7  Returns              ███████████░░░░░░░░░  50%  (1/2)   - US-7.2 missing
Epic 8  Expense Mgmt         ███████████░░░░░░░░░  50%  (1/2)   - US-8.2 missing
Epic 9  Customer Mgmt        ████████████░░░░░░░░  50%  (2/4)   - US-9.3 missing, US-9.4 partial
Epic 10 Pricing & Quotations ████████████████████ 100%  (4/4)
Epic 11 Stock & Inventory    ████████████░░░░░░░░  40%  (2/5)   - US-11.1, US-11.3 missing, US-11.4 partial
Epic 12 Van Transfers        ████████████████████ 100%  (4/4)
Epic 13 Purchase Orders      █████████████████░░░  80%  (4/5)   - US-13.4 missing
Epic 14 Goods in Transit     ████████████████████ 100%  (2/2)
Epic 15 Complaints & Alarms  ████████████████████ 100%  (3/3)
Epic 16 Cash Management      ████████████████████ 100%  (2/2)
Epic 17 Sales Targets        ███████████████░░░░░  50%  (1/2)   - US-17.2 partial
Epic 18 Reporting            ███████████░░░░░░░░░  33%  (1/3)   - US-18.1, US-18.3 missing
Epic 19 Customer Maps        ████████████████████ 100%  (1/1)
Epic 20 API & Tokens         ████████████████████ 100%  (2/2)
Epic 21 Offline & Sync       ████████████████████ 100%  (2/2)
Epic 22 Notifications        ████████████████████ 100%  (3/3)
Epic 23 Profile & Settings   ░░░░░░░░░░░░░░░░░░░░   0%  (0/2)   - Both missing
Epic 24 Tasks                █████░░░░░░░░░░░░░░░  25%  (1/2 partial / 2) - Both weak
```

---

## Recommended Test Prioritization (Next Sprint)

### P0 -- Write Immediately (financial/reversal risks)

1. `Unit/Services/InvoiceAmendServiceTest.php` -- US-5.3 Amend Invoice
2. `Unit/Services/ReturnCancelServiceTest.php` -- US-7.2 Cancel Return
3. `Feature/PaymentRegisterTest.php` -- US-6.3 View Payment Register

### P1 -- Write This Phase (admin visibility gaps)

4. `Feature/DashboardWidgetTest.php` -- US-18.1 Dashboard KPIs
5. `Feature/ActivityLogReversalTest.php` -- US-18.3 Activity Log + Reversal
6. `Feature/InvoiceRegisterTest.php` -- US-4.5 View Invoice Register
7. `Feature/StockBalanceAdminTest.php` -- US-11.3 View Stock Balances

### P2 -- Write Before Release (UX/security gaps)

8. `Feature/ProfilePageTest.php` -- US-23.1 Edit Profile
9. `Feature/SettingsPageTest.php` -- US-23.2 View Settings
10. `Feature/CustomerDirectoryTest.php` -- US-9.3 View Customer Directory
11. `Feature/ExpenseRegisterTest.php` -- US-8.2 View Expense Register

### P3 -- Backlog (admin CRUD coverage)

12. `Feature/PurchaseOrderRegisterTest.php` -- US-13.4 View PO Register
13. `Feature/TaskManagementTest.php` -- US-2.3 + US-24.1 + US-24.2 Tasks
14. `Feature/CustomerCrudAdminTest.php` -- US-9.4 Manage Customers
