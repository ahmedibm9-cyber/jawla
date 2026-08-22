# SPEC — Phase 1: Analytics & Documents

## 1.1 Dashboard Charts

### Actor & Preconditions

- Admin/Manager logged in
- Permission: `dashboard.view`

### Behavior

**New Chart Widgets** (extend existing `StatsOverviewWidget` pattern with `ChartWidget`):

| Widget                      | Chart Type     | Data Source                                   | Refresh |
| --------------------------- | -------------- | --------------------------------------------- | ------- |
| `SalesTrendWidget`          | Line chart     | `Invoice` grouped by date (last 30 days)      | On load |
| `TopProductsWidget`         | Horizontal bar | `SalesOrderItem` top 10 by revenue            | On load |
| `RepPerformanceChartWidget` | Bar chart      | `Visit` + `SalesOrder` per rep (this week)    | On load |
| `SalesByCategoryWidget`     | Doughnut       | `SalesOrderItem` grouped by `ProductCategory` | On load |
| `DailyCollectionWidget`     | Bar chart      | `Payment` grouped by date (last 14 days)      | On load |
| `VisitCompletionWidget`     | Line chart     | `Visit` status grouped by date (last 7 days)  | On load |

**Chart.js Integration:**

- Install via npm: `npm install chart.js`
- Compile with Vite: `npm run build`
- Chart components rendered via `@push('scripts')` in Blade views (same pattern as Leaflet)
- No Alpine.js chart wrappers — raw Chart.js for minimal footprint

**Layout Presets:**

| Preset     | Widgets                                                             | Target User        |
| ---------- | ------------------------------------------------------------------- | ------------------ |
| Executive  | SalesTrend + SalesByCategory + OutstandingBalance + RepPerformance  | Company owner      |
| Operations | VisitsToday + VisitCompletion + LowStockAlert + RepPerformance      | Operations manager |
| Sales      | SalesToday + TopProducts + PendingQuotations + DailyCollection      | Sales manager      |
| Finance    | OutstandingBalance + DailyCollection + SalesTrend + SalesByCategory | Accountant         |

**Preset Selection:**

- New `dashboard_preset` user preference (stored same as existing `dashboard_widgets`)
- Default: Executive
- User can switch via dropdown in dashboard header
- Switching preset resets widget order to preset default

**Drag-Drop:**

- Existing mechanism works — new widgets auto-register in `AdminPanelProvider`
- New widgets appear in default position, user can reorder/hide

### Acceptance Criteria

- [ ] 6 new chart widgets render with correct data
- [ ] Chart.js loads via Vite bundle (no CDN)
- [ ] 4 layout presets available
- [ ] Switching preset updates widget order
- [ ] Drag-drop works for all widgets including new charts
- [ ] Bilingual labels (AR/EN) for all chart titles/labels
- [ ] Dashboard loads in < 2s with all widgets

### Loading/Empty/Error States

- **Loading:** Skeleton placeholder (existing `ds/skeleton` component)
- **Empty:** "No data for this period" message
- **Error:** Graceful fallback, widget shows error state, other widgets unaffected

### Data Queries

```sql
-- Sales Trend (last 30 days)
SELECT DATE(created_at) as date, SUM(total_amount) as total
FROM invoices WHERE company_id = ? AND created_at >= NOW() - INTERVAL '30 days'
GROUP BY DATE(created_at) ORDER BY date;

-- Top Products (this month)
SELECT p.name, SUM(soi.quantity) as total_qty, SUM(soi.line_total) as revenue
FROM sales_order_items soi
JOIN sales_orders so ON soi.sales_order_id = so.id
JOIN products p ON soi.product_id = p.id
WHERE so.company_id = ? AND so.created_at >= DATE_TRUNC('month', NOW())
GROUP BY p.id, p.name ORDER BY revenue DESC LIMIT 10;

-- Rep Performance (this week)
SELECT u.name, COUNT(DISTINCT v.id) as visits, COUNT(DISTINCT so.id) as orders
FROM users u
LEFT JOIN visits v ON v.user_id = u.id AND v.created_at >= DATE_TRUNC('week', NOW())
LEFT JOIN sales_orders so ON so.user_id = u.id AND so.created_at >= DATE_TRUNC('week', NOW())
WHERE u.company_id = ? AND u.has_role('rep')
GROUP BY u.id, u.name ORDER BY orders DESC;
```

---

## 1.2 Report Templates

### Actor & Preconditions

- Admin/Manager logged in
- Permission: `reports.view`

### Behavior

**Template System:**

- Developer creates Blade template in `resources/views/reports/`
- Template receives `$data` array with pre-queried metrics
- Template defines its own layout (header, footer, charts, tables)
- No user-facing template editor — templates are code, not drag-drop

**Available Reports:**

| Report               | Data                                   | Frequency              | Permission          |
| -------------------- | -------------------------------------- | ---------------------- | ------------------- |
| Daily Rep Summary    | Visits, sales, collections per rep     | On-demand              | `reports.visits`    |
| Customer Statement   | Invoice history, payments, balance     | On-demand per customer | `reports.financial` |
| Sales Comparison     | Month-over-month, quarter-over-quarter | On-demand              | `reports.sales`     |
| Product Performance  | Revenue, quantity, returns per product | On-demand              | `reports.sales`     |
| Outstanding Balances | Unpaid invoices by customer/age        | On-demand              | `reports.financial` |
| Visit Coverage       | Visited vs assigned vs missed per area | On-demand              | `reports.visits`    |

**Generation Flow:**

1. User selects report type + parameters (date range, customer, rep, etc.)
2. App queries data, passes to Blade template
3. Template renders HTML
4. User sees preview in modal
5. User chooses export format + quality

### Acceptance Criteria

- [ ] 6 report types available
- [ ] Each report generates in < 5s
- [ ] Reports are bilingual (AR/EN)
- [ ] Reports respect date range filters
- [ ] Preview shows before download
- [ ] Export options: PDF, Excel, CSV

---

## 1.3 Export Quality

### Behavior

**PDF Export:**

- Screen-optimized: 72 DPI, RGB, smaller file size
- Print-ready: 300 DPI, CMYK-safe colors, proper margins
- User selects quality before download
- Both use same HTML template, different mPDF config

**Excel Export:**

- Uses existing `spatie/simple-excel` package
- Styled headers (bold, colored)
- Auto-column-width
- Number formatting (currency, dates)

**CSV Export:**

- Existing `ReportsPage::exportCsv()` pattern
- UTF-8 BOM for Excel compatibility
- Formula injection protection via `CsvCell::neutralize()`

**PDF Generation (enhanced PdfEngine):**

```php
// New method in PdfEngine
public function renderWithQuality(string $html, string $filename, string $quality = 'screen'): string
{
    $config = match ($quality) {
        'print' => [
            'mode' => 'utf-8',
            'format' => 'A4',
            'dpi' => 300,
            'margin_top' => 25,
            'margin_bottom' => 25,
            'margin_left' => 20,
            'margin_right' => 20,
        ],
        default => [
            'mode' => 'utf-8',
            'format' => 'A4',
            'dpi' => 72,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_left' => 15,
            'margin_right' => 15,
        ],
    };
    // ... generate and save
}
```

### Acceptance Criteria

- [ ] PDF screen-optimized: < 1MB for typical report
- [ ] PDF print-ready: < 3MB for typical report
- [ ] Excel has styled headers and auto-width
- [ ] CSV has UTF-8 BOM and formula protection
- [ ] Quality selector in export UI
- [ ] Both PDF qualities render same layout

---

## 1.4 Native Share Sheet

### Actor & Preconditions

- Any user viewing a generated document
- Browser supports Web Share API (Chrome, Safari, Edge)

### Behavior

**Share Button:**

- Appears next to download button on generated documents
- Uses `navigator.share()` API
- Falls back to copy-link if API unavailable
- Shares file (PDF/Excel) or text (CSV) depending on format

**Flow:**

1. User generates document (PDF/Excel/CSV)
2. Document stored temporarily (5 min TTL)
3. User taps share icon
4. `navigator.share()` called with file blob
5. OS share sheet appears (WhatsApp, email, Bluetooth, etc.)
6. File sent directly without downloading first

**Fallback:**

- If Web Share API unavailable: show "Copy download link" button
- Link expires after 5 minutes

### Acceptance Criteria

- [ ] Share button visible on all generated documents
- [ ] Web Share API triggers OS share sheet
- [ ] Fallback to copy-link on unsupported browsers
- [ ] Shared file is readable by recipient
- [ ] Link expires after 5 minutes
- [ ] Works on Android Chrome, iOS Safari, desktop Chrome

### Error States

- **Share cancelled:** No action needed
- **Share failed:** Show toast "Share failed, try downloading instead"
- **Link expired:** Show toast "Link expired, generate again"
