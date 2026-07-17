# Jawla Beta v1.1 — Open Decision Register

This compact register contains the only external decisions that may block final beta acceptance. Implementation can prepare configurable or mock-backed foundations, but an executing model must not invent the final answers.

## D-01: Pricing range behavior

Blocked by: Client/Finance/Manager decision  
Type: Grilling  
Needed before: B4 UAT

### Question

Is the final allowed representative price rule floor-only, or must it enforce both a lower and upper bound? Define the exact meaning and ownership of base price, manager plus/minus, and representative plus/minus using examples including 850, 900, 950, 1000, 1100, and 1200.

### Temporary implementation

Implement configurable `floor_only` and `two_sided` strategies. Use the currently documented floor example only for automated beta scaffolding. Do not call the choice final until signed off.

### Answer

Floor-only. Rep can increase from base price but not go below. No upper bound. Example to confirm: base=1000, manager floor=950, rep floor=900 — rep can sell at 900–1200+.

## D-02: Geofence override behavior and radius

Blocked by: Client/Operations decision  
Type: Grilling  
Needed before: B3 UAT

### Question

Approve or amend this behavior: in-range confirms normally; out-of-range allows a bilingual “confirm anyway” action, stores the flag/distance/accuracy, and alerts the manager; GPS denied uses a flagged fallback. Also confirm the operating radius instead of leaving 1km and 1.5km values inconsistent.

### Temporary implementation

Keep one company-configurable radius and implement the proposed flag-and-notify path.

### Answer

- **In range:** Allow confirm visit normally.
- **Out of range:** Decline. Rep cannot check in.
- **GPS denied:** GPS must be on — app won't work without it. Block check-in entirely.
- **Radius:** 500m (100m is better if feasible).

## D-03: Warehouse stock import contract

Blocked by: Real client sample file  
Type: Research  
Needed before: B2 acceptance

### Question

What are the real headings, delimiter, encoding, units, SKU/product identifiers, warehouse identifiers, batch fields, received date, sellable quantity semantics, and transit-quantity semantics? Is quantity an absolute count or a delta? How should duplicate rows and missing products be handled?

### Temporary implementation

Build preview/import against a documented mock CSV using `spatie/simple-excel`. Default to absolute reconciliation through `StockService`. Do not silently create products or add transit to sellable stock.

### Required asset

An anonymized real sample plus written answers to the quantity and duplicate rules.

### Answer

Deferred. Client will provide real CSV sample and answers when ready.

## D-04: Sales/Purchasing dual-review mechanics

Blocked by: Client Sales and Purchasing decision  
Type: Grilling  
Needed before: B7 acceptance

### Question

Can either department review first? Does Sales veto immediately end the request? Can a rejected offer be edited and resubmitted or must it be copied into a new request? Are reasons mandatory? Does an offer expire? What happens when Purchasing approves before Sales vetoes?

### Temporary implementation

Use separate immutable decisions. Sales veto prevents final approval regardless of order. Require reasons for rejection/veto and use row locking/idempotency for simultaneous decisions.

### Answer

1. **Review order:** Sales first, then Purchasing (in Odoo).
2. **Sales veto:** Offer stays in Jawla for renegotiation — not killed.
3. **Resubmission:** Yes, rejected offers can be edited and resubmitted.
4. **Reasons:** Nice-to-have — optional text field to type reason (not mandatory).
5. **Expiration:** Rep sets the expiration date on the offer.
6. **Race condition:** Purchasing (Odoo) never sees the offer unless Sales manager approves first.

