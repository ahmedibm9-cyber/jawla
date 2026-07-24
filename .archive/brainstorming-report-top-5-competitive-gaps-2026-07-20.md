# Brainstorming Report — Top 5 Competitive Gaps

**Date:** 2026-07-20  
**Intent:** Create  
**Topic:** Turn the top 5 competitive gaps from `docs/competitor-research-2026-07-20-rep-in.md` into actionable epics and stories for Jawla.

---

## Context

Jawla already matches or exceeds REP IN on many core field-sales capabilities, but still trails on five strategic gaps:

1. Portable Bluetooth field printing
2. True offline transactions and sync
3. Live rep tracking for managers
4. Public API + ERP integration framework (Odoo first)
5. Sales targets and attainment tracking

These gaps matter because they shape field usability, manager visibility, enterprise readiness, and competitive trust in sales cycles.

---

## Techniques Used

### 1. Mind Mapping
Used to break each gap into execution layers:
- platform/core infrastructure
- workflow/UI
- observability and rollout

### 2. Reverse Brainstorming
Used to surface failure modes:
- printing that works only on one printer model
- offline queue duplicating money/stock writes
- tracking that drains battery or feels invasive
- integrations that become one-off client projects instead of a reusable platform
- targets that do not reconcile with invoice/payment truth

### 3. Starbursting
Used to expose implementation questions:
- Who owns each capability?
- What is the smallest useful release?
- When does it become sales-usable?
- Where are tenant, security, and compliance boundaries?
- Why would the user trust it?
- How do we prove it works with automated and manual evidence?

---

## Chosen Epics

| Epic | Gap | Impact | Feasibility | Priority |
|---|---|---|---|---|
| **CG1** | Portable field printing | High | Medium | P1 |
| **CG2** | True offline transactions | Very High | Low/Medium | P1 |
| **CG3** | Live rep tracking | High | Medium | P1 |
| **CG4** | API + ERP ecosystem | High | Medium | P2 |
| **CG5** | Sales targets & attainment | Medium/High | High | P2 |

---

## Story Map

### Epic CG1 — Portable Field Printing
- `CG1.1.bluetooth-print-transport.story.md`
- `CG1.2.thermal-receipt-templates.story.md`
- `CG1.3.rep-print-workflow-and-device-certification.story.md`

### Epic CG2 — True Offline Transactions
- `CG2.1.offline-data-model-and-indexeddb.story.md`
- `CG2.2.sync-queue-and-idempotent-api.story.md`
- `CG2.3.offline-conflict-ux-and-observability.story.md`

### Epic CG3 — Live Rep Tracking
- `CG3.1.rep-location-ping-pipeline.story.md`
- `CG3.2.manager-live-map-and-alerts.story.md`
- `CG3.3.location-privacy-battery-controls.story.md`

### Epic CG4 — API + ERP Ecosystem
- `CG4.1.public-api-foundation.story.md`
- `CG4.2.webhooks-and-integration-docs.story.md`
- `CG4.3.odoo-connector-mvp.story.md`

### Epic CG5 — Sales Targets & Attainment
- `CG5.1.sales-targets-schema-and-policies.story.md`
- `CG5.2.attainment-engine-and-manager-ui.story.md`
- `CG5.3.rep-target-progress-and-reports.story.md`

---

## Top Insights

1. **Do not treat these as five unrelated features.** They cluster into three product advantages: field execution (CG1 + CG2), manager visibility (CG3 + CG5), and enterprise/platform trust (CG4).
2. **Offline is architecture, not a feature toggle.** It must be split into storage, sync, and conflict-handling stories or it will sprawl and break financial integrity.
3. **Printing should ship before full offline.** It closes the most visible field gap quickly and is operationally independent of server sync.
4. **API before Odoo.** A clean public API and webhook layer prevents the Odoo integration from becoming bespoke technical debt.
5. **Targets are cheap leverage.** They raise manager-perceived value fast because Jawla already has visits, invoices, and collections as truth sources.

---

## Risks

| Risk | Affected Epic(s) | Mitigation |
|---|---|---|
| Hardware fragmentation | CG1 | Start with Android Chrome + 2–3 ESC/POS printer profiles |
| Duplicate offline writes | CG2 | Idempotency keys + server-side replay protection |
| Privacy concerns from location streaming | CG3 | Session-scoped tracking + visible rep indicator + company policy toggle |
| Integration scope creep | CG4 | Odoo-only MVP behind generic connector interface |
| Misaligned quota math | CG5 | Base attainment only on persisted invoices/payments/visits, never drafts |

---

## Recommended Sequencing

### Q1
- CG1 Portable Field Printing
- CG5 Sales Targets & Attainment
- CG3.1 basic location ping foundation

### Q2
- CG2 True Offline Transactions
- CG3.2 Live Map + Alerts
- CG3.3 Privacy/Battery controls

### Q3
- CG4 Public API + Webhooks
- CG4.3 Odoo Connector MVP

### Q4
- Hardening, partner docs, broader ERP adapters, route-optimization tie-ins

---

## Recommended Next Step

Start implementation planning or story execution in this order:

1. `CG1.1` → `CG1.2` → `CG1.3`
2. `CG5.1` → `CG5.2` → `CG5.3`
3. `CG2.1` → `CG2.2` → `CG2.3`
4. `CG3.1` → `CG3.2` → `CG3.3`
5. `CG4.1` → `CG4.2` → `CG4.3`

That order delivers fastest visible differentiation first, then deeper platform advantage.
