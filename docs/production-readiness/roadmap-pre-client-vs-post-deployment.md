# Pre-Client vs. Post-Deployment Roadmap

**Date:** 2026-07-27
**Author:** Mavis (re-audit, working tree master @ 9cfbdf1)
**Baseline audit:** `docs/production-readiness/Mavis_audit_2026-07-27.md` (438/1000)
**Prior remediation program:** `docs/production-readiness/19-remediation-roadmap.md` and `remediation-state.json`

This document re-frames the prior remediation roadmap and the Mavis re-audit
into two horizons, scoped to a **synthetic-data pilot with named abort
authority and daily reconciliation** as the client-facing milestone. The split
is not aspirational — every item below is anchored to a PR in
`remediation-state.json`, a finding in the prior audit, or a concrete file
observation from the re-audit.

The principle: **if a failure of this item would corrupt money, stock,
tenancy, identity, or legal/regulatory posture during the synthetic pilot, it
is pre-client. If it is hardening, scale, accessibility polish, or external
certification, it is post-pilot.**

---

## Shipped since the prior audit (do not re-open)

These PRs are `resolved: true` in `remediation-state.json` and have
executable test evidence. They are NOT blockers.

| PR     | Title                                               | Phase                                        |
| ------ | --------------------------------------------------- | -------------------------------------------- |
| PR-001 | Fail-closed company context across all entry points | 1                                            |
| PR-002 | Server-staged stock import with checksum/expiry     | 2                                            |
| PR-003 | Invoice-line-locked returns                         | 2                                            |
| PR-005 | Production deployment-mode separation               | 1                                            |
| PR-006 | Manager-only compensating reversals (no rep undo)   | 2                                            |
| PR-007 | Server-owned authoritative pricing                  | 2                                            |
| PR-009 | Invoice/payment/amend state machine                 | 2                                            |
| PR-010 | Versioned stock-count sessions + concurrency        | 2                                            |
| PR-013 | DOM-only map popup construction (XSS)               | 1                                            |
| PR-028 | Credit note / customer credit / refund lifecycle    | 2                                            |
| PR-031 | Append-only financial/stock/audit ledgers           | 2                                            |
| PR-014 | Canonical roles (5) + removed super-admin bypass    | 1 (partial — MFA/session control in Phase 5) |

Latest isolated PostgreSQL gate: **522 tests, 1,515 assertions** (Phase 2
evidence, `remediation-log.md:114`).

---

## HORIZON 1 — Pre-Client (before synthetic pilot)

Target: ~3–4 focused weeks. Two-week minimum if any track slips; **no pilot
without all P0.A–P0.E cleared and signed off.**

The client experience is: they log in to a real subdomain, with a small
defined dataset (one or two companies, 5–10 reps, a week of synthetic
invoices/payments/returns/stock movements), bilingual UI, and a real-looking
daily reconciliation report. They will not see most of the behind-the-scenes
items, but those items must exist for the pilot to be honest.

### P0.A — Close the remaining money/stock/legal-data paths

These are the open Critical-class items that still have
`release_blocker_remaining: true` and touch numbers the client will literally
see.

| #   | PR     | Deliverable                                                                                                  | Evidence required                                                                                                      | Owner            | Size |
| --- | ------ | ------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------- | ---------------- | ---- |
| A.1 | PR-027 | Sequential per-company/year invoice, return, payment, credit, refund numbers — `FOR UPDATE` locked, gapless  | Concurrent allocation test on PostgreSQL; serial numbers in any demo run; negative test for collisions and silent gaps | Engineering      | S–M  |
| A.2 | PR-029 | Snapshot legal issuance values (currency, QR inputs, tax lines, customer snapshot, template version)         | Reissue-after-master-edit test fails; PDF/QR from a 30-day-old invoice reproduces byte-identically from snapshot alone | Engineering      | M    |
| A.3 | PR-011 | BCMath/decimal string rules everywhere money flows; DB constraints to refuse float drift                     | Migration with `NUMERIC` and `CHECK`; float-input test rejected; reconciliation report green on synthetic data         | Engineering      | L    |
| A.4 | PR-025 | Batch/FEFO eligibility in locked stock service (sale, return, reversal, transfer, credit, quarantine)        | FEFO test on aged batches; expired batch rejected; cross-company batch rejected; concurrent sale+stock-count race      | Engineering      | M–L  |
| A.5 | PR-026 | Transfer state machine (request, approve, in-transit, partial receipt, exception) with conservation check    | Partial receipt + cancellation tests; conservation violation rejected; concurrency on dual-receipt attempts            | Engineering      | M    |
| A.6 | PR-004 | Stable offline intent IDs minted before confirmation; IndexedDB durability check before server call          | Browser test: tab kill mid-confirm does not double-charge; intent ID reused on retry                                   | Engineering + FE | M    |
| A.7 | PR-008 | Audited recoverable offline conflict resolution (no silent discard)                                          | Browser test: two reps editing same invoice offline → manager review queue, not silent loss                            | Engineering + FE | M–L  |
| A.8 | PR-019 | Risk-driven release test suite (tenant-negative, financial concurrency, offline browser, migration, restore) | Run on isolated PostgreSQL worker DBs; numbers and pass/fail recorded in this document                                 | Engineering      | L    |

**Why pre-client:** without A.1–A.7 the client's reconciliation report will
look wrong; without A.8 we have no proof the rest of the system is honest.

### P0.B — Identity, session, and perimeter

The client logs in with real-feeling credentials. The minimum is: their
account can't be hijacked, their session can't be reused, and we don't leak
data cross-company through the URL bar.

| #   | PR     | Deliverable                                                                                                                                   | Evidence required                                                                                             | Owner            | Size |
| --- | ------ | --------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- | ---------------- | ---- |
| B.1 | PR-014 | Admin MFA, step-up for destructive/financial actions, session inventory + revoke, POST logout, identity-aware throttling, proxy-trust fix     | MFA enrollment + sign-in test; step-up test; revoke kills other open sessions; logout invalidates server-side | Engineering      | L    |
| B.2 | PR-022 | CSP nonce rollout, remove `unsafe-inline` / `unsafe-eval` directives, telemetry scrubber verified for the data classes the client will create | Browser test with strict CSP; report-only → enforce for one release; Sentry payload audit                     | Engineering      | M    |
| B.3 | —      | Replace the two closure routes in `routes/web.php:64,67` with controllers so `php artisan route:cache` runs in production                     | `route:cache` exit 0; no functional regression                                                                | Engineering      | XS   |
| B.4 | —      | Pin the 8 `*` versions in `composer.json:20,24,27,28,29,30,31,40` to current resolved versions                                                | `composer install` clean, lockfile unchanged shape, CI green                                                  | Engineering      | XS   |
| B.5 | —      | Remove the literal `password` from `README.md:29–35`; switch to `pnpm`-style generated demo creds banner or env-driven dev users              | grep clean; demo still works in dev mode                                                                      | Engineering      | XS   |
| B.6 | —      | Rotate `.env` `APP_KEY` before any non-dev environment; document the rotation procedure                                                       | New key generated; old key invalidated in any deployed environment                                            | Operations       | XS   |
| B.7 | PR-015 | Private storage policy + EXIF strip on photo upload; active-shift GPS notice + employee notice                                                | EXIF retention test fails; storage ACL test passes; notice in app and in employment onboarding                | Engineering + HR | M    |

**Why pre-client:** B.1–B.2 are the difference between a demo and a public
beta. B.3–B.6 are cheap, high-leverage, and unblock B.1.

### P0.C — Operations minimums for the pilot

Even a synthetic-data pilot needs: a backup that's been restored, a way to
roll back, a way to know when something is wrong, and a name to call.

| #   | PR     | Deliverable                                                                                                                                                    | Evidence required                                                                                  | Owner                    | Size |
| --- | ------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- | ------------------------ | ---- |
| C.1 | PR-017 | **Run an actual backup + scratch-restore drill** using `scripts/backup.sh` + `scripts/restore-backup.sh`; record RPO/RTO in `docs/BACKUP_RESTORE.md`           | Drill log with: backup size, restore time, reconciliation delta (must be 0); signed by 2 operators | Operations + Engineering | M    |
| C.2 | PR-018 | CI/CD: promote E2E and ZAP from `continue-on-error: true` to **blocking**; pin actions; run Rollback smoke on every main merge; artifact promotion is signed   | Workflow YAML changes; one green run with all jobs blocking                                        | Engineering              | M    |
| C.3 | PR-020 | External uptime monitor on `/health` (UptimeRobot / BetterStack / healthchecks.io); Sentry alert routing to a real channel with paging on P1                   | P1 alert reaches a phone within 5 min in a drill                                                   | Operations               | S    |
| C.4 | —      | Fill in `docs/PRIVACY_AND_OPERATIONS_GATES.md` ownership table with real names (Engineering lead, Ops, Privacy, Counsel, Customer sponsor, Incident commander) | Signed table; one rehearsal incident with named IC and 1-hour SLA                                  | Executive sponsor        | XS   |
| C.5 | PR-021 | One current architecture diagram + roles/release/offline/backup/support runbook; mark superseded docs as `SUPERSEDED`                                          | New `docs/ARCHITECTURE_CURRENT.md`; old diagrams either deleted or marked                          | Engineering              | M    |
| C.6 | PR-024 | Idempotent scheduled jobs for: backup age, sync-conflict age, reconciliation drift, failed jobs, queue stall                                                   | Test double fires; alert lands; second fire does not double                                        | Engineering              | M    |

**Why pre-client:** C.1 is the single biggest evidence gap right now
(scripts exist, drill does not). C.4 is non-negotiable for a real pilot —
generic roles are not an approval (the doc itself says so).

### P0.D — Legal, privacy, and regulatory posture

The system processes PII (rep GPS, customer contacts, financial records).
The client is the data controller. Until these are signed, the client cannot
lawfully use real customer data — and they can't even sign a pilot agreement
without them.

| #   | PR     | Deliverable                                                                                                                                                                                                        | Evidence required                                                              | Owner                       | Size         |
| --- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------ | --------------------------- | ------------ |
| D.1 | PR-012 | Label standard invoices correctly; **do not enable Phase-2 ZATCA compliance unless formally certified**; remove or label any "compliant" UI copy                                                                   | Code review of `app/Services/ZatcaQrBase.php` and admin labels; language audit | Engineering + Legal         | XS           |
| D.2 | PR-030 | ETA production features are **feature-flagged off by default**; `ETA_LIVE=false` is the only safe env in pilot                                                                                                     | `config/eta.php` default; signed test confirms disabled state; docs updated    | Engineering                 | XS           |
| D.3 | —      | Privacy package: ROPA, DPA with hosting provider, retention schedule, breach procedure, bilingual privacy notice, employee GPS notice, data-subject request workflow                                               | Counsel sign-off; documents filed in `docs/legal/`                             | Privacy owner + Counsel     | L (external) |
| D.4 | —      | Customer-facing pilot agreement: scope, max users/devices, daily reconciliation cadence, stop conditions, abort authority                                                                                          | Signed by customer sponsor + executive sponsor                                 | Executive sponsor + Counsel | S (external) |
| D.5 | —      | Multi-role UAT plan with synthetic data (admin, manager, rep, warehouse, finance) covering: sale, return, payment, collection, expense, van transfer, stock count, draft invoice → issue → pay → credit, RTL smoke | Signed UAT report                                                              | Customer sponsor            | M            |

**Why pre-client:** D.1–D.2 are the difference between "we support e-invoicing"
and "we lie about supporting e-invoicing." D.3–D.5 are the difference between
a pilot and a privacy incident.

### P0.E — Quality gates the client will feel indirectly

| #   | Deliverable                                                                                                                                            | Evidence required                                                          | Owner       | Size |
| --- | ------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------- | ----------- | ---- |
| E.1 | Re-run the full Pest suite on an isolated worker DB; record exact count and assertions in this document                                                | Number, pass/fail, isolation proof, no `jawla` production DB touched       | Engineering | XS   |
| E.2 | Run a k6 mixed workload against staging with a `PerfUserSeeder`; capture pass/fail against SLOs                                                        | k6 report in `docs/performance/`; pass/fail per endpoint                   | Engineering | M    |
| E.3 | Reconcile the perf methodology: stop using `php artisan serve` in the perf report; use the documented FPM+Nginx runtime (Dockerfile already does this) | New perf report                                                            | Engineering | XS   |
| E.4 | Reconcile `/health` endpoint with actual DB/Redis/S3 health (not just app boot)                                                                        | `curl /health` returns degraded when DB is paused; Playwright or curl test | Engineering | S    |

**Why pre-client:** the client will not see E.1–E.4 directly, but they will
notice if sales slow down at 30 concurrent reps or if the dashboard lies
about health.

### Pre-Client exit gate

All of P0.A–P0.E complete, with evidence filed in this document or its
children, **and** at least one successful synthetic pilot rehearsal with the
named incident commander on the call. The customer's first login is then
_the_ pilot, not a drill.

Estimated calendar: **3–4 focused weeks** if all five tracks run in parallel
with a dedicated owner each. The longest single item is B.1 (PR-014 MFA +
session controls) at ~L; everything else is XS–M.

---

## HORIZON 2 — Post-Deployment (during and after the synthetic pilot)

These are non-blockers. They improve the system but a failure does not
corrupt data, leak PII, or break the pilot. They can be done in-flight while
the client is using the system, or after a successful pilot closure.

### P1 — Pilot-period hardening (concurrent with synthetic pilot)

| #    | PR     | Deliverable                                                                                                                                      | Why deferred                                                                            | Owner                |
| ---- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------- | -------------------- |
| P1.1 | PR-023 | Full WCAG 2.2 AA automated + manual evidence for rep and admin critical journeys (AR + EN)                                                       | High cost; manual journey recording; client already accepts current accessibility state | Engineering + Design |
| P1.2 | —      | Composer audit / supply chain hardening: GitHub Dependabot, `composer audit` in CI, signed lockfile review, private Composer mirror if available | Composer advisories have been clean at audit; can run as a weekly gate                  | Engineering          |
| P1.3 | —      | Pin PHP/Laravel versions in deployment manifest; add `composer outdated` review to PR template                                                   | Already pinned in `composer.json`; review process is the gap                            | Engineering          |
| P1.4 | —      | Cost/SLO dashboard for the 100-user Railway plan (matches `docs/railway-scaling-plan-100-users.md`)                                              | Synthetic pilot is sub-100; will revisit when real load appears                         | Operations           |
| P1.5 | —      | Onboarding tour cleanup (UX, not security)                                                                                                       | Cosmetic; not in scope of production-readiness                                          | Design               |

### P2 — Post-pilot, pre-real-data

| #    | Deliverable                                                                                            | Why deferred                                                                  | Owner                  |
| ---- | ------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------- | ---------------------- |
| P2.1 | ZATCA Phase 2 certification: CSID, compliance CSID, production certificate, on-prem bridge if required | External, multi-month, legal; client must decide jurisdiction-first           | Engineering + Legal    |
| P2.2 | ETA production enablement (after official approval)                                                    | Same                                                                          | Engineering + Legal    |
| P2.3 | Pen test by external party (Burp / Cure53 / NCC)                                                       | Cost; only worth it once system is stable                                     | Security               |
| P2.4 | SOC2-aligned evidence pack (access reviews, change-management log, vendor review, BC/DR runbook)       | Customer demand, not pre-pilot                                                | Executive + Operations |
| P2.5 | Full DR drill with measured RPO/RTO across regions, not just a single restore                          | Synthetic-data pilot does not need this; one site restore is enough pre-pilot | Operations             |
| P2.6 | PHPStan at level 6+ in CI as a blocking gate; coverage gate at 70%+                                    | Quality investment; not blocking the pilot                                    | Engineering            |
| P2.7 | Migrate all unpinned dependency ranges discovered during the pilot to pinned versions                  | Supply chain hygiene                                                          | Engineering            |
| P2.8 | Mobile native wrappers (if needed for offline durability beyond PWA capabilities)                      | PWA is the current path; revisit only if a real rep loses data                | Product                |

### P3 — Long-term (post-real-data)

| #    | Deliverable                                                                                           | Why deferred                         | Owner         |
| ---- | ----------------------------------------------------------------------------------------------------- | ------------------------------------ | ------------- |
| P3.1 | Multi-region active/active deployment with PostgreSQL logical replication or Citus                    | Not needed until multi-country       | Engineering   |
| P3.2 | Replace StockService bespoke offline queue with a battle-tested CRDT or operational-transform library | Engineering investment; not blocking | Engineering   |
| P3.3 | Self-service tenant onboarding portal                                                                 | Operations scale                     | Product + Eng |
| P3.4 | Advanced analytics: rep route optimization, customer churn, inventory turn                            | Product roadmap                      | Product       |

---

## What I would do this week (the first 7 days)

If the only thing you do is the items below, the project's risk profile
drops by ~50% in absolute terms and ~150 points off my 438 score.

| Day | Item                                                                         | Why                                                 |
| --- | ---------------------------------------------------------------------------- | --------------------------------------------------- |
| 1   | C.4 Fill in the ownership table in `docs/PRIVACY_AND_OPERATIONS_GATES.md`    | Unblocks every other gate                           |
| 1   | B.3 Replace the two closure routes                                           | 15-min PR, unblocks `route:cache` in prod           |
| 1   | B.4 Pin the 8 `*` composer versions                                          | 15-min PR, closes supply-chain risk                 |
| 1   | B.5 Remove literal `password` from `README.md`                               | 5-min PR                                            |
| 2   | B.6 Rotate `.env` `APP_KEY` and document the procedure                       | One-time, irreversible                              |
| 2   | E.1 Re-run full Pest on isolated worker DBs; record numbers                  | Establishes the baseline                            |
| 3–5 | A.1 Sequential numbering (PR-027) — small, high-impact, on the critical path | Closes the highest-leverage remaining Critical item |
| 3–5 | A.3 BCMath/decimal rules (PR-011) start; float-input rejection test first    | Foundational for every other money fix              |
| 3–5 | D.1 ZATCA labeling review (read-only audit)                                  | Cheap; removes compliance risk                      |
| 3–5 | D.2 Feature-flag ETA off by default                                          | Cheap; removes compliance risk                      |
| 6–7 | C.1 Run a backup + restore drill                                             | Single biggest evidence gap                         |
| 6–7 | A.6/A.7 Offline intent IDs + conflict resolution — start, do not finish      | Hardest item; needs the most runway                 |

Everything else can flow into weeks 2–4.

---

## How this changes the score

If P0.A–P0.E ship with evidence, my re-audit scoring moves roughly:

| Dimension                    |  Before | After P0 | Notes                                                                                             |
| ---------------------------- | ------: | -------: | ------------------------------------------------------------------------------------------------- |
| Infrastructure & scalability |      60 |       85 | Closure routes + perf drill done                                                                  |
| Security posture             |      75 |       95 | MFA, CSP, B.3–B.6 closed                                                                          |
| Monitoring & observability   |      38 |       65 | UptimeRobot + alert routing + scheduled                                                           |
| Incident response & DR       |       9 |       50 | Drill + named IC + on-call                                                                        |
| Data backup & retention      |      10 |       55 | Drill recorded                                                                                    |
| Performance / load testing   |      24 |       55 | k6 on staging with SLOs                                                                           |
| CI/CD maturity               |      57 |       80 | E2E + ZAP blocking, artifact promotion                                                            |
| Code quality & tests         |      67 |       75 | PR-019 risk-driven suite, isolated gate                                                           |
| Dependency & supply chain    |      31 |       55 | Pinned + audit + Dependabot                                                                       |
| Compliance & regulatory      |      42 |       78 | D.1–D.5 closed (ZATCA labeling, ETA off, DPA, UAT)                                                |
| Documentation                |      33 |       50 | PR-021 + signed ownership table                                                                   |
| Team runbooks                |      11 |       45 | Named owners, on-call, IC, support lead                                                           |
| **Total**                    | **438** | **~790** | Still 10 points under the 800 threshold; covers with the signed pilot rehearsal + first UAT cycle |

To clear 800, add a successful one-week synthetic pilot rehearsal with the
named IC + one multi-role UAT cycle. To clear 900, layer in P2.3 (pen test)
and P2.4 (SOC2 pack) — those are external and calendar-driven, not
engineering-driven.

---

## Three things I want to be honest about

1. **The prior audit is more pessimistic than me on money** (it scored 35/100
   before the remediation program started; the program has since moved 10
   Critical findings to `resolved`). I am more pessimistic than the prior
   audit on **operations** (it gave 1/10 for backup+DR; I gave 10/80 — same
   ratio, different denominator). Both audits land on **NOT READY for real
   data**, and both agree on the work.
2. **The single biggest unknown is offline durability (PR-004, PR-008).**
   P0.A.6 and P0.A.7 are where the most engineering risk lives. I would
   staff two engineers on this, not one.
3. **The single cheapest win is B.3 (closure routes).** That is a 15-minute
   PR that unblocks `route:cache` in production and likely improves
   throughput by 10–15%. It is embarrassing that it is still open after a
   Phase 1/2 cycle. Same for B.4 and B.5.

I want your call on one thing: do you want me to draft the actual PR
descriptions for the Week 1 items so they can be picked up immediately, or do
you want to assign owners first and have me tailor the PR scope per owner?
