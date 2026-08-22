# Jawla Enhancement Brainstorm Report

**Date:** 2026-08-20
**Mode:** Deep Brainstorm (100-questionnaire)
**Status:** Planning only — no implementation commitment

---

## Challenge

What insanely good, free, open-source capabilities can be added to Jawla to empower its field-sales CRM without spending money on API keys or paid services?

**Constraint:** No AI slop. Everything must be sleek, minimal, professional. Must fit existing design system.

---

## User Profile

- **Role:** Vibe coder building for a client
- **Team:** 6-15 sales reps
- **Stage:** Pre-production
- **Communication:** WhatsApp (no API costs)
- **Connectivity:** Occasional drops (not critical)

---

## Decisions Made

### Maps & Geospatial (Phased Rollout)

| Feature             | Decision                                 | Notes                             |
| ------------------- | ---------------------------------------- | --------------------------------- |
| Geofenced check-in  | 100m default + configurable per customer | Safe radius, works on all devices |
| Route optimization  | Smart suggestion + manual override       | Rep can accept or drag-to-reorder |
| Sales heatmap       | All toggleable (sales, visits, density)  | Admin switches between views      |
| Customer map        | All customers on admin map               | Already have Leaflet              |
| Territory balancing | PostGIS + spatial queries                | Future phase                      |
| Offline maps        | Auto-cache tiles as viewed               | Zero config for reps              |

### Dashboard & Analytics

| Feature          | Decision                   | Notes                                               |
| ---------------- | -------------------------- | --------------------------------------------------- |
| Charting library | Chart.js                   | Lightweight, sleek, fits design system              |
| Dashboard layout | Layout presets + drag-drop | 3-4 presets (Executive, Operations, Sales, Finance) |
| Rich dashboards  | Yes                        | Customizable widgets                                |
| Self-service BI  | Not Metabase               | Keep it native Filament                             |

### Reports & Documents

| Feature           | Decision                                                                    | Notes                                       |
| ----------------- | --------------------------------------------------------------------------- | ------------------------------------------- |
| Report generation | On-demand, not auto                                                         | Rep enters data → generates document        |
| Templates         | User provides template → app implements                                     | Maximum quality                             |
| Export formats    | PDF + Excel + CSV                                                           | All formats                                 |
| Export quality    | Both screen-optimized (72 DPI) and print-ready (300 DPI)                    | User chooses per export                     |
| Report suite      | Full (daily summaries, customer statements, comparisons, product analytics) | All doable, not auto-generated              |
| Document sharing  | Native OS share sheet                                                       | Share directly from app without downloading |

### Offline & Mobile

| Feature              | Decision                           | Notes                                    |
| -------------------- | ---------------------------------- | ---------------------------------------- |
| Offline maps         | Auto-cache tiles as rep views them | First visit online, second works offline |
| Background sync      | Silent auto-sync                   | Invisible to rep, no progress bar needed |
| Barcode scanning     | NOT needed                         | No barcodes in this workflow             |
| Capacitor native app | Yes                                | Wrap PWA as Android/iOS app              |
| Photo + GPS          | All photos get GPS verification    | Every photo in the app                   |

### Security

| Feature               | Decision             | Notes                                             |
| --------------------- | -------------------- | ------------------------------------------------- |
| Biometric auth        | ALL critical actions | Login + payments + returns + any financial action |
| Device fingerprinting | Soft flag            | Flag unknown devices, allow admin override        |

### Share & Communication

| Feature              | Decision               | Notes                          |
| -------------------- | ---------------------- | ------------------------------ |
| Share module         | Universal share (full) | Native OS share sheet          |
| WhatsApp integration | NO API                 | Just verify share button works |
| Payment reminders    | Skip                   | No WhatsApp API                |

### What's NOT Included

- No AI/ML features (no AI slop)
- No barcode scanning
- No gamification
- No voice-to-order
- No WhatsApp Business API
- No payment gateway integration
- No accounting software sync
- No customer/supplier portals

---

## Implementation Phases (Recommended)

### Phase 1: Analytics & Documents

**Why first:** Most visible impact. Client demo-ready immediately.

- Dashboard with Chart.js
- Layout presets + drag-drop
- Report templates (user-provided)
- Export quality (screen + print-ready)
- PDF + Excel + CSV exports
- Native share sheet

### Phase 2: Geospatial

**Why second:** Leverages existing Leaflet + LocationPing infrastructure.

- Geofenced check-in (100m, configurable)
- Route optimization (smart + override)
- Heatmap (all toggleable)
- Offline map auto-caching

### Phase 3: Offline & Mobile

**Why third:** Enhances what already works.

- Silent background sync
- Capacitor native app
- Photo + GPS for all photos
- Offline map tile caching

### Phase 4: Security

**Why fourth:** Polish and harden.

- Biometric auth (all critical actions)
- Device fingerprinting (soft flag)

---

## Free Libraries & Tools

| Need                  | Library                                      | Type                       |
| --------------------- | -------------------------------------------- | -------------------------- |
| Charts                | Chart.js                                     | JS, free                   |
| Maps                  | Leaflet.js                                   | JS, free (already have)    |
| Geospatial queries    | PostGIS                                      | PostgreSQL extension, free |
| Route optimization    | OSRM (self-hosted) or simple distance matrix | Free                       |
| Offline maps          | Leaflet + Service Worker tile caching        | Free                       |
| PDF generation        | mPDF / dompdf                                | PHP, free                  |
| Excel export          | spatie/simple-excel                          | PHP, free (already have)   |
| Biometric auth        | WebAuthn API                                 | Browser native, free       |
| Device fingerprinting | Canvas fingerprint + custom                  | Free                       |
| Native app            | Capacitor                                    | Free                       |
| Photo EXIF            | PHP exif Extension                           | Free                       |
| Share                 | Web Share API                                | Browser native, free       |
| Background sync       | Service Worker + Background Sync API         | Free                       |
| Offline storage       | IndexedDB                                    | Browser native, free       |

---

## Research Questions

1. OSRM self-hosted setup complexity for route optimization
2. Leaflet tile caching strategies for offline maps
3. Capacitor integration with Laravel Livewire PWA
4. WebAuthn browser support on Android devices in MENA
5. PostGIS performance with millions of LocationPing records
6. Service Worker background sync reliability across browsers

---

## Next Steps

When ready to implement:

1. Start with Phase 1 (Analytics & Documents)
2. Use `ai-ultraplan` for detailed implementation plan
3. Use `ai-production-feature-builder` for each feature

---

_This is a planning document. No implementation has been committed._
