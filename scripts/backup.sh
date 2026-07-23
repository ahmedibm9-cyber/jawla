#!/usr/bin/env bash
# Encrypted, off-host PostgreSQL backup. Configure the required environment in
# the scheduled backup worker; this script intentionally fails instead of
# writing an unencrypted or local-only archive.
set -euo pipefail

: "${DATABASE_URL:?DATABASE_URL is required}"
: "${BACKUP_STORAGE_URI:?BACKUP_STORAGE_URI is required (for example remote:jawla-backups)}"
: "${BACKUP_AGE_RECIPIENT:?BACKUP_AGE_RECIPIENT is required}"

command -v pg_dump >/dev/null
command -v age >/dev/null
command -v rclone >/dev/null

timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
archive="jawla_${timestamp}.dump.age"
workdir="$(mktemp -d)"
trap 'rm -rf "$workdir"' EXIT

pg_dump "$DATABASE_URL" --format=custom --no-owner --no-privileges \
  | age -r "$BACKUP_AGE_RECIPIENT" -o "$workdir/$archive"

test -s "$workdir/$archive"
rclone copyto "$workdir/$archive" "$BACKUP_STORAGE_URI/$archive"
rclone lsf "$BACKUP_STORAGE_URI" | grep -Fqx "$archive"

echo "[$(date -u --iso-8601=seconds)] uploaded encrypted backup ${archive}"
