# Jawla (جولة) — KPI Catalog

## Sales KPIs

| #      | KPI                        | Formula                                        | Frequency                | Owner                   | Dashboard |
| ------ | -------------------------- | ---------------------------------------------- | ------------------------ | ----------------------- | --------- |
| KPI-01 | Total Sales (EGP)          | Σ invoice.total where status=submitted         | Daily / Weekly / Monthly | Sales Manager           | Sales     |
| KPI-02 | Sales by Product (tons)    | Σ invoice_items.qty grouped by product         | Weekly                   | Sales Manager           | Sales     |
| KPI-03 | Top 10 Products by Revenue | Σ (qty × unit_price) per product, ranked       | Weekly                   | Sales Manager, Admin    | Sales     |
| KPI-04 | Sales per Rep              | Σ invoice.total grouped by user                | Daily / Weekly           | Sales Manager           | Sales     |
| KPI-05 | Average Order Value        | Σ total / invoice count                        | Weekly                   | Sales Manager           | Sales     |
| KPI-06 | Conversion Rate            | # invoices / # proformas × 100                 | Weekly                   | Sales Manager           | Sales     |
| KPI-07 | Sales Trend (7-day)        | Σ total per day, last 7 days                   | Daily                    | Admin                   | Sales     |
| KPI-08 | Sales by Customer          | Σ invoice.total grouped by customer            | Monthly                  | Sales Manager           | Sales     |
| KPI-09 | Sales by Route             | Σ invoice.total where customer.route_id        | Weekly                   | Sales Manager           | Sales     |
| KPI-10 | Price Variance             | (actual_price − base_price) / base_price × 100 | Per invoice              | Sales Manager, Accounts | Sales     |

## Visit KPIs

| #      | KPI                     | Formula                                              | Frequency | Owner         | Dashboard |
| ------ | ----------------------- | ---------------------------------------------------- | --------- | ------------- | --------- |
| KPI-11 | Visit Compliance Rate   | # visits completed / # assigned × 100                | Daily     | Sales Manager | Visits    |
| KPI-12 | Missed Visit Rate       | # missed assignments / # assigned × 100              | Daily     | Sales Manager | Visits    |
| KPI-13 | Average Visit Duration  | AVG(checkout_at − arrival_confirmed_at)              | Weekly    | Sales Manager | Visits    |
| KPI-14 | GPS Arrival Accuracy    | # auto-confirmed / # total visits × 100              | Weekly    | Sales Manager | Visits    |
| KPI-15 | Visits per Rep per Day  | COUNT(visits) / COUNT(work_sessions) per user        | Daily     | Sales Manager | Visits    |
| KPI-16 | Reports Submitted Rate  | # visit_reports / # closed visits × 100              | Daily     | Sales Manager | Visits    |
| KPI-17 | Follow-up Required Rate | # visits with follow_up_needed=true / # visits × 100 | Weekly    | Sales Manager | Visits    |
| KPI-18 | Custom Visit Ratio      | # visits without assignment / # visits × 100         | Weekly    | Sales Manager | Visits    |

## Financial KPIs

| #      | KPI                     | Formula                                                           | Frequency       | Owner           | Dashboard |
| ------ | ----------------------- | ----------------------------------------------------------------- | --------------- | --------------- | --------- |
| KPI-19 | Revenue (EGP)           | Σ invoice.total (submitted)                                       | Monthly         | Accounts, Admin | Financial |
| KPI-20 | VAT Collected           | Σ invoice.vat_amount                                              | Monthly         | Accounts        | Financial |
| KPI-21 | VAT Payable             | Σ invoice.vat_amount − (input VAT on purchases)                   | Monthly         | Accounts        | Financial |
| KPI-22 | Cash in Hand per Rep    | cash_boxes.balance per rep                                        | Daily           | Accounts, Admin | Financial |
| KPI-23 | Total Collections       | Σ payments.amount                                                 | Daily / Monthly | Accounts        | Financial |
| KPI-24 | Collection by Method    | Σ amount grouped by mode_of_payment.type                          | Monthly         | Accounts        | Financial |
| KPI-25 | Returns Rate            | Σ return.total / Σ invoice.total × 100                            | Monthly         | Admin           | Financial |
| KPI-26 | Average Days to Collect | AVG(payment.date − invoice.date) — for prepaid, near 0            | Monthly         | Accounts        | Financial |
| KPI-27 | Field Expense Total     | Σ expenses.amount                                                 | Daily / Weekly  | Admin, Accounts | Financial |
| KPI-28 | Expense by Category     | Σ amount grouped by category                                      | Monthly         | Admin           | Financial |
| KPI-29 | Gross Margin            | Σ (invoice_items.line_total − product.cost × qty) / Σ total × 100 | Monthly         | Accounts, Admin | Financial |

## Stock KPIs

| #      | KPI                        | Formula                                                                           | Frequency | Owner                   | Dashboard |
| ------ | -------------------------- | --------------------------------------------------------------------------------- | --------- | ----------------------- | --------- |
| KPI-30 | Total Stock Value          | Σ (stocks.quantity × products.cost)                                               | Weekly    | Warehouse Keeper, Admin | Stock     |
| KPI-31 | Low Stock Items            | products where Σ qty across warehouses ≤ reorder_level                            | Daily     | Warehouse Keeper        | Stock     |
| KPI-32 | Expiring Batches (30 days) | COUNT(batches where expiry_date ≤ now+30d)                                        | Daily     | Warehouse Keeper        | Stock     |
| KPI-33 | Stock Turns                | cost of goods sold / average inventory value                                      | Monthly   | Admin                   | Stock     |
| KPI-34 | Goods in Transit Value     | Σ (git_items.qty × git_items.unit_price)                                          | Weekly    | Purchasing              | Stock     |
| KPI-35 | GIT Shipments Past ETA     | COUNT(git where status≠received AND estimated_arrival < now)                      | Daily     | Purchasing              | Stock     |
| KPI-36 | Stock Import Accuracy      | (# successful imports − # errors) / # total imports × 100                         | Weekly    | Warehouse Keeper        | Stock     |
| KPI-37 | Batch Coverage             | # batch-tracked products with active stock / # total batch-tracked products × 100 | Monthly   | Warehouse Keeper        | Stock     |

## Purchasing KPIs

| #      | KPI                                | Formula                                                                  | Frequency | Owner      | Dashboard |
| ------ | ---------------------------------- | ------------------------------------------------------------------------ | --------- | ---------- | --------- |
| KPI-38 | Purchase Orders Created            | COUNT(purchase_orders)                                                   | Monthly   | Purchasing | —         |
| KPI-39 | Average PO Value                   | Σ total / COUNT(po)                                                      | Monthly   | Purchasing | —         |
| KPI-40 | Supplier Quotation Comparison Rate | # purchase_requests with ≥2 quotations / # total purchase_requests × 100 | Monthly   | Admin      | —         |
| KPI-41 | Average Delivery Days              | AVG(delivery_time_days) on accepted quotations                           | Monthly   | Purchasing | —         |
| KPI-42 | International vs Local PO Ratio    | COUNT(type=international) / COUNT(type=local)                            | Monthly   | Admin      | —         |

## Alarm KPIs

| #      | KPI                          | Formula                                  | Frequency | Owner         | Dashboard |
| ------ | ---------------------------- | ---------------------------------------- | --------- | ------------- | --------- |
| KPI-43 | Open Alarms                  | COUNT(alarms where is_read=false)        | Daily     | All roles     | Alarms    |
| KPI-44 | Alarms by Type               | COUNT grouped by type                    | Weekly    | Sales Manager | Alarms    |
| KPI-45 | Alarm Response Time          | AVG(read_at − created_at)                | Weekly    | Admin         | Alarms    |
| KPI-46 | Critical Alarms Created      | COUNT(severity=critical)                 | Daily     | Admin         | Alarms    |
| KPI-47 | Alarm Resolution Rate        | # resolved / # total × 100               | Weekly    | Admin         | Alarms    |
| KPI-48 | Average Time to Resolve      | AVG(resolved_at − created_at)            | Weekly    | Admin         | Alarms    |
| KPI-49 | OOS Request Fulfillment Rate | # fulfilled / # total oos_requests × 100 | Weekly    | Sales Manager | Alarms    |

## Customer KPIs

| #      | KPI                    | Formula                                                | Frequency | Owner         | Dashboard |
| ------ | ---------------------- | ------------------------------------------------------ | --------- | ------------- | --------- |
| KPI-50 | New Customers Added    | COUNT(customers) per period                            | Weekly    | Sales Manager | —         |
| KPI-51 | Customer Approval Rate | # approved / # total pending × 100                     | Weekly    | Admin         | —         |
| KPI-52 | Customers per Rep      | COUNT(customers) grouped by account_manager            | Monthly   | Sales Manager | —         |
| KPI-53 | Complaint Rate         | # complaints / # customers                             | Monthly   | Admin         | —         |
| KPI-54 | Complaints by Type     | COUNT grouped by complaint_type                        | Monthly   | Sales Manager | Alarms    |
| KPI-55 | Customer Balance Aging | Σ balance grouped by age (0-30d, 31-60d, 61-90d, 90d+) | Monthly   | Accounts      | Financial |

## Executive KPIs (for Mohamed Taha / فيور)

| #      | KPI                 | Description                                                                                    | Dashboard |
| ------ | ------------------- | ---------------------------------------------------------------------------------------------- | --------- |
| KPI-56 | Executive Dashboard | Read-only view of: total sales today, open alarms, visit compliance rate, stock value, GIT ETA | Home      |
| KPI-57 | Active Alarms       | Count of unresolved critical + warning alarms                                                  | Alarms    |
| KPI-58 | Rep Activity        | Last check-in time for each rep, visits completed today                                        | Visits    |
| KPI-59 | Sales Snapshot      | Today's revenue vs yesterday vs same day last week                                             | Sales     |

## Implementation Notes

1. **Dashboard priority:** Sales KPIs (1-10) and Alarm KPIs (43-48) are Phase 16 must-haves
2. **Calculation engine:** Use SQL aggregations + Laravel collections (no OLAP cube needed at v1 scale)
3. **Refresh:** Dashboards refresh on page load. No real-time push required for v1 (nice-to-have via Laravel Reverb)
4. **Excel export:** Every dashboard widget should have "Export to Excel" button using spatie/simple-excel
5. **Executive visibility:** Executive role sees all KPI widgets in read-only mode (no drill-down actions)
6. **Cost-based KPIs:** KPI-29 (gross margin) available only to admin + accounts roles
