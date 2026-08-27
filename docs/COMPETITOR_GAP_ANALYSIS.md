# Bricks Rep vs Jawla — Live Competitor Gap Analysis

**Date:** 2026-08-23  
**Competitor:** Bricks Rep by GPC Company (gpccompany.bricks-rep.com)  
**Version:** 26.08.20-90e520c/151.0.0.0  
**Method:** Live browser exploration via Profile 22 (authenticated session)

---

## Competitor App Structure (Verified)

| Page            | URL                     | Features Observed                                                                                                                                                                       |
| --------------- | ----------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Home**        | `/home`                 | Dashboard: time range selector, leads visits (0), existing customers visits (0), add button                                                                                             |
| **Agenda**      | `/agenda`               | Visits tab, Todos tab, Record Non-planned Visit, Planned Visits, Recorded Visits                                                                                                        |
| **Calendar**    | `/calendar`             | Monthly calendar, prev/next/today nav, filter button, date range in URL                                                                                                                 |
| **Todos**       | `/todos`                | New/Done tabs, filter, Add new todo                                                                                                                                                     |
| **Contacts**    | `/contacts`             | Table/kanban/grid views, search, filter, Export data, Create contact, columns: name, Customer status, Area, Main address, Mobile, Class                                                 |
| **Tickets**     | `/tickets`              | Table/kanban views, search, filter, Add New Ticket, Status: جديد/معطل/قيد التنفيذ/اكتمل/ملغي                                                                                            |
| **Requests**    | `/requests`             | Table/kanban views, search, filter, Add New Request, Status: جديد/تم الموافقة/تم/تم الرفض                                                                                               |
| **Performance** | `/performance/overview` | Overview/Analysis/Daily/Detailed/Coverage tabs, Members filter, month nav, filter, metrics: Coverage, Frequency, Call rate, Plan achievement, working days, expenses, visits, detailing |
| **Reports**     | `/reports`              | Calls Report, Customers summary, custom reports request                                                                                                                                 |

---

## Jawla Features (from codebase inventory)

| Module            | Jawla Feature                                           |
| ----------------- | ------------------------------------------------------- |
| **Dashboard**     | Filament admin dashboard, Rep PWA home                  |
| **CRM**           | Customers, contacts, leads, customer types              |
| **Sales**         | Invoices, payments, returns, van stock, stock transfers |
| **Field**         | Visits (with GPS), routes, check-in/out                 |
| **Inventory**     | Products, stock, stock movements, warehouses            |
| **Finance**       | Cash box, expenses, collections                         |
| **HR**            | Users, roles, permissions, teams                        |
| **Reporting**     | Sales reports, visit reports, stock reports             |
| **Notifications** | Real-time notifications, broadcasts                     |
| **E-invoicing**   | ZATCA integration                                       |
| **Multi-lang**    | Arabic RTL + English LTR                                |

---

## Gap Analysis: What Bricks Rep Has That Jawla Doesn't

| #   | Feature                         | Bricks Rep                                                                                  | Jawla                   | Gap Severity | Notes                                                                                                                                          |
| --- | ------------------------------- | ------------------------------------------------------------------------------------------- | ----------------------- | ------------ | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **Calendar view**               | Monthly calendar with visit/task visualization                                              | ❌ No calendar view     | **HIGH**     | Bricks Rep shows visits on a monthly calendar. Jawla has visits but no calendar UI.                                                            |
| 2   | **Todos system**                | Dedicated Todos page with New/Done tabs, add/complete                                       | ❌ No todos             | **HIGH**     | Bricks Rep has a standalone task/todo system. Jawla has no equivalent.                                                                         |
| 3   | **Tickets system**              | Tickets with statuses (New/Disabled/In Progress/Completed/Cancelled)                        | ❌ No tickets           | **MEDIUM**   | Bricks Rep has a ticketing system. Jawla has no support ticket mechanism.                                                                      |
| 4   | **Requests system**             | Requests with approval workflow (New/Approved/Done/Rejected)                                | ❌ No requests          | **MEDIUM**   | Bricks Rep has a request/approval system. Jawla has no formal request workflow.                                                                |
| 5   | **Performance dashboard**       | Dedicated Performance page with Overview/Analysis/Daily/Detailed/Coverage tabs, 15+ metrics | ⚠️ Basic reporting only | **HIGH**     | Bricks Rep has a rich performance dashboard with coverage, frequency, call rate, plan achievement, detailing metrics. Jawla has basic reports. |
| 6   | **Contacts views**              | Table/kanban/grid toggle, export data                                                       | ⚠️ Basic customer list  | **LOW**      | Jawla has customers but no view toggle or export.                                                                                              |
| 7   | **Non-planned visit recording** | "Record Non-planned Visit" button on Agenda                                                 | ❌ No equivalent        | **MEDIUM**   | Bricks Rep allows recording visits outside the planned route.                                                                                  |
| 8   | **Agenda view**                 | Dedicated Agenda page combining visits + todos for the day                                  | ❌ No agenda            | **MEDIUM**   | Bricks Rep has a daily agenda combining all activities.                                                                                        |
| 9   | **Calls Report**                | Dedicated Calls Report page                                                                 | ❌ No calls tracking    | **LOW**      | Jawla doesn't track calls as a separate entity.                                                                                                |
| 10  | **Customers Summary**           | Dedicated Customers Summary report                                                          | ⚠️ Basic customer stats | **LOW**      | Jawla has customer data but no dedicated summary view.                                                                                         |

---

## Gap Analysis: What Jawla Has That Bricks Rep Doesn't

| #   | Feature                     | Jawla                                      | Bricks Rep                               | Advantage    |
| --- | --------------------------- | ------------------------------------------ | ---------------------------------------- | ------------ |
| 1   | **Invoicing**               | Full invoice creation with line items      | ❌ Not visible in rep app                | **CRITICAL** |
| 2   | **Payments**                | Payment collection and tracking            | ❌ Not visible in rep app                | **CRITICAL** |
| 3   | **Returns**                 | Return order processing                    | ❌ Not visible in rep app                | **CRITICAL** |
| 4   | **Van stock management**    | Stock levels, transfers, movements         | ❌ Not visible in rep app                | **HIGH**     |
| 5   | **Cash box**                | Cash management for reps                   | ❌ Not visible in rep app                | **HIGH**     |
| 6   | **GPS check-in/out**        | Visit verification with GPS                | ⚠️ Implied but not verified              | **MEDIUM**   |
| 7   | **ZATCA e-invoicing**       | Saudi e-invoicing compliance               | ❌ Not present                           | **CRITICAL** |
| 8   | **Multi-language RTL**      | Arabic RTL + English LTR from first commit | ⚠️ Arabic UI visible, RTL status unknown | **MEDIUM**   |
| 9   | **Real-time notifications** | Live notification system                   | ❌ Not visible                           | **MEDIUM**   |
| 10  | **Offline support**         | PWA with offline capability                | ❌ Not confirmed                         | **MEDIUM**   |

---

## Priority Recommendations

### Must Build (HIGH priority)

1. **Calendar view** — Add a monthly calendar showing visits/tasks. This is a standard field-sales feature that Bricks Rep demonstrates well.
2. **Todos/Tasks system** — Standalone todo management with New/Done states, integrated into the daily agenda.
3. **Performance dashboard** — Rich metrics page with Coverage, Frequency, Call rate, Plan achievement, detailing stats. This is a key differentiator for managers.

### Should Build (MEDIUM priority)

4. **Agenda view** — Daily agenda combining visits, todos, and tasks in one view.
5. **Tickets system** — Support ticket creation and tracking with status workflow.
6. **Requests system** — Request/approval workflow for manager sign-off.
7. **Non-planned visit recording** — Allow reps to record visits outside the planned route.

### Nice to Have (LOW priority)

8. **Contacts views** — Table/kanban/grid toggle, export functionality.
9. **Calls tracking** — Log calls as separate entities.
10. **Customers summary report** — Dedicated customer analytics view.

---

## Key Takeaways

1. **Bricks Rep is lighter than Jawla on transactional features** — No invoicing, payments, returns, or stock management visible in the rep app. This is Jawla's biggest advantage.

2. **Bricks Rep is stronger on daily workflow** — Calendar, Agenda, Todos, and Performance dashboard create a more complete daily workflow for reps and managers.

3. **Bricks Rep has a cleaner UI** — Material Design with table/kanban/grid toggles, search, and filter on every list page. Jawla's Filament UI is more admin-focused.

4. **Performance metrics are a key gap** — Bricks Rep's Performance page with 15+ metrics is a significant advantage for managers who need to monitor team performance.

5. **Jawla's transactional depth wins** — For companies that need invoicing, stock management, and e-invoicing (ZATCA), Jawla is the stronger choice.

---

## Next Steps

1. Add Calendar view to Jawla PWA
2. Build Todos/Tasks system
3. Create Performance dashboard with key metrics
4. Design Agenda view combining daily activities
5. Consider Tickets and Requests systems for manager workflows
