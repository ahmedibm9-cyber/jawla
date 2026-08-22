# SPEC: Production Readiness Verification

## Specification Version

- **PRD Reference:** PRD.md v1.0
- **Date:** 2026-08-15
- **Status:** Active

---

## 1. Deployment Verification

### 1.1 Railway Deployment Check

**Actor:** DevOps / System Admin
**Precondition:** Railway account configured, `railway.toml` exists

**Action:**

1. Run `railway whoami` to verify authentication
2. Run `railway status` to check deployment status
3. Verify service is running and healthy

**Expected Result:**

- Railway CLI authenticated
- Service status shows "deployed" or "active"
- Health endpoint returns 200

**Data Changes:** None
**Permission Check:** Railway account access required

**Acceptance Criteria:**

- [ ] `railway whoami` returns authenticated user
- [ ] `railway status` shows service running
- [ ] `curl /health` returns HTTP 200
- [ ] Application URL accessible in browser

**Error States:**

- Not authenticated: Run `railway login`
- Service not found: Check project configuration
- Health check fails: Check application logs

---

### 1.2 Application Health Check

**Actor:** DevOps / System Admin
**Precondition:** Deployment verified (1.1)

**Action:**

1. Access `/health` endpoint
2. Verify response contains:
   - Database connection status
   - Cache connection status
   - Application version

**Expected Result:**

```json
{
  "status": "healthy",
  "database": "connected",
  "cache": "connected",
  "version": "1.0.0"
}
```

**Acceptance Criteria:**

- [ ] Health endpoint returns JSON
- [ ] All subsystems report "connected"
- [ ] No error messages in response

---

## 2. ETA E-Invoicing Verification

### 2.1 ETA Sandbox Configuration

**Actor:** Developer
**Precondition:** ETA sandbox credentials available

**Action:**

1. Check `config/eta.php` configuration
2. Verify sandbox base URLs are set
3. Verify client ID and secret are in `.env`

**Expected Result:**

- `eta.enabled` = true
- `eta.api_base_url` points to sandbox
- `eta.id_base_url` points to sandbox ID service

**Acceptance Criteria:**

- [ ] Configuration values present
- [ ] Sandbox URLs accessible
- [ ] No hardcoded credentials in code

**Files to Inspect:**

- `config/eta.php`
- `.env` (check variable names only, not values)
- `app/Services/Eta/HttpEtaClient.php`

---

### 2.2 ETA Test Transaction

**Actor:** Developer
**Precondition:** Sandbox configured (2.1)

**Action:**

1. Create test invoice via `InvoiceService::create()`
2. Submit to ETA via `EtaClient::submit()`
3. Verify response contains submission UUID

**Expected Result:**

- Invoice created with status "issued"
- ETA submission returns UUID
- Invoice updated with `eta_submission_uuid`

**Data Changes:**

- New invoice record
- Stock movement record
- ETA submission record

**Acceptance Criteria:**

- [ ] Invoice created successfully
- [ ] ETA submission returns valid UUID
- [ ] Invoice record updated with ETA fields
- [ ] Stock movement recorded

**Error States:**

- Network timeout: Retry with exponential backoff
- Invalid credentials: Check `.env` configuration
- Sandbox rejection: Verify test data format

---

## 3. Offline Sync Verification

### 3.1 Sync Endpoint Test

**Actor:** Developer / QA
**Precondition:** Application deployed, test user created

**Action:**

1. Login as test rep
2. Create invoice while online
3. Verify invoice synced to server

**Expected Result:**

- Invoice created locally
- Sync queue item created
- Sync completes successfully
- Server returns sync receipt

**Acceptance Criteria:**

- [ ] Invoice created in local storage
- [ ] Sync queue item created
- [ ] Sync completes without errors
- [ ] Server receipt received

**Files to Inspect:**

- `routes/rep-sync.php`
- `app/Services/Sync/SyncHandlerRegistry.php`
- `resources/js/offline-sync.js`

---

### 3.2 Offline → Online Transition

**Actor:** Developer / QA
**Precondition:** Sync endpoint working (3.1)

**Action:**

1. Login as test rep
2. Disable network (airplane mode)
3. Create 3 invoices while offline
4. Re-enable network
5. Trigger sync

**Expected Result:**

- 3 invoices created locally
- All 3 synced successfully
- No data loss
- Sync receipts returned for each

**Data Changes:**

- 3 local invoice records
- 3 server invoice records
- 3 stock movement records
- 3 sync receipts

**Acceptance Criteria:**

- [ ] All 3 invoices created locally
- [ ] All 3 synced to server
- [ ] No duplicate records
- [ ] Stock quantities correct
- [ ] Sync receipts received

**Error States:**

- Partial sync: Retry failed items only
- Conflict: Server wins, local adjusted
- Network failure: Queue for retry

---

## 4. Performance Baseline

### 4.1 k6 Load Test

**Actor:** Developer / DevOps
**Precondition:** Application deployed, k6 installed

**Action:**

1. Run `make test-perf`
2. Review test results
3. Document baseline metrics

**Expected Result:**

- Test completes without errors
- Response times within thresholds
- No server errors (5xx)

**Acceptance Criteria:**

- [ ] k6 test completes
- [ ] P95 response time < 2s
- [ ] Error rate < 1%
- [ ] Throughput > 50 req/s

**Files to Inspect:**

- `tests/k6/performance.spec.js`
- Test output report

---

## 5. Security Audit

### 5.1 Middleware Stack Verification

**Actor:** Developer / Security
**Precondition:** Application deployed

**Action:**

1. Test each middleware individually
2. Verify rate limiting works
3. Check security headers present

**Expected Result:**

- Login rate limit: 5/min per IP+email
- POST rate limit: 60/min per user
- Security headers present on all responses

**Acceptance Criteria:**

- [ ] Login rate limit enforced
- [ ] POST rate limit enforced
- [ ] X-Frame-Options present
- [ ] X-Content-Type-Options present
- [ ] Strict-Transport-Security present

**Files to Inspect:**

- `app/Http/Middleware/SecurityHeaders.php`
- `app/Http/Middleware/ThrottlePost.php`
- `app/Providers/AppServiceProvider.php` (rate limiters)

---

### 5.2 Authentication Flow Test

**Actor:** Developer / Security
**Precondition:** Middleware verified (5.1)

**Action:**

1. Test login with valid credentials
2. Test login with invalid credentials
3. Test session regeneration
4. Test logout

**Expected Result:**

- Valid login: Redirect to dashboard
- Invalid login: Error message, no session
- Session regenerated on login
- Logout destroys session

**Acceptance Criteria:**

- [ ] Valid login succeeds
- [ ] Invalid login fails gracefully
- [ ] Session ID changes on login
- [ ] Logout clears session
- [ ] Password hashed with argon2id

---

## Test Data Requirements

| Test        | Data Needed         | Source        |
| ----------- | ------------------- | ------------- |
| Deployment  | Railway account     | User provided |
| ETA         | Sandbox credentials | User provided |
| Offline     | Test rep account    | `make seed`   |
| Performance | Test dataset        | `make seed`   |
| Security    | Test user accounts  | `make seed`   |

## Evidence Collection

| Test        | Evidence              | Format          |
| ----------- | --------------------- | --------------- |
| Deployment  | Railway status output | Screenshot/text |
| ETA         | Submission UUID       | Log output      |
| Sync        | Sync receipts         | JSON response   |
| Performance | k6 report             | HTML/JSON       |
| Security    | Test results          | Checklist       |

## Rollback Procedures

| Scenario          | Rollback Action            |
| ----------------- | -------------------------- |
| Deployment fails  | `railway rollback`         |
| ETA test fails    | Disable ETA in config      |
| Sync fails        | Disable offline mode       |
| Performance fails | Scale up Railway resources |
| Security fails    | Patch middleware, redeploy |
