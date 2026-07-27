# Runbook: Database restore

## Prerequisites

- `DATABASE_URL` for target database
- Encrypted backup archive (`*.dump.age`)
- `BACKUP_AGE_IDENTITY_FILE` for decryption
- `pg_restore` and `age` CLI tools

## Steps

1. Provision scratch database (never restore to production without approval).
2. Decrypt and restore:

   ```bash
   BACKUP_FILE=jawla_YYYYMMDD.dump.age \
   TARGET_DATABASE_URL=postgres://.../target_db \
   BACKUP_AGE_IDENTITY_FILE=/secure/path/key.txt \
   ALLOW_SCRATCH_RESTORE=1 bash scripts/restore-backup.sh
   ```

3. Point app at target DB via `.env`.
4. Verify: `php artisan migrate:status` matches code.
5. Spot-check: latest invoice visible, seeded rep can log in.
6. Record date + outcome in `docs/BACKUP_RESTORE.md`.
