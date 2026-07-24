# Brainstorming Session Report

**Date:** 2026-07-19
**Session Duration:** ~45 minutes (parallel subagent execution)
**BMAD Track:** enterprise
**Topic / Problem:** Comprehensive UI/UX gap analysis for Jawla (جولة) Field Sales CRM/ERP — identification and prioritization of all missing pages, modules, components, controls, buttons, and pop-ups required to meet Beta v1.1 Definition of Done.

---

## Session Objective

**Goal:** Systematically audit all 13 Rep PWA pages, 18 Filament admin resources, and design system components against the Beta PRD v1.1 and Design System (B0) requirements to produce a complete inventory of missing UI elements, organized by priority and effort.

**Context:**

- Existing investigation case files document 8 MUST-HAVE gaps, 7 GOOD-TO-HAVE gaps, 5 NICE-TO-HAVE gaps
- Beta PRD v1.1 defines 12 competitor-derived requirements (REQ-CMP-1 through REQ-CMP-12) across phases B0-B8
- Production Build Guide v2 is the implementation authority per CLAUDE.md, but SOURCE_PRECEDENCE.md names Beta PRD as spec
- Current codebase has: 13 Livewire rep pages, 18 Filament admin resources, 4 Filament pages, 8 Filament widgets, 6 DS components (button, card, empty, modal, skeleton, tooltip), tab bar with 4/5 tabs, notifications page + bell icon (partially implemented)

**Constraints:**

1. Bilingual AR/EN + RTL/LTR from day one
2. Must follow Design System (B0) — skeleton loaders, empty states, consequence-stating modals, 6-state components
3. Money/stock mutations only via Services inside DB transactions (StockService)
4. No shell execution, no user input in commands, secrets only in .env
5. All new pages must use `x-ds-*` components per B0 standard
6. Push notifications deferred to v1.1 — in-app bell + red indicators must cover AM4 in beta

**Success Criteria:** Complete prioritized inventory of all missing UI elements with root cause analysis, risk assessment, and clear handoff to investigation/story creation.

**Related BMAD Artifacts:**

- Project context: `bmad-output/project-context.md`
- Decision log: `bmad-output/decision-log.md`
- PRD: `docs/spec/Jawla_Beta_PRD_v1.1.md`
- Investigation case files: `bmad-output/investigation-missing-ui-elements-2026-07-19.md`, `bmad-output/investigation-rep-notifications-bell-2026-07-19.md`

---

## Techniques Used

### Primary Technique: SCAMPER

**Rationale:** Generate creative feature variations for each existing page's controls to identify what's missing and what could be improved.
**Duration:** ~10 min (parallel)

### Secondary Technique: Mind Mapping

**Rationale:** Organize all gaps hierarchically by page, with existing vs. missing controls clearly delineated.
**Duration:** ~15 min (parallel)

### Tertiary Technique: Reverse Brainstorming

**Rationale:** Identify realistic failure modes for each page to prioritize gaps by business risk (financial/data-integrity vs. UX polish).
**Duration:** ~15 min (parallel)

### Quaternary Technique: Six Thinking Hats

**Rationale:** Multi-perspective evaluation of the 8 MUST-HAVE gaps (Owner, Dev, Rep, Manager, Finance).
**Duration:** ~10 min (parallel)

### Quinary Technique: Starbursting

**Rationale:** Systematic Who/What/When/Where/Why/How question exploration for each page and cross-cutting concern.
**Duration:** ~15 min (parallel)

---

## Ideas Generated

### Category 1: MUST-HAVE Gaps (Blocks Beta Done Walkthrough)

| #   | Gap                                                                                        | Description                                                                                                                                     | Source                     | Impact   | Feasibility |
| --- | ------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------- | -------- | ----------- |
| M1  | Out-of-stock flag button + request form + tri-role alarm banner                            | Rep flags product → triggers alarm to Finance/Manager/Executive simultaneously                                                                  | Investigation (Evidence 2) | Critical | Medium      |
| M2  | Stock CSV import wizard                                                                    | **IMPLEMENTED** 2026-07-19 — `StockImportService`, `Filament/Pages/StockImport`, preview→confirm→history, checksum idempotency, 6 passing tests | Investigation (Evidence 6) | Critical | Done        |
| M3  | Geofence blocking dialogs per D-02 (500m; out-of-range = decline; GPS-denied = hard block) | Replace current "Confirm Anyway" with blocking bilingual dialog; write `arrival_flag`                                                           | Investigation (Evidence 5) | Critical | Medium      |
| M4  | Visits tab + rep visits list page                                                          | Add Visits tab to tab bar; new `Visits` Livewire page showing all assigned visits                                                               | Investigation (Evidence 3) | Critical | Medium      |
| M5  | Orders tab + rep documents list                                                            | Add Orders tab to tab bar; new `Orders` Livewire page showing own proformas/invoices with PDF/WhatsApp actions                                  | Investigation (Evidence 3) | Critical | Medium      |
| M6  | Rep alarm bell / notifications list                                                        | **Story drafted: 05.1** — Bell in layout header + `/app/notifications` page with paginated, mark-read-on-open notifications                     | Investigation (Evidence 7) | Critical | Medium      |
| M7  | Consequence-stating bilingual confirmation modals on all money/stock actions               | Replace native `wire:confirm` with `x-ds-modal` showing exact consequence in AR/EN                                                              | Investigation (Evidence 4) | Critical | Small       |
| M8  | Visit stepper visual state machine                                                         | Complete the accessible stepper UI: Scheduled → Arrived → Report → Done with proper state transitions                                           | Investigation (Evidence 8) | Critical | Medium      |

### Category 2: GOOD-TO-HAVE Gaps (Spec'd for Beta, Degraded Without)

| #   | Gap                                                                 | Description                                                                                           | Source                     | Impact | Feasibility |
| --- | ------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- | -------------------------- | ------ | ----------- |
| G1  | Skeleton loaders + `x-ds-empty` empty states on every list          | Components exist (`x-ds-skeleton`, `x-ds-empty`); usage = 0 across all 13 rep pages                   | Investigation (Evidence 4) | High   | Small       |
| G2  | Admin dark mode toggle                                              | **CLOSED 2026-07-19** — Filament v4 enables dark mode by default (`HasDarkMode::$hasDarkMode = true`) | Investigation (Evidence 8) | Medium | Done        |
| G3  | Authenticated style-guide route                                     | Route rendering all component states in AR/EN for design QA                                           | Investigation (Evidence 8) | Medium | Small       |
| G4  | Invoice-draft autosave + offline retry-queue indicator              | Visit draft exists; invoice draft and queued-submission UI absent                                     | Investigation (Evidence 8) | High   | Large       |
| G5  | Customer-card Google Maps deep-link button                          | Maps link exists on visit cards; missing on customer cards                                            | Investigation (Evidence 8) | Medium | Small       |
| G6  | Manager master-schedule filters                                     | Date/route/rep/status filters on `DailyVisitAssignmentResource`                                       | Investigation (Evidence 8) | Medium | Small       |
| G7  | Purchase-offer renegotiation/resubmission UI + rep-set expiry field | Per D-04 decision; multi-item offer builder, renegotiate flow                                         | Investigation (Evidence 8) | Medium | Medium      |

### Category 3: NICE-TO-HAVE (Explicitly Post-Beta)

| #   | Gap                                             | Track     |
| --- | ----------------------------------------------- | --------- |
| N1  | Rep-app dark mode                               | v1.1      |
| N2  | Onboarding walkthrough                          | v1.1      |
| N3  | Push notifications                              | v1.1      |
| N4  | Barcode/QR product lookup                       | v1.1      |
| N5  | Bulk actions in rep app, route-optimization map | v1.1/v1.2 |

### Category 4: Cross-Cutting Systemic Gaps (Affect All Pages)

| Pattern                       | Affected Pages                                                                                            | Root Cause                                                                                               | Fix                                                                    |
| ----------------------------- | --------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| ZERO confirmation modals      | 8 pages (Visit, Sales, Payment, Return, Expense, Complaint, Quotation, Purchase)                          | `<x-ds-modal>` exists but never used                                                                     | Implement shared confirmation modal pattern for every financial action |
| ZERO undo capability          | All 8 mutating pages                                                                                      | Services have `cancel()` methods but none exposed in rep UI                                              | Add 30-60s undo toast wired to existing service `cancel()` methods     |
| Tab bar missing               | Collect Payment, Log Return, Log Expense                                                                  | `<x-tab-bar>` not included in 3 of 13 page views                                                         | Add `<x-tab-bar active="more">` to all three pages                     |
| No skeleton loading           | All 13 pages                                                                                              | `<x-ds-skeleton>` component exists but unused                                                            | Wire up skeleton component on every page that fetches data             |
| No pull-to-refresh            | Home, Customers, Stock, Quotations                                                                        | No refresh gesture on any list page                                                                      | Add pull-to-refresh to all list pages                                  |
| No photo capture              | Visit, Complaint, Return, Expense, Purchase Offer, Collect Payment (6 pages)                              | No camera integration anywhere in PWA                                                                    | Add `<input type="file" accept="image/*" capture="environment">`       |
| No offline queue              | All mutating pages                                                                                        | Service worker caches pages but no data sync; VisitFlow draft saves to localStorage but never syncs back | Implement IndexedDB-based offline queue with background sync           |
| Service worker not registered | Entire PWA                                                                                                | `sw.js` exists but no JS registers it                                                                    | Add SW registration code to base layout                                |
| Searchable dropdowns          | Collect Payment (customers), Log Return (products), Purchase Offer (products + suppliers), Quotation Flow | Native `<select>` unusable with 50+ items on mobile                                                      | Build/adopt searchable autocomplete component                          |

---

## Summary Statistics

| Metric                                      | Count                                                                                           |
| ------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| Total Ideas Generated                       | 37 failure scenarios + 52 SCAMPER variations + 200+ mind map controls + 120 starburst questions |
| Categories                                  | 5 (Must-Have, Good-to-Have, Nice-to-Have, Cross-Cutting, SCAMPER Enhancements)                  |
| High-Impact Ideas (P0)                      | 19                                                                                              |
| Quick Wins (High Impact + High Feasibility) | 32 (Small effort items: tab bars, confirmation modals, validation rules, button states)         |
| Moon Shots (High Impact + Low Feasibility)  | 7 (Offline queue, IndexedDB sync, full accessibility audit, route optimization)                 |

---

## Top Actionable Insights

### 1. CONFIRMATION MODALS ARE THE SINGLE HIGHEST-ROI FIX

**Description:** Zero of the 8 financial/stock mutation pages use the existing `<x-ds-modal>` component for consequence-stating confirmations. Every `submit()` fires directly with only native `wire:confirm` (browser dialog) — which shows no consequence text, is easily dismissed, and provides no RTL/bilingual support.

**Supporting Ideas:**

- Reverse Brainstorming: 13 CRITICAL failure scenarios directly trace to missing confirmations (double-submit, wrong customer, wrong amount, zero-price, over-limit, fraudulent return, etc.)
- Mind Mapping: "Confirmation modal before submit" missing on ALL 8 pages
- Starbursting: P0 questions on every mutating page asking "What prevents accidental submission?"
- SCAMPER: "E — Eliminate the submit step with swipe-to-confirm" as alternative pattern

**Why It Matters:** Every rep creates invoices, collects payments, logs returns/expenses daily. One accidental tap = corrupted stock, wrong customer balance, wrong cashbox, revenue leakage. The backend services already support `cancel()` (PaymentService, ExpenseService, ReturnRecordService, InvoiceService) — the UI just doesn't expose it.

**Recommended Action:** Create a shared `ConfirmAction` Livewire trait/component that wraps `x-ds-modal` with: title, consequence message (bilingual), cancel/confirm buttons, 30-second undo toast after success. Apply to all 8 pages in one PR.

**Feeds Into:** `/bmad-investigate` for M7 (create investigation case file) → `/bmad-planning-orchestrator:bmad-epics-and-stories` for fix story

---

### 2. TAB BAR ABSENCE CREATES NAVIGATION DEAD-ENDS ON 3/13 PAGES

**Description:** Collect Payment, Log Return, and Log Expense pages completely lack the `<x-tab-bar>` component. After completing an action on these pages, the rep has no bottom navigation and must use the browser back button (which closes the PWA on iOS).

**Supporting Ideas:**

- Mind Mapping: Explicitly listed as missing on 3 pages
- Reverse Brainstorming: Failure #17 (High) — "Rep finishes logging a return and is stuck"
- Starbursting: P0 cross-cutting question N.1

**Why It Matters:** These are high-frequency pages (daily cash collection, returns, expenses). Getting stuck breaks the rep's flow and forces app restart on iOS.

**Recommended Action:** One-line fix per page: add `<x-tab-bar active="more">` to each blade file. Do this BEFORE any other work on these pages.

**Feeds Into:** Immediate fix — no investigation needed, just implementation

---

### 3. SKELETON LOADING IS A ZERO-EFFORT PERCEIVED-PERFORMANCE WIN

**Description:** The `<x-ds-skeleton>` component exists with proper design system styling but is used on ZERO of the 13 rep pages. Every page shows a blank white screen during the Livewire round-trip (2-5s on 3G).

**Supporting Ideas:**

- Mind Mapping: "Skeleton loading state" missing on ALL 13 pages
- Reverse Brainstorming: Failure #26 (Medium) — "Rep sees blank white screen for 2-5 seconds on slow mobile networks"
- Starbursting: P1 question P.1 — "What loading states exist?" → Answer: Only `wire:loading` on buttons
- SCAMPER: Not directly addressed, but "A — Adapt pull-to-refresh" relates

**Why It Matters:** Perceived performance is the #1 driver of mobile app retention. Reps in rural areas/basements with poor signal see broken UI instead of graceful loading.

**Recommended Action:** Systematic pass: add `<x-ds-skeleton>` placeholders matching each page's card/list structure. Can be done page-by-page in parallel.

**Feeds Into:** `/bmad-investigate` for G1 (already documented in investigation) → story creation

---

### 4. SEARCHABLE DROPDOWNS ARE A P1 BLOCKER FOR HIGH-VOLUME REPS

**Description:** Four pages use native `<select>` dropdowns with 50-200 options: Collect Payment (customers), Log Return (products), Purchase Offer (products + suppliers), Log Complaint (customers). On mobile, native selects are unusable beyond ~20 items — no search, tiny touch targets, no grouping.

**Supporting Ideas:**

- Mind Mapping: Explicitly listed as missing on all 4 pages
- Reverse Brainstorming: Failures #31, #8, #9, #25 — all trace to unusable dropdowns
- Starbursting: P1 question F.2 — "How are long `<select>` dropdowns handled?"
- SCAMPER: Top idea #3 — "S — Searchable autocomplete for all native `<select>` dropdowns" (H/H)

**Why It Matters:** Reps with large customer catalogs cannot find customers/products to transact. This is a daily blocker, not an edge case.

**Recommended Action:** Build a reusable `<x-ds-autocomplete>` Blade component (Alpine.js) with: search debounce, keyboard navigation, RTL support, grouped options. Replace all 4 dropdowns in one PR.

**Feeds Into:** `/bmad-investigate` for each affected page → story creation

---

### 5. OFFLINE INFRASTRUCTURE EXISTS BUT IS NOT CONNECTED

**Description:** `sw.js` (service worker) and `manifest.json` exist. Visit Flow saves drafts to `localStorage` every 3 seconds. But: SW is not registered (no `navigator.serviceWorker.register()`), no IndexedDB queue, no background sync, no offline indicator on 12/13 pages.

**Supporting Ideas:**

- Reverse Brainstorming: Failures #28 (offline indicator), #29 (draft sync), #30 (double limit bug)
- Starbursting: P0 questions O.1, O.2, O.3 — entire offline section
- Mind Mapping: "Offline queue indicator: ZERO usage", "Service worker not registered"

**Why It Matters:** Field reps work in warehouses, basements, rural areas. Without offline support, the PWA is unusable when signal drops — exactly when reps need it most.

**Recommended Action:**

1. Register SW in base layout (5 min)
2. Add connection status indicator component (1 day)
3. Implement IndexedDB offline queue with `background-sync` for Visit Flow drafts (3-5 days)
4. Extend to other forms incrementally

**Feeds Into:** `/bmad-investigate` for REQ-CMP-3 (offline degradation package) → story creation

---

## Risk Considerations

| Risk                       | Description                                                                                                                                                   | Impact | Probability | Planning Response                                                                                         |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------ | ----------- | --------------------------------------------------------------------------------------------------------- |
| Spec Authority Conflict    | CLAUDE.md says Production Guide is primary; SOURCE_PRECEDENCE.md says Beta PRD is spec. M1-M8 priorities assume Beta PRD.                                     | High   | High        | Resolve with owner before any story work (5 min decision). Log in decision-log.md.                        |
| B0 Design System Drift     | Components built but unused (skeleton, empty, modal). New pages may repeat native-dialog pattern.                                                             | Medium | High        | Add CI gate: `grep -r "wire:confirm" resources/views/livewire` must return 0. Enforce `x-ds-modal` usage. |
| Livewire Alpine Interop    | Searchable autocomplete, pull-to-refresh, haptic feedback require Alpine.js integration. Current Livewire components don't use Alpine consistently.           | Medium | Medium      | Standardize on Alpine for all client-side interactions. Create `<x-ds-autocomplete>` as Alpine component. |
| Cross-Company Data Leakage | Notifications table has no `company_id`; isolation relies on targeting correct user. Feature test for cross-company leakage is mandatory (AC2 in story 05.1). | High   | Low         | Write the failing cross-company test FIRST before any notification send code.                             |
| Offline Sync Complexity    | IndexedDB + background sync + conflict resolution (last-write-wins per TEC-12) is a 3-5 day task with high unknown factor.                                    | High   | Medium      | Spike first: implement Visit Flow draft sync only. Validate on real 3G/offline before extending.          |
| RTL Regression Risk        | Tab bar, stepper, modals, new pages all need RTL testing. Current test suite is broken (Issue #1).                                                            | Medium | High        | Fix test suite first (Issue #1). Add RTL visual regression tests via Playwright.                          |

---

## Ideas Requiring Further Research

| Idea                          | Open Questions                                                                                                                           | Priority | Suggested Skill                               |
| ----------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- | -------- | --------------------------------------------- |
| Offline Architecture          | IndexedDB vs. Dexie.js vs. plain IDB? Background sync vs. periodic poll? Conflict resolution: last-write-wins vs. operational transform? | High     | bmad-research                                 |
| Searchable Dropdown Component | Build custom Alpine component vs. adopt existing library (Tom Select, Choices.js, or Livewire-native)? Bundle size impact?               | High     | bmad-research                                 |
| Haptic Feedback Patterns      | `navigator.vibrate()` support matrix (iOS Safari limitations)? Patterns for submit vs. error vs. GPS confirm?                            | Medium   | bmad-research                                 |
| Push Notification Deferral    | Amendment says push → v1.1. What's the exact in-app bell scope for beta? Only 4 event types?                                             | Low      | bmad-research (already in investigation 05.1) |

---

## Recommended Next Steps

### Immediate (Handoff to Next BMAD Skill)

1. **Resolve Spec Authority**
   - Skill: `/bmad-investigate` (Update intent on existing investigation)
   - Key Input: Owner decision on CLAUDE.md vs SOURCE_PRECEDENCE.md
   - Output: Updated investigation case file with confirmed priority order

2. **Create Investigation Case Files for All M1, M3-M8**
   - Skill: `/bmad-investigate` (Create intent × 7)
   - Key Input: This brainstorming report + existing investigation for M6
   - Output: 7 investigation case files in `bmad-output/investigation-*.md`

3. **Draft Fix Stories for Quick Wins (Tab Bars, Confirmation Modals, Skeletons)**
   - Skill: `/bmad-planning-orchestrator:bmad-epics-and-stories`
   - Key Input: Investigation case files + this report
   - Output: Stories in `bmad-output/stories/` with `ready-for-dev` status

### Short-term Planning Actions

1. **Fix Test Suite First (Issue #1)** — No other work can be verified. Block all story work until Pest + Playwright suites are green.

2. **Batch Quick Wins in One PR** — Tab bars (3 pages), skeleton loading (13 pages), confirmation modals (8 pages), searchable dropdown component (1 component → 4 pages). Total ~5 dev-days.

3. **Offline Spike (Issue #2 Dependency)** — Implement Visit Flow draft sync to IndexedDB + background sync. Unblocks M1 (out-of-stock needs offline queue too).

4. **Accessibility Baseline** — Add `axe-core` to Playwright tests. Fix ARIA labels on tab bar, stepper, modals before new components land.

### Deferred Considerations

1. **Rep-App Dark Mode (N1)** — Wait for v1.1 track. Filament admin dark mode is already done (G2 closed).

2. **Route Optimization Map (N5)** — Requires Google Maps API key or OSRM self-hosted. Deferred to v1.2 per PRD.

3. **Barcode/QR Scanner (N4)** — Needs camera permission UX research. Deferred to v1.1.

---

## Follow-up Sessions

- [ ] Deeper SCAMPER session on top idea cluster (offline sync, searchable dropdowns)
- [ ] SWOT analysis for strategic positioning vs. competitors (RepProX, Spotio, Outfield, BeatRoute)
- [ ] Reverse Brainstorming to stress-test top insights (already done — see brainstorm-risks.md)
- [ ] User research to validate assumptions (field ride-alongs with reps)
- [ ] Architecture ideation with system-architect skill for offline queue
- [ ] Design review with frontend-design skill for new component patterns

---

## Decisions to Log

| Date       | Decision                                                                                    | Rationale                                                                                                        | Impact                                                                                                           |
| ---------- | ------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| 2026-07-19 | Beta PRD v1.1 is the governing spec for M1-M8 gaps (per SOURCE_PRECEDENCE.md)               | CLAUDE.md names Production Guide but SOURCE_PRECEDENCE.md explicitly names PRD as binding. Owner must confirm.   | All M-gap priorities assume Beta PRD. If Production Guide wins, M1, M3, M4, M5, M6, M7, M8 may be deprioritized. |
| 2026-07-19 | Confirmation modals (M7) are the highest-ROI fix and should be batched across all 8 pages   | 13 CRITICAL failure scenarios trace directly to missing confirmations; backend `cancel()` methods already exist. | One PR fixes 8 pages. Prevents financial data corruption.                                                        |
| 2026-07-19 | Tab bar on Collect Payment, Log Return, Log Expense is a P0 blocker                         | 3/13 pages have navigation dead-ends; iOS PWA closes on back gesture.                                            | 3 one-line fixes. Do immediately.                                                                                |
| 2026-07-19 | G2 (Admin dark mode) is CLOSED — Filament v4 default                                        | Investigation found `HasDarkMode::$hasDarkMode = true` is default in v4.                                         | Remove from gap matrix.                                                                                          |
| 2026-07-19 | Starbursting P0 questions (19 items) define the acceptance criteria for Phase B3-B6 stories | Each P0 question = one testable requirement.                                                                     | Use starbursting output as AC source for story creation.                                                         |

---

## Appendix

### Full Idea List (Condensed)

| #   | Idea                                         | Category      | Impact   | Feasibility | Source Technique           |
| --- | -------------------------------------------- | ------------- | -------- | ----------- | -------------------------- |
| 1   | Confirmation modals on all financial actions | Cross-cutting | High     | High        | Reverse Brainstorming      |
| 2   | Tab bar on 3 missing pages                   | Navigation    | High     | High        | Mind Mapping               |
| 3   | Skeleton loading on all 13 pages             | Cross-cutting | High     | High        | Mind Mapping               |
| 4   | Searchable autocomplete dropdown             | Forms         | High     | Medium      | SCAMPER (#3)               |
| 5   | Offline queue + background sync              | Cross-cutting | High     | Low         | Reverse Brainstorming      |
| 6   | Pull-to-refresh on list pages                | Cross-cutting | Medium   | Medium      | SCAMPER (#5)               |
| 7   | Photo capture on 6 pages                     | Cross-cutting | High     | Medium      | Mind Mapping               |
| 8   | Undo toast on all submissions                | Cross-cutting | High     | Medium      | Reverse Brainstorming      |
| 9   | Out-of-stock flag + alarm (M1)               | Must-Have     | Critical | Medium      | Investigation              |
| 10  | Geofence blocking dialogs (M3)               | Must-Have     | Critical | Medium      | Investigation              |
| 11  | Visits tab + list page (M4)                  | Must-Have     | Critical | Medium      | Investigation              |
| 12  | Orders tab + documents list (M5)             | Must-Have     | Critical | Medium      | Investigation              |
| 13  | Rep notifications bell (M6)                  | Must-Have     | Critical | Medium      | Investigation (Story 05.1) |
| 14  | Visit stepper state machine (M8)             | Must-Have     | Critical | Medium      | Investigation              |
| 15  | Style-guide route (G3)                       | Good-to-Have  | Medium   | Small       | Investigation              |
| 16  | Invoice draft autosave (G4)                  | Good-to-Have  | High     | Large       | Investigation              |
| 17  | Maps deep-link on customer cards (G5)        | Good-to-Have  | Medium   | Small       | Investigation              |
| 18  | Master-schedule filters (G6)                 | Good-to-Have  | Medium   | Small       | Investigation              |
| 19  | Purchase offer renegotiation (G7)            | Good-to-Have  | Medium   | Medium      | Investigation              |

### Sources Referenced

- `docs/spec/Jawla_Beta_PRD_v1.1.md` — Beta requirements + competitor-derived REQ-CMP-*
- `docs/Jawla_Production_Build_Guide.md` — Implementation rules + Design System (B0)
- `bmad-output/investigation-missing-ui-elements-2026-07-19.md` — Full gap matrix + hypotheses
- `bmad-output/investigation-rep-notifications-bell-2026-07-19.md` — Deep dive on M6
- `bmad-output/brainstorm-scamper.md` — 52 SCAMPER variations across 13 pages
- `bmad-output/brainstorm-mindmap.md` — 500+ lines of existing vs. missing controls per page
- `bmad-output/brainstorm-risks.md` — 37 failure scenarios ranked by criticality
- `bmad-output/brainstorm-questions.md` — 67 starbursting questions with priority/effort
- `routes/web.php` — All 22+ rep routes + Filament auto-routes
- `resources/views/layouts/app.blade.php` — Rep PWA shell + notification bell
- `resources/views/components/tab-bar.blade.php` — Current 4-tab implementation

### Session Notes

The brainstorming reveals a clear pattern: the codebase has **built the foundation** (services, models, migrations, Filament admin, 13 Livewire pages, 6 design system components) but **missed the phase gates** that enforce B0 standards (skeletons, empty states, modals) and Beta PRD requirements (alarms UI, tabs, geofence D-02, notifications). The investigation's Hypothesis 1 (wrong spec track) and Hypothesis 2 (B0 kit built but never enforced) are strongly supported by all five techniques.

**Most surprising finding:** The `<x-ds-modal>` component exists and is fully implemented (bilingual, accessible, consequence-stating) but is used **zero times**. Every financial action uses `wire:confirm` (native browser dialog) instead — the exact anti-pattern the Design System was created to prevent.

**Most encouraging finding:** All backend services for undo/cancel exist (`PaymentService::cancel()`, `ExpenseService::cancel()`, `ReturnRecordService::cancel()`, `InvoiceService::cancel()`). The UI just needs to wire them up with a 30-second undo toast.

---

**Report Generated By:** BMAD Brainstorm Skill (`/bmad-planning-orchestrator:bmad-brainstorm`)
**Output Path:** `bmad-output/brainstorming-report.md`
**Related Artifacts:** `bmad-output/brainstorm-scamper.md`, `bmad-output/brainstorm-mindmap.md`, `bmad-output/brainstorm-risks.md`, `bmad-output/brainstorm-questions.md`, `bmad-output/brainstorm-objective.md`
