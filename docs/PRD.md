# PRD — Jawla Enhancement Suite

## Users & Beneficiaries

| Role              | What they need                                                                   |
| ----------------- | -------------------------------------------------------------------------------- |
| **Admin/Manager** | Rich dashboards, visual analytics, map-based oversight, automated reports        |
| **Field Rep**     | Geofenced check-in, route optimization, offline maps, photo GPS, biometric login |
| **Accountant**    | Exportable financial reports (PDF/Excel/CSV), print-ready documents              |
| **IT/Admin**      | Device management, security controls, configurable dashboards                    |

## Problem

Jawla has solid core functionality (invoices, stock, payments, sync) but lacks:

1. **Visual analytics** — only stat cards, no charts or trends
2. **Geospatial intelligence** — Leaflet exists but no check-in automation, routing, or heatmaps
3. **Document export quality** — CSV only on reports page, PDF templates are inline HTML
4. **Offline map support** — maps require live connection
5. **Security hardening** — no biometric auth, no device fingerprinting

## Outcomes

### Phase 1: Analytics & Documents

- Admin sees rich charts (sales trends, rep performance, product mix) on dashboard
- Reports export to PDF + Excel + CSV with print-ready quality
- Documents share via native OS share sheet

### Phase 2: Geospatial

- Reps auto-check-in when entering 100m of customer (configurable)
- Admin sees sales/visit/density heatmaps on map
- Reps get optimized route suggestions for daily visits

### Phase 3: Offline & Mobile

- Map tiles auto-cache for offline use
- Silent background sync (no user action needed)
- All photos include GPS verification
- App wrapped as native via Capacitor

### Phase 4: Security

- Biometric auth for login + all critical financial actions
- Unknown devices soft-flagged for admin review

## Scope

### In Scope

- Chart.js integration in Filament dashboard
- Layout presets (Executive, Operations, Sales, Finance) + drag-drop
- Report template system (user-provided templates → on-demand generation)
- Export quality: screen-optimized (72 DPI) + print-ready (300 DPI)
- Native OS Web Share API integration
- PostGIS extension for spatial queries
- Geofenced check-in service
- Route optimization (nearest-neighbor + manual override)
- Heatmap layers (sales, visits, density)
- Leaflet tile caching via Service Worker
- Capacitor wrapper
- Photo EXIF GPS extraction + verification
- WebAuthn biometric authentication
- Device fingerprinting (canvas + navigator)

### Out of Scope (Non-Goals)

- AI/ML features (no API keys)
- WhatsApp Business API integration
- Barcode scanning
- Payment gateway integration
- Accounting software sync
- Customer/supplier portals
- Voice-to-order
- Gamification

## Success Measures

| Metric                      | Target                                  |
| --------------------------- | --------------------------------------- |
| Dashboard load time         | < 2s with all widgets                   |
| Report export time          | < 5s for PDF, < 3s for Excel            |
| Geofence check-in accuracy  | 95%+ correct triggers                   |
| Offline map availability    | 100% of visited areas cached            |
| Biometric auth success rate | 99%+ on supported devices               |
| Zero paid API dependencies  | All features work with free/open-source |

## Constraints

- **No new paid dependencies** — everything must be free/open-source
- **Existing design system** — all UI must fit sleek, minimal, professional aesthetic
- **Pre-production** — app is not yet live, changes won't break existing users
- **6-15 reps** — scale requirements are modest
- **PostgreSQL** — must use PostGIS for spatial, not a separate database

## Architecture Decisions

See ARCHITECTURE.md for full decision records.

## Approval Gates

1. **Phase 1 complete** — dashboard + reports demo to client
2. **Phase 2 complete** — geofencing + maps demo to client
3. **Phase 3 complete** — offline + native app test on device
4. **Phase 4 complete** — security audit before production launch
