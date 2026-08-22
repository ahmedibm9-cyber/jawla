# RISKS — Jawla Enhancement Suite

## Phase 1: Analytics & Documents

| Risk                                       | Impact | Likelihood | Mitigation                                                                         |
| ------------------------------------------ | ------ | ---------- | ---------------------------------------------------------------------------------- |
| Chart.js bundle increases page load        | Medium | Low        | Chart.js is 60KB gzipped. Lazy-load charts only on dashboard.                      |
| PDF templates hard to maintain             | Medium | Medium     | Use Blade templates (version-controlled, testable). Document template conventions. |
| Export quality differences confusing       | Low    | Low        | Clear UI labels: "Screen (fast)" vs "Print (high quality)".                        |
| Web Share API unavailable on desktop       | Low    | High       | Fallback to copy-link is reliable. Desktop users rarely share via OS sheet.        |
| Dashboard drag-drop conflicts with presets | Medium | Low        | Preset switch resets order. Explicit "Save as custom" option.                      |

## Phase 2: Geospatial

| Risk                                           | Impact | Likelihood | Mitigation                                                                            |
| ---------------------------------------------- | ------ | ---------- | ------------------------------------------------------------------------------------- |
| PostGIS migration corrupts data                | High   | Low        | Backup before migration. Test on dev. Geography column derived from existing lat/lng. |
| GPS accuracy causes false check-ins            | High   | Medium     | 100m default radius. Configurable per customer. Log accuracy for debugging.           |
| Route optimization too slow for many customers | Medium | Low        | Nearest-neighbor is O(n²). 6-15 reps × ~20 customers = fast.                          |
| Leaflet.heat performance with 1000+ points     | Medium | Low        | Heatmap only on admin page (not rep PWA). Server-side data aggregation.               |
| Tile caching exceeds 50MB on long routes       | Low    | Medium     | LRU eviction. Cache stats visible to user. Clear option available.                    |

## Phase 3: Offline & Mobile

| Risk                                   | Impact | Likelihood | Mitigation                                                                             |
| -------------------------------------- | ------ | ---------- | -------------------------------------------------------------------------------------- |
| Background Sync API unsupported on iOS | High   | High       | Fallback: periodic sync attempt every 30s when online. Not as seamless but functional. |
| Capacitor breaks existing PWA features | High   | Medium     | Test all PWA features in Capacitor before release. Keep PWA as primary target.         |
| EXIF GPS data stripped by some cameras | Medium | Medium     | Fallback: use device GPS (not photo EXIF) for location. EXIF is bonus.                 |
| Capacitor app rejected by app stores   | Medium | Low        | Follow store guidelines. Test with beta builds. Have web fallback.                     |
| Silent sync causes data conflicts      | Medium | Low        | Existing sync engine handles conflicts. Add conflict resolution UI if needed.          |

## Phase 4: Security

| Risk                                           | Impact | Likelihood | Mitigation                                                                             |
| ---------------------------------------------- | ------ | ---------- | -------------------------------------------------------------------------------------- |
| WebAuthn unsupported on older devices          | High   | Medium     | Fallback: password-only login. Biometric is enhancement, not requirement.              |
| Device fingerprint too sensitive (false flags) | Medium | High       | Use multiple components (canvas, WebGL, audio). Hash with salt. Allow admin override.  |
| Biometric prompt annoying for frequent actions | Medium | Medium     | Only for financial critical actions, not every action. User can disable.               |
| WebAuthn credential storage security           | High   | Low        | Use established library (web-auth/webauthn). Encrypt at rest. Follow OWASP guidelines. |

## Cross-Phase Risks

| Risk                           | Impact | Likelihood | Mitigation                                                                   |
| ------------------------------ | ------ | ---------- | ---------------------------------------------------------------------------- |
| Scope creep across phases      | High   | High       | Strict phase boundaries. Each phase reviewed before starting next.           |
| Testing gaps                   | High   | Medium     | Each task includes test requirements. `make verify` before phase completion. |
| Performance regression         | Medium | Medium     | Load test dashboard with 1000+ records. Profile queries.                     |
| Bilingual UI inconsistencies   | Medium | Low        | All new strings in lang files. RTL testing for every new component.          |
| Offline/online data divergence | High   | Low        | Existing sync engine is battle-tested. Enhance, don't replace.               |

## Risk Register

| ID  | Risk                           | Phase | Owner | Status |
| --- | ------------------------------ | ----- | ----- | ------ |
| R1  | PostGIS migration safety       | 2     | TBD   | Open   |
| R2  | iOS Background Sync fallback   | 3     | TBD   | Open   |
| R3  | WebAuthn device support        | 4     | TBD   | Open   |
| R4  | Capacitor PWA compatibility    | 3     | TBD   | Open   |
| R5  | Dashboard performance at scale | 1     | TBD   | Open   |

## Assumptions

1. PostgreSQL version supports PostGIS (9.5+)
2. Target devices are Android 8+ / iOS 14+ (WebAuthn support)
3. OpenStreetMap tiles are acceptable (no Mapbox needed)
4. 6-15 reps means data volume is modest (< 100K records per table)
5. Client will provide report templates (we implement, they design)
6. Capacitor is acceptable for native app distribution

## Unknowns

1. Exact PostGIS version available on production server
2. Client's report template designs (affects Phase 1 timeline)
3. Target device mix (Android vs iOS) affects Capacitor priority
4. Production server resources (CPU/RAM for PostGIS queries)
5. App store account status (developer account needed for Capacitor)
