# Investigation Case File: good-to-have-gaps-g3-g7

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — gaps G3, G4, G5, G6, G7 from `investigation-missing-ui-elements-2026-07-19.md`
**Severity:** Degraded UX / Spec'd for beta, degraded without
**Status:** Open
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-good-to-have-gaps-g3-g7-2026-07-19.md`

---

## Summary

**One-sentence description:**
Five good-to-have gaps remain from the UI audit: authenticated style-guide route (G3), invoice draft autosave + offline retry queue (G4), customer card Google Maps deep-link (G5), manager master-schedule filters (G6), and purchase offer renegotiation/resubmission UI (G7). These are spec'd for beta but the app is degraded without them.

---

## Gap G3: Authenticated Style-Guide Route

### Symptom

No route renders all design system component states (buttons, cards, modals, empty states, skeletons, tooltips) in both AR/EN for design QA.

### Evidence

| #    | Grade | Description                                                                                                        |
| ---- | ----- | ------------------------------------------------------------------------------------------------------------------ |
| G3-1 | [A]   | `grep -rn "styleguide" routes app` → no matches. No `/admin/styleguide` route exists.                              |
| G3-2 | [A]   | Filament has a "Style Guide" concept via custom pages but none registered.                                         |
| G3-3 | [A]   | DS components exist (`button`, `card`, `empty`, `modal`, `skeleton`, `tooltip`) but no centralized rendering page. |

### Hypothesis

Style guide was part of B0 scope but never created as a Filament page. Low priority because components work; the route is for QA verification only.

### Recommended Action

Option A — Create a single Filament page at `/admin/styleguide` that renders all DS components in all states (normal, hover, focus, active, loading, disabled) in both AR and EN. ~1 day effort.

| Field           | Value                                                                                               |
| --------------- | --------------------------------------------------------------------------------------------------- |
| Story title     | Authenticated style-guide route for design QA                                                       |
| As a            | Admin/developer                                                                                     |
| I want          | A page showing all design system components in all states                                           |
| So that         | I can verify visual consistency across Arabic and English                                           |
| Suspected files | New `app/Filament/Pages/StyleGuide.php`, new `resources/views/filament/pages/style-guide.blade.php` |

---

## Gap G4: Invoice Draft Autosave + Offline Retry Queue Indicator

### Symptom

Visit Flow has localStorage draft autosave (every 3 seconds) but Sales Flow (invoice creation) has no draft persistence. If the rep navigates away mid-invoice, all cart items are lost. No offline retry queue indicator exists on any page.

### Evidence

| #    | Grade | Description                                                                                                                                                                    |
| ---- | ----- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| G4-1 | [A]   | `resources/views/livewire/app/visit-flow.blade.php:36-45` — localStorage draft saves `$summary`, `$feedback`, `$actionTaken`, `$followUp`, `$followUpDetails` every 3 seconds. |
| G4-2 | [A]   | `app/Livewire/App/SalesFlow.php` — no localStorage save, no `wire:poll`, no draft mechanism. Cart state is purely in-memory Livewire properties.                               |
| G4-3 | [A]   | `sw.js` exists but is not registered (no `navigator.serviceWorker.register()` in base layout). No offline queue for any action.                                                |
| G4-4 | [A]   | Only Visit Flow has an offline banner (`wire:offline`). All other pages show no connection status.                                                                             |

### Hypothesis

Draft autosave was implemented for Visit Flow (the most complex form) but not extended to Sales Flow. Offline queue was planned as part of REQ-CMP-3 (connection-aware degradation) but deferred as a large-scope item.

### Recommended Action

Option A — Create a Fix Story for invoice draft autosave (medium effort). Offline queue is a separate, larger story.

| Field           | Value                                                                                 |
| --------------- | ------------------------------------------------------------------------------------- |
| Story title     | Invoice draft autosave to localStorage                                                |
| As a            | Sales rep                                                                             |
| I want          | My invoice cart to be saved locally so I don't lose it if I navigate away             |
| So that         | I can continue building an invoice after checking stock or looking up a customer      |
| Suspected files | `app/Livewire/App/SalesFlow.php`, `resources/views/livewire/app/sales-flow.blade.php` |
| Effort          | Medium (~2 days)                                                                      |

---

## Gap G5: Customer Card Google Maps Deep-Link

### Symptom

Visit cards on the Home page have a Google Maps directions link. Customer cards on the Customers page do NOT have a maps link.

### Evidence

| #    | Grade | Description                                                                                                                                                                                                             |
| ---- | ----- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| G5-1 | [A]   | `resources/views/livewire/app/home.blade.php` — visit cards include: `<a href="https://www.google.com/maps/dir/?api=1&destination={{ $visit->customer->latitude }},{{ $visit->customer->longitude }}" target="_blank">` |
| G5-2 | [A]   | `resources/views/livewire/app/customers.blade.php` — customer cards show name, code, phone, address, but NO maps link.                                                                                                  |
| G5-3 | [A]   | Customer model has `latitude`, `longitude` fields — data is available.                                                                                                                                                  |

### Hypothesis

Maps deep-link was added to Home page visit cards but not to the Customers page. Simple omission — same pattern, different page.

### Recommended Action

Option A — One-line fix per customer card. ~0.5 day effort.

| Field           | Value                                                                |
| --------------- | -------------------------------------------------------------------- |
| Story title     | Google Maps deep-link on customer cards                              |
| As a            | Sales rep                                                            |
| I want          | A directions button on each customer card that opens Google Maps     |
| So that         | I can navigate to any customer without manually entering the address |
| Suspected files | `resources/views/livewire/app/customers.blade.php`                   |

---

## Gap G6: Manager Master-Schedule Filters

### Symptom

`DailyVisitAssignmentResource` (Filament admin) exists but the master-schedule page lacks filtering by date, route, rep, and status per B3-02 spec.

### Evidence

| #    | Grade | Description                                                                                                                                                                           |
| ---- | ----- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| G6-1 | [A]   | `app/Filament/Resources/DailyVisitAssignmentResource.php` — exists with List/Create/Edit pages                                                                                        |
| G6-2 | [B]   | `app/Filament/Resources/DailyVisitAssignmentResource/Pages/ListDailyVisitAssignments.php` — standard Filament list; filters unverified (need to read the resource's `table()` method) |
| G6-3 | [A]   | B3-02 spec: "Manager master-schedule filters (date/route/rep/status)" — explicitly required                                                                                           |

### Hypothesis

DailyVisitAssignmentResource was built with basic CRUD but the advanced filters (date range, route, rep, status) were not implemented.

### Recommended Action

Option A — Add Filament table filters to the existing resource. ~1 day effort.

| Field           | Value                                                                             |
| --------------- | --------------------------------------------------------------------------------- |
| Story title     | Master-schedule filters for manager (date/route/rep/status)                       |
| As a            | Sales manager                                                                     |
| I want          | Filter the daily visit assignment list by date range, route, rep, and status      |
| So that         | I can quickly find and manage visits for a specific day, route, or rep            |
| Suspected files | `app/Filament/Resources/DailyVisitAssignmentResource.php` (table filters section) |

---

## Gap G7: Purchase Offer Renegotiation/Resubmission UI

### Symptom

Purchase offers can only be submitted once. No UI exists to renegotiate, amend, or resubmit an offer that was rejected or needs adjustment. Per D-04 decision, reps should be able to set an expiry field on offers.

### Evidence

| #    | Grade | Description                                                                                                             |
| ---- | ----- | ----------------------------------------------------------------------------------------------------------------------- |
| G7-1 | [A]   | `app/Livewire/App/SubmitPurchaseOffer.php` — creates purchase offer with `status = 'pending'`. No edit/resubmit action. |
| G7-2 | [A]   | `app/Filament/Resources/PurchaseRequestResource.php` — admin can approve/reject but no rep-side renegotiation.          |
| G7-3 | [B]   | D-04 decision: "purchase-offer renegotiation/resubmission UI + rep-set expiry field" — explicitly planned.              |

### Hypothesis

Purchase offer flow was built as submit-only. Renegotiation was planned as a follow-up feature per D-04 but never implemented.

### Recommended Action

Option A — Create a Fix Story for purchase offer renegotiation (medium effort, depends on D-04 final decision on expiry field).

| Field           | Value                                                                                                                              |
| --------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| Story title     | Purchase offer renegotiation + expiry field                                                                                        |
| As a            | Sales rep                                                                                                                          |
| I want          | To amend and resubmit a rejected purchase offer, and set an expiry date                                                            |
| So that         | I can negotiate with suppliers without starting from scratch                                                                       |
| Suspected files | `app/Livewire/App/SubmitPurchaseOffer.php`, `app/Filament/Resources/PurchaseRequestResource.php`, `app/Models/PurchaseRequest.php` |
| Effort          | Medium (~2-3 days)                                                                                                                 |

---

## Related Requirements

| Requirement                    | Source            | Gaps |
| ------------------------------ | ----------------- | ---- |
| B0-01 style-guide route        | Design System     | G3   |
| B3-07 / B5-06 offline autosave | Master Plan       | G4   |
| REQ-CMP-6 maps deep-link       | PRD v1.1 §2       | G5   |
| B3-02 manager master-schedule  | Master Plan       | G6   |
| D-04 purchase offer expiry     | Decision register | G7   |

---

## Recommended Action

**Planning Response:** Option A — Create individual fix stories for each gap (all are small-medium effort, independent of each other).

Priority order: G5 (smallest, immediate win) → G3 (small, QA enabler) → G6 (medium, manager UX) → G4 (medium, rep UX) → G7 (medium, depends on D-04).

---

## Update History

| Version | Date       | Summary                                         |
| ------- | ---------- | ----------------------------------------------- |
| 1.0     | 2026-07-19 | Initial investigation case file (bundled G3-G7) |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
