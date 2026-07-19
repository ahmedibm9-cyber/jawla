# Investigation Case File: v1-2-v2-deferred-items

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — Phase Roadmap v1.2/v2 items per PRD v1.1 §3
**Severity:** Deferred (long-term strategic)
**Status:** Open — Cataloged for v1.2+ planning
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-v1-2-v2-deferred-items-2026-07-19.md`

---

## Summary

**One-sentence description:**
Seven v1.2/v2 items are explicitly deferred in PRD v1.1 §3 — these are major strategic investments (STK-3 automation, route optimization, OCR, ZATCA/SA/inter-company, gamification, AI, form builder) requiring significant architecture and likely external dependencies. This file catalogs them for long-term roadmap planning.

**Expected behavior:** These features ship in v1.2/v2 track after v1.1 is complete.

**Actual behavior:** All explicitly deferred. None have implementation started.

**User / business impact:** None for Beta or v1.1. These are strategic differentiators for v1.2+ market positioning.

---

## Symptom Details

**Trigger conditions:** Structural — explicitly deferred per PRD v1.1 §3 phase map.

**Environments affected:** Future (v1.2+ track).

**First observed:** PRD v1.1 phase map (2026-07-19).

**Frequency:** Constant (deliberate deferral).

**Reproducible:** N/A — intentional deferral.

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed in PRD/Build Guide
> - **[B] Probable** — inferred from dependencies
> - **[C] Speculative** — not yet investigated

### Evidence Item 1: STK-3 Automation (Inventory Integration)

**Grade:** [A]
**Source:** PRD v1.1 §3: "STK-3 (automation) lands with integration work" under v1.2/v2
**Description:** REQ-STK-3 "automated inventory integration" was explicitly deferred from Beta. Current: manual CSV import (StockImportService). Future: ERP/warehouse system integration (API/webhook), real-time stock sync, automated reorder points.
**Implications:** Requires ERP connector framework, webhook ingestion, conflict resolution, audit trail. Major integration project.

---

### Evidence Item 2: Route Optimization

**Grade:** [A]
**Source:** PRD v1.1 §3: "Route optimization" under v1.2/v2; REQ-CMP-6 "Maps deep-link" done in Beta, "full optimization deferred"
**Description:** DailyVisitAssignment has route_id + customer lat/lng. Need: TSP solver (OSRM/OR-Tools), multi-stop sequencing, time windows, traffic awareness, map visualization (Leaflet/MapLibre), turn-by-turn via Google Maps deep-link.
**Implications:** Algorithm (TSP/VRP), external dependency (OSRM self-hosted or Google Maps API), map rendering, performance at scale (50+ stops).

---

### Evidence Item 3: OCR Receipts / Inter-Company / Saudi ZATCA

**Grade:** [A]
**Source:** PRD v1.1 §3: "OCR / inter-company+Saudi+ZATCA" under v1.2/v2
**Description:** Three related but distinct:

- **OCR:** Expense receipt scanning (Tesseract/Google Vision), auto-extract amount/date/category
- **Inter-company:** Multi-entity support, transfer pricing, consolidated reporting
- **ZATCA Phase 2+:** Saudi Arabia e-invoicing full compliance (CSID, cryptographic stamp, clearance flow)
  **Implications:** OCR needs ML/OCR service; Inter-company needs multi-tenant architecture; ZATCA needs CSID management, cryptographic stamping, clearance API integration.

---

### Evidence Item 4: Gamification

**Grade:** [A]
**Source:** PRD v1.1 §3: "Gamification" under v2
**Description:** Leaderboards, achievements, badges, streaks for reps (visits completed, invoices created, payments collected, OOS flags). Real-time UI, notifications, admin config.
**Implications:** New domain (Gamification), event-driven (Laravel Events), real-time (Laravel Echo/Pusher), admin config UI, performance at scale.

---

### Evidence Item 5: AI Assistant

**Grade:** [A]
**Source:** PRD v1.1 §3: "AI assistant" under v2
**Description:** Copilot for reps/managers: "What's my top customer?", "Show me low stock", "Draft visit report from voice", "Suggest price for new customer". Natural language → SQL/API, RAG over company data.
**Implications:** LLM integration (OpenAI/Azure), RAG pipeline (pgvector/pgvector), prompt engineering, cost monitoring, data privacy (no PII to LLM).

---

### Evidence Item 6: Form Builder

**Grade:** [A]
**Source:** PRD v1.1 §3: "Form builder" under v2
**Description:** Dynamic forms for visit reports, complaints, custom inspections. Drag-drop builder (JSON schema), conditional logic, validation, file upload, signature, repeatable sections. Admin creates, Rep fills.
**Implications:** JSON schema engine (JSON Schema + AJV), dynamic Livewire components, conditional rendering, versioning, migration of existing hardcoded forms.

---

### Evidence Item 7: OCR / Inter-Company / Saudi+ZATCA (consolidated)

**Grade:** [A]
**Source:** PRD v1.1 §3 grouping
**Description:** These three are often coupled: OCR for Saudi e-invoice attachments, Inter-company for Saudi groups, ZATCA for compliance. Building them together shares: document storage, cryptographic signing, audit trail, multi-entity architecture.
**Implications:** Suggests a "Saudi Compliance Module" package rather than three separate features.

---

### Evidence Summary

| #   | Item                        | Grade | Effort | Dependencies                  | Strategic Value        |
| --- | --------------------------- | ----- | ------ | ----------------------------- | ---------------------- |
| 1   | STK-3 Automation            | A     | Large  | ERP APIs, webhook framework   | High (ops efficiency)  |
| 2   | Route Optimization          | A     | Large  | OSRM/Google Maps, TSP solver  | High (rep efficiency)  |
| 3   | OCR / Inter-company / ZATCA | A     | Large  | ML/OCR, multi-tenant, crypto  | **Critical** (Saudi)   |
| 4   | Gamification                | A     | Medium | Events, Echo, admin UI        | Medium (retention)     |
| 5   | AI Assistant                | A     | Large  | LLM, RAG, pgvector            | High (differentiation) |
| 6   | Form Builder                | A     | Large  | JSON Schema, dynamic Livewire | High (flexibility)     |

---

## Hypotheses

### Hypothesis 1 — Saudi Compliance (ZATCA + Inter-company) is the highest-value v1.2 item [Plausibility: High]

**Statement:** Saudi market requires full ZATCA Phase 2 compliance + inter-company for groups. This unlocks Saudi enterprise deals. OCR supports it (receipt attachments for e-invoice).

**Supporting evidence:** Evidence 3 [A] — grouped in PRD; ZATCA is regulatory requirement.

**Contradicting evidence:** None.

**Verification step:** Prioritize ZATCA Phase 2 architecture spike in v1.1; build inter-company + OCR as supporting features.

---

### Hypothesis 2 — Route Optimization and STK-3 Automation are "nice-to-have" efficiency plays [Plausibility: Medium]

**Statement:** Route optimization saves rep time/fuel; STK-3 automation reduces manual CSV imports. Both are efficiency gains, not compliance. Can be deferred if resources tight.

**Supporting evidence:** Evidence 1, 2 [A] — both are efficiency plays.

**Contradicting evidence:** Competitor RepProX/Spotio have both; could be competitive parity requirement.

**Verification step:** Competitive analysis in v1.2 planning; survey top 3 target clients.

---

### Hypothesis 3 — AI Assistant + Form Builder are "v2 differentiators" requiring significant R&D [Plausibility: High]

**Statement:** AI Assistant (LLM + RAG) and Form Builder (JSON Schema + dynamic Livewire) are 3-6 month projects each. They define v2 "platform" positioning vs. v1 "product".

**Supporting evidence:** Evidence 5, 6 [A] — both listed under v2 in PRD.

**Contradicting evidence:** Could start Form Builder in v1.1 as internal tool, expose in v2.

**Verification step:** Prototype Form Builder JSON Schema engine in v1.1 hack week.

---

### Hypothesis 4 — Gamification is low-risk, high-engagement quick win for v1.2 [Plausibility: Medium]

**Statement:** Gamification (badges, streaks, leaderboards) uses existing event system + Echo. No external deps. High engagement ROI for reps.

**Supporting evidence:** Evidence 4 [A] — listed under v2 but low technical risk.

**Contradicting evidence:** Needs UX design to avoid "gamification fatigue".

**Verification step:** Include in v1.2 sprint 1 as low-risk starter.

---

## Suspected Components

| Component                 | Type              | Files                                                              | Blast Radius                    |
| ------------------------- | ----------------- | ------------------------------------------------------------------ | ------------------------------- |
| ERP Connector Framework   | Integration Layer | New `ErpConnector` interface, adapters (Odoo, SAP, custom)         | Stock sync, PO, Invoice         |
| Route Optimization Engine | Service           | `RouteOptimizationService`, OSRM client, TSP solver                | New page + API                  |
| ZATCA Phase 2 Module      | Compliance        | `ZatcaPhase2Service`, CSID management, crypto stamp, clearance API | Saudi production only           |
| Inter-company Module      | Domain            | `Company` hierarchy, transfer pricing, consolidated reports        | Multi-entity clients            |
| OCR Pipeline              | Service           | `OcrService` (Tesseract/Google Vision), queue job                  | Expense + e-invoice attachments |
| Gamification Engine       | Domain            | `Badge`, `Achievement`, `Leaderboard` models, Event listeners      | All rep actions                 |
| AI Assistant              | Service           | `AiAssistantService`, RAG (pgvector), prompt templates             | New UI (chat panel)             |
| Form Builder              | Platform          | JSON Schema engine, dynamic Livewire component renderer            | New admin page + Rep forms      |

---

## Related Requirements

| Requirement                           | Source                 | Status              |
| ------------------------------------- | ---------------------- | ------------------- |
| STK-3 automated inventory integration | PRD v1.0 §1            | Deferred to v1.2/v2 |
| Route optimization                    | PRD v1.1 v1.2/v2 track | Deferred            |
| OCR / Inter-company / ZATCA           | PRD v1.1 v1.2/v2       | Deferred            |
| Gamification                          | PRD v1.1 v2            | Deferred            |
| AI Assistant                          | PRD v1.1 v2            | Deferred            |
| Form Builder                          | PRD v1.1 v2            | Deferred            |

---

## Recommended Action

**Planning Response:** Option C — Escalate to long-term planning (v1.2+ roadmap)

**Rationale:** All items explicitly deferred to v1.2/v2. No action needed until v1.1 is complete and v1.2 planning begins.

**Specific gaps to address in v1.2 planning:**

1. **ZATCA Phase 2 + Inter-company + OCR** as integrated "Saudi Compliance Module" (highest revenue impact)
2. **Route Optimization + STK-3** as "Efficiency Suite" (competitive parity)
3. **Form Builder** as platform foundation (enables dynamic forms for v2 AI assistant)
4. **Gamification** as v1.2 sprint 1 quick win
5. **AI Assistant** as v2 flagship (requires Form Builder + RAG infrastructure first)

---

## Open Questions

1. **ZATCA timeline:** When does the client need Saudi production? Drives entire v1.2 schedule.
2. **ERP targets:** Which ERP systems for STK-3? (Odoo, SAP B1, Oracle NetSuite, custom?)
3. **Route optimization build vs. buy:** Self-host OSRM (free, DevOps burden) vs Google Maps Routes API (paid, zero ops)?
4. **Multi-entity scope:** How many companies in typical inter-company group? Affects data model complexity.
5. **AI budget:** LLM API costs (OpenAI/Azure) — need client approval for ongoing cost.
6. **Form Builder scope:** Replace all hardcoded forms (Visit, Complaint, Expense, Return) or only new dynamic forms?

---

## Update History

| Version | Date       | Summary of Changes                           |
| ------- | ---------- | -------------------------------------------- |
| 1.0     | 2026-07-19 | Initial cataloging of v1.2/v2 deferred items |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
