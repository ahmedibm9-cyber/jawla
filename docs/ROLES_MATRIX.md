# Roles matrix (Filament Shield + spatie/laravel-permission)

Roles are managed via the Filament Shield UI (`/admin/shield/roles`).
Access is enforced by Filament policies (admin) and route middleware (rep PWA).

**Permission format:** `action:resource` (e.g., `view_any:invoice`, `create:invoice`)
**Custom permissions:** dot-notation (e.g., `alarms.respond`, `reports.view`, `payments.collect`)

## Roles

| Role               | Description                                                                |
| ------------------ | -------------------------------------------------------------------------- |
| `super_admin`      | Full access to everything, bypasses all gates                              |
| `admin`            | Full access to all permissions (same as super_admin but can be reassigned) |
| `sales_manager`    | Manages sales, approvals, reports                                          |
| `accounts`         | Financial operations, price management                                     |
| `purchasing`       | Purchase requests, orders, supplier management                             |
| `warehouse_keeper` | Stock operations, van transfers, goods in transit                          |
| `executive`        | Read-only dashboard, alarms, tasks                                         |
| `sales_rep`        | Field work via PWA (not Filament panel)                                    |
| `rep`              | Minimal PWA access (mirrors sales_rep)                                     |
| `hr_admin`         | User management, role assignment                                           |
| `system_viewer`    | Read-only access to most resources                                         |

## Permission matrix

Refund separation of duties: `refunds.request` is assigned to field/sales
roles; `refunds.approve` is assigned only to `sales_manager`, `admin`, and
`super_admin`.

| Ability                                          | super_admin | admin | sales_manager |   accounts   | purchasing | warehouse_keeper | executive | sales_rep  | hr_admin | system_viewer |
| ------------------------------------------------ | :---------: | :---: | :-----------: | :----------: | :--------: | :--------------: | :-------: | :--------: | :------: | :-----------: |
| **Resources**                                    |             |       |               |              |            |                  |           |            |          |               |
| View/create/edit/delete users                    |      ✓      |   ✓   |               |              |            |                  |           |            |    ✓     |               |
| View/create/edit/delete companies                |      ✓      |   ✓   |               |              |            |                  |     ✓     |            |          |               |
| View/create/edit/delete customers                |      ✓      |   ✓   |       ✓       |              |            |                  |           |            |          |       ✓       |
| View/create/edit/delete invoices                 |      ✓      |   ✓   |    ✓(view)    | ✓(view/edit) |            |                  |           | ✓(create)  |          |    ✓(view)    |
| View/create/edit/delete proforma invoices        |      ✓      |   ✓   |       ✓       |              |            |                  |           |            |          |               |
| View/create/edit/delete return records           |      ✓      |   ✓   |       ✓       |   ✓(view)    |            |                  |           |            |          |               |
| View/create/edit/delete payments                 |      ✓      |   ✓   |       ✓       |   ✓(view)    |            |                  |           | ✓(collect) |          |    ✓(view)    |
| View/create/edit/delete expenses                 |      ✓      |   ✓   |       ✓       |   ✓(view)    |            |                  |           |            |          |               |
| View/create/edit/delete cash reconciliations     |      ✓      |   ✓   |       ✓       |      ✓       |            |                  |           |            |          |               |
| View/create/edit/delete products                 |      ✓      |   ✓   |               |      ✓       |  ✓(view)   |                  |           |            |          |               |
| View/create/edit/delete product prices           |      ✓      |   ✓   |               |      ✓       |            |                  |           |            |          |               |
| View/create/edit/delete stock                    |      ✓      |   ✓   |    ✓(view)    |   ✓(view)    |            |        ✓         |           |            |          |    ✓(view)    |
| View/create/edit/delete batches                  |      ✓      |   ✓   |    ✓(view)    |   ✓(view)    |     ✓      |        ✓         |           |            |          |       ✓       |
| View/create/edit/delete goods in transit         |      ✓      |   ✓   |    ✓(view)    |   ✓(view)    |     ✓      |        ✓         |           |            |          |       ✓       |
| View/create/edit/delete purchase requests        |      ✓      |   ✓   |       ✓       |   ✓(view)    |     ✓      |                  |           |            |          |       ✓       |
| View/create/edit/delete purchase orders          |      ✓      |   ✓   |    ✓(view)    |   ✓(view)    |     ✓      |                  |           |            |          |       ✓       |
| View/create/edit/delete van transfers            |      ✓      |   ✓   |       ✓       |   ✓(view)    |            |        ✓         |           |            |          |       ✓       |
| View/create/edit/delete alarms                   |      ✓      |   ✓   |       ✓       |   ✓(view)    |            |                  |     ✓     |            |          |    ✓(view)    |
| View/create/edit/delete tasks                    |      ✓      |   ✓   |       ✓       |              |            |                  |  ✓(view)  |            |          |               |
| View/create/edit/delete sales targets            |      ✓      |   ✓   |       ✓       |              |            |                  |  ✓(view)  |            |          |               |
| View/create/edit/delete routes                   |      ✓      |   ✓   |       ✓       |              |            |                  |           |            |          |               |
| View/create/edit/delete daily visit assignments  |      ✓      |   ✓   |       ✓       |              |            |                  |           |            |          |               |
| View/create/edit/delete price quotation requests |      ✓      |   ✓   |       ✓       |              |            |                  |           |            |          |               |
| View/create/edit/delete complaints               |      ✓      |   ✓   |       ✓       |              |            |                  |           |            |          |               |
| **Custom permissions**                           |             |       |               |              |            |                  |           |            |          |               |
| `reports.view`                                   |      ✓      |   ✓   |       ✓       |      ✓       |            |                  |     ✓     |            |          |       ✓       |
| `alarms.respond`                                 |      ✓      |   ✓   |       ✓       |              |            |                  |           |            |          |               |
| `payments.collect`                               |      ✓      |   ✓   |               |              |            |                  |           |     ✓      |          |               |
| `refunds.request`                                |      ✓      |   ✓   |       ✓       |              |            |                  |           |     ✓      |          |               |
| `products.manage_prices`                         |      ✓      |   ✓   |               |      ✓       |            |                  |           |            |          |               |
| `products.view_cost`                             |      ✓      |   ✓   |               |      ✓       |            |                  |           |            |          |       ✓       |
| `stock.adjust`                                   |      ✓      |   ✓   |               |              |            |        ✓         |           |            |          |               |
| `stock.import`                                   |      ✓      |   ✓   |               |              |            |        ✓         |           |            |          |               |
| `admin_preferences.view`                         |      ✓      |   ✓   |               |              |            |                  |           |            |          |               |
| `api_tokens.view`                                |      ✓      |   ✓   |               |              |            |                  |           |            |          |               |
| `view:rep_live_map`                              |      ✓      |   ✓   |       ✓       |              |            |                  |     ✓     |            |          |               |
| `view:customer_map`                              |      ✓      |   ✓   |       ✓       |              |            |                  |     ✓     |            |          |               |
| **Panel access**                                 |             |       |               |              |            |                  |           |            |          |               |
| Access `/admin`                                  |      ✓      |   ✓   |       ✓       |      ✓       |     ✓      |        ✓         |     ✓     |            |    ✓     |       ✓       |
| Access `/app` (PWA)                              |             |       |               |              |            |                  |           |     ✓      |          |               |
