# Roles matrix (spatie/laravel-permission)

Five roles. Access is enforced by Filament policies (admin) and route
middleware (rep PWA).

| Ability | system_viewer | hr_admin | sales_manager | warehouse_keeper | sales_rep |
|---|:-:|:-:|:-:|:-:|:-:|
| Manage users & roles | ✓ | ✓ |  |  |  |
| Company settings | ✓ | ✓ |  |  |  |
| Manage products & prices | ✓ |  |  |  |  |
| Manage main-warehouse stock | ✓ |  |  | ✓ |  |
| Load / unload vans | ✓ |  |  | ✓ |  |
| Approve van transfers | ✓ |  | ✓ | ✓ |  |
| Manage routes | ✓ |  | ✓ |  |  |
| Manage customers | ✓ |  | ✓ |  |  |
| View all reps' data | ✓ |  | ✓ |  |  |
| Approve / cancel invoices | ✓ |  | ✓ |  |  |
| Reverse activities | ✓ |  | ✓ |  |  |
| Create tasks for reps | ✓ |  | ✓ |  |  |
| Field work (visit/sell/collect/return) |  |  |  |  | ✓ |
| Access `/admin` | ✓ | ✓ | ✓ | ✓ |  |
| Access `/app` |  |  |  |  | ✓ |
