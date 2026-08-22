# RISKS: Production Readiness

**Updated:** 2026-08-17 (post-audit, new Railway account)

---

## Risk Summary

| Category       | Count  | Critical | High  | Medium | Low   |
| -------------- | ------ | -------- | ----- | ------ | ----- |
| Deployment     | 2      | 0        | 1     | 1      | 0     |
| Integration    | 1      | 1        | 0     | 0      | 0     |
| Data Integrity | 1      | 1        | 0     | 0      | 0     |
| Testing        | 2      | 0        | 1     | 1      | 0     |
| Security       | 2      | 0        | 0     | 1      | 1     |
| Operational    | 3      | 0        | 1     | 1      | 1     |
| **Total**      | **11** | **2**    | **3** | **4**  | **2** |

---

## Critical Risks

### R1: No Database Backup Strategy

**Category:** Data Integrity
**Severity:** Critical
**Likelihood:** Low (Postgres volumes are durable, but corruption/deletion is possible)
**Impact:** All business data lost permanently

**Evidence:** No backup config in Railway, no pg_dump cron, no S3 backup storage.

**Mitigation:** FIX-002 — configure automated backup (Railway PITR or pg_dump).

**Resolution:** Milestone 2 of remediation plan.

---

### R2: ETA E-Invoicing Not Production Ready

**Category:** Integration
**Severity:** Critical
**Likelihood:** Medium
**Impact:** Egyptian tax compliance failure

**Evidence:** `AppServiceProvider.php:87` notes "last go-live gate". `UnsignedEtaSigner` is default. No sandbox test results.

**Mitigation:** Not in remediation scope (v1.1+). Accepted risk for beta.

**Resolution:** Future task — ETA sandbox testing required before production invoicing.

---

## High Risks

### R3: No E2E Test Execution

**Category:** Testing
**Severity:** High
**Likelihood:** Medium
**Impact:** Critical user journeys unverified

**Evidence:** `make test-e2e` skips on Windows, no CI pipeline.

**Mitigation:** FIX-001 (CI workflow) + FIX-007 (offline tests).

**Resolution:** Milestones 1 and 5 of remediation plan.

---

### R4: No CI/CD Pipeline

**Category:** Deployment
**Severity:** High
**Likelihood:** Medium
**Impact:** Manual deploys, no quality gate

**Evidence:** No `.github/workflows/` directory.

**Mitigation:** FIX-001 (CI) + FIX-003 (deploy pipeline).

**Resolution:** Milestones 1 and 3 of remediation plan.

---

### R5: Queue Worker Monitoring

**Category:** Operational
**Severity:** High
**Likelihood:** Medium
**Impact:** Silent failures in background processing

**Evidence:** Queue worker configured, no alerting for failed jobs.

**Mitigation:** Configure failed job logging + Sentry alerts.

**Resolution:** Future task (not in remediation scope).

---

## Medium Risks

### R6: PHPStan Level 0 Only

**Category:** Testing
**Severity:** Medium
**Likelihood:** Medium
**Impact:** Type safety gaps

**Evidence:** `phpstan.neon` level=0.

**Mitigation:** FIX-005 — incrementally raise to level 1.

**Resolution:** Milestone 4 of remediation plan.

---

### R7: CSP unsafe-inline/eval

**Category:** Security
**Severity:** Medium
**Likelihood:** Low
**Impact:** XSS mitigation weakened

**Evidence:** Required by Livewire/Alpine. Documented as accepted risk.

**Mitigation:** Accept risk. Migrate to nonce-based CSP when Livewire v4 adds support.

**Resolution:** Accepted for now. Revisit when Livewire v4 lands.

---

### R8: X-Powered-By Leaks PHP Version

**Category:** Security
**Severity:** Medium
**Likelihood:** Low
**Impact:** Aids attacker reconnaissance

**Evidence:** Response header: `PHP/8.3.32`.

**Mitigation:** FIX-006 — remove header.

**Resolution:** Milestone 1 of remediation plan.

---

### R9: nanoid Vulnerability

**Category:** Security
**Severity:** Medium
**Likelihood:** Low
**Impact:** DoS via zero-size generators

**Evidence:** `npm audit` → nanoid <3.3.18.

**Mitigation:** FIX-004 — `npm audit fix`.

**Resolution:** Milestone 1 of remediation plan.

---

## Low Risks

### R10: Log Retention Policy

**Category:** Operational
**Severity:** Low
**Likelihood:** Medium
**Impact:** Logs grow unbounded or deleted too fast

**Mitigation:** Define retention policy, configure rotation.

**Resolution:** Future task.

---

### R11: Documentation Staleness

**Category:** Operational
**Severity:** Low
**Likelihood:** Medium
**Impact:** Developer confusion

**Mitigation:** Review critical docs, update outdated content.

**Resolution:** Ongoing.

---

## Resolved Risks (from previous audit)

| Risk                                         | Status       | Resolution                                                                                |
| -------------------------------------------- | ------------ | ----------------------------------------------------------------------------------------- |
| R1 (old): Production deployment not verified | **Resolved** | Migrated to new Railway account, health check passes at `jawla-production.up.railway.app` |
| R4 (old): Security audit not performed       | **Resolved** | PWA Production-Readiness Audit completed, score 700/1000                                  |

---

## Risk Matrix

```
Impact ↑
│
│  R1  R2
│  R3  R4  R5
│  R6  R7  R8  R9
│  R10 R11
└──────────────────→ Likelihood
     Low  Med  High
```
