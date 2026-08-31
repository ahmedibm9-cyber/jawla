# Jawla Support

## Contact

| Role          | Name   | Phone        | When                        |
| ------------- | ------ | ------------ | --------------------------- |
| Developer     | Ahmed  | [YOUR PHONE] | Business hours, best effort |
| Sales Manager | [NAME] | [PHONE]      | Business hours              |

**Emergency**: WhatsApp group "Jawla Support" — all reps + developer + sales manager.

## Quick Diagnostics (First 3 Things to Check)

### 1. App won't load

- Check Railway dashboard → service status → is it "Active"?
- Check `/health` endpoint: `https://jawla.up.railway.app/health`
- If 503: database or cache is down. Check Railway database service status.

### 2. Rep can't log in

- Check if rep's device is registered: Railway → `personal_access_tokens` table
- Check if rep's `is_active` flag is true: `users` table
- Check if rep has the correct role: `model_has_roles` table

### 3. Order/invoice didn't sync

- Rep should see sync status on home screen (green/yellow/red)
- If red: check `/app/sync-queue` for failed operations
- Manual retry: SSH into Railway, run `php artisan tinker` → check `sync_receipts` table

## Rollback

If something goes wrong:

1. **Bad deploy**: Railway → Deployments → roll back to previous successful deployment
2. **Bad migration**: `php artisan migrate:rollback` via Railway SSH
3. **Bad data**: Restore from Railway database snapshot (Settings → Backups)
4. **Nuclear option**: Restore from `pg_dump` backup (see `docs/BACKUP_RESTORE.md`)

## Railway Access

- Project URL: `https://railway.app/project/[YOUR_PROJECT_ID]`
- SSH: `railway ssh` (requires Railway CLI authenticated)
- Logs: Railway dashboard → Service → Deployments → View Logs
