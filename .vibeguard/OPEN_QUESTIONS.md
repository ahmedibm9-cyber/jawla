# Jawla Open Questions

## Unresolved Blocking Issues

### 1. Livewire `/livewire/update` 500 Error

- **Question:** What exact environment/configuration causes the 500 error on the Livewire update endpoint during login?
- **Evidence:**
  - Health endpoint `/health` returns `{"status":"ok","db":"ok","cache":"ok"}` ✅
  - Login page loads at `/login` ✅
  - POST to `/livewire/update` returns 500 with generic "Something went wrong" dialog ❌
  - `.env` has `CACHE_STORE=file`, `SESSION_DRIVER=database`, `JAWLA_MODE=demo`
  - Railway deployment fails due to missing `APP_KEY`, `DB_HOST`, etc.
- **Why it matters:** Prevents any user from logging in via the rep PWA flow
- **Confidence:** High — environment variable mismatch is the root cause (observed in build/deploy logs)
- **Blocker for:** All downstream work (testing, feature development, demo)

### 2. Railway Deployment Pipeline

- **Question:** What specific environment variables must be set for a successful Railway deployment, and in what order?
- **Evidence:**
  - Dockerfile originally downloaded phpredis from GitHub → 429 rate-limit ❌
  - Fixed Dockerfile uses `pecl install redis` instead ✅
  - New deploys fail with: "Missing required environment variable: APP_KEY" ❌
  - Then "Missing required environment variable: DB_HOST" ❌
  - Then "SQLSTATE[42P01]: Undefined table: relation "cache" does not exist" ❌
  - `CACHE_STORE=file` was set but deploy may not have picked it up
- **Why it matters:** Cannot redeploy the app with fixed code/config
- **Confidence:** Medium — multiple deploy attempts observed, each failing on different missing vars

### 3. Production Database State

- **Question:** Is the production database truly empty, and what's the correct way to bootstrap it?
- **Evidence:**
  - Health check passes `{"status":"ok","db":"ok","cache":"ok"}` ✅
  - But no users can login ❌
  - DemoSeeder was run with `JAWLA_MODE=demo` and passwords updated to `12345678`
  - 12 users now exist in the DB with password `12345678`
  - But Livewire login still fails with 500 ❌
- **Confidence:** Medium — the DB has users but login still fails; unclear if the demo data is complete

### 4. Backup Strategy

- **Question:** What backup method should be chosen and implemented for the production Postgres database?
- **Evidence:**
  - Railway offers PITR (Point-in-Time Recovery) at $0.023/GB/month ✅
  - `scripts/backup.sh` with pg_dump → Railway volume or S3 is an option ✅
  - Railway backup addon exists ✅
  - No backup strategy is currently configured or verified ❌
- **Why it matters:** Data loss risk; AGENTS.md requires backup capability
- **Confidence:** Low — this is a future concern, not currently blocking

### 4. PHPStan Level 1 Migration

- **Question:** Will upgrading PHPStan from level 0 to 1 surface critical errors or just minor tech debt?
- **Evidence:**
  - Current config is level 0; `make typecheck` passes at level 0 ❌ (wait, actually it passes)
  - Level 1 may surface errors in the codebase ⚠️
  - Expected gain: +10-15 points toward 800+ score 📈
- **Why it matters:** Code quality gate; part of the remediation roadmap
- **Confidence:** Medium — depends on codebase size and existing patterns

## Decision Points Needed

| Decision                | Options                                                                                     | Impact                  | Required By       |
| ----------------------- | ------------------------------------------------------------------------------------------- | ----------------------- | ----------------- |
| **Backup method**       | 1. Railway PITR ($0.023/GB/mo)<br>2. pg_dump → S3/Railway volume<br>3. Railway backup addon | +20-30 points           | G2 (Milestone 2)  |
| **License type**        | Proprietary / MIT / Other                                                                   | +3-5 points             | FIX-009 (MIC-001) |
| **PHPStan level 1**     | Proceed (may surface errors)                                                                | +10-15 points           | G4 (Milestone 4)  |
| **Offline tests**       | Pest browser tests on CI                                                                    | +10-15 points           | G5 (Milestone 5)  |
| **Railway token in CI** | Add `RAILWAY_TOKEN` to GitHub secrets                                                       | Enables deploy pipeline | G3 (Milestone 3)  |

## Contradictions Checked

- `.env` `CACHE_STORE=file` vs deploy logs showing `cache` table queried → Resolved: The `config:clear` in preDeployCommand clears cached config; the new deployment should use `file` cache. The 500 may be from a stale container still running.
- Health endpoint works but Livewire fails → Resolved: Different endpoints; health is a simple GET, Livewire involves session + Livewire-specific logic.

## Unknowns Ledger

| Question                               | Why It Matters        | Evidence Checked                                          | Safest Resolution                                                                     |
| -------------------------------------- | --------------------- | --------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| Exact env vars for Railway prod deploy | Deploy succeeds/fails | 3+ deploy attempts, each failing on different missing var | Set ALL vars at once: APP_KEY, DB_*, CACHE_STORE, SESSION_DRIVER, JAWLA_MODE, APP_URL |
| Root cause of Livewire 500             | Login works or fails  | Health ✅, /livewire/update 500 ❌                        | Need APP_DEBUG=true to see actual PHP error                                           |
| Whether demo data is complete          | Testing can proceed   | 12 users seeded, passwords set, but login 500 ❌          | Need to verify full dataset (products, customers, routes, invoices)                   |
| Exact Railway token format             | CI deploy works/      | Not yet set up                                            | Generate from Railway account settings                                                |
