# Investigation: Test Coverage Gaps — All Missing & Partial User Stories

> Version: 1.0 | Date: 2026-07-23 | Status: ready-for-dev

---

## Symptom Summary

18 user stories have **zero test coverage** and 12 have **partial coverage** out of 72 total across 24 epics. This leaves critical financial flows (invoice amendment, return cancellation, activity log reversal) and core admin surfaces (dashboard KPIs, stock balances, payment register) completely untested.

**Severity:** P0 for financial/reversal flows; P1 for admin visibility; P2 for UX/security; P3 for CRUD coverage.

---

## Evidence Gathered

### Grade A — Confirmed (code exists, no tests)

| Story                           | Component                 | File Path                                                                | Evidence                                                                                                                                       |
| ------------------------------- | ------------------------- | ------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| US-5.3 Amend Invoice            | `InvoiceService::amend()` | `app/Services/InvoiceService.php:207-245`                                | Method exists, cancels original + creates new draft with `amended_from` link. Zero test coverage.                                              |
| US-18.3 Activity Log + Reversal | `ReversalService`         | `app/Services/ReversalService.php`, `app/Filament/Pages/ActivityLog.php` | Service exists, called from ActivityLog page. Zero test coverage.                                                                              |
| US-6.3 View Payment Register    | `PaymentResource`         | `app/Filament/Resources/PaymentResource.php`                             | ListPayments page exists. Zero test coverage.                                                                                                  |
| US-8.2 View Expense Register    | `ExpenseResource`         | `app/Filament/Resources/ExpenseResource.php`                             | ListExpenses page exists. Zero test coverage.                                                                                                  |
| US-11.3 View Stock Balances     | `StockResource`           | `app/Filament/Resources/StockResource.php`                               | ListStocks page exists. Zero test coverage.                                                                                                    |
| US-13.4 View PO Register        | `PurchaseOrderResource`   | `app/Filament/Resources/PurchaseOrderResource.php`                       | ListPurchaseOrders page exists. Zero test coverage.                                                                                            |
| US-18.1 Dashboard KPIs          | 7 widgets                 | `app/Filament/Widgets/*.php`                                             | SalesToday, VisitsToday, OutstandingBalance, LowStockAlert, CollectionRate, RepPerformance, OpenAlarms, PendingQuotations. Zero test coverage. |
| US-23.1 Edit Profile            | `ProfilePage`             | `app/Livewire/App/ProfilePage.php`                                       | Component exists. Zero test coverage.                                                                                                          |
| US-23.2 View Settings           | `SettingsPage`            | `app/Livewire/App/SettingsPage.php`                                      | Component exists. Zero test coverage.                                                                                                          |
| US-9.3 View Customer Directory  | `TodaysCustomers`         | `app/Livewire/App/TodaysCustomers.php`                                   | Component exists. Zero test coverage.                                                                                                          |
| US-2.3/24.2 Task Completion     | `Home::completeTask`      | `app/Livewire/App/Home.php`                                              | Method exists. Only form render tested.                                                                                                        |

### Grade A — False Positive (actually covered)

| Story                | Claimed Status | Actual Status                | Evidence                                                                                                                    |
| -------------------- | -------------- | ---------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| US-7.2 Cancel Return | Missing        | **Covered at service level** | `tests/Unit/Services/ReturnServiceTest.php:81` tests `ReturnService::cancel()` with stock reversal and balance restoration. |

### Grade B — Partial Coverage (what's missing)

| Story                   | Tested                                                     | Missing                                                   |
| ----------------------- | ---------------------------------------------------------- | --------------------------------------------------------- |
| US-4.5 Invoice Register | Policy access (admin/s/accounts can viewAny)               | List page rendering, filter, data display                 |
| US-5.1 Status Flow      | Implicit transitions (Submitted→Paid, Submitted→Cancelled) | Explicit state-machine test for all 6 statuses            |
| US-9.4 Manage Customers | Form render only                                           | CRUD operations, Leaflet map, GPS coordinates             |
| US-11.1 View Van Stock  | StockSearch out-of-stock flagging                          | Stock viewing, balance display                            |
| US-11.4 Adjust Stock    | Service-level increment/decrement                          | Admin StockAdjust action, StockMovement reason=Adjustment |
| US-17.2 Rep Performance | Attainment % calculation                                   | RepPerformanceWidget render                               |
| US-24.1 Create Tasks    | Form render only                                           | Task creation, assignment, data flow                      |

---

## Hypotheses

### H1: Filament admin pages lack tests because they're auto-generated (HIGH plausibility)

- **Supporting:** AdminFormsRenderTest only smoke-tests form rendering, not data flow
- **Contradicting:** Some Filament resources do have tests (CashReconciliationTest tests admin approval action)
- **Verification:** Check if Filament's testing helpers are being used elsewhere

### H2: Livewire rep components lack tests because Playwright can't reliably trigger Alpine.js reactivity (MEDIUM plausibility)

- **Supporting:** The autocomplete browser test failed repeatedly due to Alpine.js x-model incompatibility
- **Contradicting:** Feature tests (Livewire HTTP) work fine for most components
- **Verification:** Livewire::test() can test components without browser interaction

### H3: Dashboard widgets lack tests because they're read-only and considered low-risk (LOW plausibility)

- **Supporting:** No financial mutations in widgets, just data aggregation
- **Contradicting:** Widgets are the primary admin visibility surface; incorrect KPIs could lead to bad decisions
- **Verification:** Widget tests are simple Livewire::test() calls

---

## Recommended Remediation Plan

### P0 — Financial/Reversal (3 tests)

1. **`tests/Unit/Services/InvoiceAmendServiceTest.php`** — US-5.3
   - Test: amend cancels original, creates new draft, links via `amended_from`, copies items, preserves audit trail
   - Method: `InvoiceService::amend()`

2. **`tests/Unit/Services/ReversalServiceTest.php`** — US-18.3
   - Test: reverseInvoice and reversePayment correctly undo financial mutations
   - Method: `ReversalService::reverseInvoice()`, `ReversalService::reversePayment()`

3. **`tests/Feature/PaymentResourceTest.php`** — US-6.3
   - Test: admin can list payments, filter by status, see amounts
   - Filament resource: `PaymentResource`

### P1 — Admin Visibility (4 tests)

4. **`tests/Feature/DashboardWidgetTest.php`** — US-18.1
   - Test: each widget renders without error, shows correct data shape
   - Widgets: SalesToday, VisitsToday, OutstandingBalance, LowStockAlert, CollectionRate, RepPerformance, OpenAlarms, PendingQuotations

5. **`tests/Feature/InvoiceResourceTest.php`** — US-4.5
   - Test: admin can list invoices, filter by status, see totals
   - Filament resource: `InvoiceResource`

6. **`tests/Feature/StockResourceTest.php`** — US-11.3
   - Test: admin can view stock balances, filter by warehouse/product
   - Filament resource: `StockResource`

7. **`tests/Feature/ExpenseResourceTest.php`** — US-8.2
   - Test: admin can list expenses, filter by category/rep
   - Filament resource: `ExpenseResource`

### P2 — UX/Security (4 tests)

8. **`tests/Feature/ProfilePageTest.php`** — US-23.1
   - Test: rep can view/edit profile, change password with Hash::check

9. **`tests/Feature/SettingsPageTest.php`** — US-23.2
   - Test: rep can view settings (user name, company, locale)

10. **`tests/Feature/TodaysCustomersTest.php`** — US-9.3
    - Test: rep can search customers, see directory

11. **`tests/Feature/PurchaseOrderResourceTest.php`** — US-13.4
    - Test: admin can list purchase orders

### P3 — Backlog (2 tests)

12. **`tests/Feature/TaskManagementTest.php`** — US-2.3 + US-24.1 + US-24.2
    - Test: admin creates task, rep completes task

13. **`tests/Feature/StockAdjustTest.php`** — US-11.4
    - Test: admin adjusts stock via StockResource action

---

## Decision Log Entry

```
## Investigation: Test Coverage Gaps — 2026-07-23
- Symptom: 18 user stories have zero test coverage, 12 have partial coverage
- Primary hypothesis: Filament admin pages and Livewire rep components lack tests due to auto-generation and Playwright/Alpine.js incompatibility
- Primary suspected component: Filament Resources + Livewire App components
- Case file: bmad-output/investigation-test-coverage-gaps-2026-07-23.md
- Recommended response: Option A — Create 13 test files across P0/P1/P2/P3 priority
```
