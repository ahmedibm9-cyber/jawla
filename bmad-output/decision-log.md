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
