---
phase: field-operations-expansion
reviewed: 2026-08-03T04:07:40Z
depth: deep
files_reviewed: 93
files_reviewed_list:
  - .env.example
  - app/Console/Commands/VerifyInstallationLicense.php
  - app/Filament/Pages/ReportsPage.php
  - app/Filament/Pages/StockImport.php
  - app/Filament/Resources/CollectionSubmissionResource.php
  - app/Filament/Resources/CollectionSubmissionResource/Pages/ListCollectionSubmissions.php
  - app/Filament/Resources/InstallationLicenseResource.php
  - app/Filament/Resources/InstallationLicenseResource/Pages/CreateInstallationLicense.php
  - app/Filament/Resources/InstallationLicenseResource/Pages/ListInstallationLicenses.php
  - app/Filament/Resources/ReturnRequestResource.php
  - app/Filament/Resources/ReturnRequestResource/Pages/ListReturnRequests.php
  - app/Filament/Resources/SalesOrderResource.php
  - app/Filament/Resources/SalesOrderResource/Pages/ListSalesOrders.php
  - app/Filament/Resources/TaskResource.php
  - app/Filament/Resources/WebhookDeliveryResource.php
  - app/Filament/Resources/WebhookDeliveryResource/Pages/ListWebhookDeliveries.php
  - app/Filament/Resources/WebhookEndpointResource.php
  - app/Filament/Resources/WebhookEndpointResource/Pages/CreateWebhookEndpoint.php
  - app/Filament/Resources/WebhookEndpointResource/Pages/EditWebhookEndpoint.php
  - app/Filament/Resources/WebhookEndpointResource/Pages/ListWebhookEndpoints.php
  - app/Http/Controllers/App/PdfController.php
  - app/Http/Controllers/App/SyncController.php
  - app/Livewire/App/CollectPayment.php
  - app/Livewire/App/CreateSalesOrder.php
  - app/Livewire/App/LogReturn.php
  - app/Livewire/App/Orders.php
  - app/Livewire/Concerns/CapturesPhotos.php
  - app/Models/CollectionSubmission.php
  - app/Models/CustomerAssignment.php
  - app/Models/CustomerContact.php
  - app/Models/CustomerLocation.php
  - app/Models/CustomerOutlet.php
  - app/Models/InstallationLicense.php
  - app/Models/ReturnRequest.php
  - app/Models/ReturnRequestItem.php
  - app/Models/SalesOrder.php
  - app/Models/SalesOrderItem.php
  - app/Models/WebhookDelivery.php
  - app/Models/WebhookEndpoint.php
  - app/Notifications/Channels/PushChannel.php
  - app/Notifications/RepNotification.php
  - app/Policies/CollectionSubmissionPolicy.php
  - app/Policies/DevicePolicy.php
  - app/Policies/OrganizationUnitPolicy.php
  - app/Policies/ReturnRequestPolicy.php
  - app/Policies/SalesOrderPolicy.php
  - app/Providers/AppServiceProvider.php
  - app/Providers/SyncServiceProvider.php
  - app/Rules/SafeWebhookUrl.php
  - app/Services/CollectionSubmissionService.php
  - app/Services/Contracts/PushGateway.php
  - app/Services/HttpPushGateway.php
  - app/Services/InvoiceService.php
  - app/Services/LicenseService.php
  - app/Services/NumberSequenceService.php
  - app/Services/OrganizationScopeService.php
  - app/Services/PdfEngine.php
  - app/Services/PdfService.php
  - app/Services/PricingService.php
  - app/Services/PushService.php
  - app/Services/ReturnRequestService.php
  - app/Services/ReturnService.php
  - app/Services/SalesOrderService.php
  - app/Services/StockImportService.php
  - app/Services/Sync/Handlers/CollectionSubmissionSyncHandler.php
  - app/Services/Sync/Handlers/ReturnRequestSyncHandler.php
  - app/Services/Sync/SyncService.php
  - app/Services/TaskService.php
  - app/Services/VisitReportService.php
  - app/Services/WebhookService.php
  - app/Services/WorkflowApproverResolver.php
  - bootstrap/app.php
  - config/filesystems.php
  - config/jawla.php
  - database/migrations/2026_07_20_210000_create_sync_receipts_table.php
  - database/migrations/2026_08_03_150000_create_sales_order_collection_and_return_workflows.php
  - database/migrations/2026_08_03_160000_create_integrations_and_installation_license.php
  - database/seeders/RoleSeeder.php
  - lang/ar/app.php
  - lang/en/app.php
  - reset_test_db.sql
  - resources/views/filament/pages/reports-page.blade.php
  - resources/views/livewire/app/collect-payment.blade.php
  - resources/views/livewire/app/create-sales-order.blade.php
  - resources/views/livewire/app/log-return.blade.php
  - resources/views/livewire/app/orders.blade.php
  - routes/web.php
  - tests/Feature/CommercialWorkflowTest.php
  - tests/Feature/CustomerStructureTest.php
  - tests/Feature/IntegrationAndLicenseTest.php
  - tests/Feature/ReportsPageTest.php
  - tests/Feature/Finance/AuthoritativePricingTest.php
  - tests/TestCase.php
findings:
  critical: 14
  warning: 8
  info: 0
  total: 22
status: issues_found
---

# Field Operations Expansion: Code Review Report

**Reviewed:** 2026-08-03T04:07:40Z  
**Depth:** deep  
**Files Reviewed:** 93  
**Status:** issues_found

## Summary

The implementation is not ready to ship. The review found fourteen release-blocking correctness or security defects and eight robustness gaps. The highest-risk failures allow client-supplied sales pricing, post collections without required evidence or finance reconciliation, leave commercial licensing unenforced, permit webhook SSRF, and misstate or misplace returned stock and credits. The current tests cover happy paths but do not exercise these adversarial boundaries.

## Critical Issues

### CR-01: Sales orders trust a client-controlled unit price

**Classification:** BLOCKER  
**File:** `C:/projects/jawla/app/Livewire/App/CreateSalesOrder.php:53-67`; `C:/projects/jawla/app/Services/SalesOrderService.php:47-65`  
**Issue:** The public Livewire state accepts any non-negative `items.*.unit_price`, and the service persists and totals that value verbatim. A representative can tamper with the Livewire request to submit a zero or arbitrary price, bypassing the customer's assigned price list and the application's pricing rules. This differs from `InvoiceService`, which resolves the server-authoritative price and rejects stale/tampered input at lines 94-104.

**Fix:** Inject the pricing contract into `SalesOrderService`, resolve the effective price from company, customer, product, and quantity, and either overwrite the submitted price or reject a mismatch. Route authorized discounts/overrides through a separate approval policy; never accept the browser value as authoritative. Add tests for zero-price tampering, customer price overrides, stale prices, and cross-company product IDs.

### CR-02: A collection can be submitted and posted without receipt evidence

**Classification:** BLOCKER  
**File:** `C:/projects/jawla/app/Livewire/App/CollectPayment.php:59-89`; `C:/projects/jawla/app/Livewire/Concerns/CapturesPhotos.php:10-16`; `C:/projects/jawla/app/Filament/Resources/CollectionSubmissionResource.php:47-69`  
**Issue:** Collection validation does not require a receipt/photo. The collection is committed before `attachPhotos()` runs, that helper silently returns when no photos exist, and the documented offline path explicitly skips photos. The reviewer page only displays a numeric `photos_count`; it has no detail page or evidence preview before the financial action. A payment can therefore be approved and posted with no reviewable proof, and an attachment failure leaves a valid pending submission without evidence.

**Fix:** Make evidence an atomic prerequisite of collection submission (or require a recorded, policy-authorized exception). Persist offline evidence in a durable upload outbox and make the collection operation depend on successful upload/attachment. Add a reviewer detail view that renders the evidence safely, and refuse approval when the evidence invariant is not met. Test online, offline, missing-upload, foreign-photo, and attachment-failure cases.

### CR-03: Collection approval skips the required finance reconciliation stage

**Classification:** BLOCKER  
**File:** `C:/projects/jawla/app/Services/CollectionSubmissionService.php:51-76`; `C:/projects/jawla/app/Services/WorkflowApproverResolver.php:9-26`; `C:/projects/jawla/database/seeders/RoleSeeder.php:171-203`  
**Issue:** Submission creates one approval step for the first company sales manager/admin. That single actor immediately invokes `PaymentService` and changes the collection to `approved`. There is no independent finance review/reconciliation state, no finance approver, and no `collections.review` permission for the accounts role. This collapses the specified supervisor-review and finance-reconciliation controls into one posting action.

**Fix:** Model supervisor review and finance reconciliation as separate ordered approval steps with separate permissions and immutable audit timestamps. Only call `PaymentService` in the finance reconciliation transition, and expose `submitted`, `supervisor_reviewed`, `finance_reviewed`, `reconciled`, and `rejected` states. Add tests proving one actor cannot satisfy both controls and that posting occurs only once at reconciliation.

### CR-04: The commercial installation license is never enforced on application traffic

**Classification:** BLOCKER  
**File:** `C:/projects/jawla/app/Services/LicenseService.php:59-76`; `C:/projects/jawla/app/Console/Commands/VerifyInstallationLicense.php:14-25`; `C:/projects/jawla/bootstrap/app.php:41-49`  
**Issue:** `assertValid()` is called only by the console verification command and its test. The scheduled command merely exits with failure; no middleware, gate, user-activation path, feature gate, or write service reacts to that result. An installation with no license, an expired license, or too many active users remains fully usable. Edition and feature claims are also never enforced.

**Fix:** Add a fail-closed runtime license guard to authenticated admin/app traffic and service-layer write boundaries, with narrowly scoped exemptions for license installation and recovery. Enforce active-user limits during creation/reactivation and feature/edition claims where functionality is entered. Require an installation identifier in production, provide a safe grace/recovery policy, and test actual routes and mutations under missing, expired, not-yet-valid, over-limit, and disabled-feature licenses.

### CR-05: Signed license claims can be changed in the database without invalidating the license

**Classification:** BLOCKER  
**File:** `C:/projects/jawla/app/Services/LicenseService.php:44-54`; `C:/projects/jawla/app/Services/LicenseService.php:64-74`  
**Issue:** Verification proves the signature over `raw_document` but updates only status and verification time. Enforcement then reads mutable columns such as `max_users` rather than the verified payload. Changing `max_users` to `NULL` or a larger number leaves the signature valid and disables the limit. The same divergence is possible for edition, features, installation ID, licensee, and validity columns.

**Fix:** Treat the verified document as the sole source of truth. On every verification, compare every persisted claim against the signed payload and fail closed on divergence, or atomically overwrite all derived columns from the verified payload before enforcing them. Validate claim types and ranges as well. Add tamper tests for every enforcement-relevant column.

### CR-06: Webhook URL validation is vulnerable to DNS rebinding and alternate local-address forms

**Classification:** BLOCKER  
**File:** `C:/projects/jawla/app/Rules/SafeWebhookUrl.php:10-30`; `C:/projects/jawla/app/Services/WebhookService.php:39-56`  
**Issue:** The rule rejects only literal private/reserved IPs and a few local suffixes. It never resolves hostnames. An attacker with endpoint-management permission can use a hostname resolving to loopback, RFC1918, link-local/cloud-metadata, or an IPv6 local address, and can change DNS after validation. The service performs the request later without connect-time validation or IP pinning. Redirect handling is not constrained either.

**Fix:** Enforce an egress allowlist where possible. Otherwise canonicalize the host, resolve every A/AAAA record immediately before each connection, reject if any address is not globally routable, disable redirects or validate every hop, and pin the validated IP while preserving TLS hostname/SNI verification. Put this check in `WebhookService`, not only the Filament form. Test private DNS, DNS rebinding, IPv6, `127.1`-style forms, metadata addresses, and redirects.

### CR-07: CSV exports permit spreadsheet formula injection

**Classification:** BLOCKER  
**File:** `C:/projects/jawla/app/Filament/Pages/ReportsPage.php:125-172`  
**Issue:** Customer names, representative names, product names, visit summaries, and document numbers are written directly to CSV. Spreadsheet programs interpret cells beginning with `=`, `+`, `-`, `@`, tabs, or related control characters as formulas. A representative or customer-data editor can store a malicious value that executes when a manager opens the exported file.

**Fix:** Centralize CSV-cell neutralization and prefix dangerous leading characters (including dangerous characters after leading whitespace/control characters) with an apostrophe before every `fputcsv` call. Apply it to all string cells, not only summaries. Add export tests containing formula payloads in every user-controlled field.

### CR-08: Production stock import passes an S3 object key to local-file APIs

**Classification:** BLOCKER  
**File:** `C:/projects/jawla/config/filesystems.php:28-36`; `C:/projects/jawla/app/Filament/Pages/StockImport.php:65-93`; `C:/projects/jawla/app/Services/StockImportService.php:27-32`  
**Issue:** Production defaults `storage_disk` to S3. `StockImport` calls the adapter's `path()` and passes the returned prefixed key to `hash_file()` and `SimpleExcelReader`, both of which require a readable local filesystem path. An S3 adapter does not download the object, so the production import preview fails before staging.

**Fix:** Read the object through the configured disk and stream it to a validated temporary local file, then checksum/parse it and clean it up in `finally`; alternatively change the import service to consume a stream abstraction. Add a non-local filesystem test rather than relying only on `Storage::fake('local')` semantics.

### CR-09: Return approval displays a pre-tax value but receipt posts a tax-inclusive credit

**Classification:** BLOCKER  
**File:** `C:/projects/jawla/app/Services/ReturnRequestService.php:46-69`; `C:/projects/jawla/app/Services/ReturnService.php:147-170`; `C:/projects/jawla/app/Services/ReturnService.php:187-203`  
**Issue:** The request total is quantity times unit price only. At warehouse receipt, the return service adds prorated invoice tax and issues a tax-inclusive credit note. Reviewers therefore approve a lower financial value than the system ultimately credits. The committed happy-path test uses a taxed invoice but does not assert the request total, masking this discrepancy.

**Fix:** Snapshot unit price, prorated tax, net, and gross totals in each return-request item at submission, and display/approve the gross total that will be posted. Revalidate the immutable invoice snapshot at receipt and fail if it cannot produce the approved amount. Test fully paid, partially paid, and taxed returns with rounding boundaries.

### CR-10: “Warehouse receipt” puts sellable returns back into the representative's van

**Classification:** BLOCKER  
**File:** `C:/projects/jawla/app/Services/ReturnRequestService.php:105-134`; `C:/projects/jawla/app/Services/ReturnService.php:75-87`; `C:/projects/jawla/app/Services/ReturnService.php:175-184`  
**Issue:** `receive()` records a warehouse user but passes the original representative's user ID to `ReturnService`. The return service resolves that user's active van and increments sellable stock there. No receiving warehouse is selected or authorized. A warehouse keeper can physically receive goods while the ledger adds them to a vehicle, corrupting stock location and subsequent availability.

**Fix:** Separate the receipt actor from the stock destination in the service API. Require a destination warehouse authorized for the warehouse user/company, post sellable stock there and damaged stock to an explicit quarantine location, and persist the destination on the receipt/return. Test exact source/destination stock movements and cross-warehouse authorization.

### CR-11: New commercial resources ignore organization scope and route approvals to an arbitrary manager

**Classification:** BLOCKER  
**File:** `C:/projects/jawla/app/Policies/CollectionSubmissionPolicy.php:13-30`; `C:/projects/jawla/app/Policies/ReturnRequestPolicy.php:13-30`; `C:/projects/jawla/app/Policies/SalesOrderPolicy.php:13-30`; `C:/projects/jawla/app/Services/WorkflowApproverResolver.php:9-20`  
**Issue:** The policies enforce only company ownership. They do not apply the existing team/region/branch scope represented by `organization_units.view_scope`. The resolver simply chooses the first active sales manager in the company, ignoring the representative's supervisor and organization hierarchy. A scoped manager can list company-wide records, and submissions can be assigned across branches to an unrelated manager.

**Fix:** Apply `OrganizationScopeService` (or a common scoped-query/policy abstraction) to all resource queries and record actions. Resolve approvers from the submitter's active organization assignment and supervisory chain, with an explicit documented fallback. Add same-company/different-branch list, view, approve, reject, and receive denial tests.

### CR-12: Offline idempotency accepts an optional, client-forged payload hash

**Classification:** BLOCKER  
**File:** `C:/projects/jawla/app/Http/Controllers/App/SyncController.php:20-39`; `C:/projects/jawla/app/Services/Sync/SyncService.php:52-104`; `C:/projects/jawla/database/migrations/2026_07_20_210000_create_sync_receipts_table.php:15-23`  
**Issue:** `payloadHash` is optional, and the server stores it without computing or verifying it. Mismatch detection runs only when both the stored and incoming hashes are non-null. A client can omit the hash or reuse a forged hash while changing type/payload; the server returns the original result as a duplicate instead of rejecting the conflict. Receipt uniqueness is only `(company_id, idempotency_key)`, so a second user in the company who learns or collides with a key can receive another user's stored result and block their operation.

**Fix:** Compute a canonical server-side hash over protocol version, operation type, and payload using stable serialization; always persist and compare it. Treat any client hash only as an additional integrity check. Scope receipt uniqueness and lookup to company plus user (and, if device registration is authoritative, device), and never return another user's response. Test omitted/spoofed hashes, changed operation types, key reuse across users/devices, and concurrent submission.

### CR-13: The reports page bypasses the seeded report-domain permissions

**Classification:** BLOCKER  
**File:** `C:/projects/jawla/app/Filament/Pages/ReportsPage.php:29-32`; `C:/projects/jawla/app/Filament/Pages/ReportsPage.php:88-117`; `C:/projects/jawla/database/seeders/RoleSeeder.php:19-36`  
**Issue:** The page and export endpoint check only broad `reports.view`, then expose visits, quotations, proformas, invoice totals, and balances. The seeder defines separate `reports.sales`, `reports.visits`, `reports.financial`, and `reports.stock` permissions, but none is enforced. A role given generic page access can export financial invoice data without `reports.financial`.

**Fix:** Authorize each tab/query/export against its domain permission and reject unknown tab values instead of falling through to invoices. Hide unauthorized tabs and repeat authorization inside every computed query/export method. Add direct Livewire-action tests proving a user cannot set `tab` to access or export another domain.

### CR-14: Globally unique order numbers conflict with per-company numbering

**Classification:** BLOCKER  
**File:** `C:/projects/jawla/database/migrations/2026_08_03_150000_create_sales_order_collection_and_return_workflows.php:11-18`; `C:/projects/jawla/database/migrations/2026_08_03_150000_create_sales_order_collection_and_return_workflows.php:66-75`; `C:/projects/jawla/app/Services/NumberSequenceService.php:56-103`  
**Issue:** Sales-order and return-request numbers are globally unique, while counters are generated independently per company and the formatted value uses a nullable, non-unique company abbreviation. Two companies with the same abbreviation (or both falling back to `XX`) and the same yearly counter generate the same value; the second company's insert fails. This violates independent legal-entity numbering.

**Fix:** Replace the global unique constraints with composite uniques on `(company_id, order_number)` and `(company_id, request_number)`. If global uniqueness is a business requirement, instead enforce globally unique immutable company prefixes and remove the `XX` fallback. Add a test with two companies sharing/nulling the abbreviation.

## Warnings

### WR-01: Failed webhook deliveries are never retried automatically

**Classification:** WARNING  
**File:** `C:/projects/jawla/app/Services/WebhookService.php:58-73`; `C:/projects/jawla/bootstrap/app.php:41-49`  
**Issue:** Failures receive a `next_retry_at`, but no command, job, or scheduled task consumes due deliveries. Recovery exists only as a manual Filament action, so transient outages silently become permanent unless an administrator intervenes.

**Fix:** Add a scheduled worker that leases/locks due deliveries, calls `attempt()`, honors the five-attempt cap/backoff, and exposes exhausted failures to operations. Test transient failure, retry success, exhaustion, and overlapping workers.

### WR-02: Concurrent webhook attempts can send the same event and lose attempt accounting

**Classification:** WARNING  
**File:** `C:/projects/jawla/app/Services/WebhookService.php:39-42`; `C:/projects/jawla/app/Services/WebhookService.php:58-73`  
**Issue:** `attempt()` reloads the row without a lock or lease. Two manual/scheduled callers can both observe the same attempt count, both send, and both write `attempts + 1`, producing a duplicate delivery while losing one attempt in the counter.

**Fix:** Claim the delivery atomically using a transaction and row lock or a lease/state transition before network I/O. Keep a stable event ID for receiver idempotency, then finalize only the claimed attempt.

### WR-03: Workflow components expose raw exception messages to representatives

**Classification:** WARNING  
**File:** `C:/projects/jawla/app/Livewire/App/CreateSalesOrder.php:76-78`; `C:/projects/jawla/app/Livewire/App/CollectPayment.php:82-85`; `C:/projects/jawla/app/Livewire/App/LogReturn.php:77-80`  
**Issue:** Broad `Throwable` catches render internal exception messages directly into the UI. Database, storage, encryption, or integration errors can reveal implementation/schema details, and programming faults are misrepresented as user-correctable validation errors.

**Fix:** Catch expected domain/validation exceptions and map them to localized safe messages. Report unexpected exceptions server-side with a correlation ID and show a generic bilingual failure message to the user.

### WR-04: The uncommitted PostgreSQL reset macro cannot override Laravel's concrete method

**Classification:** WARNING  
**File:** `C:/projects/jawla/tests/TestCase.php:14-38`  
**Issue:** The macro is registered after `parent::setUp()`, after `RefreshDatabase` may already have performed its reset. More importantly, Laravel's PostgreSQL schema builder already defines a concrete `dropAllTables()` method; `Macroable` handles undefined calls and cannot replace that method. The claimed atomic `DROP SCHEMA` path therefore does not run, so the deadlock workaround is ineffective. This is unrelated to the documented Windows Playwright lifecycle limitation.

**Fix:** Configure an isolated per-process database/schema, or install a custom PostgreSQL builder/reset command before `parent::setUp()` and prove it is invoked. If interpolating a schema name, validate and quote the identifier. Add an integration assertion/spy around the actual reset SQL.

### WR-05: Sales orders have no offline sync path

**Classification:** WARNING  
**File:** `C:/projects/jawla/app/Livewire/App/CreateSalesOrder.php:51-86`; `C:/projects/jawla/app/Providers/SyncServiceProvider.php:1-60`  
**Issue:** The new collection and return workflows register offline sync handlers, but sales orders submit only through a live Livewire request and have no sales-order handler. Field representatives cannot create the specified order workflow while offline, nor can the server reject/reprice it safely after a price change.

**Fix:** Add an outbox-backed `sales_order` operation with server-authoritative repricing and a user-resolvable stale-price conflict. Test offline creation, reconnect after price-list change, duplicate replay, and approval after synchronization.

### WR-06: Pending return requests can over-reserve an invoice line

**Classification:** WARNING  
**File:** `C:/projects/jawla/app/Services/ReturnRequestService.php:48-56`; `C:/projects/jawla/app/Services/ReturnService.php:118-136`  
**Issue:** Submission checks only against original invoiced quantity. It does not subtract already received returns or quantities in other active pending/approved requests. Multiple requests can therefore be approved for more than was sold; the later warehouse receipt fails only after operational approval.

**Fix:** Lock invoice lines and calculate available return quantity as sold less received and active reserved quantities during submission/approval. Revalidate on receipt and release reservations on rejection/cancellation. Add concurrent and multi-request tests.

### WR-07: Webhook secrets can be trivially weak

**Classification:** WARNING  
**File:** `C:/projects/jawla/app/Filament/Resources/WebhookEndpointResource.php:47-50`  
**Issue:** The endpoint form requires a secret on creation but imposes no minimum length or entropy, so a one-character HMAC key is accepted and makes webhook authentication readily forgeable.

**Fix:** Generate at least 32 cryptographically random bytes by default, enforce a minimum-strength server-side rule, display the value only once, and provide audited rotation. Test short-secret rejection and rotation behavior.

### WR-08: Synchronous after-commit integrations can report failure after the domain write succeeded

**Classification:** WARNING  
**File:** `C:/projects/jawla/app/Services/SalesOrderService.php:78-89`; `C:/projects/jawla/app/Services/CollectionSubmissionService.php:61-83`; `C:/projects/jawla/app/Services/ReturnRequestService.php:109-141`  
**Issue:** Webhook dispatch performs database writes and HTTP calls synchronously inside `DB::afterCommit`. Failures outside the inner HTTP catch (for example endpoint decryption or JSON encoding) can bubble after the business transaction has committed, returning an error to the caller even though the order approval, payment, or return receipt already succeeded. A retry then encounters surprising already-completed state.

**Fix:** Persist an outbox event in the domain transaction and process integrations asynchronously. Keep post-commit delivery failure separate from the domain response, while logging/alerting it with an event ID. Test an integration exception after commit and verify the client receives the committed domain result exactly once.

---

_Reviewed: 2026-08-03T04:07:40Z_  
_Reviewer: Codex (gsd-code-reviewer)_  
_Depth: deep_
