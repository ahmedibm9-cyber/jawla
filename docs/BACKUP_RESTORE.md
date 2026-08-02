# Backup and restore

> Status (2026-07-21): the automated `spatie/laravel-backup` pipeline described
> in earlier drafts is **not installed** (no package, no `config/backup.php`).
> Adding it is a pending decision (new Composer package + an S3-compatible bucket
>
> - `BACKUP_ARCHIVE_PASSWORD`). Until then the durable backup is **Railway's
>   managed Postgres backups** on the `jawla-db` volume, supplemented by the manual
>   `pg_dump` procedure below. Do not cite the spatie pipeline as live.

## Automated encrypted off-host backup

`scripts/backup.sh` now fails closed unless it can create an encrypted archive
and verify its upload to an off-host rclone remote. To activate a dedicated
backup worker, provide `DATABASE_URL`, `BACKUP_STORAGE_URI`,
`BACKUP_AGE_RECIPIENT`, `age`, and `rclone`; configure object-storage lifecycle
retention for at least 30 days. Do not mount `/tmp` as backup storage.

1. Railway dashboard → New → Cron Service
2. Schedule: `0 2 * * *` (daily 02:00 UTC)
3. Start command: `bash scripts/backup.sh`
4. Provide only the backup worker's DB read credentials plus the encrypted
   backup remote configuration; do not expose those credentials to the web app.
5. Alert if the job exits non-zero or a backup is older than 24 hours.

## Current mechanism (interim, no extra packages)

- **Railway managed Postgres** — the `jawla-db` service retains automated
  volume backups per the Railway plan. Verify retention + cadence in the Railway
  dashboard (Database → Backups) and record the settings during the drill.
- **Manual logical dump** — run before any risky migration/deploy:

  ```bash
  # From a machine with the Railway DATABASE_URL (never commit it):
  pg_dump "$DATABASE_URL" --no-owner --no-privileges -Fc -f jawla_$(date +%Y%m%d).dump
  ```

## Restore drill (mandatory, run by an operator with DB credentials)

This must be executed against a **scratch** database — never production.

1. Provision a throwaway Postgres (local container or a scratch Railway DB).
2. Restore the latest encrypted dump with the fail-closed helper:
   ```bash
   BACKUP_FILE=jawla_YYYYMMDD.dump.age \
   SOURCE_DATABASE_URL=postgres://.../jawla_source \
   TARGET_DATABASE_URL=postgres://.../jawla_restore_test \
   BACKUP_AGE_IDENTITY_FILE=/secure/path/restore-key.txt \
   RESTORE_EVIDENCE_FILE=/secure/path/jawla-restore-evidence.txt \
   ALLOW_SCRATCH_RESTORE=1 bash scripts/restore-backup.sh
   ```
   The helper compares critical source/restored table counts and writes a
   mode-0600 evidence file. Any mismatch exits non-zero.
3. Point a local app copy at `jawla_restore_test` (`.env` `DB_*`), then:
   ```bash
   php artisan migrate:status   # schema matches code
   ```
4. Verify data integrity: the latest invoice is visible; a seeded rep can log in;
   `stocks.quantity` reconciles against `stock_movements`.
5. Record the date + outcome in the log below. **Do not** mark the drill done
   until steps 1–4 actually pass.

## Release requirement

Railway volume backups alone are not sufficient release evidence. Before any
real-data release, record an independent encrypted off-host backup and a
successful scratch restore below, including measured RPO/RTO and the financial
and stock reconciliation results.

## Restore log

_(empty — no drill has been executed and recorded yet. Add one row per drill:
`YYYY-MM-DD — operator — outcome`.)_

### Restore drill checklist (for operator with Railway access)

**Prerequisites:**

- [ ] Railway CLI authenticated (`railway whoami`)
- [ ] `age` installed (`age --version`)
- [ ] `pg_dump`, `pg_restore`, `psql` available
- [ ] `rclone` configured (if using off-host backup)

**Steps:**

1. [ ] Get production database URL: `railway variables --service jawla | grep DATABASE_URL`
2. [ ] Create scratch database on Railway (or use local PostgreSQL)
3. [ ] Run backup: `DATABASE_URL=<prod-url> BACKUP_STORAGE_URI=<remote> BACKUP_AGE_RECIPIENT=<pubkey> bash scripts/backup.sh`
4. [ ] Run restore drill:
   ```bash
   BACKUP_FILE=jawla_YYYYMMDD.dump.age \
   SOURCE_DATABASE_URL=<prod-url> \
   TARGET_DATABASE_URL=<scratch-url> \
   BACKUP_AGE_IDENTITY_FILE=/secure/path/key.txt \
   RESTORE_EVIDENCE_FILE=restore-evidence-$(date +%Y%m%d).txt \
   ALLOW_SCRATCH_RESTORE=1 bash scripts/restore-backup.sh
   ```
5. [ ] Verify: `php artisan migrate:status` against scratch DB
6. [ ] Verify: latest invoice visible, seeded rep can log in
7. [ ] Verify: `stocks.quantity` reconciles against `stock_movements`
8. [ ] Record RPO/RTO: time from backup creation to successful restore
9. [ ] Record outcome in restore log above

**Expected duration:** ~15-30 min depending on database size
