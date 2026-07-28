# Fix Story: PR-002 — Server-Validated Stock Import

**Epic:** Inventory Integrity
**Story ID:** FIX-PR-002
**Priority:** P0 — Launch Blocker
**Investigation Reference:** `bmad-output/investigation-production-readiness-2026-07-28.md`

---

## Story

**As a** warehouse manager
**I want** stock imports to be validated server-side at confirmation time
**So that** tampered preview data cannot corrupt inventory

---

## Acceptance Criteria

1. **Server-side import token**
   - After CSV upload and preview, generate a signed token containing:
     - Company ID (from session)
     - Warehouse ID
     - Product IDs and expected quantities
     - Timestamp (expires in 30 minutes)
   - Token stored server-side (Redis/DB)

2. **Confirmation validates against token**
   - On confirm, server revalidates:
     - Company matches current context
     - Warehouse exists and belongs to company
     - Products exist and belong to company
     - Quantities are non-negative
     - Token is fresh (not expired)
   - If any check fails, reject with clear error

3. **Atomic stock update**
   - All rows processed in single transaction
   - Partial success rolls back everything
   - StockService::move() used for each row

4. **Test coverage**
   - Tampered product ID test → rejection
   - Tampered quantity test → rejection
   - Cross-company ID test → rejection
   - Expired token test → rejection
   - Partial failure test → full rollback

---

## Suspected Files

| File | Change |
|------|--------|
| `app/Livewire/StockImport.php` | Generate and store import token |
| `app/Services/StockImportService.php` | Validate token on confirm |
| `app/Services/StockService.php` | Ensure company validation in move() |
| `app/Models/StockImportToken.php` | New model for token storage |
| `database/migrations/xxxx_create_stock_import_tokens.php` | Token table |

---

## Verification Steps

1. **Tampered ID test:** Upload CSV → modify product ID in Livewire payload → confirm → expect rejection
2. **Tampered quantity test:** Upload CSV → modify quantity in Livewire payload → confirm → expect rejection
3. **Cross-company test:** Upload CSV → modify warehouse ID to different company → confirm → expect rejection
4. **Expired token test:** Upload CSV → wait 31 minutes → confirm → expect rejection
5. **Partial failure test:** Upload CSV with 1 invalid row → confirm → expect full rollback

---

## Implementation Notes

- **Approach:** Signed token with server-side validation, revalidate all data at confirm time
- **Risk:** Breaking existing stock import flow during transition
- **Mitigation:** Feature flag for new validation, backward-compatible for existing imports

---

## Definition of Done

- [ ] `make verify` passes
- [ ] Import token generated and stored
- [ ] Confirmation validates against token
- [ ] Tampered data rejected with clear error
- [ ] Expired token rejected
- [ ] Partial failure rolls back everything
- [ ] Test coverage for all tampered/expired/cross-company scenarios
