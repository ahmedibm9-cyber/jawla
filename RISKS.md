# Jawla Go-Live Risk Register

## Product Risks

| ID    | Risk                                                   | Likelihood | Impact   | Mitigation                                              | Owner         |
| ----- | ------------------------------------------------------ | ---------- | -------- | ------------------------------------------------------- | ------------- |
| P-001 | ETA no grace period — must have real credentials day 1 | Low        | Critical | Verify with client's accountant before go-live          | Developer     |
| P-002 | Rep reverts to notebook if app fails on day 1          | Medium     | High     | War room WhatsApp, visible sync status, quick bug fixes | Sales Manager |
| P-003 | Offline data loss destroys rep trust                   | Medium     | High     | Sync status indicator, manual offline test, queue cap   | Developer     |
| P-004 | Device registration wall blocks rep at 8 AM            | Low        | High     | Manual artisan registration for 8 reps                  | Developer     |

## Security Risks

| ID    | Risk                                         | Likelihood        | Impact | Mitigation                       | Owner     |
| ----- | -------------------------------------------- | ----------------- | ------ | -------------------------------- | --------- |
| S-001 | SESSION_ENCRYPT=false in production          | High (if not set) | Medium | Set in Railway dashboard Phase 1 | Developer |
| S-002 | APP_KEY compromised from git history         | Low               | High   | Rotate key before go-live        | Developer |
| S-003 | Demo mode accidentally enabled in production | Low               | Medium | Guard added (aborts with 500)    | Developer |

## Operational Risks

| ID    | Risk                                              | Likelihood              | Impact | Mitigation                               | Owner         |
| ----- | ------------------------------------------------- | ----------------------- | ------ | ---------------------------------------- | ------------- |
| O-001 | No incident owner — first outage has no responder | High (if not addressed) | High   | WhatsApp group + SUPPORT.md              | Sales Manager |
| O-002 | Backup restore fails on first attempt             | Medium                  | Medium | Run drill before go-live                 | Developer     |
| O-003 | PostgreSQL unstable on Windows dev machines       | Known                   | Low    | Tests run in CI (Linux)                  | Developer     |
| O-004 | Performance degrades under real load              | Low                     | Medium | Rate limiting + response time monitoring | Developer     |

## Delivery Risks

| ID    | Risk                                    | Likelihood | Impact | Mitigation                                                | Owner     |
| ----- | --------------------------------------- | ---------- | ------ | --------------------------------------------------------- | --------- |
| D-001 | ETA cert signer not ready Week 2        | Medium     | High   | Stub mode for launch week, batch-submit later             | Developer |
| D-002 | k6 load test reveals performance issues | Low        | Medium | Rate limiting is the guardrail; real usage data is better | Developer |
| D-003 | Automated backup not ready for launch   | Low        | Low    | Manual drill sufficient for V1                            | Developer |

## Accepted Risks (V1)

| Risk                                 | Acceptance Rationale                                   |
| ------------------------------------ | ------------------------------------------------------ |
| No PagerDuty/Opsgenie                | WhatsApp sufficient for 8 reps; formal tools for scale |
| No automated nightly backups         | Railway built-in snapshots + manual drill for V1       |
| No k6 load testing suite             | Rate limiting + real usage monitoring for 8 reps       |
| CSP allows unsafe-inline/unsafe-eval | Blocked by Livewire/Alpine; migration planned for v4   |
| Like wildcard injection in search    | Low impact (filter bypass only, not data exfiltration) |
