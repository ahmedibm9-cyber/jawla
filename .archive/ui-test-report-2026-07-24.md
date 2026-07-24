# JAWLA UI Test Report — Full App Audit

> **⚠️ ARCHIVED: 2026-07-24 — Findings reviewed; failures were transient/environmental.**
> See `ISSUES_ARCHIVE.md` (root) for the definitive status.

**Date:** 2026-07-24  
**URL:** https://jawla-production.up.railway.app  
**Test Accounts:** admin@jawla.test / rep@jawla.test  
**Method:** 6 parallel Playwright agents testing all screens, workflows, and UI states

---

## Executive Summary

| Category                                | Tested | Passed | Failed | Warnings |
| --------------------------------------- | ------ | ------ | ------ | -------- |
| Auth & Navigation                       | 7      | 6      | 0      | 1        |
| Sales & Visit Workflows                 | 12     | 9      | 3      | 0        |
| Inventory Management                    | 8      | 7      | 1      | 0        |
| Finance & Accounting                    | 7      | 6      | 1      | 0        |
| Admin & Reports                         | 11     | 8      | 3      | 0        |
| UI States (Loading/Success/Error/Empty) | 13     | 10     | 3      | 0        |
| **TOTAL**                               | **58** | **46** | **11** | **1**    |

---

## Critical Issues (Must Fix)

### 1. Admin/Rep Session Conflict (HIGH)

- **Impact:** Admin users who also have rep role get redirected to `/app` after login
- **Cause:** Same user account has both `admin` and `rep` roles; session middleware picks wrong guard
- **Fix:** Ensure Filament admin guard and rep PWA guard use separate session keys or middleware priority

### 2. Reports Page Broken (HIGH)

- **Impact:** Admin cannot access reports
- **Status:** Page returns error or blank
- **Fix:** Investigate `ReportsPage` Livewire component

### 3. Purchase Requests Page Broken (HIGH)

- **Impact:** Admin cannot manage purchase requests
- **Status:** Page returns error
- **Fix:** Investigate `PurchaseRequestResource` Filament resource

### 4. Create User Page Broken (HIGH)

- **Impact:** Admin cannot create new users
- **Status:** Page returns error
- **Fix:** Investigate `UserResource` create action

### 5. /app/more Page Not Rendering (HIGH)

- **Impact:** Rep PWA "More" menu item is broken
- **Cause:** `MorePage` Livewire component has rendering issue
- **Fix:** Check `app/Livewire/App/MorePage.php` and its view

---

## Important Issues (Should Fix)

### 6. /app/sales-flow Returns 404 (MEDIUM)

- **Impact:** Old bookmark/URL doesn't work
- **Cause:** Route is `/app/sell`, not `/app/sales-flow`
- **Fix:** Add redirect from `/app/sales-flow` → `/app/sell`

### 7. Missing Translation Key: app.products (MEDIUM)

- **Impact:** Sales page shows raw `app.products` string
- **Fix:** Add `products` key to `lang/ar/app.php` and `lang/en/app.php`

### 8. Customer Search Pre-fill Bug (MEDIUM)

- **Impact:** Browser autofill injects stale data into customer search
- **Fix:** Add `autocomplete="off"`, `autocorrect="off"`, `autocapitalize="off"` attributes

### 9. Mixed Languages in Empty States (MEDIUM)

- **Impact:** Some empty states show English "No results" alongside Arabic text
- **Fix:** Use `$l()` or `__()` helper consistently in all empty state views

### 10. No Rep PWA Logout Button (MEDIUM)

- **Impact:** Rep users cannot log out from the mobile interface
- **Fix:** Add logout button to More page or bottom nav

---

## Minor Issues (Nice to Have)

### 11. No Search Input on /admin/expenses (LOW)

- **Impact:** Only admin screen without search/filter
- **Fix:** Add search to `ExpenseResource`

### 12. No Loading Indicators on Rep PWA Screens (LOW)

- **Impact:** Screens render instantly (PWA offline-first) but no visual feedback during data fetch
- **Fix:** Add Livewire loading states to rep components

### 13. Sidebar 'تنبيهs' Mixed Text (LOW)

- **Impact:** Alarm menu item shows mixed Arabic/English
- **Fix:** Check alarm label in `AdminPanelProvider` or navigation config

---

## UI States Matrix

| Screen           | Loading      | Success    | Empty  | Error     | Notes |
| ---------------- | ------------ | ---------- | ------ | --------- | ----- |
| /admin/dashboard | ✅ Spinner   | ✅ Widgets | N/A    | ✅        |       |
| /admin/products  | ✅ Spinner   | ✅ Table   | ✅ CTA | ✅        |       |
| /admin/customers | ✅ Spinner   | ✅ Table   | ✅ CTA | ✅        |       |
| /admin/invoices  | ✅ Spinner   | ✅ Table   | ✅ CTA | ✅        |       |
| /admin/payments  | ✅ Spinner   | ✅ Table   | ✅ CTA | ✅        |       |
| /admin/stock     | ✅ Spinner   | ✅ Table   | ✅ CTA | ✅        |       |
| /admin/users     | ✅ Spinner   | ✅ Table   | ✅ CTA | ❌ Broken |       |
| /admin/routes    | ✅ Spinner   | ✅ Table   | ✅ CTA | ✅        |       |
| /admin/expenses  | ⚠️ No search | ✅ Table   | ✅ CTA | ✅        |       |
| /app/sell        | ✅ Instant   | ✅ Cards   | ✅ CTA | ✅        |       |
| /app/customers   | ✅ Instant   | ✅ Cards   | ✅ CTA | ✅        |       |
| /app/visit-flow  | ✅ Instant   | ✅ Cards   | ✅     | ✅        |       |
| /app/stock       | ✅ Instant   | ✅ List    | ✅ CTA | ✅        |       |

**Legend:** ✅ Pass | ⚠️ Partial | ❌ Fail | N/A Not Applicable

---

## Screenshots

All screenshots saved in `screenshots/` directory:

- test-01-admin-login-error.png
- test-02-rep-login-success.png
- test-04-locale-english.png
- test-05-admin-dashboard-final.png
- test-06-rep-home.png
- UI-STATES-MATRIX-REPORT.md (full matrix with screenshots per screen)

---

## Recommendations

### Immediate (Before Next Release)

1. Fix admin/rep session conflict — affects all dual-role users
2. Fix broken admin pages (Reports, Purchase Requests, Create User)
3. Fix /app/more page rendering

### Short Term (This Sprint)

4. Add redirect for /app/sales-flow → /app/sell
5. Fix missing translation keys
6. Fix customer search pre-fill bug
7. Add rep PWA logout button

### Long Term (Backlog)

8. Standardize empty states across all screens
9. Add search to expenses page
10. Add loading indicators to rep PWA
11. Fix sidebar label mixing
