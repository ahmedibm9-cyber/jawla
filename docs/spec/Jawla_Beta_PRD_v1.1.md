# Jawla (جولة) — Beta PRD **v1.1** (Competitor-Aligned)
**Supersedes:** Beta PRD v1.0 (voice-message extraction). All REQ-* IDs from v1.0 are preserved unchanged; this version adds competitor-derived requirements (REQ-CMP-*), assigns every requirement to a build phase, and re-states scope boundaries.
**Companion document:** *Jawla_Build_Guide_v1.1_Amendment* — shares phase labels **B0–B8 / v1.0 / v1.1 / v1.2 / v2**. The amendment owns sequencing, schema deltas, risks, and design standards; this PRD owns requirements and client traceability.
**Sources:** Client voice messages AM1–AM9 (unchanged authority for client intent) · Competitor research (RepProX, Spotio, Outfield, BeatRoute) · Note: no competitor named "REP IN" exists in the research; RepProX is the adopted benchmark.

**Scope-lock rule:** Client-mandated requirements (AM-traceable) are beta-locked and immune to competitor-based deprioritization. Competitor research *adds* to beta or *defers guide-only* features; it never removes client asks.

---

## 1. Beta scope at a glance (what ships in B0–B8)

**Client-mandated core (unchanged from v1.0):** roles & admin-managed permissions (REQ-ROL-1…8) · daily visit assignment + master schedule + rep day view (REQ-VST-1/2/3) · customer GPS record + geofenced Confirmed Arrival + visit report (REQ-VST-4/5/6/7) · field customer creation with manager approval (REQ-CUS-1/2/3/4) · Finance base price → manager range → rep negotiation floor (REQ-PRC-1/2/4/5/6/7/8) · proforma with hard price enforcement + auto bank details (REQ-INV-1/2/3/4) · warehouse CSV import + rep live stock lookup (REQ-STK-1/2/4/5) · purchase-offer submission + Sales/Purchasing dual review (REQ-PUR-1/2/3/4) · out-of-stock alarms broadcast to Finance/Manager/Executive (REQ-ALM-1/2/3/4) · complaints→alarm→manager (REQ-CRM-1/2/3) · cross-role visibility of reports/quotations/proformas + Finance section (REQ-RPT-1/2/3).

**Competitor-derived beta additions (new in v1.1):** see §2.

**Explicitly still deferred by the client (unchanged):** multi-currency (AM3/AM5) · nested range delegation mechanics (REQ-PRC-3) · automated inventory integration (REQ-STK-3) · exact ± limit values (configuration, not constants).

---

## 2. NEW — Competitor-derived beta requirements (REQ-CMP-*)

| ID | Requirement | Competitor evidence | Phase | Priority |
|---|---|---|---|---|
| REQ-CMP-1 | **Signature capture** on visit report and on invoice (canvas → stored image) | RepProX; research marks *Must-Have, Low complexity* | B3, B5 | M |
| REQ-CMP-2 | **Visit stepper UI** — visible state machine: Scheduled → Arrived (GPS) → Report → Done | RepProX "Visit Scheduled → Location Validated" flow | B3 | M |
| REQ-CMP-3 | **Connection-aware degradation package:** offline indicator, localStorage **draft autosave** for visit reports & invoices, submission retry queue, cached read-only day data | Offline/sync = *Must-Have* across sources; full offline deferred (stack conflict — see Amendment R1) | B3 | M |
| REQ-CMP-4 | **Bottom tab bar** rep navigation (Home · Visits · Customers · Orders · More) | *Table-Stakes* navigation pattern | B3 | M |
| REQ-CMP-5 | **Standard UI states everywhere:** skeleton loading, explicit empty states with action, inline Arabic-first validation, success toasts | NN/g-cited; *Table-Stakes* | B0 (standard), all | M |
| REQ-CMP-6 | **Maps deep-link** from customer card (turn-by-turn via Google Maps intent) | Route tools *Must-Have*; full optimization deferred | B3 | M |
| REQ-CMP-7 | **WhatsApp share** of proforma/invoice PDF (`wa.me` link) | Report share option; disproportionate Egypt-market value | B4, B5 | M |
| REQ-CMP-8 | **Minimal manager dashboard widgets:** visits today · pending quotations · open alarms · sales today | RepProX admin dashboard benchmark (*Must-Have* for admin) | B6 | M |
| REQ-CMP-9 | Rep-app **search** on customers and products | Spotio filter/search; *Table-Stakes* | B3, B5 | M |
| REQ-CMP-10 | **GPS edge-case handling:** out-of-range → "Confirm anyway" with `out_of_range_confirmed` flag + auto manager notification; GPS-denied → flagged capture + enable prompt | Research edge-case list ("Permission Denied") + proposed answer to open Q3 | B3 | M |
| REQ-CMP-11 | Admin **dark mode** (Filament built-in) | *Advanced UX expectation*; zero cost | B2 | S |
| REQ-CMP-12 | **Sales invoice + collections (simplified):** invoice from proforma/direct under the two hard rules (no oversell; atomic invoice+stock+cash), bilingual PDF + QR + sequential number; collections cash/cheque/transfer; cash-box ledger (no recon UI) | RepProX day flow includes order → payment; aligns AM9 continuation | B5 | M |

**Deliberately NOT added to beta despite competitor presence** (full rationale in Amendment §1.2): full offline-first (v1.1 architecture spike), push notifications (v1.1 — in-app alarm bell covers AM4 intent), onboarding walkthrough (v1.1), route optimization (v1.2), barcode lookup (v1.1), returns processing (v1.0), gamification / AI assistant / custom form builder (v2), accounting live sync (v1.1+ discovery), biometric/2FA (v1.1).

---

## 3. Requirement → Phase compatibility map

| Phase | Client REQ-* | Competitor REQ-CMP-* |
|---|---|---|
| **B0** | — | CMP-5 (UI-state standard kit) |
| **B1** | ROL-1…8 (roles seeded; admin-managed view permissions) | — (schema incl. signature/flag/activities columns per Amendment §3.1) |
| **B2** | CUS-3 (approval queue), STK-1/2 (import), INV-4 (bank accounts), PRC-1 (base price), RPT-3 (Finance section) | CMP-11 |
| **B3** | VST-1…7, CUS-1/2/4 | CMP-1 (visit), CMP-2, CMP-3, CMP-4, CMP-6, CMP-9 (customers), CMP-10 |
| **B4** | PRC-2/4/5/6/7/8, INV-1/2/3/4 | CMP-7 (proforma) |
| **B5** | STK-4/5, INV continuation | CMP-1 (invoice), CMP-7, CMP-9 (products), CMP-12 |
| **B6** | ALM-1…4, CRM-1…3, RPT-1/2 | CMP-8 |
| **B7** | PUR-1…4 | — |
| **B8** | Demo of AM1→AM9 end-to-end | Regression on hard rules + states |
| **v1.0** | — (client deferrals stay deferred) | Returns, cash recon UI, expenses, van transfers, supplier comparison+POs, transit+landed cost, batch/COA/expiry+backfill, ETA full compliance (**go-live gate**), full reports/exports/map, Odoo migration |
| **v1.1** | PRC-3 revisit (nested ranges) if client re-opens | Offline architecture decision (**top priority**), push, onboarding, barcode, biometric, rep dark mode, bulk actions, sync discovery |
| **v1.2 / v2** | STK-3 (automation) lands with integration work | Route optimization, OCR / inter-company+Saudi+ZATCA, gamification, AI, form builder |

Traceability is bidirectional: every beta phase closes specific REQ IDs; every REQ ID appears in exactly one beta phase or a named later track.

---

## 4. Technical specifications (v1.0 list, amended)

Unchanged from PRD v1.0: TEC-1 GPS subsystem (now with CMP-10 edge behavior) · TEC-2 pricing enforcement (**validator must be range-shape-configurable** — floor-only vs two-sided — until Q1/Q2 are answered; Amendment §3.3) · TEC-3 alarm broadcast · TEC-4 import pipeline (file format still pending Q4; **now must include a read-only in-transit quantity column** per Amendment R3) · TEC-5 EGP-only · TEC-6 central bank details · TEC-7 admin-managed permissions · TEC-8 mobile-first field usage.
New: **TEC-9** signature storage (file + path columns) · **TEC-10** client-side draft persistence keyed per form + retry queue · **TEC-11** `activities` audit table (login, price changes, user edits) · **TEC-12** last-write-wins conflict policy documented for all rep submissions (revisited in v1.1 offline work).

## 5. UX requirements (amended)

All PRD v1.0 UX requirements stand (mobile-first rep loop, queue-driven manager, loud alarms, read-heavy Finance/Executive). Added: the **Amendment §5 design standards are binding for beta** — bottom tabs, card lists, stepper, skeletons, empty/error states, Arabic-first microcopy, GPC palette with alarm-red exclusivity, RTL/LTR flip, admin dark mode.

## 6. Open questions (carried forward — status)

| # | Status |
|---|---|
| ⛔ Q1 range math (1000±100 vs 1200) | **Still blocking the validator's final shape** — mitigated by configurable strategy (build proceeds), answer needed before client UAT |
| ⛔ Q2 proforma upper bound | Same mitigation as Q1 |
| ⛔ Q3 geofence behavior | **Proposed answer embedded as REQ-CMP-10** (confirm-anyway + flag + notify). Needs client sign-off, no longer blocks build |
| ⛔ Q4 stock file sample | **Still blocking B2 importer finalization** — chase immediately; importer scaffolds against a mock meanwhile |
| ▫ Q5–Q10 | Unchanged; Q5 (dual-review mechanics) shapes B7 polish only |

---

## 7. Definition of Beta Done (client-facing)

A phone-in-hand walkthrough that reproduces the client's own AM1→AM9 narrative in order: manager assigns 5 visits → rep starts day → GPS-confirmed arrival (incl. one out-of-range case) → signed visit report that survives an app kill → new customer pending → manager approves → rep requests price → manager sets 1000 ± range → rep issues proforma at 950 (accepted) and 850 (**system-rejected**) → proforma carries bank details, shares via WhatsApp → invoice with QR, stock deducted atomically, payment collected → rep flags Material 952 out-of-stock → red alarm hits Finance + Manager + Executive simultaneously → complaint logged → manager resolves → dashboard widgets reflect the day. Everything bilingual AR/EN, RTL-correct, on seed data.

*End of PRD v1.1. Change log: +12 REQ-CMP requirements · +4 TEC specs · phase map added · Q3 answered-pending-sign-off · zero client requirements removed or weakened.*
