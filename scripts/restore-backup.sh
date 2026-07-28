#!/usr/bin/env bash
# Restore only into an explicitly named non-production scratch database.
set -euo pipefail

: "${BACKUP_FILE:?BACKUP_FILE is required}"
: "${SOURCE_DATABASE_URL:?SOURCE_DATABASE_URL is required for reconciliation}"
: "${TARGET_DATABASE_URL:?TARGET_DATABASE_URL is required}"
: "${BACKUP_AGE_IDENTITY_FILE:?BACKUP_AGE_IDENTITY_FILE is required}"
: "${ALLOW_SCRATCH_RESTORE:?Set ALLOW_SCRATCH_RESTORE=1 after confirming the target is disposable}"
: "${RESTORE_EVIDENCE_FILE:?RESTORE_EVIDENCE_FILE is required}"

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
command -v psql >/dev/null
test -f "$BACKUP_FILE"
test -f "$BACKUP_AGE_IDENTITY_FILE"

age --decrypt -i "$BACKUP_AGE_IDENTITY_FILE" "$BACKUP_FILE" \
  | pg_restore --dbname="$TARGET_DATABASE_URL" --clean --if-exists --no-owner --no-privileges

{
  echo "Jawla restore drill"
  echo "completed_at=$(date -u --iso-8601=seconds)"
  echo "backup=$(basename "$BACKUP_FILE")"

  for table in users products customers invoices stocks stock_movements payments; do
    source_count="$(psql "$SOURCE_DATABASE_URL" --no-psqlrc --tuples-only --no-align \
      --command "SELECT count(*) FROM ${table}")"
    restored_count="$(psql "$TARGET_DATABASE_URL" --no-psqlrc --tuples-only --no-align \
      --command "SELECT count(*) FROM ${table}")"

    echo "${table}: source=${source_count} restored=${restored_count}"

    if [[ "$source_count" != "$restored_count" ]]; then
      echo "Reconciliation failed for ${table}." >&2
      exit 1
    fi
  done

  echo "result=PASS"
} >"$RESTORE_EVIDENCE_FILE"

chmod 600 "$RESTORE_EVIDENCE_FILE"
echo "Restore and row-count reconciliation passed. Evidence: ${RESTORE_EVIDENCE_FILE}"
