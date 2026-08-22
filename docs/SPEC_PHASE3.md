# SPEC — Phase 3: Offline & Mobile

## 3.1 Silent Background Sync

### Actor & Preconditions

- Rep has offline data queued (existing sync engine)
- Browser supports Background Sync API (Chrome) or equivalent

### Behavior

**Current State:**

- Rep taps "Sync" button manually
- SyncQueue component shows pending items
- Sync happens on page load

**New Behavior:**

- Sync happens automatically when connection returns
- No "Sync" button tap needed
- No progress bar (silent)
- Rep sees data update in real-time as sync completes

**Implementation:**

```javascript
// Enhanced sw.js background sync
self.addEventListener("sync", (event) => {
  if (event.tag === "jawla-sync") {
    event.waitUntil(syncOfflineData());
  }
});

// Register sync when data is queued
async function queueForSync(data) {
  const db = await openDB();
  await db.put("sync-queue", data);
  const registration = await navigator.serviceWorker.ready;
  await registration.sync.register("jawla-sync");
}

// Client-side: listen for sync completion
navigator.serviceWorker.addEventListener("message", (event) => {
  if (event.data.type === "SYNC_COMPLETE") {
    // Refresh current page data silently
    Livewire.emit("syncCompleted");
  }
});
```

**Retry Logic:**

- Exponential backoff: 1s, 2s, 4s, 8s, 16s
- Max retries: 5
- Failed items stay in queue, retried on next connection

### Acceptance Criteria

- [ ] Sync triggers automatically on connection return
- [ ] No user action required
- [ ] No progress bar visible
- [ ] Data updates in real-time as sync completes
- [ ] Failed items retry with exponential backoff
- [ ] Max 5 retries before marking as failed
- [ ] Works on Android Chrome (Background Sync API)
- [ ] Fallback: periodic sync attempt on iOS (no Background Sync API)

---

## 3.2 Photo GPS Verification

### Actor & Preconditions

- Rep takes photo via app camera
- GPS permission granted

### Behavior

**EXIF Extraction:**

1. Photo captured via `<input type="file" capture="camera">`
2. Client-side EXIF extraction via `exif-js` library
3. GPS coordinates extracted from EXIF data
4. Coordinates sent to server with photo upload

**Server-side Verification:**

```php
// In PhotoService or new PhotoGpsService
public function verifyPhotoLocation(Photo $photo, float $expectedLat, float $expectedLng): array
{
    $photoLat = $photo->latitude;
    $photoLng = $photo->longitude;

    if (!$photoLat || !$photoLng) {
        return ['valid' => false, 'reason' => 'no_gps_data'];
    }

    $distance = $this->haversine($photoLat, $photoLng, $expectedLat, $expectedLng);

    return [
        'valid' => $distance <= 100, // 100m tolerance
        'distance_m' => round($distance),
        'reason' => $distance > 100 ? 'wrong_location' : null,
    ];
}
```

**UI Flow:**

1. Rep takes photo
2. Photo shows in review screen with GPS badge:
   - ✅ "GPS verified: 23m from customer" (green)
   - ⚠️ "GPS warning: 150m from customer" (yellow)
   - ❌ "GPS mismatch: 500m from customer" (red)
3. Rep can submit regardless (GPS is informational, not blocking)
4. Admin sees GPS verification status in photo gallery

### Acceptance Criteria

- [ ] Photo captures GPS from EXIF data
- [ ] GPS coordinates stored with photo record
- [ ] Distance from expected location calculated
- [ ] Visual badge shows verification status
- [ ] Admin can see GPS status per photo
- [ ] GPS is informational, not blocking submission
- [ ] Works on Android and iOS

### Data Model

```sql
-- Add to existing photos table or photo metadata
ALTER TABLE photos ADD COLUMN latitude DECIMAL(10, 7);
ALTER TABLE photos ADD COLUMN longitude DECIMAL(10, 7);
ALTER TABLE photos ADD COLUMN gps_accuracy_m DECIMAL(5, 2);
ALTER TABLE photos ADD COLUMN gps_verified BOOLEAN DEFAULT false;
```

---

## 3.3 Capacitor Native App

### Actor & Preconditions

- Developer building the app
- Node.js installed
- Android SDK (for Android) or Xcode (for iOS)

### Behavior

**Setup:**

```bash
npm install @capacitor/core @capacitor/cli
npx cap init jawla com.jawla.app
npx cap add android
npx cap add ios
```

**Configuration:**

```json
// capacitor.config.json
{
  "appId": "com.jawla.app",
  "appName": "Jawla",
  "webDir": "public",
  "server": {
    "url": "https://your-domain.com",
    "cleartext": false
  },
  "plugins": {
    "Camera": {
      "permissions": ["camera", "photos"]
    },
    "Geolocation": {
      "permissions": ["location"]
    },
    "LocalNotifications": {
      "smallIcon": "ic_stat_icon_config_sample",
      "iconColor": "#488AFF"
    }
  }
}
```

**Benefits:**

- App Store / Play Store distribution
- Native push notifications (via Capacitor Push Notifications plugin)
- Native camera access
- Native geolocation (better battery than browser)
- Splash screen + app icon
- No browser chrome (full-screen app)

**Build Process:**

```bash
npm run build          # Vite build
npx cap sync           # Copy web assets to native project
npx cap open android   # Open in Android Studio
# Build APK/AAB in Android Studio
```

### Acceptance Criteria

- [ ] App builds as Android APK
- [ ] App builds as iOS IPA (if Mac available)
- [ ] Camera works natively
- [ ] Geolocation works natively
- [ ] Push notifications work
- [ ] Splash screen shows on launch
- [ ] App icon displays correctly
- [ ] Offline functionality works
- [ ] Deep linking works (/app/* routes)

---

## 3.4 Offline Map Tile Caching (Enhanced)

### Behavior

**Same as Phase 2.4**, but with additional features:

**Cache Statistics:**

```javascript
// New API endpoint for cache info
GET /api/cache-stats
Response: {
  tiles_cached: 1234,
  cache_size_mb: 12.5,
  last_cached: "2026-08-20T10:30:00Z"
}
```

**Manual Cache Management:**

- Admin can clear cache from settings
- Rep can see cache size in profile
- "Clear map cache" option in settings

### Acceptance Criteria

- [ ] Cache stats endpoint works
- [ ] Admin can clear cache
- [ ] Rep can see cache size
- [ ] Clear cache button works
