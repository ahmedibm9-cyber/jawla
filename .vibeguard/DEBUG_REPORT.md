# Debug Report: Postgres Deployment Failure

**Date:** 2026-08-16
**Skill:** V-Systematic Debugging
**Status:** Completed — root cause identified, manual fix required

---

## Failure

Jawla staging deployment fails. Postgres service shows `activeDeployments: []`. App returns 404.

## Reproduction

1. `railway status` → jawla-staging: Failed, Postgres: Offline
2. `curl /health` → 404
3. `railway logs -s Postgres` → `relation "webhook_deliveries" does not exist` repeating every minute

## First Incorrect State

Postgres logs show the `webhook_deliveries` table does not exist. The scheduled command `app:deliver-webhooks` queries this table every minute, fails with a SQL error, and after 10 retries Railway stops the container.

## Hypotheses Tested

| #   | Hypothesis                | Result                                                   |
| --- | ------------------------- | -------------------------------------------------------- |
| H1  | Migration not run         | **CONFIRMED** — table missing despite migration existing |
| H2  | Table dropped manually    | Unlikely, no evidence                                    |
| H3  | Migration failed silently | Possible — partial migration state                       |

## Root Cause

**Database is in a partially migrated state.** The migration `2026_08_03_160000_create_integrations_and_installation_license.php` creates `webhook_deliveries`, but the table doesn't exist in the database. This means either:

- The migration never ran (despite `migrate --force` in pre-deploy)
- A subsequent migration or rollback dropped the table
- The database was restored from a backup taken before this migration

The app's scheduler queries `webhook_deliveries` every minute via `app:deliver-webhooks`. Each attempt fails, and after 10 retries Railway stops the container.

## Fix Required (Manual)

### Option A: Run missing migration (preferred)

1. Open Railway dashboard → jawla-staging service
2. Go to Deployments → click latest deployment → Shell
3. Run: `php artisan migrate --force`
4. Verify: `php artisan migrate:status` shows all migrations run
5. The scheduler should recover automatically

### Option B: Recreate database (if migration state is corrupted)

1. Open Railway dashboard → Postgres service
2. Connect via psql or Railway's database panel
3. Run: `DROP DATABASE railway; CREATE DATABASE railway;`
4. Redeploy jawla-staging (pre-deploy will run all migrations)
5. **WARNING:** This destroys all existing data

### Option C: Disable webhook scheduler (temporary workaround)

1. In Railway dashboard, set env var: `WEBHOOK_SCHEDULER_ENABLED=false`
2. Redeploy — app will start without the failing scheduled command
3. Fix migrations separately

## Regression Prevention

- Add health check that verifies critical tables exist
- Monitor scheduler failures in Sentry
- Add migration status check to pre-deploy command

## Verification After Fix

```bash
railway status  # Postgres should show active deployment
curl https://jawla-staging-staging.up.railway.app/health  # Should return 200
railway logs -s Postgres --lines 10  # No more "relation does not exist" errors
```

## Remaining Risks

| Risk                                 | Impact  | Mitigation                                          |
| ------------------------------------ | ------- | --------------------------------------------------- |
| Other tables may also be missing     | Unknown | Run `migrate:status` to verify                      |
| Database may have inconsistent state | Medium  | Consider full re-seed after migration               |
| Webhook data may be lost             | Low     | webhooks are new feature, likely no production data |
