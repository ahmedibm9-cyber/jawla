# Investigation Case File: b8-demo-regression-completion

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — gap B8 from Phase Roadmap
**Severity:** Degraded UX / Missing functionality (blocks Beta B8 completion)
**Status:** Open
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-b8-demo-regression-completion-2026-07-19.md`

---

## Summary

**One-sentence description:**
The B8 phase (Demo of AM1→AM9 end-to-end + Regression on hard rules + states) has a service-level E2E test (`AMEndToEndTest`) but **zero browser/E2E tests** (Playwright/Dusk) and **no UI-level regression suite** for hard rules (no negative stock, sequential numbering, atomic invoice+stock+cash) or B0 UI states (skeletons, empty states, modals).

**Expected behavior:** Per PRD v1.1 §7 Definition of Beta Done and Build Guide §10:

- A phone-in-hand walkthrough reproducing the client's AM1→AM9 narrative in order, fully on the Rep PWA + Admin panel
- Regression on hard rules: no negative stock, sequential numbering per company, atomic invoice+stock+cash box, activity log with reverse
- Regression on B0 UI states: skeleton loaders, empty states with actions, consequence-stating modals, RTL/LTR flip

**Actual behavior:**

- `AMEndToEndTest.php` covers AM1→AM9 at **service/model layer only** (no browser interaction)
- **Zero Playwright/Dusk tests** exist
- **Zero UI regression tests** for hard rules or B0 states
- The `DemoSeeder` creates data but no automated walkthrough verifies the actual UI

**User / business impact:** B8 cannot be marked complete. The client's AM1→AM9 phone walkthrough (Definition of Beta Done) has no automated verification. Hard-rule regressions could ship undetected.

---

## Symptom Details

**Trigger conditions:** Structural — test infrastructure and regression coverage absent.

**Environments affected:** All (test infrastructure gap).

**First observed:** 2026-07-19 (phase roadmap audit)

**Frequency:** Constant (infrastructure gap)

**Reproducible:** Yes — run `php artisan test` and observe no browser tests run; run `npm test` or Playwright — not configured.

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed, reproducible, or corroborated by 2+ independent sources.
> - **[B] Probable** — single source, code-read inference, or intermittent.
> - **[C] Speculative** — pattern match or untested hypothesis only.

### Evidence Item 1: Service-layer E2E test exists but no browser test

**Grade:** [A]
**Source:** `tests/Feature/AMEndToEndTest.php`
**Description:** `AMEndToEndTest::test_am1_through_am9_full_narrative_reproduces()` runs against the seeded database and calls services/models directly (`Visit::create`, `VisitReport::create`, `PriceQuotationRequest::create`, `AlarmService::raise`, etc.). It uses `$this->actingAs($rep)` for auth but never visits a URL, never clicks a button, never fills a form.

**Verbatim excerpt:**

```php
// AM3: GPS-confirmed arrival (mock: visit is created + arrival confirmed)
$visit = Visit::create([...]);
$visit->update(['is_out_of_route' => false]);
$this->assertTrue($visit->arrival_confirmed, 'AM3: Arrival confirmed');
```

**Implications:** The test validates data integrity at the model/service layer but **cannot catch UI bugs**: broken buttons, missing modals, RTL breaks, skeleton loading failures, GPS permission flows, camera access, etc.

---

### Evidence Item 2: Zero Playwright/Dusk tests

**Grade:** [A]
**Source:** `glob "**/*.spec.{ts,js}"` → 0 files; `glob "**/Browser/*Test.php"` → 0 files; `playwright.config.js` not found
**Description:** No browser automation framework is configured. The project has no `playwright.config.js`, no `dusk.php`, no `tests/Browser` directory.

**Implications:** No CI pipeline can run browser tests. The AM1→AM9 walkthrough cannot be automated at the UI level.

---

### Evidence Item 3: No UI regression tests for hard rules

**Grade:** [A]
**Source:** `grep -rn "negative.*stock\|sequential.*number\|atomic.*invoice\|stock.*cash" tests/` → 0 results for UI tests
**Description:** The hard rules from CLAUDE.md §8 / Build Guide §8 are:

- No negative van stock (service enforces, but UI has no validation test)
- Sequential numbering per company (service enforces, but UI sequence display not tested)
- Atomic invoice+stock+cash box (service enforces, but no integration test verifying rollback on failure)
- Activity log with reverse/redo (service exists, but UI reverse action not tested)

**Implications:** A silent regression in any hard rule could reach production without detection.

---

### Evidence Item 4: No UI regression tests for B0 states

**Grade:** [A]
**Source:** `grep -rn "skeleton\|empty.*state\|modal.*confirm\|x-ds-" tests/` → 0 results
**Description:** B0 Design System mandates:

- Skeleton loaders on every list (`x-ds-skeleton`)
- Empty states with action (`x-ds-empty`)
- Consequence-stating modals (`x-ds-modal`) on all destructive/financial actions
- RTL/LTR flip on every page

**Implications:** B0 compliance cannot be verified automatically. Visual regressions (skeleton missing, empty state broken, modal missing) ship undetected.

---

### Evidence Item 5: DemoSeeder creates data but no walkthrough verification

**Grade:** [A]
**Source:** `database/seeders/DemoSeeder.php`
**Description:** The seeder creates all data for AM1→AM9 (5 visits, 5 customers, 1 pending customer, products, route, work session, etc.) but there is **no test that verifies the Rep can actually complete the walkthrough in the UI**:

- Rep logs in → sees 5 visits → starts work → GPS check-in → fills report → signs → submits
- Manager approves pending customer → sees alarm → approves
- Rep requests price → Manager sets 1000±100 → Rep enters 950 → confirms → creates proforma
- Rep shares proforma via WhatsApp → creates invoice → stock deducted → payment collected
- Rep flags Material 952 out-of-stock → alarm hits Finance+Manager+Executive
- Complaint logged → Manager resolves → Dashboard reflects

**Implications:** The "phone-in-hand walkthrough" is the **Definition of Beta Done** but has zero automated verification.

---

### Evidence Summary

| #   | Title                                 | Grade | Source             | Key Implication               |
| --- | ------------------------------------- | ----- | ------------------ | ----------------------------- |
| 1   | Service-layer E2E test only           | A     | AMEndToEndTest.php | Cannot catch UI bugs          |
| 2   | Zero Playwright/Dusk tests            | A     | glob search        | No UI automation possible     |
| 3   | No UI regression for hard rules       | A     | grep tests/        | Silent regression risk        |
| 4   | No UI regression for B0 states        | A     | grep tests/        | B0 compliance unverified      |
| 5   | DemoSeeder has no UI walkthrough test | A     | DemoSeeder.php     | Beta Done definition untested |

---

## Hypotheses

### Hypothesis 1 — Browser tests were deferred because service-layer test was considered "enough" [Plausibility: High]

**Statement:** The team wrote the service-layer E2E test and considered it sufficient for Beta, deferring browser automation to v1.0 or later.

**Supporting evidence:**

- Evidence 1 [A] — Service test exists and passes
- Evidence 2 [A] — Zero browser tests exist
- Build Guide §10 mentions "Pest + Laravel Dusk or Playwright (E2E)" but none implemented

**Contradicting evidence:** Build Guide §10 says "E2E: at minimum, rep day flow + admin master-data flow + RTL smoke" — this is explicitly required for Beta.

**Verification step:** Check CI config — if no browser test job, the requirement was deferred.

---

### Hypothesis 2 — Hard-rule UI regression tests were never written because services enforce them [Plausibility: Medium]

**Statement:** The team assumed service-layer enforcement (e.g., `StockService::decrement` throws on negative) is sufficient, so no UI test was written to verify the user sees the error message, modal, or rollback.

**Supporting evidence:**

- Evidence 3 [A] — no UI regression tests for hard rules
- Services do enforce rules (confirmed in `StockServiceTest`, `InvoiceFlowTest`)

**Contradicting evidence:** Build Guide §8 says "All money mutations happen inside DB::transaction via a Service" — but doesn't say UI doesn't need to show the error. The user must see the consequence.

**Verification step:** Check if `StockSearch` or `SalesFlow` UI shows the bilingual error from `StockService` when stock is insufficient.

---

### Hypothesis 3 — B0 UI states were built but never tested because no visual regression tool [Plausibility: High]

**Statement:** The `x-ds-skeleton`, `x-ds-empty`, `x-ds-modal` components exist and are used in some pages (e.g., Notifications page) but most pages don't use them. No visual regression tool (Playwright + pixelmatch, Percy, Chromatic) was configured, so no test catches missing skeletons/empty states.

**Supporting evidence:**

- Evidence 4 [A] — zero UI regression tests for B0 states
- Evidence 2 [A] — no Playwright/Dusk means no visual regression possible

**Contradicting evidence:** None.

**Verification step:** Run `grep -rn "x-ds-skeleton\|x-ds-empty\|x-ds-modal" resources/views/livewire/app/` — most pages show 0 usage.

---

### Hypothesis 4 — The AM1→AM9 walkthrough was tested manually once and considered "done" [Plausibility: Medium]

**Statement:** A human walked through the AM1→AM9 flow in the browser, it worked, so no automated test was written.

**Supporting evidence:**

- Evidence 5 [A] — DemoSeeder creates perfect data but no test uses it for UI walkthrough
- The `AMEndToEndTest` is the only E2E test

**Contradicting evidence:** Build Guide §7 says "After every phase: run tests, print a summary, then commit" — but B8 is the demo phase, so tests should exist before B8 commit.

**Verification step:** Check git history for when `AMEndToEndTest` was added vs `DemoSeeder`.

---

## Suspected Components

### Component: E2E Test Infrastructure (Missing)

| Attribute              | Detail                                                                                          |
| ---------------------- | ----------------------------------------------------------------------------------------------- |
| Type                   | Test infrastructure                                                                             |
| File / path            | `playwright.config.js` (missing), `tests/e2e/` (missing), `.github/workflows/e2e.yml` (missing) |
| Responsibility         | Run browser tests against local/dev/staging                                                     |
| Confidence             | High (grade-A evidence)                                                                         |
| Architecture reference | Build Guide §10, §11                                                                            |

**Why suspected:** Evidence 1, 2, 3, 4 — all point to missing browser test infrastructure.

**Blast radius:** New dependency (Playwright), new CI job, new test directory, ~5-10 new test files. No production code changes.

---

### Component: AMEndToEndTest (Incomplete)

| Attribute              | Detail                                 |
| ---------------------- | -------------------------------------- |
| Type                   | Feature test                           |
| File / path            | `tests/Feature/AMEndToEndTest.php`     |
| Responsibility         | Verify AM1→AM9 at service layer        |
| Confidence             | High (exists, passes)                  |
| Architecture reference | Build Guide §7 Definition of Beta Done |

**Why suspected:** Evidence 1 — covers service layer but not UI. Needs browser equivalent.

**Blast radius:** New Playwright test file(s) re-implementing the same narrative at browser level. No production code changes.

---

### Component: Hard-Rule UI Regression Suite (Missing)

| Attribute              | Detail                                                                                                                    |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| Type                   | Test suite                                                                                                                |
| File / path            | `tests/e2e/hard-rules.spec.ts` (to create)                                                                                |
| Responsibility         | Verify UI shows errors/rollbacks for: negative stock, duplicate invoice number, failed atomic transaction, reverse action |
| Confidence             | High (Evidence 3)                                                                                                         |
| Architecture reference | Build Guide §8 Non-negotiable business rules                                                                              |

**Why suspected:** Evidence 3 — zero UI tests for hard rules.

**Blast radius:** New test file; may need new test data factories. No production code changes.

---

### Component: B0 UI State Regression Suite (Missing)

| Attribute              | Detail                                                                                                        |
| ---------------------- | ------------------------------------------------------------------------------------------------------------- |
| Type                   | Test suite                                                                                                    |
| File / path            | `tests/e2e/b0-states.spec.ts` (to create)                                                                     |
| Responsibility         | Verify every list has skeleton, every empty state has action, every financial action has modal, RTL/LTR works |
| Confidence             | High (Evidence 4)                                                                                             |
| Architecture reference | Design System §B0, Build Guide §3                                                                             |

**Why suspected:** Evidence 4 — zero UI regression for B0 states.

**Blast radius:** New test file; may need to audit all pages for component usage first.

---

### Component: AM1→AM9 Browser Walkthrough Test (Missing)

| Attribute              | Detail                                               |
| ---------------------- | ---------------------------------------------------- |
| Type                   | E2E test                                             |
| File / path            | `tests/e2e/am1-am9-walkthrough.spec.ts` (to create)  |
| Responsibility         | Full phone-in-hand walkthrough using DemoSeeder data |
| Confidence             | High (Evidence 5 + Build Guide §7)                   |
| Architecture reference | Build Guide §7 Definition of Beta Done               |

**Why suspected:** Evidence 5 — Definition of Beta Done explicitly requires this walkthrough.

**Blast radius:** New test file; reuses `DemoSeeder`; no production code changes.

---

## Related Requirements

| Requirement                                         | Type    | Source                      | Status                                      |
| --------------------------------------------------- | ------- | --------------------------- | ------------------------------------------- |
| B8 phase: Demo of AM1→AM9 end-to-end                | FR      | PRD v1.1 §3, Build Guide §7 | **Violated** (service test only)            |
| Regression on hard rules + states                   | NFR     | Build Guide §8, §3          | **Violated** (zero UI tests)                |
| E2E: rep day flow + admin master-data + RTL smoke   | NFR     | Build Guide §10             | **Violated** (0 browser tests)              |
| After every phase: run tests, print summary, commit | Process | Build Guide §1              | **Partial** (unit/feature pass, no browser) |

---

## Recommended Action

**Planning Response:** Option A — Create Fix Stories (multiple)

### Option A — Create Fix Stories

| Story | Title                                                                                   | Priority                     |
| ----- | --------------------------------------------------------------------------------------- | ---------------------------- |
| B8.1  | Set up Playwright + CI job for browser tests                                            | P0 (blocks all B8)           |
| B8.2  | AM1→AM9 browser walkthrough test (using DemoSeeder)                                     | P0 (Definition of Beta Done) |
| B8.3  | Hard-rule UI regression suite (negative stock, atomic invoice, sequential num, reverse) | P0 (hard rules)              |
| B8.4  | B0 UI state regression suite (skeletons, empty states, modals, RTL)                     | P0 (B0 compliance)           |
| B8.5  | Admin master-data flow regression (CRUD + permissions)                                  | P1 (admin flow)              |

**Suggested order:** B8.1 → B8.2 → B8.3 → B8.4 → B8.5

---

### Story Draft: B8.1 — Set up Playwright + CI

| Field                     | Value                                                                                                              |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| Epic                      | B8 Demo & Regression                                                                                               |
| Story title               | Playwright setup + GitHub Actions CI job                                                                           |
| As a                      | DevOps / QA Engineer                                                                                               |
| I want                    | Playwright installed, configured, running in CI on every PR                                                        |
| So that                   | Browser tests run automatically and block merges on failure                                                        |
| Suggested AC 1            | `npm install -D @playwright/test` + `playwright install chromium`                                                  |
| Suggested AC 2            | `playwright.config.ts` with baseURL `http://localhost:8000`, projects for chromium/firefox/webkit, retries=2 on CI |
| Suggested AC 3            | `.github/workflows/e2e.yml` runs `php artisan serve` → `npx playwright test` on PR                                 |
| Suggested AC 4            | Test runs headless on CI, headed locally with `npx playwright test --headed`                                       |
| Suspected files / modules | `package.json`, `playwright.config.ts`, `.github/workflows/e2e.yml`                                                |
| Investigation reference   | `bmad-output/investigation-b8-demo-regression-completion-2026-07-19.md`                                            |

---

### Story Draft: B8.2 — AM1→AM9 Browser Walkthrough

| Field                     | Value                                                                                                                                                                                                                                                                                   |
| ------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Epic                      | B8 Demo & Regression                                                                                                                                                                                                                                                                    |
| Story title               | AM1→AM9 phone-in-hand browser walkthrough test                                                                                                                                                                                                                                          |
| As a                      | QA Engineer                                                                                                                                                                                                                                                                             |
| I want                    | A Playwright test that logs in as rep, completes the full AM1→AM9 narrative in the browser                                                                                                                                                                                              |
| So that                   | The Definition of Beta Done is verifiable automatically                                                                                                                                                                                                                                 |
| Suggested AC 1            | Test uses `DemoSeeder` (via `php artisan db:seed --class=DemoSeeder` in `beforeAll`)                                                                                                                                                                                                    |
| Suggested AC 2            | Covers: login → 5 visits → GPS check-in (mock) → report → sign → submit → manager approve customer → rep price request → manager set 1000±100 → rep 950 → confirm → proforma → WhatsApp share → invoice → stock check → payment → out-of-stock flag → alarm badge → complaint → resolve |
| Suggested AC 3            | GPS mocked via `page.setGeolocation()` + permission granted                                                                                                                                                                                                                             |
| Suggested AC 4            | Signature canvas interaction (mouse/touch)                                                                                                                                                                                                                                              |
| Suggested AC 5            | Runs in < 5 min on CI                                                                                                                                                                                                                                                                   |
| Suspected files / modules | New `tests/e2e/am1-am9.spec.ts`; reuses `DemoSeeder`                                                                                                                                                                                                                                    |
| Investigation reference   | `bmad-output/investigation-b8-demo-regression-completion-2026-07-19.md`                                                                                                                                                                                                                 |

---

### Story Draft: B8.3 — Hard-Rule UI Regression Suite

| Field                     | Value                                                                                                                                              |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| Epic                      | B8 Demo & Regression                                                                                                                               |
| Story title               | Hard-rule UI regression: negative stock, atomic invoice, sequential numbering, reverse                                                             |
| As a                      | QA Engineer                                                                                                                                        |
| I want                    | Playwright tests that verify the UI correctly handles hard-rule violations                                                                         |
| So that                   | No silent regression in CLAUDE.md §8 rules reaches production                                                                                      |
| Suggested AC 1            | Negative stock: rep tries to sell > van stock → UI shows bilingual error modal → no invoice created                                                |
| Suggested AC 2            | Duplicate invoice number: submit same invoice twice → second submit shows "duplicate" error → no double-create                                     |
| Suggested AC 3            | Atomic invoice: force stock decrement failure mid-transaction → UI shows rollback toast → cashbox/invoice/stock unchanged                          |
| Suggested AC 4            | Reverse action: admin reverses invoice → UI shows confirmation modal → after reverse, invoice status = cancelled, stock restored, cashbox restored |
| Suggested AC 5            | Sequential numbering: create 3 invoices → verify numbers are SALESINVOICE-XX-YYYY-001, 002, 003 per company                                        |
| Suspected files / modules | New `tests/e2e/hard-rules.spec.ts`; uses `InvoiceService`, `StockService`, `PaymentService`                                                        |
| Investigation reference   | `bmad-output/investigation-b8-demo-regression-completion-2026-07-19.md`                                                                            |

---

### Story Draft: B8.4 — B0 UI State Regression Suite

| Field                     | Value                                                                                                                                                                      |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Epic                      | B8 Demo & Regression                                                                                                                                                       |
| Story title               | B0 UI state regression: skeletons, empty states, modals, RTL                                                                                                               |
| As a                      | QA Engineer                                                                                                                                                                |
| I want                    | Playwright tests verifying B0 Design System compliance on every page                                                                                                       |
| So that                   | No page ships without skeleton/empty/modal/RTL compliance                                                                                                                  |
| Suggested AC 1            | Skeleton: every list page (`/app/customers`, `/app/stock`, `/app/visits`, `/app/orders`, `/app/quotations`, `/app/notifications`) shows `x-ds-skeleton` rows while loading |
| Suggested AC 2            | Empty state: each list page with 0 results shows `x-ds-empty` with icon + message + action button                                                                          |
| Suggested AC 3            | Modal: every financial action (invoice, payment, return, expense, quotation price, proforma, purchase offer, visit report) shows `x-ds-modal` with consequence text        |
| Suggested AC 4            | RTL: test all pages in `ar` locale — flex direction, margins, icons mirrored correctly                                                                                     |
| Suggested AC 5            | LTR: test all pages in `en` locale — layout correct                                                                                                                        |
| Suspected files / modules | New `tests/e2e/b0-states.spec.ts`; audits all 13 Rep pages + Admin pages                                                                                                   |
| Investigation reference   | `bmad-output/investigation-b8-demo-regression-completion-2026-07-19.md`                                                                                                    |

---

## Open Questions

1. **GPS mocking strategy:** Playwright's `page.setGeolocation()` requires HTTPS or localhost. CI runs on `localhost:8000` — works. But permission prompt handling needs `context.grantPermissions(['geolocation'])`.

2. **Signature canvas interaction:** Canvas drawing via mouse events is flaky in headless. Alternative: inject base64 signature via `page.evaluate()` or use `page.touchscreen.tap()` for mobile.

3. **Test data isolation:** `DemoSeeder` uses `RefreshDatabase` trait — each test gets fresh DB. For Playwright, need to seed once per test run (`beforeAll`) not per test.

4. **RTL testing:** Should run two projects in Playwright config: one with `locale=ar`, one with `locale=en`. Or set `page.setExtraHTTPHeaders({'Accept-Language': 'ar'})` per test.

5. **Admin flow regression (B8.5):** What specific admin flows? Customer CRUD, Product CRUD, Visit Assignment CRUD, User/Role management, Permission checks (rep can't access admin, manager can't access finance, etc.)

---

## Update History

| Version | Date       | Summary of Changes              |
| ------- | ---------- | ------------------------------- |
| 1.0     | 2026-07-19 | Initial investigation case file |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
