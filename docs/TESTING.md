# Testing strategy

## Unit (Pest)
- Money math, VAT calculation, currency formatting.
- `StockService` negative-stock rejection.
- `ReversalService` symmetry (reverse then re-apply returns to original).
- Invoice number sequence per company.
- ZATCA TLV byte-exactness against a known vector.

## Feature / integration
- Full sale flow: invoice + items + stock + movements + balance, all in
  one transaction; forced mid-transaction failure rolls back everything.
- Collection updates cash box + customer balance + invoice paid/remaining.
- Return restores stock and reduces balance.
- Role matrix: each role's forbidden routes return 403.
- Route lock: rep cannot open a visit off-route without the custom flag.
- Rate limiter on login and POST routes.

## E2E (Playwright)
- Rep day: login → start work (mocked geolocation) → route → visit → sell 3
  items → collect → return → end day → verify numbers in admin panel.
- Admin: create product → load van → see stock reflected.
- RTL Arabic smoke on both surfaces.

## Coverage target
All business rules covered; ≥ 70 % on `app/Services`.
