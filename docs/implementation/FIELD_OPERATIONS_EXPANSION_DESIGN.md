# Field operations expansion design

**Status:** approved implementation design  
**Product model:** licensed, self-hosted deployment operated and hosted by each client

## Scope decision

Jawla is not a centrally operated SaaS. Each installation serves one licensed
client and may contain several companies, legal entities, branches, regions,
areas, teams, warehouses, and representatives. Cross-customer platform billing,
tenant support impersonation, and a vendor-wide tenant console are out of scope.

The existing representative PWA remains the supported client for this work.
Native iOS/Android background location is a separate packaging decision because
it requires a mobile runtime and platform-specific permissions. This expansion
must not claim reliable tracking after the browser is suspended or terminated.

## Design alternatives

### Approval workflows

1. **Generic workflow builder:** flexible, but introduces a rule language,
   versioning, and unsafe configuration before Jawla has stable workflow needs.
2. **Resource-specific actions only:** simple initially, but duplicates audit,
   authorization, rejection, and sequencing rules in every UI.
3. **Shared approval ledger with explicit domain services:** selected. The
   ledger records steps and decisions; task, order, collection, return, and
   stock services own their state transitions and side effects.

### Organization hierarchy

1. Separate tables for branches, regions, areas, and teams.
2. A company-scoped `organization_units` tree with a constrained unit type.
3. Reuse warehouses and free-text route regions.

Option 2 is selected. It supports the required hierarchy without duplicating
tree logic. Warehouses remain stock locations and are not organizational units.

### Customer structure

1. Add more nullable columns to `customers`.
2. Add company-scoped outlets, contacts, locations, and dated assignments.

Option 2 is selected because one customer can have many of each and assignment
history must remain auditable.

## State machines

### Task

`draft -> assigned -> accepted -> in_progress -> submitted -> approved`

Alternative transitions:

- `submitted -> changes_requested -> in_progress`
- `submitted -> rejected -> in_progress`
- `approved -> reopened -> in_progress`
- `draft|assigned -> cancelled`

### Sales order

The implemented first lifecycle is `draft -> submitted -> approved|rejected`.
The server resolves every line from the active customer/product pricing rules
and rejects stale or tampered browser/offline prices. Approval freezes the commercial order snapshot. Invoicing and stock movement
remain separate transactions; approving an order does not create an invoice or
change stock.

### Collection

`pending_review -> supervisor_reviewed -> finance_reviewed -> reconciled`, with
`rejected` available from either review step.

Submission atomically requires owned, unattached receipt/photo evidence. The
supervisor and finance reviewer are distinct approval actors. Only the explicit
finance reconciliation transition posts through `PaymentService`, producing the
payment, cash-box movement, and customer/invoice allocation. Rejection produces
no financial movement. Corrections to reconciled payments use the existing
controlled payment reversal path.

### Return

`pending_approval -> approved|rejected`; an approved request can then become
`received`.

Each request line snapshots unit price, prorated tax, net, and gross value while
locking and reserving the remaining invoice quantity. Warehouse receipt requires
an authorized main-warehouse destination and, for damaged items, an explicit
quarantine warehouse. The existing transactional return service revalidates the
approved snapshot before it changes stock, credit notes, or customer balances.

## Service pseudocode

### Submit approval work

Purpose: create an auditable approval sequence without applying a domain side
effect before all required reviewers decide.

1. Confirm the actor and subject belong to the active company.
2. Confirm the domain service says the subject can be submitted.
3. Lock the subject and reject duplicate active submissions.
4. Create the approval request and its ordered steps in one transaction.
5. Move the subject to its submitted or under-review state.
6. After commit, notify the first approver.

### Record an approval decision

1. Lock the approval request and current pending step.
2. Confirm the actor is the assigned approver or has the required permission.
3. Require a reason for rejection or changes requested.
4. Record the immutable decision, actor, time, and reason.
5. If approved and another step exists, activate the next step.
6. If every step is approved, ask the domain service to apply its final
   transition inside the same transaction.
7. If rejected or changes are requested, ask the domain service to move to the
   matching state without applying financial or stock effects.
8. After commit, notify the submitter and next approver where applicable.

Failure cases: wrong company, stale state, duplicate decision, unauthorized
approver, missing reason, missing workflow step, or a domain transition that no
longer applies. Each fails the transaction and preserves the previous state.

### Assign a representative

1. Confirm the customer, representative, and organization unit share a company.
2. Lock the customer's active assignment.
3. End the previous assignment at the new assignment's start time.
4. Create the new assignment with purpose, priority, and allowed transactions.
5. Record an activity containing previous and new assignment identifiers.

### Verify a self-hosted license

1. Paste the vendor-issued JSON document and detached Base64 signature into the
   installation-license administration page.
2. Verify the exact JSON bytes with the vendor public key in
   `JAWLA_LICENSE_PUBLIC_KEY`.
3. Production requires `JAWLA_INSTALLATION_ID`; require an exact signed claim
   match.
4. Store the signed document, its SHA-256 hash, validity window, edition,
   feature claims, and user limit; never store a reusable plaintext license key.
5. Re-verification derives every enforceable column from the signed payload,
   preventing database edits from expanding claims.
6. Authenticated admin/PWA traffic and field-write services fail closed when the
   license is missing, invalid, outside its validity window, over its active-user
   limit, or missing the requested feature. The separate recovery route remains
   available to users with `licenses.manage`.

## Operational configuration

- `JAWLA_PUSH_GATEWAY_URL` points to a client-operated Web Push gateway that
  accepts a browser subscription and notification payload. The optional
  `JAWLA_PUSH_GATEWAY_TOKEN` authenticates Jawla to that gateway. With no gateway
  configured, database/in-app notifications continue to work.
- Webhook endpoints are configured per company in the admin panel. Domain
  transactions write pending outbox rows and never perform network I/O. The
  minute scheduler leases due rows, resolves all A/AAAA records, rejects any
  non-public address, disables redirects, pins the validated address while
  retaining TLS hostname verification, and sends `X-Jawla-Event` plus an
  `X-Jawla-Signature: sha256=...` HMAC header. Failed attempts use bounded
  exponential retries up to five attempts. Signing secrets are generated from
  32 random bytes and audited when rotated.
- `JAWLA_LICENSE_PUBLIC_KEY` is the vendor verification key. Multiline PEM keys
  may use escaped `\\n`. `JAWLA_INSTALLATION_ID` binds licenses to one client
  installation. `php artisan app:verify-license` is the deployment and
  monitoring gate; the scheduler runs it daily and returns a failing exit code
  for an invalid, expired, mismatched, or over-limit license.
- The reports page authorizes visits, sales, and finance domains independently,
  rejects unknown tabs, neutralizes spreadsheet-formula prefixes in every string
  cell, and streams CSV exports in 500-row chunks.
- Stock-import uploads are read through the configured filesystem disk. Remote
  objects are streamed to short-lived local files for parsing and checksummed
  again from the source disk at confirmation.

## Migration order

1. Approval ledger and expanded task lifecycle.
2. Organization units, user assignments, and registered devices.
3. Customer outlets, contacts, locations, and dated assignments.
4. Sales orders and order lines.
5. Collection evidence/review metadata and staged return processing.
6. Webhook endpoints/deliveries and local license records.

All new tables are company-scoped. Financial and stock effects remain inside
transactions and continue through the existing finance and stock services.
