#!/bin/bash
# Railway cron job: daily PostgreSQL backup
# Set up as a Railway cron service with this command.
# Backups are stored in /tmp/backups with 7-day retention.

set -euo pipefail

BACKUP_DIR="/tmp/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=7

mkdir -p "$BACKUP_DIR"

# Use Railway's internal DB connection vars
export PGHOST="${DB_HOST:-127.0.0.1}"
export PGPORT="${DB_PORT:-5432}"
export PGDATABASE="${DB_DATABASE:-jawla}"
export PGUSER="${DB_USERNAME:-postgres}"
export PGPASSWORD="${DB_PASSWORD:-}"

OUTPUT_FILE="$BACKUP_DIR/jawla_${TIMESTAMP}.sql.gz"

echo "[$(date)] Starting backup to $OUTPUT_FILE"

pg_dump --format=custom --compress=9 --file="$OUTPUT_FILE" 2>&1

if [ $? -eq 0 ]; then
    SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
    echo "[$(date)] Backup completed: $OUTPUT_FILE ($SIZE)"
else
    echo "[$(date)] BACKUP FAILED" >&2
    exit 1
fi

# Clean up old backups
find "$BACKUP_DIR" -name "jawla_*.sql.gz" -mtime +$RETENTION_DAYS -delete
echo "[$(date)] Cleaned backups older than $RETENTION_DAYS days"

echo "[$(date)] Backup job complete"
