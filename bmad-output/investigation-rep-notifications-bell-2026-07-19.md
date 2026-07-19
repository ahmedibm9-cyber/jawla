# Investigation Case File: rep-notifications-bell

**Date:** 2026-07-19
**Project:** Jawla (جولة) — Field Sales CRM/ERP
**Reported By:** Owner — issue backlog item `bmad-output/issues/05-rep-notifications-bell.md` (gap M6 from `investigation-missing-ui-elements-2026-07-19.md`)
**Severity:** Degraded UX / Missing must-have functionality (AM4 in-app coverage clause of the Amendment unmet)
**Status:** Open — Ready for Story
**Case File Version:** 1.0
**Investigation Case File:** `bmad-output/investigation-rep-notifications-bell-2026-07-19.md`

---

## Summary

**One-sentence description of the issue:**
Reps have no in-app way to learn the outcome of anything they submit — quotation pricing, customer approval/rejection (B2-05 notify clause), complaint resolution (B6-03), or out-of-stock resolution — because no rep-facing notification surface, delivery mechanism, or sender hooks exist anywhere in the codebase.

**Expected behavior:** Per the Amendment (push deferred to v1.1 _because_ "in-app alarm bell + red indicators" cover AM4 in beta): a bell icon with an unread badge in the rep app, a paginated `/app/notifications` page, and exactly one notification to the correct same-company rep for each of the four outcome events.

**Actual behavior:** No bell, no badge, no notifications page, no per-rep notification records, and none of the four source flows notifies the submitting rep on outcome.

**User / business impact:** Every rep must phone or message a manager to learn whether a quotation was priced, a new customer was approved, or a complaint was closed — the exact friction AM4 was raised to remove. Blocks the Definition-of-Beta-Done walkthrough steps that depend on the rep seeing outcomes in-app.

---

## Symptom Details

**Trigger conditions:** Structural — the gap is always present; confirmed by static inventory (no runtime reproduction needed).

**Environments affected:**

- [x] Production
- [x] Staging
- [x] Development / local

**First observed:** 2026-07-19 (UI-completeness audit, Evidence 7 / gap M6)
**Frequency:** Constant (code-level absence)
**Reproducible:** Yes

**Reproduction steps:**

1. Log in as a rep, submit a quotation request from `/app/quotations`.
2. As admin/accounts, run the `set_price` action on the request in Filament.
3. Observe the rep app: no indicator anywhere that the request was priced; the rep discovers it only by manually reopening `/app/quotations`.

---

## Evidence

> **Grading Key**
>
> - **[A] Confirmed** — directly observed, reproducible, or corroborated by 2+ independent sources.
> - **[B] Probable** — single source, code-read inference, or intermittent.
> - **[C] Speculative** — pattern match or untested hypothesis only.

### Evidence Item 1: No persistent notification mechanism exists for reps

**Grade:** [A]
**Source:** `Glob database/migrations/*notification*` → no files; `app/Models/User.php:15,22`; grep `Notification` across `app/`
**Description:**
`User` already uses the `Illuminate\Notifications\Notifiable` trait, but there is **no `notifications` table migration**, so the database notification channel is unusable as-is. The 8 files that reference "Notification" (`AlarmResource`, `StockImport`, `ActivityLog`, `VanTransferResource`, `StockResource`, `Login`, `User`, `CollectPayment`) use Filament's transient toast `Notification` for admin session feedback — nothing persistent, nothing rep-visible.

**Implications:** The issue's preferred vehicle (Laravel database notifications, no new package) needs only the framework's stock migration (`php artisan make:notifications-table`) plus Notification classes — the trait is already in place.

---

### Evidence Item 2: Rep app shell has no header region and no bell anywhere

**Grade:** [A]
**Source:** `resources/views/layouts/app.blade.php:17-19`; `resources/views/livewire/app/home.blade.php:3-19`; prior audit Evidence 7 (grep `Alarm|notification` in `app/Livewire` + layouts → 0 hits)
**Verbatim excerpt:**

```blade
<body>
  <a href="#main" class="skip-link">{{ __('app.skip_to_content') }}</a>
  <main id="main">{!! $slot !!}</main>
```

**Description:** The layout renders pages directly into `<main>` with **no shared header bar**; each page draws its own hero (e.g. `home-hero` in home.blade.php). The issue calls for a "bell in `layouts/app.blade.php` header" — that header does not exist yet.

**Implications:** The story must either add a small persistent header strip to the layout (bell available on every page, matches the issue text) or mount the bell inside each page hero (more churn, inconsistent). Layout-level is the smaller, spec-aligned change; the unread count needs a Livewire component (or layout-level query) so it updates per navigation.

---

### Evidence Item 3: Alarm domain exists but is admin-only by design

**Grade:** [A]
**Source:** `app/Services/AlarmService.php:69-86`; `app/Filament/Resources/AlarmResource.php`
**Verbatim excerpt:**

```php
$roleMap = [
    'out_of_stock_request' => ['accounts', 'sales_manager', 'executive'],
    'customer_complaint' => ['sales_manager'],
    'new_customer_pending' => ['sales_manager'],
    'price_quotation_requested' => ['sales_manager'],
    'purchase_request' => ['sales_manager', 'purchasing'],
];
```

**Description:** `AlarmService::raise()` fans out `alarm_reads` rows to **admin-panel roles only** — `rep` never appears in the role map — and alarms are surfaced exclusively through the Filament `AlarmResource`. Alarms model company-wide role broadcasts ("someone in Finance must act"), not per-user outcome messages ("_your_ quotation was priced").

**Implications:** Confirms the issue's recommendation: do **not** overload alarms for rep notifications. Per-user, per-event Laravel notifications are the semantically correct fit; the alarm domain stays untouched.

---

### Evidence Item 4: Quotation flow — outcome event exists but notifies no one, and the real statuses differ from the issue wording

**Grade:** [A]
**Source:** `app/Filament/Resources/PriceQuotationRequestResource.php:81-103`; `app/Livewire/App/QuotationFlow.php:79-80`
**Description:** The admin `set_price` action (visible to admin/accounts) updates the request inline: `$r->update(['status' => 'priced'])` — no service, no notification. The rep later opens `/app/quotations` and confirms (`status → 'confirmed'`) themselves. So the actual lifecycle is `requested → priced → confirmed/cancelled`; there is no "approved/rejected" status pair as the issue text says.

**Implications:** The rep-facing notification event is **"quotation priced"** (and possibly "cancelled"), not "approved/rejected". The story ACs must use the real statuses. Sender hook point: the `set_price` action closure (or an extracted service method).

---

### Evidence Item 5: Customer approval — B2-05 notify clause violated at the hook point

**Grade:** [A]
**Source:** `app/Filament/Resources/CustomerResource.php:149-177`
**Description:** `approve` and `reject` are inline Filament actions writing `status`, `approved_by/rejected_by`, timestamps, and `rejection_reason` directly to the model. Nothing notifies the rep who created the customer. B2-05 explicitly requires notifying the submitting rep, with the rejection reason.

**Implications:** Hook point is these two action closures. The `rejection_reason` captured in the form (line 166-167) must be carried into the rejection notification payload.

---

### Evidence Item 6: Complaint resolution — resolve() is silent, unlike log()

**Grade:** [A]
**Source:** `app/Services/ComplaintService.php:39-51` (contrast `log()` at 27-33)
**Description:** `ComplaintService::log()` raises a `customer_complaint` alarm to sales_manager, but `resolve()` only updates the complaint row (`status`, `assigned_to`, `resolution`, `resolved_at`). The submitting rep (`$complaint->user_id`) is never told, violating the B6-03 notify clause.

**Implications:** Cleanest hook of the four — `resolve()` is already a service method inside a transaction; add the notification send there. `user_id` on the complaint identifies the recipient.

---

### Evidence Item 7: Out-of-stock "resolved" event cannot fire yet — no resolve path exists

**Grade:** [A]
**Source:** grep `OutOfStockRequest` across `app/` → only `OutOfStockService` (raise-only), its contract, and the model; `AlarmResource` renders the alarm type but has no request-status action
**Description:** The new `OutOfStockService` (issue #2 work, uncommitted) implements `raise()` with idempotency, but no code path ever moves an `OutOfStockRequest` from `open` to resolved. The fourth notification event in issue #5 ("out-of-stock resolved → flagging rep") therefore has no trigger to hook into.

**Implications:** Matches the issue's dependency note ("benefits from #2"). The story should ship the Notification class for this event but wire it only when #2's resolve flow lands — or the AC scopes to the three live events with the fourth marked dependent.

---

### Evidence Summary

| #   | Title                                                                                  | Grade | Source                                   | Key Implication                                                      |
| --- | -------------------------------------------------------------------------------------- | ----- | ---------------------------------------- | -------------------------------------------------------------------- |
| 1   | No persistent notification mechanism; Notifiable trait present, table migration absent | [A]   | migrations glob, User.php:22             | Stock Laravel notifications migration + classes is all that's needed |
| 2   | No rep header/bell surface; layout is `<main>` only                                    | [A]   | layouts/app.blade.php:17-19              | Story must add a layout-level header strip for the bell              |
| 3   | Alarm domain is admin-role broadcast; reps never recipients                            | [A]   | AlarmService.php:69-86                   | Use Laravel notifications; leave alarms untouched                    |
| 4   | Quotation `set_price` is inline + silent; real statuses are priced/confirmed/cancelled | [A]   | PriceQuotationRequestResource.php:81-103 | Event = "priced"; fix issue wording in ACs                           |
| 5   | Customer approve/reject inline actions notify no one (B2-05)                           | [A]   | CustomerResource.php:149-177             | Hook the two action closures; carry rejection_reason                 |
| 6   | ComplaintService::resolve() silent (B6-03)                                             | [A]   | ComplaintService.php:39-51               | Cleanest hook — already a service transaction                        |
| 7   | Out-of-stock resolve path doesn't exist                                                | [A]   | grep OutOfStockRequest                   | Fourth event blocked by issue #2                                     |

---

## Hypotheses

_This is a feature-gap investigation; hypotheses explain why the gap exists and what the correct construction is._

### Hypothesis 1 — The notification surface was never built because the alarm domain was scoped admin-only and nothing else claimed the rep side [Plausibility: High]

**Statement:** Alarms were built to satisfy the admin half of AM4 (role broadcasts into Filament), and the rep half ("bell + red indicators") was deferred implicitly along with push, leaving no per-user outcome channel at all.

**Supporting evidence:**

- AlarmService role map contains no `rep` (Evidence 3, [A])
- Zero notification/bell references in rep Livewire pages or layout (Evidence 2, [A])
- Amendment text defers _push_ only, explicitly keeping the in-app bell in beta scope (issue file, [A])

**Contradicting evidence:**

- None identified.

**Verification step (for the dev agent):**
None needed — gap is structural and confirmed. Build per the story.

---

### Hypothesis 2 — Laravel database notifications is the correct vehicle and is one migration away from usable [Plausibility: High]

**Statement:** Because `User` already has `Notifiable`, adding the framework's `notifications` table migration plus four Notification classes (database channel) delivers per-user, read-tracked, paginated notifications with zero new packages — exactly the issue's stated preference — while keeping the alarm domain single-purpose.

**Supporting evidence:**

- `Notifiable` already on User (Evidence 1, [A])
- No notifications migration exists (Evidence 1, [A])
- Alarm schema is company-broadcast shaped, wrong for per-user read state without overloading `alarm_reads` (Evidence 3, [A])

**Contradicting evidence:**

- The stock `notifications` table has no `company_id`; company scoping must come from targeting the correct user (notifiable), backed by a cross-company feature test ([B] — design consideration, not a blocker).

**Verification step (for the dev agent):**
`php artisan make:notifications-table && php artisan migrate` in dev; assert `$rep->notifications()->paginate()` works, and write the cross-company isolation test before wiring senders.

---

### Hypothesis 3 — Pull-based visibility in QuotationFlow masked the gap [Plausibility: Medium]

**Statement:** Because reps _can_ see priced quotations by manually opening `/app/quotations` (and confirm there), the flow appeared "done" in earlier phase checks, hiding the absence of proactive outcome delivery for all four events.

**Supporting evidence:**

- QuotationFlow confirm path exists and works (Evidence 4, [A])
- Customer rejection reason and complaint resolution have **no** rep-visible surface at all, yet were not flagged until the UI audit ([B] — inference)

**Contradicting evidence:**

- None identified.

**Verification step (for the dev agent):**
None — informational; explains why only the bell (not the flows) needs building.

---

## Suspected Components

_These are the build sites, mapped from the evidence._

### Component: Rep PWA shell (layout header + notifications page)

| Attribute              | Detail                                                                                                                                                         |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Type                   | UI module                                                                                                                                                      |
| File / path            | `resources/views/layouts/app.blade.php`, new `app/Livewire/App/Notifications.php` + view, new bell Livewire component, `routes/web.php` (`/app/notifications`) |
| Responsibility         | Persistent bell + unread badge on every rep page; paginated, mark-read-on-open notifications list                                                              |
| Confidence             | High (grade-A inventory)                                                                                                                                       |
| Architecture reference | Rep PWA group in `routes/web.php:67-86`                                                                                                                        |

**Why suspected:** Evidence 2 — no header exists; the bell needs a layout-level mount to appear on all 13 rep pages without touching each hero.

**Blast radius:** Layout change touches every rep page render (visual regression risk on RTL/LTR); new route inside the existing `ensure.rep` middleware group; translations in `lang/en/app.php` + `lang/ar/app.php`; must use `x-ds-*` components per B0 (note gap M7/G1 — don't add another native-dialog page).

---

### Component: Outcome-event sender hooks (three live + one dependent)

| Attribute              | Detail                                                                                                                                                                                                                                                                                         |
| ---------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Type                   | Service + Filament action closures                                                                                                                                                                                                                                                             |
| File / path            | `app/Filament/Resources/PriceQuotationRequestResource.php:81-103` (`set_price`), `app/Filament/Resources/CustomerResource.php:149-177` (`approve`/`reject`), `app/Services/ComplaintService.php:39-51` (`resolve`), `app/Services/OutOfStockService.php` (future resolve, blocked by issue #2) |
| Responsibility         | Emit exactly one database notification to the submitting rep on each outcome                                                                                                                                                                                                                   |
| Confidence             | High (grade-A hook points)                                                                                                                                                                                                                                                                     |
| Architecture reference | Services layer; CLAUDE.md service rules                                                                                                                                                                                                                                                        |

**Why suspected:** Evidence 4–7 pinpoint the four emission sites; three are live, one has no trigger yet.

**Blast radius:** `set_price` and customer approve/reject are inline Filament closures — adding sends there is low-risk but untested today; consider extracting to small service methods so the "exactly one notification" AC is unit-testable. Complaint resolve is already transactional — the send should go after commit (or via `afterCommit`) so a rolled-back resolution never notifies. Recipient derivation: quotation `user_id` (requesting rep), customer `added_by` (confirmed, Customer.php:20), complaint `user_id`, out-of-stock `user_id`.

---

## Related Requirements

| Requirement                                                                | Type | Source                              | Status                                                 |
| -------------------------------------------------------------------------- | ---- | ----------------------------------- | ------------------------------------------------------ |
| AM4 in-app coverage — "in-app alarm bell + red indicators" in beta         | FR   | Amendment 1.2 (push→v1.1 rationale) | **Violated**                                           |
| B2-05 — notify submitting rep of customer approval/rejection (with reason) | FR   | Master Plan                         | **Violated** (Evidence 5)                              |
| B6-03 — notify submitting rep of complaint resolution                      | FR   | Master Plan                         | **Violated** (Evidence 6)                              |
| B6-01 — quotation outcome visibility to requesting rep                     | FR   | Master Plan                         | Violated (pull-only, Evidence 4)                       |
| REQ-CMP-5 / B0 — standard UI states on new page                            | NFR  | PRD v1.1 §2                         | At Risk (gap G1 pattern — new page must not repeat it) |
| A11y — `aria-live="polite"` badge, button + aria-label, RTL                | NFR  | Issue #5 / design system            | At Risk (to be met by story)                           |

---

## Recommended Action

**Planning Response:** Option A — Create a Fix Story

### Option A — Create a Fix Story

| Field                                | Value                                                                                                                                                                                                                                      |
| ------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Epic                                 | Issue backlog #5 (spans B2/B6 notify clauses + AM4 UI)                                                                                                                                                                                     |
| Story title                          | Rep in-app notifications bell + outcomes feed                                                                                                                                                                                              |
| As a                                 | Sales rep                                                                                                                                                                                                                                  |
| I want                               | A bell with an unread badge on every rep page and a notifications list                                                                                                                                                                     |
| So that                              | I learn quotation, customer-approval, and complaint outcomes without calling my manager                                                                                                                                                    |
| Suggested AC 1                       | Quotation priced, customer approved, customer rejected (incl. reason), complaint resolved → exactly one database notification to the submitting rep of the same company; cross-company and cross-rep leakage covered by failing-path tests |
| Suggested AC 2                       | Bell + accurate unread badge (`aria-live="polite"`) on all rep pages; `/app/notifications` paginated, mark-read on open persists; bilingual + RTL; uses `x-ds-*` states                                                                    |
| Suspected files / modules            | See Suspected Components above                                                                                                                                                                                                             |
| Verification steps (from hypotheses) | H2 verification: create notifications migration first, cross-company isolation test before senders                                                                                                                                         |
| Investigation reference              | `bmad-output/investigation-rep-notifications-bell-2026-07-19.md`                                                                                                                                                                           |

Story drafted at `bmad-output/stories/05.1.rep-notifications-bell.story.md`, status `ready-for-dev`.

---

## Open Questions

1. **Quotation event semantics:** issue says "approved/rejected", but the real lifecycle is `requested → priced → confirmed/cancelled` (Evidence 4). Assumed for the story: notify on **priced** (info) and **cancelled** (warning). Confirm with owner.
2. **Out-of-stock resolved event:** no resolve path exists (Evidence 7). Story scopes it as "wire when issue #2 lands" — confirm this sequencing or pull #2's resolve flow forward.
3. **Red dot "only for critical":** which of the four events count as critical? Assumed: customer **rejected** and quotation **cancelled** (negative outcomes) show the red dot; others increment the badge only.
4. ~~Customer→rep linkage column~~ **Resolved during investigation:** `customers.added_by` (`app/Models/Customer.php:20`) records the creating rep — use it as the recipient for approval/rejection notifications.

---

## Update History

| Version | Date       | Summary of Changes              |
| ------- | ---------- | ------------------------------- |
| 1.0     | 2026-07-19 | Initial investigation case file |

---

_BMAD Planning & Orchestrator · Investigation Case File · tracks `bmad-investigate` from the BMAD Method by the BMAD Code Organization (https://github.com/bmad-code-org/BMAD-METHOD)_
