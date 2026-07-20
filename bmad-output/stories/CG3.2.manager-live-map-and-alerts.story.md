# Story CG3.2 -- Manager Live Map and Alerts

**Status:** ready-for-dev
**Epic:** CG3 -- Live Rep Tracking
**Estimated effort:** Medium (~1 week)
**Blocked by:** CG3.1
**Labels:** gps, map, dashboard, p1

---

## Story

**As a** sales manager  
**I want** a live admin map of active reps and stale-location alerts  
**So that** I can supervise field execution without manually calling reps.

---

## Acceptance Criteria

- Admin page/widget renders active reps on a Leaflet map.
- Last-seen age is visible per rep.
- Stale/no-signal rep states are flagged clearly.
- Map is tenant-scoped and permission-gated.
- Query count and refresh cadence remain bounded for 50+ active reps.
