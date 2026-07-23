# Privacy and operational release gates

**Status:** implementation template — not legal advice and not an approval.

Jawla must remain restricted to synthetic-data demonstrations until every owner
below has supplied the evidence marked **required** and the release authority
has signed the go/no-go record.

## Data inventory and privacy approval

| Processing activity                 | Data                                            | Purpose                           | System/vendor                    | Required owner evidence                                     |
| ----------------------------------- | ----------------------------------------------- | --------------------------------- | -------------------------------- | ----------------------------------------------------------- |
| Rep account and role administration | name, email, phone, employee ID                 | access control and support        | Jawla, hosting provider          | controller, retention period, access matrix                 |
| Field location tracking             | coordinates, timestamps, rep ID                 | route/visit operations            | Jawla, maps provider             | lawful basis, employee notice, precision/retention decision |
| Customer and financial records      | customer contacts, invoices, payments, tax data | sales, collection, accounting     | Jawla, database/backup vendor    | controller, retention schedule, rights workflow             |
| Photos and signatures               | images, metadata, user/customer context         | visit and document evidence       | object storage vendor            | notice, private-access policy, deletion schedule            |
| Error telemetry                     | pseudonymous device/browser diagnostics         | reliability and incident response | Sentry or configured replacement | DPA, region/transfer review, scrubber verification          |
| Browser offline state               | queued write payloads and status                | resilient field work              | device IndexedDB                 | device-loss procedure and logout/identity-purge proof       |

Before real data, the accountable privacy owner and counsel must approve and
retain: a ROPA/data inventory, bilingual privacy notice, employee/location
notice, lawful bases, vendor/DPA and transfer register, retention/deletion
schedule, data-subject request workflow, breach procedure, and current signed
terms reflecting the actual Railway/backup/object-storage topology.

## Release ownership

| Gate                             | Primary owner             | Deputy                 | Required evidence                                                              |
| -------------------------------- | ------------------------- | ---------------------- | ------------------------------------------------------------------------------ |
| Security and tenancy             | Engineering lead          | Security reviewer      | clean blocking CI, authenticated authorization tests, remediation record       |
| Financial/stock integrity        | Finance owner             | Sales operations owner | simultaneous/rollback test evidence and reconciliation sign-off                |
| Offline/PWA safety               | Engineering lead          | Field operations owner | shared-device, update, offline/reconnect evidence on supported devices         |
| Backup/restore/rollback          | Operations owner          | Independent operator   | encrypted off-host backup, measured scratch restore, reconciliation, RPO/RTO   |
| Monitoring and incident response | Operations owner          | Support lead           | dashboard, tested alert delivery, incident exercise and on-call contacts       |
| Privacy/legal/tax                | Privacy owner and counsel | Executive sponsor      | signed documents, vendor register, ETA preproduction approval where applicable |
| UAT and pilot authorization      | Customer sponsor          | Project owner          | signed multi-role UAT, synthetic-data pilot plan, abort authority              |

Fill the names, contact paths, dates, and evidence links in a release record;
generic roles are not an approval.

## Incident and support minimums

1. A support lead acknowledges P1 incidents within one hour and keeps a
   bilingual customer/rep communication template.
2. The incident commander can disable real-data access, pause promotion, and
   direct rollback without waiting for a developer.
3. Each exercise records detection time, owner, customer impact, containment,
   recovery, data reconciliation, and follow-up actions.
4. The first real-data pilot has a written scope, synthetic-data fallback,
   max user/device count, daily reconciliation, stop conditions, and named
   abort/rollback authority.

## Backup and rollback evidence

`scripts/backup.sh` uploads only encrypted archives to an off-host rclone
remote and fails closed if its encryption or remote-storage prerequisites are
absent. `scripts/restore-backup.sh` refuses `DATABASE_URL` and requires an
explicit scratch-target acknowledgement. Before release, an independent
operator must record actual RPO/RTO from a restore and reconcile invoices,
payments, returns, cash boxes, stock movements, and the PWA/client rollback
path in `docs/BACKUP_RESTORE.md`.
