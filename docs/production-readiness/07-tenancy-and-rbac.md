# Tenancy and RBAC

## Tenant boundary verdict

**FAIL — Critical.** `BelongsToCompany` scopes only when `ActiveCompanyContext` has a value. The Filament panel middleware does not include `SetActiveCompanyContext`, so initial panel requests can run without a tenant constraint. Role-only policies and global admin bypasses do not repair this.

Key evidence:

- `app/Models/Concerns/BelongsToCompany.php:13-18`
- `app/Support/ActiveCompanyContext.php:7-26`
- `app/Http/Middleware/SetActiveCompanyContext.php:14-20`
- `app/Providers/Filament/AdminPanelProvider.php:84-99`
- `app/Providers/AuthServiceProvider.php:13-21`

The route inventory confirmed that only a custom bare `/admin` route showed the active-company middleware; Filament resource routes did not.

## Approved-role implementation matrix

`NI` means the controlling role name is not implemented; behavior is not inferred from a legacy role.

| Capability | system_viewer | hr_admin | sales_manager | warehouse_keeper | sales_rep |
|---|---:|---:|---:|---:|---:|
| Role seeded | NI | NI | Yes | Yes | NI (`rep` exists) |
| Admin panel access | NI | NI | Allow | Allow | NI |
| Rep app access | Deny | Deny | Deny | Deny | NI (`rep` allowed) |
| User/role management | NI | NI | Deny | Deny | NI |
| Invoice management | NI | NI | Allow | Deny | NI |
| Stock import/reconciliation | NI | NI | Deny | Allow | NI |
| Live rep GPS map | NI | NI | Allow | Deny | NI |
| Reports/activity/reversal | NI | NI | Partial | Partial | NI |
| Company administration | NI | NI | Deny | Deny | NI |

Actual seeded roles are `admin`, `sales_manager`, `accounts`, `purchasing`, `warehouse_keeper`, `executive`, `rep`, and `super_admin`. The primary specification requires `system_viewer`, `hr_admin`, `sales_manager`, `warehouse_keeper`, and `sales_rep`.

## Boundary coverage required

Every allow and deny case must be tested with two companies and direct hostile IDs across:

- initial Filament GET and Livewire update;
- global search and relation selectors;
- create/edit/view/delete and custom page actions;
- bulk actions, imports, downloads, PDFs, and future exports;
- maps, reports, activity, supplier comparisons, and dashboards;
- rep endpoints and sync operation nested IDs;
- public API token company and ability boundaries;
- service calls invoked outside HTTP;
- super-admin/platform administration, if retained.

Tests that add `where(company_id, ...)` manually do not prove framework resource isolation.

## Service-level ownership

Global scopes are defense in depth, not authorization. Every financial/stock service must resolve the company-owned customer, product, warehouse, batch, document, and user inside the transaction. Independent foreign keys do not establish that these records share a company.

## RBAC decisions

The owner must either implement the five controlling roles or formally supersede the primary specification with a migration matrix. Broad `admin`/`super_admin` Gate bypasses require a separately documented platform-admin threat model, MFA/step-up, session controls, and auditable break-glass behavior.

