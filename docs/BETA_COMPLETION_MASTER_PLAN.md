# Jawla Beta v1.1 — Completion Master Plan

**Status:** Execution-ready  
**Purpose:** Finish and certify the Jawla beta defined by B0–B8.  
**Primary executor:** GLM-5.2 at High/Max effort.  
**Release represented by this plan:** Client beta/UAT release, not unrestricted production invoicing.  
**Sources of truth:**

1. `Jawla_Beta_PRD_v1.1.md`
2. `Jawla_Build_Guide_v1.1_Amendment.md`
3. The still-binding non-phase sections of the original build guide
4. `AGENTS.md`
5. `docs/BUSINESS_RULES.md`, `docs/SECURITY.md`, `docs/ROLES_MATRIX.md`, `docs/DESIGN_SYSTEM.md`, `docs/TESTING.md`, `docs/DEPLOYMENT.md`, and `docs/BACKUP_RESTORE.md`

If these sources conflict, the PRD owns what must exist, the amendment owns sequencing, and the repository's source-of-truth rules decide all remaining conflicts. Never let an AI model silently choose a business rule.

---

## 1. Release outcome

The beta is complete only when a user can perform this exact seeded phone walkthrough without database editing, command-line repair, or skipped steps:

1. A manager assigns five visits to a representative.
2. The representative starts the workday and sees the assignments.
3. The representative arrives at a customer using GPS.
4. One visit uses the out-of-range confirmation path and alerts the manager.
5. The representative signs and submits a visit report.
6. A partially written report survives closing and reopening the app.
7. The representative creates a customer that remains pending.
8. A manager approves that customer with approver and timestamp recorded.
9. The representative requests a product price.
10. The manager sets a base price and permitted range.
11. A proforma at 950 is accepted.
12. A proforma at 850 is rejected on the server.
13. The accepted proforma contains the correct bank details and can be shared through WhatsApp.
14. An invoice is issued with a sequential number, bilingual PDF, QR code, and signature.
15. Invoice creation, stock deduction, stock movement, customer balance, and payment/cash records remain transactionally consistent.
16. Overselling is rejected with no partial records.
17. The representative can search products and see current van stock plus read-only transit quantity.
18. The representative flags Material 952 as unavailable.
19. Finance, Manager, and Executive receive the critical alarm.
20. A complaint is raised, acknowledged, and resolved.
21. Dashboard widgets reflect the completed day.
22. The entire flow works in Arabic and English, including RTL/LTR, on a phone-sized viewport.

Passing isolated tests without passing this walkthrough is not Beta Done.

---

## 2. Model decision

### 2.1 Best single model

Use **GLM-5.2 High/Max** as the sole primary executor.

Reasons:

- It is the strongest all-round long-horizon coding model in the supplied pool.
- Its 1M context can hold the PRD, amendment, plan, repository conventions, and relevant code together.
- It is better suited than a cheaper model to the tightly coupled Laravel, Livewire, Filament, stock, money, tenancy, and authorization work.
- Keeping one primary model avoids architecture drift and contradictory fixes across the 222-file uncommitted worktree.

Do not hand the whole plan to several models simultaneously. They will overlap files, interpret rules differently, and make final verification harder.

### 2.2 Safe multi-model arrangement

If several model sessions are available, use them in these roles:

| Role | Model | Allowed work |
|---|---|---|
| Lead implementer and integrator | **GLM-5.2 High/Max** | Every critical-path ticket, all final decisions, integration, financial/stock/security code, phase gates |
| Independent rules reviewer | **Qwen3.7 Max** | Read-only review of requirements, tenancy, authorization, pricing boundaries, rollback behavior, and test gaps |
| Isolated code specialist | **Kimi K2.7 Code** | Bounded Blade/Livewire/JavaScript fixes or a tightly scoped debugging ticket after GLM defines the interface |
| Visual/browser QA | **MiniMax M3** | Screenshot-based RTL/LTR, mobile, dark-mode, empty/loading/error-state, and visual consistency review |
| Mechanical implementation | **DeepSeek V4 Pro** | Clearly specified, isolated Filament CRUD, translations, factories, or test fixtures; never critical money/stock orchestration without GLM review |
| Fast clerical work | DeepSeek V4 Flash / Plus-tier models | Documentation formatting, translation-key inventory, seed-data text, and other reversible mechanical tasks only |

Do not use GLM-5.1, Kimi K2.6, MiMo-V2.5, MiniMax M2.7, Qwen3.6 Plus, or Flash models for pricing, invoice transactions, stock mutation, tenant isolation, authorization, or release certification.

### 2.3 Handoff protocol

Every implementation session receives only one ticket and must:

1. Read `AGENTS.md`, this entire plan, both v1.1 source documents, and the files named by the ticket.
2. Inspect the current implementation before changing anything.
3. Preserve unrelated user changes.
4. State the ticket ID and acceptance criteria before editing.
5. Add or update tests alongside the implementation.
6. Run the ticket's narrow tests first, then the phase suite.
7. Report exact files changed, commands run, results, assumptions, and remaining risks.
8. Commit only after the ticket is verified, using an atomic message.
9. Never mark a requirement complete without evidence.

Only the GLM-5.2 lead may merge or reconcile work produced by another model.

---

## 3. Current starting condition

The repository contains implementations for most beta modules, but completion claims cannot currently be trusted.

- Branch: `master`, current recorded HEAD `37f9c64`.
- Worktree: approximately 222 uncommitted files.
- Existing report claims 32 passing tests and 105 assertions, but this must be rerun from the preserved worktree.
- Existing modules include schema, roles, admin resources, representative flows, pricing/proformas, invoices/payments, alarms, dashboards, PDFs, and demo data.
- The correct approach is repair and requirement closure, not rebuilding everything.

Known release blockers include:

1. Fatal `PricingService` interface/class naming collision and placeholder pricing behavior.
2. Broken and unsafe stock resource/import implementation.
3. Stock writes that bypass `StockService` and matching `stock_movements`.
4. Import code references `maatwebsite/excel`, which is not installed; the project has `spatie/simple-excel`.
5. Invalid Filament action namespaces and a nonexistent stock header-action call.
6. Stock UI references a nonexistent `is_reserved` column.
7. Missing stock policy/tenant enforcement around the new resource.
8. Broken Leaflet JavaScript loading and coordinate persistence.
9. Customer edit can overwrite saved coordinates with the administrator's current location.
10. Representatives can potentially open another representative's visit by changing the URL.
11. Customer approval does not consistently record approver, approval time, or rejection reason.
12. Representative creation does not provision the van warehouse and cash box.
13. User creation explicitly uses bcrypt despite the Argon2id requirement.
14. Development lazy-loading protection is missing.
15. Existing tests mostly exercise services directly and do not prove the real browser workflows.
16. The AM1–AM9 test creates several records directly and therefore does not certify the actual UI or validation paths.
17. Financial services use floats and have insufficient tenant, ownership, idempotency, overpayment, and missing-warehouse safeguards.
18. Some accessibility changes contain untranslated labels and invalid keyboard behavior.

---

## 4. Non-negotiable execution rules

These rules apply to every ticket:

- Do not begin the next phase until the current phase gate passes.
- No new package outside the approved stack without user approval.
- No secrets in source, frontend output, logs, seed data, screenshots, or test fixtures.
- No shell execution from application code.
- Every write receives server-side validation.
- Never pass unfiltered request data into a model.
- All money and stock operations occur in services inside database transactions.
- Do not use binary floating-point arithmetic for persisted money.
- No negative van stock.
- Every stock mutation creates exactly one matching movement, or a matched transfer pair.
- Financial and stock records are reversed with compensating records; they are never destructively deleted.
- All document numbers are server-generated, sequential per company, immutable, and concurrency-safe.
- Every query and model-binding path is tenant- and role-safe.
- Every list is paginated and every relationship is eager-loaded where needed.
- Arabic is the default authoring language; English must also be complete.
- Destructive and financial actions require bilingual consequence-specific confirmation.
- Tests include success, validation failure, authorization failure, tenant failure, and transaction rollback.
- The app must remain installable and useful under intermittent connectivity, but the beta does not pretend to be fully offline.
- ETA integration is not part of beta, so beta invoices are demo/UAT artifacts. Real production invoicing remains disabled until the v1.0 ETA gate passes.

---

## 5. Status and evidence format

Use these statuses only:

- `NOT STARTED`
- `IN PROGRESS`
- `BLOCKED — DECISION`
- `BLOCKED — DEFECT`
- `IMPLEMENTED — UNVERIFIED`
- `VERIFIED`

For every verified ticket, add an evidence row to the phase report:

| Ticket | Commit | Tests | Browser evidence | Requirement IDs | Reviewer | Result |
|---|---|---|---|---|---|---|

No evidence means the ticket remains unverified.

---

## 6. Phase R — Preserve and recover the current build

No beta feature work starts before this recovery phase passes.

### R-01 — Preserve the current worktree

**Owner:** GLM-5.2  
**Priority:** P0  
**Dependencies:** None

Tasks:

- Record the current branch, HEAD, remotes, PHP/Node versions, and full status.
- Save a full diff and untracked-file inventory outside the source tree or in an approved recovery folder.
- Confirm which changes belong to the current beta effort.
- Create a recovery branch named with the `codex/` prefix or an equivalent user-approved checkpoint branch.
- Commit the current intended work as a clearly labelled recovery checkpoint only after reviewing that it contains no secrets or temporary files.
- Do not discard, reset, clean, or overwrite any user work.

Exit evidence:

- The original work can be reconstructed.
- The working branch and checkpoint commit are recorded.
- `git status` after the checkpoint is understood; ideally clean before repairs begin.

### R-02 — Install the authoritative planning documents

**Owner:** GLM-5.2  
**Priority:** P0  
**Dependencies:** R-01

Tasks:

- Copy the two supplied v1.1 documents into `docs/spec/` without modifying their wording.
- Add a short source-precedence note.
- Mark old completion reports as historical, not authoritative.
- Do not modify `docs/BUSINESS_RULES.md` or `docs/SECURITY.md`.
- Create the requirement traceability table described in section 17.

Exit evidence:

- Both source documents are version-controlled.
- Every beta requirement has one planned owner and one acceptance test location.

### R-03 — Establish a trustworthy baseline

**Owner:** GLM-5.2  
**Reviewer:** Qwen3.7 Max  
**Priority:** P0  
**Dependencies:** R-01

Run and record:

- Dependency validation and vulnerability audit.
- Syntax check on every PHP file, including untracked PHP files.
- Code formatting check.
- Fresh database migration and seed using the supported database.
- Full automated test suite.
- Frontend clean install and production build.
- Route listing and application boot.
- Search for forbidden shell functions, `eval`, direct stock quantity writes, unbounded `get()`, `$request->all()`, hard-coded secrets, and debug output.

Do not fix findings in this ticket. Produce a baseline with exact failing commands and classify each failure P0–P3.

Exit evidence:

- A reproducible baseline report exists.
- Every failure has a ticket or is explicitly out of beta scope.

### R-04 — Restore application compilation and container boot

**Owner:** GLM-5.2  
**Priority:** P0  
**Dependencies:** R-03

Tasks:

- Alias the pricing contract correctly and remove the class-name collision.
- Replace the placeholder pricing implementation as part of B4; until then, make the container boot without pretending pricing is complete.
- Repair invalid Filament namespaces and APIs.
- Remove all references to unavailable classes and packages.
- Confirm every service contract resolves through the container.
- Confirm all routes and Filament resources can be discovered.

Required tests:

- Syntax check over all PHP files.
- Container resolution test for every service contract.
- Route-list smoke test.
- Filament panel boot test.

### R-05 — Repair stock integrity and stock administration

**Owner:** GLM-5.2  
**Reviewer:** Qwen3.7 Max  
**Priority:** P0  
**Dependencies:** R-04

Tasks:

- Make the stock resource read-only for balances unless an adjustment action explicitly calls `StockService::reconcile()`.
- Remove generic create, edit, delete, and bulk-delete paths for stock balances.
- Remove nonexistent fields and filters such as `is_reserved` unless a binding schema requirement proves they belong.
- Add a `StockPolicy` and tenant-aware queries.
- Require adjustment reason, counted quantity, exact consequence confirmation, user, timestamp, and reference.
- Guarantee every adjustment runs in a transaction and creates a movement.
- Replace the unavailable Maatwebsite importer with `spatie/simple-excel`.
- Validate file type, size, headings, encoding, row count, values, duplicates, warehouse ownership, product ownership, batch ownership, dates, and quantities.
- Do not create products silently during stock import.
- Use a staging/preview step: validate all rows, show accepted/rejected counts, then require confirmation before applying.
- Apply all valid rows in a transaction or documented safe chunks; a failed chunk must not leave unexplained balances.
- Use `StockService` for every applied delta.
- Record import status, source filename, counts, errors, actor, company, warehouse, and timestamps in `warehouse_import_logs`.
- Define clearly whether imported quantity means absolute counted stock or a delta. Default to absolute reconciliation unless the client file says otherwise.
- Make rerunning the same import idempotent or explicitly blocked by checksum.
- Export a row-level error file or readable error list.

Required tests:

- Cross-company warehouse/product IDs are rejected.
- Unauthorized roles cannot view or import stock.
- Missing/extra headings fail clearly.
- Invalid numbers, negative quantities, duplicate rows, unknown SKU, invalid batch, and bad encoding are reported.
- Preview changes nothing.
- Confirmed import creates matching stock movements.
- A failed import rolls back the affected transaction/chunk.
- Reimport behavior matches the documented idempotency rule.
- All lists paginate.

### R-06 — Repair the customer map

**Owner:** Kimi K2.7 Code  
**Integrator:** GLM-5.2  
**Priority:** P0  
**Dependencies:** R-04

Tasks:

- Import Leaflet JavaScript and CSS through Vite; do not depend on an undefined global.
- Use one supported Filament custom field implementation, not an unused field plus a raw view.
- Bind latitude and longitude through Filament's state system.
- Load saved coordinates on edit.
- Never auto-replace saved coordinates on edit.
- Put “use my current location” behind an explicit button.
- Handle browser denial, timeout, inaccurate GPS, offline tiles, and missing customer coordinates.
- Default a new unsaved customer to the configured Egypt operating area, not Riyadh.
- Validate legal latitude/longitude ranges on the server.
- Ensure map listeners and map instances are cleaned up on Livewire navigation.

Required tests/evidence:

- Create customer with selected coordinates.
- Edit unrelated customer data without coordinate changes.
- Explicitly update coordinates.
- GPS denied path.
- Arabic and English rendering in light and dark admin themes.

### R-07 — Close immediate authorization and provisioning holes

**Owner:** GLM-5.2  
**Reviewer:** Qwen3.7 Max  
**Priority:** P0  
**Dependencies:** R-04

Tasks:

- Authorize bound visits by company, assigned representative, and permitted status.
- Scope PDF downloads by company and role.
- Scope proformas, invoices, customers, products, warehouses, alarms, and purchase requests consistently.
- Record customer approval actor/time and rejection actor/time/reason.
- Prevent a representative from approving their own pending customer.
- Provision one van warehouse and one cash box when a representative role is assigned.
- Make provisioning transactional and idempotent.
- Decide safe behavior when the representative role is removed or company changes; never delete financial history.
- Replace explicit bcrypt calls with Laravel's configured `Hash` service.
- Enable `Model::preventLazyLoading(! app()->isProduction())`.

Required tests:

- Representative A receives 404/403 for Representative B's visit.
- Cross-company IDs fail even when guessed directly.
- PDF authorization cannot be bypassed by URL editing.
- Approval metadata and rejection reason persist.
- Representative provisioning runs exactly once and rolls back on failure.
- New passwords are Argon2id.

### R-08 — Repair regression and accessibility defects

**Owner:** Kimi K2.7 Code  
**Integrator:** GLM-5.2  
**Priority:** P1  
**Dependencies:** R-04

Tasks:

- Translate all accessible labels and navigation names.
- Replace fake clickable `div` elements with native buttons/links.
- Remove invalid keyboard handlers.
- Verify focus order, visible focus, 44px targets, dialog focus containment/return, error association, and live regions.
- Verify reduced motion.
- Keep red reserved for alarms/errors.
- Confirm no accessibility fix breaks Livewire actions.

### Recovery gate

The recovery phase passes only when:

- The worktree is preserved.
- The app boots.
- Every PHP file parses.
- Dependencies match imports.
- Fresh migration/seed works.
- Stock cannot be mutated outside `StockService`.
- Visit and document ownership checks pass.
- The map saves and preserves coordinates.
- The full existing test suite passes or each remaining failure belongs to a later explicit ticket.

Commit: `fix: beta recovery — restore safe executable baseline`

---

## 7. Phase B0 — UI standards and shell

### B0-01 — Design tokens and bilingual style guide

**Owner:** Kimi K2.7 Code  
**Integrator:** GLM-5.2

- Reconcile existing CSS with the binding GPC palette, typography, spacing, radii, shadows, focus, and alarm-red rules.
- Define logical-direction utilities; avoid left/right assumptions.
- Create an authenticated style-guide route restricted to development/admin.
- Render typography, colors, buttons, inputs, cards, status chips, tabs, stepper, tables, confirmations, alerts, and toasts in AR and EN.
- Verify Noto Kufi Arabic or approved fonts load without layout shift.

### B0-02 — Standard interface states

**Owner:** Kimi K2.7 Code

- Implement reusable loading skeleton, empty state with useful action, inline error, page error, disabled state, optimistic/submitting state, and success toast.
- Apply them to every beta page and resource.
- Never show an indefinite spinner with no explanation.
- Prevent duplicate submission while a write is pending.

### B0-03 — Arabic, English, RTL, accessibility, and error pages

**Owner:** GLM-5.2

- Move user-facing text into translation files.
- Establish Arabic-first fallback behavior.
- Verify full layout direction changes, number/date formatting, mixed SKU text, telephone numbers, currency, icons, and chevrons.
- Complete bilingual 403, 404, 419, and 500 pages.
- Verify semantic landmarks, headings, labels, errors, focus, keyboard use, contrast, and screen-reader names.

### B0-04 — Representative shell and installable baseline

**Owner:** Kimi K2.7 Code

- Ensure the app manifest has correct bilingual name/short name, icons, theme/background colors, start URL, scope, and standalone display.
- Ensure the service worker caches only safe shell assets at B0.
- Add an update-available strategy and prevent stale authenticated HTML from being cached incorrectly.
- Show a clear online/offline indicator.
- Provide bottom-tab shell placeholders for Home, Visits, Customers, Orders, and More.

### B0 tests and gate

- Component rendering tests for every standard state.
- AR/EN direction and translation smoke tests.
- Keyboard and reduced-motion checks.
- Mobile widths at 320, 360, 390, and 430 pixels.
- Admin light/dark screenshots.
- No untranslated user-facing strings on beta routes.
- Production asset build passes.

Commit: `feat: phase B0 — UI standards and installable shell`

---

## 8. Phase B1 — Schema, tenancy, authentication, roles, and audit

### B1-01 — Schema certification

**Owner:** GLM-5.2  
**Reviewer:** Qwen3.7 Max

- Compare every current migration with the binding schema.
- Confirm all required beta and deferred-module tables exist as required by schema-first B1.
- Confirm primary keys, foreign keys, cascade/restrict behavior, nullable fields, indexes, unique constraints, decimal precision, timestamps, soft-delete rules, and status values.
- Confirm `visit_reports.signature_path`, `invoices.signature_path`, `visits.arrival_flag`, `activities`, nullable item `batch_id`, and append-only movements.
- Add database constraints for impossible values where supported: negative quantities/balances where forbidden, invalid coordinate ranges, invalid range values, and invalid status combinations.
- Make migrations work on a clean database; do not rely on historical local state.

### B1-02 — Tenant isolation

**Owner:** GLM-5.2  
**Reviewer:** Qwen3.7 Max

- Inventory every tenant-owned model and require a reliable company path.
- Apply the approved company scope consistently.
- Ensure console jobs, seeders, imports, queued work, Filament, Livewire, route binding, policies, services, and PDF endpoints set and respect company context.
- Reject mismatched company IDs inside services even if the caller is compromised.
- Do not let super-admin behavior accidentally become the default no-policy behavior.

### B1-03 — Seven roles and permissions

**Owner:** DeepSeek V4 Pro  
**Integrator:** GLM-5.2

- Seed exactly the seven roles and the approved permission matrix.
- Make seeding idempotent.
- Add policies for every beta resource and financial/stock action.
- Distinguish view, create, update, approve, reject, collect, cancel/reverse, import, and export permissions.
- Verify panel access and representative-app access independently.
- Verify cost/base-price visibility is restricted as specified.

### B1-04 — Authentication and session security

**Owner:** GLM-5.2

- Argon2id hashing through the framework hash service.
- Login rate limit: five attempts/minute per IP plus normalized email.
- POST rate limit: 60/minute per authenticated user, with a safe guest fallback.
- Regenerate sessions on login and invalidate on logout.
- Secure, HttpOnly, SameSite cookies in production and TLS enforcement.
- `APP_DEBUG=false` in production.
- Account-active and company-active checks.
- Bilingual login errors that do not reveal whether an account exists.
- Security headers without breaking Filament, Livewire, Leaflet, PDFs, or PWA behavior.

### B1-05 — Activity audit

**Owner:** GLM-5.2

- Record login, failed privileged action where appropriate, user changes, role changes, price changes, approvals/rejections, stock imports/adjustments, invoice issue/cancel, payment collect/cancel, alarm state changes, and purchase review decisions.
- Include actor, company, action, subject type/id, safe metadata, and timestamp.
- Redact secrets, passwords, tokens, signature image data, and unnecessary personal data.
- Prevent normal application users from editing/deleting activities.

### B1-06 — Model and query safety

**Owner:** GLM-5.2

- Review `$fillable`, casts, enums, date/time handling, money precision, and relationships.
- Remove unsafe direct writes from controllers/Livewire components.
- Enable lazy-loading prevention outside production and repair resulting N+1 queries.
- Paginate all list/search/admin queries and cap search results.
- Add deterministic sorting.

### B1 tests and gate

- Clean PostgreSQL `migrate:fresh --seed`.
- Schema assertions for every amendment column and critical constraint.
- Role-by-permission matrix tests.
- Cross-company CRUD, route-binding, PDF, import, and service-call tests.
- Login/session/rate-limit tests.
- Audit-record tests and redaction tests.
- N+1 checks on major lists.

Commit: `feat: phase B1 — schema auth tenancy roles and audit`

---

## 9. Phase B2 — Administration and master data

### B2-01 — Company and bank accounts

**Owner:** DeepSeek V4 Pro  
**Integrator:** GLM-5.2

- Company details, Egyptian operating defaults, VAT settings, bilingual identity, invoice/proforma details, and active status.
- Multiple bank accounts with one controlled default per company.
- Validate account data and prevent cross-company selection.
- Restrict Finance-sensitive fields by permission.
- Ensure PDFs use the snapshotted/selected account expected by the document.

### B2-02 — User lifecycle and representative provisioning

**Owner:** GLM-5.2

- Create/edit/deactivate users with server validation and Argon2id hashing.
- Assign roles within permitted company scope.
- Provision rep van warehouse and cash box transactionally and idempotently.
- Prevent unsafe deletion when history exists; deactivate instead.
- Record role and account changes in activities.

### B2-03 — Products, categories, suppliers, routes, and pricing base

**Owner:** DeepSeek V4 Pro  
**Integrator:** GLM-5.2

- Complete bilingual CRUD, validation, pagination, search, filters, permissions, and tenant scoping.
- Validate unique SKU/code per company.
- Keep cost/base price hidden from unauthorized roles.
- Support EGP-only beta pricing.
- Make route assignment usable by daily scheduling.
- Avoid hard deletion when referenced.

### B2-04 — Customer administration and GPS

**Owner:** Kimi K2.7 Code  
**Integrator:** GLM-5.2

- Complete customer fields, route assignment, contact data, financial limits, status, GPS picker, search, pagination, and tenant scope.
- Validate GPS and present a map/deep-link safely.
- Handle customers without coordinates explicitly.

### B2-05 — Customer approval queue

**Owner:** GLM-5.2

- Queue pending field-created customers.
- Approve with actor/time and activate according to policy.
- Reject with mandatory bilingual reason, actor/time, and safe inactive behavior.
- Notify the submitting representative.
- Prevent duplicate/conflicting decisions.
- Audit every decision.

### B2-06 — Warehouse stock import

**Owner:** GLM-5.2  
**Decision dependency:** D-03 real client file before final acceptance

- Complete the repaired importer from R-05 against a documented mock format.
- Add a downloadable template with bilingual instructions.
- Add preview, validation report, confirmation, progress/result state, idempotency, and import history.
- Map the real client file only after D-03 is answered.
- Include the agreed read-only transit quantity source without allowing it to inflate sellable van stock.

### B2-07 — Admin usability

**Owner:** MiniMax M3 review; fixes integrated by GLM-5.2

- Enable Filament dark mode.
- Verify all resources in light/dark AR/EN.
- Standard states, confirmations, pagination, search, filters, responsive tables, and accessible forms.
- No raw database IDs where a meaningful label should appear.

### B2 tests and gate

- Admin creates the complete master-data set from a clean seed.
- User/rep provisioning and deactivation tests.
- Customer map persistence and approval/rejection tests.
- Bank-default uniqueness and PDF selection tests.
- Stock import preview/apply/rollback/security/idempotency tests.
- All resources pass role and tenant tests.
- Real sample import remains the only allowed D-03 blocker.

Commit: `feat: phase B2 — secure admin master data and stock import`

---

## 10. Phase B3 — Representative day and visit loop

### B3-01 — Start/end work session

**Owner:** GLM-5.2

- Start the day once, record time and optional permitted context, and show current session.
- Prevent duplicate active sessions.
- Define safe end-day behavior when a visit/report/retry is pending.
- Show clear no-session and completed-day states.

### B3-02 — Daily assignments and manager master schedule

**Owner:** GLM-5.2

- Manager assigns visits by representative/date/customer/route/purpose.
- Prevent cross-company assignment and invalid/inactive entities.
- Representative sees only their date-appropriate assignments.
- Manager master schedule supports date, route, representative, customer, and status filters with pagination.
- Define reschedule/cancel behavior without deleting history.
- Notify/refresh representative data when assignments change.

### B3-03 — Visit stepper and state machine

**Owner:** GLM-5.2

- States: Scheduled → Arrived → Report → Done.
- Enforce allowed transitions on the server.
- Require an active work session and correct assignment/ownership.
- Make transition calls idempotent for retries/double taps.
- Preserve history and timestamps.
- Show the current and completed steps accessibly in AR/EN.

### B3-04 — GPS arrival and edge cases

**Owner:** GLM-5.2  
**Reviewer:** Qwen3.7 Max  
**Decision dependency:** D-02 client sign-off

- Use one configurable geofence radius; do not scatter 1km/1.5km constants.
- Capture coordinates, accuracy, timestamp, calculated distance, and arrival flag.
- In-range arrival proceeds normally.
- Out-of-range requires consequence-specific confirmation, stores `out_of_range_confirmed`, and notifies the manager.
- GPS denied/unavailable stores `gps_denied`, prompts enablement, allows only the agreed fallback, and notifies/flags as required.
- Missing customer coordinates use a clearly defined flagged path; never calculate from `(0,0)`.
- Reject invalid/spoofed-out-of-range payloads as far as the browser/server architecture reasonably allows.
- Make repeated arrival submissions idempotent.

### B3-05 — Signed visit report

**Owner:** Kimi K2.7 Code  
**Integrator:** GLM-5.2

- Validate required report fields and follow-up consistency.
- Capture signature on a touch-friendly canvas.
- Validate signature MIME/size/dimensions and reject invalid base64.
- Store signature privately using a random server-generated path.
- Persist `signature_path` on the report.
- Save report and close visit inside one service transaction.
- Prevent a second report unless explicitly allowed.
- Authorize signature access.
- Clean up orphaned files if database persistence fails.

### B3-06 — Field customer creation

**Owner:** GLM-5.2

- Mobile form with server validation, duplicate hints, GPS capture, route, and contact details.
- Force status to pending regardless of client payload.
- Record creator and company server-side.
- Raise manager notification.
- Prevent the representative from using the customer in restricted flows until approved, according to the PRD.

### B3-07 — Graceful offline/degraded package

**Owner:** Kimi K2.7 Code  
**Integrator:** GLM-5.2

- Persistent connection indicator.
- Versioned, per-user/per-company/per-form local draft keys.
- Autosave visit report and beta invoice drafts without storing secrets or signature blobs longer than necessary.
- Restore, discard, and “draft from another account” safeguards.
- Submission retry queue with unique operation ID, retry count, last error, exponential backoff, and manual retry.
- Server idempotency key for every queued write.
- Cached read-only current-day assignments/customers sufficient to view the day when disconnected.
- Clear stale cache on logout/company/user change.
- Document last-write-wins behavior and where it does not apply to financial/stock operations.
- Do not claim full offline transaction support.

### B3-08 — Representative navigation, search, and maps

**Owner:** Kimi K2.7 Code

- Bottom tabs: Home, Visits, Customers, Orders, More.
- Active state, keyboard/screen-reader names, RTL behavior, safe-area padding, and no overlap with content.
- Customer search with capped/paginated results and clear empty/error/loading states.
- Google Maps HTTPS deep-link using validated coordinates and an address fallback.
- Clear action when location is unavailable.

### B3 tests and gate

- Assignment ownership and tenant tests.
- Work-session duplication and close-day tests.
- State transition and idempotency tests.
- GPS in-range, out-of-range, denied, inaccurate, missing-customer-coordinate, and replay tests.
- Signature validation, private access, rollback, and orphan cleanup tests.
- Pending-customer security and notification tests.
- Browser test: kill/reopen restores draft.
- Browser test: offline queue retries once without duplicate report/customer.
- AR/EN mobile walkthrough on a physical phone or equivalent browser session.

Commit: `feat: phase B3 — complete representative day and visit loop`

---

## 11. Phase B4 — Pricing chain and proforma

### B4-01 — Pricing strategy configuration

**Owner:** GLM-5.2  
**Reviewer:** Qwen3.7 Max  
**Decision dependency:** D-01 before UAT

- Implement a strategy interface with `floor_only` and `two_sided` modes.
- Store the selected strategy per company in server-controlled configuration/database settings.
- Validate nonnegative and logically consistent ranges.
- Use decimal strings/Money value objects, never floats.
- Centralize all range calculations and bilingual errors.
- Audit strategy and range changes.

### B4-02 — Price request and manager queue

**Owner:** GLM-5.2

- Representative submits customer, product, quantity, visit, and notes.
- Validate ownership, approved customer, active product, company, and duplicate/open-request behavior.
- Manager sees a Requested queue and can approve/reject with permission and reason.
- Store base price, manager/rep allowances, actor, timestamps, and immutable request history.
- Notify representative of outcome.

### B4-03 — Server-side price enforcement

**Owner:** GLM-5.2  
**Reviewer:** Qwen3.7 Max

- Replace the placeholder `PricingService` with persisted quotation/range logic.
- Derive allowed price only from authorized server records.
- Reject missing, expired, superseded, rejected, cross-company, wrong-product, wrong-customer, and wrong-representative quotations.
- Test inclusive boundaries and decimal rounding.
- Never rely on disabled fields or JavaScript for enforcement.

### B4-04 — Proforma lifecycle

**Owner:** GLM-5.2

- Create proforma from an approved quotation with one or more validated items if the binding schema supports them.
- Enforce price strategy for each line.
- Generate concurrency-safe sequential number per company.
- Calculate subtotal, VAT, and total using decimal-safe operations.
- Select/inject company bank account.
- Support draft/sent/converted/cancelled states with allowed transitions.
- Store historical document data so later company/product changes do not rewrite issued PDFs.
- Prevent direct editing of number, issuer, company, and calculated totals.

### B4-05 — Proforma PDF and WhatsApp sharing

**Owner:** Kimi K2.7 Code  
**Integrator:** GLM-5.2

- Generate and store one bilingual historical PDF.
- Include company/customer details, items, totals, validity, bank details, and document number.
- Authorize access and use private/signed delivery as appropriate.
- Build a safely encoded `wa.me` link containing a message and an authorized/publicly usable document link according to deployment constraints.
- Provide a copy-link fallback where direct attachment is not possible.
- Do not claim that `wa.me` attaches a local file automatically.

### B4 tests and gate

- Floor-only and two-sided strategy tests.
- 900 boundary accepted, 899.99 rejected under the example; 950 accepted and 850 rejected.
- Cross-tenant, wrong-rep, wrong-customer/product, expired, and superseded quotation tests.
- Concurrent number-generation test.
- Decimal/VAT/rounding tests.
- PDF content and access tests.
- WhatsApp URL encoding and fallback tests.

Commit: `feat: phase B4 — configurable pricing and proforma enforcement`

---

## 12. Phase B5 — Invoices, collections, and live stock

### B5-01 — Harden invoice prerequisites

**Owner:** GLM-5.2  
**Reviewer:** Qwen3.7 Max

- Validate authenticated representative, active company, approved customer, customer/company match, active product, unit, positive quantity, price permission, optional proforma ownership/status, optional visit ownership/status, batch ownership, and warehouse ownership.
- Require a provisioned van warehouse. Never silently create an invoice without stock deduction.
- Define direct-invoice permission separately from proforma conversion.
- Lock source proforma and prevent duplicate conversion.
- Use request/idempotency keys to prevent duplicate invoices.

### B5-02 — Atomic invoice transaction

**Owner:** GLM-5.2  
**Reviewer:** Qwen3.7 Max

Within one database transaction:

1. Lock the numbering sequence.
2. Lock relevant sellable stock rows.
3. Revalidate all entities and price permissions.
4. Create invoice and invoice items.
5. Decrement stock only through `StockService`.
6. Create matching append-only stock movements.
7. Update customer balance.
8. Mark source proforma converted once.
9. Record the activity.
10. Commit all or nothing.

Additional rules:

- Use decimal-safe arithmetic and persisted decimal fields.
- Do not update stock with model increments outside the stock service.
- A missing stock row means zero stock.
- Concurrent sales cannot oversell.
- PDF generation happens after commit or through a safe after-commit process; PDF failure must not corrupt financial state.
- Cancellation is idempotent and creates compensating stock/balance records; it cannot run twice.
- Beta invoice editing after issue is prohibited; use cancellation/compensation.

### B5-03 — Invoice number, QR, PDF, and signature

**Owner:** GLM-5.2 with Kimi K2.7 Code on the template

- Sequential, immutable, per-company invoice number.
- Bilingual simplified invoice PDF with snapshotted data.
- QR content defined and tested for the beta; clearly label it as non-ETA production compliance.
- Optional/required signature according to the PRD flow, validated and stored privately.
- Generate the stored artifact once; do not silently rewrite historical invoices.
- Authorized download and WhatsApp sharing.

### B5-04 — Collections and cash-box ledger

**Owner:** GLM-5.2  
**Reviewer:** Qwen3.7 Max

- Support cash, cheque, and transfer with method-specific required fields.
- Validate positive amount, customer/company, invoice/company/customer, collector permission, currency, and payment date.
- Prevent overpayment unless an explicit customer-credit rule exists.
- Apply payment, invoice paid/remaining amounts, customer balance, and cash-box balance in one transaction.
- Cash changes cash-box balance; cheque/transfer follow the binding accounting behavior.
- Treat payment records as append-only beta ledger entries and expose a paginated ledger view with running/recorded balance.
- Cancellation/reversal is idempotent, permission-controlled, confirmed, audited, and compensating.
- Never allow the cash box or invoice remaining amount to drift through repeated requests.

### B5-05 — Representative live stock and transit visibility

**Owner:** GLM-5.2

- Product/SKU/name search with pagination/cap.
- Show sellable van quantity separately from read-only transit quantity.
- Never include transit in oversell validation.
- Timestamp the last stock update and show stale/offline state.
- Tenant and representative warehouse scope.
- Eager-load product/unit/batch data.

### B5-06 — Invoice draft degradation

**Owner:** Kimi K2.7 Code  
**Integrator:** GLM-5.2

- Autosave non-issued invoice form drafts locally.
- Do not create an invoice or reserve/deduct stock while offline.
- Queue submission with idempotency key when connectivity returns.
- Revalidate price and stock on the server at submission time.
- Display a clear conflict/rejection result instead of silently changing price or quantity.

### B5 tests and gate

- Successful invoice creates invoice, items, exact stock movement, lower stock, and customer balance.
- Forced failure after each transaction step leaves no partial state.
- Missing van warehouse rejects the invoice.
- Oversell and concurrent oversell tests.
- Cross-tenant/customer/product/warehouse/proforma tests.
- Duplicate request and duplicate conversion tests.
- Decimal, VAT, and rounding tests.
- Cancellation twice does not reverse twice.
- Cash/cheque/transfer, partial payment, full payment, invalid amount, and overpayment tests.
- Payment forced-rollback and reversal tests.
- PDF/QR/signature/access/share tests.
- Browser offline draft/retry conflict test.

Commit: `feat: phase B5 — atomic invoices collections and live stock`

---

## 13. Phase B6 — Alarms, complaints, visibility, and dashboard

### B6-01 — Alarm foundation

**Owner:** GLM-5.2

- Tenant-scoped alarm and recipient records.
- Severity, type, message, reference, creator, timestamps, acknowledged/resolved state, and resolution note.
- Per-user read state without hiding unresolved alarms.
- Idempotent alarm creation for retried source actions.
- Paginated role-appropriate list and unread count.
- Red only for urgent/critical alarms.

### B6-02 — Out-of-stock alarm broadcast

**Owner:** GLM-5.2

- Representative submits product, requested quantity, customer/visit, and note.
- Validate ownership/company and avoid duplicate open requests.
- Broadcast simultaneously to Finance, Manager, and Executive recipients in the same company.
- Payload visibly identifies representative and material.
- Recipient absence is reported; it must not leak to another company.
- Acknowledge and resolve according to permission rules.

### B6-03 — Complaints lifecycle

**Owner:** DeepSeek V4 Pro  
**Integrator:** GLM-5.2

- Representative logs complaint with customer, visit, category, description, and optional safe evidence.
- Raise manager alarm.
- Manager acknowledges and resolves with mandatory resolution.
- Preserve immutable source complaint and complete timeline.
- Notify submitting representative of resolution.

### B6-04 — Minimal dashboards

**Owner:** DeepSeek V4 Pro  
**Reviewer:** MiniMax M3 visual QA

- Visits today.
- Pending quotations.
- Open alarms.
- Sales today.
- Each widget uses the viewer's company, role, timezone, and permitted scope.
- Numbers link to filtered lists where useful.
- Clear loading, empty, error, and stale states.
- Verify query count and indexes.

### B6-05 — Required report/document visibility

**Owner:** GLM-5.2

- Role-appropriate paginated lists for visit reports, quotations, proformas, and invoices.
- Finance section includes required bank/pricing/document visibility without exposing unrelated admin controls.
- Executive views remain read-heavy.
- Search/filter/export only where beta requires it; full report suite remains v1.0.

### B6 tests and gate

- Exact three-role out-of-stock broadcast in the correct company.
- Duplicate/retry does not create duplicate alarms.
- Read/acknowledge/resolve authorization tests.
- Complaint lifecycle and notifications.
- Dashboard values verified against seeded records and timezone boundaries.
- Cross-company and role visibility tests.
- Mobile/admin AR/EN visual tests.

Commit: `feat: phase B6 — alarms complaints and live dashboard`

---

## 14. Phase B7 — Purchase offers and dual review

### B7-01 — Representative purchase-offer submission

**Owner:** DeepSeek V4 Pro  
**Integrator:** GLM-5.2

- Supplier, product, quantity, offered price, validity, terms, notes, and safe attachment if required.
- Server-side company/role/entity validation.
- EGP-only beta behavior unless the client explicitly changes scope.
- Immutable submitted snapshot and submitter/timestamp.
- Draft/submitted status with idempotent submission.

### B7-02 — Sales and Purchasing dual review

**Owner:** GLM-5.2  
**Reviewer:** Qwen3.7 Max  
**Decision dependency:** D-04

- Separate Sales and Purchasing decisions with actor/time/reason.
- Sales veto immediately prevents final approval.
- Purchasing approval cannot override a Sales veto.
- Define order independence, resubmission, expiry, and conflicting simultaneous decisions according to D-04.
- Lock rows during decisions and make retries idempotent.
- No destructive deletion.

### B7-03 — Queues, alarms, and audit

**Owner:** DeepSeek V4 Pro  
**Integrator:** GLM-5.2

- Role-specific pending queues, filters, pagination, states, and decision confirmations.
- Notify submitter and other reviewer as required.
- Audit submission and both decisions.

### B7 tests and gate

- Both review orders.
- Sales veto first/second.
- Purchasing approve first followed by Sales veto.
- Duplicate and simultaneous decisions.
- Unauthorized, wrong-role, cross-company, expired, rejected, and resubmitted paths.
- Bilingual queue and confirmation flow.

Commit: `feat: phase B7 — purchase offers and dual review`

---

## 15. Phase B8 — Seed, end-to-end QA, deployment, and release

### B8-01 — Deterministic demo data

**Owner:** GLM-5.2

- Idempotent `DemoSeeder` for one primary demo company and any isolation-test company.
- Seven roles and named demo users with non-production credentials documented safely.
- Five assigned visits for the representative today.
- Routes, customers with valid Cairo/Egypt coordinates, pending customer, products including Material 952, base prices, bank account, van warehouse, cash box, sellable stock, transit quantity, and dashboard-ready records.
- Seed dates relative to `today()` without becoming invalid at month/year boundaries.
- No real personal data or secrets.

### B8-02 — Complete automated test pyramid

**Owner:** GLM-5.2  
**Reviewer:** Qwen3.7 Max

Required suites:

- Unit: Money, PriceRange strategies, GPS distance/range, number formatting, and state transitions.
- Service: stock increment/decrement/reconcile/transfer, pricing, invoice, payment, alarm, complaint, approvals, and purchase review.
- Feature: auth, rate limits, roles, tenant isolation, admin resources, import, visit flow, pricing/proforma, invoice/payment, alarms/complaints, purchase flow, PDF access, and locale.
- Failure-path tests for every money/stock write.
- Browser/E2E: representative day flow, admin master-data/approval flow, and RTL smoke.
- Regression test reproducing the full Beta Done narrative through real application endpoints/components—not direct model creation as a substitute.

Coverage must prove behavior; do not chase a percentage while leaving critical branches untested.

### B8-03 — Browser and device QA

**Owner:** MiniMax M3 for visual review; GLM-5.2 owns fixes

Test:

- Chrome/Chromium desktop and Android-sized mobile.
- iPhone-sized Safari-equivalent behavior where available.
- 320/360/390/430px widths and admin desktop widths.
- Arabic RTL and English LTR.
- Admin light and dark mode.
- Slow connection, offline transition, reconnect, stale cache, app kill/reopen, duplicate taps, back navigation, session expiry, and CSRF expiry.
- GPS allowed, denied, timeout, low accuracy, and out-of-range.
- Signature touch input.
- PDF download, QR scan, WhatsApp URL, and print readability.
- Empty, loading, validation, server error, permission error, and success states on every beta workflow.

Record screenshots/video and defect IDs. No visual P0/P1 may remain.

### B8-04 — Security and data-integrity audit

**Owner:** Qwen3.7 Max read-only audit; GLM-5.2 fixes

- Tenant-boundary attack matrix using guessed IDs and modified payloads.
- Role/permission bypass attempts.
- Mass assignment, upload validation, stored XSS, reflected XSS, CSRF, open redirect, insecure direct object reference, path traversal, log leakage, and secret scan.
- Rate-limit and session behavior.
- Concurrent stock sale, duplicate submission, duplicate payment, duplicate cancellation, and sequence concurrency.
- Confirm no direct stock writes and no financial writes outside services/transactions.
- Dependency audit and production configuration review.

### B8-05 — Performance and reliability

**Owner:** GLM-5.2

- Query count/N+1 checks for dashboard, schedule, customer/product search, alarms, and admin lists.
- Index review for common tenant/date/status/search queries.
- Pagination limits and search throttling.
- Import memory/time limits and safe chunking.
- PDF generation time and file cleanup.
- Queue/retry observability if queues are used.
- Structured logs without sensitive payloads.
- Health endpoint that proves application availability without leaking details.

### B8-06 — UAT deployment, backup, and rollback

**Owner:** GLM-5.2

- Separate UAT environment and database.
- Production-like `APP_DEBUG=false`, TLS, secure sessions, queue/scheduler configuration, private storage, and least-privilege credentials.
- Run migrations in a rehearsed deployment sequence.
- Seed only approved demo data in UAT.
- Verify backup creation, encryption/access, retention, and restore into a clean environment.
- Document rollback for application release and forward-fix/restore strategy for database changes.
- Verify logs, health checks, disk space, failed jobs, and error reporting.
- Explicit feature flag/configuration preventing real production invoices before ETA compliance.

### B8-07 — Release artifacts and client acceptance

**Owner:** GLM-5.2

Produce:

- Requirement traceability report with every beta ID marked Verified or accepted blocker.
- Phase reports and test evidence.
- Known limitations: partial offline only, no push, no returns, no reconciliation UI, no full transit operations, no batches/COA workflow, no ETA production integration, no full reports, no Odoo migration.
- Demo credentials and reset instructions.
- Client walkthrough script in Arabic and English.
- Admin and representative quick-start notes.
- UAT feedback/defect log with severity and owner.
- Release notes and exact deployed commit.
- Signed approval for D-01 through D-04.

### B8 final beta gate

All must be true:

- All R and B0–B8 phase gates pass.
- Clean dependency install, fresh migration/seed, production asset build, syntax check, formatter check, and full tests pass.
- No P0 or P1 defects remain.
- No unresolved security, tenant, stock, money, or authorization finding remains.
- Exact Beta Done phone walkthrough passes twice from a reset seed.
- AR/EN and RTL/LTR pass.
- Offline draft/retry behavior passes without duplicate writes.
- Backup restore is proven.
- Client decisions D-01–D-04 are signed off.
- The deployed commit and evidence are recorded.
- The release is labelled Beta/UAT and production invoicing remains gated.

Commit: `feat: phase B8 — certified Jawla beta v1.1`

---

## 16. Master verification commands

The executor must adapt commands to the repository's approved tooling, but the gate must include equivalent evidence for:

```text
composer validate --strict
composer audit
syntax check every PHP file
vendor/bin/pint --test
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan test
npm ci
npm run build
route/panel/application boot smoke
forbidden-pattern and secret scan
browser walkthroughs
backup restore rehearsal
```

Use PostgreSQL for final certification if PostgreSQL is the deployment database. A passing SQLite-only test suite is insufficient for database constraints, locking, and concurrency.

---

## 17. Requirement traceability

| Requirements | Planned tickets |
|---|---|
| REQ-ROL-1…8 | B1-02, B1-03, B1-04 |
| REQ-VST-1…3 | B3-01, B3-02 |
| REQ-VST-4 | B2-04, R-06 |
| REQ-VST-5…7 | B3-03, B3-04, B3-05 |
| REQ-CUS-1,2,4 | B3-06 |
| REQ-CUS-3 | B2-05 |
| REQ-PRC-1 | B2-03 |
| REQ-PRC-2,4…8 | B4-01…B4-03 |
| REQ-INV-1…4 | B4-04, B4-05, B5-01…B5-04 |
| REQ-STK-1,2 | R-05, B2-06 |
| REQ-STK-4,5 | B5-05 |
| REQ-PUR-1…4 | B7-01…B7-03 |
| REQ-ALM-1…4 | B6-01, B6-02 |
| REQ-CRM-1…3 | B6-03 |
| REQ-RPT-1…3 | B2, B6-04, B6-05 |
| REQ-CMP-1 | B3-05, B5-03 |
| REQ-CMP-2 | B3-03 |
| REQ-CMP-3 | B3-07, B5-06 |
| REQ-CMP-4 | B0-04, B3-08 |
| REQ-CMP-5 | B0-01…B0-03 and every phase gate |
| REQ-CMP-6 | B3-08 |
| REQ-CMP-7 | B4-05, B5-03 |
| REQ-CMP-8 | B6-04 |
| REQ-CMP-9 | B3-08, B5-05 |
| REQ-CMP-10 | B3-04 |
| REQ-CMP-11 | B2-07 |
| REQ-CMP-12 | B5-01…B5-06 |
| TEC-1 | B3-04 |
| TEC-2 | B4-01…B4-03 |
| TEC-3 | B6-01, B6-02 |
| TEC-4 | R-05, B2-06, B5-05 |
| TEC-5 | B2-03, B4, B5, B7 |
| TEC-6 | B2-01, B4-04, B4-05 |
| TEC-7 | B1-03 |
| TEC-8 | B0, B3, B5, B8-03 |
| TEC-9 | B3-05, B5-03 |
| TEC-10 | B3-07, B5-06 |
| TEC-11 | B1-05 |
| TEC-12 | B3-07 and conflict documentation |

Before beta sign-off, replace every planned mapping with links to the exact tests and evidence.

---

## 18. Suggested execution batches

Do not ask one model to execute the entire document in one context. Use these controlled batches:

1. R-01 to R-03: preservation and baseline.
2. R-04: boot/compilation only.
3. R-05: stock recovery only.
4. R-06 to R-08: map, access, provisioning, and regressions.
5. B0.
6. B1-01 and B1-02.
7. B1-03 to B1-06.
8. B2-01 to B2-03.
9. B2-04 to B2-07.
10. B3-01 to B3-04.
11. B3-05 to B3-08.
12. B4.
13. B5-01 and B5-02.
14. B5-03 to B5-06.
15. B6.
16. B7.
17. B8-01 and B8-02.
18. B8-03 to B8-05.
19. B8-06 and B8-07.

Each batch ends with tests and a handoff report. Critical batches are sequential. Read-only review and visual QA may run in parallel, but two models must never edit the same files concurrently.

---

## 19. Scope explicitly excluded from this beta

Do not allow an executing model to pull these into B0–B8:

- Full offline-first architecture
- Push notifications
- Onboarding walkthrough
- Barcode lookup
- Biometric/2FA
- Representative dark mode
- Returns processing
- Cash reconciliation UI
- Expenses and van transfers
- Supplier comparison, purchase orders, and partial receipts
- Full goods-in-transit operations and landed cost
- Batch/COA/expiry workflow and historical backfill
- Full Egypt ETA integration
- Full reports/exports/visit map
- Odoo/Excel production migration and cutover
- Multi-currency
- Route optimization
- OCR, Saudi/ZATCA, gamification, AI assistant, or form builder

Existing schema for later modules may remain, but building their complete UI or workflows is not a beta task.

---

## 20. After beta

After client beta acceptance and the documented two-to-three-week hardening period, proceed in this order:

1. Returns, reconciliation UI, expenses, and van transfers.
2. Supplier comparison, purchase orders, and partial receipts.
3. Goods in transit and landed cost.
4. Batch, COA, expiry, and invoice-batch backfill.
5. Full Egyptian ETA compliance—mandatory before real production invoicing.
6. Full reports, exports, and visit map.
7. Odoo/Excel migration rehearsal, cutover, and production launch.

Only then begin v1.1 offline architecture, push, onboarding, barcode, 2FA, representative dark mode, bulk operations, and accounting-sync discovery.

---

## 21. Executor's starting prompt

Use this prompt when starting the GLM-5.2 execution session:

> You are the lead implementer for Jawla Beta v1.1. Read `AGENTS.md`, `docs/BETA_COMPLETION_MASTER_PLAN.md`, `docs/BETA_OPEN_DECISIONS.md`, and the two v1.1 source documents completely. Execute exactly one ticket at a time, beginning with R-01. Preserve all existing work. Do not add packages or invent business rules. Write tests alongside every change. Do not move to the next ticket until the current ticket's acceptance criteria and relevant phase gate pass. For each ticket, report files changed, tests run with results, requirement IDs closed, assumptions, and remaining blockers. Critical stock, money, tenant, pricing, and authorization work must be implemented or integrated by GLM-5.2 and independently reviewed before its phase gate.

