#!/usr/bin/env bash
# Verify backup integrity: download latest backup, decrypt, test restore to a
# throwaway database, run a smoke query, then clean up.
# Run monthly or before major releases.
set -euo pipefail

: "${DATABASE_URL:?DATABASE_URL is required}"
: "${BACKUP_STORAGE_URI:?BACKUP_STORAGE_URI is required}"
: "${BACKUP_AGE_RECIPIENT:?BACKUP_AGE_RECIPIENT is required}"
: "${VERIFY_DB_NAME:=jawla_backup_verify_$$}"

command -v pg_dump >/dev/null
command -v age >/dev/null
command -v rclone >/dev/null
command -v psql >/dev/null

workdir="$(mktemp -d)"
trap 'dropdb --if-exists "$VERIFY_DB_NAME" 2>/dev/null; rm -rf "$workdir"' EXIT

echo "=== Backup restore verification ==="

# 1. Find latest backup
latest="$(rclone lsf "$BACKUP_STORAGE_URI" | grep -E '^jawla_.*\.dump\.age$' | sort -r | head -1)"
if [ -z "$latest" ]; then
  echo "ERROR: No backups found at $BACKUP_STORAGE_URI" >&2
  exit 1
fi
echo "Latest backup: $latest"

# 2. Download and decrypt
rclone copyto "$BACKUP_STORAGE_URI/$latest" "$workdir/$latest"
age -d -i <(echo "$BACKUP_AGE_RECIPIENT") -o "$workdir/dump" "$workdir/$latest" 2>/dev/null \
  || age -d -o "$workdir/dump" "$workdir/$latest"
test -s "$workdir/dump"

# 3. Restore to throwaway database
createdb "$VERIFY_DB_NAME" 2>/dev/null || true
pg_restore --no-owner --no-privileges --dbname="$VERIFY_DB_NAME" "$workdir/dump" 2>/dev/null || true

# 4. Smoke: table count + row count sanity
tables="$(psql -d "$VERIFY_DB_NAME" -t -A -c "SELECT count(*) FROM information_schema.tables WHERE table_schema='public'")"
rows="$(psql -d "$VERIFY_DB_NAME" -t -A -c "SELECT count(*) FROM users")"
echo "Tables: $tables | Users: $rows"

if [ "$tables" -lt 10 ]; then
  echo "ERROR: Too few tables ($tables) — restore may be corrupt" >&2
  exit 1
fi
if [ "$rows" -lt 1 ]; then
  echo "ERROR: No users found — restore may be corrupt" >&2
  exit 1
fi

echo "=== Backup verification PASSED ==="
