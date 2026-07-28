# Jawla rollback runbook

## Scope

Application rollback and database recovery are separate decisions. Prefer an
application rollback or a forward corrective migration. Never run
`migrate:rollback`, `migrate:fresh`, or an in-place destructive restore in
production.

## Railway application rollback

Use `.github/workflows/rollback.yml`:

1. In Railway production, identify the previously successful deployment and
   confirm its `canRollback` state.
2. Dispatch `rollback-production` with that deployment ID.
3. Enter the exact confirmation `ROLLBACK_PRODUCTION`.
4. A required reviewer on the GitHub `production` environment approves the
   action.
5. The workflow verifies rollback eligibility, calls Railway's deployment
   rollback API, waits for terminal `SUCCESS`, then requires `/health` to report
   healthy database and cache dependencies.

The workflow needs production environment secret `RAILWAY_API_TOKEN` and
variable `PRODUCTION_URL`. It never rolls back the database.

Railway retains rollback images for a plan-dependent window. Record the chosen
deployment ID and verify `canRollback` before beginning; do not assume an old
image remains available.

## Legacy host application rollback

`scripts/deploy.sh` records the previous commit and automatically restores that
application release if installation, migration, optimization, or readiness
fails. Its rollback is safe only when migrations use expand/contract
compatibility and the old release can operate against the newer schema.

For a later operator-directed rollback, deploy the earlier immutable commit:

```bash
RELEASE_REF=<previous-full-commit-sha> \
APP_DIR=/var/www/jawla \
bash scripts/deploy.sh
```

## Database recovery

Restore an encrypted pre-deploy backup into a new database, reconcile it, then
repoint the application only after independent approval. The helper refuses the
production `DATABASE_URL`, requires an explicit disposable scratch target, and
compares critical row counts:

```bash
BACKUP_FILE=/secure/jawla_YYYYMMDD.dump.age \
SOURCE_DATABASE_URL=postgres://.../jawla_source \
TARGET_DATABASE_URL=postgres://.../jawla_restore_check \
BACKUP_AGE_IDENTITY_FILE=/secure/age-key.txt \
RESTORE_EVIDENCE_FILE=/secure/jawla-restore-evidence.txt \
ALLOW_SCRATCH_RESTORE=1 \
bash scripts/restore-backup.sh
```

Do not repoint production solely from row counts. Also reconcile invoice/payment
totals, stock against `stock_movements`, cash positions, latest legal numbering,
and representative login/read journeys.

## After rollback

- Confirm `/health` returns 200 with `db=ok` and `cache=ok`.
- Confirm admin and rep authentication.
- Verify a read-only sample of invoices, payments, stock, and sync receipts.
- Check error rate, queue/failed jobs, and duplicate sync/conflict alarms.
- Record start/end time, deployment IDs, approver, reason, data checks, and any
  follow-up actions in the incident record.
