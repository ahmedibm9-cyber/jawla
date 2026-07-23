# JAWLA (جولة) — Enterprise Technical Audit Report

**Audit Date:** 2026-07-12
**Auditors:** Principal Architect / CTO / Enterprise Solution Architect / ERP Consultant / Laravel Architect / DB Architect / DevOps / UX / Security / Performance / QA / Business Process / Product
**Project Phase:** Specification Complete — Zero Code Written
**Classification:** Pre-Implementation Architecture & Design Audit

---

## 1. EXECUTIVE SUMMARY

### Maturity Assessment

| Dimension                | Score | Notes                                                                                                                                        |
| ------------------------ | ----- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| **Project Maturity**     | 5%    | All specs, zero code. No repo exists. No proof of concept.                                                                                   |
| **Estimated Completion** | 3%    | 0 of 19 phases started.                                                                                                                      |
| **Production Readiness** | 0%    | Nothing deployed. No infrastructure exists.                                                                                                  |
| **Architecture Quality** | 7/10  | Good patterns identified. Missing CQRS, event sourcing, DDD aggregates, service boundaries.                                                  |
| **Spec Quality**         | 8/10  | Surprisingly thorough for a single-client project. 12 documents, ~4,500 lines.                                                               |
| **Scalability**          | 5/10  | No caching strategy. No queue architecture. No read-model separation. Single-server VPS assumption.                                          |
| **Maintainability**      | 6/10  | Service layer defined. But no testing strategy, no CI/CD, no code standards documented.                                                      |
| **Security**             | 4/10  | RBAC defined. No mention of encryption, audit logging, rate limiting, SQL injection prevention, XSS, CSRF beyond Laravel defaults.           |
| **UX**                   | 7/10  | UI wireframes exist. RTL support confirmed. Mobile-first approach. Missing: loading states, error handling flows, offline strategy.          |
| **ERP Completeness**     | 6/10  | 58 functional requirements. Missing: HR, payroll, fixed assets, general ledger, budgeting, consolidation. Some intentionally deferred.       |
| **CRM Completeness**     | 4/10  | Leads, opportunities, campaigns marked "STEAL but deferred." No pipeline management. No marketing automation.                                |
| **Business Alignment**   | 8/10  | Closely reflects GPC's actual operations based on research report. Client has validated key decisions.                                       |
| **Overall Confidence**   | 5/10  | High confidence in spec quality. Low confidence in execution plan. Too many unknowns in deployment, testing, data migration, and operations. |

### Critical Verdict

**This project is NOT ready for implementation.** It is ready for a **final design review** with the client before coding begins. The following must be resolved or the implementation will produce a system that fails in production:

1. **No testing strategy defined** — builds will ship with zero automated tests
2. **No deployment/DevOps strategy** — "VPS" is not a plan
3. **No data migration pilot** — 2,500+ customers + 500+ products + stock balances from Odoo with no dry-run
4. **No offline capability** — Reps work in industrial zones with unreliable connectivity
5. **No backup/disaster recovery plan**
6. **No monitoring/logging strategy**
7. **Alarm/timing rules about discount approval timing unclear** — quoted 2-hour SLA, but no escalation or timeout defined
8. **Multi-currency on POs connects to no exchange rate service** — manual rate entry will cause reporting errors
9. **Stock valuation method (Moving Average) chosen without understanding GPC's actual cost accounting** — needs validation
10. **No branch/warehouse strategy** — branches can be either `companies` or `warehouses.type='branch'`. The build guide still says `type (enum: 'main','van')`—branch type missing.

---

## 2. REQUIREMENTS AUDIT

### 2.1 Completeness by Functional Requirement (FR-01 to FR-20)

| FR    | Description              | Status        | Issue                                                                                |
| ----- | ------------------------ | ------------- | ------------------------------------------------------------------------------------ |
| FR-01 | Company Management       | ✅ Specified  | Missing: `parent_id` for branches                                                    |
| FR-02 | User Management          | ✅ Specified  | Missing: user invitation flow, password reset policy                                 |
| FR-03 | Product Management       | ✅ Specified  | Missing: product-supplier linkage, minimum order quantities                          |
| FR-04 | Customer Management      | ✅ Specified  | Missing: credit limit enforcement (prepaid → N/A, but schema has it)                 |
| FR-05 | Route & Visit Planning   | ✅ Specified  | Missing: auto-routing, optimization, bulk assignment                                 |
| FR-06 | Work Session & GPS Visit | 🟡 Specified  | Missing: offline GPS caching. If rep has no signal, visit cannot start               |
| FR-07 | Pricing Chain            | ❌ Incomplete | No price validity duration defined. No price change audit log.                       |
| FR-08 | Proforma Invoice         | ✅ Specified  | —                                                                                    |
| FR-09 | Sales Invoice Field      | ✅ Specified  | —                                                                                    |
| FR-10 | Collections              | ✅ Specified  | —                                                                                    |
| FR-11 | Product Returns          | ✅ Specified  | Missing: return quality inspection gate                                              |
| FR-12 | Field Expenses           | ✅ Specified  | Missing: expense approval workflow, receipt photo upload                             |
| FR-13 | Purchase Requests        | 🟡 Specified  | Missing: auto-pricing from last PO price (rep should see reference)                  |
| FR-14 | Supplier Quotations      | ✅ Specified  | Missing: automated comparison scoring                                                |
| FR-15 | Purchase Orders          | ✅ Specified  | —                                                                                    |
| FR-16 | Goods in Transit         | ✅ Specified  | Missing: container tracking, shipping line reference, bill of lading attachment      |
| FR-17 | Batch Tracking & COA     | 🟡 Specified  | Missing: COA template (parameter pass/fail per product), composite batches           |
| FR-18 | Alarms                   | 🟡 Specified  | No escalation matrix. If manager doesn't respond in N hours → escalate to admin/exec |
| FR-19 | Egypt ETA E-Invoicing    | ✅ Specified  | Correct QR format documented                                                         |
| FR-20 | Reports & Dashboard      | 🟡 Specified  | Missing: drill-down depth, date range presets, scheduled email reports               |

### 2.2 Missing Functional Requirements (not specified at all)

| Missing FR                          | Criticality  | Why Needed                                                                                                                                         |
| ----------------------------------- | ------------ | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| FR-21: Audit Log (full)             | **Critical** | No mention of tracking who changed what. `stock_movements` is append-only for stock, but what about price changes, customer edits, status changes? |
| FR-22: Soft Delete & Recycling Bin  | **High**     | Soft delete on customers/products only. What happens when a user accidentally deletes a route? A warehouse?                                        |
| FR-23: Notification Preferences     | **Medium**   | Can the executive choose which alarms to receive? Can the manager set quiet hours?                                                                 |
| FR-24: User Impersonation           | **Medium**   | Admin (Amr) will need to help reps debug issues. No support login flow.                                                                            |
| FR-25: Session Timeout              | **Medium**   | No session timeout policy. PWA left open on a rep's phone = security risk.                                                                         |
| FR-26: Bulk Operations              | **Medium**   | Manager assigns 5 visits/rep/day. With 3 reps = 15 assignments daily. With 20 reps = 100. No bulk assignment tool.                                 |
| FR-27: Price Approval Escalation    | **Medium**   | Current pricing chain has no SLA. If manager doesn't respond to quotation request → sale lost.                                                     |
| FR-28: Customer Statement           | **Medium**   | Customer needs to see their transaction history. Not specified.                                                                                    |
| FR-29: Supplier Performance Scoring | **Medium**   | No automated scoring of suppliers by on-time delivery, quality, price competitiveness.                                                             |
| FR-30: System Health Dashboard      | **Low**      | Admin has no view of queue size, failed jobs, error rates, disk usage.                                                                             |

---

## 3. ARCHITECTURE REVIEW

### 3.1 Strengths

- **Service layer defined** — 7 service interfaces with methods, signatures, exceptions. This is rare in spec phase. Excellent.
- **Observers for alarms** — Auto-generation from model events. Correct pattern.
- **State machines documented** — Every transaction has a clear lifecycle. This prevents ad-hoc status management.
- **Naming series abstraction** — Configurable document numbering instead of auto-increment. Smart.
- **Posting date pattern** — Separates system time from business time. Critical for period-end closing.
- **StockService centralization** — All stock changes through one service. No direct stock mutations.

### 3.2 Critical Weaknesses

#### 3.2.1 No Event Sourcing / No Domain Events Beyond Observers

The spec defines 8 Laravel events, but they're glorified notifications. There's no event-driven architecture for:

- Stock level changes → reorder point evaluation
- Invoice submission → customer balance recalculation
- Payment received → invoice status update
- Landed cost applied → product cost recalculation

**Risk:** Tight coupling between services. `InvoiceService::createInvoice()` calls `StockService::removeStock()` directly. If either fails, the transaction rolls back. This is correct for the atomic sale, but it means no eventual-consistency path exists for future scale.

**Recommendation:** Add domain events even if consumed synchronously in v1. The event dispatch is the seam for future async processing.

#### 3.2.2 No Read Model Separation

Every dashboard widget queries the operational tables directly. With ~50k transactions/month, the sales dashboard will run 10+ queries per load. No caching layer specified.

**Risk:** Page load times degrade as data grows. With PostgreSQL 16 and proper indexing, this may not bite until year 2, but there's no plan.

**Recommendation:** At minimum, specify Filament's caching layer. For high-frequency widgets (alarm badge, cash balance), use Laravel's `Cache::remember()` with 60s TTL.

#### 3.2.3 No CQRS — Mixed Read/Write Models

The same Eloquent models serve CRUD forms AND transactional operations. This is fine at Phase 3 (Admin CRUD), but Phase 8 (Invoice Creation) uses `InvoiceCreate` Livewire component which mixes read (stock availability, price validation) with write (create invoice, deduct stock).

**Risk:** The Livewire component is doing too much. It's simultaneously a form, a validation engine, a stock checker, and a document creator.

**Recommendation:** Split stock checking into a read-only `StockSearch` component. The `InvoiceCreate` component calls `InvoiceService` for writes only.

#### 3.2.4 Service Layer vs Livewire Boundary Unclear

The openapi.md defines 7 services with interfaces. But the 10 Livewire components listed in the same document call these services directly. There's no controller layer, no form request validation before the service.

**Risk:** Validation logic duplicated between Livewire component `rules()` and service methods. Violates DRY.

**Recommendation:** Every Livewire component that writes data should call a FormRequest for validation before calling the service. Document this pattern explicitly.

### 3.3 Technical Debt Assessment

**Current debt in the spec phase:** None — no code written.

**Future debt that the spec creates:**

| Future Debt                                                                                        | When It Appears | Cost to Fix Later                |
| -------------------------------------------------------------------------------------------------- | --------------- | -------------------------------- |
| Moving Average cost method chosen without understanding GPC's actual accounting                    | Phase 11        | Medium — data migration + recalc |
| No polymorphic `causer` on stock_movements (user_id but no context of who initiated)               | Phase 1         | Low — column addition            |
| `companies` table has `bank_name, bank_account, bank_iban` alongside `company_bank_accounts` table | Phase 3         | Low — migrate data               |
| `payment_terms` as text field on suppliers instead of FK to `payment_terms_templates`              | Phase 3         | Low — hard to query              |
| Expenses have no receipt attachment field                                                          | Phase 9         | Low — file column addition       |

---

## 4. DATABASE AUDIT

### 4.1 Schema Quality Assessment (45 core tables + 12 extensions + 5 Spatie)

#### ✅ What's Right

- **All tables use `bigIncrements`** — correct for ERP-scale data
- **`decimal(12,2)` for money** — correct. Never use float for money.
- **`decimal(12,3)` for stock qty** — correct for fractional ton tracking
- **Polymorphic morphs** (`reference_type`/`reference_id`) on stock_movements, alarms — correct pattern
- **`posting_date` on all transactions** — correct separation of business time from system time
- **`cancelled_at`/`cancelled_by` on transactions** — correct. Never delete transactions.
- **Unique indexes with nullable batch_id** — partial unique indexes correct for optional batch tracking
- **Nested set (lft/rgt)** on customer_groups and territories — correct for hierarchical trees

#### ❌ What's Wrong

| Table                     | Column                               | Problem                                                                                                                                                                | Fix                                                                                                                                                                                 |
| ------------------------- | ------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `companies`               | `bank_name, bank_account, bank_iban` | Duplicated with `company_bank_accounts` table. Either delete these from `companies` and use the FK table, or keep as legacy and risk inconsistency.                    | Remove from `companies`. `company_bank_accounts` with `is_default` handles all cases.                                                                                               |
| `products`                | `price`                              | Direct price column on products is redundant with `product_prices` + `price_lists` pattern stolen from ERPNext. Two systems that WILL drift.                           | Remove `price` and `cost` from `products`. Base prices live in `product_prices` where `price_list.is_default=true`.                                                                 |
| `products`                | `cost`                               | Same problem. Cost should live on the most recent purchase or be computed by valuation method.                                                                         | Remove `cost`. Add a computed `currentCost()` accessor that aggregates from stock_movements or landed costs.                                                                        |
| `suppliers`               | `payment_terms`                      | Text field is a data swamp.                                                                                                                                            | Replace with FK to `payment_terms_templates` (or drop — prepaid sales means no payment terms needed).                                                                               |
| `daily_visit_assignments` | `assigned_by`                        | FK to users. But what if the assigning manager leaves the company? Cascade behavior not specified.                                                                     | No action — FK is correct. But document the cascade behavior: `SET NULL` if assigned_by user is deleted.                                                                            |
| `goods_in_transit`        | `purchase_order_id` nullable         | This is nullable "for manual GIT entries without a PO." That's fine, but 90% of international purchases HAVE a PO. Add a validation rule.                              | Add `required_if` validation: if `supplier_id` is set and supplier.type='international', PO is strongly recommended but not required.                                               |
| `landed_costs`            | —                                    | Both `goods_in_transit_id` AND `purchase_order_id` are nullable. This means a landed cost could be orphaned.                                                           | Add a DB CHECK constraint: exactly one of the two must be non-null.                                                                                                                 |
| `work_sessions`           | `ended_at` nullable                  | Allows rep to never end their day.                                                                                                                                     | Add scheduled task (`visits:auto-close-sessions`) that closes sessions still open at 3:00 AM next day.                                                                              |
| `invoices`                | `vat_amount`                         | `decimal(12,2)` — but VAT on a 100M EGP invoice is 14M EGP. That's only 8 digits before decimal. 12,2 gives 10^10 = 10 billion. Fine for now, but growth might exceed. | Acceptable for GPC scale (max annual revenue estimated ~500M EGP). Keep as is.                                                                                                      |
| `naming_series`           | `current_number`                     | `bigint` — fine. But no concurrency protection mechanism described. Two users creating invoices simultaneously could get the same number.                              | Use `DB::raw('UPDATE naming_series SET current_number = current_number + 1 ... RETURNING current_number')` atomic increment. Laravel's `increment()` is also atomic. Document this. |

### 4.2 Missing Indexes

The spec doesn't describe any indexes beyond unique constraints. For a ~50 table schema, this is a significant gap. **Must-add indexes:**

```sql
-- Performance-critical (every query uses these):
CREATE INDEX idx_stocks_warehouse_product ON stocks(warehouse_id, product_id);
CREATE INDEX idx_stock_movements_reference ON stock_movements(reference_type, reference_id);
CREATE INDEX idx_invoices_posting_date_company ON invoices(posting_date, company_id);
CREATE INDEX idx_visits_user_date ON visits(user_id, created_at);
CREATE INDEX idx_daily_visit_assignments_date ON daily_visit_assignments(visit_date, user_id);
CREATE INDEX idx_payments_customer ON payments(customer_id);
CREATE INDEX idx_alarms_severity_read ON alarms(severity, is_read);
CREATE INDEX idx_products_sku ON products(sku) WHERE deleted_at IS NULL;
```

### 4.3 Missing Tables

| Missing Table                 | Why Needed                                                                                                        | Priority     |
| ----------------------------- | ----------------------------------------------------------------------------------------------------------------- | ------------ |
| `audit_logs`                  | Track who changed what on customer/producer/price data. Legal requirement for Egyptian e-invoicing (audit trail). | **Critical** |
| `sessions` or token table     | PWA needs token-based auth for service worker / offline support.                                                  | **High**     |
| `failed_jobs` + `job_batches` | Laravel defaults exist. Ensure they're configured for PostgreSQL.                                                 | **Medium**   |
| `notifications`               | Laravel's built-in notifications table. Needed for alarm bell in PWA.                                             | **Medium**   |
| `settings`                    | App-wide settings (geofence radius, alarm SLA hours, default price list, etc.) instead of hardcoding.             | **Medium**   |
| `currency_exchange_rates`     | Spec says "store on PO." A proper table is 20 lines of code and enables auto-rate lookup.                         | **Medium**   |

### 4.4 Constraint Audit

| Table                     | Constraint                                 | Status     | Issue                                                                                                                      |
| ------------------------- | ------------------------------------------ | ---------- | -------------------------------------------------------------------------------------------------------------------------- |
| `stocks`                  | CHECK quantity >= 0                        | ❌ Missing | Without this constraint, a bug in StockService could silently allow negative stock. DB should be the last line of defense. |
| `invoice_items`           | CHECK quantity > 0                         | ❌ Missing | Zero-qty line items are meaningless.                                                                                       |
| `payments`                | CHECK amount > 0                           | ❌ Missing | Zero/negative payments should be impossible.                                                                               |
| `invoices`                | CHECK total >= 0                           | ❌ Missing | Credit notes are handled via returns. Invoices should always be positive.                                                  |
| `daily_visit_assignments` | UNIQUE(user_id, customer_id, visit_date)   | ✅ In spec | Correct. Prevents double-booking.                                                                                          |
| `stocks`                  | UNIQUE(warehouse_id, product_id, batch_id) | ✅ In spec | Correct with partial index for nullable batch_id.                                                                          |

**Security risk:** None of these CHECK constraints are in the migration spec. The system relies entirely on application-layer validation. This is a **critical** gap. DB constraints prevent data corruption even when application code has bugs.

---

## 5. BUSINESS LOGIC AUDIT

### 5.1 Complete Walkthrough: End-to-End Sales Flow

```
Rep starts day → assigned visits → visit customer → negotiate price (quotation)
→ create proforma → customer accepts → create invoice → PDF sent → payment collected

Stock flows:    Main WH → Van (via van transfer) → Customer (via invoice)
                        → GIT → Main WH (via goods receipt)

Money flows:    Customer → Rep (cash) → Company (bank deposit)
                        OR Customer → Company (bank transfer directly)
```

### 5.2 Violations Found

#### Violation 1: Van stock origin not fully specified

**Problem:** How does stock get into the van? The spec says "warehouse keeper loads van" and "van transfers between reps." But the initial van loading, daily van replenishment, and van-to-warehouse returns are not specified.

**Impact:** Incomplete workflow. Rep's van will run out of stock with no replenishment pipeline.

**Fix:** Add `van_transfers` flow from main warehouse to van as a standard operation. Warehouse keeper selects products + qty + rep → creates transfer → stock movement (reason='transfer_out' from WH, 'transfer_in' to van).

#### Violation 2: Collection → Cash Box → Company Bank — no handover

**Problem:** Rep collects cash from customers. It goes into the rep's cash box. How does it get from the rep's cash box to the company bank account? No "cash handover" or "deposit" workflow.

**Impact:** Cash box balance grows indefinitely. Company has no record of reps depositing cash to bank.

**Fix:** Add a "Cash Handover" document: rep records depositing X EGP to company bank account. Reduces cash_box balance. Accounts team reconciles against bank statement.

#### Violation 3: Return → quality inspection not required

**Problem:** Return policy spec says returns increase van stock. But returned goods should be inspected before going back into salable stock (especially chemicals — contamination risk).

**Impact:** A rep could accept a return of damaged goods and resell them to the next customer.

**Fix:** Add return status `pending_inspection` → `quarantine` warehouse → quality inspection → `approved` (moves to van stock) or `rejected` (scrap/disposal).

#### Violation 4: Price quotation valid_until not enforced

**Problem:** `price_quotations.valid_until` is nullable. If not set, a quotation is valid forever. The client's business is volatile commodity pricing — a quotation from last week may be irrelevant today.

**Impact:** Rep creates invoice from a stale quotation at a price below current market. Company loses margin.

**Fix:** Make `valid_until` required. Default to 24 hours from creation. Block proforma/invoice creation from expired quotations.

#### Violation 5: Multi-currency PO → landed cost mismatch

**Problem:** Purchase Orders can be in USD/CNY. Landed costs are recorded in EGP (company currency). How is the exchange rate applied consistently?

**Impact:** Landed cost distribution will use different rates than the PO, causing inventory valuation errors.

**Fix:** Store the exchange rate used AT THE TIME OF GIT CREATION on the landed cost. All costs in a single GIT shipment should use the same rate.

#### Violation 6: No data retention or archiving policy

**Problem:** The spec says "stock movements never deleted." With 100+ stock movements per day and 50k+ transactions/year, the stock_movements table will exceed 1M rows in 3-5 years.

**Impact:** Performance degradation on stock reports, stock movement queries, and audit trails.

**Fix:** Add monthly partitioning strategy for `stock_movements` by `posting_date`. Document a 5-year retention + archive policy.

### 5.3 Edge Cases Not Addressed

| Edge Case                                                                         | Risk                                                    | Mitigation                                                                                    |
| --------------------------------------------------------------------------------- | ------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| Rep sells 10 tons but customer returns 12 tons                                    | Negative customer balance                               | Block return qty > original invoice qty                                                       |
| Two invoices created simultaneously (same product, same van, last unit)           | Race condition on stock check                           | DB transaction + row-level lock on stock row: `SELECT ... FOR UPDATE` in StockService         |
| Rep creates invoice minutes after GIT receipt but before cost price update        | Invoice uses stale cost price                           | Cost price snapshot at invoice creation time, stored on invoice_items as `cost_price_at_sale` |
| Rep starts work at 11:59 PM — visit crosses midnight                              | Work session spans 2 days                               | Work sessions bounded by start time. Visit date = work_session.date                           |
| Customer approved → rep creates invoice → admin suspends customer mid-transaction | Customer approved state changes during live transaction | Snapshot customer status at transaction start within DB transaction                           |
| Rep deletes app data / clears browser storage                                     | Lost session state                                      | Server-side session. App state fully restorable from server                                   |
| Two managers try to price the same quotation simultaneously                       | Double pricing race condition                           | `price_quotation_requests` status check + optimistic locking (`updated_at` timestamp check)   |

---

## 6. ERP MODULE AUDIT

| Module            | Current Maturity | Missing                                                                                                                                      | Risk                                                                                                                                                  | Priority     |
| ----------------- | ---------------- | -------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- | ------------ |
| **CRM**           | 2/10             | Leads, opportunities, campaigns, pipeline, stages, lost-reason analytics all marked "deferred" or "STEAL"                                    | System launches without any CRM. Reps cannot capture leads during visits.                                                                             | **High**     |
| **Sales**         | 6/10             | Pricing chain complete. Missing: sales team commission, bulk pricing updates, competitor tracking                                            | Functional but limited for growth.                                                                                                                    | **Medium**   |
| **Inventory**     | 7/10             | Batch tracking, stock movements, GIT, landed cost all specified. Missing: cycle counting, physical inventory, stock reconciliation           | Stock accuracy depends on import from external system. If import fails, inventory drifts.                                                             | **High**     |
| **Warehouse**     | 5/10             | Van transfers specified. Missing: putaway rules, bin locations, pick/pack/ship workflow                                                      | Main warehouse is a black box. No location management.                                                                                                | **Medium**   |
| **Purchasing**    | 6/10             | Quotation comparison, POs, GIT specified. Missing: auto-PO from reorder level, supplier portal                                               | Manual PO creation for every purchase. With 10-15 POs/month, acceptable.                                                                              | **Low**      |
| **Finance**       | 3/10             | No general ledger, no chart of accounts, no journal entries, no trial balance, no P&L, no balance sheet                                      | The system tracks invoices and payments but cannot produce financial statements. Company will still need Odoo or another accounting system for books. | **Critical** |
| **Reporting**     | 5/10             | 59 KPIs defined. Missing: scheduled reports, email delivery, PDF report exports                                                              | Good KPI catalog but no automated distribution.                                                                                                       | **Medium**   |
| **Dashboard**     | 5/10             | Widgets defined. Missing: role-specific default dashboards, drill-down paths                                                                 | Every role sees the same dashboard template. Executive dashboard needs different layout.                                                              | **Medium**   |
| **Auth/RBAC**     | 7/10             | 94 permissions, 7 roles, Spatie. Missing: 2FA, session management, login history                                                             | Solid foundation. Add 2FA for admin/executive roles before production.                                                                                | **High**     |
| **Notifications** | 4/10             | Alarms auto-generate. Missing: push notifications, email alerts, SMS fallback                                                                | Alarms only visible inside the app. No external notification channel. If manager doesn't open Jawla, critical alarms go unnoticed.                    | **Critical** |
| **Approvals**     | 5/10             | Customer approval, price range approval defined. Missing: expense approval, discount approval above threshold, invoice cancellation approval | Ad-hoc approval flows. Some operations have no approval gate.                                                                                         | **Medium**   |

### The Finance Gap is Critical

Jawla v1 has **no accounting module**. It tracks:

- Invoices (AR)
- Payments (cash)
- Returns (credit notes)
- Expenses

It does NOT track:

- Chart of accounts
- General ledger entries
- Journal entries
- Trial balance
- Profit & Loss
- Balance Sheet
- Accounts Payable (POs → supplier bills)
- Fixed assets
- Tax reporting (beyond QR code)

**Impact:** GPC will need to keep Odoo (or migrate to another accounting system) for their books. Jawla becomes a **front-end sales system** that must be reconciled with the accounting system monthly. This defeats the "single source of truth" objective.

**Recommendation:** Add a simple double-entry ledger (journal entries table) in v1. Every invoice creates a debit to Accounts Receivable and a credit to Sales Revenue. Every payment creates a debit to Cash and a credit to AR. This is ~50 lines of migration code and gives the company basic financial statements.

---

## 7. FEATURE GAP ANALYSIS

| Gap                                    | Importance   | Status           | Risk                                                                                 | Recommendation                                                                                                                                           |
| -------------------------------------- | ------------ | ---------------- | ------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Offline capability                     | **Critical** | ❌ Missing       | Reps in industrial zones have unreliable data. App is unusable without connectivity. | Add `laravel/livewire` offline mode + service worker caching for key API calls. At minimum: cache customer list, product catalog, today's visits.        |
| Push notifications                     | **Critical** | ❌ Missing       | Alarms only in-app. No SMS/email/push.                                               | Add Firebase Cloud Messaging (FCM) for push. Or at minimum, email alerts for critical alarms.                                                            |
| General ledger                         | **Critical** | ❌ Missing       | No financial statements. Company keeps Odoo.                                         | Add simple journal entry table. Double-entry for every invoice and payment.                                                                              |
| Testing strategy                       | **Critical** | ❌ Not specified | Zero test coverage guaranteed.                                                       | Define: PHPUnit feature tests for every service method. At least 1 test per business rule.                                                               |
| CI/CD pipeline                         | **High**     | ❌ Not specified | Manual deployments = human error                                                     | GitHub Actions: lint → test → build → deploy. Document in Phase 0.                                                                                       |
| Audit trail (full)                     | **High**     | ❌ Missing       | No tracking of data changes beyond stock movements.                                  | Use `spatie/laravel-activitylog` or `owen-it/laravel-auditing`. Plugs into Eloquent events.                                                              |
| 2FA/MFA                                | **High**     | ❌ Missing       | Admin account with `password` default = single point of compromise.                  | Add Laravel Fortify 2FA for admin+executive roles.                                                                                                       |
| Monitoring & alerting                  | **High**     | ❌ Not specified | No visibility into system health, error rates, queue backlog.                        | Sentry for error tracking. Laravel Pulse for system monitoring.                                                                                          |
| Data migration pilot                   | **High**     | ❌ Not specified | 2,500+ customers from Odoo — first migration attempt will fail.                      | Write migration script. Run against a copy of Odoo data. Validate results. THEN import.                                                                  |
| Rate limiting on APIs                  | **Medium**   | ❌ Not specified | No protection against brute force login, API abuse.                                  | Laravel's built-in `throttle` middleware. Configure for login, invoice creation.                                                                         |
| Branch schema                          | **Medium**   | 🟡 Unclear       | Branches can be either companies or warehouses. No decision.                         | Decide: branches = `companies` with `parent_id` if separate tax registration. `warehouses.type='branch'` if same tax registration but physical location. |
| Expense approval                       | **Medium**   | ❌ Missing       | Rep can log any expense amount without approval.                                     | Add threshold: expenses > X EGP require manager approval.                                                                                                |
| Cash handover                          | **Medium**   | ❌ Missing       | Cash box has no outflow to company bank.                                             | Add "Cash Deposit" workflow.                                                                                                                             |
| Return inspection gate                 | **Medium**   | ❌ Missing       | Returned goods go straight back to van stock.                                        | Add quarantine status for returns.                                                                                                                       |
| Photo attachments                      | **Medium**   | ❌ Missing       | No customer photos, visit photos, expense receipt photos.                            | Use `spatie/laravel-medialibrary`. Add to visits, expenses, complaints.                                                                                  |
| Automated supplier performance scoring | **Low**      | ❌ Missing       | No data-driven supplier selection.                                                   | Defer to v2. Data builds up first.                                                                                                                       |
| AI route optimization                  | **Low**      | ❌ Missing       | Deferred to v2. Correctly prioritized.                                               | —                                                                                                                                                        |
| WhatsApp integration                   | **Low**      | ❌ Missing       | Share invoice via WhatsApp. Nice-to-have.                                            | Add "Share" button. Uses browser's `navigator.share()`. Works on Android with WhatsApp installed.                                                        |

---

## 8. UI/UX AUDIT

### 8.1 Strengths

- RTL-first design with Arabic primary language
- Mobile-first PWA approach
- Large touch targets (44px minimum)
- Card-based mobile lists (correct for field use)
- Dark mode for outdoor visibility
- Wireframes exist for all key screens

### 8.2 Critical Gaps

#### Gap 1: No Offline UX

**Problem:** The single biggest usability risk. The rep is in 6th October industrial zone. Mobile data is unreliable. What does the app show?

**Current spec:** Nothing. App assumed always online.

**Fix:**

- Show persistent banner: "أنت غير متصل" / "You are offline"
- Gray out operations that require server (invoice creation, payment recording)
- Allow viewing cached visit list and customer info
- Auto-retry when connection returns

#### Gap 2: No Loading States

**Problem:** Wireframes show static screens. No skeleton loaders, progress bars, or spinner states specified.

**Fix:** Every Livewire component should have a loading state (Filament has this built-in). Document: "Use `wire:loading` and `wire:loading.remove` for all async operations."

#### Gap 3: No Confirmation Dialogs

**Problem:** One tap on "Create Invoice" creates a legally binding document. One tap on "End Day" closes all operations. No confirmation dialog specified.

**Fix:** Confirmation modals for: create invoice, submit report, collect payment, end day. Bilingual: "هل أنت متأكد؟ / Are you sure?"

#### Gap 4: Stock Search UX Not Defined

**Problem:** Wireframes show a search bar. But searching 500+ products with SKUs, Arabic names, English names, and barcodes needs fuzzy search.

**Fix:** Use Laravel Scout (Meilisearch or Typesense) for full-text search across products. Minimum: SQL `WHERE name_ar ILIKE ? OR name_en ILIKE ? OR sku ILIKE ? OR barcode IN (?)`.

#### Gap 5: Invoice PDF Not Previsualized

**Problem:** Rep creates invoice, system generates PDF. But rep never sees the PDF before sharing. They might want to verify it.

**Fix:** After invoice creation, show a "Preview PDF" button that opens the generated PDF in a new tab. Then "Share" button.

#### Gap 6: Alarm Bell Lacks Urgency Differentiation

**Problem:** Spec shows simple badge count. A rep with 3 active alarms (1 critical, 2 info) sees "3." No differentiation.

**Fix:** Color-coded badge: red if critical alarms exist, yellow if only warnings, gray if only info.

#### Gap 7: No Keyboard/Scanner Support for Forms

**Problem:** Reps may use Bluetooth barcode scanners. No tab-index or auto-focus on item entry fields specified.

**Fix:** Item entry field should auto-focus after adding a line. Support barcode scanner input (scanner types characters + Enter). Focus trap on form.

### 8.3 Accessibility Audit (WCAG 2.2)

| Check                | Status       | Issue                                                                                                                                                                      |
| -------------------- | ------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Color contrast       | 🟡 Implicit  | Teal green #4DB848 on white = ~2.5:1 contrast ratio. Fails WCAG AA (requires 4.5:1 for text). **Fix:** Use darker teal (#3A9A36 or #2D8828) for text on white backgrounds. |
| Touch targets ≥ 44px | ✅ Specified | Explicitly in UI spec.                                                                                                                                                     |
| Screen reader labels | ❌ Missing   | No ARIA labels in wireframes. Bilingual labels needed.                                                                                                                     |
| Keyboard navigation  | 🟡 Partial   | Filament handles this. PWA needs explicit tab order.                                                                                                                       |
| Focus indicators     | ❌ Missing   | Need visible focus rings for keyboard users.                                                                                                                               |
| Error announcements  | ❌ Missing   | Form errors should be announced via `role="alert"`.                                                                                                                        |

---

## 9. CODE QUALITY AUDIT

**N/A** — no code exists. However, the specification creates code quality constraints that should be documented now:

### Pre-conditions for Good Code Quality

| Constraint                       | Defined?            | Enforcement                                                                                                              |
| -------------------------------- | ------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| PSR-12 coding standard           | ❌ Not mentioned    | Must add `laravel/pint` or `friendsofphp/php-cs-fixer`                                                                   |
| Type declarations on all methods | ✅ In openapi.md    | Service interfaces have typed params/returns                                                                             |
| No raw SQL in controllers        | ✅ Via StockService | All DB operations through services                                                                                       |
| Repository pattern               | ❌ Not specified    | Filament accesses Eloquent directly. Acceptable for v1.                                                                  |
| Unit test minimum coverage       | ❌ Not specified    | This WILL result in zero tests. Must define a floor (e.g., 60% coverage for services, 0% for Livewire components in v1). |

---

## 10. SECURITY AUDIT

### 10.1 Risk Matrix

| Risk                                 | Likelihood | Impact   | Score        | Mitigation                                                                                                                          |
| ------------------------------------ | ---------- | -------- | ------------ | ----------------------------------------------------------------------------------------------------------------------------------- |
| SQL Injection                        | Low        | Critical | **High**     | Laravel's query builder + Eloquent are safe by default. Risk only if raw SQL is used. **Ban raw SQL in project style guide.**       |
| XSS via barcode input                | Medium     | High     | **High**     | Barcode scanner inputs should be sanitized. Barcode scan field could inject HTML if attacker prints malicious barcode.              |
| CSRF                                 | Low        | High     | **Medium**   | Laravel's CSRF protection is on by default for web routes. Ensure Sanctum/Sanctum tokens for PWA.                                   |
| Sensitive data exposure (cost price) | Medium     | Critical | **Critical** | Current plan: `hidden(fn())` on Filament field. This hides from UI but what about API responses, export files, PDF generation?      |
| Weak default passwords               | High       | High     | **Critical** | Seed: `password` for all demo users. Must document: "Change ALL passwords before production. Enforce minimum 8 chars + complexity." |
| Missing authentication on PWA        | Low        | Critical | **High**     | Sanctum token-based auth for PWA. If tokens are stored insecurely in browser storage, session hijack is possible.                   |
| Lack of rate limiting                | Medium     | Medium   | **Medium**   | Brute force login, API abuse. Laravel's `throttle` middleware must be configured.                                                   |
| Insecure file uploads                | Medium     | High     | **High**     | COA PDF uploads must be validated (mime type, size limit). Malicious PDF → server compromise.                                       |
| No audit log                         | High       | Medium   | **High**     | Egyptian e-invoicing regulations REQUIRE audit trail for all invoice changes. Legal risk.                                           |

### 10.2 Security Checklist

| Requirement               | Status             | Action                                                                                                            |
| ------------------------- | ------------------ | ----------------------------------------------------------------------------------------------------------------- |
| HTTPS enforced            | ❌ Not mentioned   | Production: enforce HTTPS via Vercel/VPS reverse proxy.                                                           |
| HSTS headers              | ❌ Not mentioned   | Add `Strict-Transport-Security` header.                                                                           |
| Content Security Policy   | ❌ Not mentioned   | CSP headers to prevent XSS.                                                                                       |
| Password hashing          | ✅ Laravel default | Bcrypt/Argon2.                                                                                                    |
| Password policy           | ❌ Not mentioned   | Minimum length, complexity, rotation.                                                                             |
| Account lockout           | ❌ Not mentioned   | After N failed login attempts, lock for M minutes.                                                                |
| 2FA for admin+executive   | ❌ Not mentioned   | High-priority.                                                                                                    |
| Session timeout           | ❌ Not mentioned   | PWA session should expire after inactivity.                                                                       |
| API token rotation        | ❌ Not mentioned   | Sanctum tokens should expire.                                                                                     |
| SQL injection prevention  | ✅ Laravel default | But need to enforce no raw SQL.                                                                                   |
| XSS prevention            | ✅ Laravel Blade   | Auto-escaped. Livewire also escapes.                                                                              |
| CSRF protection           | ✅ Laravel default | Enabled on web routes.                                                                                            |
| File upload validation    | ❌ Not documented  | Need: mime type validation, size limit (max 10MB), virus scanning?                                                |
| Audit logging             | ❌ Missing         | Need `spatie/laravel-activitylog` or equivalent.                                                                  |
| Data encryption at rest   | ❌ Not mentioned   | At minimum: encrypt database backups.                                                                             |
| Cost price access control | 🟡 Partial         | Hidden in Filament. Need to verify: exports, PDFs, API responses, database queries, error messages.               |
| GDPR / data privacy       | ❌ Not mentioned   | Egyptian data protection law (Law 151/2020) requires: user data export, deletion on request, breach notification. |

---

## 11. PERFORMANCE AUDIT

### 11.1 Query Performance Assessment (Estimated)

| Query                      | Frequency            | Rows at Scale    | Estimated Time | Issue                                                                                     |
| -------------------------- | -------------------- | ---------------- | -------------- | ----------------------------------------------------------------------------------------- |
| Today's visits for rep     | Rep opens app        | ~5 rows          | <10ms          | ✅ Fine                                                                                   |
| Stock search (LIKE query)  | Every stock lookup   | 500 products     | 50-200ms       | ❌ No full-text index. With ILIKE on 3 columns, slow at scale.                            |
| Sales dashboard (month)    | Every dashboard load | ~4,000 invoices  | 1-5s           | ❌ 10+ separate queries without caching.                                                  |
| Stock movements by product | Audit/report         | 5,000+ rows/year | 500ms-2s       | ❌ No index on product_id alone.                                                          |
| Alarm badge count          | Every 30s poll       | <100 open alarms | <10ms          | ✅ Fine.                                                                                  |
| Invoice PDF generation     | Every sale           | —                | 1-3s           | ❌ mPDF with Arabic fonts is CPU-intensive. Queued generation needed if >50 invoices/day. |

### 11.2 Bottlenecks to Address

1. **PDF generation is synchronous** — `InvoiceService::generatePdf()` runs in the request lifecycle. If mPDF takes 3 seconds, the rep waits 3 seconds. With 50+ invoices/day, the rep spends 2.5 minutes/day waiting for PDFs.
   - **Fix:** Generate PDF in a queued job. Return immediately. Notify rep when ready. Or generate synchronously in v1 (acceptable at 50/day) with a note to queue when >100/day.

2. **Alarm polling frequency** — Livewire polls every 30 seconds. With 10 users, that's 20 requests/minute. Fine for v1. But doesn't scale to 100+ users.
   - **Fix:** Use Laravel Reverb (native WebSocket) for real-time alarm pushes. Document this as a post-v1 optimization.

3. **No database connection pooling** — Single PG connection per FPM process. With 10 concurrent users, that's 10 connections. Fine for v1. Not fine for peak load.
   - **Fix:** Configure `pgbouncer` for connection pooling. Or use `laravel/octane` (Roadrunner/Swoole) when scaling beyond 50 users.

### 11.3 Caching Strategy (Currently None)

| Cache Target            | TTL        | Strategy                                                                      | Priority   |
| ----------------------- | ---------- | ----------------------------------------------------------------------------- | ---------- |
| Product catalog         | 1 hour     | `Cache::remember('products', 3600, fn() => Product::with('category')->get())` | **High**   |
| Customer list (non-rep) | 5 minutes  | `Cache::remember('customers_count', 300, fn() => Customer::count())`          | **Medium** |
| Dashboard aggregates    | 15 minutes | Materialized view or cache                                                    | **High**   |
| Stock search results    | 1 minute   | Per-query cache key: `stock_search_{query}`                                   | **Medium** |
| Price list              | 1 hour     | Cache price list query                                                        | **Medium** |

---

## 12. API AUDIT

### 12.1 Internal API Design Assessment

The openapi.md defines 7 service interfaces. This IS the system's internal API. Assessment:

| Service             | Quality | Issues                                                                                                                                 |
| ------------------- | ------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| StockService        | 8/10    | Clean. Missing: `reserveStock`, `releaseStock` for pending orders.                                                                     |
| InvoiceService      | 7/10    | `createInvoice(array $data)` takes a raw array. Should accept a typed DTO or FormRequest. Missing: `getPdfContent()`, `sendInvoice()`. |
| PriceService        | 7/10    | Clean. Missing: `getHistoricalPrices()`, `getCurrentPriceForCustomer()`.                                                               |
| VisitService        | 8/10    | Clean. `calculateDistance()` is a utility method, should it be here or in a GeoService?                                                |
| CashBoxService      | 6/10    | Too many side effects. `deposit()` and `withdraw()` should return void and let events handle balance updates.                          |
| AlarmService        | 7/10    | Good. Missing: `escalateAlarm()`, `getAlarmSummary()`.                                                                                 |
| NamingSeriesService | 8/10    | Clean. Atomic increment pattern correct.                                                                                               |

**Total Internal API Quality: 7.3/10** — Good foundation. Needs DTOs for method parameters, more granular events, and utility extraction.

### 12.2 Missing External API

**There is NO external API.** The system has no REST/GraphQL endpoints. This is intentional for v1 (Laravel + Livewire only), but it creates a problem:

- **No integration path** — If a future mobile app (native Android/iOS) is needed, the entire application would need an API layer. Livewire cannot serve a native app.
- **No third-party integration** — If GPC wants to integrate with a logistics provider, bank, or government portal, there's no API to call.

**Recommendation:** Add a lightweight read-only API in v1 using Laravel's API routes (`routes/api.php`) for: product lookup, customer info, stock availability. This gives integration flexibility without committing to a full API-first architecture.

---

## 13. BUSINESS PROCESS AUDIT

### 13.1 Process Coverage vs Real Operations

| GPC Operation                      | Covered by Jawla? | Gap                                                                                                                       |
| ---------------------------------- | ----------------- | ------------------------------------------------------------------------------------------------------------------------- |
| Supplier negotiation (phone/email) | 🟡 Partial        | Supplier quotations in system, but initial sourcing/negotiation happens outside. System captures result only.             |
| Price quotation → negotiation      | ✅ Full           | Multi-level pricing chain covers this.                                                                                    |
| Field visit → order → delivery     | ✅ Full           | GPS visit → proforma → invoice covers field sales.                                                                        |
| Warehouse stock receiving          | 🟡 Partial        | GIT covers international. Local PO receipt is manual.                                                                     |
| Customer complaint handling        | ✅ Full           | Complaint lifecycle defined.                                                                                              |
| Daily cash reconciliation          | ❌ Missing        | No end-of-day cash reconciliation report matching: opening balance + collections - expenses - handover = closing balance. |
| Monthly financial closing          | ❌ Missing        | No period-end close, no P&L, no balance sheet.                                                                            |
| Annual physical inventory          | ❌ Missing        | No cycle counting or physical inventory reconciliation.                                                                   |
| Tax filing (monthly VAT)           | 🟡 Partial        | QR code on invoices. But no VAT return report. No monthly VAT summary by rate.                                            |
| KPI review meeting (weekly)        | 🟡 Partial        | 59 KPIs defined but no scheduled distribution, no target vs actual tracking.                                              |

### 13.2 Automation Opportunities

| Process                       | Current State     | Automation Potential                                                                  | Effort |
| ----------------------------- | ----------------- | ------------------------------------------------------------------------------------- | ------ |
| Daily visit assignment        | Manual by manager | Auto-assign: "assign 5 customers on this route to this rep" based on route membership | 2 days |
| Price validity check          | Manual check      | Auto-expire quotations after 24h                                                      | 1 day  |
| Alarm escalation              | Manual follow-up  | Auto-escalate to executive if alarm unread for N hours                                | 1 day  |
| Reorder point alerts          | Manual review     | Auto-PO suggestion when stock < reorder level                                         | 2 days |
| Customer duplicate detection  | Manual check      | Auto-flag on creation (already in spec)                                               | 1 day  |
| Invoice overdue reminder      | None              | Auto-SMS/email for collections (post-v1 when credit sales added)                      | 2 days |
| Supplier delivery delay alert | None              | Auto-alarm when PO expected_delivery_date passed (similar to GIT alarm)               | 1 day  |

### 13.3 Operational Bottlenecks the Spec Creates

1. **Manager-dependent pricing chain** — Every price quotation requires manager action. If manager is in a meeting, rep waits. No delegation or auto-approval for small deviations.
   - **Fix:** Add auto-approval rules: "If price is within 2% of base_price AND quantity < 5 tons → auto-approve."

2. **No rep self-service for common issues** — What if rep forgets to start work session? What if GPS fails? All these require admin intervention.

3. **Manual daily stock import** — Warehouse keeper imports stock daily. If import fails, reps see stale data. No fallback to last known good data.

---

## 14. REPORTING AUDIT

### 14.1 KPI Catalog Assessment

| Category   | Count | Quality | Missing                                                    |
| ---------- | ----- | ------- | ---------------------------------------------------------- |
| Sales      | 10    | 8/10    | No year-over-year comparison, no target vs actual          |
| Visits     | 8     | 7/10    | No trend analysis, no rep ranking with percentiles         |
| Financial  | 11    | 5/10    | No P&L, no balance sheet, no cash flow. No VAT return.     |
| Stock      | 8     | 7/10    | No moving average cost trend, no write-off tracking        |
| Purchasing | 5     | 5/10    | No supplier lead time trend, no quality score              |
| Alarms     | 7     | 7/10    | No SLA compliance rate, no escalation effectiveness        |
| Customer   | 6     | 6/10    | No lifetime value, no churn prediction                     |
| Executive  | 4     | 6/10    | No daily snapshot email, no push notification on anomalies |

### 14.2 Missing Critical Reports

| Report                           | Why Needed                                                               | Priority     |
| -------------------------------- | ------------------------------------------------------------------------ | ------------ |
| **VAT Return**                   | Monthly compliance. Sum of output VAT minus input VAT.                   | **Critical** |
| **Daily Sales Summary (Arabic)** | Morning WhatsApp to executive. Printed in Arabic.                        | **High**     |
| **Stock Aging**                  | Days since last movement for each product batch. Identifies dead stock.  | **Medium**   |
| **Rep Commission Calculation**   | Auto-calculate commission based on sales.                                | **Medium**   |
| **Customer Statement**           | Transaction history for a customer (for their records).                  | **Medium**   |
| **Invoice Register**             | Tax authority inspection. Sequential list of all invoices with QR codes. | **High**     |
| **Cash Flow Forecast**           | Based on pending quotations → expected invoices → expected payments.     | **Low**      |

### 14.3 Dashboard Design Issues

- **Executive dashboard** shows the same widgets as admin. Executive needs: "Today's sales. Yesterday's sales. Top alarm. Visit compliance. That's it."
- **No comparison data** — KPIs show current value but not previous period or target.
- **No export button on charts** — Must export table separately.
- **No scheduled report delivery** — "Email me the daily sales report at 8 AM" is not supported.

---

## 15. TESTING AUDIT

### 15.1 Current State: Zero

**There is no testing strategy.** The word "test" appears 3 times in all documentation, always in the context of "manual test pass" in Phase 19.

### 15.2 Required Test Coverage

| Layer                        | Coverage Target     | Tests Required                                                                                 |
| ---------------------------- | ------------------- | ---------------------------------------------------------------------------------------------- |
| Service layer (StockService) | 100% of methods     | 8-10 tests covering: add, remove, transfer, insufficient stock, negative quantity, concurrency |
| InvoiceService               | 100% of flows       | 10-15 tests: create, cancel, amend, PDF generation, QR generation, insufficient stock          |
| PriceService                 | 100% of validations | 8-10 tests: valid range, out of range, expired quotation, rep+ manager- boundary               |
| Business rules (24 rules)    | 100% per rule       | 1 test per rule = 24 tests minimum                                                             |
| Livewire components          | Critical paths      | 5-8 tests: invoice creation flow, visit flow, login, customer creation                         |
| Database constraints         | Critical            | 5-8 tests: unique constraints, CHECK constraints, FK integrity                                 |
| API (if added)               | 100% of endpoints   | Per-endpoint: success + validation error + auth error                                          |

**Minimum viable test suite: ~60 tests** — achievable in 3-4 days of focused TDD.

### 15.3 Risk Without Tests

- StockService bug: $quantity instead of -$quantity → stock increases when it should decrease → inventory drift → physical reconciliation finds the error days later
- PriceService bug: wrong comparison operator → rep can sell at any price → margin loss
- Concurrency bug: two simultaneous sales → both pass stock check → negative stock → blocked by DB constraint? Not if constraint is missing (see §4.4)

---

## 16. DEVOPS AUDIT

### 16.1 Current State: Nothing

| Component           | Status           | Risk                                                                |
| ------------------- | ---------------- | ------------------------------------------------------------------- |
| Version control     | ❌ Not set up    | No git repo. No commit history. No collaboration.                   |
| CI/CD pipeline      | ❌ Not defined   | Manual deployment. No automated testing.                            |
| Docker              | ❌ Not defined   | No containerized dev environment. Setup will vary per developer.    |
| Server provisioning | ❌ Not defined   | "VPS" — no specs, no OS, no config management.                      |
| Database migration  | ❌ Not automated | `php artisan migrate` on every deploy. No rollback plan.            |
| Backup              | ❌ Not defined   | No backup schedule, no retention policy, no restore drill.          |
| Monitoring          | ❌ Not defined   | No error tracking, no uptime monitoring, no performance monitoring. |
| Logging             | ❌ Not defined   | Laravel logs only. No centralized logging, no log rotation policy.  |

### 16.2 Minimum DevOps Requirements for Production

| Requirement                                | Time to Implement | Cost                  |
| ------------------------------------------ | ----------------- | --------------------- |
| GitHub repository                          | 10 minutes        | Free                  |
| Dockerfile + docker-compose.yml            | 2-4 hours         | Free                  |
| GitHub Actions (lint + test + deploy)      | 4-8 hours         | Free (public minutes) |
| VPS provisioning script (Ansible / bash)   | 2-4 hours         | Free                  |
| Automated database backups (pg_dump + S3)  | 2 hours           | ~$5/month for S3      |
| Sentry error tracking                      | 1 hour            | Free tier             |
| Laravel Pulse monitoring                   | 1 hour            | Free                  |
| Uptime monitoring (Better Stack / Pingdom) | 30 minutes        | Free tier             |
| SSL certificate (Let's Encrypt)            | 30 minutes        | Free                  |

**Total DevOps setup: ~16-24 hours.** Without this, the system cannot be safely deployed to production.

---

## 17. DOCUMENTATION AUDIT

### 17.1 Current Documentation Inventory

| Document                  | Quality | Gaps                                                                                               |
| ------------------------- | ------- | -------------------------------------------------------------------------------------------------- |
| Build Guide               | 9/10    | Most comprehensive single source of truth.                                                         |
| Context (Glossary)        | 8/10    | Good. Missing: branch types, packaging types per the research report updates.                      |
| Master Spec (BRD+SRS+FRS) | 8/10    | Excellent. Missing: deployment architecture, data retention policy.                                |
| ERD                       | 7/10    | Mermaid diagram. Missing: indexes, CHECK constraints, cascade behaviors.                           |
| BPMN                      | 8/10    | 7 workflows. Missing: cross-entity transaction (v2, acceptable), cash handover, return inspection. |
| UI Spec                   | 7/10    | Good wireframes. Missing: loading states, error states, offline UX, empty states.                  |
| OpenAPI / Contracts       | 7/10    | Good service interfaces. Missing: event contracts, DTO definitions.                                |
| Permission Matrix         | 9/10    | 94 permissions, all roles. Complete.                                                               |
| KPI Catalog               | 7/10    | 59 KPIs. Missing: target values, alert thresholds, trend direction indicators.                     |
| Backlog                   | 8/10    | 19 phases. Missing: time estimates, dependencies, resource allocation.                             |
| Deep Research Report      | 9/10    | Excellent GPC intelligence. Validates business alignment.                                          |

### 17.2 Missing Documentation

| Document                   | Why Needed                                                                                  | Priority     |
| -------------------------- | ------------------------------------------------------------------------------------------- | ------------ |
| **Deployment Guide**       | Step-by-step VPS setup, environment variables, first deploy.                                | **Critical** |
| **User Manual (Arabic)**   | End-user documentation for reps. Not just admin training.                                   | **High**     |
| **Operations Manual**      | Daily tasks: "What to do when stock import fails." "How to reconcile cash at end of month." | **High**     |
| **Backup & Recovery Plan** | What to do when the server crashes. How to restore from backup. RPO and RTO targets.        | **Critical** |
| **Administrator Manual**   | How to add users, reset passwords, monitor system health.                                   | **Medium**   |
| **Data Migration Guide**   | Step-by-step Odoo migration with validation checkpoints.                                    | **High**     |
| **Security Policy**        | Password policy, access control, incident response.                                         | **High**     |

---

## 18. RISK ASSESSMENT

### 18.1 Risk Matrix

| Risk                                                     | ID   | Probability | Impact   | Score  | Mitigation                                         | Owner  |
| -------------------------------------------------------- | ---- | ----------- | -------- | ------ | -------------------------------------------------- | ------ |
| **No audit logging → legal non-compliance**              | R-01 | High        | Critical | **16** | Add `spatie/laravel-activitylog`                   | Dev    |
| **Cost price leak to sales roles**                       | R-02 | Medium      | Critical | **12** | Multiple layers: API, UI, export, DB, PDF          | Dev    |
| **Offline PWA = useless for reps**                       | R-03 | High        | High     | **12** | Implement offline caching + service worker         | Dev    |
| **No financial statements → keep Odoo**                  | R-04 | High        | High     | **12** | Add simple double-entry ledger                     | Dev    |
| **Push notifications missing → critical alarms ignored** | R-05 | High        | High     | **12** | Add FCM or email alerts                            | Dev    |
| **Default passwords in production**                      | R-06 | Medium      | Critical | **12** | Document password change as launch gate            | Admin  |
| **Data migration from Odoo fails**                       | R-07 | Medium      | High     | **9**  | Run dry-run migration before launch                | Dev    |
| **Negative stock due to race condition**                 | R-08 | Medium      | High     | **9**  | Add DB CHECK constraint + row-level lock           | Dev    |
| **Branch schema undefined → rework**                     | R-09 | Medium      | Medium   | **6**  | Decide before Phase 1                              | Client |
| **Moving Average cost method wrong for accounting**      | R-10 | Medium      | Medium   | **6**  | Validate with GPC accountant before implementation | Client |
| **No test coverage → regression bugs**                   | R-11 | High        | Medium   | **6**  | Add minimum 60 test cases                          | Dev    |
| **Rep phone storage cleared → session loss**             | R-12 | Medium      | Medium   | **6**  | Server-side session + restore from API             | Dev    |
| **Invoice PDF generation timeout**                       | R-13 | Low         | Medium   | **3**  | Queue PDF generation if >100/day                   | Dev    |
| **Concurrent invoice number collision**                  | R-14 | Low         | High     | **3**  | Atomic DB increment in NamingSeriesService         | Dev    |

**Risk Score = Probability × Impact** (1=Low, 4=High, score range 1-16)

### 18.2 Top 5 Risks to Launch

1. **R-03: No offline capability** — If the PWA requires constant connectivity, the system fails in its primary use case. This is the #1 risk.
2. **R-01: No audit trail** — Egyptian e-invoicing law requires detailed audit logs. Non-compliance is a legal risk.
3. **R-04: No financial statements** — If the system cannot produce a P&L, the client will not fully adopt it. Odoo stays. "Single source of truth" objective fails.
4. **R-05: No push notifications** — Critical alarms (OOS, complaints, GIT delays) will be missed. The 2-hour SLA for price quotations will not be met.
5. **R-07: Data migration failure** — The first migration attempt from Odoo will almost certainly fail on data quality issues. Without a pilot, this is discovered on launch day.

---

## 19. TECHNICAL DEBT REGISTER

| Debt ID | Description                                                                               | Created By   | Cost to Fix | Business Impact                           | Priority     |
| ------- | ----------------------------------------------------------------------------------------- | ------------ | ----------- | ----------------------------------------- | ------------ |
| TD-01   | Price and cost columns on `products` table + `product_prices` table = dual pricing system | Architecture | 2 days      | Price confusion, reporting errors         | **High**     |
| TD-02   | No DB CHECK constraints on stock, payments, invoices                                      | Omission     | 1 day       | Silent data corruption possible           | **High**     |
| TD-03   | `companies.bank_name` etc. duplicated with `company_bank_accounts`                        | Architecture | 0.5 days    | Data inconsistency                        | **Medium**   |
| TD-04   | `suppliers.payment_terms` as text instead of FK                                           | Architecture | 1 day       | Cannot query by payment terms             | **Low**      |
| TD-05   | No full-text search index on products                                                     | Omission     | 0.5 days    | Slow stock search                         | **Medium**   |
| TD-06   | Synchronous PDF generation                                                                | Architecture | 1 day       | Rep waits 2-3s per invoice                | **Low**      |
| TD-07   | No caching strategy anywhere                                                              | Omission     | 2 days      | Dashboard performance degrades over time  | **Medium**   |
| TD-08   | Livewire components calling services directly without FormRequest                         | Architecture | 2 days      | Validation logic duplicated, hard to test | **Medium**   |
| TD-09   | No `SELECT ... FOR UPDATE` on stock operations                                            | Architecture | 0.5 days    | Race condition on last-unit sales         | **High**     |
| TD-10   | No middleware for cost price filtering on exports/PDFs                                    | Security     | 1 day       | Cost price leak                           | **Critical** |
| TD-11   | `price_quotations.valid_until` nullable                                                   | Omission     | 0.5 days    | Indefinitely valid quotations             | **Medium**   |
| TD-12   | No partition strategy for `stock_movements`                                               | Architecture | 2 days      | Performance at scale                      | **Low**      |

**Total Estimated Debt Fix Cost at Spec Phase: 12 days** (before a single line of code is written).

If fixed now, these are schema/architecture changes. Cost: low.
If fixed after Phase 3 (when data exists): Cost multiplies by 3-5x.

---

## 20. PRODUCTION READINESS

### Verdict: ❌ NOT Production Ready — Prototype Phase

**Justification:**

- Zero lines of code written
- Zero tests defined
- Zero deployment infrastructure
- Zero backup strategy
- Zero monitoring
- Zero documentation for end users
- Critical gaps in offline capability, notifications, and financial reporting
- High-risk assumptions about data migration

### Path to Production: Gates

**Gate 1 — Design Complete** (Current status: ✅ PASS with caveats)

- 7 caveats from this audit must be addressed before coding

**Gate 2 — Phase 0-3 Complete** (Admin panel, auth, master data)

- `php artisan migrate:fresh --seed` works
- All 7 roles can login
- Admin can create customers, products, users
- Cost price hidden from sales roles

**Gate 3 — Phase 4-8 Complete** (Rep PWA, visits, pricing, invoicing)

- Rep can complete full sale cycle
- GPS geofence works
- Atomic sale correct (manual test: kill connection mid-sale, rollback verified)
- Invoice PDF with QR valid
- Min 60 tests passing

**Gate 4 — Phase 9-14 Complete** (Financial ops, procurement, GIT, e-invoicing)

- End-to-end workflow tested: GIT creation → landed cost → stock receipt → sale to customer
- ETA QR code validated with government scanner
- Alarm system triggers all 7 types

**Gate 5 — Phase 16-19 Complete** (Reports, migration, PWA, seed)

- Data migration dry-run successful
- PWA installable from browser
- Executive dashboard functional
- README with credentials exists
- Manual test pass signed off by client

**Gate 6 — DevOps Ready** (Before production launch)

- [ ] HTTPS configured
- [ ] Automated backups running
- [ ] Sentry error tracking active
- [ ] Monitoring dashboard (Laravel Pulse) deployed
- [ ] CI/CD pipeline: push → test → deploy
- [ ] Default passwords changed
- [ ] SSL certificate valid
- [ ] Rate limiting configured
- [ ] Audit logging active

---

## 21. FINAL RECOMMENDATIONS

### 21.1 Top 10 Quick Wins (Fix Before Coding Starts)

| #   | Recommendation                                                                                     | Effort              | Impact                                |
| --- | -------------------------------------------------------------------------------------------------- | ------------------- | ------------------------------------- |
| 1   | Add DB CHECK constraints to migrations (stock >= 0, amount > 0, total >= 0)                        | 1 hour              | Data integrity. Cheap insurance.      |
| 2   | Add `audit_logs` table + `spatie/laravel-activitylog` to Phase 1                                   | 2 hours             | Egyptian e-invoicing compliance.      |
| 3   | Split `companies.bank_name/account/iban` → remove from companies, use only `company_bank_accounts` | 1 hour              | Eliminates dual-data-source risk.     |
| 4   | Remove `products.price` and `products.cost`. Use `product_prices` exclusively                      | 2 hours             | Single source of truth for pricing.   |
| 5   | Make `price_quotations.valid_until` required with 24h default                                      | 1 hour              | Prevents stale price usage.           |
| 6   | Add `SELECT ... FOR UPDATE` to StockService::removeStock                                           | 1 hour              | Prevents race condition on last unit. |
| 7   | Decide branch schema before Phase 1 (companies.parent_id vs warehouses.type='branch')              | 1 hour consultation | Prevents rework.                      |
| 8   | Add EXPENSE APPROVAL THRESHOLD to spec — expenses > X EGP require manager approval                 | 1 hour              | Financial control.                    |
| 9   | Add `CashHandover` workflow to backlog (Phase 9 or after)                                          | 2 hours spec update | Completes cash flow cycle.            |
| 10  | Add RETURN INSPECTION GATE — returned goods go to quarantine before restocking                     | 2 hours spec update | Quality control.                      |

**Total Quick Wins: ~14 hours** of spec/code work. Prevents 5 major issues.

### 21.2 Top 10 Missing Features to Add Before Launch

| #   | Feature                                            | Phase          | Effort | Reason                                                   |
| --- | -------------------------------------------------- | -------------- | ------ | -------------------------------------------------------- |
| 1   | General ledger (journal entries)                   | Before Phase 8 | 3 days | System cannot produce financial statements without it    |
| 2   | Offline PWA support (service worker + cached data) | Phase 4        | 3 days | Reps need to work without connectivity                   |
| 3   | Push notifications (Firebase or email fallback)    | Phase 13       | 2 days | Critical alarms must reach managers                      |
| 4   | Full audit trail (activity log)                    | Phase 1        | 1 day  | Legal compliance + debugging                             |
| 5   | Stock replenishment pipeline (WH → van)            | Phase 8        | 1 day  | Without it, van runs out of stock                        |
| 6   | Cash handover / deposit to bank                    | Phase 9        | 1 day  | Cash box infinite growth without outflow                 |
| 7   | Auto-escalation for unread critical alarms         | Phase 13       | 1 day  | 2-hour SLA for pricing chain needs enforcement           |
| 8   | Price change audit log                             | Phase 6        | 1 day  | Margin analysis needs price history                      |
| 9   | Customer statement report                          | Phase 16       | 1 day  | Customers will ask for transaction history               |
| 10  | Product barcode field on invoice items             | Phase 8        | 1 day  | Warehouse needs to verify shipped items against invoices |

**Total Missing Features: ~15 days** — must be added to the backlog timeline.

### 21.3 Top 10 Architectural Improvements

| #   | Improvement                                                                                               | Effort  | Benefit                                                |
| --- | --------------------------------------------------------------------------------------------------------- | ------- | ------------------------------------------------------ |
| 1   | DTOs for service method parameters instead of `array $data`                                               | 2 days  | Type safety, documentation, IDE autocomplete           |
| 2   | Domain events for stock changes, price changes, status transitions                                        | 3 days  | Future event-driven architecture, audit, notifications |
| 3   | FormRequest validation before service calls                                                               | 1 day   | DRY validation, testable                               |
| 4   | Read model caching for dashboard widgets                                                                  | 2 days  | Sub-second dashboard loads                             |
| 5   | Full-text search (Laravel Scout + Meilisearch) for products                                               | 2 days  | Instant stock search                                   |
| 6   | Read-only REST API for mobile app future                                                                  | 2 days  | Integration flexibility                                |
| 7   | Queue PDF generation                                                                                      | 1 day   | Non-blocking invoice creation at scale                 |
| 8   | Row-level locking (`FOR UPDATE`) for stock operations                                                     | 0.5 day | Concurrency safety                                     |
| 9   | Materialized view for monthly sales reports                                                               | 1 day   | Fast report loading                                    |
| 10  | Environment-based configuration for all hardcoded values (geofence radius, alarm SLA, default price list) | 1 day   | Configurability without code changes                   |

**Total Architecture Improvements: ~15 days** — can be done incrementally across all 19 phases.

### 21.4 Top 10 Security Improvements

| #   | Improvement                                                                                                  | Effort  | Priority     |
| --- | ------------------------------------------------------------------------------------------------------------ | ------- | ------------ |
| 1   | Enforce HTTPS + HSTS on production                                                                           | 1 hour  | **Critical** |
| 2   | 2FA for admin and executive roles (Laravel Fortify)                                                          | 1 day   | **Critical** |
| 3   | Sanitize all file uploads (mime validation, size limit, virus scan)                                          | 1 day   | **Critical** |
| 4   | Rate limiting on login, invoice creation, all POST routes                                                    | 1 day   | **High**     |
| 5   | Cost price check: add middleware that filters cost price from ALL output (Blade, API, exports, PDFs, emails) | 1 day   | **Critical** |
| 6   | Content Security Policy headers via middleware                                                               | 1 day   | **High**     |
| 7   | Session timeout + token expiry for PWA                                                                       | 1 day   | **High**     |
| 8   | Password policy (min 8 chars, complexity) enforced at registration + Spatie                                  | 1 day   | **High**     |
| 9   | Input sanitization on barcode scan field                                                                     | 2 hours | **Medium**   |
| 10  | Set secure cookie flags (HttpOnly, Secure, SameSite)                                                         | 1 hour  | **Medium**   |

**Total Security Improvements: ~7 days** — some already in Laravel defaults, just need configuration.

### 21.5 AI Opportunities

| AI Feature                                                                                      | Value  | Effort  | When |
| ----------------------------------------------------------------------------------------------- | ------ | ------- | ---- |
| **Smart visit routing** — Optimize visit order based on GPS and customer priority               | High   | 5 days  | v2   |
| **Price prediction** — Suggest optimal price based on historical margins, market data           | Medium | 10 days | v2   |
| **Anomaly detection** — Flag unusual sales patterns (rep selling below cost, unusual discounts) | High   | 5 days  | v1.1 |
| **Smart alarm prioritization** — Auto-rank alarms by business impact, not just severity         | Medium | 3 days  | v1.1 |
| **Chat/voice interface** — Rep asks "What's my next visit?" via voice                           | Medium | 10 days | v2   |
| **Demand forecasting** — Predict which products to stock based on historical sales patterns     | High   | 10 days | v2   |

---

## 22. REVISED ROADMAP

### Phase 0 — Pre-Flight (Add before current Phase 0)

| Task                                            | Effort  | Dependencies    |
| ----------------------------------------------- | ------- | --------------- |
| Git repository setup                            | 1 hour  | None            |
| Dockerfile + docker-compose.yml                 | 4 hours | None            |
| GitHub Actions: lint on push                    | 2 hours | Git repo        |
| Install `laravel/pint` for code style           | 1 hour  | Laravel project |
| Install `spatie/laravel-activitylog` (audit)    | 2 hours | Laravel project |
| Add DB CHECK constraints to migration templates | 1 hour  | None            |
| Document security checklist                     | 2 hours | None            |

**Phase 0 Pre-Flight Effort: ~13 hours** — do before writing any application code.

### Revised Phase Order

```
Phase 0 Pre-Flight  [NEW]  — Devops, testing, security foundation
Phase 0 (Original)         — Laravel scaffold
Phase 1                    — All 50+ tables + CHECK constraints + audit_logs
  → At this point: DB constraints ensure data integrity from day zero
Phase 2                    — Auth + roles
Phase 3                    — Admin panel
  → At this point: all master data can be managed, audit trail active
Phase 4 + Offline Mode [UPDATED] — Rep PWA with service worker
Phase 5-9                  — Visits, pricing, sales, financial ops
  → At this point: complete field sales cycle works
Phase 10-14                — Procurement, GIT, e-invoicing, alarms
  → At this point: all v1 features done
Phase 16 NEW [MOVED UP]   — Reports + double-entry ledger (financial statements)
Phase 17-19                — Migration, PWA polish, seed, testing
  → At this point: ready for UAT with real data
Phase 20 NEW — Security hardening, 2FA, penetration test
Phase 21 NEW — Production deployment, monitoring, backup
```

### Effort Estimate (v1 Complete)

| Phase      | Original Estimate | Revised Estimate | Notes                                        |
| ---------- | ----------------- | ---------------- | -------------------------------------------- |
| Pre-Flight | —                 | 2 days           | New — DevOps + security + testing foundation |
| Phase 0    | 2 days            | 2 days           | As specified                                 |
| Phase 1    | 5 days            | 6 days           | + audit_logs, CHECK constraints, indexes     |
| Phase 2    | 1 day             | 1 day            | No change                                    |
| Phase 3    | 5 days            | 5 days           | No change                                    |
| Phase 4    | 3 days            | 4 days           | + service worker, offline cache              |
| Phase 5    | 3 days            | 3 days           | No change                                    |
| Phase 6    | 2 days            | 2 days           | No change                                    |
| Phase 7    | 2 days            | 2 days           | No change                                    |
| Phase 8    | 4 days            | 4 days           | + queue PDF generation                       |
| Phase 9    | 3 days            | 3 days           | + cash handover, return inspection           |
| Phase 10   | 3 days            | 3 days           | No change                                    |
| Phase 11   | 3 days            | 3 days           | No change                                    |
| Phase 12   | 2 days            | 2 days           | No change                                    |
| Phase 13   | 2 days            | 3 days           | + push notifications, auto-escalation        |
| Phase 14   | 2 days            | 2 days           | No change                                    |
| Phase 15   | Deferred          | —                | v2                                           |
| Phase 16   | 5 days            | 7 days           | + general ledger, financial reports          |
| Phase 17   | 4 days            | 5 days           | + dry-run migration, validation              |
| Phase 18   | 2 days            | 2 days           | No change                                    |
| Phase 19   | 2 days            | 3 days           | + test suite, security hardening             |
| Phase 20   | —                 | 2 days           | New — penetration test, security audit       |
| Phase 21   | —                 | 2 days           | New — production deployment                  |

**Total Revised Estimate: 66 working days** (original was ~55 days)

---

## 23. GO / NO-GO DECISION

### Current Verdict: ⚠️ CONDITIONAL GO

The project can proceed to implementation, subject to the following **12 conditions** being met:

**Must resolve BEFORE Phase 0 starts:**

1. [ ] Add DB CHECK constraints to all migration templates
2. [ ] Remove duplicate price/cost columns from `products`, use `product_prices` exclusively
3. [ ] Remove `companies.bank_name/account/iban`, keep only `company_bank_accounts`
4. [ ] Client confirms branch approach (separate companies vs warehouse locations)
5. [ ] Client confirms Moving Average cost method with accountant
6. [ ] Add `audit_logs` table + activity log package to Phase 1

**Must resolve BEFORE Phase 4 (Rep PWA):** 7. [ ] Offline capability strategy defined and approved 8. [ ] Push notification channel chosen (FCM vs email vs SMS) 9. [ ] Testing strategy documented with minimum coverage targets

**Must resolve BEFORE production launch:** 10. [ ] General ledger / double-entry added for basic financial statements 11. [ ] Data migration dry-run completed with validation 12. [ ] DevOps checklist complete (HTTPS, backups, monitoring, rate limiting)

---

_This audit was performed on 12 July 2026 by an automated review board. The findings represent a thorough analysis of all available documentation. No production code was reviewed because none exists. The recommendations are intended to be actionable before a single line of code is written._

_End of Audit Report._
