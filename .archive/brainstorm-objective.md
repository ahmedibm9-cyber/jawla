# Brainstorming Objective

**Topic:** Comprehensive UI/UX gap analysis for Jawla (جولة) Field Sales CRM/ERP — identification and prioritization of all missing pages, modules, components, controls, buttons, and pop-ups required to meet Beta v1.1 Definition of Done.

**Context:**

- Existing investigation case files document 8 MUST-HAVE gaps, 7 GOOD-TO-HAVE gaps, 5 NICE-TO-HAVE gaps
- Beta PRD v1.1 defines 12 competitor-derived requirements (REQ-CMP-1 through REQ-CMP-12) across phases B0-B8
- Production Build Guide v2 is the implementation authority per CLAUDE.md, but SOURCE_PRECEDENCE.md names Beta PRD as spec
- Current codebase has: 13 Livewire rep pages, 18 Filament admin resources, 4 Filament pages, 8 Filament widgets, 6 DS components (button, card, empty, modal, skeleton, tooltip), tab bar with 4/5 tabs, notifications page + bell icon (partially implemented)

**Key Constraints:**

1. Bilingual AR/EN + RTL/LTR from day one
2. Must follow Design System (B0) — skeleton loaders, empty states, consequence-stating modals, 6-state components
3. Money/stock mutations only via Services inside DB transactions (StockService)
4. No shell execution, no user input in commands, secrets only in .env
5. All new pages must use `x-ds-*` components per B0 standard
6. Push notifications deferred to v1.1 — in-app bell + red indicators must cover AM4 in beta

**Techniques to Apply:**

1. **SCAMPER** — Generate feature variations for each missing UI element
2. **Mind Mapping** — Organize all gaps hierarchically by module/page/component
3. **Reverse Brainstorming** — Identify failure modes and risks for each gap
4. **Six Thinking Hats** — Multi-perspective evaluation (Owner, Dev, Rep, Manager, Finance)
5. **Starbursting** — Question exploration for each gap (Who/What/When/Where/Why/How)

**Deliverable:**

- `bmad-output/brainstorming-report.md` — organized ideas + top insights
- Individual technique outputs in `bmad-output/brainstorm-scratch/`
- Updated `bmad-output/decision-log.md` with key decisions
