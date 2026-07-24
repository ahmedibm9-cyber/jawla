# Investigation Case File: rep-login-fragility

> **⚠️ ARCHIVED: 2026-07-24 — Rep login consolidated per LOGIN.1 story.**
> See `ISSUES_ARCHIVE.md` (root) for the definitive status.

**Date:** 2026-07-21
**Project:** Jawla (جولة) Field Sales CRM/ERP
**Reported By:** Assistant (reflog forensics during Phase 6 work) — surfaced two "restore login" commits
**Severity:** Degraded UX — resolved outage with **latent outage risk** (rep login broke twice)
**Status:** Hypothesis Confirmed
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-rep-login-fragility-2026-07-21.md`

---

## Summary

**One-sentence description:**
Rep login broke at least twice because the "unified login" refactor (`d3fddc6`)
was rolled back piecemeal, leaving two parallel rep-login systems and a
guest-redirect that still points at the wrong one.

**Expected behavior:** One canonical way for a rep to authenticate; an
unauthenticated rep hitting a protected `/app/*` route lands on a working login
page and, after login, reaches `/app`.

**Actual behavior (now):** Login _works_ (304 tests, 959 assertions pass), but via
**two coexisting systems** — the restored separate `/app/login`
(`LoginController::create/store`) **and** the unified Filament `/admin/login`
(`Login.php` + `LoginResponse`, which also authenticates reps and redirects to
`/app`). `redirectGuestsTo('/admin/login')` sends guests to the unified one while
the separate one is the "restored" canonical page — the two disagree.

**Actual behavior (during the incident window):** After the routes were restored
(`57d460a`) but before the controller methods were (`a0daf22`), `/app/login`
routed to `LoginController::create/store` that did not exist → rep login broken.

**User / business impact:** Total rep-app lockout during each broken window (the
entire rep PWA is gated behind login). Currently functional but fragile: any edit
near auth can re-break it because there are two sources of truth.

---

## Symptom Details

**Trigger conditions:** Editing/refactoring anything in the login path while the
two parallel systems coexist. Historically triggered by the unified-login refactor
and its incomplete rollback across multiple commits.

**Environments affected:**

- [x] Production (rep login is the gate to the whole rep PWA)
- [x] Staging
- [x] Development / local

**First observed:** Pre-session — `57d460a` "restore rep login routes accidentally
removed from web.php" was already in history at session start. Recurred during this
session — `a0daf22` "restore LoginController create() and store() — rep login was
broken" (2026-07-21 17:47).
**Frequency:** Twice observed (routes, then methods), on separate commits.
**Reproducible:** Yes, historically — `git show` of the diffs confirms the removals.

**Reproduction (historical):**

1. `d3fddc6` deletes `/app/login` route + blade + `LoginController::create/store` + `LoginRequest` (unify login into `/admin/login`).
2. Rollback restores the `/app/login` **routes** (`57d460a`) pointing at `create`/`store`.
3. `create`/`store` still absent → `/app/login` errors until `a0daf22` restores them.

---

## Evidence

> Grades: **[A]** confirmed/observed · **[B]** probable/inferred · **[C]** speculative.

### Evidence Item 1 — The unified-login refactor deleted the rep login set

**Grade:** [A] · **Source:** `git show d3fddc6 --stat` + commit body
The commit "feat: unified login — single /admin/login for admin and rep" explicitly:

```
- Removed old /app/login route and blade view
- Deleted dead LoginController::create/store and LoginRequest
- Guest redirect → /admin/login in bootstrap/app.php
```

Deleted `LoginController.php` (−39 lines) and `LoginRequest.php` (−21).
**Implications:** The rep login was intentionally removed as a coherent set. Any
rollback must restore the _same_ set atomically.

### Evidence Item 2 — Routes restored separately, before methods

**Grade:** [A] · **Source:** `git show 57d460a -- routes/web.php`
`57d460a` re-adds the guest group `Route::get('/login', [LoginController::class, 'create'])`
and `Route::post('/login', [LoginController::class, 'store'])`.
**Implications:** These routes reference `create`/`store`, which were still deleted
at that point → `/app/login` broken until the methods returned.

### Evidence Item 3 — Methods restored later, in a bundled commit

**Grade:** [A] · **Source:** `git show a0daf22`
"restore LoginController create() and store() — rep login was broken … The
LoginController was reduced to only destroy()." Restored `create(): View` and
`store(): RedirectResponse`. Notably the commit **also swept up unrelated
in-progress work** (`ActionToast.php`, `action-toast.blade.php`, the Phase-6
brainstorm report, `decision-log.md`) — evidence of multi-actor commits on a
shared working tree.
**Implications:** Confirms the two-phase (routes-then-methods) rollback and the
broken window between `57d460a` and `a0daf22`.

### Evidence Item 4 — Guest redirect never reverted (lingering)

**Grade:** [A] · **Source:** `bootstrap/app.php:27`
`$middleware->redirectGuestsTo('/admin/login');` — still the unified-login target,
never reverted with the rest of the rollback.
**Implications:** Unauthenticated reps are redirected to `/admin/login`, not the
restored `/app/login`. The two login entry points disagree on which is canonical.

### Evidence Item 5 — Unified Filament login still live alongside the restored one

**Grade:** [A] · **Source:** `app/Filament/Auth/Pages/Login.php`, `LoginResponse.php:16`
`LoginResponse` still routes reps: `if ($user->hasRole('rep')) return redirect()->intended('/app');`
So `/admin/login` authenticates reps too. **Two parallel rep-login systems coexist.**
**Implications:** This is the core fragility — two sources of truth for rep auth.

### Evidence Item 6 — Login restored in a different shape than removed

**Grade:** [B] · **Source:** `ls app/Http/Requests/App/LoginRequest.php` → absent
The original `LoginRequest` was **not** restored; `store()` now inlines validation.
**Implications:** The rollback is not a clean revert; it's a hand-rebuild, which is
how pieces (methods, request, redirect) drift out of sync.

---

## Hypotheses (ranked)

### H1 — Incomplete, piecemeal rollback of the unified-login refactor **(High)**

`d3fddc6` removed the rep-login set atomically; the walk-back restored it across
separate commits (routes `57d460a`, methods `a0daf22`) and never reverted the
guest redirect (E4) or removed the parallel Filament rep-login (E5).

- **Supporting:** E1–E5 (all [A]).
- **Contradicting:** none.
- **Verification:** confirm `/app/login` and `/admin/login` both authenticate a rep
  today, and that `redirectGuestsTo` points at `/admin/login` while `/app/login` is
  presented as the rep page. (Read-only; already confirmed via code.)

### H2 — Multi-actor commits on a shared working tree amplified the drift **(Medium)**

`a0daf22` bundled unrelated in-progress work (E3), showing concurrent actors
committing into one tree/branch — coordinated deletions can land without their
dependencies (routes without methods).

- **Supporting:** E3 [A]. **Contradicting:** the root deletions predate the session.
- **Verification:** review branch/worktree ownership; check for other bundled commits.

### H3 — Panel-path churn independently disturbed login wiring **(Low)**

`c1172d1` (move panel to root) → `41eaab4` (revert to `/admin`) touched
`LoginController.php` too.

- **Supporting:** commit list [B]. **Contradicting:** the decisive removal is `d3fddc6`.
- **Verification:** diff `c1172d1`/`41eaab4` for login side-effects.

---

## Suspected Components

### 1. Auth entry points (canonical-path ambiguity) — Confidence: High

- **Components:** `routes/web.php` (rep guest group), `app/Http/Controllers/App/LoginController.php`, `bootstrap/app.php` (`redirectGuestsTo`), `app/Filament/Auth/Pages/Login.php` + `app/Filament/Auth/Http/Responses/LoginResponse.php`.
- **Why:** E4 + E5 — two live rep-login systems and a redirect pointing at the non-restored one.
- **Blast radius:** the entire rep PWA (login gates all `/app/*`); admin login shares `/admin/login`.

### 2. Login validation/shape — Confidence: Medium

- **Component:** `LoginController::store` (inlined validation) vs the deleted `LoginRequest` (E6).
- **Why:** rebuilt differently from the original; rate-limit (`throttle:login`) + argon2id + session regeneration + activity logging must be re-verified present.
- **Blast radius:** login security posture (brute-force throttling, session fixation).

---

## Recommended Action

**Option C → feeds Option A.** This is an architectural decision, not a one-line
fix: **choose ONE canonical rep-login path and delete the other.**

1. **Decide (owner):** unified `/admin/login` for everyone, **or** separate
   `/app/login` for reps. (Recommendation: separate `/app/login` — it matches the
   PWA UX and the currently-restored code; the unified path was already walked back.)
2. **Consolidate to the decision:**
   - If separate: remove the rep branch from `Login.php`/`LoginResponse`, and change
     `redirectGuestsTo` to `/app/login` (E4) — this is the concrete lingering bug.
   - If unified: remove the `/app/login` route + `LoginController::create/store` +
     `app.login` blade again, and keep `redirectGuestsTo('/admin/login')`.
3. **Guardrail:** add a feature test asserting the canonical rep-login page renders
   and a rep can authenticate end-to-end, so this can't silently break a third time.
4. **Security re-verify:** `throttle:login` (5/min IP+email), argon2id, session
   regeneration on login, activity logging — all present on the surviving path.

Draft as a `ready-for-dev` consolidation story once the path decision is made.
Until then this stays **Hypothesis Confirmed** (root cause known; fix gated on a
one-line product decision).

---

## Verification checklist for the dev agent (do NOT assume)

- [ ] Confirm both `/app/login` and `/admin/login` authenticate a rep _today_.
- [ ] Confirm `redirectGuestsTo('/admin/login')` vs the intended canonical page.
- [ ] Confirm `throttle:login`, argon2id, `session()->regenerate()`, activity log on the surviving path.
- [ ] After consolidation, delete the losing path entirely (no dead route/method/blade left to re-break).
- [ ] Add the end-to-end rep-login feature test as a regression guard.
