# Backup and Disaster Recovery

## Verdict

**NOT VERIFIED and blocking.**

## Available controls

- A fail-closed `pg_dump` script is present.
- Encryption/upload tooling is referenced.
- A guarded scratch-restore procedure is documented.
- The documentation acknowledges that the previously described Spatie backup package is not installed.

## Missing operational proof

- Restore log is empty.
- Independent backup service/configuration and schedule are external and unseen.
- Docker image does not install `age` or `rclone`, so the documented cron design requires unverified external customization.
- Database and object/file backup consistency is not established.
- Retention, region/provider separation, immutability, encryption-key escrow/rotation, access review, and deletion are not evidenced.
- Backup-age/failure alerts and tested delivery are not evidenced.
- RPO/RTO are not approved or measured.
- No row, sequence, financial-ledger, stock-ledger, attachment, and hash reconciliation has been recorded after restore.

## Required drill

1. Name the data owner, restore operator, approver, and incident commander.
2. Create an independent encrypted backup from production-shaped staging.
3. Restore into an isolated scratch environment with no production connections.
4. Apply the exact application artifact and schema procedure.
5. Reconcile companies/users, sequences, invoices/items, payments, returns, expenses, cash/customer balances, stock/movements/batches/transfers, sync receipts, activities, photos/signatures/PDFs, and authority response records.
6. Measure backup age, data loss window, restore duration, and service recovery.
7. Test missing/corrupt backup, missing key, partial object backup, and provider outage.
8. Record date, artifact/backup hashes, results, deviations, owners, RPO and RTO.

No backup or restore was executed during this audit because it would exceed the audit-only boundary and external-service prohibition.

