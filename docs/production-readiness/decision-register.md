# Production Remediation Decision Register

The approved decisions in `Jawla_Codex_Production_Remediation_Prompt.md` govern implementation. This register records the product rules that must not be silently changed.

## Deployment model

- Private client-hosted installation; one installation may contain several companies in the same client group.
- Evaluation uses a separate explicit demo database and permanent bilingual demo markings.
- Production uses a separate clean database and one-time secure bootstrap.
- Demo transactions and identities never move into production.

## Canonical roles

- `sales_rep`
- `sales_manager`
- `hr_admin`
- `warehouse_keeper`
- `system_viewer`

Setup administrators may hold multiple canonical roles. Broad legacy bypasses are not retained. All admin users require MFA; named dangerous actions require step-up authentication.

## Financial rules

- Server-authoritative effective pricing; no rep price entry.
- Draft invoices have no official number or postings.
- Issuance atomically assigns the legal number and posts stock/receivable.
- Issued content is immutable.
- Amendment uses linked credit plus replacement.
- Overpayment is explicit unallocated customer credit.
- Returns reference original invoice lines and preserve product/batch/price/tax/currency.
- Paid returns default to customer credit; cash refund requires manager approval and sufficient cash.
- Committed corrections are immutable compensating transactions, never rep undo or deletion.

## Inventory rules

- Exact quantities, default scale three decimal places.
- One locked stock mutation service.
- Batch-tracked products require eligible same-company batches; FEFO applies.
- Expired/damaged goods are not sellable and move to quarantine.
- Reconciliation is a versioned count/variance/approval workflow with an immutable delta.
- Transfers use explicit in-transit state, partial receipt and immutable exception records while conserving total stock.

## Offline rules

- Core field day is supported after online setup.
- One active registered device per rep per shift.
- Stable business-intent key before confirmation.
- Durable outbox commit precedes success UI.
- Same key/different payload is a conflict.
- Financial intents cannot be discarded locally.
- Conflicts are resolved through an audited manager workflow.
- Pending work survives restart, network loss, updates, logout/account-switch policy, and storage pressure.

## Compliance and documents

- Production may claim standard server-generated invoices and verified ZATCA Phase 1-style TLV QR only.
- ZATCA Phase 2 and Egyptian ETA production claims/controls remain disabled without external certification.
- Legal number is atomic per company/document type/year and separate from public UUID.
- Money uses exact decimal arithmetic with two currency decimals and round-half-up by default.
- Issued artifacts use immutable snapshots, private storage, content hashes, and versioned generators.

## Privacy, accessibility, operations

- GPS is visible and active only during an open shift; periodic default retention 90 days and visit proof two years, configurable with local legal review.
- Files are private and safely re-encoded/validated.
- Core journeys must meet WCAG 2.2 AA in Arabic RTL and English LTR.
- Default RPO is one hour and RTO four hours, subject to platform capability and acceptance.
- Initial target is 75 concurrent active users with the latency/load goals stated in the execution prompt.
