# Permission Matrix

Legend: ✅ full access | 🔶 partial/own only | ❌ denied

| Permission                     | Admin | Sales Mgr | Accounts | Purchasing | Wh Keeper | Executive | Rep |
| ------------------------------ | ----- | --------- | -------- | ---------- | --------- | --------- | --- |
| **Master Data**                |       |           |          |            |           |           |     |
| `companies.manage`             | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `users.manage`                 | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `roles.manage`                 | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `products.manage`              | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `products.manage_prices`       | ✅    | ❌        | ✅       | ❌         | ❌        | ❌        | ❌  |
| `products.view_cost`           | ✅    | ❌        | ✅       | ✅         | ❌        | ❌        | ❌  |
| `products.manage_cost`         | ✅    | ❌        | ✅       | ❌         | ❌        | ❌        | ❌  |
| `products.view`                | ✅    | ✅        | ✅       | ✅         | ✅        | ✅        | ✅  |
| `categories.manage`            | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `suppliers.manage`             | ✅    | ❌        | ❌       | ✅         | ❌        | ❌        | ❌  |
| `suppliers.view`               | ✅    | ✅        | ✅       | ✅         | ✅        | ✅        | ❌  |
| **Customers**                  |       |           |          |            |           |           |     |
| `customers.manage`             | ✅    | 🔶        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `customers.approve`            | ✅    | ✅        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `customers.view_all`           | ✅    | ✅        | ✅       | ✅         | ✅        | ✅        | ❌  |
| `customers.view_own`           | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `customers.add`                | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `customer_groups.manage`       | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `territories.manage`           | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ❌  |
| **Routes**                     |       |           |          |            |           |           |     |
| `routes.manage`                | ✅    | ✅        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `routes.view`                  | ✅    | ✅        | ❌       | ❌         | ❌        | ❌        | 🔶  |
| **Sales / Field**              |       |           |          |            |           |           |     |
| `sessions.manage`              | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `visit_assignments.manage`     | ✅    | ✅        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `visits.view_assigned`         | ✅    | ✅        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `visits.view_all`              | ✅    | ✅        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `visits.execute`               | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `visits.custom`                | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| **Pricing**                    |       |           |          |            |           |           |     |
| `pricing.request`              | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `pricing.set_range`            | ✅    | ✅        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `pricing.negotiate`            | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| **Proforma Invoices**          |       |           |          |            |           |           |     |
| `proformas.create`             | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `proformas.view_all`           | ✅    | ✅        | ✅       | ❌         | ❌        | ❌        | ❌  |
| `proformas.view_own`           | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `proformas.cancel`             | ✅    | ✅        | ❌       | ❌         | ❌        | ❌        | 🔶  |
| `proformas.convert_to_invoice` | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| **Invoices**                   |       |           |          |            |           |           |     |
| `invoices.create`              | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `invoices.view_all`            | ✅    | ✅        | ✅       | ❌         | ❌        | ❌        | ❌  |
| `invoices.view_own`            | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `invoices.approve`             | ✅    | ✅        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `invoices.cancel`              | ✅    | ❌        | ✅       | ❌         | ❌        | ❌        | ❌  |
| `invoices.amend`               | ✅    | ❌        | ✅       | ❌         | ❌        | ❌        | ❌  |
| `invoices.print`               | ✅    | ✅        | ✅       | ❌         | ❌        | ❌        | ✅  |
| `invoices.export_pdf`          | ✅    | ✅        | ✅       | ❌         | ❌        | ❌        | ✅  |
| **Collections / Payments**     |       |           |          |            |           |           |     |
| `payments.collect`             | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `payments.view_all`            | ✅    | ✅        | ✅       | ❌         | ❌        | ❌        | ❌  |
| `payments.view_own`            | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `payments.reverse`             | ✅    | ❌        | ✅       | ❌         | ❌        | ❌        | ❌  |
| **Returns**                    |       |           |          |            |           |           |     |
| `returns.create`               | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `returns.view_all`             | ✅    | ✅        | ✅       | ❌         | ❌        | ❌        | ❌  |
| `returns.view_own`             | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `returns.approve`              | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ❌  |
| **Expenses**                   |       |           |          |            |           |           |     |
| `expenses.log`                 | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `expenses.view_all`            | ✅    | ✅        | ✅       | ❌         | ❌        | ❌        | ❌  |
| `expenses.view_own`            | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| **Stock / Warehouse**          |       |           |          |            |           |           |     |
| `stock.view`                   | ✅    | ✅        | ❌       | ✅         | ✅        | ✅        | ✅  |
| `stock.import`                 | ✅    | ❌        | ❌       | ❌         | ✅        | ❌        | ❌  |
| `stock.adjust`                 | ✅    | ❌        | ❌       | ❌         | ✅        | ❌        | ❌  |
| `stock.export`                 | ✅    | ✅        | ✅       | ✅         | ✅        | ❌        | ❌  |
| `batches.manage`               | ✅    | ❌        | ❌       | ❌         | ✅        | ❌        | ❌  |
| `batches.view`                 | ✅    | ✅        | ❌       | ✅         | ✅        | ✅        | 🔶  |
| `warehouses.manage`            | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `goods_in_transit.manage`      | ✅    | ❌        | ❌       | ✅         | ❌        | ❌        | ❌  |
| `goods_in_transit.receive`     | ✅    | ❌        | ❌       | ✅         | ✅        | ❌        | ❌  |
| `goods_in_transit.view`        | ✅    | ✅        | ✅       | ✅         | ✅        | ✅        | ✅  |
| `landed_costs.manage`          | ✅    | ❌        | ✅       | ✅         | ❌        | ❌        | ❌  |
| `van_transfers.request`        | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `van_transfers.approve`        | ✅    | ✅        | ❌       | ❌         | ❌        | ❌        | ❌  |
| **Purchasing**                 |       |           |          |            |           |           |     |
| `purchase_requests.submit`     | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `purchase_requests.view_all`   | ✅    | ✅        | ❌       | ✅         | ❌        | ❌        | ❌  |
| `purchase_requests.veto`       | ✅    | ✅        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `supplier_quotations.manage`   | ✅    | ❌        | ❌       | ✅         | ❌        | ❌        | ❌  |
| `supplier_quotations.view`     | ✅    | ❌        | ❌       | ✅         | ❌        | ❌        | ❌  |
| `purchase_orders.manage`       | ✅    | ❌        | ❌       | ✅         | ❌        | ❌        | ❌  |
| `purchase_orders.view`         | ✅    | ✅        | ❌       | ✅         | ❌        | ❌        | ❌  |
| **Alarms / Alerts**            |       |           |          |            |           |           |     |
| `alarms.view_all`              | ✅    | ✅        | ✅       | ❌         | ❌        | ✅        | ❌  |
| `alarms.respond`               | ✅    | ✅        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `alarms.flag_out_of_stock`     | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| **Complaints / CRM**           |       |           |          |            |           |           |     |
| `complaints.submit`            | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| `complaints.manage`            | ✅    | ✅        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `complaints.view_all`          | ✅    | ✅        | ✅       | ❌         | ❌        | ✅        | ❌  |
| `complaints.view_own`          | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ✅  |
| **Tax / E-Invoicing**          |       |           |          |            |           |           |     |
| `tax_templates.manage`         | ✅    | ❌        | ✅       | ❌         | ❌        | ❌        | ❌  |
| `eta_qr.view`                  | ✅    | ❌        | ✅       | ❌         | ❌        | ❌        | ❌  |
| **Reports**                    |       |           |          |            |           |           |     |
| `reports.dashboard_view`       | ✅    | ✅        | ✅       | ✅         | ✅        | ✅        | ❌  |
| `reports.sales`                | ✅    | ✅        | ✅       | ❌         | ❌        | ❌        | ❌  |
| `reports.financial`            | ✅    | ❌        | ✅       | ❌         | ❌        | ❌        | ❌  |
| `reports.purchasing`           | ✅    | ❌        | ❌       | ✅         | ❌        | ❌        | ❌  |
| `reports.stock`                | ✅    | ❌        | ❌       | ✅         | ✅        | ❌        | ❌  |
| `reports.visits`               | ✅    | ✅        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `reports.intercompany`         | ✅    | ❌        | ✅       | ❌         | ❌        | ❌        | ❌  |
| `exports.excel`                | ✅    | ✅        | ✅       | ✅         | ✅        | ❌        | ❌  |
| **Admin / System**             |       |           |          |            |           |           |     |
| `data_migration.import`        | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `system.config`                | ✅    | ❌        | ❌       | ❌         | ❌        | ❌        | ❌  |
| `audit_log.view`               | ✅    | ❌        | ✅       | ❌         | ❌        | ❌        | ❌  |
| `warehouse_import.manage`      | ✅    | ❌        | ❌       | ❌         | ✅        | ❌        | ❌  |

Total permissions: 94

## Role permission counts

| Role             | Total |
| ---------------- | ----- |
| Admin            | 94    |
| Sales Manager    | 40    |
| Accounts         | 30    |
| Purchasing       | 22    |
| Warehouse Keeper | 20    |
| Executive        | 14    |
| Rep              | 30    |
