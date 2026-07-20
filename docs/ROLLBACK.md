# Jawla — Deployment, Backup & Rollback Runbook

**Scope:** UAT / demo release. Real ETA (Egyptian Tax Authority) e-invoice
submission is a **go-live gate** and is intentionally out of this release —
see [ZATCA / ETA gating](#zatca--eta-gating). Do not issue legally-binding tax
invoices from this build.

**Verified:** 2026-07-20 against PostgreSQL 17, fresh `migrate:fresh --seed`
plus a full `pg_dump` → `pg_restore` cycle with matching row counts.

---

## 1. Pre-deploy checklist

- [ ] `php artisan test` green (feature/unit suite).
- [ ] `composer test:browser` green (E2E; run separately — spawns Chromium).
- [ ] Target `.env` reviewed: `APP_ENV=production`, `APP_DEBUG=false`,
      `APP_KEY` set, DB credentials correct, `APP_URL` = real host, TLS enforced.
- [ ] No secrets committed — `.env` is server-only; `.env.example` holds blank
      placeholders only.
- [ ] A backup of the current production DB exists (see §3) **before** migrating.

## 2. Deploy

```bash
git pull --ff-only                      # or deploy the tagged release
composer install --no-dev --optimize-autoloader
php artisan migrate --force             # forward-only; never migrate:fresh in prod
php artisan config:cache route:cache view:cache
php artisan filament:optimize
npm ci && npm run build                 # if assets are built on the host
```

Notifications and jobs run **synchronously** (no class implements
`ShouldQueue` in this release), so a queue worker is **not required** for V1.
`QUEUE_CONNECTION=database` is inert until a queued job is added; if one is,
run `php artisan queue:work` under a supervisor before deploying it.

## 3. Backup (run before every deploy)

Custom-format, compressed, restorable dump (what was rehearsed):

```bash
export PGPASSWORD='***'
pg_dump -Fc -h <host> -p 5432 -U <user> -d <database> \
  -f "jawla_$(date +%Y%m%d_%H%M%S).dump"
```

Store the dump encrypted, off-host. Verify it restores (§4) at least once per
release — an untested backup is not a backup.

## 4. Restore / verify a backup

```bash
createdb -h <host> -U <user> jawla_restore_check
pg_restore -h <host> -U <user> -d jawla_restore_check \
  --no-owner --no-privileges "jawla_YYYYMMDD_HHMMSS.dump"
```

Confirm the restore by comparing row counts against the source:

```sql
SELECT 'users'      AS t, count(*) FROM users
UNION ALL SELECT 'products',      count(*) FROM products
UNION ALL SELECT 'customers',     count(*) FROM customers
UNION ALL SELECT 'invoices',      count(*) FROM invoices
UNION ALL SELECT 'stocks',        count(*) FROM stocks
UNION ALL SELECT 'naming_series', count(*) FROM naming_series;
```

## 5. Rollback

Roll back **application first, database second**, and only restore the DB if the
release included migrations that must be undone.

1. **Application**
   ```bash
   git checkout <previous-release-tag>
   composer install --no-dev --optimize-autoloader
   php artisan config:cache route:cache view:cache
   ```
2. **Database** (only if schema/data changed and must be reverted)
   - Preferred: `pg_restore` the pre-deploy backup from §3 into a new DB, then
     repoint `DB_DATABASE`. This avoids destructive in-place drops.
   - Migrations here are forward-only and reversal is a **compensating
     transaction** (never `delete()`), so most data issues are corrected
     forward rather than by `migrate:rollback`.
3. **Verify** — run the §4 row-count query and smoke-log into both panels (§6).

## 6. Post-deploy / post-restore smoke

- [ ] `GET /admin/login` and `GET /` (redirects to `/admin/login`) return 200.
- [ ] Log in as `admin@` (Filament panel loads) and as `rep@` (`/app` loads).
- [ ] Create a test invoice → confirm sequential number, stock decrement, and a
      matching `stock_movements` row.
- [ ] Confirm bilingual rendering: `/locale/ar` → `dir="rtl"`, `/locale/en` →
      `dir="ltr"`.

## 7. ZATCA / ETA gating

QR strategy is resolved per company in
`app/Services/InvoiceQrService.php::resolveStrategy()`:

- **Egypt (default, `country = EG`)** → `EgyptQrStrategy` (simple QR). This is
  what the demo/UAT tenant uses. It is **not** a full ETA e-invoice submission.
- **Saudi (`country = SA`, `zatca_enabled`, `zatca_csid` present)** →
  `ZatcaPhase2Strategy` (cryptographic stamp). Activates only when a real CSID
  is provisioned; falls back to Phase 1 otherwise.

For a real Egyptian production launch, full **ETA Phase 2** integration
(submission + cryptographic signing + UUID/hash chain) must be completed and
certified before issuing legally-binding invoices. Until then, treat all
invoices from this build as demo/UAT only.

## 8. Health & monitoring

- Point the platform health check at `/admin/login` (cheap, unauthenticated,
  exercises the framework + DB session driver).
- Ship application logs off-host; alert on 5xx rate and DB connection errors.
- Alarms/notifications are in-app (database channel) — there is no external
  push dependency to monitor in V1.
