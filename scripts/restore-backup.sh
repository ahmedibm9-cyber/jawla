#!/usr/bin/env bash
# Restore only into an explicitly named non-production scratch database.
set -euo pipefail

: "${BACKUP_FILE:?BACKUP_FILE is required}"
: "${TARGET_DATABASE_URL:?TARGET_DATABASE_URL is required}"
: "${BACKUP_AGE_IDENTITY_FILE:?BACKUP_AGE_IDENTITY_FILE is required}"
: "${ALLOW_SCRATCH_RESTORE:?Set ALLOW_SCRATCH_RESTORE=1 after confirming the target is disposable}"

if [[ "$ALLOW_SCRATCH_RESTORE" != "1" ]]; then
  echo "Refusing restore without explicit scratch-target confirmation." >&2
  exit 64
fi

if [[ -n "${DATABASE_URL:-}" && "$TARGET_DATABASE_URL" == "$DATABASE_URL" ]]; then
  echo "Refusing to restore over DATABASE_URL." >&2
  exit 64
fi

command -v age >/dev/null
command -v pg_restore >/dev/null
test -f "$BACKUP_FILE"
test -f "$BACKUP_AGE_IDENTITY_FILE"

age --decrypt -i "$BACKUP_AGE_IDENTITY_FILE" "$BACKUP_FILE" \
  | pg_restore --dbname="$TARGET_DATABASE_URL" --clean --if-exists --no-owner --no-privileges

echo "Restore completed to the explicitly confirmed scratch target. Run the reconciliation checklist before recording this drill."
