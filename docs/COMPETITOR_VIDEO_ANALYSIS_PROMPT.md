# Competitor App Video — Complete Spec Sheet Extraction Prompt

**Purpose:** Feed this prompt to a multimodal AI (Gemini 1.5 Pro, GPT-4o, Claude 3.5 Sonnet with video input) along with a screen-recording/video file of a competitor's field-sales/CRM app. The AI will output an exhaustive, structured specification document capturing **every observable detail** — UI, UX, features, permissions, roles, flows, data models, edge cases, and architectural inferences.

---

## 🎯 PROMPT (COPY-PASTE INTO AI WITH VIDEO ATTACHED)

```
You are a Senior Product Architect & Competitive Intelligence Analyst. I am giving you a VIDEO (screen recording / demo / walkthrough) of a COMPETITOR'S FIELD-SALES CRM/ERP APPLICATION. Your task: extract **EVERY OBSERVABLE DETAIL** and produce a **COMPLETE SPECIFICATION SHEET** — as if you were reverse-engineering the product for a clone build.

Assume the video may show: admin panel, rep mobile/PWA, manager views, settings, onboarding, error states, offline behavior, sync indicators, notifications, reports, imports/exports, integrations, etc. The video may be narrated, silent, fast, or incomplete. Extract what is visible; infer what is probable; mark uncertainties explicitly.

OUTPUT FORMAT: A single, massive Markdown document (`COMPETITOR_SPEC_SHEET.md`) with the sections below. Be exhaustive. Use tables, hierarchies, and explicit references to timestamps (MM:SS) where possible. If something is NOT visible but strongly implied, mark `[INFERRED]`. If visible, mark `[OBSERVED @ MM:SS]`.

================================================================================
SECTION 0 — META & CONTEXT
================================================================================
- Competitor name / product name (from logo, URL, narration)
- Video source (YouTube link, demo file name, sales deck, etc.)
- Video duration, resolution, date published
- Platform(s) shown: Web admin, iOS, Android, PWA, desktop Electron, etc.
- Language(s) in UI (Arabic/RTL? English/LTR? Others?)
- Target market / geography visible (currency, VAT, address formats, phone masks)
- Narrator / presenter role (CEO, PM, sales engineer, customer)
- Video type: marketing demo, technical walkthrough, customer testimonial, internal leak

================================================================================
SECTION 1 — INFORMATION ARCHITECTURE & NAVIGATION
================================================================================
1.1 Global Navigation (Admin)
- Top-level menu items (exact labels, icons, order)
- Sidebar vs top-bar vs drawer
- Collapsible sections, accordion groups
- Search / command palette (Cmd+K) presence
- Breadcrumbs pattern
- Deep-linking / URL structure visible (e.g., `/admin/customers/123/edit`)

1.2 Global Navigation (Rep App / Mobile)
- Bottom tab bar items (labels, icons, order, badges)
- Hamburger / drawer contents
- Floating action button (FAB) behavior
- Gesture navigation (swipe back, pull-to-refresh, long-press)
- Onboarding / first-run flow screens

1.3 Context Switching
- Company / tenant selector (multi-tenant?)
- Role switcher (if admin impersonates rep)
- Language toggle (AR/EN) — position, behavior, persistence
- Theme toggle (light/dark/system)

================================================================================
SECTION 2 — ROLES, PERMISSIONS & ACCESS CONTROL
================================================================================
2.1 Roles Observed / Named
- List every role label seen in UI (e.g., "System Admin", "Sales Manager", "Warehouse Keeper", "Field Rep", "Finance", "Executive")
- Role assignment UI (admin panel: user edit → roles dropdown / checkboxes)

2.2 Permission Granularity (Inferred from UI)
- Resource-level: Create / Read / Update / Delete / Approve / Reject / Export / Print
- Field-level: Hidden / Read-only / Editable per role (e.g., cost price hidden from rep)
- Row-level: Own records only / Team / Region / All (visible in list filters)
- Action-level: Buttons conditionally rendered (e.g., "Approve" only for Manager)

2.3 Access Control Surfaces
- Admin panel route guards (403 pages shown?)
- Rep app route guards (blocked screens, redirect to login)
- API token / scopes visible in settings
- Impersonation / "Login as" feature
- Session management (active sessions list, revoke)

2.4 Authentication & Security UI
- Login screen: email/phone, password, biometric, SSO (Google, Microsoft, SAML), MFA (TOTP, SMS)
- Password policy hint (min length, complexity)
- Forgot password / reset flow
- Remember me / trusted device
- Session timeout warning modal
- Concurrent session limit notice

================================================================================
SECTION 3 — MASTER DATA & CONFIGURATION (ADMIN)
================================================================================
For EACH master data entity observed, document:

3.1 Entity Catalog (from sidebar / resources)
| Entity | Fields Visible (label + type) | Required | Validation Hints | Relations Shown | Import/Export | Bulk Actions | Audit Log |
|--------|-------------------------------|----------|------------------|-----------------|---------------|--------------|-----------|
| Companies | name_ar, name_en, tax_no, vat%, logo, address, phone, email, website, currency, timezone | | | warehouses, users, routes | CSV/Excel | activate/deactivate | |
| Users | name, email, phone, employee_code, role(s), avatar, status, language, timezone, van_warehouse, cash_box | | | company, route, territory | | reset password, deactivate | |
| Products | sku, name_ar, name_en, barcode, unit, category, brand, vat_applicable, base_price, cost_price, min_price, max_price, image, description, is_active | | | prices, stock, batches | | | |
| Customers | code, name_ar, name_en, phone, email, address, lat/lng, route, territory, customer_group, payment_terms, credit_limit, status (pending/approved/rejected), assigned_rep, tax_number, vat_exempt | | | visits, orders, invoices, balances | | approve/reject | |
| Routes | name_ar, name_en, region, color, sequence, assigned_reps, customers (ordered) | | | reps, visits | | | |
| Warehouses | name, type (main/van), address, manager, is_default | | | stock, transfers | | | |
| Price Lists / Price Books | name, currency, validity_start, validity_end, customer_groups, products (price + min/max) | | | | | | |
| Tax Templates | name, lines (tax_type, rate, applies_to) | | | | | | |
| Bank Accounts | bank_name, account_name, iban, swift, currency, is_default | | | | | | |
| Payment Modes | name_ar, name_en, type (cash/card/transfer/wallet), requires_reference | | | | | | |
| Expense Categories | name_ar, name_en, requires_receipt, limit_per_txn, limit_daily | | | | | | |
| Territories / Regions | name, parent, manager | | | | | | |
| Categories / Brands / Units | hierarchical? | | | | | | |
| Numbering Series | prefix, current_number, padding, entity_type (invoice/proforma/return) | | | | | | |

3.2 Configuration Screens
- General settings: company info, logo, timezone, date format, number format, default currency
- VAT / tax settings: rates, inclusive/exclusive, registration number display
- Printing / PDF: template selector, logo placement, QR code position, font, paper size
- Notifications: email/SMS/push templates, triggers, channels per role
- Integration settings: ERP sync (Odoo, SAP, Netsuite), accounting (Xero, QuickBooks), SMS gateway, WhatsApp Business API, Map provider (Google/Mapbox/OSM), Payment gateway
- Feature flags / modules toggle (enable/disable: returns, expenses, van transfers, batches, ZATCA, etc.)
- Backup / export / data retention policies

================================================================================
SECTION 4 — REP APP (FIELD / MOBILE / PWA) — CORE FLOWS
================================================================================
For EACH flow, document: screens (in order), fields, actions, validations, error states, offline behavior, sync indicators, success/toast messages, navigation transitions.

4.1 Daily Start / Work Session
- "Start Day" / "Begin Route" screen: GPS capture, timestamp, selfie?, vehicle odometer?, checklist (vehicle condition, stock count)
- Route summary: customer count, estimated km, estimated time, load summary
- Start button → creates `work_session` record

4.2 Customer Visit Flow
4.2.1 Visit List / Today's Schedule
- Grouped by status: Pending / In Progress / Completed / Missed / Cancelled
- Sort: sequence, distance, time window
- Card shows: customer name/code, address, time window, status badge, distance from current location, quick actions (call, navigate, start)
- Pull-to-refresh, filter by status, search

4.2.2 Visit Detail / Execution
- Customer header: name, code, phone (tap to call), address (tap to navigate), outstanding balance, credit limit usage %, last visit date
- GPS Geofence Check:
  - Radius shown (meters/km)
  - Current distance display
  - "Confirm Arrival" button state: enabled / disabled / "Outside range — manual confirm?"
  - Manual confirm: requires reason / photo / signature?
  - Auto-check-in on enter geofence?
  - Exit geofence → auto-complete or prompt?
- Visit Actions (tabs or accordion):
  - **Sell / Order**: product search, category drill, barcode scan, qty stepper, price display (read-only or negotiable?), discount field, VAT toggle, line subtotal, order summary, submit → creates Order/Invoice
  - **Collect Payment**: invoice selector (unpaid/partial), amount, payment mode, reference, cheque date, photo of cheque, print receipt, update balance
  - **Return**: invoice selector, product picker, qty, reason code, condition (saleable/damaged), credit note preview, stock restore
  - **Complaint**: type, description, photo, urgency, assign to manager, SLA timer
  - **New Customer**: form (all fields from 3.1), GPS auto-fill, photo of shop/sign, status = Pending
  - **Stock Check / Van Inventory**: search, filter by category, show qty per warehouse (van + main), low stock badge
  - **Visit Report / Notes**: free text, structured fields (competitor activity, shelf share, display compliance), photos, voice note
  - **Signature Capture**: customer sign, rep sign, clear, redo
  - **End Visit**: summary screen (orders, collections, returns, complaints, duration, distance), "Complete Visit" button

4.2.3 Offline / Sync Behavior
- Offline banner / indicator (color, position)
- Local-first writes: which actions queue locally?
- Sync trigger: auto on connect / manual pull-to-sync / background
- Conflict resolution UI (server wins / merge / prompt)
- Pending sync queue screen with retry buttons
- Data freshness timestamps (last synced 2 min ago)

4.3 Stock & Inventory (Rep View)
- Van stock list: search, barcode scan, category filter, qty, batch/expiry, low stock threshold
- Stock request / replenishment: create request → manager approve → warehouse pick → rep receive (scan)
- Inter-van transfer: select target rep, products, qty, reason, GPS handoff photo
- Physical count: cycle count mode, scan → enter counted qty → variance → submit for approval
- Expiry / near-expiry alerts
- Damaged goods write-off

4.4 Quotation / Pricing Negotiation (If Visible)
- Request price: product + qty → send to manager
- Manager response: approved price, min/max range, validity
- Rep counter-offer within range
- Audit trail of negotiation steps
- Convert to Proforma / Order

4.5 Proforma / Invoice Generation
- From order or direct
- Floor price enforcement warning
- Bank details auto-included
- QR code (ZATCA / generic)
- PDF preview, WhatsApp share, email, print
- Numbering series selection

4.6 End of Day / Close Session
- Summary: visits done, missed, orders, collections, returns, complaints, expenses, km driven, duration
- Cash box reconciliation: expected vs counted, variance, notes
- Expense entry: category, amount, receipt photo, description
- Sync finalization
- "End Day" → locks session, uploads all

================================================================================
SECTION 5 — ADMIN PANLE — OPERATIONAL WORKFLOWS
================================================================================
5.1 Visit Management (Manager)
- Daily visit assignment: drag-drop customers to reps, optimize route, publish
- Real-time tracking: map with rep locations, visit status pins, geofence circles
- Visit approval / rejection (custom visits)
- Visit reports review: list, filter, read, comment, escalate
- Missed visit analysis: reasons, patterns, alerts

5.2 Order / Invoice Approval
- Pending orders queue (rep submitted)
- Price approval: min/max range, override with reason
- Credit limit check: block / warn / approve with guarantor
- Stock availability check (real-time across warehouses)
- Convert to Invoice: atomic, numbering, PDF, email/SMS
- Invoice cancellation / reversal (compensating)

5.3 Stock Management (Warehouse)
- Main warehouse stock grid: product, batch, qty, reserved, available, expiry
- Stock import: CSV/Excel template download, mapping UI, preview, validate, commit, error report
- Stock adjustment: reason codes, approval if > threshold
- Van loading: pick list, scan confirm, transfer creates movement rows
- Goods In Transit: PO receipt, GRN, landed cost allocation
- Batch / serial tracking
- Cycle count scheduling & execution

5.4 Pricing & Price Lists
- Base price list management
- Customer-specific price overrides
- Promotional pricing: date ranges, volume tiers, customer groups
- Price change approval workflow
- Price history / audit log

5.5 Purchase / Procurement (If Visible)
- Purchase request (rep) → Sales Manager review → Purchasing review → PO creation
- Supplier management: portal, quotes comparison, rating
- PO dispatch, acknowledgment, ASN, receipt, 3-way match
- Landed cost: freight, customs, insurance allocation

5.6 Alarms / Notifications Center
- Alarm types: Out of Stock, Credit Limit, Visit Missed, Complaint SLA, Payment Overdue, Target Achievement, New Lead
- Severity: Critical / Warning / Info
- Recipients per type per role
- Acknowledgment / resolution workflow
- Escalation timers
- Dashboard: filters, grouping, bulk actions

5.7 Reports & Analytics
- Report catalog: list every report name visible
- For each: parameters (date range, rep, route, customer, product, warehouse), output (table, chart, PDF, Excel, scheduled email), drill-down capability
- Dashboards: KPI cards, charts (bar, line, pie, funnel), real-time vs cached
- Export scheduling: recipients, frequency, format

5.8 Financial / Accounting
- Chart of accounts (if visible)
- Journal entries (auto from invoices/collections/returns/expenses)
- Bank reconciliation
- VAT return preparation (ZATCA / generic)
- Customer statements
- Aging reports
- Cash flow forecast

================================================================================
SECTION 6 — DATA MODEL INFERENCE (FROM UI)
================================================================================
For each entity observed, infer:
- Table name (snake_case)
- Columns (name, type, nullable, default, indexes, FK)
- Relationships (belongsTo, hasMany, manyToMany, polymorphic)
- Soft deletes? Audit columns (created_by, updated_by, deleted_at)?
- Enum values (statuses, types)
- Computed / virtual fields (balance, available_qty, credit_utilization)
- Partitioning / sharding hints (by company_id? date?)

Present as Mermaid ER diagram code block + table.

================================================================================
SECTION 7 — UI/UX DESIGN SYSTEM (PIXEL-LEVEL)
================================================================================
7.1 Color Palette
- Primary, secondary, accent, success, warning, error, info (hex + usage context)
- Light / dark mode variants
- Semantic color mapping (e.g., `color-danger` used for delete, overdue, critical alarm)

7.2 Typography
- Font families (Arabic: Noto Kufi / Cairo / Amiri? Latin: Inter / Roboto / System?)
- Scale: display, headline, title, body, caption, overline (sizes, weights, line-heights)
- RTL adjustments (letter-spacing, word-spacing)

7.3 Spacing & Layout
- Base unit (4px? 8px?)
- Grid: columns, gutters, breakpoints (mobile, tablet, desktop)
- Container max-widths
- Border radius scale
- Shadow / elevation scale

7.4 Component Inventory (every distinct component seen)
| Component | Variants | States (default/hover/focus/disabled/loading/error/success) | Props Observed | Accessibility (ARIA, focus trap, keyboard) |
|-----------|----------|-------------------------------------------------------------|----------------|--------------------------------------------|
| Button | primary/secondary/outline/ghost/icon/fab | | size, loading, disabled, fullWidth | |
| Input | text/textarea/select/date/number/phone/email/password | | label, hint, error, prefix, suffix, mask | |
| Table / DataGrid | density, striped, hover, sortable, filterable, paginated, virtualized | | columns, rowActions, bulkActions, selection | |
| Card | elevated/outlined/filled | | header, media, actions, footer | |
| Modal / Drawer | sizes, scrollable, persistent | | title, closeOnOverlayClick, actions | |
| Toast / Snackbar | success/error/warning/info | | duration, action, persist | |
| Badge / Chip | dot, label, icon, removable | | color, size | |
| Avatar | image, initials, fallback, status indicator | | size, shape | |
| Dropdown / Menu | trigger, positioning, groups, dividers | | searchable, multiSelect | |
| Date/Time Picker | single, range, datetime, relative presets | | locale, min/max, disabledDates | |
| Map / Geofence | Leaflet/Mapbox/Google, markers, circles, clustering | | offline tiles, current location | |
| Signature Pad | canvas, clear, undo, stroke width/color | | required, background | |
| Barcode/QR Scanner | camera, gallery, torch, formats | | vibration, beep, overlay | |
| Chart | line, bar, pie, donut, area, funnel | | tooltip, legend, zoom, export | |

7.5 Icon System
- Library (Lucide, Heroicons, Material, custom SVG, FontAwesome)
- Naming convention
- RTL mirroring behavior (which icons flip)

7.6 Motion & Micro-interactions
- Transition durations, easings
- Loading states: skeleton, spinner, progress bar, shimmer
- Success/check animations
- Swipe gestures, pull-to-refresh physics
- Modal enter/exit, drawer slide

7.7 Responsive Breakpoints (observed)
- Mobile (<640), Tablet (640-1024), Desktop (>1024)
- Layout shifts: sidebar → drawer, tabs → accordion, table → cards

================================================================================
SECTION 8 — TECHNICAL ARCHITECTURE CLUES (FROM UI)
================================================================================
8.1 Frontend Framework Signals
- Livewire / Alpine / Inertia / Vue / React / Svelte / vanilla? (inspect network, devtools if visible)
- SSR vs CSR vs SSG (view source on load)
- Hydration markers
- Component library (Filament, Tailwind UI, Radix, shadcn, custom)

8.2 State Management
- Optimistic updates visible? (UI updates before server response)
- Local storage / IndexedDB usage (offline queue)
- WebSocket / SSE / polling for real-time (alarms, tracking, sync status)
- Cache invalidation patterns (stale-while-revalidate?)

8.3 API Patterns
- REST vs GraphQL vs tRPC (network tab if visible)
- Versioning in URL (/api/v1/)
- Response envelope: `{ data, meta, links }` vs raw
- Error format: RFC 7807 / custom
- Pagination: cursor vs offset vs page
- File upload: direct to S3 (presigned) vs multipart to API

8.4 Backend Hints
- Laravel? (routes, CSRF token name, `X-CSRF-TOKEN`, `laravel_session` cookie, `_token` field, `_method` override)
- Queue: job status polling, horizon UI
- Scheduled commands: "Last run 2 min ago"
- Multi-tenancy: `company_id` in every URL / response?
- Feature flags: LaunchDarkly / Flipper / custom

8.5 Infrastructure
- Cloudflare headers (`cf-ray`, `cf-cache-status`)
- Load balancer cookies
- CDN asset URLs (Vite manifest, hashed filenames)
- S3 / R2 / Cloudflare R2 for uploads
- Sentry / Bugsnag / Rollbar script tags

================================================================================
SECTION 9 — LOCALIZATION & ACCESSIBILITY (A11Y)
================================================================================
9.1 RTL / Arabic
- `dir="rtl"` on `<html>` toggle
- Logical CSS properties (`margin-inline-start`, `padding-inline-end`)
- `:dir(rtl)` selectors
- Icon mirroring (chevron-left ↔ chevron-right)
- Text alignment flip
- Number formatting: Arabic-Indic digits (٠١٢٣٤٥٦٧٨٩) vs Western
- Calendar: Hijri / Gregorian toggle
- Font: Noto Kufi Arabic / Cairo / Amiri — fallback stack

9.2 Accessibility (WCAG 2.2 AA)
- Skip to main content link
- Focus visible outlines (not `outline: none`)
- Focus order logical
- ARIA labels on icon buttons
- Live regions for toasts / alerts (`aria-live="polite"` / `assertive`)
- Color contrast (visual check)
- Text resize up to 200% without horizontal scroll
- Keyboard operability: all interactive elements reachable, modals trap focus
- Screen reader announcements for: route changes (Livewire), form errors, sync status, alarm arrival
- Alt text on images / icons
- Form labels associated (`<label for>` or `aria-labelledby`)
- Error messages linked to inputs (`aria-describedby`)

================================================================================
SECTION 10 — EDGE CASES, ERROR STATES & EMPTY STATES
================================================================================
Document EVERY error/empty state screen seen:
| Context | Trigger | Message (verbatim) | Action Buttons | Illustration | Retry Logic |
|---------|---------|-------------------|----------------|--------------|-------------|
| Visit start | GPS timeout | "Unable to get location. Enable GPS or enter manually." | [Retry GPS] [Manual Entry] | 📍 icon | 3 retries then fallback |
| Order submit | Stock insufficient | "Only 5 units available in van. Request transfer?" | [Adjust Qty] [Request Transfer] [Cancel] | ⚠️ | |
| Sync | Conflict | "Server version differs. Keep local or use server?" | [Keep Mine] [Use Theirs] [View Diff] | 🔄 | |
| Login | Rate limited | "Too many attempts. Try again in 5 minutes." | [Wait] [Forgot Password?] | ⏱️ | |
| Customer list | Empty | "No customers assigned. Contact your manager." | [Request Customer] | 📭 | |
| Stock import | Invalid row | "Row 47: SKU 'ABC-123' not found. Skipped." | [Download Errors] [Retry Import] | 📊 | |

================================================================================
SECTION 11 — ONBOARDING & HELP
================================================================================
- First-run flow: slides, interactive tutorial, tooltip coach marks
- Contextual help: `?` icons, hover tooltips, "Learn more" links to docs
- In-app documentation / knowledge base search
- Video tutorials embedded
- Support chat widget (Intercom, Crisp, custom)
- Feedback / bug report button (shake to report?)
- Keyboard shortcuts cheat sheet (Cmd+/)

================================================================================
SECTION 12 — PERFORMANCE INDICATORS (FROM VIDEO)
================================================================================
- Page load times (visual: skeleton → content)
- List rendering: virtualized? (scroll 1000 rows)
- Image loading: blur-up, lazy, priority
- Transition jank (60fps? dropped frames?)
- Offline-to-online sync duration (progress bar)
- PDF generation time (spinner duration)
- Map tile load speed
- Search latency (debounce visible?)

================================================================================
SECTION 13 — COMPETITIVE DIFFERENTIATORS & GAPS (VS JAWLA)
================================================================================
| Feature / Capability | Competitor | Jawla (Current) | Gap | Priority to Close |
|----------------------|------------|-----------------|-----|-------------------|
| Offline-first PWA | ✅ Full | 🟡 Beta | | |
| ZATCA Phase 2 | ✅ | 🟡 Phase 1 only | | |
| AI visit coaching | ✅ | ❌ | | |
| Route optimization | ✅ | ❌ | | |
| ... | | | | |

================================================================================
SECTION 14 — TIMESTAMP INDEX (FOR REVIEW)
================================================================================
| MM:SS | Screen / Flow | Key Observation |
|-------|---------------|-----------------|
| 00:12 | Login | SSO + MFA |
| 00:45 | Admin sidebar | 14 resources |
| 01:30 | Customer create | Leaflet map picker |
| ... | | |

================================================================================
SECTION 15 — UNCERTAINTIES & ASSUMPTIONS
================================================================================
List everything you COULD NOT see but inferred, with confidence (High/Med/Low):
- "Backend uses Laravel — [INFERRED, High] — CSRF token name `_token`, `laravel_session` cookie, route `/admin` with Filament-style resources"
- "Real-time tracking uses WebSockets — [INFERRED, Med] — rep location updates every 5s on map without refresh"
- "Batch tracking enabled — [INFERRED, Low] — expiry column visible but no batch selection in order flow"

================================================================================
DELIVERABLE
================================================================================
Write the complete specification to `COMPETITOR_SPEC_SHEET.md`. Include:
- Mermaid ER diagram
- Mermaid user flow diagrams for top 5 flows
- Tables for every section above
- Timestamp index
- Executive summary (1 page) at top: "If we clone this, we need X weeks, Y devs, Z risks"

BEGIN EXTRACTION NOW. WATCH THE VIDEO FRAME-BY-FRAME IF NECESSARY. BE OBSESSIVE.
```

---

## 🎬 HOW TO USE THIS PROMPT

### 1. Prepare the Video

- **Best:** High-res screen recording (1080p+), 60fps, full flows start-to-finish
- **Acceptable:** YouTube demo, sales webinar recording, customer testimonial with screen share
- **Split long videos:** >30 min → chunk into 10-min segments, run prompt on each, merge outputs

### 2. Choose Your AI

| Model                 | Video Input                       | Context Window | Best For                             |
| --------------------- | --------------------------------- | -------------- | ------------------------------------ |
| **Gemini 1.5 Pro**    | ✅ Native (2M tokens)             | 2M             | Longest videos, most detail          |
| **GPT-4o**            | ✅ Native (128K)                  | 128K           | Balanced                             |
| **Claude 3.5 Sonnet** | ❌ (use transcript + screenshots) | 200K           | Best reasoning if you extract frames |
| **Gemini 1.5 Flash**  | ✅ Native                         | 1M             | Faster/cheaper                       |

### 3. Frame Extraction (if model doesn't take video directly)

```bash
# Extract 1 frame/sec + keyframes at scene changes
ffmpeg -i competitor_demo.mp4 -vf "fps=1,select='gt(scene,0.3)'" -vsync vfr frames/frame_%04d.png

# Or use: ffmpeg -i video.mp4 -vf "select=eq(pict_type\,I)" -vsync vfr keyframes/key_%04d.png
```

Feed frames + transcript to Claude/GPT.

### 4. Run the Prompt

- Paste prompt + attach video (or frames + transcript)
- Let it run. It will take 5–30 min depending on video length.
- Save output as `COMPETITOR_SPEC_SHEET.md`

### 5. Post-Process

```bash
# Validate Mermaid diagrams render
npx @mermaid-js/mermaid-cli -i COMPETITOR_SPEC_SHEET.md -o spec.pdf

# Convert to Notion / Confluence / GitBook
pandoc COMPETITOR_SPEC_SHEET.md -o spec.docx
```

---

## 🔍 WHAT THIS CATCHES THAT OTHERS MISS

| Typical Analysis           | This Prompt                                                                                                         |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| "They have visit tracking" | Exact geofence radius, manual confirm flow, exit behavior, offline queue, sync conflict UI, battery optimization    |
| "Role-based access"        | Every role name, every permission visible in UI, field-level hiding (cost price), row-level filters, impersonation  |
| "Stock management"         | Van vs main warehouse views, batch/expiry, transfer flow, cycle count, import template columns, error row reporting |
| "Arabic support"           | `dir=rtl` toggle, logical CSS, icon mirroring, Arabic-Indic digits, Hijri calendar, font stack, number formatting   |
| "ZATCA compliant"          | TLV fields in QR, Phase 1 vs 2, crypto stamp key management, test vector match                                      |
| "Offline works"            | Which actions queue, conflict resolution UI, sync trigger, pending queue screen, data freshness indicator           |

---

## 📁 SAVE LOCATION

This prompt is saved at: `docs/COMPETITOR_VIDEO_ANALYSIS_PROMPT.md`

Run it against any competitor demo video. The output spec sheet becomes your **reverse-engineered PRD** — ready for gap analysis, sprint planning, or "build vs buy" decisions.
