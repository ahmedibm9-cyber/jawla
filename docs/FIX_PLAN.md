# Fix Plan — Production Readiness Remediation

**Source:** Production Readiness Audit (JAWLA-2026-08-03, score 835/1000)
**Scope:** 7 configuration, documentation, and verification fixes
**Code changes:** 0 — all fixes are Railway env vars, documentation, or external audits

---

## Fix inventory

| Fix ID  | Type            | Requires commit | Blocks client testing |
| ------- | --------------- | --------------- | --------------------- |
| FIX-001 | Railway env var | No              | No                    |
| FIX-002 | Railway env var | No              | No                    |
| FIX-003 | Documentation   | No              | No                    |
| FIX-004 | Railway env var | No              | No                    |
| FIX-005 | Railway config  | No              | No                    |
| FIX-006 | External audit  | No              | No                    |
| FIX-007 | External audit  | No              | No                    |

---

## FIX-001 — Secure session cookies on staging

**Priority:** P0
**Resolves:** SEC-001 (session cookies may not be secure on staging)
**Risk:** low
**Behavior change:** none

### What to do

Set this environment variable on the **jawla-staging** service in Railway dashboard:

```
SESSION_SECURE_COOKIE=true
```

Railway dashboard → jawla-staging service → Variables tab → Add variable.

### Verification

1. Open staging URL in browser
2. Open DevTools → Application → Cookies
3. Confirm the `jawla_session` cookie has `Secure` flag = ✅
4. Confirm `HttpOnly` = ✅ and `SameSite=Lax`

### Expected score gain: 10-15 points

---

## FIX-002 — Add staging domain to CORS allowlist

**Priority:** P0
**Resolves:** SEC-002 (CORS may block staging API calls)
**Risk:** low
**Behavior change:** none
**Code change:** Already applied in working tree (`config/cors.php`)

### What to do

The CORS config was already updated to read `APP_STAGING_URL` from env. Now set the variable on Railway:

```
APP_STAGING_URL=https://jawla-staging-staging.up.railway.app
```

Railway dashboard → jawla-staging service → Variables tab → Add variable.

### Verification

1. Open staging URL in browser
2. Open DevTools → Network tab
3. Perform any action that triggers an API call (e.g., navigate to a page)
4. Confirm no CORS errors in console
5. Confirm requests return 200, not 403

### Expected score gain: 15-20 points

---

## FIX-003 — Document backup/restore process

**Priority:** P1
**Resolves:** DEP-001 (no verified backup/restore process)
**Risk:** low
**Behavior change:** none

### What to do

Create `docs/BACKUP_RESTORE.md` with:

1. **Railway automatic backups** — Railway provides daily automatic backups for PostgreSQL services. Document where to find them (Railway dashboard → Postgres service → Backups tab).

2. **Manual backup command:**

   ```bash
   railway run pg_dump $DATABASE_URL > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

3. **Restore command:**

   ```bash
   psql $DATABASE_URL < backup_20260803_120000.sql
   ```

4. **What's backed up:** All tables (users, customers, products, invoices, payments, visits, stock movements, etc.)

5. **What's NOT backed up:** File uploads on local `public` disk (photos stored in `storage/app/public`). If using S3, those are separately managed by AWS.

6. **Recovery time objective (RTO):** < 1 hour for full restore from backup.

7. **Recovery point objective (RPO):** 24 hours (daily backups).

### Verification

- Run `railway run pg_dump $DATABASE_URL > /tmp/test_backup.sql` and confirm it succeeds
- Restore to a scratch database and verify table counts match

### Expected score gain: 10-15 points

---

## FIX-004 — Configure Sentry error tracking

**Priority:** P2
**Resolves:** OBS-001 (Sentry DSN empty — no error tracking)
**Risk:** low
**Behavior change:** none

### What to do

1. Create a Sentry project at sentry.io (free tier: 5K errors/month)
2. Copy the DSN
3. Set on Railway (both staging and production):
   ```
   SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project-id
   SENTRY_ENVIRONMENT=staging
   SENTRY_RELEASE=1.0.0
   ```

The Sentry config at `config/sentry.php` is already fully configured with scrubbing, breadcrumbs, and tracing. Just set the DSN.

### Verification

1. After setting DSN, trigger a test error: visit `/nonexistent-route-xyz`
2. Check Sentry dashboard for the error event
3. Confirm PII is scrubbed (SentryScrubber at `app/Support/SentryScrubber.php`)

### Expected score gain: 5-10 points

---

## FIX-005 — Set up monitoring and alerting

**Priority:** P2
**Resolves:** OBS-002 (no monitoring or alerting)
**Risk:** low
**Behavior change:** none

### What to do

1. **Railway health check** — Railway dashboard → jawla-staging service → Settings → Healthcheck path: `/up`

2. **Railway alerts** — Railway dashboard → Project Settings → Notifications → Enable deploy notifications and failure alerts.

3. **Uptime monitoring (free)** — Use UptimeRobot (free tier: 50 monitors):
   - Add monitor: `https://jawla-staging-staging.up.railway.app/up`
   - Check interval: 5 minutes
   - Alert via email + Slack/webhook

4. **What the health endpoint checks** (`routes/system.php` → `SystemPageController@health`):
   - Database connectivity
   - Cache connectivity
   - Returns JSON with `db_ok` and `cache_ok` booleans

### Verification

1. Visit `/up` — should return 200 with JSON
2. Stop the Railway service → confirm UptimeRobot detects downtime
3. Restart → confirm recovery alert

### Expected score gain: 5-10 points

---

## FIX-006 — Run Lighthouse audit

**Priority:** P3
**Resolves:** PERF-001 (no Lighthouse data)
**Risk:** none (read-only audit)
**Behavior change:** none

### What to do

After FIX-001 and FIX-002 are applied and the staging app is accessible:

1. Install Lighthouse CLI:

   ```bash
   npm install -g lighthouse
   ```

2. Run against staging:

   ```bash
   lighthouse https://jawla-staging-staging.up.railway.app/app \
     --output=html \
     --output-path=./docs/lighthouse-report.html \
     --chrome-flags="--headless"
   ```

3. Review scores for:
   - Performance (target: ≥ 90)
   - Accessibility (target: ≥ 90)
   - Best Practices (target: ≥ 90)
   - SEO (target: ≥ 90)
   - PWA (target: all checks pass)

4. If any score is below 90, file issues for remediation.

### Verification

- Lighthouse HTML report exists at `docs/lighthouse-report.html`
- All 4 category scores ≥ 90

### Expected score gain: 5-10 points

---

## FIX-007 — Run axe-core accessibility audit

**Priority:** P3
**Resolves:** A11Y-001 (no a11y testing)
**Risk:** none (read-only audit)
**Behavior change:** none

### What to do

1. Install axe-core CLI:

   ```bash
   npm install -g @axe-core/cli
   ```

2. Run against key pages:

   ```bash
   axe https://jawla-staging-staging.up.railway.app/app --save docs/axe-report-app.json
   axe https://jawla-staging-staging.up.railway.app/admin --save docs/axe-report-admin.json
   ```

3. Review violations:
   - **Critical:** Must fix before production
   - **Serious:** Should fix before production
   - **Moderate/Low:** Track for later

4. Common RTL accessibility issues to watch:
   - `dir` attribute consistency
   - Logical CSS properties (margin-inline-start vs margin-left)
   - Focus order in RTL layout
   - Icon mirroring

### Verification

- axe reports exist at `docs/axe-report-*.json`
- 0 critical violations
- Serious violations documented with remediation plan

### Expected score gain: 3-5 points

---

## Execution order

```
FIX-001 (P0)  ──┐
                 ├──► FIX-002 (P0) ──► FIX-006 (P3) ──► FIX-007 (P3)
FIX-003 (P1) ──┘         │
                          │
FIX-004 (P2) ─────────────┤
                          │
FIX-005 (P2) ─────────────┘
```

**Parallelizable:**

- FIX-001 + FIX-002 can be done simultaneously (different env vars)
- FIX-003 can be done anytime (documentation)
- FIX-004 + FIX-005 can be done simultaneously (different services)
- FIX-006 + FIX-007 require staging to be accessible (after FIX-001 + FIX-002)

**Total effort:** ~30 minutes for P0 fixes, ~1 hour for all fixes.

---

## Post-fix expected score

| Category                            | Current | After fixes |     Change |
| ----------------------------------- | ------: | ----------: | ---------: |
| Security and privacy                |     165 |         175 |        +10 |
| Reliability and data integrity      |     100 |         110 |        +10 |
| Deployment and environment safety   |      60 |          70 |        +10 |
| Observability, backup, and recovery |      30 |          45 |        +15 |
| Performance and scalability         |      65 |          75 |        +10 |
| Accessibility and UX resilience     |      30 |          35 |         +5 |
| **Total**                           | **835** | **885-920** | **+50-85** |

**Projected new band:** Conditional production candidate (850-899) or Strong production candidate (900+)
