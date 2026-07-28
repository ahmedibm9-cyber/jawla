# Fix Story: PR-005 — Remove Demo Credentials from Production

**Epic:** Deployment & Security
**Story ID:** FIX-PR-005
**Priority:** P0 — Launch Blocker
**Investigation Reference:** `bmad-output/investigation-production-readiness-2026-07-28.md`

---

## Story

**As a** DevOps engineer
**I want** production deployments to never seed demo credentials or synthetic data
**So that** the production environment contains only real business data with secure, unique credentials

---

## Acceptance Criteria

1. **Separate seed commands for demo vs production**
   - `php artisan seed:super-admin` — creates admin with generated password
   - `php artisan seed:demo` — demo data (explicitly marked demo-only)
   - Production deploy runs ONLY `seed:super-admin`

2. **Generated credentials, not hardcoded**
   - Password generated via `Str::random(32)` or similar
   - Credentials displayed once during setup, not stored in code
   - First-login password change required

3. **Production deploy script updated**
   - Remove `DemoSeeder --force` from `railway.toml` pre-deploy
   - Add check: if demo data exists, abort with error

4. **Audit trail for initial setup**
   - Log who created the initial admin and when
   - Store credential hash, not plaintext

5. **Existing production credentials rotated**
   - If `superadmin@jawla.test` exists in production, force password reset
   - Document rotation in changelog

---

## Suspected Files

| File | Change |
|------|--------|
| `railway.toml` | Remove DemoSeeder from pre-deploy |
| `app/Console/Commands/SeedSuperAdmin.php` | Generate password, don't hardcode |
| `database/seeders/DemoSeeder.php` | Mark as demo-only, don't run in prod |
| `app/Console/Commands/SeedDemo.php` | New command for demo data |
| `database/seeders/DatabaseSeeder.php` | Conditional logic for env |

---

## Verification Steps

1. **Fresh deploy test:** Deploy to ephemeral environment → verify no demo data, no known credentials
2. **Existing deploy test:** Check production database for `superadmin@jawla.test` → rotate if exists
3. **Rollback test:** Deploy → verify admin can login with generated password → force change
4. **Audit test:** Verify setup log entry exists with timestamp and operator

---

## Implementation Notes

- **Approach:** Minimal change — separate demo from production seeding, generate passwords
- **Risk:** Breaking existing deployments during transition
- **Mitigation:** Run migration to rotate credentials before deploying new seeder

---

## Definition of Done

- [ ] `make verify` passes
- [ ] Fresh deploy produces no demo data
- [ ] Generated credentials are secure and unique
- [ ] Existing production credentials rotated
- [ ] Setup audit trail exists
