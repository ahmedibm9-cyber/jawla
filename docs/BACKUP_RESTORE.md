# Backup and restore

> Status (2026-07-21): the automated `spatie/laravel-backup` pipeline described
> in earlier drafts is **not installed** (no package, no `config/backup.php`).
> Adding it is a pending decision (new Composer package + an S3-compatible bucket
>
> - `BACKUP_ARCHIVE_PASSWORD`). Until then the durable backup is **Railway's
>   managed Postgres backups** on the `jawla-db` volume, supplemented by the manual
>   `pg_dump` procedure below. Do not cite the spatie pipeline as live.

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
2. Restore the latest dump:
   ```bash
   createdb jawla_restore_test
   pg_restore --no-owner --no-privileges -d jawla_restore_test jawla_YYYYMMDD.dump
   ```
3. Point a local app copy at `jawla_restore_test` (`.env` `DB_*`), then:
   ```bash
   php artisan migrate:status   # schema matches code
   ```
4. Verify data integrity: the latest invoice is visible; a seeded rep can log in;
   `stocks.quantity` reconciles against `stock_movements`.
5. Record the date + outcome in the log below. **Do not** mark the drill done
   until steps 1–4 actually pass.

## Decision needed

- Install `spatie/laravel-backup` for encrypted, scheduled, off-Railway archives
  (nightly DB + weekly files, 30-day retention, S3-compatible bucket)? This is
  the recommended production posture but requires package approval + bucket
  credentials. Track under the ops backlog.

## Restore log

_(empty — no drill has been executed and recorded yet. Add one row per drill:
`YYYY-MM-DD — operator — outcome`.)_
