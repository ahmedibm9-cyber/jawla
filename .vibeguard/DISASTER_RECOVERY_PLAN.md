# Jawla Disaster Recovery Plan

## Phase 1: Business Impact Analysis

### Critical Services and Data

| Service/Data                | Owner         | Description                                                                                                                                                                                                            | Why Critical                                                                       |
| --------------------------- | ------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| **User authentication**     | Ops/Security  | 12 demo users with roles (sales_rep, rep, admin, hr_admin, warehouse_keeper, sales_manager, system_viewer); `is_active` flag gates all login                                                                           | All access flows through login; no rep can work without auth                       |
| **Company data**            | Ops           | Global Plastic Company (GPC): tax number, currency EGP, VAT 14%, bank details, rep discount %                                                                                                                          | All data is company-scoped; no company = no data access                            |
| **Product/inventory**       | Ops/Logistics | 50 products across 4 categories; stock levels in van/main warehouses; stock_movements table                                                                                                                            | Stock can't go negative; every sale reduces van stock; movements tracked for audit |
| **Sales orders & payments** | Finance       | Purchase orders, invoices, collect payments, returns logging                                                                                                                                                           | Revenue flow; no orders = no revenue recording                                     |
| **Customer data**           | Sales         | 50 customers across 3 routes (Cairo/Giza/Alexandria); customer assignments; outlets                                                                                                                                    | Reps visit customers; customer data drives sales route planning                    |
| **Route assignments**       | Sales         | 3 routes (Cairo/Giza/Alexandria) with sales rep assignments                                                                                                                                                            | Routes determine which customers each rep serves                                   |
| **License activation**      | Finance/Legal | `LicenseService::assertCanActivateUser()` checks yearly license; `is_active` + role check; **proprietary commercial license** - license key required for user activation; legal compliance required for yearly renewal | Without valid license, users can't be activated; legal compliance                  |

### Critical User Journeys

| Journey               | Actors    | Steps                                                                                         | RTO (max) | RPO (max) |
| --------------------- | --------- | --------------------------------------------------------------------------------------------- | --------- | --------- |
| **Rep daily flow**    | Sales rep | Check in → Pick route → Visit customers → Sell from van stock → Collect cash → Record returns | 4 hours   | 24 hours  |
| **Admin master data** | Admin     | Add/edit companies, products, customers, routes, users                                        | 8 hours   | 48 hours  |
| **Health monitoring** | Ops       | `/health` endpoint returns status for SRE/dashboard                                           | 1 hour    | 1 hour    |
| **License renewal**   | Finance   | Renew license yearly; reactivate users                                                        | 24 hours  | 7 days    |

### Maximum Tolerable Disruption (MTD)

- **Full app outage**: 24 hours → business severely impacted (reps can't sell, admin can't manage)
- **Partial degradation** (login OK but some features down): 8 hours → reps can still sell basic flow
- **Data loss of 1 day**: Acceptable but not ideal; weekly backups sufficient
- **Data loss of >1 week**: Critical → potential regulatory issues with Zatca/tax records

### Owners

| Service/Data           | Primary Owner      | Secondary Owner      | Contact |
| ---------------------- | ------------------ | -------------------- | ------- |
| User auth + login      | Ops Lead           | Security Lead        | -       |
| Company data           | Operations Manager | Finance Manager      | -       |
| Product/inventory      | Logistics Manager  | Warehouse Supervisor | -       |
| Sales orders/ payments | Finance Lead       | Operations Manager   | -       |
| Customer data          | Sales Manager      | CRM Administrator    | -       |
| Route assignments      | Sales Manager      | Operations Manager   | -       |
| License compliance     | Finance Lead       | Legal Counsel        | -       |

## Phase 2: Define Recovery Objectives

### Approved RTO/RPO

| Service/Data            | RTO (Maximum) | RPO (Maximum) | Justification                                                                                      |
| ----------------------- | ------------- | ------------- | -------------------------------------------------------------------------------------------------- |
| User authentication     | 4 hours       | 24 hours      | Reps need login to work; credential data can be recreated from onboarding records                  |
| Company data            | 8 hours       | 24 hours      | Tax/VAT data has regulatory implications; can be reconstructed from business records               |
| Product/inventory       | 4 hours       | 24 hours      | Stock levels can be recalculated from physical count; movements table is critical                  |
| Sales orders & payments | 8 hours       | 48 hours      | Revenue records; can be reconstructed from bank statements + order confirmations                   |
| Customer data           | 8 hours       | 24 hours      | Contact details from business cards/CRM export; route assignments from memory/maps                 |
| Route assignments       | 8 hours       | 7 days        | Can be recreated from sales rep memory + customer geographic data                                  |
| License activation      | 24 hours      | 7 days        | License key from purchase records; proprietary commercial license; reactivation window from vendor |

### Backup Frequency

| Service/Data               | Backup Frequency                          | Retention  | Storage                                        |
| -------------------------- | ----------------------------------------- | ---------- | ---------------------------------------------- |
| PostgreSQL (production)    | Daily pg_dump (midnight)                  | 30 days    | Railway volume + S3 bucket (offline/immutable) |
| Database schema/migrations | On every deploy (git)                     | Indefinite | GitHub (version-controlled)                    |
| Demo seed data             | After each significant dataset change     | Indefinite | Git (seeders/) + backed up with repo           |
| License config             | On change (yearly)                        | Indefinite | Git + physical copy                            |
| User credentials           | Never store plain text; reset on recovery | N/A        | Auth0/Supabase auth (if migrated)              |

### Recovery Priority Order

1. **User authentication** - Get reps logging in ASAP
2. **Company data** - Restore company context so data is scoped
3. **Product/inventory** - Restore stock levels so sales can proceed
4. **Sales orders** - Restore revenue recording capability
5. **Customer/route data** - Restore sales routing capability
6. **License activation** - Restore license validity

## Phase 3: Design Recovery Architecture

### Backup Types and Frequency

| Backup Type            | Command/Method                           | Frequency            | Retention     | Notes                                                         |
| ---------------------- | ---------------------------------------- | -------------------- | ------------- | ------------------------------------------------------------- |
| **Full Postgres dump** | `pg_dump -Fc railway > backup.dump`      | Daily (02:00 UTC)    | 30 days       | Compressed; stored in S3/Railway volume                       |
| **Schema-only**        | `pg_dump -s railway > schema.sql`        | On every deploy      | Indefinite    | Git-versioned; no data                                        |
| **Seed data**          | `php artisan db:seed --class=DemoSeeder` | After dataset change | Indefinite    | Git tracked; `JAWLA_MODE=demo` required                       |
| **License config**     | Manual export                            | On change            | Indefinite    | Docs + git + `LICENSE` file with proprietary commercial terms |
| **Railway PITR**       | Railway built-in                         | Continuous           | Point-in-time | $0.023/GB/month add-on                                        |

### Recovery Architecture

```mermaid
graph LR
    A[Production Railway] -->|Replicates| B[Railway PITR (primary)]
    A -->|Daily dump| C[S3 Bucket (offline/immutable)]
    B --> D[Restore to new Railway project]
    C --> E[Restore via pg_restore]
    D --> F[Railway variables + env]
    E --> F
    F --> G[App redeploy]
    G --> H[Verify health + login]

    style A fill:#e3f2fd,stroke:#1976d2,stroke-width:2px
    style B fill:#c8e6c9,stroke:#388e3c,stroke-width:2px
    style C fill:#fff3e0,stroke:#f57c00,stroke-width:2px
    style D fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px
    style G fill:#fb8c00,stroke:#f57c00,stroke-width:2px
```

### Separation and Immutability

- **Railway PITR**: Built-in point-in-time recovery; data stored in Railway volume; immutable by default
- **S3 bucket**: Separate AWS account/region; bucket policy with `ObjectLock` for immutability; lifecycle rule to transition to Glacier after 30 days
- **Git repo**: All code/config in Git; `git restore` for code; `git reset` for seed data; signed commits for integrity
- **No single point of failure**: Railway volume + S3 + Git = 3 independent backup sources

### Credentials and Access

| Credential                 | Stored                                | Access                | Rotation                     |
| -------------------------- | ------------------------------------- | --------------------- | ---------------------------- |
| Railway API token          | GitHub Actions secret `RAILWAY_TOKEN` | CI/CD only            | Rotate every 90 days         |
| PostgreSQL `postgres` user | Railway injected; not in .env         | Railway internal only | Rotate via Railway dashboard |
| S3 bucket access           | IAM user in separate AWS account      | Backup job only       | Rotate yearly                |
| Railway dashboard          | Web UI                                | Ops team              | MFA enabled                  |
| S3 console                 | Web UI                                | Ops team              | MFA enabled                  |

### Infrastructure/Config Restoration

1. **Create new Railway project** → `railway create-project`
2. **Add PostgreSQL service** → Railway auto-provisions; note `PGHOST`, `PGPORT`, `PGUSER`, `PGPASSWORD`, `PGDATABASE`
3. **Restore from PITR** → Railway dashboard → "Restore" → select timestamp
4. **Or restore from dump** → `pg_restore -d railway backup.dump`
5. **Redeploy app** → `railway up` with all env vars from `.env` + `RAILWAY_TOKEN`
6. **Verify** → `curl https://app.url/health` → should return `{"status":"ok","db":"ok","cache":"ok"}`
7. **Test login** → `superadmin@jawla.test` / `12345678`

### Third-Party Dependencies

| Dependency            | Failure Mode            | Mitigation                                                          |
| --------------------- | ----------------------- | ------------------------------------------------------------------- |
| Railway platform      | Platform outage         | Multi-region not possible; PITR + S3 backup mitigates data loss     |
| Laravel Sanctum       | Token auth service down | Sessions on PG; can switch to database sessions manually            |
| S3 storage            | AWS outage              | Railway volume + Git repo = source of truth; S3 is secondary        |
| Leaflet maps          | CDN/ API down           | Offline mode; static tiles cached; fallback to OSM                  |
| Sentry error tracking | Service down            | Error logs still written to storage/logs/laravel.log; manual triage |

## Phase 4: Write Runbooks

### Runbook: Login Failure (Most Likely Scenario)

**Trigger**: Rep can't login; error dialog "حدث خطأ ما. يرجى المحاولة مرة أخرى لاحقًا."

**Detection**:

- User reports login failure
- Monitor: `GET /health` returns 500 or timeout
- Log: `laravel.log` shows Livewire /livewire/update 500

**Declaration**:

1. Check `railway status` → service running?
2. Check `railway variables` → all env vars present?
3. Check health: `curl https://app.url/health`
4. If health OK but login 500 → proceed with restoration

**Restore Steps**:

1. `railway variables` → list all env vars
2. If missing `APP_KEY`: generate new key `base64:$(openssl base64 -d 32)` → `railway variables set APP_KEY=...`
3. If missing DB vars: Railway auto-injects `PG*` vars; if missing, add manually from railway postgres service details
4. If `CACHE_STORE` not `file`: `railway variables set CACHE_STORE=file`
5. If `JAWLA_MODE` not `production` or `demo`: `railway variables set JAWLA_MODE=production`
6. `railway up` → redeploy
7. `curl https://app.url/health` → verify `{"status":"ok","db":"ok","cache":"ok"}`
8. Test login: `superadmin@jawla.test` / `12345678`

**Validation**:

- Health endpoint returns 200 + OK JSON
- Login page loads at `/login`
- POST credentials → successful redirect to `/app`
- Rep dashboard accessible

**Failback**:

- If restore doesn't work, escalate to Railway support with backup ID
- Document time-to-recovery; log as incident

**Contacts**: Ops Lead (primary), Railway Support (secondary)

### Runbook: Database Restoration (Worst Case)

**Trigger**: Complete DB loss; no healthy replicas; data corruption detected

**Detection**:

- `curl /health` → db:error or connection refused
- Railway dashboard shows DB status: failed
- Automated alert from SRE

**Declaration**:

1. Confirm failure with secondary check
2. Notify stakeholders: Ops Lead, Finance Lead, Legal Counsel
3. Open incident ticket

**Restore Steps**:

1. **If Railway PITR enabled**:
   - Railway dashboard → Browse previous backups
   - Select timestamp just before failure
   - Click "Restore" → new Railway project created
   - Note new project ID; deploy app

2. **If using pg_dump backup**:

   ```bash
   # Restore to new Railway project
   pg_restore -d new_railway_db backup.dump
   # Or via Railway dashboard: Import backup
   ```

3. **Restore seed data**:

   ```bash
   JAWLA_MODE=demo php artisan db:seed --class=DemoSeeder --force
   # Set all passwords to temporary value
   railway run php artisan tinker --execute="
       App\Models\User::query()->each(function(\$u) {
           \$u->update(['password' => Hash::make('TempPass123!')]);
       });
   "
   ```

4. **Redeploy app**:

   ```bash
   railway up
   ```

5. **Verify**:
   ```bash
   curl https://app.url/health
   # superadmin@jawla.test / TempPass123!
   # Change all passwords after restore
   ```

**Validation**:

- Health endpoint returns OK
- All 12 users login with temporary password
- Change all passwords to production values after verification

**Failback**:

- Re-integrate restored data with any incremental changes since backup
- Document RTO achieved; record incident post-mortem

**Contacts**: Ops Lead (primary), Railway Support (secondary), Finance Lead (for license reactivation)

### Runbook: License Reactivation

**Trigger**: License expired; users can't be activated; `LicenseService::assertCanActivateUser()` fails

**Detection**:

- Admin tries to add new user → "License limit exceeded" error
- `/health` still OK but auth blocked
- `php artisan tinker` → `app(LicenseService::class)->assertCanActivateUser(1)` fails

**Declaration**:

1. Check license expiry date in config/docs
2. Verify purchase records
3. Notify Finance Lead

**Restore Steps**:

1. **If license key still valid**:
   - `railway variables set LICENSE_KEY=...` (if env var exists)
   - Or update `config/license.php` with new key
   - `railway up`

2. **If license yearly renewal needed**:
   - Purchase new license key from vendor
   - `railway variables set LICENSE_KEY=new_key`
   - `railway up`
   - `php artisan tinker --execute="app(LicenseService::class)->refreshLicense()"`

3. **If reactivating existing users**:
   ```bash
   railway run php artisan tinker --execute="
       \$users = App\Models\User::where('is_active', true)->take(5)->get();
       foreach (\$users as \$u) {
           app(LicenseService::class)->activate(\$u->id);
       }
   "
   ```

**Validation**:

- `app(LicenseService::class)->assertCanActivateUser(1)` passes
- New user can be created and activated
- `/health` returns OK

**Failback**:

- Update license docs + vendor contract
- Record renewal date for future

## Phase 5: Exercise and Measure

### Exercise 1: Login Restoration (Tabletop)

**Scenario**: Rep can't login; error on Livewire update

**Participants**: Ops Lead, 1 rep, Admin

**Steps**:

1. Simulate failure: Set `APP_KEY` missing in env (temporarily remove from railway variables)
2. Measure time from detection to working login
3. Record steps taken, time spent, any errors

**Expected RTO**: < 30 minutes
**Actual RTO**: ______ (measure during exercise)
**Defects found**: ______

### Exercise 2: Database Restoration

**Scenario**: Simulate DB loss; restore from pg_dump backup

**Participants**: Ops Lead, 1 Dev, 1 DBA (simulated)

**Steps**:

1. Delete railway postgres service (simulated - don't actually delete)
2. Restore from backup
3. Redeploy app
4. Verify health + login

**Expected RTO**: < 2 hours (with PITR) or < 4 hours (from dump)
**Actual RTO**: ______
**RPO achieved**: ______ (data loss measured)

**Defects found**: ______

### Exercise 3: License Reactivation

**Scenario**: License expired; users can't be activated

**Participants**: Finance Lead, Ops Lead

**Steps**:

1. Simulate expiry: Set fictitious expiry date in docs
2. Measure time from detection to working license
3. Record steps, time, vendor contact

**Expected RTO**: < 24 hours (coincides with license renewal cycle)
**Actual RTO**: ______
**Defects found**: ______

### Exercise Metrics Summary

| Exercise             | Expected RTO   | Actual RTO | RTO Gap | Defects | Status |
| -------------------- | -------------- | ---------- | ------- | ------- | ------ |
| Login restoration    | < 30 min       | ______     | ______  | ______  | ______ |
| DB restoration       | < 2 hrs (PITR) | ______     | ______  | ______  | ______ |
| License reactivation | < 24 hrs       | ______     | ______  | ______  | ______ |

## Phase 6: Maintain Readiness

### Automated Backup Verification

```bash
# Monthly: Verify backup exists and is restorable
0 2 1 * * pg_lsbackup | wc -l | grep -q 1 && echo "Backup OK" || echo "MISSING backup"

# Weekly: Verify pg_dump can restore
pg_restore --list backup_$(date +%Y%m%d).dump > /dev/null 2>&1 && echo "Restore test OK" || echo "Restore FAILED"

# Daily: Check Railway volume health
railway postgres heath > /dev/null 2>&1 && echo "DB healthy" || echo "DB issue detected"
```

### Monitor Recovery Assets

| Asset               | Monitoring Method                         | Alert On                              |
| ------------------- | ----------------------------------------- | ------------------------------------- |
| Railway PITR status | `railway postgres status` daily           | "failed" or "no backups"              |
| S3 bucket integrity | `aws s3api head-bucket` weekly            | 403/404 errors                        |
| Git repo health     | `git status` daily                        | diverged/unpushed branches            |
| Railway variables   | `railway variables --check` weekly        | Missing critical vars (APP_KEY, DB_*) |
| Health endpoint     | Uptime monitor ping `/health` every 5 min | 500 or timeout                        |

### Rotate Credentials

| Credential                          | Rotation Frequency                  | Method                                               |
| ----------------------------------- | ----------------------------------- | ---------------------------------------------------- |
| Railway API token (`RAILWAY_TOKEN`) | Every 90 days                       | GitHub Secrets → rotate; update all deploys          |
| PostgreSQL superuser password       | Yearly (or on suspected compromise) | Railway dashboard → rotate; update env vars          |
| S3 bucket access keys               | Yearly                              | IAM console → rotate; update backup job              |
| Laravel `APP_KEY`                   | Never rotate (baked into image)     | If rotated: re-encrypt all cookie data; notify users |

### Update After Changes

- **New feature deployed** → Re-run `php artisan migrate --force`; verify backup includes new tables
- **Env var added** → Add to `.env` + railway variables; document in runbook
- **License renewed** → Update expiry date in docs + `config/license.php`; note in runbook
- **Backup method changed** → Update this plan; notify all stakeholders

### Quarterly Review

- [ ] Verify all backups exist and are restorable
- [ ] Measure actual RTO/RPO from last exercise; compare to approved objectives
- [ ] Check for new critical services/data since last review
- [ ] Rotate credentials per schedule
- [ ] Update runbooks for any architecture changes
- [ ] Review and approve RTO/RPO objectives (or adjust based on business needs)
- [ ] Review and sign-off by: Ops Lead, Finance Lead, Security Lead

## Required Outputs

### 1. `.vibeguard/DISASTER_RECOVERY_PLAN.md`

Created as part of this exploration (see below).

### 2. `.vibeguard/RECOVERY_TEST_REPORT.md`

To be created after exercises are run. Will contain:

- Exercise 1 (Login restoration): RTO, defects, status
- Exercise 2 (DB restoration): RTO, RPO, defects, status
- Exercise 3 (License reactivation): RTO, defects, status
- Overall readiness status: ready | conditional | not_ready

### Business-Impact and RTO/RPO Matrix

| Service/Data            | RTO      | RPO      | Backup Frequency    | Last Verified | Owner              |
| ----------------------- | -------- | -------- | ------------------- | ------------- | ------------------ |
| User authentication     | 4 hours  | 24 hours | Daily pg_dump + Git | $(date)       | Ops Lead           |
| Company data            | 8 hours  | 24 hours | Daily pg_dump + Git | $(date)       | Operations Manager |
| Product/inventory       | 4 hours  | 24 hours | Daily pg_dump + Git | $(date)       | Logistics Manager  |
| Sales orders & payments | 8 hours  | 48 hours | Daily pg_dump + Git | $(date)       | Finance Lead       |
| Customer data           | 8 hours  | 24 hours | Daily pg_dump + Git | $(date)       | Sales Manager      |
| Route assignments       | 8 hours  | 7 days   | Daily pg_dump + Git | $(date)       | Sales Manager      |
| License activation      | 24 hours | 7 days   | On change (yearly)  | $(date)       | Finance Lead       |

### Scenario Runbooks

1. **Login failure** (most likely) - See runbook above
2. **Database restoration** (worst case) - See runbook above
3. **License reactivation** - See runbook above

### Restoration Evidence

- **Exercise 1** (Login restoration): ______ completed on ______ RTO: ______ Defects: ______
- **Exercise 2** (DB restoration): ______ completed on ______ RTO: ______ RPO: ______ Defects: ______
- **Exercise 3** (License reactivation): ______ completed on ______ RTO: ______ Defects: ______

### Gaps and Owners

| Gap                                          | Owner        | Resolution Plan                             | Target Date    |
| -------------------------------------------- | ------------ | ------------------------------------------- | -------------- |
| No formal backup strategy documented         | Ops Lead     | Create DISASTER_RECOVERY_PLAN.md            | Completed      |
| Livewire /livewire-update 500 blocking login | Dev Lead     | Fix env vars + redeploy; or debug PHP error | High priority  |
| Railway PITR not configured                  | Ops Lead     | Enable Railway PITR ($0.023/GB/mo)          | This quarter   |
| S3 offsite backup not configured             | Ops Lead     | Create S3 bucket + pg_dump cron job         | This quarter   |
| License renewal process undocumented         | Finance Lead | Document vendor contract + expiry date      | This quarter   |
| No runbook tested/exercised                  | Ops Lead     | Run all 3 exercises; measure RTO/RPO        | End of quarter |

### Readiness Status

**ready** ☐ All critical services have documented backups, RTO/RPO approved, exercises completed, runbooks tested.

**conditional** ☐ Most things in place but some gaps; exercises partially completed; RTO/RPO approximate.

**not_ready** ☐ Critical gaps exist; no backups verified; RTO/RPO undefined; exercises not run.

---

*Plan generated on $(date)*. Next review: $(date +%Y-%m-%d). Keep this file updated after any architecture change, incident, or quarterly review.
