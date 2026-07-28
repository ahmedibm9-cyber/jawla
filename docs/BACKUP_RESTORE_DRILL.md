# Backup & Restore Drill Log

## Prerequisites

- `DATABASE_URL` set to production-like PostgreSQL
- `BACKUP_STORAGE_URI` set to rclone remote
- `BACKUP_AGE_RECIPIENT` set to age public key
- `BACKUP_AGE_IDENTITY_FILE` set to age identity file
- `TARGET_DATABASE_URL` set to a disposable scratch database
- `ALLOW_SCRATCH_RESTORE=1`

## Drill Steps

### 1. Run backup

```bash
./scripts/backup.sh
```

Record: backup filename, size, upload time.

### 2. Verify backup exists on remote

```bash
rclone lsf $BACKUP_STORAGE_URI | grep jawla_
```

Record: archive name confirmed.

### 3. Restore to scratch database

```bash
BACKUP_FILE=<path-to-archive> \
TARGET_DATABASE_URL=<scratch-db-url> \
BACKUP_AGE_IDENTITY_FILE=<identity-file> \
ALLOW_SCRATCH_RESTORE=1 \
./scripts/restore-backup.sh
```

Record: restore time, any errors.

### 4. Reconciliation checklist

After restore, verify:

- [ ] Company count matches source
- [ ] Invoice count matches source
- [ ] Payment count matches source
- [ ] Return count matches source
- [ ] Stock movement count matches source
- [ ] User count matches source
- [ ] No orphaned records (FK violations)

### 5. Record results

| Metric               | Value |
| -------------------- | ----- |
| Backup date          |       |
| Backup size          |       |
| Upload time          |       |
| Restore time         |       |
| Reconciliation delta |       |
| RPO (max data loss)  |       |
| RTO (max downtime)   |       |
| Drilled by           |       |
| Date drilled         |       |

## Sign-off

- [ ] Engineering lead: _____________ Date: _______
- [ ] Operations owner: _____________ Date: _______
