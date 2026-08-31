# Jawla Go-Live Implementation Plan

## Scope

Resolve all production blockers to enable first real workday usage with 8 field reps.

## Non-Goals

- Full ETA cert signer implementation (deferred to Week 2)
- k6 load testing suite (deferred to Week 2)
- Automated nightly backup pipeline (deferred to Week 2)
- PagerDuty/Opsgenie setup (WhatsApp sufficient for V1)
- RBAC matrix tests (defense-in-depth, not blocking)

## Architecture Decisions

| Decision               | Choice                                     | Rationale                                                   | Reversible         |
| ---------------------- | ------------------------------------------ | ----------------------------------------------------------- | ------------------ |
| ETA mode               | Stub/mock for launch week                  | Legal grace period likely; prove integration contract first | Yes — flip env var |
| Backup strategy        | Manual pg_dump drill first, automate later | Proves chain works; automation can wait                     | Yes                |
| Incident ownership     | WhatsApp group + SUPPORT.md                | Egyptian SME pattern; 8 reps don't need PagerDuty           | Yes                |
| Device registration    | Manual artisan command per rep             | 8 reps × 2 min = 16 min total                               | Yes                |
| Performance monitoring | Rate limiting + response time logging      | Real usage > synthetic load for 8 reps                      | Yes                |

---

## Phase 1: Production Config (Today — 30 min)

**Goal**: App is configured for production safety.

### Task 1.1: Set production env vars in Railway

- **Files**: Railway dashboard (no code changes)
- **Vars to set**:
  - `SESSION_ENCRYPT=true`
  - `LOG_LEVEL=warning`
  - `APP_DEBUG=false`
  - `SESSION_SECURE_COOKIE=true`
  - `SESSION_DRIVER=redis`
  - `CACHE_DRIVER=redis`
  - `QUEUE_CONNECTION=redis`
- **Acceptance**: `php artisan tinker` → `config('session.encrypt')` returns `true`, `config('app.debug')` returns `false`
- **Rollback**: Set vars back to previous values in Railway dashboard
- **Owner**: Developer

### Task 1.2: Rotate APP_KEY

- **Files**: Railway dashboard
- **Command**: `php artisan key:generate` locally, paste new key into Railway `APP_KEY`
- **Acceptance**: All sessions invalidated (users must re-login), `config('app.key')` shows new key
- **Rollback**: Restore previous APP_KEY (invalidates all sessions again)
- **Owner**: Developer

### Task 1.3: Write SUPPORT.md

- **File**: `SUPPORT.md` (repo root)
- **Content**: Who to call, phone number, Railway project URL, first 3 things to check
- **Acceptance**: File exists with 3 sections: Contact, Quick Diagnostics, Rollback
- **Owner**: Developer + Sales Manager

### Task 1.4: Verify Sentry integration

- **Files**: `.env.production`
- **Command**: Set `SENTRY_DSN` in Railway, verify errors appear in Sentry dashboard
- **Acceptance**: Trigger a test error, confirm it appears in Sentry within 60 seconds
- **Owner**: Developer

---

## Phase 2: Operational Readiness (This Week — 2 hours)

**Goal**: Backup works, offline works, devices register.

### Task 2.1: Backup/Restore Drill

- **Files**: Railway dashboard, scratch database
- **Steps**:
  1. `railway run pg_dump $DATABASE_URL > backup.sql`
  2. Create scratch Railway database
  3. `railway run psql $SCRATCH_URL < backup.sql`
  4. Run `php artisan migrate:status` on scratch
  5. Spot-check 3 tables (companies, invoices, users)
- **Acceptance**: Restore completes, all tables present, data matches source
- **Rollback**: Delete scratch database
- **Owner**: Developer

### Task 2.2: Offline Sync Manual Test

- **Files**: None (manual browser test)
- **Steps**:
  1. Open Chrome DevTools → Network → Offline
  2. Log in as rep, create a visit
  3. Create an order (should queue locally)
  4. Go back Online
  5. Verify sync endpoint fires and server received both records
- **Acceptance**: Visit and order appear in database after reconnection
- **Rollback**: N/A (test only)
- **Owner**: Developer + QA

### Task 2.3: Device Registration on Real Phone

- **Files**: None (SSH into Railway)
- **Steps**:
  1. Get rep's device fingerprint from app
  2. SSH: `railway run php artisan tinker` → register device
  3. Verify rep can access `/app` after registration
- **Acceptance**: Rep sees home dashboard after registration
- **Rollback**: Delete device record via tinker
- **Owner**: Developer

### Task 2.4: Add Sync Status Indicator

- **File**: `resources/views/livewire/app/home.blade.php`
- **Change**: Add green/yellow/red dot showing last sync time
- **Acceptance**: Rep sees sync status on home screen
- **Rollback**: Remove indicator from blade template
- **Owner**: Developer

---

## Phase 3: Pre-Launch Validation (Before Go-Live — 1 day)

**Goal**: App is validated for real usage.

### Task 3.1: ETA Stub Mode

- **Files**: `.env.production`
- **Command**: Set `ETA_ENABLED=false` in Railway
- **Acceptance**: Invoice PDFs generate with "pending ETA submission" flag, no ETA API calls
- **Rollback**: Set `ETA_ENABLED=true` when real credentials arrive
- **Owner**: Developer

### Task 3.2: Load Test (Quick)

- **Files**: None (command-line only)
- **Command**: `ab -n 500 -c 20 -H "Cookie: <session>" https://jawla.up.railway.app/app`
- **Acceptance**: p95 < 2.5s, http_req_failed < 5%
- **Rollback**: N/A (test only)
- **Owner**: Developer

### Task 3.3: Create WhatsApp Support Group

- **Files**: None (WhatsApp)
- **Steps**:
  1. Create "Jawla Support" group
  2. Add all 8 reps + developer + sales manager
  3. Pin SUPPORT.md content as group description
- **Acceptance**: All members in group, support contact visible
- **Owner**: Sales Manager

### Task 3.4: End-to-End Smoke Test

- **Files**: None (manual walkthrough)
- **Steps**:
  1. Rep logs in at `/app`
  2. Completes a visit (check-in, report, signature)
  3. Sells an item (invoice + stock decrement)
  4. Collects payment
  5. Verifies admin panel shows the sale
- **Acceptance**: Full sales cycle completes without errors
- **Rollback**: N/A (test only)
- **Owner**: Developer + 1 Rep

---

## Phase 4: Week 2 Hardening (Post-Launch)

**Goal**: Production-grade operations.

### Task 4.1: Real ETA Cert Signer

- **Files**: `app/Services/Eta/` (new signer implementation)
- **Dependencies**: Client ETA credentials + signing certificate
- **Acceptance**: One successful preprod submission to ETA API
- **Owner**: Developer

### Task 4.2: Automated Backup

- **Files**: `.github/workflows/backup.yml` (new)
- **Command**: Weekly `pg_dump` to S3
- **Acceptance**: Backup appears in S3 bucket weekly
- **Owner**: Developer

### Task 4.3: k6 Load Test

- **Files**: `tests/k6/` (existing scripts)
- **Command**: Run against staging with PerfUserSeeder
- **Acceptance**: SLOs met (p95 < 1.5s reads, < 2.5s writes)
- **Owner**: Developer

### Task 4.4: Session Encryption Upgrade

- **Files**: `.env.production`
- **Command**: Set `SESSION_ENCRYPT=true` (if deferred from Phase 1)
- **Acceptance**: Sessions encrypted at rest
- **Owner**: Developer

---

## Critical Path

```
Phase 1 (Today) → Phase 2 (This Week) → Phase 3 (Before Go-Live) → Phase 4 (Week 2)
```

Phase 1 has no dependencies. Phase 2 tasks are independent. Phase 3 depends on Phase 2 completion. Phase 4 is post-launch.

## Approval Gates

| Gate              | Required Approver         | Evidence                                                 |
| ----------------- | ------------------------- | -------------------------------------------------------- |
| Go-Live           | Sales Manager + Developer | Phases 1-3 complete, smoke test passed                   |
| ETA Activation    | Developer + Client        | Real credentials received, preprod submission successful |
| Backup Automation | Developer                 | First automated backup verified                          |

## Risks

| Risk                                    | Likelihood | Impact | Mitigation                                    |
| --------------------------------------- | ---------- | ------ | --------------------------------------------- |
| ETA no grace period                     | Low        | High   | Check with client's accountant before go-live |
| Offline data loss in edge cases         | Medium     | High   | Sync status indicator + manual test           |
| Restore drill fails                     | Low        | Medium | Run drill before go-live, not on go-live day  |
| Performance issue found late            | Low        | Medium | Rate limiting + response time monitoring      |
| Device registration fails on real phone | Low        | Medium | Test on 3 devices before go-live              |
