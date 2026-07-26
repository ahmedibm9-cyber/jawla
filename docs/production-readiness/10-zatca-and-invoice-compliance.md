# ZATCA and Invoice Compliance

## Capability verdict

| Capability | Judgment | Defensible claim |
|---|---|---|
| Server-side numbering | Partial | A server sequence service exists; sequential/collision/legal behavior is not proven |
| Saudi Phase 1 QR | Partial | Five-tag TLV QR implementation exists; official validation evidence was not obtained |
| Saudi Phase 2 | Fail/unsupported | No production Phase 2 capability |
| Egyptian ETA | Fail/scaffold | Abstractions and a builder exist; production signing/schema/submission safety are incomplete |
| PDF authorization/escaping | Partial/positive | Inspected rep routes and interpolation are protected |
| Issued document immutability | Fail | PDF is generated from mutable master data and lacks issuance snapshot/hash |
| Credits/refunds/paid returns | Fail | Accounting and compliant note lifecycle are not implemented |

## ZATCA Phase 2

`ZatcaPhase2Strategy` inherits the same five-tag Phase 1 TLV and changes a label. The audit found no XML invoice signing, invoice/document hash chain, previous invoice hash, cryptographic stamp, onboarding/certificate lifecycle, clearance/reporting, rejection handling, or official conformance evidence.

The product must not claim Saudi Phase 2 readiness. The narrow possible claim is “Phase 1-style five-tag QR generation,” subject to official vector validation and legal review.

## Egyptian ETA

The ETA builder explicitly states exact schema validation remains outstanding. Item/unit/tax fields are incomplete or hardcoded, the configured signer is unsigned, and remote submission lacks a durable idempotent outbox. A remote acceptance followed by local rollback/timeout can create ambiguous duplicate submission.

Do not enable or market ETA production use before official preproduction certification and an exercised retry/rejection workflow.

## Numbering

Row-locking a company/year sequence is a useful design. Remaining risks:

- random suffix makes displayed numbers non-monotonic;
- companies sharing abbreviations interact with a global unique number;
- first-use concurrency and transaction-abort handling are untested;
- rollback/no-gap and year-boundary rules lack accounting/tax approval;
- invoice/return opaque public identifiers should be separated from legal sequence semantics.

## Document immutability

PDF/QR material should be bound to issuance. Current on-demand generation uses current company/customer/product data; a delayed first generation or cache loss may alter history. Currency is hardcoded as EGP in PDF rendering, including Saudi contexts. No issued binary/canonical hash is retained.

## Required external decisions and evidence

1. Jurisdictions, taxpayer entities, invoice types, currencies, tax-inclusive/exclusive rules, rounding, credit/debit notes, and archival period.
2. Official ZATCA/ETA provider and credential/certificate ownership.
3. Authority certification/preproduction test results and signed sample documents.
4. Immutable issuance snapshot and response/archive policy.
5. Rejection, correction, cancellation, refund, paid-return, clock failure, certificate rotation, timeout, and duplicate-submission procedures.
6. Legal/accounting written approval. Repository code or passing unit tests cannot substitute for authority acceptance.

