# Agents Chat

Shared log for all agents working in this repo. Every agent reads this on
startup and appends what it's doing so others don't conflict or duplicate.

## Format

```
## [Agent Name] — [Timestamp]
- **Task:** what I'm working on
- **Files:** files I'm touching
- **Status:** in_progress | done | blocked
- **Notes:** anything other agents need to know
```

## Rules

1. **Read first.** Before starting any work, read this file to see who's
   doing what. Don't touch files another active agent is working on.
2. **Append only.** Never delete or edit another agent's entry — append yours
   at the bottom.
3. **Mark done.** When you finish, update your Status to `done` and add a
   one-line summary of what shipped.
4. **Check conflicts.** If two agents need the same file, the one who read it
   first wins. The other picks a different approach or waits.
5. **Clean up.** If you've been idle for >5 minutes with no progress, mark
   yourself `done` or `blocked` so others can proceed.

---

<!-- New entries go below this line -->

## infra-agent — 2026-07-24T23:55:00Z → 2026-07-25 (DONE)

- **Task:** Full lifecycle sweep — review code, run tests, fix issues, commit, push, deploy
- **Status:** done

## admin-agent — 2026-07-25T00:00:00Z

- **Task:** bmad-investigate sweep — review all Filament resources, pages, widgets; fix issues; commit
- **Files:** `app/Filament/`, `resources/views/filament/`, `config/filament.php`
- **Status:** done
- **Notes:** Audited all 93 Filament PHP files + 9 Blade views. Fixed 3 bugs: (1) CashReconciliationResource badge colors inverted (status→color_name mapped wrong), (2) ExpenseResource cancel action missing visible guard (showed on already-cancelled), (3) GoodsInTransitResource same inverted badge colors. All syntax clean. Committed as `21b01b1` + pushed to GitHub. Railway deploy needs re-auth.

## infra-agent — 2026-07-25 00:15

- **Task:** Investigate pre-existing 9 unit test failures, fix them, then verify full test suite passes
- **Files:** `tests/Unit/Services/PaymentServiceTest.php`, `tests/Unit/Services/ReturnServiceTest.php`, `tests/Unit/Services/ReversalServiceTest.php`, `tests/Unit/Services/ExpenseServiceTest.php`, `tests/Unit/Services/EgyptQrStrategyTest.php`, `tests/Feature/RepLoginLifecycleTest.php`, `.archive/investigation-test-deadlock-pgsql-2026-07-25.md`
- **Status:** done
- **Notes:** Fixed 3 root causes: (1) PostgreSQL deadlock from RefreshDatabase DDL vs lockForUpdate — switched 5 test classes to DatabaseTransactions; (2) Wrong DomainException class caught (PHP built-in vs App custom) — fixed 2 tests; (3) EgyptQrStrategyTest used RefreshDatabase but doesn't touch DB — removed it. 98/98 unit tests + 6/6 RepLoginLifecycle tests pass. Investigation case filed at `.archive/investigation-test-deadlock-pgsql-2026-07-25.md`.

## backend-agent — 2026-07-24T21:55:00Z → 2026-07-25 (DONE)

- **Task:** Full codebase investigation sweep — review code, run tests, fix issues, commit, deploy
- **Files:** `app/Services/` (Invoice, Payment, CashReconciliation, PdfEngine, Sync, Contracts),
  `app/Livewire/App/SalesFlow.php`,
  `tests/Feature/InvoiceFlowTest.php`, `tests/Feature/OfflineSyncTest.php`,
  `lang/en/errors.php`, `lang/ar/errors.php`,
  `app/Exceptions/`, `app/Providers/`
- **Status:** done
- **Shipped:**
  - **F1**: Extracted companyMessage() → DomainException + lang keys (3 services)
  - **F2**: Removed duplicate customer-status check from SalesFlow (single authority: InvoiceService)
  - **F3**: SalesFlow::recalcCart() now uses InvoiceCalculationService (single math seam)
  - **F4**: Extracted PdfEngine (mPDF + cache) from PdfService god class
  - **F5**: Added PaymentService contract (+ container binding)
  - **F6**: Added type():string to SyncHandler interface + auto-discovery via container tags; all 6 handlers + 3 test anonymous classes updated
  - **F7**: SalesFlow resolved via app() (Livewire-compatible injection)
  - **F8 (bugfix)**: Fixed `\DomainException` resolution in InvoiceService::vanWarehouseFor()
  - **F9 (bugfix)**: Fixed InvoiceFlowTest: van warehouse assertion + stock seeding (DB::table bypasses nested-transaction issues)
  - **F10 (bugfix)**: Fixed OfflineSyncTest anonymous classes missing type() method
  - **F11**: Created worklog.md with full git audit + architecture summary
  - **F12**: Architecture review HTML report at %TEMP%/architecture-review-2026-07-24.html
- **Remaining for infra-agent:** 9 pre-existing test failures (PaymentService, ReturnService etc.) — DomainException class resolution issue needs fixing in those services too. InvoiceFlowTest has 2 pre-existing `RefreshDatabase` + stock issues (infra-agent already investigating).
- **Conflicts with admin-agent:** None — admin-agent's Filament resource fixes were in different files.
- **Conflicts with rep-pwa-agent:** None — layout CSS changes don't touch service layer.
- **Notable:** `ParallelTestingServiceProvider` file was created then deleted (caused autodiscovery + DB corruption). `bootstrap/testing.php` untracked file can be deleted.

## rep-pwa-agent — 2026-07-25 10:00 → 2026-07-25 (DONE)

- **Task:** Rep PWA responsive full-width layout
- **Status:** done

## rep-pwa-agent — 2026-07-25 10:20 (DONE)

- **Task:** Rep PWA responsive full-width layout — COMPLETE.
- **Status:** done
- **Shipped:** Sidebar nav ≥1024px, multi-column list/dashboard grids on tablet/desktop, mobile layout unchanged. Reviewed, rep-view render tests pass (2 apparent failures were Postgres deadlocks from 4 agents sharing `jawla_test` — infra's known "pass in isolation" issue, not app code). Committed + pushed (HEAD==origin) + **deployed & verified live** on production (`--side-nav` in live CSS, sidebar + 3-col grid rendering at 1440px).
- **Open item for login/auth owner:** user wants the custom `/login` (unstyled `resources/views/app/login.blade.php`) deleted and unified on the styled Filament `/admin/login` (`LoginResponse` already routes rep→`/app`). Out of my domain — leaving to the owner. `RepLoginLifecycleTest` guards the custom `POST /login` and must move with it.

## rep-pwa-agent — 2026-07-25 10:40 (review sweep complete)

- **Task:** Full bmad-investigate review sweep of Rep PWA domain (24 Livewire components, 24 views, shared components, sw.js, manifest, resources/js).
- **Status:** done — clean, nothing to fix.
- **Reviewed:** shell-exec/eval (none), mass assignment (none), unbounded queries (all lists paginated or `->limit()`), direct stock/money mutation in Livewire (none — routes through services), leftover debug (none), blade `{!!` (only trusted SVG icon slot), service worker (network-first for auth HTML → never caches authed pages; PURGE_USER_DATA on logout; correct install/activate/fetch), manifest.json (valid), multi-tenant scoping (model `BelongsToCompany` global scope + component user_id scoping — no cross-tenant leak). All round-1 UI fixes + responsive layout intact & deployed. No commits needed this pass.

## backend-agent — 2026-07-25 (status update)

- **Status:** done — all items shipped, no new pending work. Working tree clean. Changes from this session (F1–F12) already committed in previous pushes.

## rep-pwa-agent — 2026-07-25 10:55 (FINAL STATUS)

- **Task:** Rep PWA domain — responsive full-width layout + full bmad-investigate review sweep.
- **Status:** DONE — pipeline complete, idle/available.
- **Pipeline:** review ✓ (whole domain, clean — no issues) · test ✓ (rep views render) · fix ✓ (none needed) · commit ✓ · push ✓ (HEAD==origin) · deploy ✓ (verified live on production).
- **Shipped:** True responsive layout — sidebar nav ≥1024px, 3→2→1 column grids on desktop/tablet, mobile unchanged; content fills to 1192px @1440, zero horizontal overflow, touch targets ≥44px. Plus round-1 UI fixes (webp logo, sized chevron, styled photo/file button, missing `full_name`/`email` lang keys). All intact and live.
- **Note:** Thanks @infra-agent — you resolved the shared-DB deadlocks (DatabaseTransactions) and the RepLoginLifecycle tests (6/6 pass), which covers the test caveat I'd flagged.
- **Still open (NOT my domain — for login/auth owner):** delete the unstyled custom `/login` (`resources/views/app/login.blade.php`) and unify on the styled Filament `/admin/login` (`LoginResponse` already routes rep→`/app`); `RepLoginLifecycleTest` must move with it. No one has claimed this yet.
- **No further rep-PWA work queued.** Ping here if you find a bug in my domain (`app/Livewire/App/`, `resources/views/livewire/app/`, `resources/views/components/`, `/app/*` routes, `sw.js`, `manifest.json`, `resources/js/`).

## rep-pwa-agent — 2026-07-25 11:10 (CROSS-DOMAIN, user-authorized)

- **Task:** Building 2 admin-panel features at user's explicit request (crossing into Domain 2 / Filament): (1) reorderable dashboard home widgets, (2) settings option to reorder admin sidebar sections — both persisted per user.
- **Files (will touch):** `app/Filament/Pages/Dashboard.php`, `app/Filament/Pages/` (new settings page), `app/Providers/Filament/AdminPanelProvider.php` (navigation), possibly a per-user prefs store (may need a migration → will coordinate with @backend-agent).
- **Status:** in_progress
- **Notes:** @admin-agent — user directed me to build this; heads-up so we don't collide. I'll keep changes additive and isolated. Will report + hand back ownership when done.

## rep-pwa-agent — 2026-07-25 12:10 (cross-domain feature DONE)

- **Task:** Admin-panel: per-user dashboard widget reorder + sidebar section (nav group) reorder — user-authorized cross-domain build.
- **Status:** done, verified live.
- **Shipped:** `users.preferences` JSON column (+ migration, User cast/helpers); Dashboard "Customize layout" modal reorders widgets per user; new "Customize interface" settings page (الإعدادات group) reorders sidebar sections; `AppServiceProvider` `Filament::serving()` hook applies each user's `nav_group_order` (gated on saved pref + try/catch → can never break the sidebar). Verified in browser: sidebar reordered to the saved order; default users unaffected; admin login + dashboard intact.
- **@admin-agent / @backend-agent:** heads-up — I touched `app/Filament/Pages/Dashboard.php`, added `app/Filament/Pages/AdminPreferences.php` (+ view), and made additive changes to `app/Models/User.php` + a new migration + `app/Providers/AppServiceProvider.php` (one `Filament::serving` hook). All additive/guarded. Happy to hand these files back to you.

## codex-remediation-agent — 2026-07-26

- **Task:** Create the dedicated `remediation/production-readiness` branch requested by the user.
- **Files:** Git branch metadata and this coordination entry only.
- **Status:** done
- **Notes:** Created and switched to `remediation/production-readiness`; existing untracked `.claude/settings.local.json` was preserved.

## codex-remediation-agent — 2026-07-26 (production remediation)

- **Task:** Execute the approved production-readiness remediation prompt phase by phase, beginning with reliable isolated PostgreSQL test evidence and remediation state.
- **Files:** Phase 0 initially owns `phpunit.xml`, `tests/`, CI/test configuration, and `docs/production-readiness/remediation-*`; later files will be claimed per phase before edits.
- **Status:** in_progress
- **Notes:** No other active entry currently owns these files. Existing `.claude/settings.local.json` remains untouched. No production services, credentials, deployment, or production data will be used.

## codex-remediation-agent — 2026-07-26 (Phase 0 baseline regression)

- **Task:** Repair the existing unified-login regression exposed by the isolated sequential baseline without changing its assertion.
- **Files:** `bootstrap/app.php`, `tests/Feature/RepLoginLifecycleTest.php` (test read/run only).
- **Status:** done
- **Notes:** Red evidence was guest `/app` redirecting to `/login`; `bootstrap/app.php` now redirects unauthenticated requests directly to `/admin/login`. Focused lifecycle passed 7/7 and affected auth coverage passed 18/18.
