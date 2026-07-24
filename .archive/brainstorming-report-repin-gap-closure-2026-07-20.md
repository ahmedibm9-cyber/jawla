# Brainstorming Report — REP IN Feature-Gap Closure Plan

**Date:** 2026-07-20
**Topic:** Close the feature gaps where REP IN (biggest competitor) has capabilities Jawla lacks.
**Source of competitor facts:** `docs/competitor-research-2026-07-20-rep-in.md`
**Method:** Verified REP IN's documented feature set against Jawla's *actual current codebase* (post-V1.0 build), then applied a competitive gap matrix (SWOT-derived), mind-map grouping into epics, and Impact×Feasibility prioritization.

---

## 1. Verified gap matrix (REP IN → Jawla, current state)

| REP IN capability | Jawla today | Gap? |
|---|---|---|
| Warehouse / van stock management | Stock + StockService + van warehouses | ✅ have |
| Order + return capture | Sales Flow + Log Return | ✅ have |
| Collections + account statements | Payments + customer balance | ✅ have |
| Portable / thermal invoice printing | **CG1 Bluetooth print (built)** | ✅ have |
| Map location + directions | GPS geofence, directions, **Customer Map** | ✅ have |
| Customer location registration on map | Add Customer w/ Leaflet picker | ✅ have |
| Dynamic role permissions | spatie/permission, 7 roles | ✅ have |
| Shift start/end logging | Work sessions | ✅ have |
| Task notifications | Notification bell + feed | ✅ have |
| Signature capture | Visit report signature | ✅ have |
| Sales reporting | ReportsPage + widgets + exports | ✅ have (≈80%) |
| **Offline data storage / true offline txns** | localStorage drafts only | ❌ **GAP (CG2)** |
| **Live rep tracking / real-time route monitoring** | check-in/out GPS + static map | ❌ **GAP (CG3)** |
| **ERP integration (Odoo/SAP/Oracle/Dynamics) + API** | none (no `routes/api.php`, no webhooks) | ❌ **GAP (CG4)** |
| **Targets by rep/supervisor/region + attainment** | no target model | ❌ **GAP (CG5)** |
| **Barcode reading** | none | ❌ **GAP (minor)** |
| **Photo capture (receipts / products)** | none in rep flows | ❌ **GAP (minor)** |
| AI in field work (route opt, suggestions) | none | ❌ gap (REP IN also weak — marketing only) |

**Net: 6 real feature gaps** (4 major = CG2–CG5, 2 minor = barcode + photo), plus AI/route-optimization as a leapfrog opportunity where REP IN itself is weak.

> Strategic note from the research: REP IN's *biggest* weaknesses are **trust, pricing transparency, and product-ops** (stale iOS build, broken Android link, privacy mismatch), not features. Feature parity is necessary but the decisive wins are also GTM. This report scopes **features**; §6 flags the GTM levers.

---

## 2. Mind-map: gaps grouped into epics

```
Beat REP IN
├── Field capability (reps feel it)
│   ├── CG2 True offline transactions  ← highest differentiator
│   ├── Barcode scan → product lookup   (quick win)
│   └── Photo capture (receipt/product) (quick win)
├── Manager visibility (buyers pay for it)
│   ├── CG3 Live rep tracking + live map
│   └── CG5 Sales targets & attainment
└── Enterprise / ecosystem (deals & stickiness)
    └── CG4 Public API + webhooks + Odoo connector
```

---

## 3. Impact × Feasibility scoring

| Gap | Buyer impact | Feasibility | Notes |
|---|---|---|---|
| CG2 True offline | **Very High** | Low–Med | REP IN's clearest field claim; hard but defining. Service worker + IndexedDB + idempotent sync queue. |
| CG3 Live rep tracking | **High** | Medium | Managers pay for this. Location-ping pipeline + live Leaflet map + battery/privacy controls. Builds on existing GPS. |
| Barcode scan | Medium | **High** | `BarcodeDetector` API / camera → product lookup in Sales Flow. Days, not weeks. |
| Photo capture | Medium | **High** | `getUserMedia`/file input on visit report, complaint, return. Days. |
| CG5 Sales targets | Med–High | Medium | Target model (rep/team/region/period) + attainment engine + manager UI + rep progress. Contained, high demo value. |
| CG4 API + Odoo | High (enterprise) | Medium | Sanctum-token public API + webhooks + Odoo connector MVP. Unlocks the ERP-integration story REP IN only *claims*. |

---

## 4. Recommended closure plan (waves)

**Wave 1 — Field differentiators + quick wins (ship first, reps + demos feel it)**
1. **Barcode scan → product lookup** (quick win; slots into existing Sales Flow autocomplete)
2. **Photo capture** on visit report / complaint / return (quick win; proof-of-presence, matches REP IN)
3. **CG2 True offline transactions** — the flagship differentiator. Sequenced: (a) offline data model + IndexedDB, (b) idempotent sync queue + server dedupe, (c) conflict-resolution UX + observability. *Start the design spike now; it's the longest pole.*

**Wave 2 — Manager visibility (the buyer's cheque-signing features)**
4. **CG3 Live rep tracking** — location-ping pipeline, manager live map + alerts, privacy/battery controls. Reuses the geofence GPS + the new Customer Map.
5. **CG5 Sales targets & attainment** — schema + policies, attainment engine + manager UI, rep progress + reports.

**Wave 3 — Enterprise ecosystem (stickiness & bigger deals)**
6. **CG4 API + ERP** — public API foundation (Sanctum), webhooks + integration docs, **Odoo connector MVP** (Odoo is the ERP REP IN names most). This is where you convert REP IN's "we'll integrate it for you" into "here's the documented platform."

**Wave 4 — Leapfrog (where REP IN is only marketing)**
7. AI/route optimization: route sequencing, smart replenishment suggestions, anomaly detection on collections/variance — visible, measurable AI to beat REP IN's blog-only AI story.

Rationale for ordering: lead with what reps *feel* and demos *show* (offline + barcode + photo), then the manager features that *close deals* (tracking + targets), then the ecosystem features that *retain and upsell* (API/Odoo). Quick wins (barcode, photo) ship in parallel with the CG2 design spike so there's visible momentum while the hard offline work matures.

---

## 5. Top insights

1. **You're closer than the research implies.** Of REP IN's documented features, Jawla already matches ~11 including the portable printing (CG1) that's usually the hardest field feature. Only 6 real gaps remain.
2. **CG2 offline is the single highest-leverage feature** and the longest to build — start its design now; everything else is faster.
3. **Two gaps are days, not weeks** (barcode, photo) — bank them early for demo/GTM momentum.
4. **CG4 (API/Odoo) is a wedge, not just parity** — REP IN only *claims* ERP integration without public docs; a documented API flips this from parity into advantage.
5. **Feature parity won't win alone** — pair the plan with REP IN's exposed GTM weaknesses (see §6).

---

## 6. GTM levers to pair with features (REP IN's real soft spots)

- **Transparent pricing + self-serve trial** (REP IN hides pricing behind a demo form).
- **Public changelog + healthy store listings** (REP IN's last iOS build is Aug 2022; Android link 404s).
- **Trust center + aligned privacy disclosures** (REP IN's App Store vs. privacy-policy mismatch).
- **Public API docs + case studies + review-generation** (REP IN's proof points are thin/inconsistent).

---

## 7. Risks

| Risk | Mitigation |
|---|---|
| Offline sync conflicts / data divergence (CG2) | Idempotency keys, server-authoritative reconciliation, explicit conflict UX; ship read-offline before write-offline |
| Live tracking battery drain + privacy pushback (CG3) | Adaptive ping intervals, on-shift-only tracking, clear consent + privacy controls |
| Public API security surface (CG4) | Sanctum tokens, scoped abilities, per-token rate limits, audit logging |
| ERP connector maintenance burden (CG4) | Start with Odoo-only MVP + webhooks; avoid promising SAP/Oracle until demanded |
| Scope creep vs. V1 go-live | These are post-V1 competitive features; do not let them block the ETA go-live gate |

---

## 8. Next BMAD steps

- Feed **CG2 (offline)** and **CG3 (live tracking)** into `bmad-prd` / `bmad-architecture` first — they need real design, not just stories.
- **Barcode + photo capture**: small enough to go straight to fix-stories.
- Reconcile with the existing epics in `bmad-output/epics-top-5-competitive-gaps-2026-07-20.md` and stories `CG2.*`–`CG5.*` (already drafted) — this report **updates their priority and confirms CG1 is done**.
