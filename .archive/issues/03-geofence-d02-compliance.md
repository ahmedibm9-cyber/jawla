# Geofence check-in: implement signed decision D-02 (500m, decline out-of-range, block GPS-denied)

## Overview

`VisitFlow` implements the _pre-decision_ proposal: hardcoded 1500m radius and a `skipGpsAndConfirm()` "confirm anyway" path (`app/Livewire/App/VisitFlow.php:73-102`). The client's signed answer in `docs/BETA_OPEN_DECISIONS.md` D-02 says: radius **500m** (100m if feasible), out-of-range = **rep cannot check in**, GPS denied = **check-in blocked entirely**. The `visits.arrival_flag` column exists but is never written. Current behavior is wrong, not merely incomplete.

## Scope

**Included:** configurable radius, server-side range enforcement, blocking UI states, `arrival_flag` persistence, removal of the confirm-anyway path, idempotent arrival submission.
**Excluded:** manager notification on out-of-range attempts (D-02 removed the override path, so no alert is required; log the attempt instead), offline check-in.

## Technical Requirements

- One company-configurable radius (`companies.geofence_radius_m`, default 500) — no scattered constants; migration + Filament company field.
- **Server-side** validation: `confirmArrival()` recomputes distance from submitted coordinates and rejects out-of-range regardless of client state (never trust `withinRange` from the browser).
- GPS denied/unavailable → blocking bilingual screen: "GPS must be enabled to check in" with enable instructions; no report step reachable.
- Out-of-range → blocking bilingual message showing distance; retry button; attempt logged to `activities`.
- Write `arrival_flag='in_range'` on success; store coordinates, accuracy, distance, timestamp.
- Repeated arrival submissions idempotent (already-checked-in visit returns current state).

## Implementation Plan

1. Migration: `companies.geofence_radius_m` unsignedInteger default 500.
2. `VisitFlow::confirmArrival()` — server recompute + reject; delete `skipGpsAndConfirm()`; write `arrival_flag`.
3. Blade: replace the amber confirm-anyway card (`visit-flow.blade.php:101-107`) with blocked state; add GPS-denied blocked state (currently only an error string at line 22).
4. Tests: in-range success writes flag+coords; 501m rejected server-side even with forged `withinRange`; GPS-denied blocks; double-submit idempotent.

## Acceptance Criteria

- [ ] Rep at ≤ radius checks in normally; `arrival_flag`, coords, distance persisted
- [ ] Rep beyond radius cannot check in — server-enforced, bilingual message with distance
- [ ] GPS denied blocks check-in with enable prompt
- [ ] Radius read from company config; no 1000/1500 constants remain (`grep -rn "1500\|1000" app/Livewire/App/VisitFlow.php` clean)
- [ ] Feature tests above pass

## Priority

Score 6.25 — violates a signed client decision; every field visit exercises this path daily.

## Dependencies

- **Blocks:** Beta walkthrough step 3–4
- **Blocked by:** #1

## Implementation Size

- **Estimated effort:** Small (1–2 days)
