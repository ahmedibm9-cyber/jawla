# SPEC — Phase 2: Geospatial

## 2.1 Geofenced Check-in

### Actor & Preconditions

- Rep has active work session (checked in for shift)
- GPS permission granted
- Browser/device supports Geolocation API

### Behavior

**Auto Check-in Trigger:**

1. Rep's GPS ping arrives at server (existing `LocationPingService`)
2. Service calculates distance to nearest unvisited customer in today's assignment
3. If distance <= configured radius (default 100m): auto-create `Visit` record
4. Rep receives notification: "Checked in at [Customer Name]"
5. Rep can dismiss or override (if wrong customer)

**Distance Calculation:**

```sql
-- PostGIS query
SELECT c.id, c.name,
  ST_Distance(
    ST_SetSRID(ST_MakePoint(:lng, :lat), 4326)::geography,
    ST_SetSRID(ST_MakePoint(c.longitude, c.latitude), 4326)::geography
  ) as distance_m
FROM customers c
JOIN customer_assignments ca ON ca.customer_id = c.id
WHERE ca.user_id = :rep_id
  AND ca.route_id = :route_id
  AND c.latitude IS NOT NULL
  AND c.longitude IS NOT NULL
ORDER BY distance_m ASC
LIMIT 1;
```

**Configuration:**

- Default radius: 100m (company setting)
- Per-customer override: customer edit form has "Check-in radius" field
- Valid range: 50m - 500m
- Admin can set company default in settings

**Deduplication:**

- No double check-in within 30 minutes of last check-in at same customer
- If rep is within radius of multiple customers, use closest

**Manual Override:**

- Rep can tap "Check in here" button on customer list
- Bypasses geofence, works for customers without GPS coordinates

### Acceptance Criteria

- [ ] Auto check-in triggers within 100m of customer
- [ ] Check-in creates Visit record with `checkin_latitude`, `checkin_longitude`, `checkin_at`
- [ ] Notification shown to rep on auto check-in
- [ ] Rep can dismiss wrong auto check-in
- [ ] No double check-in within 30 minutes
- [ ] Manual check-in still works
- [ ] Per-customer radius configurable (50-500m)
- [ ] Company default radius configurable

### Data Changes

```sql
-- New column on customers table
ALTER TABLE customers ADD COLUMN checkin_radius_m INTEGER DEFAULT 100;
```

### Loading/Empty/Error States

- **GPS unavailable:** Show warning, disable auto check-in, manual only
- **GPS inaccurate:** Log accuracy, skip check-in if accuracy > 100m
- **No customers nearby:** Silent, no action

---

## 2.2 Route Optimization

### Actor & Preconditions

- Rep has active work session
- Today's visit assignments loaded
- At least 2 customers with GPS coordinates

### Behavior

**Smart Suggestion:**

1. Rep opens "Today's Customers" view
2. App shows optimized order based on nearest-neighbor algorithm
3. Suggested order displayed as numbered list with distance/time estimates
4. Rep can accept (tap "Use suggested order") or drag-to-reorder manually

**Algorithm:**

- Nearest-neighbor from current location (or starting point if no ping)
- Optional: add time windows if customer has preferred visit times
- Calculate total route distance and estimated time

**UI:**

```
┌─────────────────────────────────┐
│ Today's Route (Optimized)       │
│ ─────────────────────────────── │
│ 1. Customer A (2.3 km, ~5 min) │
│ 2. Customer B (4.1 km, ~8 min) │
│ 3. Customer C (1.8 km, ~4 min) │
│ ─────────────────────────────── │
│ Total: 8.2 km, ~17 min driving │
│                                 │
│ [Accept Route] [Reorder Manually]│
└─────────────────────────────────┘
```

**Manual Override:**

- Drag-and-drop reordering via Alpine.js + SortableJS
- Distance/time estimates recalculate on reorder
- "Reset to suggested" button to revert

**Distance Matrix:**

- Pre-calculate on server using Haversine formula
- For more accuracy: use OSRM API (self-hosted, free)
- Cache distances for same-day requests

### Acceptance Criteria

- [ ] Suggested order shown when 2+ customers with GPS
- [ ] Nearest-neighbor from current location
- [ ] Distance and time estimates shown per leg
- [ ] Total distance/time shown
- [ ] Rep can accept suggested order
- [ ] Rep can drag-to-reorder
- [ ] "Reset to suggested" button works
- [ ] No suggestions if < 2 customers with GPS

### Data Model

```sql
-- Route suggestions stored for learning (optional)
CREATE TABLE route_suggestions (
  id BIGSERIAL PRIMARY KEY,
  company_id BIGINT NOT NULL,
  user_id BIGINT NOT NULL,
  work_session_id BIGINT NOT NULL,
  suggested_order JSONB NOT NULL, -- [{customer_id, distance_m, eta_seconds}]
  accepted BOOLEAN DEFAULT false,
  actual_order JSONB, -- what rep actually did
  created_at TIMESTAMP DEFAULT NOW()
);
```

---

## 2.3 Heatmap Layers

### Actor & Preconditions

- Admin/Manager logged in
- Permission: `reports.view`

### Behavior

**Three Toggleable Layers:**

| Layer            | Data                                    | Color Scale                     |
| ---------------- | --------------------------------------- | ------------------------------- |
| Sales Density    | Invoice totals per customer location    | Green (high) → Red (low)        |
| Visit Frequency  | Visit count per customer (last 30 days) | Blue (many) → Gray (few)        |
| Customer Density | Customer count per grid cell            | Purple (dense) → White (sparse) |

**Implementation:**

- Use `Leaflet.heat` plugin (lightweight, free)
- Data loaded via AJAX from new API endpoint
- Admin toggles layers via map controls
- Only one layer visible at a time

**Data Endpoint:**

```
GET /admin/api/heatmap/{type}
Response: [{lat, lng, value}]
```

**Heatmap Data Query (Sales Density):**

```sql
SELECT c.latitude as lat, c.longitude as lng,
  COALESCE(SUM(i.total_amount), 0) as value
FROM customers c
LEFT JOIN invoices i ON i.customer_id = c.id
  AND i.created_at >= NOW() - INTERVAL '30 days'
WHERE c.latitude IS NOT NULL
  AND c.company_id = :company_id
GROUP BY c.id, c.latitude, c.longitude;
```

### Acceptance Criteria

- [ ] Three heatmap layers available
- [ ] Toggle between layers via map control
- [ ] Data loads via AJAX (not page reload)
- [ ] Heatmap renders smoothly with 100+ points
- [ ] Color scale matches legend
- [ ] Bilingual legend labels

### UI

```
┌─────────────────────────────────┐
│ [🗺️ Map] [🔥 Sales] [📍 Visits] [👥 Density] │
│ ─────────────────────────────── │
│                                 │
│     Leaflet map with heatmap    │
│                                 │
│ ─────────────────────────────── │
│ Legend: ■ High  ■ Medium  ■ Low │
└─────────────────────────────────┘
```

---

## 2.4 Offline Map Auto-Caching

### Actor & Preconditions

- Rep has PWA installed (or using browser)
- Service Worker registered (existing `public/sw.js`)

### Behavior

**Auto-Cache Strategy:**

1. When Leaflet map loads tiles, Service Worker intercepts fetch requests
2. Tiles cached in `jawla-map-tiles-v1` cache
3. On subsequent visits, tiles served from cache first
4. No user action required — caching is automatic

**Implementation:**

```javascript
// In sw.js - add tile caching strategy
const TILE_CACHE = "jawla-map-tiles-v1";

self.addEventListener("fetch", (event) => {
  const url = new URL(event.request.url);

  // Cache tile requests from OpenStreetMap
  if (
    url.hostname === "tile.openstreetmap.org" &&
    url.pathname.match(/^\/\d+\/\d+\/\d+\.png$/)
  ) {
    event.respondWith(
      caches.open(TILE_CACHE).then((cache) => {
        return cache.match(event.request).then((cached) => {
          if (cached) return cached;
          return fetch(event.request).then((response) => {
            if (response.ok) cache.put(event.request, response.clone());
            return response;
          });
        });
      })
    );
  }
});
```

**Cache Management:**

- Max cache size: 50MB (configurable)
- LRU eviction when limit reached
- Cache cleared on logout (existing `PURGE_USER_DATA` message)
- Cache version in key name for easy invalidation

### Acceptance Criteria

- [ ] Map tiles cached automatically on first view
- [ ] Cached tiles work offline
- [ ] Cache size limited to 50MB
- [ ] LRU eviction when full
- [ ] Cache cleared on logout
- [ ] No user action required
- [ ] Works on Android Chrome, iOS Safari

### Error States

- **Cache full:** Oldest tiles evicted, new tiles cached
- **Tile fetch fails:** Show gray tile with retry
- **Offline + no cache:** Show "Map unavailable offline" message
