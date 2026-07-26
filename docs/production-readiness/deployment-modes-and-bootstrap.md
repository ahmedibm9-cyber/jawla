# Deployment Modes and Production Bootstrap

Jawla has two explicit runtime modes. They must use different databases and must never share transaction, identity, sequence, GPS, stock, balance, audit, or sync data.

## Evaluation/demo

Required environment:

```dotenv
JAWLA_MODE=demo
```

Use a dedicated demo database and private object store. After migrations, the ordinary database seeder may be run to install canonical roles and synthetic evaluation data:

```text
php artisan migrate --force
php artisan db:seed --force
```

The seeder generates a different 24-character password for every demo identity and writes the credential map to:

```text
storage/app/private/demo-credentials.json
```

Treat that file as a secret, deliver it only through an approved private channel, and remove or rotate it when the evaluation ends. Do not copy the demo database, credentials file, private objects, or selected transaction rows into production.

Demo mode visibly marks the application and financial documents as evaluation samples. External tax submission is disabled.

## Production

Required environment:

```dotenv
JAWLA_MODE=production
APP_ENV=production
APP_DEBUG=false
```

Production must start with a separate, empty database. Ordinary deployment runs migrations and cache preparation only. It does not seed demo content or create an administrator.

After verifying the empty production database, supply a unique strong password through the deployment platform's secret manager:

```dotenv
JAWLA_BOOTSTRAP_ADMIN_PASSWORD=<unique secret of at least 16 characters>
```

Run the one-time bootstrap with explicit legal-company and administrator values:

```text
php artisan app:bootstrap-production \
  --confirm=BOOTSTRAP \
  --company-name-en="Example Company" \
  --company-name-ar="<legal Arabic name>" \
  --tax-number="<legal tax number>" \
  --admin-name="<administrator name>" \
  --admin-email="<administrator email>"
```

The command refuses demo mode, missing confirmation, weak secrets, and any database that already contains a company or user. It installs canonical roles and creates the company and setup administrator in one database transaction.

Immediately after success:

1. Remove `JAWLA_BOOTSTRAP_ADMIN_PASSWORD` from the runtime environment.
2. Log in through the approved HTTPS domain.
3. Verify the active company shown in the header.
4. Configure the setup administrator's required security controls before other users are admitted.
5. Add real master data and explicitly approved opening balances through validated application workflows only.

Do not rerun the bootstrap. Do not run `DemoSeeder` or promote a demo backup into production.
