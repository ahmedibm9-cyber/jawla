# ZATCA Phase 1 QR (Saudi Arabia only)

For `companies.country = 'SA'` and `zatca_enabled = true`, the invoice PDF
carries a QR code encoding the following five fields as Base64-encoded
TLV (Tag-Length-Value):

1. Seller name (UTF-8).
2. VAT registration number.
3. Invoice timestamp (ISO 8601, timezone-aware).
4. Invoice total including VAT (string).
5. VAT amount (string).

Encoding: for each field, one byte tag (1..5), one byte length, then value
bytes; concatenate; Base64-encode the whole. Implement in
`App\Services\InvoiceQrService` with a `ZatcaPhase1Strategy` implementing
`App\Services\Contracts\QrStrategy`. Unit-test the TLV bytes against a
known-good vector before wiring to the PDF.

Egyptian invoices use a simple strategy: QR encodes `invoice_number|total`
as UTF-8 text.
