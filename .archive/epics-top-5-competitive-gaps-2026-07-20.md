# Epics — Top 5 Competitive Gaps

## CG1 — Portable Field Printing

**Goal:** Match and exceed REP IN's portable-printer field invoicing with Bluetooth print support for invoices and receipts in the rep app.

**Why it matters:** Biggest field-demo gap. Egyptian FMCG distribution expects same-moment printed proof in the van.

**Stories:**
- CG1.1 Bluetooth print transport
- CG1.2 Thermal receipt / invoice templates
- CG1.3 Rep workflow integration + device certification

---

## CG2 — True Offline Transactions

**Goal:** Move from draft-only offline support to real offline transaction capture with safe sync and conflict handling.

**Why it matters:** Connectivity in field routes is unreliable. Draft autosave is not enough to claim offline readiness.

**Stories:**
- CG2.1 Offline data model + IndexedDB persistence
- CG2.2 Sync queue + idempotent API contract
- CG2.3 Conflict UX + observability

---

## CG3 — Live Rep Tracking

**Goal:** Give managers a live, map-based view of active reps during work sessions.

**Why it matters:** REP IN's strongest promise is real-time field visibility; Jawla currently records location events but does not stream the day.

**Stories:**
- CG3.1 Rep location ping pipeline
- CG3.2 Manager live map + alerts
- CG3.3 Privacy, battery, and control settings

---

## CG4 — API + ERP Ecosystem

**Goal:** Ship a reusable integration platform instead of one-off custom sync, starting with public API/webhooks and an Odoo connector.

**Why it matters:** REP IN wins enterprise conversations by saying it integrates with ERP systems. Jawla can leapfrog by being more open and documented.

**Stories:**
- CG4.1 Public API foundation
- CG4.2 Webhooks + integration docs
- CG4.3 Odoo connector MVP

---

## CG5 — Sales Targets & Attainment

**Goal:** Add manager-set targets and rep attainment reporting powered by existing invoices, collections, and visit data.

**Why it matters:** Small build, large manager value. Closes a classic SFA capability gap quickly.

**Stories:**
- CG5.1 Schema + policies
- CG5.2 Attainment engine + manager UI
- CG5.3 Rep progress + reports
