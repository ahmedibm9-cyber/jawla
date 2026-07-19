# Jawla V1 Release Checklist

**Status: 92% Ready** _(corrected after code verification — see banner)_  
**Blocker Count: 1 P0 Issue** _(purchase dual review completion; blockers #1–#3 verified already fixed)_  
**Time to Release: 3-5 Business Days**  
**Last Updated: 2026-07-20 (verification pass)**

> **⚡ VERIFICATION UPDATE 2026-07-20:** Direct code verification found blockers #1 (stock import), #2 (confirmation modals), and #3 (notification bell) were **already fixed** before this checklist was written — their sections below are kept for the acceptance criteria record and marked RESOLVED. Blocker #4 remains, rescoped to: PO generation, rep outcome notifications, D-04 resubmission + expiry. Test suite verified at **160/160 passing (501 assertions) on PostgreSQL**, not 60/65.

---

## Critical Path: Must-Fix Before UAT

### ✅ What's ALREADY DONE (No Action Needed)

| Component                    | Status  | Evidence                                                                |
| ---------------------------- | ------- | ----------------------------------------------------------------------- |
| Database schema & migrations | ✅ 100% | 24 migrations, all tables present                                       |
| Core models & relationships  | ✅ 100% | 30+ models with factories, StockService working                         |
| Authentication & roles       | ✅ 100% | Argon2id, rate limiting, 7 roles, 50+ permissions tested                |
| 15+ Filament admin resources | ✅ 95%  | Companies, Users, Products, Routes, Customers, Stock, Invoices, etc.    |
| Rep PWA (16 Livewire pages)  | ✅ 92%  | Home, Visits, Customers, Orders, Sales, Collections, Returns, Expenses  |
| Geofencing (500m blocking)   | ✅ 100% | GPS required, 500m radius enforced, out-of-range alerts manager         |
| Atomic invoicing             | ✅ 100% | DB::transaction wrapping, VAT calculation, sequential numbering         |
| Collections & cash box       | ✅ 100% | Cash/cheque/transfer, balance updates, reversals                        |
| Accessibility (WCAG 2.1 AA)  | ✅ 95%  | 180+ improvements, RTL/LTR, keyboard, focus, skeleton states            |
| Offline drafts               | ✅ 95%  | localStorage autosave, service worker shell, graceful offline indicator |
| 60+ passing tests            | ✅ 100% | Auth, roles, stock, invoice, alarms, payments                           |

---

## Critical Blockers: MUST FIX (4 Issues)

### ✅ Blocker #1: Stock Import — **RESOLVED (verified 2026-07-20)**

All acceptance criteria below verified met in code: `spatie/simple-excel` reader, company+warehouse scoping, `StockService::reconcile()` per row inside `DB::transaction()`, movements written, no `is_reserved` references.

**What was wrong (historical):**

- References `maatwebsite/excel` (not installed); project has `spatie/simple-excel`
- No tenant company scoping on import
- No stock_movements audit trail created on import
- `is_reserved` column doesn't exist but is referenced

**Acceptance Criteria:**

- [ ] Import uses `spatie/simple-excel`
- [ ] Company + warehouse scoped validation
- [ ] Each row calls `StockService::reconcile()` inside DB::transaction()
- [ ] stock_movements created for each imported row
- [ ] Tests pass: multi-company isolation, validation, idempotency

**Blocks:** B2 gate, B5 (invoices need loadable stock), B8 gate

---

### ✅ Blocker #2: Confirmation Modals — **RESOLVED (verified 2026-07-20)**

All 4 pages (plus complaint and quotation-flow) verified to gate their mutating action behind an `<x-ds.modal>` confirm with bilingual consequence text.

**What was wrong (historical):**

- 4 financial pages have NO confirmation modals:
  - Sales Flow (most critical: invoice creation)
  - Collect Payment (cash box + customer balance)
  - Log Return (stock + balance mutation)
  - Log Expense (cash box deduction)

**Acceptance Criteria:**

- [ ] `<x-ds-modal>` confirmation dialog added to 4 pages
- [ ] Bilingual titles + consequence messages
- [ ] Prevents accidental financial mutations

**Blocks:** B5 gate, Beta DoD safety requirement

**Examples:**

```
Sales Flow confirmation:
"Create Invoice? (Title)
You will sell X items for EGP Y. Your van stock will decrease."

Collect Payment confirmation:
"Collect Payment? (Title)
EGP X will be added to your cash box. Customer balance will decrease."
```

---

### ✅ Blocker #3: Rep Notification Bell — **RESOLVED (verified 2026-07-20)**

Bell + unread badge in rep header, `/app/notifications` Livewire page, `notifications` migration, and all sender hooks (quotation priced, customer approve/reject, complaint resolved) verified wired.

**What was wrong (historical):**

- Reps never learn quotation, customer approval, or complaint outcomes
- No bell icon in rep home
- No notifications page
- No database notifications being sent to reps

**Acceptance Criteria:**

- [ ] Bell icon + unread count in home header
- [ ] `/app/notifications` page (Livewire component, paginated)
- [ ] Notifiable hooks in 4 approval points:
  - PriceQuotationRequestResource::setPrice() → notify rep
  - CustomerResource::approve() → notify rep
  - CustomerResource::reject() → notify rep + reason
  - ComplaintService::resolve() → notify rep + resolution
- [ ] Mark notification read/unread
- [ ] Tests: 6-8 tests covering notification creation, read state, outcomes

**Blocks:** B6 gate, REQ-CRM-1 requirement

---

### 🔴 Blocker #4: Purchase Dual Review Incomplete (P0) — **HOURS: 6-8** _(rescoped 2026-07-20 — the only remaining P0)_

**What's Wrong (verified):**

- B7 phase incomplete
- Rep can submit offer; dual review **exists**: sales_approve/reject + purchasing_approve/reject with role guards and Sales-first status gating (`PurchaseRequestResource.php:97-148`)
- **MISSING:** Purchase Order (PO) generation on purchasing approval
- **MISSING:** Feedback loop to rep (no notifications on any decision)
- **MISSING:** D-04 resubmission loop (rejected offers editable + resubmittable) and rep-set expiry date
- **MISSING:** Service-layer extraction (decisions are inline in the Filament resource) + reason capture on reject

**Acceptance Criteria (B7-01 → B7-03):**

- [ ] Purchasing decision logic in `PurchaseRequestService`
- [ ] Decision history: actor, timestamp, reason
- [ ] PO generation from approved offers
- [ ] Notification: Rep learns PO created or rejected
- [ ] Tests: 8-12 tests covering ordering, veto, PO generation, notifications

**Blocks:** B7 phase gate, B8 gate

**Notes:** Decision D-04 signed off (Sales first → Purchasing; veto stays offer open for renegotiation)

---

## High-Priority Tests: MUST WRITE (2 Suites)

### 🔴 E2E Browser Tests (Playwright) — **HOURS: 8-10**

**What's Missing:**

- Rep full day flow walk-through (start work → visit → sell → collect → end day)
- Admin master data flow (create company → routes → customers → products → load van)
- Offline draft survival (kill app → reopen → verify draft restored)
- RTL/LTR bilingual smoke test on mobile

**Acceptance Criteria:**

- [ ] 8-12 Playwright tests written
- [ ] All tests pass on PostgreSQL (not SQLite)
- [ ] Rep day walkthrough (5 visits) passes
- [ ] Admin setup walkthrough (company + master data) passes
- [ ] Offline draft recovery: localStorage draft survives app close/reopen

**Blocks:** B8 gate certification

---

### 🔴 UAT Deployment Rehearsal — **HOURS: 4-6**

**What's Missing:**

- Fresh migration on UAT database not tested
- Backup creation + restore not rehearsed
- Rollback plan not documented

**Acceptance Criteria:**

- [ ] Fresh `migrate:fresh --seed` on PostgreSQL UAT database
- [ ] Backup created, encrypted, verified
- [ ] Restore backup to clean database (proves it works)
- [ ] Rollback plan documented (app + DB rollback steps)
- [ ] Health endpoint + monitoring verified
- [ ] Environment variables all correct (no hardcoded secrets)

**Blocks:** B8 gate, production deployment sign-off

---

## Nice-to-Have Improvements (Not Blocking)

| Issue                                         | Hours | Benefit                         | Priority |
| --------------------------------------------- | ----- | ------------------------------- | -------- |
| Native selects → autocomplete (mobile UX)     | 3-4   | Better mobile UX on 4 pages     | P2       |
| DS component adoption (card, button, tooltip) | 4-5   | Code consistency, maintenance   | P2       |
| Browser/Device QA screenshots                 | 4-6   | Visual sign-off matrix          | P1       |
| Full configuration audit                      | 1-2   | Secrets removal, env validation | P1       |

---

## Time Budget

| Work Stream                    | Hours     | Days    | Critical? |
| ------------------------------ | --------- | ------- | --------- |
| **P0 Blockers (4 issues)**     | 15-20     | 2       | YES       |
| **Test Suites (E2E + Deploy)** | 12-16     | 2-3     | YES       |
| **Polish (Autocomplete + DS)** | 7-9       | 1       | NO        |
| **TOTAL**                      | **34-45** | **5-6** | —         |

**If started today:** V1 production-ready by end of week (with 1-day UAT buffer).

---

## Go/No-Go Checklist (Before Deploying to UAT)

### Technical Ready?

- [ ] All 4 P0 blockers fixed + tested
- [ ] Stock import: works with real CSV, creates movements, multi-company safe
- [ ] Confirmation modals: on all 8 financial pages
- [ ] Rep notification bell: tested end-to-end
- [ ] Purchase dual review: complete with PO generation
- [ ] E2E tests: 8+ pass on PostgreSQL
- [ ] UAT deployment: fresh migrate + restore rehearsed
- [ ] No secrets in `.env.example` or production `.env`
- [ ] No P0/P1 visual bugs on mobile/desktop AR/EN

### Product Ready?

- [ ] Beta DoD 21-step walkthrough passes all steps
- [ ] Geofencing: 500m blocking works (GPS required)
- [ ] Invoicing: atomic, VAT correct, sequential numbers
- [ ] Stock: no oversells, movements logged
- [ ] Collections: cash/cheque/transfer working
- [ ] Alarms: out-of-stock broadcasts to 3 roles
- [ ] Complaints: submitted, acknowledged, resolved
- [ ] Dashboard: visits, quotations, alarms, sales today

### Operations Ready?

- [ ] Railway deployment tested (fresh boot)
- [ ] Backup automation configured + restore rehearsed
- [ ] Health endpoint + monitoring set up
- [ ] CI/CD pipeline operational
- [ ] Rollback procedure documented

### Compliance Ready?

- [ ] ZATCA Phase 2 gating in place (demo invoices only, real invoicing blocked)
- [ ] ETA compliance will be enforced before production invoicing
- [ ] This is demo/UAT release, not production

---

## Current Metrics

| Metric                  | Value                                                      | Target     |
| ----------------------- | ---------------------------------------------------------- | ---------- |
| **Phase Gates Passing** | 6/10 (R, B0, B1, B3, B4, B5)                               | 10/10      |
| **Tests Passing**       | 160/160 (501 assertions, PostgreSQL) — verified 2026-07-20 | keep green |
| **Code Coverage**       | ~85%                                                       | 90%+       |
| **Security Audit**      | Green (Argon2id, HTTPS, rate limit, no shells)             | Green      |
| **Accessibility**       | WCAG 2.1 AA (180+ improvements)                            | AA         |
| **Performance**         | Admin < 2s, searches < 1s, LCP < 2s                        | < 2s       |
| **Uptime**              | N/A (not production yet)                                   | 99.9%      |

---

## Day-by-Day Plan

### **Day 1 (Today)**

- [ ] Start stock import fix (spatie/simple-excel replacement, tenant scoping, movements)
- [ ] Start confirmation modals (Sales Flow, Collect, Return, Expense)
- **Target:** 8 hours progress on Blockers #1 + #2

### **Day 2**

- [ ] Finish stock import tests + commit
- [ ] Finish confirmation modals + translations + commit
- [ ] Start Rep notification bell (bell icon, notifications page, hooks)
- **Target:** Blockers #1 + #2 complete, #3 half-done

### **Day 3**

- [ ] Finish Rep notification bell + tests + commit
- [ ] Start Purchase dual review (Purchasing decision, PO generation)
- **Target:** Blockers #1–3 complete

### **Day 4**

- [ ] Finish Purchase dual review + tests + commit
- [ ] Start E2E browser tests (Playwright, rep/admin walkthroughs)
- **Target:** All 4 P0 blockers complete; tests started

### **Day 5**

- [ ] Finish E2E tests (at least 8 passing on PostgreSQL)
- [ ] Start UAT deployment rehearsal (fresh migrate, backup, restore)
- **Target:** Tests passing; deployment rehearsed

### **Day 6**

- [ ] Finish deployment rehearsal + rollback plan
- [ ] Browser/device QA screenshots (visual sign-off)
- [ ] Final secrets audit
- **Target:** Production-ready, ready for UAT

### **Day 7+**

- [ ] UAT testing with client
- [ ] Defect triage + fixes (if any)
- [ ] Client sign-off
- **Target:** Beta release / UAT cutover

---

## Sign-Off Approvers

| Role               | Name       | Responsibility                       | Approval            |
| ------------------ | ---------- | ------------------------------------ | ------------------- |
| **Technical Lead** | GLM-5.2    | Fix all blockers, tests, deployment  | ✅ Pending blockers |
| **Product/Client** | —          | Beta DoD walkthrough, UAT acceptance | ⏳ After fixes      |
| **Operations**     | —          | Deployment, backup, rollback         | ⏳ After rehearsal  |
| **Quality**        | MiniMax M3 | Visual QA, device testing            | ⏳ After E2E        |

---

## Risk Hotspots

| Risk                                | Likelihood | Impact   | Mitigation                                 |
| ----------------------------------- | ---------- | -------- | ------------------------------------------ |
| Stock import still broken after fix | 20%        | HIGH     | Test with real CSV file before sign-off    |
| E2E tests fail on PostgreSQL        | 25%        | HIGH     | Run on actual PostgreSQL, not SQLite       |
| Secrets leak in `.env`              | 10%        | CRITICAL | Audit both example + production env        |
| Client finds critical gaps in UAT   | 70%        | MEDIUM   | Build 3-5 day UAT buffer; plan for defects |

---

## Success Criteria

**V1 is production-ready when:**

1. ✅ All 4 P0 blockers fixed + tested
2. ✅ E2E tests passing (8+) on PostgreSQL
3. ✅ UAT deployment rehearsed successfully
4. ✅ Beta DoD 21-step walkthrough passes
5. ✅ Client accepts release
6. ✅ No P0/P1 defects outstanding
7. ✅ ZATCA Phase 2 gating active (demo invoices only)

**Expected completion:** 2026-07-25 (end of week)

---

**Last Updated:** 2026-07-20  
**Prepared By:** Gap Analysis Team  
**Next Review:** After first 2 blockers complete (Day 2)
