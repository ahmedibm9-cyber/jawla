# ADR 0002: Skip Sales Orders — Proforma Invoice → Invoice Direct

**Status:** Accepted  
**Date:** 2026-07-12

## Context

Jawla is a field sales ERP for a plastics trading company. The sales workflow is:

1. Rep visits customer → negotiates price → creates **Proforma Invoice** (commitment)
2. Proforma is **converted to Invoice** (stock deducted, accounting entry created)

Standard ERP patterns (ERPNext, Odoo, SAP) insert a **Sales Order** step between quotation and invoice:

```
Quotation → Sales Order → Delivery Note → Sales Invoice
```

The client's existing workflow (Odoo + Excel) does NOT use sales orders — reps create proformas directly and convert to invoices.

## Decision

Jawla will NOT have a Sales Order table or Sales Order workflow. The flow is simply:

```
Price Quotation → Proforma Invoice → Invoice
```

Sales Orders are deferred to v2 if needed.

## Rationale

- **Field sales workflow doesn't need it** — A Sales Order is an internal commitment document that makes sense when a central sales desk processes orders. In field sales, the proforma (sent to the customer) IS the commitment document.
- **Simpler UI for reps** — Fewer steps, fewer screens, fewer statuses to track.
- **Client validated** — Current workflow works without it. Adding it would be speculative complexity.

## Consequences

### Positive

- Fewer tables (no sales_orders, sales_order_items)
- Shorter transaction chain for the rep — create proforma → convert → done
- Less state to manage (no partial fulfillment against SO, no SO→invoice reconciliation)

### Negative

- No intermediate "committed but not yet delivered" state between proforma and invoice — the proforma fills this role partially
- No partial fulfillment tracking against a customer commitment (a customer could order 10 tons, receive 3 now, 7 later — would need separate invoices or a workaround)
- Less granular audit trail for multi-shipment orders

### Mitigation

- The Proforma Invoice already captures the customer's commitment (products, quantities, prices)
- For multi-shipment orders: create one proforma → convert to multiple invoices (partial conversion) or use multiple proformas
- If partial fulfillment becomes critical, sales_orders can be added in v2 without breaking existing data
