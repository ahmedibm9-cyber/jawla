---
id: LOGIN.1
title: Consolidate rep login onto a single canonical path (/app/login)
status: ready-for-dev
owner: unassigned
severity: high
source: bmad-output/investigation-rep-login-fragility-2026-07-21.md
decision: Canonical rep-login path = separate /app/login (chosen 2026-07-21)
---

## Context

Rep login broke twice (`57d460a`, `a0daf22`) because the "unified login" refactor
(`d3fddc6`) was rolled back **piecemeal**. The result is two rep-auth systems and
redirects that disagree on which is canonical. See the investigation case file for
the graded evidence.

**Decision:** the separate **`/app/login`** is canonical for reps (matches the PWA
UX and the currently-restored `LoginController`). `/admin/login` is the admin
entry point (Filament), with a graceful fallback for a stray rep.

## Current state (verified in code)

- `/app/login` → `LoginController::create/store`; `store()` is **already rep-only**
  (rejects non-reps + inactive), rate-limited (`throttle:login`), argon2id,
  `session()->regenerate()`, activity-logged. ✅
- `User::canAccessPanel()` **already excludes reps** — reps cannot reach the admin
  panel. ✅
- Filament panel (`AdminPanelProvider`: `->path('admin')->login(Login::class)`)
  handles its own guest redirect to `/admin/login`, independent of the global
  redirect. ✅
- **Bug 1:** `bootstrap/app.php` → `redirectGuestsTo('/admin/login')` — sends
  unauthenticated reps to the admin login, not `/app/login`.
- **Bug 2:** `LoginController::destroy()` → `redirect('/admin/login')` — a rep
  logging out lands on the admin login, not `/app/login`.

## Acceptance criteria

1. `redirectGuestsTo('/app/login')` — an unauthenticated visitor to any `/app/*`
   route lands on the rep login. Admin panel auth is unaffected (Filament owns its
   own redirect).
2. `LoginController::destroy()` redirects to `route('app.login')` — logout returns
   a rep to the rep login. Guest redirect and logout now **agree** `/app/login` is
   canonical.
3. `/admin/login` still logs an admin in and reaches the panel; a stray rep who
   authenticates there is still gracefully sent to `/app` (keep the `LoginResponse`
   rep fallback — it is now clearly secondary, not a competing canonical path).
4. Security controls intact on `/app/login`: `throttle:login` (5/min IP+email),
   argon2id, session regeneration, activity logging.
5. A rep cannot access the admin panel (`canAccessPanel` unchanged).

## Regression guard (the reason it broke twice)

Add an end-to-end feature test asserting the full rep-login lifecycle so a future
edit cannot silently re-break it:

- guest → `/app/*` redirects to `/app/login`
- rep authenticates at `/app/login` → reaches `/app`
- non-rep rejected at `/app/login`
- rep logout → `/app/login`
- admin authenticates at `/admin/login` → reaches `/admin`

## Out of scope

- Do NOT modify Filament `Login.php::authenticate()` (fragile; works).
- No new packages. No change to `canAccessPanel` or the rep role model.

## Dev Notes

Files: `bootstrap/app.php` (redirect), `app/Http/Controllers/App/LoginController.php`
(`destroy`), `tests/Feature/` (new regression test). Cite the investigation case
file in the commit.
