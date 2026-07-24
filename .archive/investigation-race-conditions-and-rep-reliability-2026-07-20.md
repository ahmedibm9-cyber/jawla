# Investigation Case File: race-conditions-and-rep-reliability

> **⚠️ ARCHIVED: 2026-07-24 — All concurrency/money-integrity findings fixed (lockForUpdate, refresh, idempotent guards).**
> See `ISSUES_ARCHIVE.md` (root) for the definitive status.

**Date:** 2026-07-20
**Project:** Jawla — field sales PWA (Laravel 13 / Livewire / Filament / PostgreSQL)
**Reported By:** Owner ("review everything; REP account has flaws — things look broken, actions fail or hang")
**Severity:** Degraded UX today (pre-launch, demo data) → would be **Data Loss / money-integrity** the day real reps go live
**Status:** Open — Stories Created
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-race-conditions-and-rep-reliability-2026-07-20.md`

---

## Summary

**One-sentence description of the issue:**
A systematic sweep of every money/stock service and every REP screen found one confirmed duplicate-invoice race, a recurring "check-then-act without lock" pattern across all four cancel/undo paths, missing overpayment and idempotency guards on the online payment path, and two UI gaps that explain the reported "actions fail or hang" symptom.

**Expected behavior:** Every money/stock mutation is atomic, idempotent under double-tap and network retry, and impossible to apply twice; every failed request tells the rep what happened.

**Actual behavior:** A fast double-tap on the sale confirm button creates two real invoices; concurrent or repeated cancels double-reverse stock and balances; a payment can exceed the invoice remainder or land on a cancelled invoice; a timed-out Livewire request gives the rep no feedback at all (perceived hang) and retrying it duplicates the write.

**User / business impact:** Pre-launch, so no real money is wrong yet. At launch, these become silent inventory drift, wrong customer balances, and duplicate invoices — the exact failures that destroy trust in a sales system. The owner's observed "fails or hangs" symptom is explained by findings E5/E6.

---

## Symptom Details

**Trigger conditions:**

- Double-tap / impatient re-tap on submit buttons (reps on phones, in the field)
- Weak connectivity: request reaches the server but the response is lost, then the rep retries
- Two actors touching the same record (rep's Undo toast vs. admin cancel in Filament; two admins on the same van transfer)
- First-ever sale of a product from a van (stock row doesn't exist yet)

**Environments affected:**

- [x] Production (Railway — demo data)
- [x] Development / local

**First observed:** Owner reports REP flakiness during testing (date unknown); all code findings verified against current `master` (commit `ef4bab9`).
**Frequency:** Intermittent — all are timing/connectivity dependent, which is why they present as "random" flakiness.
**Reproducible:** Not yet reproduced live; every finding below is code-read inference (grade B/C by definition). Each hypothesis includes a concrete reproduction/verification step.

**Reproduction steps (candidate, for H1):**

1. Rep opens `/app/sell`, adds items, opens the confirm modal.
2. Tap "Confirm" twice within ~300 ms (before the Livewire round-trip disables the button).
3. Check `invoices` — expect two invoices with identical items, double stock decrement, double customer-balance increment.

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed, reproducible, or corroborated by 2+ independent sources.
> - **[B] Probable** — single source, code-read inference, or intermittent.
> - **[C] Speculative** — pattern match or untested hypothesis only.

### E1 — Duplicate invoice on double-tap: SalesFlow never clears the cart after submit

**Grade:** [B]
**Source:** code read — `app/Livewire/App/SalesFlow.php:182-241`, `resources/views/livewire/app/sales-flow.blade.php:191-204`
**Description:**
`SalesFlow::submit()` creates the invoice, sets `step = 'done'` — but **never resets `$cart` or `$customerId`**. The confirm button's `wire:loading.attr="disabled"` only takes effect after the first request starts; a second tap queued by Livewire re-runs `submit()` against the still-populated cart and passes every validation gate again. Contrast: `CollectPayment::submit()` (line 97) calls `$this->reset([...])`, so its queued second call fails validation — the guard exists in one flow and is missing in the other.

**Verbatim excerpt:**

```php
$this->createdInvoiceId = $invoice->id;
$this->step = 'done';           // cart and customerId remain populated —
$this->successMessage = ...;    // a queued second submit() passes all checks
```

**Implications:** Two real invoices, two stock decrements, double customer-balance increment from one intended sale. Highest-priority single fix.

---

### E2 — Recurring TOCTOU pattern: every cancel/undo path checks state without a lock

**Grade:** [B]
**Source:** code read — `app/Services/InvoiceService.php:149-232`, `ReturnService.php:72-101`, `PaymentService.php:58-87`, `ExpenseService.php:35-51`, `app/Livewire/App/ActionToast.php:81-103`, `VanTransferService.php:43-134`
**Description:**
None of the four cancel paths re-reads the record with `lockForUpdate()` and re-checks status _inside_ the transaction:

- `InvoiceService::cancelWithoutTransaction()` sets `status = Cancelled` with **no check that it isn't already cancelled** — a second call re-increments stock and re-decrements balance.
- `ReturnService::cancel()`, `PaymentService::cancel()`, `ExpenseService::cancel()` — same shape.
- `ActionToast` (the rep Undo) guards with `whereNull('cancelled_at')` **before** calling the service — a classic check-then-act gap when an admin cancels the same record concurrently in Filament. Its docblock claims "a double-click or replay cannot double-reverse," which the code does not actually guarantee.
- `VanTransferService::ship()/receive()/cancel()/reject()` check status after a plain `findOrFail()` — two concurrent `ship()` calls both read `Pending` and both move stock.

The codebase already contains the correct idiom — `CashReconciliationService::review()` (line 56) does `lockForUpdate()` + status re-check + throw — so this is an omission, not a missing skill.

**Verbatim excerpt:**

```php
// InvoiceService::cancelWithoutTransaction — no status guard, no lock:
$invoice->update(['status' => InvoiceStatus::Cancelled, ...]);
// ...then unconditionally re-increments stock and reverses balance

// CashReconciliationService::review — the correct in-repo pattern:
$fresh = CashReconciliation::whereKey(...)->lockForUpdate()->firstOrFail();
throw_if($fresh->status !== 'pending', ...);
```

**Implications:** Double stock reversal and double balance reversal under concurrent cancel/undo; double stock movement on van transfers. Fix is mechanical: copy the `CashReconciliationService::review()` idiom into all five services.

---

### E3 — Payment path: no overpayment guard, no status guard, stale read during cancel

**Grade:** [B]
**Source:** code read — `app/Services/PaymentService.php:13-56`, `InvoiceService.php:224-228`, `app/Livewire/App/CollectPayment.php:55-99`
**Description:**

1. `collect()` never checks `amount <= remaining_amount` — the UI prefills the remainder but the field is editable; `remaining_amount` can go negative and the invoice flips to `Paid` on an overpayment.
2. `collect()` never checks invoice status — a payment can be applied to a **cancelled** invoice (its `paid_amount`/`remaining_amount` still mutate).
3. `InvoiceService::cancelWithoutTransaction()` computes the unpaid portion from `$invoice->total - $invoice->paid_amount` **without locking the invoice** — a payment landing concurrently makes the balance reversal wrong by the payment amount.
4. The **online** submit path has no idempotency key: if the request times out after the server committed, the rep's retry is a second, separate payment. (The offline path is properly idempotent via `sync_receipts` — E7.)

**Implications:** Customer balances drift; the money-integrity rule "any failure → full rollback, no partial state" holds per-transaction but not across the retry boundary.

---

### E4 — Proforma double-conversion and first-sale stock-row race

**Grade:** [C]
**Source:** code read — `app/Services/InvoiceService.php:106-110`, `StockService.php:78-91`, migration `2026_07_12_100003` (partial unique indexes), `StockService.php:47-73`
**Description:**

1. Converting a proforma does a blind `update(['status' => 'converted_to_invoice'])` — two concurrent conversions of the same proforma both succeed and create two invoices. A conditional update (`where status != converted`) + affected-row check would close it.
2. `StockService::move()`: when no stock row exists, both concurrent first-sales pass the `lockForUpdate()` on zero rows and both `Stock::create()` — the partial unique index correctly kills one, but as an unhandled 500, not a retry or friendly error.
3. `StockService::reconcile()` reads `balance()` and mutates via `firstOrNew()` **without any lock** — a sale between the read and the save is silently overwritten (lost update).

**Implications:** Low-probability but real; #2 surfaces as the "random failure" class the owner reports.

---

### E5 — "Actions hang": no global Livewire request-failure handler

**Grade:** [B]
**Source:** code read — `resources/js/app.js` (no `Livewire.hook` / failure hook anywhere), `resources/views/layouts/app.blade.php`
**Description:**
When a Livewire request fails (timeout, dropped connection, 419 session expiry, 500), nothing tells the rep. The button un-disables and the screen simply doesn't change. Combined with field connectivity, this **is** the reported "actions fail or hang" symptom. There is also no handling for `navigator.onLine === true` but no real internet (captive portal / dead 3G): the online branch fires `$wire.submit()`, which dies silently instead of falling back to the offline outbox.

**Implications:** Every flaky-network tap looks like a frozen app; some retries silently duplicate writes (E1/E3.4).

---

### E6 — Confirm buttons are a silent no-op if the JS bundle fails

**Grade:** [C]
**Source:** code read — `resources/views/livewire/app/sales-flow.blade.php:197` (`window.jawlaSync.enqueue(...)` unguarded), same pattern in payment/return/visit confirm buttons
**Description:**
The offline branch calls `window.jawlaSync` with no existence check. If `app.js` failed to load/parse (bad deploy, cached stale bundle, low-end browser), tapping Confirm while offline throws inside the Alpine handler — visibly nothing happens.

**Implications:** Second contributor to "button does nothing" reports; one-line guard + visible error message fixes it.

---

### E7 — Counter-evidence: several subsystems are already correct

**Grade:** [B]
**Source:** code read — `NumberSequenceService.php` (lock + unique-violation fallback), `StockService::move()` (lock + negative-stock rejection), `sync_receipts` migration + `resources/js/offline/sync.js` (client keys, server dedupe, `applied|duplicate` reconciliation), `CashReconciliationService::review()`, cash_boxes `user_id` unique, stocks partial unique indexes
**Description:** Invoice numbering, the core stock mutation, the entire offline sync pipeline, and reconciliation review are race-safe. The DB constraint layer is a solid last line of defense.

**Implications:** The fixes are targeted patches to known-good in-repo patterns — not an architectural rework. This also **contradicts** any hypothesis that the offline sync queue is the source of duplicates.

---

### E8 — Screen-size fit: code audit is clean; live audit still owed

**Grade:** [C]
**Source:** code read — all 24 rep blades (card-based layouts, no fixed widths >100px outside the default Laravel welcome page, `x-ds.*` components used in 14 views, prior UI-sweep commit `b5860ee` fixed the issue-#7 findings list)
**Description:** Static reading found no obvious overflow risks at 320 px (no wide grids, no unwrapped flex rows of many children, modals sized `calc(100vw - 32px)`). But layout truth requires rendering: **this skill reads code and cannot certify visual fit.** A live browser sweep at 320/375/768/1024/1440 px across rep + admin + all roles is the verification step.

**Implications:** "Things look broken" is not yet localized to a file. Needs a rendered-browser audit (Story 08.3 below) before it can be triaged like the rest.

### Evidence Summary

| #   | Title                                                                 | Grade | Source                      | Key Implication                  |
| --- | --------------------------------------------------------------------- | ----- | --------------------------- | -------------------------------- |
| E1  | SalesFlow keeps cart after submit                                     | B     | SalesFlow.php:182-241       | Double-tap → two real invoices   |
| E2  | TOCTOU in all cancel/undo + van transfer paths                        | B     | 5 services + ActionToast    | Double stock/balance reversal    |
| E3  | Payment: no overpay/status guard, no online idempotency               | B     | PaymentService.php:13-87    | Balance drift; retry duplicates  |
| E4  | Proforma double-convert; stock-row create race; reconcile lost-update | C     | InvoiceService/StockService | Rare failures surface as 500s    |
| E5  | No Livewire failure feedback                                          | B     | app.js, layouts             | The reported "hang" symptom      |
| E6  | `window.jawlaSync` unguarded                                          | C     | sales-flow.blade.php:197    | Silent dead button offline       |
| E7  | Numbering/stock-core/offline-sync are safe                            | B     | multiple                    | Fixes are targeted, not systemic |
| E8  | Responsive: static-clean, live audit owed                             | C     | all rep blades              | Needs rendered-browser sweep     |

---

## Hypotheses

### Hypothesis 1 — Missing post-submit state reset makes the sale flow double-submittable [Plausibility: High]

**Statement:** A double-tap on the sale Confirm button creates two invoices because `SalesFlow::submit()` leaves `$cart`/`$customerId` intact, so the queued second Livewire call passes all validations.

**Supporting evidence:** E1 (B); contrast with CollectPayment's reset at line 97 (B).
**Contradicting evidence:** None identified — Livewire's request queueing serializes the calls but does not prevent the second one.
**Verification step:** Pest test: mount SalesFlow, fill cart, call `submit()` twice in a row → assert exactly one invoice exists. It will fail before the fix and pass after `submit()` gains a reset + an early-return guard (`if ($this->step !== 'cart') return;`).

---

### Hypothesis 2 — The cancel/undo family double-reverses money and stock under concurrency or replay [Plausibility: High]

**Statement:** Because no cancel path re-checks status under `lockForUpdate()`, concurrent (rep Undo vs. admin cancel) or repeated cancels apply the compensating transaction twice.

**Supporting evidence:** E2 (B); the correct idiom exists in-repo (E7, B), showing the divergence is accidental.
**Contradicting evidence:** Filament UI hides cancel actions on already-cancelled records — but that is a UI check, exactly the pattern CLAUDE.md forbids relying on.
**Verification step:** Pest test per service: call `cancel()` twice → second call must throw and leave stock/balance unchanged. Concurrency variant: two parallel transactions (pcntl or sequential with a manual stale model) → exactly one reversal.

---

### Hypothesis 3 — The online write path is not retry-safe, unlike the offline path [Plausibility: High]

**Statement:** A timed-out online submit that actually committed server-side is invisible to the rep (no failure handler, E5) and the natural retry creates a duplicate invoice/payment, because only the offline outbox carries idempotency keys.

**Supporting evidence:** E3.4 (B), E5 (B), E7 (B — proves the team already built the key mechanism for offline).
**Contradicting evidence:** None identified.
**Verification step:** Route the online path through the same idempotency mechanism (client-generated key per confirm-tap, checked against `sync_receipts`), then test: same key twice → one payment, second response returns the original receipt.

---

### Hypothesis 4 — The "looks broken" reports are residual visual defects only findable in a rendered browser [Plausibility: Medium]

**Statement:** Static code shows no remaining layout hazards (prior sweep fixed the catalogued list), so the remaining visual flaws live in rendered reality — real device widths, Arabic line lengths, dynamic states (long names, 99+ badges, empty/error states) — and require a live screenshot audit to enumerate.

**Supporting evidence:** E8 (C); owner symptom report (B — user report).
**Contradicting evidence:** Owner may be describing pre-`b5860ee` builds; the recent UI-fix commit may already have resolved what they saw.
**Verification step:** Browser sweep (agent-browser/Playwright) across every rep + admin screen at 320/375/768/1024/1440 px, both locales, screenshotting each state; file findings back into this case (Update intent).

---

## Suspected Components

### Component: SalesFlow (rep sale wizard)

| Attribute              | Detail                                                                                 |
| ---------------------- | -------------------------------------------------------------------------------------- |
| Type                   | Livewire component                                                                     |
| File / path            | `app/Livewire/App/SalesFlow.php` + `resources/views/livewire/app/sales-flow.blade.php` |
| Responsibility         | Cart → confirm → InvoiceService::create                                                |
| Confidence             | High (E1)                                                                              |
| Architecture reference | rep-in-system-spec — sale flow                                                         |

**Why suspected:** Missing post-submit reset (E1); unguarded `window.jawlaSync` (E6).
**Blast radius:** Duplicate invoices → stock, customer balance, invoice numbering sequence gaps on rollback, ZATCA/ETA reporting of a duplicate document.

### Component: Money-mutation services (cancel family + payments + van transfers)

| Attribute              | Detail                                                                                                     |
| ---------------------- | ---------------------------------------------------------------------------------------------------------- |
| Type                   | Service layer                                                                                              |
| File / path            | `app/Services/{Invoice,Return,Payment,Expense,VanTransfer}Service.php`, `app/Livewire/App/ActionToast.php` |
| Responsibility         | All compensating transactions and transfer state machine                                                   |
| Confidence             | High (E2, E3)                                                                                              |
| Architecture reference | CLAUDE.md non-negotiables — "all money mutations in DB::transaction via a Service"                         |

**Why suspected:** Uniform absence of lock + status re-check; payment guards missing.
**Blast radius:** Every stock quantity, cash box, and customer balance in the system.

---

## Related Requirements

| Requirement                                                                                     | Type | Source                   | Status                                                                                                                                  |
| ----------------------------------------------------------------------------------------------- | ---- | ------------------------ | --------------------------------------------------------------------------------------------------------------------------------------- |
| "A sale creates invoice+items+stock+balance in one transaction; any failure → no partial state" | FR   | CLAUDE.md business rules | At Risk (holds per-transaction, broken across retry boundary)                                                                           |
| "Reversal is a compensating transaction, logged, never delete()"                                | FR   | CLAUDE.md business rules | At Risk (can apply twice)                                                                                                               |
| "No negative van stock, rejected at service layer"                                              | FR   | CLAUDE.md business rules | Held (StockService enforces) — but silently skipped when a rep has **no van warehouse** (`if ($vanWarehouse)` in Invoice/ReturnService) |
| "RTL Arabic + LTR English work everywhere; every list paginated; bilingual errors"              | NFR  | CLAUDE.md                | At Risk pending live audit (E8)                                                                                                         |

---

## Recommended Action

**Planning Response:** Option A — three fix stories, drafted now.

| Field                   | Value                                                                                                                                                                                                                                               |
| ----------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Epic                    | 08 — Launch-hardening (race conditions + rep reliability)                                                                                                                                                                                           |
| Stories                 | `08.1.concurrency-hardening.story.md` (H1+H2+E3+E4), `08.2.rep-action-reliability.story.md` (H3+E5+E6), `08.3.live-ui-audit.story.md` (H4 — audit story, produces findings, not fixes), `08.4.price-bounds-negotiation.story.md` (Open Q2 decision) |
| Investigation reference | this file                                                                                                                                                                                                                                           |

Sequencing: 08.1 first (pure backend, testable), then 08.2 and 08.4 (same flow surface), 08.3 anytime (independent, read-only audit).

---

## Open Questions

All four resolved by the owner on 2026-07-20:

1. **Van-less rep sales:** RESOLVED — "the rep makes deals based on availability of the product in the stock warehouses." Encoded in Story 08.1 AC 5 as: sales must always be stock-backed; a rep with no van warehouse is blocked with a bilingual error (assumption "block rather than fall back to main warehouse" flagged in that story's Dev Notes).
2. **Rep price editing:** RESOLVED — sales manager sets catalog + min/max price; rep negotiates within the range. → Story 08.4.
3. **Overpayment policy:** RESOLVED — accept it; surplus becomes customer credit; invoice caps at fully paid (remainder never negative). → Story 08.1 AC 4.
4. **Expense over-spend:** RESOLVED — deferred by owner ("keep for further improvements"). No floor check; current behavior stands. Logged as a future-improvement candidate, out of scope for epic 08.

---

## Update History

| Version | Date       | Summary of Changes              |
| ------- | ---------- | ------------------------------- |
| 1.0     | 2026-07-20 | Initial investigation case file |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
