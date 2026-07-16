# Jawla (جولة) — Build Guide **v1.1 Amendment** (Competitor-Aligned Phase Plan)
**Status:** Supersedes §8 (Build phases) of *Build Guide v1*. All other sections of v1 (stack §2, schema §4, roles §5, modules §6, business rules §7) remain in force except where amended below.
**Companion document:** *Jawla_Beta_PRD_v1.1* — the two documents share requirement IDs and phase labels. PRD defines *what/why*; this amendment defines *when/how*.
**Inputs:** Client voice messages AM1–AM9 · Competitor research report (RepProX, Spotio, Outfield, BeatRoute) · Jawla reconstruction report (current-state mirror).

---

## 0. Source clarification & method

- No product named "REP IN" appears in either research report. The benchmark competitor used throughout is **RepProX** (closest match, most-cited field-rep app), reinforced by patterns marked *Table-Stakes* / *Must-Have* across Spotio, Outfield, and BeatRoute. Rows in the gap matrix cite the actual source.
- The second research file is a reconstruction of **Jawla itself**, not a competitor. It is used here only as a current-state consistency check. Known inaccuracies in it (Laravel 10 vs locked 13.x; Fortify/Jetstream speculation) are **rejected** — Build Guide v1 §2 remains the stack authority.
- **Override rule (non-negotiable):** any requirement traceable to the client's voice messages (REQ-* in the PRD) is **beta-locked regardless of competitor presence**. Competitor absence never ejects a client-mandated feature. This specifically protects the price-range enforcement engine (AM6/AM9) — none of the competitors have it; it is Jawla's differentiator, not dead weight.

---

## 1. Gap Analysis Matrix (three-way: Competitors × Guide v1 × Client PRD)

Legend: ✅ present · ➖ absent · ◐ partial. **Decision** column is the binding outcome.

### 1.1 In competitors AND in Guide v1 → **beta, benchmark-checked**

| Capability | Competitor evidence | Guide v1 | Client PRD | Decision & benchmark note |
|---|---|---|---|---|
| Daily visit assignment & rep day view | RepProX workflow ("Visit Scheduled") | ✅ Ph4 | ✅ REQ-VST-1/2/3 | **Beta B3.** Benchmark: assignment visible to rep before day start; master schedule for manager |
| GPS check-in / geofence arrival | RepProX ("Location Validated"); Table-Stakes | ✅ Ph5 (1 km) | ✅ REQ-VST-5/6 | **Beta B3.** Exceed benchmark: show a **visit stepper** (Scheduled → Arrived → Report → Done) mirroring RepProX's state language |
| On-site order/invoice creation | RepProX; Table-Stakes ("orders on/off-site") | ✅ Ph8 | ◐ (AM9 proforma) | **Beta B5** (simplified — see §2) |
| Payment/collection recording | RepProX ("records payment") | ✅ Ph9 | ➖ | **Beta B5** (cash/cheque/transfer; receipt photo optional v1.0) |
| Returns processing | RepProX ("or returns") | ✅ Ph9 | ➖ | **v1.0.** In competitors but absent from client's beta definition; medium complexity. Risk logged §4 |
| Manager dashboard w/ charts | RepProX admin (sales vs cash bars); Must-Have | ✅ Ph16 (late) | ◐ (AM2 visibility) | **Pull minimal widget set into Beta B6:** visits today, pending quotations, open alarms, sales today. Full reports stay v1.0 |
| Customer mgmt + map | All competitors | ✅ Ph3 (Leaflet) | ✅ REQ-CUS-* | **Beta B2** |
| Check-in/out of workday | Competitor daily flow | ✅ Ph4 (work_sessions) | ➖ | **Beta B3** (already cheap, anchors the day loop) |
| Search & filters on lists | Spotio (filter/search); Table-Stakes | ◐ (Filament free on admin) | ➖ | **Beta:** admin free via Filament; add product/customer search in rep PWA (B3/B5) |
| Excel export of reports | Table-Stakes-adjacent (Nice-to-Have, Low) | ✅ Ph16 | ➖ | **v1.0** with reports; Filament export trivial |

### 1.2 In competitors, NOT in Guide v1 → **evaluated; essentials integrated**

| Capability | Competitor evidence & priority | Verdict | Justification | Placement |
|---|---|---|---|---|
| **Signature capture** | RepProX; report marks *Must-Have, Low complexity* | **ADD to beta** | Completes proof-of-visit/delivery chain with GPS; trivial (canvas → PNG). Schema amendment §3.1 | **B3** (visit report) + **B5** (invoice) |
| **Offline operation + sync** | RepProX ("Offline Operation → Data Synced"); *Must-Have, High* across sources | **Partial beta package + v1.1 architecture decision** | Full offline-first **conflicts with the locked Livewire stack** (server-rendered UI needs connectivity). Re-architecting now would blow the Wed/Thu commitment. Beta ships a graceful-degradation package (§2 B3): connection indicator, localStorage **draft autosave** for visit reports & invoices, submission retry queue, cached read-only day data. The real offline decision (Livewire vs API+shell) is the **single biggest competitive risk** — scheduled as v1.1 spike. Risk register §4 | **B3 (partial)** → **v1.1 (decision)** |
| Empty / error / loading states, skeletons | NN/g-cited; *Table-Stakes* | **ADD to beta as UI standard** | Cheap; drives perceived quality; defined once in §5 and applied everywhere | **B0** standard, all phases |
| Bottom tab bar (rep app) | Table-Stakes pattern across field apps | **ADD to beta** | Replaces ad-hoc nav in Ph4 shell: Home · Visits · Customers · Orders · More | **B3** |
| "Save draft" / continued sessions | *Must-Have, High* | **Beta (client-side)** | Folded into the offline package: autosave per form key; survives app kill | **B3** |
| Google Maps deep-link per customer | Route tools *Must-Have* | **ADD to beta** | One-line `geo:`/gmaps intent from customer card = turn-by-turn for free. Full route optimization stays out | **B3** |
| WhatsApp share of proforma/invoice PDF | Report: share via WhatsApp (*Nice-to-Have, Low*) | **ADD to beta** | Disproportionate value in the Egyptian market; `wa.me` share link, zero API cost | **B4/B5** |
| Push notifications | *Must-Have, Medium* | **v1.1** | Beta covers alerting via in-app alarm bell + red indicators (client's actual ask, AM4). Web-push on iOS PWA is unreliable; do it properly in v1.1 | v1.1 |
| Onboarding walkthrough | *Must-Have, Low* | **v1.1** | ~10 users, personally trained by the implementer; a walkthrough adds nothing to beta demo | v1.1 |
| Route optimization / turn-by-turn in-app | *Must-Have, High* | **v1.2+** | High complexity; deep-link covers 80% of field need meanwhile | v1.2 |
| Barcode/QR product lookup | RepProX | **v1.1** (already §12.2 guide) | Low value for ton-unit polymer trading vs FMCG; cheap later | v1.1 |
| Business card OCR | Outfield; *Nice-to-Have* | v1.2 | Marginal for B2B factory customers | v1.2 |
| ERP/accounting **live sync** | *Must-Have, High* per report | **v1.1+** | Client is migrating **off** Odoo onto Jawla (guide Ph17 = one-time migration, stays v1.0). Live sync target (if any) unknown → discovery item | v1.1+ |
| Biometric / 2FA login | *Must-Have if 2FA, Medium* | v1.1 | Sanctum sessions adequate for 10 users in beta | v1.1 |
| Gamification / leaderboards | Outfield, BeatRoute; *Nice-to-Have* | v2 | Differentiator-class, not core ops | v2 |
| AI assistant (Spotio DASH-style) | *Nice-to-Have/Differentiator, High* | v2 | Revisit after data accumulates | v2 |
| Custom form/field builder | Outfield; *Nice-to-Have, High* | v2 | ERP-grade config; premature | v2 |
| Dark mode | *Nice-to-Have* | **Beta (admin only)** — Filament ships it free (guide §3 already says enable) | rep app dark mode v1.1 | B2 / v1.1 |
| Bulk actions (multi-select) | Spotio; *Nice-to-Have* | v1.1 | Filament gives admin bulk free; rep app later | v1.1 |

### 1.3 In Guide v1, NOT in competitors → **deprioritized to post-beta** (client-locked items exempt)

| Guide v1 feature | Client-locked? | Decision | Rationale & risk (see §4) |
|---|---|---|---|
| Price-range enforcement chain (Ph6–7) | **YES (AM2/AM6/AM9)** | **BETA — exempt from rule** | The differentiator. Absent from every competitor = moat, not bloat |
| Stock CSV import + rep stock lookup (Ph3 part) | **YES (AM2)** | **BETA — exempt** | Client's explicit interim process |
| Purchase requests + dual review (Ph10 part) | **YES (AM3)** | **BETA (submission + approve/veto queue)** | Supplier quotation comparison & POs → v1.0 |
| Out-of-stock alarms + complaints (Ph13) | **YES (AM4/AM7)** | **BETA — exempt** | Client's "alarm" language is emphatic |
| Goods in transit (Ph11) | no (profile-derived) | **v1.0** | Not in voice messages, not in competitors. Risk R3: rep stock view incomplete for 90%-import business → mitigate: transit qty shown read-only via import file column |
| Landed cost (Ph11) | no | **v1.0** | Finance continues current method meanwhile |
| Batch/COA/expiry (Ph12) | no | **v1.0** | Risk R4: invoices created in beta lack batch links → mitigate: `batch_id` stays **nullable** on item tables from day one (schema unchanged), backfill wizard in v1.0 |
| Cash box reconciliation UI (Ph9 part) | no | **Ledger in beta, UI in v1.0** | Hard rule §7 v1 (sale = invoice+stock+cash box in one transaction) still enforced — the *records* exist in beta; the reconciliation screens come in v1.0 |
| Expenses, van transfers (Ph9/§6) | no | **v1.0** | Zero competitor or client signal for beta |
| Supplier quotation comparison + POs + partial receipts (Ph10 rest) | no | **v1.0** | Purchasing dept can work manually for weeks |
| Egypt ETA **full** e-invoicing (Ph14) | no (but legal) | **QR + sequential numbering in BETA; full ETA integration v1.0 before go-live** | Risk R5: issuing real invoices without ETA = compliance exposure → beta demo uses proforma emphasis; hard gate before production invoices |
| Inter-company / Saudi entity (Ph15) | no (client deferred) | **v2** | Unchanged |
| Odoo/Excel data migration (Ph17) | no | **v1.0 (pre-go-live gate)** | Demo uses seed data; migration is a launch blocker, not a beta blocker |
| Full reports suite + visit map (Ph16) | partial (AM2 visibility) | **Minimal widgets B6; full suite v1.0** | Client's AM2 visibility need met by B6 + document lists |

---

## 2. Revised Phase Plan

### BETA track (target: demonstrable slice for the client's Wednesday/Thursday answer, then 2–3 week hardening)

| Phase | Maps to v1 | Scope | Definition of Done |
|---|---|---|---|
| **B0 — Setup + UI standards** | Ph0 (+new) | v1 Ph0 tasks **plus** the §5 design-standard kit: state components (empty/error/loading skeleton), bottom-tab shell scaffold, RTL type scale, alarm-red tokens | App boots; a styleguide route renders all standard states in AR/EN RTL |
| **B1 — Full schema + auth** | Ph1 + Ph2 | **All 45 tables** (schema-first: deferred modules get tables now, UI later — prevents migration debt) + §3.1 amendment columns. 7 roles, panel gating | `migrate:fresh` clean; role-gated logins work |
| **B2 — Admin master data (trimmed)** | Ph3 | Company (Egypt, bank accounts REQ-INV-4), users, categories/products (cost hidden), suppliers, routes, customers+Leaflet+**approval queue**, warehouse stock + **CSV import** (REQ-STK-1/2). Enable Filament dark mode. *Cut from beta:* transit-warehouse ops UI | Admin creates all master data; imports client's sample stock file; approves a pending customer |
| **B3 — Rep day loop** | Ph4 + Ph5 (+new) | Bottom tabs; Start Work; assigned visits (REQ-VST-1/3); **visit stepper** with GPS geofence (REQ-VST-5/6) incl. out-of-range + GPS-denied fallbacks (§5); visit report + **signature capture**; add-customer (pending, REQ-CUS-1/2); **draft autosave + connection indicator + retry queue**; customer search; **Maps deep-link** | Rep completes a full visit on a phone incl. signature; kills the app mid-report and loses nothing; out-of-range path produces flagged confirmation |
| **B4 — Pricing chain + proforma** | Ph6 + Ph7 | Quotation request → manager "Requested" queue → approved price + ± range (REQ-PRC-4/5/6/7); **floor enforcement** on proforma (REQ-INV-3, pending Q1/Q2 answers — build the validator range-shape-configurable); bank details injection; **WhatsApp share** of PDF | The 850-below-900 case is rejected server-side; proforma PDF shares via wa.me |
| **B5 — Sales invoice + collections (simplified)** | Ph8 + Ph9 part | Invoice from proforma/direct; **atomic transaction** (v1 hard rules 1&2 intact: stock never negative, invoice+stock+cash box or rollback); bilingual PDF + QR + sequential numbering; batch selection **skipped** (nullable); collections (cash/cheque/transfer); cash-box ledger (no recon UI); product stock lookup for reps (REQ-STK-4/5); rep signature on invoice | Oversell blocked; invoice PDF QR scans; payment recorded; rep sees live stock incl. read-only transit column |
| **B6 — Alarms + minimal dashboard** | Ph13 + Ph16 slice | Out-of-stock urgent request (REQ-ALM-1/2/3: broadcast Finance+Manager+Executive, payload "Rep X / Material Y"); complaints→alarm (REQ-CRM-1/2/3); manager acknowledge→resolve; red indicators; dashboard widgets: visits today · pending quotations · open alarms · sales today | Flagging out-of-stock lights up all three roles; manager resolves; widgets live |
| **B7 — Purchase requests (client slice)** | Ph10 part | Rep submits supplier offer (REQ-PUR-1/2); dual-review queue with Sales veto + Purchasing approve (REQ-PUR-3/4, mechanics pending Q5) | Offer flows to both queues; veto kills it |
| **B8 — Seed, demo script, QA** | Ph19 + Ph18 min | v1 Ph19 seeder trimmed to beta modules; manifest.json + installable shell; **demo script mirroring AM1→AM9 in order**; regression pass on the two hard rules | `migrate:fresh --seed` → the full client story is walkable start-to-finish on a phone |

### v1.0 track (post-beta → go-live)
Sequence: **(1)** Returns + cash-box recon UI + expenses + van transfers → **(2)** Supplier quotation comparison + POs + partial receipts → **(3)** Goods in transit + landed cost → **(4)** Batch/COA/expiry + invoice-batch backfill wizard → **(5)** ETA full compliance gate → **(6)** Full reports/exports/visit map → **(7)** Odoo/Excel migration + go-live cutover. Rationale: money-handling completeness first, then supply chain, then compliance, then launch.

### v1.1 track
Offline architecture spike & decision (Livewire vs API+shell — **top item**), web push, onboarding walkthrough, barcode lookup, biometric/2FA, rep-app dark mode, bulk actions, accounting-sync discovery.

### v1.2 / v2 track
Route optimization, business-card OCR (v1.2) · Inter-company + Saudi entity + ZATCA, gamification, AI assistant, custom form builder (v2).

---

## 3. Schema & rules amendments (delta to v1 §4/§7)

**3.1 New columns (add in B1):** `visit_reports.signature_path` (nullable string) · `invoices.signature_path` (nullable string) · `visits.arrival_flag` enum(`in_range`,`out_of_range_confirmed`,`gps_denied`) default `in_range` · `activities` audit table (id, user_id, action, subject_type, subject_id, meta json, created_at) — recommendation adopted from the reconstruction report §IX.
**3.2 Unchanged but re-affirmed:** `batch_id` nullable on all item tables (enables beta-without-batches, v1.0 backfill). Append-only `stock_movements`. The two hard rules of v1 §0.3 apply to beta B5 exactly as written.
**3.3 Pricing validator (B4):** implement range check as a strategy object configurable for **floor-only** or **two-sided** — the client's Q1/Q2 contradiction (1000 ±100 vs "start at 1200"; "900 and above") means the shape must be switchable without refactor.

---

## 4. Risk register (created by deprioritizations)

| # | Risk | Caused by | Mitigation |
|---|---|---|---|
| R1 | **Offline gap vs competitors** — reps in dead zones can't transact | Livewire stack vs Must-Have offline | Beta degradation package (B3); v1.1 architecture spike is mandatory, not optional |
| R2 | Draft autosave is client-side only (localStorage) — device loss loses drafts | B3 scope | Acceptable for beta; server drafts in v1.1 |
| R3 | Rep stock view blind to in-transit goods (90% import business) | Ph11 → v1.0 | Read-only transit column in the daily import file (B5) |
| R4 | Beta invoices carry no batch links | Ph12 → v1.0 | Nullable `batch_id` + v1.0 backfill wizard |
| R5 | Real invoices before ETA integration = compliance exposure | Ph14 full → v1.0 | Beta = proforma-first demo; **production invoicing gated on ETA milestone** |
| R6 | Pricing engine built on unresolved Q1/Q2 | Client contradiction | Strategy-object validator (§3.3); chase answers this week |
| R7 | Cash handling half-visible (ledger without recon UI) | Ph9 split | Ledger is transactionally correct from day one; UI-only debt |

---

## 5. Design recommendations (applied standards)

**Interface** — Bottom tab bar (Home · Visits · Customers · Orders · More) per Table-Stakes pattern; card lists with bold title/status chip/chevron (RepProX pattern); skeleton loaders instead of spinners; explicit empty states with action ("لا توجد زيارات اليوم" + refresh); success toast + subtle check animation on submit (celebration-class animations deferred); alarm red reserved exclusively for urgent items per v1 §3; keep v1 palette/typography (GPC teal/blue, Noto Kufi Arabic) — competitor blue/green trend confirms it; high-contrast AR/EN with full RTL flip on language switch; admin dark mode on (Filament built-in).

**Workflow logic** — Visit is a visible **stepper state machine**: Scheduled → Arrived (GPS) → Report → Done, mirroring RepProX's "Visit Scheduled → Location Validated" language; **out-of-range behavior (proposed answer to Q3):** allow "Confirm anyway" → `arrival_flag=out_of_range_confirmed` → auto-notifies manager (never silently block a rep standing in a huge industrial zone); **GPS-denied:** capture-with-flag + prompt to enable; conflict policy documented as **last-write-wins** (Salesforce precedent) until v1.1 offline work revisits; every destructive/irreversible action gets a confirm dialog; form validation blocks progression with inline Arabic-first messages.

**Data architecture** — Schema-first (all 45 tables in B1) so post-beta phases are UI work, not migrations; append-only `stock_movements` as the audit spine; new `activities` table for login/price-change/user-edit audit; signatures stored as files with path columns (§3.1); PDF artifacts (proforma/invoice) written once and stored, never re-rendered for history; all money `decimal(12,2)` per v1 — reconstruction report's rounding warning re-affirmed.

---

*End of amendment. Build order: B0 → B8, then v1.0 (1)→(7). The companion PRD v1.1 carries the requirement-level detail and the open client questions (Q1–Q10) — Q1/Q2/Q3/Q4 remain the only external blockers.*
