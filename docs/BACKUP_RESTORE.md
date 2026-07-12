# Backup and restore

## Backups (spatie/laravel-backup)
- Nightly database dump + weekly full (files + DB).
- Encrypted with `BACKUP_ARCHIVE_PASSWORD` (from `.env`).
- Shipped to an S3-compatible bucket; 30-day retention.

## Monthly restore drill (mandatory)
1. Pull the latest archive to a scratch VPS.
2. Decrypt and restore the DB to `jawla_restore_test`.
3. Boot the app pointing at the restored DB.
4. Verify: latest invoice visible; a rep can log in.
5. Document date + result in this file.

Restore log:
- YYYY-MM-DD — outcome.
