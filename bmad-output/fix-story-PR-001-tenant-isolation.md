# Fix Story: PR-001 — Mandatory Tenant Isolation

**Epic:** Security & Data Isolation
**Story ID:** FIX-PR-001
**Priority:** P0 — Launch Blocker
**Investigation Reference:** `bmad-output/investigation-production-readiness-2026-07-28.md`

---

## Story

**As a** system administrator
**I want** company data to be completely isolated at every authenticated entry point
**So that** no user can access or mutate another company's records under any circumstances

---

## Acceptance Criteria

1. **Filament middleware chain includes mandatory tenant resolution**
   - `SetActiveCompanyContext` runs before any Filament resource/page access
   - Missing context = 403 Forbidden, not silent scope skip

2. **Global scope is fail-closed**
   - `BelongsToCompany` throws if `ActiveCompanyContext` is null
   - No code path can bypass company scope

3. **Policies enforce company ownership**
   - Every policy check includes `company_id` comparison
   - Cross-company record access returns 403

4. **Database-level defense**
   - Row-Level Security (RLS) policies on critical tables OR
   - Application-level company_id validation in every service method

5. **Test matrix passes**
   - Two-company allow/deny matrix for:
     - Direct record access (by ID)
     - Global search results
     - Relation queries
     - Bulk actions
     - Livewire updates
     - Imports/exports
     - API endpoints

---

## Suspected Files

| File | Change |
|------|--------|
| `app/Models/Concerns/BelongsToCompany.php` | Make scope mandatory, throw on null context |
| `app/Support/ActiveCompanyContext.php` | Ensure initialization at every entry |
| `app/Http/Middleware/SetActiveCompanyContext.php` | Move to global middleware stack |
| `app/Providers/Filament/AdminPanelProvider.php` | Register middleware in Filament chain |
| `app/Policies/*.php` | Add company_id checks to all policies |
| `app/Providers/AuthServiceProvider.php` | Ensure policies are registered |

---

## Verification Steps

1. **Manual test:** Login as Company A admin → navigate to Company B record by ID → expect 403
2. **Manual test:** Login as Company A admin → global search → expect only Company A results
3. **Automated test:** Two-company Pest test suite covering all access paths
4. **Database test:** Verify RLS policies (if implemented) block cross-company queries

---

## Implementation Notes

- **Approach:** Start with middleware enforcement, then add policy checks, then database constraints
- **Risk:** Breaking existing admin functionality during migration
- **Mitigation:** Feature flag for old behavior during transition, monitor error rates

---

## Definition of Done

- [ ] `make verify` passes
- [ ] Two-company test matrix documented and passing
- [ ] No code path bypasses company scope
- [ ] 403 returned for all cross-company access attempts
- [ ] Performance impact measured (scope adds ~1-2ms per query)
