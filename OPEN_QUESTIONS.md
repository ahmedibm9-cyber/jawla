# Jawla — Open Questions

## High Priority

### 1. CI/Deploy Branch Mismatch

**Question:** Why does `ci.yml` trigger on `main` while `deploy.yml` triggers on `master`? The default branch is `master`.

**Evidence:**
- `.github/workflows/ci.yml:4-6` — triggers on `main`
- `.github/workflows/deploy.yml:4-5` — triggers on `master`
- `git branch --show-current` returns `master`

**Impact:** CI never runs on the branch that deploys. Lint and test checks are effectively dead for production pushes.

**Resolution options:**
- Change `ci.yml` to trigger on `master`
- Rename branch from `master` to `main`
- Add `master` to ci.yml branch list

### 2. Is Railway the Active Deployment?

**Question:** Is Railway currently hosting the production application, or is this stale configuration?

**Evidence:**
- `railway.toml` exists with 2 replicas, Redis, health check config
- `deploy.yml` targets Railway staging + production
- `README.md` mentions Railway, Render, and Laravel Forge

**Impact:** If Railway is inactive, deploy.yml is a no-op and backups may target wrong infrastructure.

**Resolution:** Check Railway dashboard or run `railway status` if CLI is available.

### 3. Are Closure Routes Blocking `route:cache`?

**Question:** Which closure routes in `routes/web.php` prevent `route:cache` from working?

**Evidence:**
- `docs/DEPLOYMENT.md:39` states: "route:cache is intentionally disabled until closure routes are removed"
- `routes/web.php` contains inline closures for some routes

**Impact:** Every request re-parses route definitions — performance penalty in production.

**Resolution:** Identify closure routes, convert to controller methods, enable `route:cache`.

## Medium Priority

### 4. What Is the Actual Production Database State?

**Question:** Is the production PostgreSQL database managed by Railway, or is there a separate database server?

**Evidence:**
- `railway.toml` references PostgreSQL via Railway's managed database
- `config/database.php` defaults to `pgsql` with env-sourced connection details
- No evidence of external database hosting

**Impact:** Backup scripts may target wrong database if configuration is stale.

### 5. Are Stress Test Results Used for Decisions?

**Question:** Are the static stress test result files in `tests/stress/` reviewed before deployments?

**Evidence:**
- 15+ timestamped `.txt` result files in `tests/stress/`
- No CI gate on stress test results
- k6 scripts exist but not in CI pipeline

**Impact:** Performance regressions may go undetected.

### 6. What Is the ETA Integration Status?

**Question:** Has the Egyptian Tax Authority integration ever been activated in production?

**Evidence:**
- `config/eta.php:12` — `enabled => false` (default)
- `NullEtaClient` is the default binding
- `UnsignedEtaSigner` is a stub (needs taxpayer certificate)
- Production readiness audit marked PR-030 as resolved with `ETA_ENABLED=false`

**Impact:** E-invoicing compliance may be required by Egyptian law. If so, this needs activation before go-live.

## Low Priority

### 7. Is the Service Worker Cache Version Manual?

**Question:** Who increments the `jawla-public-v6` cache name in `public/sw.js`?

**Evidence:**
- `public/sw.js:3` — `const CACHE_NAME = 'jawla-public-v6'`
- No automated version bumping in build scripts

**Impact:** Manual process; risk of stale caches if forgotten.

### 8. What Happens When `sync_receipts` Table Grows Large?

**Question:** Is there a retention policy for `sync_receipts`? The append-only design means rows are never deleted.

**Evidence:**
- `SyncReceipt` model uses `AppendOnly` concern
- No purge command for `sync_receipts` (unlike `LocationPurgeService` for `location_pings`)
- `LocationPurgeService` only purges `location_pings` table

**Impact:** Table will grow unbounded over time. May need periodic archival.

### 9. Are There Any Feature Flags Beyond ETA?

**Question:** Are there other feature flags in the codebase besides `ETA_ENABLED`?

**Evidence:**
- `config/jawla.php` has `mode` (production/demo)
- `config/eta.php` has `enabled`
- No feature flag service (e.g., LaunchDarkly, Envoyer) detected

**Impact:** Minimal — only 2 flags found. But worth confirming no hidden conditional logic.

## Resolved Questions (from Previous Exploration)

These were answered during the production readiness remediation:

- **PR-008 conflict handling:** Conflicts quarantined via null response on SyncReceipt, not separate columns
- **PR-018 CI existence:** `.github/workflows/ci.yml` exists (task agent initially reported it missing)
- **PR-022 CSP:** Deferred — Alpine.js requires `unsafe-eval`
- **PR-027 numbering:** Already gapless via NumberSequenceService
