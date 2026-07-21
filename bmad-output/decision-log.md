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
