# JAWLA Comprehensive Visual UI Test Plan

**Document status:** Proposed plan — no test execution performed  
**Application:** JAWLA (جولة) field-sales CRM/ERP PWA and Filament admin panel  
**Plan version:** 1.0  
**Prepared:** 2026-07-30  
**Primary languages:** Arabic (RTL) and English (LTR)  
**Release target:** External prospective clients and production users

This document defines the required coverage, data, environments, evidence, and
release gates. A checked box means a test is planned, not passed. Results must
be recorded separately during execution; no result may be inferred from the
existence of source code or an automated test.

## 1. Executive Summary and Testing Objectives

### 1.1 Purpose

The objective is to verify that every discoverable JAWLA screen, state, role,
control, workflow, integration, and PWA behavior is visually correct,
functionally usable, secure by role, responsive, bilingual, accessible, and
resilient before external users evaluate it.

The test campaign covers:

- The rep PWA under `/app`, the Filament admin panel under `/admin`, unified
  authentication, system/error pages, PDFs, printing, downloads, and API-token
  administration.
- Every visible and keyboard-reachable button, link, icon action, menu item,
  tab, toggle, checkbox, radio, dropdown, combobox, date/time picker, search
  field, slider if present, file input, pagination control, modal, toast, map
  control, draggable widget, and form input.
- All roles and both positive and negative role-based access paths.
- Arabic/RTL and English/LTR at every supported viewport.
- Online, slow, intermittent, offline, recovery, stale-cache, retry, duplicate,
  conflict, expired-session, and storage-pressure behavior.
- End-to-end sales, stock, visit, cash, purchasing, administration, reporting,
  and notification journeys.
- Configured third-party and browser integrations, including safe failure when
  an integration is disabled or unavailable.
- Deterministic visual regression baselines for every route/role/locale/state
  combination that is materially different.

### 1.2 Quality objectives

- [ ] No user can see, navigate to, or invoke a capability outside their
      assigned permissions.
- [ ] Every critical rep and admin journey completes without broken navigation,
      data loss, duplicate financial/stock mutations, inaccessible controls, or
      unexplained dead ends.
- [ ] Every screen is usable at 320 CSS px width through large desktop screens,
      in portrait and landscape where applicable.
- [ ] Arabic content is fully RTL, English is fully LTR, mixed Arabic/English
      identifiers remain readable, and no text is clipped or visually reordered.
- [ ] Offline-capable actions clearly communicate their status and synchronize
      once, safely, after connectivity returns.
- [ ] Demo users immediately see realistic, non-empty dashboards and registers
      without creating or importing data.
- [ ] Loading, empty, success, validation, permission, conflict, error, and
      recovery states are designed and testable rather than accidental.
- [ ] Visual changes are intentional, reviewed, and traceable to an approved
      baseline.
- [ ] WCAG 2.2 AA, Core Web Vitals, application performance budgets, security,
      privacy, and data-isolation gates are met.

### 1.3 Risk-based priority

| Priority | Area                                                                                                        | Failure impact                                                           |
| -------- | ----------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------ |
| P0       | Authentication, company isolation, permissions, invoice/payment/return/cash/stock mutations, offline replay | Unauthorized access, financial loss, inventory corruption, data exposure |
| P1       | Rep full-day flow, admin master data, dashboards, synchronization, maps/GPS, files, reports/PDF/print       | Core job cannot be completed or demonstrated                             |
| P2       | Search/filter/sort, preferences, notifications, responsive layout, localization, accessibility              | High user friction or exclusion                                          |
| P3       | Cosmetic polish and non-blocking animation differences                                                      | Reduced trust without functional loss                                    |

### 1.4 Coverage and evidence model

Every test case receives an ID in the form
`JWL-{AREA}-{SCREEN}-{ROLE}-{LOCALE}-{VIEWPORT}-{STATE}` and records:

- build commit, deployment URL, database seed version, browser/OS/device, user
  role, locale, viewport, color scheme, network profile, and timestamp;
- steps, expected result, actual result, pass/fail/blocked/not-applicable;
- screenshot before and after each material state change;
- video for drag/drop, offline recovery, maps, camera, printing, and real-time
  behavior;
- console errors, failed requests, server correlation ID, and accessibility
  output where applicable;
- created record IDs and cleanup/rollback result for mutating tests.

Automated visual comparison will use stable seeded data, a fixed clock/time
zone, deterministic ordering, animation suppression, and masks only for
approved dynamic values such as timestamps, map tiles, generated IDs, and
cursor blinks. A mask may not hide a genuine layout defect.

### 1.5 Execution sequence after plan approval

1. Freeze the candidate build and seed manifest.
2. Validate environment, accounts, permissions, integrations, and demo data.
3. Inventory controls from source plus the rendered accessibility tree.
4. Capture baseline screenshots for all screens, roles, locales, states, and
   representative viewports.
5. Execute P0/P1 workflows, then failure/offline/recovery scenarios.
6. Execute the browser/device, accessibility, performance, and exploratory
   passes.
7. Retest fixes, run the complete regression set, archive evidence, and obtain
   sign-off.

No step in this sequence is executed by this planning task.

## 2. Environment and Account Configuration

### 2.1 Test environments

| Environment               | Purpose                                                                                  | Data                                       | External services                           | Mutation policy                   |
| ------------------------- | ---------------------------------------------------------------------------------------- | ------------------------------------------ | ------------------------------------------- | --------------------------------- |
| Local isolated            | Component and focused exploratory work                                                   | Refreshable synthetic fixtures             | Fake/sandbox                                | Destructive tests allowed         |
| Linux CI                  | Pest unit/feature/browser suite, visual snapshots, accessibility automation, asset build | Fresh isolated database per run            | Fake/sandbox                                | Fully disposable                  |
| Staging release candidate | Full role, device, integration, offline, and demo-data acceptance                        | Production-shaped synthetic demo seed      | Sandboxes or controlled test accounts       | Resettable; no real customer data |
| Production demo           | Final non-destructive smoke and prospective-client acceptance                            | Approved synthetic showcase data           | Production connections only when authorized | No destructive or load tests      |
| Isolated performance      | Volume, concurrency, soak, and slow-query tests                                          | Scale seed, never the public demo database | Controlled/fake where cost-bearing          | Disposable                        |

Staging must match production PHP, Node assets, database engine, Redis/cache,
queue worker, object storage, TLS, headers, service-worker scope, environment
flags, locale defaults, and deployment topology. Paid device clouds or external
test services require separate approval; the default is Linux CI plus available
physical devices.

### 2.2 Account and role matrix

Credentials must be generated or stored in the private demo-credentials store,
never in this plan, screenshots, source control, browser logs, or issue text.

| Account profile         | Required roles                        | Surface                                | Permission objective                                                                   |
| ----------------------- | ------------------------------------- | -------------------------------------- | -------------------------------------------------------------------------------------- |
| Unauthenticated visitor | None                                  | `/`, `/login`, `/offline`, error pages | Only public/system pages; protected routes redirect or deny                            |
| Setup administrator     | `super_admin`, `hr_admin`             | Admin                                  | All permissions, user/role/company/bootstrap controls                                  |
| Amr administrator       | `admin`, `hr_admin`                   | Admin                                  | All application permissions; complete sidebar and widget access                        |
| Sales manager           | `sales_manager`                       | Admin                                  | Sales, visits, quotations, customers, maps, alarms, approvals, relevant reports        |
| Accounts                | `accounts`                            | Admin                                  | Invoices, payments, expenses, reconciliation, financial reporting; no unrelated writes |
| Purchasing              | `purchasing`                          | Admin                                  | Requests, supplier comparison, orders, goods in transit                                |
| Warehouse keeper        | `warehouse_keeper`                    | Admin                                  | Stock, batches, import/export, transfers, receiving                                    |
| Executive               | `executive`                           | Admin                                  | Read-oriented dashboard, maps, alarms, targets, executive reports                      |
| HR administrator        | `hr_admin`                            | Admin                                  | Users, roles, route assignment only unless combined with another role                  |
| System viewer           | `system_viewer`                       | Admin                                  | Read-only permitted registers/reports; no create/update/delete                         |
| Primary sales rep       | `rep`, `sales_rep`                    | PWA                                    | Full assigned-day rep journey                                                          |
| Secondary sales rep     | `rep`, `sales_rep`                    | PWA                                    | Cross-rep isolation, alternate route/van, real-time scenarios                          |
| Suspended/disabled user | Representative role, disabled         | Login/PWA/Admin                        | Authentication denied; existing sessions revoked                                       |
| Cross-company user      | Authorized in two synthetic companies | Both where applicable                  | Explicit company switch and strict tenant isolation                                    |

### 2.3 Account configuration checklist

- [ ] Seed at least one active account for every standalone role above.
- [ ] Seed Amr's administrator account with `admin` and verify that the role
      resolves to the full permission set.
- [ ] Seed two reps assigned to different vans, routes, customers, and companies
      for isolation and broadcast testing.
- [ ] Seed one user with multiple companies and one with exactly one company.
- [ ] Seed disabled, locked/rate-limited, expired-password if supported, and
      session-expired account states.
- [ ] Verify admin accounts cannot enter the rep PWA unless intentionally
      assigned a rep role, and reps cannot enter Filament.
- [ ] Verify hidden navigation, direct URL denial, action authorization, and
      server-side rejection independently; hidden UI alone is not evidence.
- [ ] Verify locale and dashboard preferences are isolated per user and survive
      login, logout, refresh, and a second device.
- [ ] Verify session revocation, concurrent sessions, remember-me if present,
      CSRF renewal, idle timeout, absolute timeout, and post-login redirect.

### 2.4 Authentication inventory and planned gap checks

The repository inventory currently identifies unified login, logout, password
change from the rep profile, and admin session management. No password-recovery
route was identified during planning. Execution must therefore:

- [ ] Test valid/invalid login, required fields, password visibility if present,
      keyboard submission, rate limiting, locked/disabled users, and safe error
      wording in both languages.
- [ ] Test logout online, logout with pending offline actions, browser back
      behavior, cache/user-data isolation, and all open sessions.
- [ ] Test password change, incorrect current password, confirmation mismatch,
      password policy, session rotation, and other-device behavior.
- [ ] Confirm whether forgot-password/request-reset/reset-link screens are a
      supported release requirement. If required, their absence is an S1 release
      blocker; once implemented, test expired, invalid, already-used, and
      cross-account reset tokens plus email delivery and localization.
- [ ] Test admin session list, revoke-one, revoke-all-others, current-session
      protection, and stale-tab behavior.

## 3. Detailed Test Categories with Checklists for Each Screen and Component

### 3.1 Mandatory control-level contract

For every screen listed below, generate a rendered control inventory in Arabic
and English. Every control must pass all applicable checks:

- [ ] Correct accessible name, role, value/state, help text, and visible label.
- [ ] Mouse, touch, keyboard, and assistive-technology activation produce the
      same authorized outcome.
- [ ] Normal, hover, focus-visible, active, selected, checked, disabled, loading,
      success, warning, and error appearances are distinguishable.
- [ ] Focus order matches visual/logical order; focus is not lost after modal,
      toast, Livewire update, pagination, validation, drag/drop, or navigation.
- [ ] Links have correct destinations and external-link indication; icon-only
      actions have tooltips/labels; no empty or duplicate links.
- [ ] Inputs support typing, paste, selection, clear, valid/invalid boundaries,
      autocomplete, mobile keyboard type, and localized errors.
- [ ] Dropdowns/comboboxes support opening, search, option navigation, selection,
      clearing, escape, no-results, long labels, and disabled options.
- [ ] Checkboxes, radios, toggles, tabs, accordions, and sliders if rendered
      expose and retain state correctly.
- [ ] Date/time/currency/quantity controls handle min/max, zero, decimals,
      leap dates, time zone, Arabic/Latin numerals, and invalid manual entry.
- [ ] Tables support search, filter, sort, pagination, row actions, bulk
      selection, empty results, horizontal overflow, and retained state.
- [ ] Destructive and financial actions show a bilingual confirmation with the
      exact consequence, prevent double submission, and report the final result.
- [ ] File inputs validate type, MIME, extension, size, count, corruption,
      duplicate, cancel, interrupted upload, progress, retry, preview, remove, and
      authorization.
- [ ] Modals trap focus, label title/body/actions, close via explicit action and
      escape when safe, restore focus, and do not permit background interaction.
- [ ] Toasts and live regions announce results without covering controls or
      becoming the only record of a failure.
- [ ] Drag/drop offers a keyboard alternative, clear drop target, placeholder,
      successful persistence, cancellation, reset, and cross-device behavior.
- [ ] No control shifts unexpectedly, clips, overlaps, appears off-screen, or
      becomes unreachable at any required viewport or zoom level.

The final control register must reconcile the source inventory with the rendered
accessibility tree. A route is not complete while any rendered control lacks a
test-case ID and result.

### 3.2 Global, system, and shared UI

- [ ] Root routing chooses the correct destination for unauthenticated, rep, and
      admin users.
- [ ] Unified login renders logo, language switch, form controls, validation,
      rate-limit state, and appropriate post-login destination.
- [ ] Global locale switch changes copy, document language/direction, icons,
      dates, numbers, currency, layout, and persisted preference.
- [ ] Admin top bar centers the company name at all widths without colliding
      with navigation, account controls, notifications, or mobile actions.
- [ ] Admin sidebar shows only authorized groups/items, correct active state,
      readable labels, collapse/expand behavior, scrolling, tooltips, and mobile
      drawer behavior.
- [ ] Rep header and bottom navigation show correct active state, badges,
      safe-area spacing, back behavior, and no overlap with content or keyboard.
- [ ] Company switcher confirms context, refreshes scoped data, prevents stale
      cross-company content, and handles unsaved forms.
- [ ] Breadcrumbs, page titles, back buttons, skip link, logo/home links, user
      menu, logout, theme if present, and notification badge all work.
- [ ] Onboarding modal/flow handles first login, completion, interruption,
      refresh, locale switch, and subsequent-login suppression.
- [ ] Offline page, health page, 403, 404, 419, 429, 500, and maintenance states
      are branded, bilingual, accessible, actionable, and do not leak internals.
- [ ] Global loading indicator, skeletons/spinners, disabled submission state,
      optimistic updates, toasts, and retry affordances behave consistently.

### 3.3 PWA shell, install, update, and storage

- [ ] Manifest name/short name, start URL, scope, display mode, theme/background
      colors, orientation behavior, categories, shortcuts if present, and every icon
      size/maskable icon validate.
- [ ] Install prompt and manual install guidance work on supported Android,
      desktop Chromium, and iOS Safari fallback instructions.
- [ ] Installed standalone launch, splash screen, status bar, app icon, deep
      links, task switching, external links, and return-to-app work.
- [ ] First-load online, subsequent warm load, hard refresh, new version, service
      worker activation, waiting update, refresh prompt, skip-waiting behavior, and
      rollback to a healthy build are verified.
- [ ] Cache contents and cache names contain no credentials, private API
      responses, cross-user data, or stale data after logout/account switch.
- [ ] Storage estimate/pressure warning, recovery link, quota exceeded,
      IndexedDB unavailable, private browsing, eviction, and cache corruption have
      safe outcomes.

### 3.4 Rep PWA screen-by-screen checklist

Apply the control-level contract and full state matrix in 3.8 to every row.

| Route/screen                                      | Required visual and interaction coverage                                                                                                                                                      |
| ------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `/app` Home                                       | Day/route summary, KPIs, quick actions, current visit, notifications, sync/offline status, location tracking, empty/no-assignment day                                                         |
| `/app/customers` Today's customers                | Customer cards/list, status, search/filter if present, directions/contact actions, visit entry, add customer, pagination/long list                                                            |
| `/app/visits` Visits                              | Assigned/completed/missed/custom visits, date/status filters, navigation, empty day, stale assignment                                                                                         |
| `/app/visit/{visit}` Visit flow                   | GPS permission, in/out-of-range check-in, route/customer details, photo capture, actions during visit, check-out, elapsed/status changes, duplicate/retry                                     |
| `/app/orders` Orders                              | Order/invoice/proforma list, search/status/date filtering, detail/open/PDF actions, empty and large histories                                                                                 |
| `/app/sell` and `/app/sell/{customer}` Sales flow | Customer selection, product search, barcode/SKU entry, cart quantities, stock/price constraints, discount/tax/payment terms, draft/proforma/invoice paths, confirmation, duplicate prevention |
| `/app/quotations` Quotation flow                  | List/detail/proforma/done steps, negotiation/range validation, expiry/status, create/open/share/download, back/refresh state                                                                  |
| `/app/stock` Stock search                         | SKU/barcode/name search, availability by permitted warehouse/van, out-of-stock alarm, debounce, no results, long result set                                                                   |
| `/app/sync-queue` Sync queue                      | Pending/failed/conflict counters and rows, retry one/all, clear resolved items, conflict details/resolution, logout guard, reconnect automation                                               |
| `/app/notifications` Notifications                | Read/unread styling, badge count, mark read/all, deep links, live arrival, duplicates, empty state, pagination                                                                                |
| `/app/more` More                                  | All secondary navigation cards/links, permissions, badges, external-link behavior                                                                                                             |
| `/app/profile` Profile                            | Read/edit profile fields, current/new/confirm password, image/avatar if present, validation, save/cancel, reauthentication                                                                    |
| `/app/settings` Settings                          | Locale and available preferences/toggles, persistence, reset/defaults, effect on shell and content                                                                                            |
| `/app/customers/create` Add customer              | Bilingual names/contact/address/group, GPS capture/map coordinates, required/format validation, duplicate detection, offline submission, approval status                                      |
| `/app/complaints` Log complaint                   | Customer/category/severity/details, attachments/photos if present, submission, alarm/notification outcome, validation/offline retry                                                           |
| `/app/collect-payment` Collect payment            | Customer/invoice selection, balance, amount/method/reference/date, partial/overpayment boundaries, confirmation, receipt/PDF/print, retry/idempotency                                         |
| `/app/returns` Log return                         | Invoice/product/quantity/reason/condition, calculated refund/credit, stock impact explanation, confirmation, photo if present, offline/idempotency                                            |
| `/app/expenses` Log expense                       | Category/amount/date/note/receipt upload, limits, confirmation, offline retry, duplicate prevention                                                                                           |
| `/app/reconcile` Cash reconciliation              | Expected vs counted cash, denominations/variance/note, close/reopen rules, confirmation, print/export if offered                                                                              |
| `/app/transfers` Van transfers                    | Source/destination, item/quantity, request/status history, insufficient stock, duplicate, approval/rejection reflection                                                                       |
| `/app/purchase-offer` Submit purchase offer       | Supplier/items/quantities/prices/terms/files, validation, submit/reset, status feedback                                                                                                       |
| PDF routes                                        | Proforma, invoice, receipt authorization, filename, bilingual content, totals/QR, browser view/download, print layout, missing/expired record                                                 |

Embedded PWA components requiring separate states:

- [ ] Photo capture: camera permission, file fallback, orientation, preview,
      retake/remove, compression, EXIF privacy, upload progress/failure/offline.
- [ ] Location tracker: permission prompt, denied/blocked/unavailable/timeout,
      inaccurate/stale GPS, background/foreground, battery-conscious polling, stop.
- [ ] Action toast: success/warning/error, announcement, stacking, timeout,
      dismiss, action/retry, safe-area placement.
- [ ] Barcode entry/scanning fallback: supported/unsupported browser, manual
      entry, unknown/inactive/cross-company product, repeated scan increments.
- [ ] Bluetooth print: supported/unsupported browser, permission/device picker,
      connect/disconnect/reconnect, wrong device, print failure, saved printer,
      PDF fallback, Arabic output.

### 3.5 Admin dashboard, widgets, and custom pages

#### Dashboard and widgets

- [ ] Dashboard renders Visits Today, Sales Today, Rep Performance, Pending
      Quotations, Outstanding Balance, Open Alarms, and Low Stock Alert only when
      authorized.
- [ ] Each metric reconciles with its underlying filtered register, has correct
      currency/date/number localization, and handles zero, normal, large, negative,
      delayed, and error values.
- [ ] Charts, legends, axes, labels, tooltips, colors, truncation, empty data,
      dense data, keyboard/screen-reader alternative, and responsive resize work.
- [ ] Widget links and row actions navigate with correct filters/context.
- [ ] Customize utility can add/remove visible widgets, reorder by drag/drop and
      keyboard alternative, persist per user, reset defaults, prevent duplicates,
      and recover from an obsolete widget key.
- [ ] Widget grid uses multiple columns at supported desktop widths (including a
      typical 14-inch 1366×768 display), collapses intentionally on smaller widths,
      and preserves logical RTL/LTR order.
- [ ] Concurrent tabs, another device, locale switch, permissions change, and
      role change do not corrupt saved layout.
- [ ] Polling/live updates update values without focus loss, repeated
      announcements, visual jumps, or duplicate network activity.

#### Custom admin pages

| Page                | Required coverage                                                                                                                    |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| Dashboard           | Widget behavior above, top bar/sidebar, refresh/polling, customization                                                               |
| Reports             | Report type/date/company/rep filters, chart/table consistency, empty/large data, export/print                                        |
| Supplier Comparison | Request selection, comparable offers, sorting/totals, missing offer, decision/approval path                                          |
| Collect Payment     | Customer/invoice/amount/method validation, confirmation, receipt, permissions, idempotency                                           |
| Rep Live Map        | Rep pins/status/last-seen, polling, popups, stale/offline rep, map failure, zoom/pan, XSS-safe content                               |
| Customer Map        | Customer pins/clustering if present, filters, popups, missing coordinates, fit bounds, map failure                                   |
| Stock Import        | Template/help, upload, column mapping/preview, row errors, approval threshold, progress, partial/atomic failure, retry, audit record |
| Session Management  | Device/session list, current marker, revoke one/all others, confirmation, stale sessions, current session protection                 |
| API Tokens          | Token name/abilities, one-time reveal/copy, revoke confirmation, empty/list states, no token leakage                                 |
| Admin Preferences   | Widget visibility/order, drag/keyboard reorder, save/reset, persistence, permissions                                                 |
| Activity Log        | Search/filter/date/actor/event/subject, detail diff, pagination, redaction, immutable/read-only behavior                             |

### 3.6 Filament resource coverage

The following 24 resources are in scope:

`Company`, `User`, `Customer`, `Task`, `Alarm`, `Batch`, `Invoice`,
`Proforma Invoice`, `Return Record`, `Payment`, `Expense`,
`Cash Reconciliation`, `Product`, `Product Price`, `Stock`,
`Goods in Transit`, `Purchase Request`, `Purchase Order`, `Van Transfer`,
`Route`, `Sales Target`, `Daily Visit Assignment`,
`Price Quotation Request`, and `Complaint`.

For every resource and every role that can see it:

- [ ] List page: title, navigation/breadcrumb, columns, formatting, badges,
      relationships, search, every filter, sort, pagination/page size, row select,
      bulk actions, column toggle, persistence, empty/no-result/loading/error.
- [ ] Create page/action when authorized: every field/control, defaults,
      dependencies, required/unique/range/file validation, save/create-another,
      cancel/unsaved warning, duplicate submission, success destination.
- [ ] View page/modal when present: complete data, relationships, money/tax/date
      formatting, long/missing values, files, audit metadata, safe HTML.
- [ ] Edit page/action when authorized: initial values, dirty state, concurrent
      update conflict, validation, cancel, save, stale record, immutable fields.
- [ ] Delete/archive/restore when present: exact bilingual consequence,
      dependencies, financial/destructive restrictions, authorization, audit event,
      safe failure, list refresh.
- [ ] Domain actions: all header, row, table, bulk, approval, reject, cancel,
      receive, assign, import/export, PDF, share, refund, and status-transition
      actions exposed by the resource.
- [ ] Direct URL and crafted request denial for every unauthorized operation.
- [ ] Company and record scoping prevents cross-tenant search suggestions,
      counts, exports, files, relationship options, and direct IDs.
- [ ] Responsive table/card layouts, sticky actions, modals, and form grids in
      Arabic and English.

Resource-specific P0 assertions:

- [ ] Company/User: role assignment, company membership, sensitive-field
      masking, disabled users, last-admin protection.
- [ ] Customer/Route/Visit Assignment: approval, duplicate/customer ownership,
      GPS/map picker, assignment conflicts and date boundaries.
- [ ] Product/Product Price/Stock/Batch: SKU/barcode uniqueness, cost
      visibility, effective dates, expiry, units/decimals, no direct stock mutation.
- [ ] Invoice/Proforma/Payment/Return/Reconciliation/Expense: totals, tax,
      discounts, status transitions, immutable posting, balances, confirmation,
      ledger/stock effects, PDF/QR/print, cancellation/refund controls.
- [ ] Purchase Request/Order/Goods in Transit/Supplier Comparison: approval
      chain, quantities/costs, landed cost, partial receive, documents, status.
- [ ] Van Transfer: source/destination, stock availability, request/approve/
      dispatch/receive/reject transitions and matching stock movements.
- [ ] Alarm/Complaint/Task/Target: severity/status/assignee/deadline, broadcast,
      read/resolve, progress and overdue styling.

### 3.7 Search, filtering, files, print, and export

- [ ] Test exact, partial, case-insensitive, Arabic diacritic, Arabic/Latin
      numeral, SKU/barcode, whitespace, punctuation, long query, no-result, and
      unsafe-string search input.
- [ ] Test every filter alone and in combination; applied-filter chips, reset,
      URL/state persistence, back/forward, locale switch, export scope, and
      unauthorized filter options.
- [ ] Test sort ascending/descending, nulls, localized strings, numeric money,
      dates, stable tie order, and pagination retention.
- [ ] Validate each upload/download with allowed and rejected type, MIME
      spoofing, large/zero/corrupt file, filename Unicode/RTL/path characters,
      interruption, authorization, content disposition, and malware-processing
      policy where applicable.
- [ ] Verify exports contain only authorized rows/columns, respect filters and
      locale, escape spreadsheet formulas, use correct encoding, and remain usable
      at large volume.
- [ ] Verify PDF and print preview at A4 and intended thermal width: page breaks,
      repeated headers, no clipped RTL text, totals/signatures/QR, grayscale,
      browser print cancel, Bluetooth failure fallback, and filename.

### 3.8 State and edge-case matrix

Every materially different screen must be exercised in:

- [ ] loading/skeleton, first load, warm load, and refresh;
- [ ] empty, one record, typical data, maximum-length data, large paginated data;
- [ ] success, validation error, permission denied, not found, rate limited,
      server error, maintenance, and recoverable integration error;
- [ ] slow response, request timeout, dropped request, duplicate tap, double
      browser submission, stale response, and retry;
- [ ] online, offline before navigation, offline after form completion, network
      loss during submit/upload/download, intermittent network, and recovery;
- [ ] pending offline mutation, successful sync, failed sync, server conflict,
      duplicate/idempotent replay, logout with pending work, and storage pressure;
- [ ] expired/revoked session during view, edit, upload, payment, and sync;
- [ ] locale switch mid-flow, RTL/LTR, long translations, mixed-direction text,
      missing translation, and Arabic/Latin numbers;
- [ ] permissions removed mid-session, record deleted/updated by another user,
      company switched in another tab, and concurrent tabs/devices;
- [ ] system clock/time-zone boundary, end of day/month/year, leap day, daylight
      changes where relevant, and future/past dates;
- [ ] reduced motion, forced colors/high contrast where supported, 200% zoom,
      text spacing overrides, screen reader, and keyboard only.

## 4. Workflow and Integration Testing Matrix

### 4.1 End-to-end workflow matrix

| ID    | Journey                    | Roles                             | Major screens/systems                                                                  | Required variants                                          |
| ----- | -------------------------- | --------------------------------- | -------------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| WF-01 | First login and onboarding | Every role                        | Login, locale, onboarding, destination                                                 | AR/EN, invalid login, disabled user, mobile/desktop        |
| WF-02 | Rep full day               | Rep, manager                      | Home, route/customers, visit, GPS/photo, sale, payment, return/expense, reconciliation | Online, offline/reconnect, missed visit, two reps          |
| WF-03 | Visit execution            | Rep, manager                      | Assignments, visit flow, maps/live map, alarms                                         | In/out of geofence, denied GPS, stale location, check-out  |
| WF-04 | Cash sale                  | Rep, accounts                     | Sales flow, stock, invoice, payment, receipt, dashboard                                | Exact/partial payment, retry, print/PDF, stock failure     |
| WF-05 | Credit sale and collection | Rep, accounts, manager            | Invoice, outstanding balance, collect payment, receipt                                 | Partial/multiple/overpayment rejection, cancellation       |
| WF-06 | Quotation to invoice       | Rep, manager, accounts            | Quotation flow/request, proforma, approval, invoice, WhatsApp/PDF                      | Approved/rejected/expired, price override boundary         |
| WF-07 | Return/refund              | Rep, manager, accounts, warehouse | Return, approval, invoice balance, stock/ledger                                        | Partial/full, damaged/resellable, duplicate/offline        |
| WF-08 | Van replenishment          | Rep, warehouse, manager           | Transfer request, approval, dispatch, receive, stock                                   | Insufficient stock, partial/reject, concurrent request     |
| WF-09 | Purchasing lifecycle       | Rep/purchasing/warehouse/accounts | Purchase offer/request, comparison, order, goods in transit, receive                   | Approval/veto, landed cost, partial receipt, documents     |
| WF-10 | Customer lifecycle         | Rep, manager                      | Add customer, approval, assignment, map, visit, sale                                   | Duplicate, missing GPS, offline create, reject/approve     |
| WF-11 | Complaint/alarm/task       | Rep, manager                      | Complaint, alarm broadcast, notification, task/resolve                                 | Severity, real-time, cross-company isolation               |
| WF-12 | Stock administration       | Warehouse/admin                   | Product, batch, stock import/export, movement history                                  | Valid/invalid import, approval threshold, expiry, rollback |
| WF-13 | Admin master data          | Admin/HR                          | Company, users, roles, routes, products, pricing                                       | Create/edit/archive, validation, direct URL denial         |
| WF-14 | Dashboard/report decision  | Manager/executive/accounts        | Widgets, charts, filtered registers, reports/export                                    | Zero/typical/large data, live refresh, AR/EN               |
| WF-15 | Notification lifecycle     | Rep/manager                       | Trigger, broadcast/in-app notification, badge, deep link                               | Read/unread, duplicates, offline arrival, revoked user     |
| WF-16 | Session/security lifecycle | Every role/admin                  | Login, session management, password change, logout                                     | Expiry, revoke, back button, pending offline work          |
| WF-17 | Multi-company switch       | Multi-company user                | Switcher, dashboards, resources, files, sync queue                                     | Unsaved form, stale tab/cache, no data leakage             |
| WF-18 | API token lifecycle        | Admin/authorized user             | Create/copy/use/revoke token, API v1                                                   | Ability scope, invalid/expired/revoked, company scope      |
| WF-19 | Demo client walkthrough    | Admin, manager, rep, executive    | Dashboard → maps → customer → invoice → payment → report                               | No setup/import required, coherent cross-module story      |

Each workflow must verify UI, persisted database outcome, audit event, relevant
notification, financial/stock invariants, permissions, tenant isolation,
idempotency, and rollback/recovery.

### 4.2 Offline behavior matrix

| Resource/action                           | Offline expectation                                                                    | Cache/queue strategy to verify                   | Recovery acceptance                  |
| ----------------------------------------- | -------------------------------------------------------------------------------------- | ------------------------------------------------ | ------------------------------------ |
| PWA shell/static assets                   | Opens after one successful online load                                                 | Cache-first/versioned precache                   | Correct build, no broken assets      |
| Previously viewed read screens            | Show last safe data with stale/offline label                                           | Bounded cached data                              | Refreshes after reconnect            |
| New uncached route                        | Branded offline fallback                                                               | Navigation fallback                              | Retry opens real page online         |
| Visit check-in/out                        | Queue only if product policy permits; show pending                                     | Idempotent mutation queue                        | Exactly one server mutation          |
| Customer/complaint/expense/return capture | Preserve validated draft/pending action where supported                                | Per-user encrypted/isolated local store          | Sync success or actionable conflict  |
| Sale/payment/reconciliation               | Follow explicit high-risk offline policy; never imply posting before server acceptance | Idempotency key and guarded queue or clear block | No duplicate money/stock effect      |
| Photo/file upload                         | Retain bounded pending reference or clearly require retry                              | Quota-aware deferred upload                      | Progress, retry, no orphan/duplicate |
| Search/live maps/reports                  | Explain unavailable/live-data limitation                                               | Network-first/no unsafe stale claim              | Automatic/manual refresh             |
| Logout with queued work                   | Warn and block/discard only by explicit safe choice                                    | Identity-scoped queue/cache                      | No next-user data exposure           |
| App update with queued work               | Do not strand or corrupt queued schema                                                 | Compatible queue migration/version gate          | Queue remains recoverable            |

### 4.3 Integration matrix

| Integration                            | UI entry points                                  | Tests and failure behavior                                                                                      |
| -------------------------------------- | ------------------------------------------------ | --------------------------------------------------------------------------------------------------------------- |
| PostgreSQL                             | All data screens                                 | Transaction outcome, constraints, concurrency, pagination, no cross-company rows                                |
| Redis cache/session/queue              | Login, polling, notifications, sync              | Outage/degraded mode, session consistency, queued job retry, no stale permission data                           |
| Object storage (S3/Railway-compatible) | Photos, receipts, attachments, downloads         | Upload/download/authorization, timeout, unavailable bucket, metadata removal, signed access                     |
| Browser geolocation/GPS                | Visit, add customer, tracking, maps              | Allow/deny/block/timeout/inaccurate/stale, mobile background/foreground, privacy                                |
| Camera/file picker                     | Visit, complaint, expense, return                | Permission, capture/fallback, orientation, size, offline, privacy metadata                                      |
| Leaflet/OpenStreetMap                  | Customer map, rep map, map picker                | Tile/network failure, attribution, CSP, pan/zoom, empty/missing GPS, safe popups                                |
| Sentry                                 | Global client/server failures                    | Scrubbed event delivery, no secrets/PII, offline queue behavior, disabled configuration                         |
| Mail provider                          | Password recovery and other configured mail      | Sandbox delivery, localized subject/body/link, bounce/timeout, no account enumeration                           |
| In-app notification/broadcast          | Bell, badges, alarms, workflow events            | Delivery, read state, reconnect, duplicate/order, tenant/role targeting                                         |
| Browser push                           | Notification permission/subscription if released | Current planning inventory indicates push is deferred; absence is a documented scope decision or S1 if promised |
| WhatsApp deep links                    | Invoice/proforma/quotation actions               | Correct number/message/document URL, encoding, missing number, app absent, external warning                     |
| ETA e-invoicing                        | Egyptian invoice submission/status               | Disabled state, preproduction OAuth, accepted/rejected/timeout/retry, signing prerequisite, status/audit        |
| ZATCA/QR                               | Saudi invoice/proforma/PDF                       | Enabled/disabled company, valid QR, configuration absence, Arabic print, no secret exposure                     |
| Web Bluetooth printer                  | Print buttons                                    | Support detection, device selection, connect/retry, Arabic output, PDF fallback                                 |
| Barcode input/scanner path             | Sales flow/product lookup                        | Valid/unknown/inactive/duplicate/cross-company code, manual fallback                                            |
| PDF/QR renderer                        | Invoice/proforma/receipt                         | Authorization, totals, fonts/RTL, QR scan, browser download/print, errors                                       |
| Sanctum public API                     | API Tokens and `/api/v1`                         | Token abilities, revoke, throttling, pagination, tenant scope, UI one-time reveal                               |

Real external calls must use official sandboxes or dedicated test accounts. No
test may contact a production tax authority, send a real client message, incur a
paid charge, or expose credentials without separate written authorization.

## 5. Demo Data Deployment Verification Checklist

### 5.1 Demo data principles

The public demo must use synthetic data only, contain no real customer or
employee information, and tell a coherent bilingual business story. The
existing seed includes the bilingual GPC company, administrator/manager/
accounts/purchasing/warehouse/executive/rep profiles, Cairo/Giza/Alexandria
customers, routes/vans, products/stock/batches, invoices/payments/returns,
visits, expenses, alarms, quotations, purchase requests/orders, and multiple
statuses. Execution must verify actual deployed counts and relationships rather
than assume local seeder code ran in production.

### 5.2 Required showcase profile

- [ ] One primary Egyptian bilingual company and one secondary synthetic
      company for tenant-switch/isolation demonstrations.
- [ ] At least one user for every role in section 2, with two active reps,
      one disabled user, and one multi-company user.
- [ ] Customers across Cairo, Giza, Alexandria, urban/rural-style address
      lengths, customer groups, approved/pending/rejected status, valid/missing GPS,
      credit limits, balances, contacts, Arabic and English names.
- [ ] Products across all categories with Arabic/English names, SKU, barcode,
      units, tax treatments, prices, cost visibility rules, active/inactive state,
      normal/low/zero stock, expiring/expired batches, and two van inventories.
- [ ] At least 90 days of coherent activity, including today, this week, this
      month, prior periods, and future assignments/expirations.
- [ ] Invoices/proformas/payments/returns/expenses/reconciliations spanning
      draft, pending, approved, paid, partially paid, overdue, cancelled, rejected,
      and completed states as valid for each module.
- [ ] Purchasing data spanning request, offers, comparison, approval, order,
      transit, partial receipt, received, and rejected/cancelled paths.
- [ ] Visits spanning assigned, checked-in, completed, missed, out-of-range, and
      rescheduled states, with safe synthetic locations.
- [ ] Alarms, complaints, tasks, targets, notifications, activity logs, files,
      maps, and dashboard values are non-empty and cross-linked.
- [ ] PDFs/QR codes, sample receipt attachment/photo, exportable registers, and
      printable records are available without exposing secrets.
- [ ] No prospective client must create a customer, import stock, or wait for a
      scheduled job before the principal walkthrough works.

### 5.3 Volume and scalability demonstration

Two distinct seeds are required:

- **Showcase seed:** fast to reset and visually rich enough that every dashboard,
  filter, chart, pagination control, and status appears naturally.
- **Scale seed:** isolated from the public demo and large enough to validate
  pagination, search, exports, reports, maps, queues, and database performance
  at the agreed production envelope.

Acceptance counts must be defined in a machine-readable seed manifest and
approved before execution. As a starting target, the showcase should contain at
least 50 customers, 50 products, 3 routes, 2 vans, 100 visits, 100 invoices, 50
payments, and representative records in every remaining module. Scale data
should test at least 10,000 customers, 5,000 products, and 100,000 transactional
records unless the agreed business capacity is higher. These are plan targets,
not claims about the currently deployed database.

### 5.4 Deployment verification

- [ ] Production/demo seeding is guarded, explicitly authorized, idempotent,
      logged, and cannot overwrite real data.
- [ ] Seed version/hash and execution timestamp match the release candidate.
- [ ] Credentials file exists only in private storage with restricted access;
      passwords are random and not logged or committed.
- [ ] Referential-integrity and company-scope checks pass with zero orphan or
      cross-company records.
- [ ] Money totals reconcile: invoice totals, payments, balances, refunds,
      expenses, and cash reconciliation.
- [ ] Stock totals reconcile to stock movements, invoices, returns, receipts,
      and transfers; negative stock appears only where policy explicitly permits.
- [ ] Every role lands on a meaningful non-empty screen and sees only permitted
      navigation/actions/data.
- [ ] Every dashboard widget and report reconciles to the seeded register data.
- [ ] Searches, combined filters, sorting, pagination, maps, exports, downloads,
      and charts all have meaningful results.
- [ ] Arabic/English names, addresses, currencies, tax rates, time zone, dates,
      phone formats, and regional terms are appropriate to the synthetic locale.
- [ ] Uploaded demo files are harmless synthetic assets, scan clean, have no
      embedded real metadata, and download with correct authorization.
- [ ] Rerunning the seed produces no unintended duplicates and retains expected
      stable identifiers/baselines.
- [ ] A reset/restore runbook can return the demo to the canonical state, and a
      reset is tested before public access.
- [ ] Public demo banners/terms explain that data is synthetic and actions may
      be periodically reset.

## 6. Cross-Browser and Cross-Device Testing Requirements

### 6.1 Supported browser matrix

Test the latest stable and previous major version at campaign start:

| Platform                                        | Browsers/modes                              | Coverage                                                                   |
| ----------------------------------------------- | ------------------------------------------- | -------------------------------------------------------------------------- |
| Windows 11                                      | Chrome, Edge, Firefox                       | Full admin; critical PWA; keyboard; printing                               |
| macOS current                                   | Safari, Chrome, Firefox                     | Full critical journeys; admin visual; printing                             |
| Android current and one older supported version | Chrome browser and installed standalone PWA | Full rep journey, camera, GPS, install, offline, barcode fallback          |
| iOS/iPadOS current and previous major           | Safari browser, Home Screen standalone PWA  | Full rep journey, install fallback, safe areas, camera/GPS, offline limits |
| Linux CI                                        | Chromium, Firefox, WebKit                   | Automated functional, visual, accessibility regression                     |

The repository's Pest browser/Playwright lifecycle issue on Windows means the
authoritative automated browser run must execute in Linux CI. Windows still
requires manual/browser-native verification. Laravel Dusk or a manually managed
Playwright server may be used locally only if separately configured.

### 6.2 Viewports and physical devices

- Mobile portrait: 320×568, 360×800, 390×844, 412×915.
- Mobile landscape: representative 568×320 and 844×390.
- Tablet: 768×1024 and 820×1180 in both orientations.
- Desktop/laptop: 1280×720, 1366×768 (typical 14-inch), 1440×900,
  1920×1080.
- At least one physical low/mid-range Android phone, one recent iPhone, one
  iPad/tablet, one 14-inch laptop, and one large desktop display.
- Test DPR 1 and high-DPR, browser UI visible/hidden, display scaling 100/125/
  150%, notches/safe areas, virtual keyboard, and external keyboard.

### 6.3 Responsive acceptance checklist

- [ ] No horizontal page scroll except an intentionally contained data table or
      chart with an accessible alternative.
- [ ] Content order, sidebar/bottom navigation, forms, tables/cards, widgets,
      charts, maps, modals, and sticky actions adapt at each breakpoint.
- [ ] The 1366×768 admin dashboard displays a purposeful multi-column widget
      grid when space permits rather than an unintended single vertical stack.
- [ ] Touch targets are at least 44×44 CSS px or have equivalent spacing; hover
      is never required.
- [ ] On-screen keyboard does not hide focused fields, validation, primary
      actions, or modal controls.
- [ ] Orientation change preserves data, scroll/focus, modal state, maps/charts,
      and the active workflow step.
- [ ] Long Arabic/English labels, 200% zoom, text spacing overrides, and dynamic
      font sizing do not clip or overlap.

## 7. Performance and Accessibility Considerations

### 7.1 Performance budgets

Measure cold and warm load on representative production-like data, with
instrumentation disabled only if the production build also disables it.

| Metric                            | Release budget                                                        |
| --------------------------------- | --------------------------------------------------------------------- |
| Largest Contentful Paint          | p75 ≤ 2.5 s on representative mobile                                  |
| Interaction to Next Paint         | p75 ≤ 200 ms                                                          |
| Cumulative Layout Shift           | p75 ≤ 0.10                                                            |
| Time to First Byte                | p75 ≤ 800 ms for primary HTML                                         |
| Critical authenticated navigation | p95 ≤ 2.0 s server response, excluding approved external latency      |
| Search/filter feedback            | p95 ≤ 500 ms after debounce for showcase data                         |
| Dashboard usable                  | p95 ≤ 3.0 s, no progressive layout collapse                           |
| Offline shell launch              | ≤ 1.0 s after successful install/cache                                |
| Memory/CPU                        | No unbounded growth during 30-minute rep session or dashboard polling |

- [ ] Test cold/warm cache, Slow 4G, high latency, packet loss, 2×/4× CPU
      slowdown, low-end device, and large seed.
- [ ] Record route/API timing, query count, payload size, image size, JS/CSS
      transfer, long tasks, layout shifts, memory, queue latency, and map cost.
- [ ] Verify bounded pagination, no N+1 on list/dashboard/report routes, no
      duplicate polling/listeners, and cancellation of stale searches.
- [ ] Load, stress, concurrency, and soak tests run only in the isolated
      performance environment, never against the public production demo.

### 7.2 Accessibility requirements

Target WCAG 2.2 Level AA for both application surfaces and both languages.

- [ ] Automated semantic/contrast checks run on every unique page/state in
      Chromium, with critical violations confirmed manually.
- [ ] Every critical flow is completed by keyboard alone with visible focus,
      logical order, no trap, skip navigation, and correct modal/menu behavior.
- [ ] Screen-reader passes: NVDA + Chrome/Firefox on Windows, VoiceOver + Safari
      on iOS/macOS, and TalkBack + Chrome on Android for critical journeys.
- [ ] Headings, landmarks, lists, tables, forms, errors, status messages,
      dialogs, tabs, menus, charts, maps, drag/drop, and icon controls expose correct
      names/roles/states/relationships.
- [ ] Form errors are specific, associated, announced, preserved, and do not
      rely on color; financial confirmations are understandable in Arabic/English.
- [ ] Text/non-text contrast, focus contrast, color independence, high contrast/
      forced colors, reduced motion, 200% zoom, 320 CSS px reflow, text spacing, and
      target size pass.
- [ ] Charts provide text/table equivalents; maps provide list/search
      alternatives; drag/drop widget ordering has keyboard controls.
- [ ] RTL reading/navigation order and mixed-direction phone, SKU, invoice,
      email, currency, and date strings are understandable to screen readers.
- [ ] Live notifications, sync status, validation, loading, and refreshed
      dashboard values use restrained live-region announcements without repetition.
- [ ] Camera, GPS, Bluetooth, offline, and integration-denied states provide an
      accessible fallback and recovery instruction.

## 8. Defect Reporting and Severity Classification

### 8.1 Required defect record

Every defect must include:

- unique ID, concise bilingual-impact title if language-specific, build commit,
  environment, seed version, account role/company, locale, browser/OS/device,
  viewport/orientation, network state, and prerequisites;
- exact reproducible steps, expected/actual result, frequency, affected data
  record IDs, and whether retry/reload changes the result;
- screenshot/video, DOM/accessibility evidence, console/network/server
  correlation evidence with secrets and personal data removed;
- severity, priority, affected requirements/test cases, regression status,
  suspected scope, workaround, data/security/financial impact, and owner;
- fix commit/deployment, retest evidence, adjacent regression evidence, and
  closure approval.

### 8.2 Severity definitions

| Severity    | Definition and examples                                                                                                                    | Release treatment                                     |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------------- |
| S0 Critical | Security breach, cross-company exposure, credential/secret leak, unrecoverable data loss, duplicate/corrupt financial or stock posting     | Immediate stop; release blocked; incident process     |
| S1 Blocker  | Login/role unavailable, critical journey cannot complete, offline sync loses work, major browser/device unusable, required recovery absent | Release blocked                                       |
| S2 Major    | Important feature or role impaired, serious visual/accessibility issue, incorrect report/widget, unreliable integration with workaround    | Block unless explicitly risk-accepted with owner/date |
| S3 Minor    | Localized UI/validation/responsive/accessibility defect with reasonable workaround and no material data impact                             | May ship only within agreed threshold                 |
| S4 Cosmetic | Minor spacing, wording, or non-functional visual difference                                                                                | Backlog or approved baseline update                   |

Priority is separate:

- **P0:** fix immediately/current release;
- **P1:** fix before release candidate sign-off;
- **P2:** scheduled near-term;
- **P3:** backlog.

Any ambiguity involving money, stock, permissions, sensitive data, or tenant
isolation is classified at the higher severity until disproved.

### 8.3 Visual-difference triage

- [ ] Confirm same build, seed, role, locale, viewport, fonts, browser, clock,
      animation setting, and mask configuration.
- [ ] Classify as intended change, environment noise, flaky rendering, content
      drift, or defect.
- [ ] Intended changes require product/design approval and a new reviewed
      baseline; test automation may not silently overwrite baselines.
- [ ] A tolerance threshold cannot excuse clipping, overlap, missing controls,
      order changes, unreadable text, or accessibility failures.

## 9. Sign-Off Criteria

### 9.1 Traceability matrix

| Requirement area                 | Planned evidence                                                | Release gate                              |
| -------------------------------- | --------------------------------------------------------------- | ----------------------------------------- |
| Every page/view                  | Route/resource inventory + screenshot/state manifest            | 100% inventory coverage                   |
| Every interactive element        | Source/accessibility-tree control register + interaction result | 100% enabled controls tested              |
| Roles/RBAC/tenancy               | Role-route-action matrix, direct URL/request negatives          | 100% role cells; zero leakage             |
| E2E journeys                     | Workflow videos, persisted/audit/invariant checks               | All P0/P1 variants pass                   |
| Integrations                     | Sandbox/failure/recovery evidence per 4.3                       | All enabled critical integrations pass    |
| Responsive/browser/device        | Screenshot matrix and physical-device notes                     | Required matrix complete                  |
| Offline/PWA                      | Offline matrix, install/update/storage/sync evidence            | No data loss/duplicate/stale-user data    |
| Authentication/session           | Login/logout/change/recovery/session results                    | All supported flows pass; gaps resolved   |
| Notifications/realtime           | Trigger/delivery/read/deep-link/reconnect evidence              | Correct targeting; no duplicate/leak      |
| Charts/dashboard                 | Visual, accessible alternative, data reconciliation             | All values reconcile                      |
| Search/filter/files/export/print | Boundary, security, visual, content evidence                    | All enabled capabilities pass             |
| Localization/accessibility       | AR/EN baselines, WCAG automation/manual reports                 | Zero critical/serious barriers            |
| Performance                      | Web Vitals, route/query/load reports                            | Budgets met or approved lower target      |
| Demo readiness                   | Deployed seed manifest, counts, walkthrough                     | No setup needed; all modules demonstrable |

### 9.2 Mandatory release gates

Sign-off requires all of the following:

- [ ] Candidate commit, configuration, database migration set, asset build, seed
      manifest, and deployment are immutable and identified.
- [ ] 100% of discovered routes/resources/pages/widgets and rendered interactive
      controls are mapped to executed results or an approved not-applicable reason.
- [ ] All P0 and P1 tests pass in Arabic and English on their required roles,
      environments, browsers, devices, and network variants.
- [ ] Zero open S0 or S1 defects; zero unapproved S2 defects; S3/S4 backlog is
      reviewed and within the agreed threshold.
- [ ] All money/stock workflows reconcile, are idempotent, and retain matching
      audit/stock-movement/ledger evidence.
- [ ] Role, direct URL/action authorization, tenant isolation, cache isolation,
      file authorization, and API-token scope tests pass.
- [ ] Offline/install/update/storage/reconnect/conflict/logout tests pass with
      no loss, duplicate posting, cache poisoning, or next-user data exposure.
- [ ] Required integrations pass sandbox and failure-mode tests; disabled or
      deferred integrations are accurately disclosed and have safe UI behavior.
- [ ] Visual regression diffs are reviewed; no unauthorized baseline updates.
- [ ] Required browser/device matrix, physical-device pass, responsive layouts,
      14-inch dashboard grid, Arabic RTL, and English LTR pass.
- [ ] WCAG 2.2 AA automation and manual critical-flow checks have no open
      critical/serious barriers.
- [ ] Performance budgets, representative load, queue/polling behavior, and
      scale-data pagination/search/export checks pass.
- [ ] The production demo seed is synthetic, complete, localized, reconciled,
      resettable, and gives every role an immediate meaningful walkthrough.
- [ ] Backup/restore and deployment rollback are verified; post-deploy
      non-destructive smoke checks pass.
- [ ] `make verify`, dependency security audits, Linux CI browser tests, and the
      approved release pipeline pass on the candidate build.
- [ ] QA, product/design, security/privacy, accessibility, operations, finance/
      stock domain owner, and release owner sign the evidence package.

### 9.3 Exit statement format

The final execution report must state:

1. exact coverage achieved versus this plan;
2. commands, environments, devices, accounts, and integrations actually tested;
3. pass/fail/blocked/not-applicable counts;
4. open defects and accepted risks;
5. demo-data verification outcome;
6. rollback readiness;
7. a clear release decision: `GO`, `CONDITIONAL GO`, or `NO-GO`.

A numerical readiness score may summarize evidence, but it cannot override a
failed mandatory gate or be reported as 100% unless every required cell has
executed, reviewed evidence.
