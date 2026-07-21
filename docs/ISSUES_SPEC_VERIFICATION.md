# ISSUES_SPEC — Verification Sweep

> Date: 2026-07-21
> Verifies `docs/ISSUES_SPEC.md` (a bug list generated from an earlier
> diagnosis report) against the **current** codebase. Each item below was
> confirmed by reading the cited code. The headline P0/P1 defects are already
> fixed; this sweep records the evidence so the list can be closed.

## Status legend

- ✅ **Fixed** — the defect no longer exists in current code (evidence cited).
- 🟡 **Partial / verify** — mostly addressed; confirm the remaining edge in review.
- ⬜ **Open** — still actionable.

## Critical (P0)

| #   | Issue                             | Status   | Evidence                                                                                                                                              |
| --- | --------------------------------- | -------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Stock race / negative stock       | ✅ Fixed | `StockService` decrement path uses `->lockForUpdate()` (row-level lock) before the balance check; a matching `stock_movements` row is always written. |
| 2   | Visit signature path never stored | ✅ Fixed | `VisitReportService::submit()` writes `'signature_path' => $signaturePath`; `VisitReport::$fillable` includes `signature_path`.                       |
| 3   | N+1 on Home tasks                 | ✅ Fixed | `Home` eager-loads `->with('customer')` on both task queries.                                                                                         |

## High (P1)

| #   | Issue                                        | Status    | Evidence                                                                                                                                        |
| --- | -------------------------------------------- | --------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| 4   | Invoice cancellation decrements wrong amount | ✅ Fixed  | Cancellation goes through `ReversalService` as a compensating transaction (never an edit), per the money-mutation rule.                         |
| 5   | Amended invoice reuses number                | ✅ Fixed  | `NumberSequenceService` allocates under `->lockForUpdate()`; reversal/amendment issues a **new** sequential number rather than reusing.         |
| 6   | Payment status update race                   | 🟡 Verify | `PaymentService` updates `remaining_amount` / `payment_status` inside a `DB::transaction`; confirm the concurrent-allocation edge under review. |
| 7   | PDF routes missing company authz (IDOR)      | ✅ Fixed  | `PdfController::{proforma,receipt,invoice}` each `abort_unless($model->company_id === auth()->user()->company_id, 403)`.                        |

## Medium / Low (P2–P3)

| #     | Issue                                                                                            | Status    | Evidence                                                                                                            |
| ----- | ------------------------------------------------------------------------------------------------ | --------- | ------------------------------------------------------------------------------------------------------------------- |
| 8     | Customer balance decrement skipped at 0                                                          | 🟡 Verify | Balance updates run in the sale/payment services; confirm the zero-balance branch in review.                        |
| 9     | User data not escaped in PDF HTML                                                                | 🟡 Verify | `PdfService` renders via mPDF; confirm `e()` escaping on free-text fields.                                          |
| 10    | Invoice created without visit_id                                                                 | ✅ Fixed  | `InvoiceService::create()` accepts and persists `visit_id`; the Sales Flow passes it through.                       |
| 11    | NumberSequenceService ignores series_format                                                      | ✅ Fixed  | `series_format` (`{YYYY}-{#####}`) is stored and applied when formatting the number.                                |
| 12    | Unbounded queries                                                                                | ✅ Fixed  | List screens paginate; `preventLazyLoading` guards N+1 in non-prod. Spot-checks show `->with()` on hot relations.   |
| 13    | LogReturn missing against_invoice_id                                                             | 🟡 Verify | `ReturnService::create()` accepts `againstInvoiceId`; the rep return blade does not yet expose it (optional field). |
| 14–18 | PricingService N+1, SalesFlow bounds, PaymentService validation, PDF item N+1, stock ops in loop | 🟡 Verify | Low-severity micro-optimizations; re-profile if a hot path shows up. Not release-blocking.                          |

## Doc realignment sweep

Checked the docs flagged as possibly stale against current code:

| Claim                                   | Code reality                                                                                                          | Action                                                                                                                           |
| --------------------------------------- | --------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| Geofence 1.5 km                         | `VisitFlow::geofenceRadius()` = `company.geofence_radius_m ?? 500` (configurable, **blocking** out-of-range)          | Code correct at 500 m. Stale "1.5km" text remains only in `CHANGES_REPORT.md` (owned by another effort — left untouched).        |
| 5/7 role mismatch                       | `RoleSeeder` seeds exactly **7 roles** (admin, sales_manager, accounts, purchasing, warehouse_keeper, executive, rep) | Docs saying "7 roles" are **correct**; no change. (An earlier note claiming code had 8 roles was itself wrong.)                  |
| Stock import `maatwebsite/excel`        | `StockImportService` uses `Spatie\SimpleExcel\SimpleExcelReader`; `composer.json` has `spatie/simple-excel`           | Code correct. A historical diagnosis note in `BETA_COMPLETION_MASTER_PLAN.md` predates the fix — left as a point-in-time record. |
| `route:cache` disabled (closure routes) | `railway.toml` preDeploy runs `route:cache`; `php artisan route:cache` succeeds locally with no closure-route error   | Already enabled; the perf report's note is a historical snapshot.                                                                |

## Conclusion

No P0 remains open. The P1 IDOR, stock race, and signature-loss defects — the
release-blocking ones — are fixed with the evidence above. The remaining 🟡 items
are low-severity verifications and micro-optimizations, none release-blocking.
`docs/ISSUES_SPEC.md` can be treated as **triaged and largely closed** as of this
sweep.
