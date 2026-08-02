# Egypt ETA E-Invoicing — Full Integration Plan

> **Status:** Not started — requires ETA sandbox credentials + development
> **Priority:** Go-live blocker (B2 from production readiness review)
> **Owner:** Compliance + Development

---

## Current State

- `app/Services/EgyptQrStrategy.php` — generates simple `INV-001|1234.56` format
- `app/Services/InvoiceQrService.php` — routes to EgyptQrStrategy for Egypt/Saudi
- **NOT compliant** with Egypt's ETA (Egyptian Tax Authority) requirements

## ETA Requirements (Simplified)

Egypt's e-invoicing system requires:

1. **QR Code (Data Matrix)** containing:
   - UUID (Universally Unique Identifier)
   - Seller name + tax registration number
   - Invoice timestamp (ISO 8601)
   - Invoice total (including tax)
   - Tax amounts (VAT)
   - Digital signature / cryptographic stamp

2. **ETA API Integration:**
   - CSID (Company Serial ID) — unique identifier from ETA
   - Cryptographic key pair (RSA 2048-bit)
   - Digital signature on each invoice
   - Submission to ETA portal (or sandbox for testing)

3. **Ongoing Compliance:**
   - Handle ETA API updates and schema changes
   - Maintain cryptographic keys securely
   - Audit trail for all submitted invoices

## Implementation Steps

### Phase 1: ETA Sandbox Setup (1-2 days)

1. [ ] Register for ETA sandbox access (requires Egyptian company TIN)
2. [ ] Obtain CSID from ETA
3. [ ] Generate RSA 2048-bit key pair
4. [ ] Store keys securely (Railway encrypted env vars or Vault)
5. [ ] Test sandbox API connectivity

### Phase 2: QR Code Format (1-2 days)

1. [ ] Implement ETA-compliant QR code format (Data Matrix)
2. [ ] Include all required fields (UUID, seller info, tax amounts, timestamp)
3. [ ] Generate base64-encoded QR code for invoice PDF
4. [ ] Test with ETA sandbox validators

### Phase 3: Digital Signature (2-3 days)

1. [ ] Implement RSA-PKCS#1 v1.5 signing
2. [ ] Sign invoice data before QR generation
3. [ ] Verify signature format matches ETA requirements
4. [ ] Test signature verification in sandbox

### Phase 4: API Integration (3-5 days)

1. [ ] Implement ETA API client (submit invoice, check status)
2. [ ] Handle API responses (accepted, rejected, pending)
3. [ ] Implement retry logic for transient failures
4. [ ] Store submission status in `invoices` table (new columns)
5. [ ] Add admin UI for ETA submission status

### Phase 5: Production Readiness (1-2 days)

1. [ ] Switch from sandbox to production ETA endpoints
2. [ ] Rotate cryptographic keys for production
3. [ ] Configure monitoring for ETA API failures
4. [ ] Document operational procedures
5. [ ] Train support team on ETA compliance

## Estimated Total: 8-14 days

## Dependencies

- **ETA sandbox credentials** (user must obtain from Egyptian Tax Authority)
- **Company TIN** (Tax Identification Number) for registration
- **Cryptographic key management** (secure storage solution)

## Risk Factors

1. **ETA API changes** — schema updates may break integration
2. **Key rotation** — requires secure key management process
3. **Audit requirements** — must maintain submission logs for 5+ years
4. **Performance** — each invoice requires API call (may need queuing)

## Alternative: Proforma-Only Approach

If full ETA integration is deferred:

1. Mark all invoices as "Proforma" (not tax invoices)
2. Add legal disclaimer: "This is a proforma invoice, not a tax invoice"
3. Buyers handle their own tax filing
4. **Legal risk:** may not be acceptable for B2B sales in Egypt

## Decision Required

Before proceeding, confirm:

1. [ ] Do you have an Egyptian company TIN for ETA registration?
2. [ ] Can you obtain ETA sandbox credentials?
3. [ ] Is the 8-14 day timeline acceptable?
4. [ ] Should we implement proforma-only as interim solution?
