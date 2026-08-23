# Rollback Procedure

## Overview

Railway keeps every deployment as a immutable artifact. Rollback is a single
command — but rehearse it before you need it under pressure.

## Prerequisites

- Railway CLI authenticated (`railway login`)
- Project linked (`railway link`)
- Access to the Railway dashboard

## Quick rollback (< 2 minutes)

```bash
# List recent deployments
railway logs --limit 10

# Roll back to a specific deployment
railway rollback <deployment-id>
```

Railway switches traffic to the previous Docker image instantly. No rebuild,
no database migration reverse — just traffic switch.

## What Railway rolls back

- Application code (Docker image)
- Environment variables (as they were at that deployment)
- Start command, build command, health check

## What Railway does NOT roll back

- Database migrations (PostgreSQL schema is forward-only)
- Persistent storage (volumes are not affected)
- Domain/DNS changes

## If database migration is the problem

1. Roll back the application code first (above)
2. The old code will run against the new schema — usually safe if migrations
   are additive (new columns, new tables)
3. If the migration broke the old code, you need a forward-fix migration
4. Never run `migrate:rollback` in production without understanding the
   data impact

## Rehearsal schedule

Run `scripts/rollback-rehearsal.sh` monthly or before major releases.

## Post-rollback checklist

- [ ] Health check passes: `curl -sf https://jawla.up.railway.app/health`
- [ ] No errors in Sentry for 5 minutes
- [ ] Core flows work: login, create invoice, collect payment
- [ ] PWA offline mode functions
- [ ] Push notifications deliver
