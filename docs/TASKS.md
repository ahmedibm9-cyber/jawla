# TASKS — Jawla Enhancement Suite

## Phase 1: Analytics & Documents

### 1.1 Chart.js Setup

| Task                              | Files                                  | Est.  | Status |
| --------------------------------- | -------------------------------------- | ----- | ------ |
| Install chart.js via npm          | `package.json`                         | 15min | ⬜     |
| Configure Vite to bundle chart.js | `vite.config.js`                       | 30min | ⬜     |
| Create base ChartWidget class     | `app/Filament/Widgets/ChartWidget.php` | 1h    | ⬜     |

### 1.2 Dashboard Widgets

| Task                                       | Files                                                | Est.  | Status |
| ------------------------------------------ | ---------------------------------------------------- | ----- | ------ |
| SalesTrendWidget (line chart, 30 days)     | `app/Filament/Widgets/SalesTrendWidget.php`          | 2h    | ⬜     |
| TopProductsWidget (horizontal bar, top 10) | `app/Filament/Widgets/TopProductsWidget.php`         | 2h    | ⬜     |
| RepPerformanceChartWidget (bar chart)      | `app/Filament/Widgets/RepPerformanceChartWidget.php` | 2h    | ⬜     |
| SalesByCategoryWidget (doughnut)           | `app/Filament/Widgets/SalesByCategoryWidget.php`     | 2h    | ⬜     |
| DailyCollectionWidget (bar chart, 14 days) | `app/Filament/Widgets/DailyCollectionWidget.php`     | 2h    | ⬜     |
| VisitCompletionWidget (line chart, 7 days) | `app/Filament/Widgets/VisitCompletionWidget.php`     | 2h    | ⬜     |
| Register widgets in AdminPanelProvider     | `app/Providers/Filament/AdminPanelProvider.php`      | 15min | ⬜     |

### 1.3 Layout Presets

| Task                                   | Files                                                | Est.  | Status |
| -------------------------------------- | ---------------------------------------------------- | ----- | ------ |
| Add dashboard_preset preference        | `app/Filament/Pages/Dashboard.php`                   | 1h    | ⬜     |
| Create 4 preset configurations         | `config/dashboard-presets.php`                       | 1h    | ⬜     |
| Preset selector UI in dashboard header | `resources/views/filament/pages/dashboard.blade.php` | 1h    | ⬜     |
| Switch preset resets widget order      | `app/Filament/Pages/Dashboard.php`                   | 30min | ⬜     |

### 1.4 Report Templates

| Task                            | Files                                                    | Est.  | Status |
| ------------------------------- | -------------------------------------------------------- | ----- | ------ |
| Create Blade template directory | `resources/views/reports/`                               | 15min | ⬜     |
| Daily Rep Summary template      | `resources/views/reports/daily-rep-summary.blade.php`    | 3h    | ⬜     |
| Customer Statement template     | `resources/views/reports/customer-statement.blade.php`   | 3h    | ⬜     |
| Sales Comparison template       | `resources/views/reports/sales-comparison.blade.php`     | 2h    | ⬜     |
| Product Performance template    | `resources/views/reports/product-performance.blade.php`  | 2h    | ⬜     |
| Outstanding Balances template   | `resources/views/reports/outstanding-balances.blade.php` | 2h    | ⬜     |
| Visit Coverage template         | `resources/views/reports/visit-coverage.blade.php`       | 2h    | ⬜     |
| ReportGenerator service         | `app/Services/ReportGeneratorService.php`                | 2h    | ⬜     |
| Filament ReportPage             | `app/Filament/Pages/EnhancedReportsPage.php`             | 3h    | ⬜     |

### 1.5 Export Quality

| Task                               | Files                                 | Est.  | Status |
| ---------------------------------- | ------------------------------------- | ----- | ------ |
| Add renderWithQuality to PdfEngine | `app/Services/PdfEngine.php`          | 1h    | ⬜     |
| Screen-optimized config (72 DPI)   | `app/Services/PdfEngine.php`          | 30min | ⬜     |
| Print-ready config (300 DPI)       | `app/Services/PdfEngine.php`          | 30min | ⬜     |
| Excel export with styling          | `app/Services/ExcelExportService.php` | 2h    | ⬜     |
| Quality selector in export UI      | `resources/views/reports/`            | 1h    | ⬜     |

### 1.6 Native Share Sheet

| Task                                 | Files                                                  | Est.  | Status |
| ------------------------------------ | ------------------------------------------------------ | ----- | ------ |
| ShareLink service (signed, 5min TTL) | `app/Services/ShareLinkService.php`                    | 1h    | ⬜     |
| Share button component               | `resources/views/components/ds/share-button.blade.php` | 1h    | ⬜     |
| Web Share API integration            | `resources/js/share.js`                                | 1h    | ⬜     |
| Fallback to copy-link                | `resources/js/share.js`                                | 30min | ⬜     |

**Phase 1 Total: ~35 hours**

---

## Phase 2: Geospatial

### 2.1 PostGIS Setup

| Task                                    | Files            | Est.  | Status |
| --------------------------------------- | ---------------- | ----- | ------ |
| Enable PostGIS extension                | migration        | 30min | ⬜     |
| Add geography columns to customers      | migration        | 1h    | ⬜     |
| Add geography columns to location_pings | migration        | 1h    | ⬜     |
| Populate from lat/lng                   | migration (data) | 1h    | ⬜     |
| Add spatial indexes                     | migration        | 30min | ⬜     |

### 2.2 Geofenced Check-in

| Task                                       | Files                                  | Est.  | Status |
| ------------------------------------------ | -------------------------------------- | ----- | ------ |
| GeofenceService                            | `app/Services/GeofenceService.php`     | 3h    | ⬜     |
| Auto check-in logic in LocationPingService | `app/Services/LocationPingService.php` | 2h    | ⬜     |
| Check-in notification to rep               | Livewire component                     | 1h    | ⬜     |
| Manual override UI                         | Livewire component                     | 1h    | ⬜     |
| Per-customer radius setting                | Filament resource form                 | 1h    | ⬜     |
| Company default radius setting             | Filament settings page                 | 30min | ⬜     |
| Tests                                      | `tests/Feature/GeofenceTest.php`       | 2h    | ⬜     |

### 2.3 Route Optimization

| Task                            | Files                                | Est. | Status |
| ------------------------------- | ------------------------------------ | ---- | ------ |
| RouteService (nearest-neighbor) | `app/Services/RouteService.php`      | 3h   | ⬜     |
| Distance matrix calculation     | `app/Services/RouteService.php`      | 2h   | ⬜     |
| Route suggestion API endpoint   | `routes/api.php`                     | 1h   | ⬜     |
| Route suggestion UI             | Livewire component                   | 3h   | ⬜     |
| Drag-drop reorder (SortableJS)  | `resources/js/sortable.js`           | 2h   | ⬜     |
| Tests                           | `tests/Feature/RouteServiceTest.php` | 2h   | ⬜     |

### 2.4 Heatmap Layers

| Task                           | Files                                        | Est.  | Status |
| ------------------------------ | -------------------------------------------- | ----- | ------ |
| Install leaflet.heat           | `public/leaflet-heat.js`                     | 15min | ⬜     |
| Heatmap API endpoint (3 types) | `routes/api.php`                             | 2h    | ⬜     |
| Heatmap controller             | `app/Http/Controllers/HeatmapController.php` | 1h    | ⬜     |
| Heatmap toggle UI              | Filament page                                | 2h    | ⬜     |
| Heatmap data queries           | `app/Services/HeatmapService.php`            | 2h    | ⬜     |
| Tests                          | `tests/Feature/HeatmapTest.php`              | 1h    | ⬜     |

### 2.5 Offline Map Tiles

| Task                             | Files               | Est.  | Status |
| -------------------------------- | ------------------- | ----- | ------ |
| Add tile caching to sw.js        | `public/sw.js`      | 2h    | ⬜     |
| Cache size management (50MB LRU) | `public/sw.js`      | 1h    | ⬜     |
| Cache clear on logout            | `public/sw.js`      | 30min | ⬜     |
| Tests                            | manual verification | 1h    | ⬜     |

**Phase 2 Total: ~35 hours**

---

## Phase 3: Offline & Mobile

### 3.1 Silent Background Sync

| Task                         | Files                  | Est. | Status |
| ---------------------------- | ---------------------- | ---- | ------ |
| Enhance sw.js sync handler   | `public/sw.js`         | 2h   | ⬜     |
| Client-side sync listener    | `resources/js/sync.js` | 1h   | ⬜     |
| Exponential backoff logic    | `public/sw.js`         | 1h   | ⬜     |
| iOS fallback (periodic sync) | `resources/js/sync.js` | 1h   | ⬜     |

### 3.2 Photo GPS Verification

| Task                        | Files                              | Est.  | Status |
| --------------------------- | ---------------------------------- | ----- | ------ |
| Install exif-js             | `package.json`                     | 15min | ⬜     |
| EXIF extraction client-side | `resources/js/photo-gps.js`        | 1h    | ⬜     |
| PhotoGpsService server-side | `app/Services/PhotoGpsService.php` | 2h    | ⬜     |
| GPS badge UI                | Livewire component                 | 1h    | ⬜     |
| Admin photo GPS status      | Filament resource                  | 1h    | ⬜     |
| Migration (add columns)     | migration                          | 30min | ⬜     |
| Tests                       | `tests/Feature/PhotoGpsTest.php`   | 1h    | ⬜     |

### 3.3 Capacitor Native App

| Task                            | Files                   | Est.  | Status |
| ------------------------------- | ----------------------- | ----- | ------ |
| Install Capacitor               | `package.json`          | 30min | ⬜     |
| Configure capacitor.config.json | `capacitor.config.json` | 1h    | ⬜     |
| Add Android platform            | `android/`              | 1h    | ⬜     |
| Configure native plugins        | `capacitor.config.json` | 1h    | ⬜     |
| Build and test APK              | manual                  | 2h    | ⬜     |
| App icon + splash screen        | `resources/`            | 1h    | ⬜     |
| iOS platform (if Mac available) | `ios/`                  | 2h    | ⬜     |

### 3.4 Cache Management

| Task                     | Files              | Est.  | Status |
| ------------------------ | ------------------ | ----- | ------ |
| Cache stats API endpoint | `routes/api.php`   | 1h    | ⬜     |
| Admin clear cache button | Filament settings  | 1h    | ⬜     |
| Rep cache size display   | Livewire component | 30min | ⬜     |

**Phase 3 Total: ~20 hours**

---

## Phase 4: Security

### 4.1 Biometric Authentication

| Task                             | Files                              | Est.  | Status |
| -------------------------------- | ---------------------------------- | ----- | ------ |
| Install web-auth/webauthn        | `composer.json`                    | 15min | ⬜     |
| WebauthnService                  | `app/Services/WebauthnService.php` | 4h    | ⬜     |
| Registration flow (client)       | `resources/js/webauthn.js`         | 3h    | ⬜     |
| Login flow (client)              | `resources/js/webauthn.js`         | 2h    | ⬜     |
| Critical action confirmation     | Livewire component                 | 2h    | ⬜     |
| Migration (webauthn_credentials) | migration                          | 30min | ⬜     |
| Tests                            | `tests/Feature/WebauthnTest.php`   | 2h    | ⬜     |

### 4.2 Device Fingerprinting

| Task                            | Files                                       | Est.  | Status |
| ------------------------------- | ------------------------------------------- | ----- | ------ |
| Fingerprint generator (client)  | `resources/js/fingerprint.js`               | 2h    | ⬜     |
| DeviceFingerprintService        | `app/Services/DeviceFingerprintService.php` | 2h    | ⬜     |
| Device Management Filament page | `app/Filament/Resources/DeviceResource.php` | 3h    | ⬜     |
| Login integration               | `app/Services/AuthService.php`              | 1h    | ⬜     |
| New device alarm                | `app/Services/AlarmService.php`             | 1h    | ⬜     |
| Migration (user_devices)        | migration                                   | 30min | ⬜     |
| Tests                           | `tests/Feature/DeviceFingerprintTest.php`   | 2h    | ⬜     |

### 4.3 Integration

| Task                       | Files                             | Est. | Status |
| -------------------------- | --------------------------------- | ---- | ------ |
| Enhanced login flow        | `app/Livewire/Auth/LoginForm.php` | 2h   | ⬜     |
| Biometric settings page    | Livewire component                | 1h   | ⬜     |
| Device management settings | Filament settings                 | 1h   | ⬜     |

**Phase 4 Total: ~25 hours**

---

## Summary

| Phase                          | Hours     | Dependencies                      |
| ------------------------------ | --------- | --------------------------------- |
| Phase 1: Analytics & Documents | ~35h      | chart.js (npm)                    |
| Phase 2: Geospatial            | ~35h      | PostGIS, leaflet.heat, sortablejs |
| Phase 3: Offline & Mobile      | ~20h      | exif-js, Capacitor                |
| Phase 4: Security              | ~25h      | web-auth/webauthn                 |
| **Total**                      | **~115h** |                                   |

## Critical Path

1. Phase 1 → Phase 2 (no dependency, can parallel)
2. Phase 3 depends on Phase 2 (offline maps)
3. Phase 4 independent (can start anytime)

## Verification

After each phase:

```bash
make verify  # lint + typecheck + test + build
make test    # full test suite
```
