# Investigation Case File: v1.1-deferred-items

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — Phase Roadmap v1.1 items per PRD v1.1 §3
**Severity:** Deferred (post-beta, pre-v1.2)
**Status:** Open — Cataloged for v1.1 planning
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-v1-1-deferred-items-2026-07-19.md`

---

## Summary

**One-sentence description:**
Nine v1.1 items are explicitly deferred in PRD v1.1 §3 — the top priority is "Offline architecture decision" which is architectural and blocks subsequent v1.1 work. This file catalogs them for post-Beta planning.

**Expected behavior:** These features ship in v1.1 track after Beta Done.

**Actual behavior:** All explicitly deferred. Only "Offline architecture decision" is architectural; others are feature additions.

**User / business impact:** None for Beta. v1.1 is the first post-beta release with significant UX and architecture upgrades.

---

## Symptom Details

**Trigger conditions:** Structural — explicitly deferred per PRD v1.1 §3 phase map.

**Environments affected:** Future (v1.1 track).

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

### Evidence Item 1: Offline Architecture Decision (Top Priority)

**Grade:** [A]
**Source:** PRD v1.1 §3: "Offline architecture decision (**top priority**)"
**Description:** REQ-CMP-3 requires "connection-aware degradation package: offline indicator, localStorage draft autosave for visit reports & invoices, submission retry queue, cached read-only day data". Current: Visit Flow saves drafts to localStorage every 3s but **never syncs**; no IndexedDB queue, no background sync, no offline indicator on 12/13 pages.
**Implications:** This is an architectural spike — decision between IndexedDB + Background Sync vs. Service Worker + Workbox vs. custom queue. Blocks all other v1.1 offline work.

---

### Evidence Item 2: Push Notifications

**Grade:** [A]
**Source:** PRD v1.1: "push" deferred to v1.1; Amendment: "in-app alarm bell + red indicators cover AM4 intent in beta"
**Description:** Beta uses in-app bell (implemented). v1.1 adds FCM/APNs push for reps when app is closed. Needs: FCM setup, device token storage, notification payload mapping, permission flow.
**Implications:** Depends on offline architecture (retry queue for when app wakes from push).

---

### Evidence Item 3: Onboarding Walkthrough

**Grade:** [A]
**Source:** PRD v1.1: "onboarding walkthrough" deferred to v1.1
**Description:** No first-run tutorial, no feature highlights, no guided first visit. Rep drops into Home page cold.
**Implications:** UX improvement; low technical complexity; can use Shepherd.js or custom stepper.

---

### Evidence Item 4: Barcode/QR Product Lookup

**Grade:** [A]
**Source:** PRD v1.1: "barcode/QR product lookup" deferred to v1.1; REQ-CMP-9 mentions "rep-app search on customers and products"
**Description:** Stock Search uses text input only. Camera-based barcode scanning would require: `<input type="file" accept="image/*" capture="environment">` + QuaggaJS or BarcodeDetector API. REQ-CMP-9 also implies search autocomplete (currently missing on Purchase Offer, Log Return, Collect Payment).
**Implications:** Medium effort; high rep-value feature.

---

### Evidence Item 5: Biometric / 2FA

**Grade:** [A]
**Source:** PRD v1.1: "biometric/2FA" deferred to v1.1
**Description:** Current: session-based auth only. v1.1: WebAuthn (biometric) + TOTP (2FA) for admin/rep login.
**Implications:** Security upgrade; WebAuthn requires HTTPS (OK on prod); TOTP needs QR code generation + recovery codes.

---

### Evidence Item 5: Rep Dark Mode

**Grade:** [A]
**Source:** PRD v1.1: "rep dark mode" deferred to v1.1; REQ-CMP-11 (admin dark mode) already done via Filament v4 default
**Description:** Admin has dark mode (Filament v4). Rep PWA has no dark mode toggle. Needs: CSS custom properties for dark theme, toggle in More page, persist preference, system preference detection.
**Implications:** Low-medium effort; Tailwind `dark:` variant ready.

---

### Evidence Item 6: Bulk Actions in Rep App

**Grade:** [A]
**Source:** PRD v1.1: "bulk actions in rep app" deferred to v1.1
**Description:** No multi-select on any rep list (Customers, Stock, Visits, Orders, Quotations). No "Select all", "Delete selected", "Assign route to selected", "Export selected".
**Implications:** Medium effort; needs selection state management across Livewire components.

---

### Evidence Item 7: Route Optimization Map

**Grade:** [A]
**Source:** PRD v1.1: "route-optimization map" deferred to v1.1/v1.2
**Description:** DailyVisitAssignment has route_id and customer lat/lng. No map view showing optimized route. Would need: OSRM (self-hosted) or Google Maps Directions API, TSP solver, map rendering (Leaflet/MapLibre).
**Implications:** High effort; external dependency; v1.2 may be more realistic.

---

### Evidence Item 8: PRC-3 Nested Ranges (if client re-opens)

**Grade:** [A]
**Source:** PRD v1.1: "PRC-3 revisit (nested ranges) if client re-opens" deferred to v1.1
**Description:** Current pricing: base price → manager ± range → rep floor. Nested ranges = manager delegates sub-range to sub-manager. Currently blocked on client decision (Q1/Q2 unanswered).
**Implications:** Zero effort until client decides; schema already supports (PricingRange model has parent_id nullable).

---

### Evidence Item 9: Sync Discovery

**Grade:** [A]
**Source:** PRD v1.1: "sync discovery" deferred to v1.1
**Description:** Related to offline architecture — conflict resolution strategy (last-write-wins per TEC-12), server reconciliation, multi-device sync. Depends on offline architecture decision.
**Implications:** Part of offline spike; not separable.

---

### Evidence Summary

| #   | Item                          | Grade | Priority   | Dependencies    |
| --- | ----------------------------- | ----- | ---------- | --------------- |
| 1   | Offline Architecture Decision | A     | **Top**    | None (spike)    |
| 2   | Push Notifications            | A     | High       | Offline arch    |
| 3   | Onboarding Walkthrough        | A     | Medium     | None            |
| 4   | Barcode/QR Lookup             | A     | High       | None            |
| 5   | Biometric / 2FA               | A     | Medium     | Auth system     |
| 6   | Rep Dark Mode                 | A     | Low        | CSS/JS          |
| 7   | Bulk Actions                  | A     | Medium     | Livewire        |
| 7   | Route Optimization Map        | A     | Low (v1.2) | OSRM/Google API |
| 8   | PRC-3 Nested Ranges           | A     | Blocked    | Client decision |
| 9   | Sync Discovery                | A     | High       | Offline arch    |

---

## Hypotheses

### Hypothesis 1 — Offline Architecture is the critical path for v1.1 [Plausibility: High]

**Statement:** All other v1.1 items (push, bulk actions, barcode, sync) depend on the offline architecture decision. The spike must complete before any v1.1 implementation.

**Supporting evidence:** Evidence 1 [A] — offline is "top priority"; Evidence 2, 9 depend on it.

**Contradicting evidence:** Onboarding, dark mode, biometric could theoretically proceed in parallel.

**Verification step:** Run 2-week offline architecture spike immediately after Beta Done.

---

### Hypothesis 2 — Route Optimization is realistically v1.2, not v1.1 [Plausibility: High]

**Statement:** PRD lists "route-optimization map" under v1.1/v1.2. Building OSRM + TSP solver + map rendering is 4-6 weeks minimum. v1.1 likely only has capacity for offline + push + dark mode + onboarding.

**Supporting evidence:** Evidence 7 [A] — external dependency, algorithmic complexity.

**Contradicting evidence:** None.

**Verification step:** Move to v1.2 in planning; confirm with owner.

---

### Hypothesis 3 — PRC-3 Nested Ranges is effectively "never" without client sign-off [Plausibility: Medium]

**Statement:** Q1/Q2 pricing math questions have been open since Beta start. Client hasn't answered. PRC-3 is explicitly "if client re-opens".

**Supporting evidence:** Evidence 8 [A] — "if client re-opens".

**Contradicting evidence:** None.

**Verification step:** Formal client decision request; if no answer in 2 weeks, mark "won't fix".

---

## Suspected Components

| Component            | Type               | Files                                                                         | Blast Radius                                              |
| -------------------- | ------------------ | ----------------------------------------------------------------------------- | --------------------------------------------------------- |
| Offline Architecture | Spike/Architecture | New `OfflineQueueService`, `IndexedDB` wrapper, `BackgroundSync` registration | All rep pages + services                                  |
| Push Notifications   | Service            | New `PushNotificationService`, FCM integration, device token migration        | Auth + Rep app                                            |
| Onboarding           | Component          | New `OnboardingTour` (Shepherd.js), `onboarding_completed` flag on User       | Rep app only                                              |
| Barcode Scanner      | Component          | `BarcodeScanner` Livewire component, QuaggaJS/BarcodeDetector                 | Stock Search, Log Return, Purchase Offer, Collect Payment |
| Biometric/2FA        | Auth               | WebAuthn service, TOTP service, migration for existing users                  | Auth system                                               |
| Rep Dark Mode        | CSS/JS             | Tailwind `dark:` variants, toggle in More page, localStorage pref             | All rep pages                                             |
| Bulk Actions         | Livewire Trait     | `HasBulkActions` trait, selection state                                       | All rep list pages                                        |
| Route Optimization   | Service + Map      | OSRM setup, TSP solver, MapLibre/Leaflet page                                 | New page + service                                        |

---

## Related Requirements

| Requirement                       | Source                   | Status                        |
| --------------------------------- | ------------------------ | ----------------------------- |
| REQ-CMP-3 Offline degradation     | PRD v1.1 §2              | Deferred (top priority spike) |
| Push notifications                | PRD v1.1 v1.1 track      | Deferred                      |
| Onboarding walkthrough            | PRD v1.1 v1.1 track      | Deferred                      |
| REQ-CMP-9 Barcode/QR lookup       | PRD v1.1 §2              | Deferred                      |
| Biometric/2FA                     | PRD v1.1 v1.1 track      | Deferred                      |
| REQ-CMP-11 Rep dark mode          | PRD v1.1 §2              | Deferred (admin done)         |
| REQ-CMP-9 Bulk actions            | PRD v1.1 §2              | Deferred                      |
| Route optimization                | PRD v1.1 v1.1/v1.2 track | Deferred                      |
| PRC-3 Nested ranges               | PRD v1.0                 | Blocked on client             |
| Sync conflict resolution (TEC-12) | PRD §4                   | Deferred                      |

---

## Recommended Action

**Planning Response:** Option C — Escalate to planning (v1.1 sprint planning after Beta Done)

**Rationale:** All items explicitly deferred. The only actionable now is the **Offline Architecture Spike** (2 weeks). Everything else waits for v1.1 sprint planning.

**Specific gaps to address in planning:**

1. Run 2-week Offline Architecture Spike immediately after Beta Done
2. Client decision on PRC-3 (2-week deadline)
3. v1.1 capacity planning: offline + push + dark mode + onboarding = ~4 weeks
4. Route optimization → move to v1.2
5. Barcode + bulk actions + onboarding can parallelize after offline spike

---

## Open Questions

1. **Offline scope:** Full offline-first (read+write offline) or connection-aware degradation only (REQ-CMP-3 says "degradation package")?
2. **Push provider:** FCM (Google) only, or need APNs for iOS? (PWA on iOS has limited push support)
3. **Barcode format:** EAN-13 only, or QR codes too? (affects library choice)
4. **2FA scope:** Admin only, or rep too? (rep devices may not have TOTP apps)
5. **Route optimization:** Self-host OSRM (free, maintenance) vs Google Maps API (paid, zero maintenance)?

---

## Update History

| Version | Date       | Summary of Changes                        |
| ------- | ---------- | ----------------------------------------- |
| 1.0     | 2026-07-19 | Initial cataloging of v1.1 deferred items |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
