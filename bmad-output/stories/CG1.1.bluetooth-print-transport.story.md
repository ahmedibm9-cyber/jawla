# Story CG1.1 -- Bluetooth Print Transport

**Status:** ready-for-dev
**Epic:** CG1 -- Portable Field Printing
**Estimated effort:** Medium (~2 days)
**Blocked by:** none
**Labels:** printing, bluetooth, mobile, p1

---

## Story

**As a** sales rep  
**I want** the app to detect and pair with supported Bluetooth thermal printers  
**So that** I can print invoices and receipts immediately in the field.

---

## Acceptance Criteria

- Detect Web Bluetooth support and show a Print action only when supported.
- Pairing stores a rep-scoped preferred printer profile on-device.
- If Bluetooth is unsupported or pairing fails, the app falls back to PDF/share without breaking the flow.
- Printer connection state is visible to the rep before printing.
- No printing path bypasses authorization for the underlying document.

---

## Technical Details

- Primary surfaces: rep success screens after invoice/payment creation.
- Browser target: Android Chrome first.
- Use ESC/POS-capable profile abstraction so multiple printer widths can be added later.

---

## Verification Steps

1. Pair a supported printer from a rep device.
2. Print from invoice and payment success flows.
3. Verify unsupported browser shows fallback path only.
