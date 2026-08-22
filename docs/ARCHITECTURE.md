# ARCHITECTURE — Jawla Enhancement Suite

## Current State

```
┌─────────────────────────────────────────────────┐
│                    CLIENT                        │
│  ┌─────────────┐  ┌─────────────┐  ┌──────────┐│
│  │ Livewire PWA │  │  Leaflet.js │  │ Service  ││
│  │ (Alpine.js)  │  │  (vendored) │  │ Worker   ││
│  └─────────────┘  └─────────────┘  └──────────┘│
└─────────────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────┐
│                    SERVER                        │
│  ┌─────────────┐  ┌─────────────┐  ┌──────────┐│
│  │   Filament   │  │   Livewire  │  │  mPDF    ││
│  │   Admin      │  │   Services  │  │  Engine  ││
│  └─────────────┘  └─────────────┘  └──────────┘│
│  ┌─────────────┐  ┌─────────────┐  ┌──────────┐│
│  │  LocationPing│  │   Sync      │  │ Webhooks ││
│  │  Service     │  │   Service   │  │          ││
│  └─────────────┘  └─────────────┘  └──────────┘│
└─────────────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────┐
│                 DATABASE                         │
│  PostgreSQL (no PostGIS)                         │
│  - customers (lat/lng decimal)                   │
│  - location_pings (lat/lng decimal)              │
│  - visits (checkin_lat/lng)                      │
└─────────────────────────────────────────────────┘
```

## Proposed Architecture

### Phase 1: Analytics & Documents

```
┌─────────────────────────────────────────────────┐
│                    CLIENT                        │
│  ┌─────────────┐  ┌─────────────┐  ┌──────────┐│
│  │ Livewire PWA │  │  Chart.js   │  │ Web      ││
│  │ (Alpine.js)  │  │  (via Vite) │  │ Share API││
│  └─────────────┘  └─────────────┘  └──────────┘│
└─────────────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────┐
│                    SERVER                        │
│  ┌─────────────┐  ┌─────────────┐  ┌──────────┐│
│  │  ChartWidget │  │  Report     │  │  PdfEngine││
│  │  (Filament)  │  │  Service    │  │  (enhanced)││
│  └─────────────┘  └─────────────┘  └──────────┘│
│  ┌─────────────┐  ┌─────────────┐              │
│  │  ExcelExport │  │  ShareLink  │              │
│  │  Service     │  │  Service    │              │
│  └─────────────┘  └─────────────┘              │
└─────────────────────────────────────────────────┘
```

**New Dependencies:**

- `chart.js` (npm, free) — charting library
- No new PHP packages needed

**Architecture Decisions:**

| Decision            | Choice                             | Rationale                                                             |
| ------------------- | ---------------------------------- | --------------------------------------------------------------------- |
| Chart rendering     | Server-side (Filament ChartWidget) | Consistent with existing widget pattern, no client-side JS complexity |
| PDF template engine | Blade templates (not inline HTML)  | Maintainable, testable, version-controlled                            |
| Export quality      | Separate mPDF configs              | Same template, different DPI/margins                                  |
| Share mechanism     | Web Share API with fallback        | Native on mobile, graceful degradation on desktop                     |

### Phase 2: Geospatial

```
┌─────────────────────────────────────────────────┐
│                    CLIENT                        │
│  ┌─────────────┐  ┌─────────────┐  ┌──────────┐│
│  │  Leaflet.js  │  │  Leaflet.heat│  │ Alpine.js││
│  │  (existing)  │  │  (new)       │  │ + Sortable││
│  └─────────────┘  └─────────────┘  └──────────┘│
└─────────────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────┐
│                    SERVER                        │
│  ┌─────────────┐  ┌─────────────┐  ┌──────────┐│
│  │  Geofence    │  │  Route      │  │ Heatmap  ││
│  │  Service     │  │  Service    │  │ API      ││
│  └─────────────┘  └─────────────┘  └──────────┘│
│  ┌─────────────┐                                │
│  │  PostGIS     │                                │
│  │  (extension) │                                │
│  └─────────────┘                                │
└─────────────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────┐
│                 DATABASE                         │
│  PostgreSQL + PostGIS                            │
│  - customers (geography POINT)                   │
│  - location_pings (geography POINT)              │
│  - ST_DWithin() for distance queries             │
└─────────────────────────────────────────────────┘
```

**New Dependencies:**

- `laravel-geobound` or raw PostGIS (free) — spatial queries
- `leaflet.heat` (JS, free) — heatmap plugin
- `sortablejs` (JS, free) — drag-drop reordering
- PostGIS PostgreSQL extension (free)

**Architecture Decisions:**

| Decision           | Choice                       | Rationale                                                  |
| ------------------ | ---------------------------- | ---------------------------------------------------------- |
| Spatial queries    | PostGIS (native extension)   | No separate service, full SQL power, already on PostgreSQL |
| Route optimization | Server-side nearest-neighbor | Simple, fast for 6-15 reps, no external API                |
| Heatmap data       | REST API endpoint            | Decoupled from page load, cacheable                        |
| Map tile caching   | Service Worker intercept     | No server changes, works offline                           |

### Phase 3: Offline & Mobile

```
┌─────────────────────────────────────────────────┐
│                    CLIENT                        │
│  ┌─────────────┐  ┌─────────────┐  ┌──────────┐│
│  │  Service     │  │  IndexedDB  │  │ Capacitor││
│  │  Worker      │  │  (offline)  │  │ (native) ││
│  └─────────────┘  └─────────────┘  └──────────┘│
│  ┌─────────────┐  ┌─────────────┐              │
│  │  Background  │  │  EXIF.js    │              │
│  │  Sync API    │  │  (GPS)      │              │
│  └─────────────┘  └─────────────┘              │
└─────────────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────┐
│                    SERVER                        │
│  ┌─────────────┐  ┌─────────────┐              │
│  │  SyncService │  │  PhotoGps   │              │
│  │  (enhanced)  │  │  Service    │              │
│  └─────────────┘  └─────────────┘              │
└─────────────────────────────────────────────────┘
```

**New Dependencies:**

- `@capacitor/core` (npm, free) — native wrapper
- `@capacitor/camera` (npm, free) — native camera
- `@capacitor/geolocation` (npm, free) — native GPS
- `exif-js` (JS, free) — EXIF extraction
- No new PHP packages

### Phase 4: Security

```
┌─────────────────────────────────────────────────┐
│                    CLIENT                        │
│  ┌─────────────┐  ┌─────────────┐              │
│  │  WebAuthn    │  │  Fingerprint│              │
│  │  API         │  │  Generator  │              │
│  └─────────────┘  └─────────────┘              │
└─────────────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────┐
│                    SERVER                        │
│  ┌─────────────┐  ┌─────────────┐              │
│  │  Webauthn    │  │  Device     │              │
│  │  Service     │  │  Fingerprint│              │
│  └─────────────┘  │  Service    │              │
│  ┌─────────────┐  └─────────────┘              │
│  │  Filament    │                                │
│  │  DeviceMgmt  │                                │
│  │  Resource    │                                │
│  └─────────────┘                                │
└─────────────────────────────────────────────────┘
```

**New Dependencies:**

- `web-auth/webauthn` (PHP, free) — WebAuthn server library
- No JS packages (native Web API)

---

## Cross-Cutting Concerns

### Performance

- Chart data queries: add indexes on `created_at` for all time-series tables
- Heatmap queries: PostGIS spatial indexes on `customers` and `location_pings`
- Map tile cache: max 50MB, LRU eviction
- PDF generation: async via queue for large reports

### Security

- WebAuthn credentials: encrypted at rest
- Device fingerprints: hashed, not stored raw
- Share links: signed, time-limited (5 min TTL)
- GPS data: already stored, no new sensitivity

### Offline

- All new features degrade gracefully offline
- Charts show last-cached data
- Reports queue for generation when online
- Maps use cached tiles
- Biometric falls back to password

### Accessibility

- Chart.js has built-in ARIA support
- Heatmap has text alternative (table view)
- Biometric has password fallback
- All new UI follows existing bilingual pattern

### Localization

- All new strings in `lang/en.php` and `lang/ar.php`
- Chart labels bilingual
- Report templates bilingual
- Map controls bilingual
