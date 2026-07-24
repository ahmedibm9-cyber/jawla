# Decision Log

## 2026-07-18 — UI Control Module Audit

### Decisions Made

| #   | Decision                                                                  | Rationale                                                                                                     | Status   |
| --- | ------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- | -------- |
| D1  | Prioritize confirmation modals for all financial actions                  | Zero confirmation modals across 8 mutating pages is a critical safety gap                                     | Accepted |
| D2  | Add tab bar to Collect Payment, Log Return, Log Expense                   | Navigation dead-ends break mobile UX on iOS PWA                                                               | Accepted |
| D3  | Register service worker in layout                                         | PWA is not installable/offline-capable despite having manifest + sw.js                                        | Accepted |
| D4  | Fix `$recalcCart()` no-op in SalesFlow                                    | Cart totals are wrong; no tax calculation occurs                                                              | Accepted |
| D5  | Replace native `<select>` with searchable autocomplete                    | 50+ item selects are unusable on mobile touch screens                                                         | Accepted |
| D6  | Use existing `<x-ds-modal>`, `<x-ds-skeleton>`, `<x-ds-empty>` components | Design system components exist but are unused; raw HTML used instead                                          | Accepted |
| D7  | Add photo capture to Visit Flow, Complaints, Returns                      | Proof-of-presence and evidence capture is critical for field operations                                       | Accepted |
| D8  | Wire up existing service cancel methods for undo                          | `PaymentService::cancel()`, `ExpenseService::cancel()`, `ReturnService::cancel()` exist but are never exposed | Accepted |

### Risks Identified

| Risk                                   | Impact                                         | Mitigation                   |
| -------------------------------------- | ---------------------------------------------- | ---------------------------- |
| Financial actions have no confirmation | Accidental payments/invoices                   | Add confirmation modals (D1) |
| No skeleton loading on any page        | Poor perceived performance on slow connections | Add skeleton states (P1)     |
| Service worker not registered          | PWA not installable, not offline-capable       | Register in layout (D3)      |
| Cart recalculation is no-op            | Wrong totals, no tax                           | Implement recalculation (D4) |

### Pending Decisions

| Question                        | Options          | Recommendation                                          |
| ------------------------------- | ---------------- | ------------------------------------------------------- |
| Should we add barcode scanning? | Yes / No / Later | Later (P3) — requires camera API + barcode library      |
| Should we add voice notes?      | Yes / No / Later | Later (P3) — Web Speech API has limited browser support |
| Should we add dark mode?        | Yes / No / Later | Later (P3) — not requested by client                    |
| Should we virtualize lists?     | Yes / No / Later | Later — current lists are < 100 items                   |

## Investigation: missing-ui-elements — 2026-07-19

- Symptom: Spec'd beta UI surfaces missing — out-of-stock flow, stock import wizard, Visits/Orders tabs, rep notifications, geofence D-02 behavior, unused DS state components.
- Primary hypothesis: Build followed the Production Guide track instead of the governing Beta v1.1 spec (CLAUDE.md vs SOURCE_PRECEDENCE.md conflict).
- Primary suspected component: Rep PWA shell (app/Livewire/App + tab-bar/layout) and Filament StockResource.
- Case file: bmad-output/investigation-missing-ui-elements-2026-07-19.md
- Recommended response: Option C — resolve spec authority, then stories M3→M1→M2→M4/M5→M6→M7/G1→G2–G7.

## Investigation: rep-notifications-bell — 2026-07-19

- Symptom: Reps never learn quotation, customer-approval, or complaint outcomes in-app — no bell, no notification mechanism, no sender hooks exist (issue #5 / gap M6).
- Primary hypothesis: Alarm domain was scoped admin-only and nothing claimed the rep side; Laravel database notifications is the correct vehicle (Notifiable trait present, only the table migration missing).
- Primary suspected component: Rep PWA shell (layouts/app.blade.php header + new /app/notifications page) plus four sender hook points (PriceQuotationRequestResource set_price, CustomerResource approve/reject, ComplaintService::resolve, OutOfStockService future resolve).
- Case file: bmad-output/investigation-rep-notifications-bell-2026-07-19.md
- Recommended response: Option A — story created at bmad-output/stories/05.1.rep-notifications-bell.story.md (ready-for-dev; blocked by issue #1 test-suite repair).

## Investigation: rep-ui-audit — 2026-07-19

- Symptom: REP account UI completeness — verified against original M1-M8 and G1-G7 gaps identified in v1.0 audit.
- Primary hypothesis: REP UI is ~95% complete; all M-gaps (M1-M8) are now implemented. Remaining issues are non-blocking refactors (hardcoded bilingual strings, native `<select>` drop downs, DS card/button adoption).
- Primary suspected component: Rep PWA shell (16 Livewire pages) + translation layer (`lang/en/app.php`, `lang/ar/app.php`).
- Case file: bmad-output/investigation-missing-ui-elements-2026-07-19.md (v2.0 update)
- Recommended response: Option A — 7 low-priority fix stories (P1-P7) for remaining polish items; Option B for notification bell story — mark it completed (already built).

## Investigation: rep-native-select-to-autocomplete — 2026-07-19

- Symptom: Four REP pages use native `<select>` dropdowns with 50-100 items, violating accepted D5 decision (2026-07-18) to replace with searchable autocomplete.
- Primary hypothesis: D5 accepted but autocomplete component never built; four pages never migrated.
- Primary suspected component: New Autocomplete component (to be created) + 4 REP pages (collect-payment, log-return, log-complaint, submit-purchase-offer).
- Case file: bmad-output/investigation-rep-native-select-to-autocomplete-2026-07-19.md
- Recommended response: Option A — create fix story: build autocomplete component + migrate 8 dropdowns across 4 pages.

## Investigation: rep-ds-card-button-tooltip-adoption — 2026-07-19

- Symptom: Three DS components (`x-ds.card`, `x-ds.button`, `x-ds.tooltip`) exist but have zero usage across all 16 REP pages; raw HTML used instead.
- Primary hypothesis: D6 was scoped to "critical path" components only (modal, skeleton, empty); card/button/tooltip deemed non-blocking and deferred.
- Primary suspected component: DS components (existing) + all 16 REP page views (migration targets).
- Case file: bmad-output/investigation-rep-ds-card-button-tooltip-adoption-2026-07-19.md
- Recommended response: Option A — create fix story: migrate ~80 cards, ~50 buttons, add tooltips to icon-only actions across 16 pages.

## Brainstorm: top-5-competitive-gaps-roadmap — 2026-07-20

- Topic: Convert the top 5 competitive gaps from `docs/competitor-research-2026-07-20-rep-in.md` into roadmap-ready epics and stories.
- Techniques used: Mind Mapping + Reverse Brainstorming + Starbursting.
- Chosen epics: CG1 portable printing, CG2 true offline transactions, CG3 live rep tracking, CG4 API + ERP ecosystem, CG5 sales targets & attainment.
- Primary insight: The five gaps cluster into three strategic advantages — field execution (CG1+CG2), manager visibility (CG3+CG5), and enterprise/platform trust (CG4).
- Artifacts:
  - `bmad-output/brainstorming-report-top-5-competitive-gaps-2026-07-20.md`
  - `bmad-output/epics-top-5-competitive-gaps-2026-07-20.md`
  - `bmad-output/stories/CG1.1.bluetooth-print-transport.story.md` ... `CG5.3.rep-target-progress-and-reports.story.md`
- Recommended next step: execute CG1 then CG5, then CG2, CG3, CG4 in order.

## Brainstorm: phase6-ui-polish — 2026-07-21

- Topic: "Think about all tasks in Phase 6" (UI polish) from plans/whimsical-squishing-cosmos.md.
- Techniques: Mind Mapping + SCAMPER + Reverse Brainstorming (inline, no subagents).
- Method: live audit of app.css + ds/* + rep blades, not the stale review doc.
- Primary finding: **Phase 6 is ~85% already done** by the concurrent effort — modal scrim, responsive breakpoints, landscape, dark mode (unverified), heroicons, 44px bell, focus mgmt, RTL text-end, scroll-to-top all shipped.
- Genuinely remaining: B1 undo toast (reuse outbox discard() pre-sync + ReversalService post-sync, as one global action-toast — never delete()); B2 dark-mode QA (shipped but untested — the sleeper risk); B3 pull-to-refresh; micro-fixes (font scaling, skeleton aria, canvas DPR, token dedup); optional C1 style-guide route.
- Top risk: untested dark mode reaching field reps + undo semantics vs. the immutable-reversal rule and the offline outbox.
- OPEN DECISION: is rep dark mode IN this release (QA it) or OUT (gate behind opt-in / revert media query)? It was previously N1 = v1.1.
- Artifact: bmad-output/brainstorming-report-phase6-ui-polish-2026-07-21.md
- Recommended next: bmad-epics-and-stories for B1 (undo) + B2 (dark-mode QA); order B2→B1→verify-close→style-guide→micro-fixes, deferring rep-blade edits until concurrent churn settles.

## Investigation: rep-login-fragility — 2026-07-21

- Symptom: Rep login broke twice; two parallel rep-login systems now coexist after a piecemeal rollback of the "unified login" refactor.
- Primary hypothesis: Incomplete rollback of d3fddc6 ("unified login") — routes restored (57d460a) before controller methods (a0daf22), and redirectGuestsTo('/admin/login') + the Filament rep-login path were never reverted, leaving two sources of truth.
- Primary suspected component: Auth entry points — routes/web.php + LoginController + bootstrap/app.php (redirectGuestsTo) + Filament Auth Login.php/LoginResponse.
- Case file: bmad-output/investigation-rep-login-fragility-2026-07-21.md
- Recommended response: Option C→A — decide ONE canonical rep-login path, delete the other, align redirectGuestsTo, add an end-to-end rep-login regression test. Concrete lingering bug: redirectGuestsTo points at /admin/login while /app/login is the restored rep page.

## Go-live gates B2/B3 — 2026-07-21

- B2 (durable photo storage): DECISION = Railway bucket. DONE — flysystem-s3 installed, PhotoService config-driven disk (PHOTO_DISK=s3), Photo::url() signed URLs for the private bucket, provisioned Railway bucket `jawla-photos` (ams), S3 creds set on the app service, round-trip validated (put/get/delete OK against the live bucket). Photos durable + replica-shared.
- B3 (backups): DECISION = Railway managed Postgres backups sufficient for V1; spatie/laravel-backup NOT installed. Remaining: operator runs the pg_dump→restore drill once and records it.
- B1 (ETA e-invoicing): status = credentials coming; held + correctly gated off. The one remaining hard go-live blocker.
- B4 (live k6 + Burp): staging/manual passes remain.

## Investigation: race-conditions-and-rep-reliability — 2026-07-20

- Symptom: full-app sweep — REP actions "fail or hang", suspected race conditions in money/stock flows, screen-fit unverified
- Primary hypothesis: sale double-submit (cart never reset) + a repo-wide check-then-act-without-lock pattern in every cancel/undo path; online writes lack the idempotency the offline path already has
- Primary suspected component: SalesFlow + the money-mutation service cancel family (Invoice/Return/Payment/Expense/VanTransfer)
- Case file: bmad-output/investigation-race-conditions-and-rep-reliability-2026-07-20.md
- Recommended response: Option A — stories 08.1 (concurrency hardening), 08.2 (rep action reliability), 08.3 (live UI audit), 08.4 (price bounds)
- Owner decisions captured: sales always stock-backed; min/max price bounds set by manager; overpayment = customer credit; expense-floor check deferred

## Audit: full-stack-audit — 2026-07-20

- Scope: security (OWASP-aligned), frontend, backend, CI/CD, auth, UI/UX — static review + dependency audits
- Headline: SW-1 service worker cache-first navigations (stale app after deploy + pages readable after logout) — likely true root cause of "looks broken / hangs"; 1 High, 8 Medium, 7 Low/Info new findings
- Report: bmad-output/full-stack-audit-2026-07-20.md
- Response: fix SW-1 first, then epic 08 stories; Railway env/backup verification and staging pen-test flagged as owner actions

## Investigation: pwa-production-readiness — 2026-07-22

- Scope: all 137 numbered domains in the supplied PWA production-readiness checklist, plus release gates and go/no-go questions.
- Method: static forensic review only; no tests, builds, browsers, scanners, database, network, Railway, or deployment actions were run.
- Result: 60 Fail, 76 Partial, 1 N/A, 0 Pass.
- Headline: **NO-GO for normal production, any real-data pilot, and real Egyptian invoicing.** P0 blockers include cross-tenant Activity Log exposure, authenticated PWA cache/client-state leakage, financial/offline state races, false sync state, unsafe SW updates, unproved restore/rollback, fail-open CI, privacy/legal gaps, unsigned ETA, and contrast failure.
- Case file: `bmad-output/investigation-pwa-production-readiness-2026-07-22.md`
- Full audit: `bmad-output/pwa-production-readiness-audit-2026-07-22.md`
- Recommended response: Option C — freeze real-data launch, reconcile the authoritative baseline/owners, execute the systemic workstreams, then repeat independent runtime verification against one clean immutable release artifact.

## Remediation verification — 2026-07-22

- The PostgreSQL-backed suite passed: 332 tests, 1,038 assertions, including 12 browser E2E tests.
- Corrected post-remediation defects: legacy cash boxes are adopted under a user-row lock; sync unique-key races return the durable result; deferred service-worker updates re-prompt after the queue drains.
- Corrected stale test assumptions: return photo attachment now supplies the mandated active van and customer balance; purchase-order assertions validate the required random suffix as well as sequence.
- Removed `bmad-output/issues/01-repair-test-suite.md` only after its verification outcome was demonstrated. The production readiness audit remains NO-GO because operational, legal, tax, staging, and recovery evidence is still absent.

## Investigation: Null Property Access in Dashboard Widgets — 2026-07-23

- Symptom: 500 error "Attempt to read property company_id on null" on admin dashboard when session expires during Livewire update
- Primary hypothesis: Session expiry causes Auth::user() to return null, all 8 dashboard widgets crash
- Primary suspected component: All 8 dashboard widgets (null-unsafe Auth::user() pattern)
- Case file: bmad-output/investigation-null-company-id-widget-crash-2026-07-23.md
- Recommended response: Option A — create fix story for null guard across all widgets
- Fix story: bmad-output/stories/HOTFIX.null-guard-dashboard-widgets.story.md

## Investigation: Test Coverage Gaps — 2026-07-23

- Symptom: 18 user stories have zero test coverage, 12 have partial coverage (72 total across 24 epics)
- Primary hypothesis: Filament admin pages and Livewire rep components lack tests due to auto-generation patterns and Playwright/Alpine.js incompatibility
- Primary suspected component: Filament Resources (PaymentResource, ExpenseResource, StockResource, PurchaseOrderResource, TaskResource) + Livewire App components (ProfilePage, SettingsPage, TodaysCustomers) + Dashboard Widgets (7 total)
- Case file: bmad-output/investigation-test-coverage-gaps-2026-07-23.md
- Recommended response: Option A — Create 13 test files across P0/P1/P2/P3 priority
- P0 (financial/reversal): InvoiceAmendServiceTest, ReversalServiceTest, PaymentResourceTest
- P1 (admin visibility): DashboardWidgetTest, InvoiceResourceTest, StockResourceTest, ExpenseResourceTest
- P2 (UX/security): ProfilePageTest, SettingsPageTest, TodaysCustomersTest, PurchaseOrderResourceTest
- P3 (backlog): TaskManagementTest, StockAdjustTest
- Note: US-7.2 Cancel Return was flagged as "missing" but is actually covered at service level (ReturnServiceTest.php:81)
