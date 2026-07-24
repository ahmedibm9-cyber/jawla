# Story CG4.1 -- Public API Foundation

**Status:** ready-for-dev
**Epic:** CG4 -- API + ERP Ecosystem
**Estimated effort:** Large (~1-2 weeks)
**Blocked by:** none
**Labels:** api, integration, platform, p2

---

## Story

**As a** partner or customer IT team  
**I want** a documented, stable API for Jawla core data  
**So that** I can integrate master data and operational events without custom DB access.

---

## Acceptance Criteria

- Versioned API surface exists for core read models (customers, products, stock, invoices, payments).
- Auth model is explicit and tenant-safe.
- API schema is documented (OpenAPI or equivalent).
- Error format is stable and documented.
- Rate limits and audit logging apply.
