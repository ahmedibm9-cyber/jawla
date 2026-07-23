# Jawla — Full-Stack Audit Report

**Date:** 2026-07-20 · **Commit audited:** `ef4bab9` (master) · **Method:** static code review + dependency audits (no live pen-test, no rendered-browser pass — see Limitations)
**Companion documents:** `investigation-race-conditions-and-rep-reliability-2026-07-20.md` (concurrency findings, not repeated here), stories `08.1`–`08.4`

**Direct answer to the owner's question:** No — the earlier investigation was scoped to race conditions and rep reliability. This wider pass found **1 additional High, 8 Medium, and 7 Low/Info** findings, plus one discovery (SW-1) that likely explains the original "looks broken / actions hang" reports better than anything found before. It also confirmed a long list of things that are genuinely done right. What static review cannot prove is listed honestly at the end.

---

## Severity index (fix in this order)

| #   | ID     | Finding                                                                                                             | Domain            | Severity                           |
| --- | ------ | ------------------------------------------------------------------------------------------------------------------- | ----------------- | ---------------------------------- |
| 1   | SW-1   | Service worker serves cached pages forever (stale app after deploy + pages readable after logout)                   | Frontend/Security | **High**                           |
| 2   | —      | Entire epic 08 backlog (double-submit, cancel-twice, online idempotency)                                            | Backend           | High (already storied)             |
| 3   | SEC-1  | Guzzle < 7.15.1 — 3 published advisories                                                                            | Security/Deps     | Medium                             |
| 4   | AUTH-1 | No password policy anywhere (`Password::defaults` never configured; admin user form has a bare `password` field)    | AuthN             | Medium                             |
| 5   | AUTH-2 | No MFA option for admin/finance/manager accounts                                                                    | AuthN             | Medium                             |
| 6   | OPS-1  | Prod logs go to daily files on an ephemeral Railway filesystem — lost on every redeploy                             | Backend/Ops       | Medium                             |
| 7   | OPS-2  | No backup tooling in the repo; Railway-side backup status unverified                                                | CI/CD/Ops         | Medium (until verified)            |
| 8   | SEC-2  | `exists:customers,id` validation bypasses the company scope (raw DB check)                                          | Security          | Medium                             |
| 9   | UX-1   | Collect-payment customer dropdown hard-caps at 100 customers, no search                                             | UI/UX             | Medium                             |
| 10  | OPS-3  | APP_DEBUG defaults to `true` in `.env.example`; production value on Railway unverified                              | Security/Ops      | Medium (until verified)            |
| 11  | SEC-3  | Sync sale handler doesn't verify `visit_id` ownership                                                               | Security          | Low                                |
| 12  | CI-1   | `composer audit` / `npm audit` run with `                                                                           |                   | true` — never fail the build       | CI/CD | Low |
| 13  | CI-2   | Weekly ZAP scan targets `https://staging.jawla.app` — does that environment exist?                                  | CI/CD             | Low                                |
| 14  | SEC-4  | CSP allows `unsafe-inline` + `unsafe-eval`                                                                          | Security          | Low (accepted for Livewire/Alpine) |
| 15  | SEC-5  | `Visit`, `VisitReport`, `Batch`, item models lack the company scope (mitigated by ownership checks / parent access) | Security          | Low (hardening)                    |
| 16  | UX-2   | SW "API/other" branch caches authenticated GET JSON into Cache Storage                                              | Frontend          | Low                                |
| 17  | OPS-4  | `QUEUE_CONNECTION=database` but zero `ShouldQueue` jobs — dead config                                               | Backend           | Info                               |

---

## 1. Security

### SW-1 (shared with Frontend) — cache-first navigations never revalidate — **High**

`public/sw.js` (fetch handler, navigation branch): once a page is cached, it is served from cache **forever** — the network is never consulted again until the `jawla-shell-v5` constant is manually bumped.

Consequences, in order of severity:

1. **Stale app after every deploy.** Cached HTML references old hashed Vite bundles and old Livewire snapshots. After a deploy, reps run last week's UI against this week's backend → broken-looking screens, failed/419 Livewire posts with no feedback. **This is the most credible root cause of the owner's "things look broken / actions fail or hang" reports** — it matches the "random, per-device, per-time" flavor of the complaints (each device breaks differently depending on what it cached and when).
2. **Pages readable after logout.** A logged-out (or expired) session still gets fully rendered cached pages — customer names, balances, invoices — with zero network request. Auth middleware never runs. On a shared/stolen device this is sensitive-data exposure (OWASP A01/A07 flavored).
3. Stale CSRF tokens in cached pages → first POST from a cached page 419s.

**Remediation:** switch navigations to **network-first with cache fallback** (the offline fallback already exists); cache-bust on deploy (inject build hash into `CACHE`); on `logout`, `postMessage` the SW to purge caches. Repro: log in on device A, deploy any change, revisit any previously-visited page — old build renders; or logout, then navigate back to `/app/customers` with DevTools offline.

### SEC-1 — vulnerable dependency — **Medium**

`composer audit` (run 2026-07-20): `guzzlehttp/guzzle` < 7.15.1 — 3 medium advisories (host-only cookie scope not preserved; unbounded response cookies DoS; +1). **Fix:** `composer update guzzlehttp/guzzle` (used by the ETA HTTP client). `npm audit`: 0 vulnerabilities.

### SEC-2 — tenant-scope bypass in validation rules — **Medium**

`CollectPayment` validates `customer_id => exists:customers,id` — Laravel's `exists` rule queries the table directly, **ignoring the `BelongsToCompany` global scope**. A hostile rep can submit another company's customer id and pass validation. Today the downstream scoped `Customer::findOrFail` inside the transaction throws and rolls back (accidental defense), so no cross-company write lands — but the guard is luck, not design, and the same rule pattern may exist elsewhere. **Fix:** use `Rule::exists('customers','id')->where('company_id', auth()->user()->company_id)` (audit all `exists:` rules in Livewire components + Form Requests + sync handlers).

### SEC-3 — sync sale accepts foreign `visit_id` — **Low**

`SaleSyncHandler` verifies the customer belongs to the rep's company but attaches `visit_id` unchecked — a crafted sync payload can link a sale to another rep's visit (data-integrity, not disclosure). **Fix:** assert the visit belongs to the rep, as `VisitFlow::mount()` already does.

### SEC-4 — CSP allows `unsafe-inline`/`unsafe-eval` — **Low (accepted)**

`SecurityHeaders.php:20`. Required by Livewire/Alpine today; revisit with nonces post-launch. Everything else in the header set is right (HSTS, nosniff, DENY, Referrer-Policy).

### SEC-5 — unscoped models — **Low (hardening)**

`Visit`, `VisitReport`, `Batch`, and child item models (`InvoiceItem`, `ReturnItem`, …) lack `BelongsToCompany`. Every current access path is guarded (ownership checks or via scoped parents), but any future direct query is one forgotten `where` away from a leak. **Fix:** add the trait where a `company_id` column exists; add a company-isolation Pest test per model.

### Verified-good (evidence, not vibes)

Argon2id hashing · sessions httpOnly/secure-in-prod/regenerated on login + invalidated on logout · login throttle 5/min per email+IP, POST throttle 60/min per user, API throttle per token — all match spec · no `exec/eval/shell_exec` anywhere · no `{!! !!}` on user data (2 usages, both static template slots) · `.env` untracked · PDF routes check company **and** owner · photo uploads validated (image/mimes/5MB) and ownership-checked on attach · 21 model policies + Filament panel gate · Sanctum API scoped per token ability + company context · sync envelope validated, per-op payload validated, customer company-asserted · security headers middleware registered globally.

---

## 2. Frontend

- **SW-1** above is the dominant frontend finding.
- **UX-2 / Low:** the SW's catch-all branch caches authenticated GET responses (JSON, PDFs) into Cache Storage; combine the SW-1 fix with `Cache-Control: no-store` on PDF/API responses.
- **State management:** the double-submit/cart-reset bugs are already storied (08.1). No other state bugs found by reading; Alpine `setInterval` leak from the old audit is fixed (visit-flow.blade.php:61 clears it).
- **Cross-browser/rendering/perf:** not verifiable statically — needs the 08.3 rendered audit (320→1440 px, AR+EN, throttled CPU/network). Vite-hashed assets + preconnected fonts + skeleton states are in place; no render-blocking findings in code.

## 3. Backend

- Concurrency: fully covered in the companion case file + story 08.1 (double-cancel family, payment guards, proforma double-convert, stock-row race, reconcile lost-update). Nothing new found this pass.
- **OPS-1 / Medium:** `config/logging.php` defaults production to `json-daily` — files under `storage/logs` on Railway's **ephemeral** filesystem vanish on redeploy/restart. You currently lose your production audit trail of errors. **Fix:** `LOG_CHANNEL=stderr` (JSON formatter) on Railway so logs land in Railway's log drain.
- **OPS-4 / Info:** database queue configured, zero queued jobs — either remove the config noise or start queueing the heavy work (PDF generation runs inline in web requests today; fine at current scale, queue it when invoices/day grows).
- Error handling: money paths correctly transactional; Livewire flows catch `Throwable` and surface bilingual messages; sync returns per-op statuses. Gap: caught exceptions in rep flows are shown but **not logged** (`SalesFlow.php:219`, `CollectPayment.php:74` swallow silently) — add `report($e)` so production failures are visible. **Low.**
- N+1: `preventLazyLoading` active outside prod; no violations found in rep components (eager loads present).

## 4. CI/CD Pipeline

- **Working:** GitHub Actions with Postgres 16 service, Pint + full Pest suite on every push/PR, composer/npm audit steps, weekly scheduled ZAP DAST workflow. This is more than most projects this size have.
- **CI-1 / Low:** both audit steps end in `|| true` — a critical CVE would print and pass. Make `composer audit` blocking (keep npm advisory-level threshold).
- **CI-2 / Low:** ZAP targets `https://staging.jawla.app` — if no staging environment answers there, the weekly scan has been silently useless. Point it at a real staging URL or the production domain with safe policy.
- **OPS-2 / Medium until verified:** no backup tooling in the repo and no restore drill documented. Railway Postgres has platform backups on some plans — **verify in the Railway dashboard that automated backups are ON and do one test restore before launch.** docs/ specs backups; the implementation is the gap.
- **OPS-3 / Medium until verified:** `.env.example` ships `APP_DEBUG=true` (line 29). Confirm the Railway production env sets `APP_DEBUG=false` and `APP_ENV=production` (debug pages leak config/queries/paths). One `railway variables` check settles it.
- Deployment: no migration/rollback runbook found — document "deploy = migrate + release" order and how to roll back a bad migration (Railway redeploy alone won't un-migrate).

## 5. Authentication / Authorization

- **AUTH-1 / Medium:** no `Password::defaults()` configured and the Filament user form's password field (`UserResource.php:55`) carries no strength rule — an admin can set `123` as a rep's password. **Fix:** `Password::defaults(fn () => Password::min(10)->uncompromised())` in a provider + apply to the Filament field and any reset path.
- **AUTH-2 / Medium:** no MFA anywhere. For the panel that moves money (finance/manager/admin), add TOTP 2FA (Filament has first-party support) — reps on PWA can stay password-only for launch if you accept the risk.
- **Verified-good:** role gate on rep login (active + `rep` role, generic error either way — no user enumeration), session regeneration, rep/admin login separation, Filament `canAccessPanel` + per-resource policies, Sanctum abilities per endpoint, privilege escalation not possible via mass assignment (`$fillable` everywhere, no `->all()` writes found).
- Session lifetime is Laravel's default 120 min idle — fine; consider `expire_on_close=false` + remember-me policy explicitly per your field-usage pattern.

## 6. UI / UX

- **UX-1 / Medium:** `CollectPayment::render()` loads `limit(100)` customers into a plain dropdown — customer #101 can never receive a payment, and scrolling 100 names on a phone is painful. Reuse the search-as-you-type pattern SalesFlow already has.
- The full WCAG/RTL/touch-target/visual pass **cannot be done from code** — that is story `08.3` (matrix: 24 rep screens + Filament, 5 widths, 2 locales, 5 states each). Prior sweeps fixed everything statically catalogable (aria-labels, label/for, skip-link, `text-start`, tabular-nums, skeleton/empty states, bilingual confirm modals).
- Error messaging: bilingual and present in flows; add the global failure toast (story 08.2) to close the silent-failure class.

---

## Priority order

1. **SW-1** — small change, kills the biggest class of field complaints and a data-exposure hole. Do this before anything else; it also invalidates part of the "looks broken" reports, so do it **before** running the 08.3 visual audit.
2. **Story 08.1** (money integrity) → **08.2** (retry-safety + feedback).
3. SEC-1 (composer update), AUTH-1, OPS-1, OPS-3 verify, OPS-2 verify — each under an hour.
4. SEC-2 rule audit, UX-1, AUTH-2, CI-1/CI-2, 08.4 price bounds.
5. **Story 08.3** rendered audit (after SW-1 + a deploy, on fresh caches).
6. Low/hardening items.

## Limitations — what this review could NOT establish

| Gap                                                              | Why                                                      | What's needed                                                                                                                       |
| ---------------------------------------------------------------- | -------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| Runtime behavior (rendering, WCAG, touch targets, cross-browser) | Static review only                                       | Story 08.3: agent-browser/Playwright matrix + axe-core scan                                                                         |
| Actual exploitability (OWASP validation)                         | No pen-test performed                                    | ZAP active scan against a real staging env + one manual pen-test pass (authz matrix per role, IDOR fuzzing) before real money flows |
| Race conditions under real load                                  | Code-read inference (grade B)                            | The Pest concurrency tests specified in 08.1 + a k6/Artillery smoke at ~50 concurrent reps                                          |
| Production config (APP_DEBUG, LOG_CHANNEL, backups)              | Lives in Railway, not the repo                           | 15-minute Railway env + backup verification (I can do this on request — read-only)                                                  |
| ETA/ZATCA compliance of the signing seam                         | `UnsignedEtaSigner` is a stub by design (B1 in progress) | Complete B1 with real signing + ETA sandbox round-trip                                                                              |
| Human usability (flow friction, Arabic copy quality)             | Not testable by any tool                                 | 2–3 real reps doing a full field day on the pilot                                                                                   |

---

_Full-stack audit · complements `/bmad-investigate` case file of the same date · BMAD Planning & Orchestrator_
