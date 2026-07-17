# R-03 Baseline Report

**Date:** July 16, 2026  
**Branch:** recovery/beta-checkpoint-pre-r1  
**Commit:** 130ea7b

---

## Checks Run

| Check | Result | Failures | Severity |
|-------|--------|----------|----------|
| `composer validate --strict` | PASS | 0 | — |
| `composer audit` | PASS | 0 | — |
| PHP syntax check (all app/*.php) | FAIL | 1 | P0 |
| `vendor/bin/pint --test` | PASS | 0 | — |
| `php artisan optimize:clear` | PASS | 0 | — |
| `php artisan migrate:fresh --seed` | PASS | 0 | — |
| `php artisan test` | PASS | 32 tests, 105 assertions | — |
| `npm run build` | PASS | 0 | — |
| `php artisan route:list` | PASS | 68 routes | — |
| Forbidden pattern scan | FAIL | 3 | P0/P1/P2 |
| Secret scan | PASS | 0 | — |

---

## Findings

### P0 — Fatal / Release Blockers

#### 1. PricingService class-name collision
- **File:** `app/Services/PricingService.php`
- **Issue:** `Cannot declare class App\Services\PricingService because the name is already in use`
- **Cause:** The contract interface `app/Services/Contracts/PricingService.php` and the implementation `app/Services/PricingService.php` share the same fully-qualified class name `App\Services\PricingService`. PHP cannot resolve this.
- **Fix ticket:** R-04

#### 2. Explicit bcrypt usage (violates Argon2id rule) — RESOLVED
- **File:** `app/Filament/Resources/UserResource.php:54`
- **Issue:** `->dehydrateStateUsing(fn ($state) => bcrypt($state))` uses `bcrypt()` directly instead of Laravel's `Hash` facade (which is configured for Argon2id).
- **Status:** Fixed in code — `bcrypt()` replaced with `Hash::make()`

### P1 — Security / Integrity

#### 3. Unbounded `->get()` queries (12 instances)
- **Files:** Home.php, QuotationFlow.php, LogComplaint.php, SubmitPurchaseOffer.php, StockSearch.php, TodaysCustomers.php, ReportsPage.php
- **Issue:** 12 `->get()` calls without pagination. Some have `->limit()` but not all.
- **Fix ticket:** R-07 / B1-06

### P2 — Lower Priority

#### 4. Stock import references unavailable package
- **File:** `app/Imports/StockImport.php`
- **Issue:** References `Maatwebsite\Excel` which is not installed. Project has `spatie/simple-excel`.
- **Fix ticket:** R-05

#### 5. StockResource references nonexistent `is_reserved` column
- **File:** `app/Filament/Resources/StockResource.php`
- **Issue:** Filter and column reference `is_reserved` which does not exist in the stocks table schema.
- **Fix ticket:** R-05

#### 6. LeafletMapPicker uses raw view injection, not Filament state system
- **File:** `app/Filament/Resources/CustomerResource.php`
- **Issue:** Uses `Forms\Components\View::make()` instead of a proper Filament field with state binding. Coordinates may not persist correctly on edit.
- **Fix ticket:** R-06

---

## Items NOT Found (Good)

- No `exec()`, `shell_exec()`, `system()`, `passthru()`, `proc_open()`, `eval()` in app code
- No `$request->all()` in app code
- No hard-coded secrets in app code
- No `dd()`, `dump()`, `var_dump()`, `print_r()` in app code (false positive on `add` method name)

---

## Summary

| Severity | Count | Tickets |
|----------|-------|---------|
| P0 | 1 | R-04 |
| P1 | 1 | R-07, B1-06 |
| P2 | 3 | R-05, R-06 |
| **Total** | **5** | — |

The app boots, tests pass, migrations work, and the build succeeds. The P0 PricingService collision is the most critical — it prevents the container from resolving pricing correctly and will cause a fatal error when any code path tries to use the pricing service.
