# Story CG3.1 -- Rep Location Ping Pipeline

**Status:** ready-for-dev
**Epic:** CG3 -- Live Rep Tracking
**Estimated effort:** Medium (~1 week)
**Blocked by:** none
**Labels:** gps, tracking, backend, p1

---

## Story

**As a** manager  
**I want** active reps to publish periodic location pings during work sessions  
**So that** I can monitor route execution in near real time.

---

## Acceptance Criteria

- Rep app sends throttled location pings only during active work sessions.
- Server stores timestamp, coordinates, accuracy, and rep/company ownership.
- No pings are accepted for other companies or inactive sessions.
- Battery-conscious cadence is configurable.
- Missing permission / denied GPS states are explicit and non-silent.
