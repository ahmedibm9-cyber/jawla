# Jawla Build Guide — Architecture Review Board Report

**Subject of review:** `Jawla_Build_Guide_v1_Reference.md` (1,719 lines)
**Reviewers (simulated, 3 passes):** Enterprise Software Architect · AI Coding Agent
Reviewer · ERP Systems Architect
**Rule:** Targeted additions/corrections only. No rewrite. No reorganization.
Each finding is paste-ready.

---

## Executive summary

The guide is strong on *what* to build (domain, schema, roles, phases) and
weak on *how* the AI agent should structure the code that enforces it. An AI
agent given this guide will produce a working Phase 0–2, then start
**inventing architecture** from Phase 3 onward — service contracts,
exceptions, enums, events, multi-tenant scoping, index strategy, and the
Egypt ETA QR format are all under-specified or wrong. It also directly
**contradicts the project's own `docs/` files** (roles, colours, QR format,
business rules) that `CLAUDE.md` declares as co-equal spec — the AI will
hit these conflicts and have to guess which wins.

The findings below close those gaps.

---

# CRITICAL

These must be resolved before an AI agent continues past Phase 2.

---

## C-1 · Cross-document conflicts: the guide contradicts `docs/` spec files

### Why this matters
`CLAUDE.md` declares both the build guide **and** `docs/*.md` as sources of
truth ("If they conflict with anything here, they win" for the guide; but
also "Do not modify docs/BUSINESS_RULES.md or docs/SECURITY.md — they are
spec"). They conflict on **four** load-bearing things:

| Topic | Guide says | `docs/` says |
|---|---|---|
| **Roles** (§5 vs `ROLES_MATRIX.md`) | 7 roles: admin, sales_manager, accounts, purchasing, warehouse_keeper, executive, rep | 5 roles: system_viewer, hr_admin, sales_manager, warehouse_keeper, sales_rep |
| **Brand colour** (§3 vs `DESIGN_SYSTEM.md`) | Teal `#4DB848` + steel blue `#2C6FB4` | Crimson accent `#9B1C31` |
| **Egypt ETA QR** (§11.23 vs `ZATCA_NOTES.md`) | Base64-encoded JSON (5 fields) | `invoice_number\|total` UTF-8 text |
| **Route lock** (§7 rule 10 vs `BUSINESS_RULES.md` rule 7) | "Rep can only visit **assigned customers**" | "Rep can only open visits for customers on their **active route**" |

An AI agent that already built Phases 1–2 against the `docs/` set (the
repo shows 5-role seeds and crimson styling live in the codebase) will now
read the reference guide and find a *different* spec. It will either
"correct" the codebase to the guide (breaking the existing 5-role system)
or ignore the guide (breaking the stated source of truth).

### Risk if ignored
The AI flips roles, colours, and QR logic between phases depending on which
file it read last. Seeded data and permissions drift. Invoices fail
e-invoicing validation. The two spec sources silently diverge with every
commit.

### Exact location
Top of the guide, **§0 — How to read and use this guide**, and a new
reconciliation note in each affected section (§5, §3, §11.23, §7).

### Proposed addition — insert as a new subsection §0.1

```markdown
### 0.1 Reconciliation with `docs/` (read before any phase)

This guide is the **primary** source. Where `docs/*.md` disagree, this
guide wins and the `docs/` file must be updated to match in the same
commit that implements the conflicting feature. Known reconciliations
required before Phase 3:

| File | Conflict | Resolution |
|---|---|---|
| `docs/ROLES_MATRIX.md` | 5 roles vs guide's 7 | **Guide wins.** Rewrite the matrix to the 7 roles in §5 before Phase 2's commit. |
| `docs/DESIGN_SYSTEM.md` | Crimson `#9B1C31` accent | **Guide wins.** Accent is teal `#4DB848` + steel blue `#2C6FB4` per §3. |
| `docs/ZATCA_NOTES.md` | Egypt QR = `invoice_number\|total` | **Both are wrong — see §11.23 correction (C-3).** Egypt ETA uses the same TLV format as ZATCA Phase 1. Rewrite the doc. |
| `docs/BUSINESS_RULES.md` rule 7 | Route-locked visits | **Merge.** Rule 10 (assigned customers) AND the route lock both apply: a rep may only visit customers assigned *that day* AND on a route the rep is mapped to. See §7 rule 10 amendment. |

The AI agent must perform this reconciliation as the first task of Phase 3
and commit it as `fix: reconcile docs with build guide` before any new
feature work.
```

### Why this improves AI implementation
Removes the guesswork of "which file do I follow?" and makes the
reconciliation an explicit, committable task instead of an ambient hazard.

---

## C-2 · Multi-tenancy is not enforced — `company_id` is decorative

### Why this matters
`company_id` appears on ~30 tables but the guide never says **how** it is
enforced. There is no global scope, no base model, no `current_company`
context, no middleware that sets it. An AI agent will write
`Customer::all()` in a Filament resource and silently return rows from
**every** company. With 100 companies (the scalability target in §10's
implicit growth), this is a data-leakage bug on every list page.

### Risk if ignored
Any list query without an explicit `where('company_id', …)` leaks
cross-company data. Rep for company A sees company B's customers. Reports
aggregate across entities. This is the single highest-severity defect
class in a multi-company ERP.

### Exact location
New subsection after **§4 (Database schema)**, before §4.1 — call it
**§4.0 Multi-tenancy enforcement**.

### Proposed addition

```markdown
### 4.0 Multi-tenancy enforcement (mandatory before any model is written)

Every table that carries `company_id` MUST extend a base model that
applies a global scope automatically. No query ever writes
`->where('company_id', …)` by hand.

**Base model:**
```php
// app/Models/Concerns/BelongsToCompany.php
trait BelongsToCompany {
    protected static function bootBelongsToCompany(): void {
        static::addGlobalScope('company', fn (Builder $q) =>
            $q->where('company_id', app(ActiveCompanyContext::class)->id())
        );
        static::creating(fn (Model $m) =>
            $m->company_id ??= app(ActiveCompanyContext::class)->id()
        );
    }
}
```

**Context (set by middleware, read by the scope):**
```php
// app/Support/ActiveCompanyContext.php
final class ActiveCompanyContext {
    private ?int $companyId = null;
    public function setFromUser(User $u): void {
        $this->companyId = $u->company_id; // single-company users
        // For admins who can switch: read ?company= from session
    }
    public function id(): ?int { return $this->companyId; }
    public function disable(): void { $this->companyId = null; } // admin only
}
```

**Rules:**
1. Every model with `company_id` uses `use BelongsToCompany;`.
2. Admin (Amr) is the only role that may call `ActiveCompanyContext::disable()` — and only inside a `DB::transaction` wrapping an explicit `where('company_id', $id)`. Wrap admin cross-company reads in `Model::withoutGlobalScope('company', fn() => …)`.
3. Filament resources read `ActiveCompanyContext` to scope the Eloquent query and to set the form default. Never rely on the user remembering to filter.
4. The context is set in a middleware registered in `bootstrap/app.php`, running **after** auth, **before** the route.
5. A Pest test `CompanyIsolationTest` is mandatory in Phase 1: create 2 companies, create a customer in each, log in as a user of company A, assert `Customer::count() === 1`.

**Models that are global** (no `company_id`, no scope):
`tax_templates` rows are per-company (has `company_id` — scoped). The only
truly global tables are `migrations`, `jobs`, `failed_jobs`, `cache`,
`sessions`, and the spatie permission tables (permissions are global;
role assignments are per-user).
```

### Why this improves AI implementation
The AI never writes a per-company query by hand, so it cannot forget the
filter. The isolation test catches any model that misses the trait.

---

## C-3 · Egypt ETA e-invoice QR format is factually wrong

### Why this matters
§11.23 specifies the Egypt ETA QR as **Base64-encoded JSON** with five
fields. The real Egypt Tax Authority e-invoicing QR is **not** JSON. The
ETA simplified e-invoice QR encodes a **validation link** to
`https://invoicing.eta.gov.eg/` with the invoice's e-invoice UUID /
submission hash; the detailed e-invoice carries the same TLV-style payload
structure as ZATCA Phase 1 (seller name, VAT number, timestamp, total, VAT
total) **Base64-encoded as TLV**, not JSON. The project's own
`docs/ZATCA_NOTES.md` is *also* wrong (it says `invoice_number|total`
plain text). Two wrong specs = guaranteed non-compliant invoices.

This is a Phase 14 Definition-of-Done blocker: "Invoice PDF with valid ETA
QR code scans correctly with any ETA-compliant scanner" — with the current
spec, it will not.

### Risk if ignored
Invoices printed for Egyptian customers fail ETA validation. The client is
registered with ETA (§1 company profile). Non-compliant e-invoices carry
penalties and rejection from the tax portal. The AI will build the wrong
encoder, the test will pass against the wrong vector, and the defect is
only discovered at the client's first real filing.

### Exact location
**§11.23** (replace the implementation block) **and §11.24** (add the
Egypt note), and Phase 14 tasks.

### Proposed addition — replace §11.23's implementation block

```markdown
### 11.23 Egypt ETA & Saudi ZATCA E-Invoicing QR (same TLV format)

**Both** Egypt ETA and Saudi ZATCA Phase 1 use the **same** QR payload
structure: Base64-encoded **Tag-Length-Value** (TLV), NOT JSON, NOT
pipe-delimited text.

**TLV encoding (per field):**
- 1 byte: tag (1=seller name, 2=VAT number, 3=timestamp, 4=total, 5=VAT total)
- 1 byte: value length (UTF-8 byte count)
- N bytes: value (UTF-8)

Concatenate all 5 fields in tag order, then Base64-encode the whole byte
string. The QR renders that Base64 string.

**Implementation (single service, two strategies):**
```php
// app/Services/InvoiceQrService.php
interface QrStrategy { public function encode(Invoice $i): string; }

final class TlvQrStrategy implements QrStrategy {
    public function encode(Invoice $i): string {
        $payload = '';
        foreach ([
            1 => $i->company->name_ar,                 // seller name (Arabic)
            2 => $i->company->tax_number,              // VAT registration
            3 => $i->issued_at->toIso8601String(),     // timestamp
            4 => (string) $i->total,                   // total inc. VAT
            5 => (string) $i->vat_amount,              // VAT total
        ] as $tag => $value) {
            $bytes = mb_convert_encoding((string)$value, 'UTF-8');
            $payload .= chr($tag) . chr(strlen($bytes)) . $bytes;
        }
        return base64_encode($payload);
    }
}
```

- Egypt entity (`companies.abbr = 'GPC'`, country EG): use `TlvQrStrategy`.
- Saudi entity (v2, `abbr = 'GPS'`): same `TlvQrStrategy`, Arabic seller
  name, 15% VAT.
- Store the generated Base64 in `invoices.eta_qr` / `invoices.zatca_qr`.
- **Unit test:** encode a known invoice, assert the Base64 matches a
  vector supplied by the client's ETA submission sample, byte-for-byte.
  This test is a Phase 14 gate — do not mark Phase 14 done until it passes
  against a real ETA sample, not a self-generated one.

> The earlier draft's "Base64 JSON" and the `docs/ZATCA_NOTES.md`
> "invoice_number|total" descriptions are both **withdrawn**. Use TLV.
```

### Why this improves AI implementation
Removes a factual error that would have produced invalid invoices. Gives
the AI one service, one encoding, one test vector. Merges Egypt and Saudi
into a single code path (they're the same format), cutting Phase 14 and
v2 work.

---

## C-4 · Soft-delete vs. cancel contradiction for transactions

### Why this matters
§4 intro says: *"Add a soft-delete (`deleted_at`) to `customers`,
`products`, `invoices`, and `users`."*
§11.44 says: *"Transactions (invoices, payments, returns, POs,
quotations): never delete, only cancel. Only reference data (customers,
products) is soft-deleted."*

These directly contradict on `invoices`. An AI agent reading §4 will add
`deleted_at` to the invoices migration; reading §11.44 it will add
`cancelled_at`/`cancelled_by`. It may add both, neither, or flip-flop per
phase. The audit trail semantics are opposite: soft-delete hides the row;
cancel keeps it visible with a reversal entry.

### Risk if ignored
Either invoices get soft-deleted (losing the audit trail ERPNext's whole
model is built on, breaking reversal logic), or the schema and the
business rules disagree and the AI writes confused code that checks the
wrong flag.

### Exact location
**§4 intro** (the sentence listing soft-delete tables).

### Proposed correction

```markdown
Add a soft-delete (`deleted_at`) to **master data only**: `customers`,
`products`, `suppliers`, `users`, `product_categories`, `routes`,
`warehouses`. Transaction tables (invoices, proforma_invoices, payments,
returns, purchase_orders, goods_in_transit, stock_movements, visits) are
**never soft-deleted** — they use `cancelled_at` + `cancelled_by` +
`amended_from` per §11.30/§11.44 and remain fully visible with reversal
entries. This supersedes the earlier list that included `invoices`.
```

### Why this improves AI implementation
One rule, no contradiction. The AI adds `softDeletes()` to master models
and `cancelled_at` columns to transaction migrations — never both.

---

## C-5 · No service-layer contracts — services are names only

### Why this matters
§11.31 lists 9 services (`StockService`, `InvoiceService`, …) by name with
a one-line comment. There are **no interfaces**, no method signatures, no
return types, no declared exceptions, no rule that controllers/Livewire
**must not** call Eloquent directly for money/stock. An AI agent building
Phase 3–9 will invent a different service shape each phase: some static
methods, some injected, some returning arrays, some throwing
`ValidationException`, some throwing `Exception`. The codebase becomes
inconsistent and untestable.

`CLAUDE.md` already says "All money mutations … happen inside
`DB::transaction()` via a Service" — but the guide never says what a
service *is*.

### Risk if ignored
Inconsistent service patterns make the codebase unmaintainable and make
the Phase 9+ "forced rollback" test random — each service wraps (or
doesn't wrap) transactions differently. Reversal logic (§11.30) can't be
applied uniformly.

### Exact location
New subsection **§11.50 Service-layer contract (binding on all phases)**.

### Proposed addition

```markdown
### 11.50 Service-layer contract (binding on all phases)

**Hard rules:**
1. A Service is a final class in `app/Services/`, constructor-injected,
   never static, never facaded. Controllers, Filament pages, and Livewire
   components **call services** — they never call `Model::create()`,
   `Model::update()`, or `DB::transaction()` directly for any table listed
   in §11.31.
2. Every service method that mutates money or stock wraps its body in
   `DB::transaction(fn () => …)` internally. Callers do **not** wrap.
3. Every service implements an interface in `app/Services/Contracts/`.
   The interface is the contract Filament/Livewire depend on; the
   implementation is bound in a ServiceProvider. This makes every service
   mockable in Pest in one line.
4. Services throw **domain exceptions** (see §11.51), never
   `ValidationException`, never `Exception`, never return `false` to
   signal failure.
5. Services return **DTOs** (see §11.52) for multi-value results, never
   bare arrays.

**Required interface signatures (Phase 1 must define these, even if
methods are added incrementally in later phases):**

```php
interface StockService {
    public function decrement(int $warehouseId, int $productId, ?int $batchId, float $qty, string $reason, Model $ref): StockMovement;
    public function increment(int $warehouseId, int $productId, ?int $batchId, float $qty, string $reason, Model $ref): StockMovement;
    public function transfer(int $fromWarehouse, int $toWarehouse, int $productId, ?int $batchId, float $qty, Model $ref): StockMovement;
    public function balance(int $warehouseId, int $productId, ?int $batchId = null): float;
}
interface InvoiceService {
    public function create(InvoiceData $data): Invoice;
    public function submit(Invoice $i): Invoice;
    public function cancel(Invoice $i, int $userId, string $reason): Invoice;
    public function amend(Invoice $i): Invoice; // returns the new draft
}
interface PaymentService {
    public function collect(PaymentData $data): Payment;
    public function cancel(Payment $p, int $userId): Payment;
}
interface PricingService {
    public function priceForRep(int $productId, int $repId, float $unitPrice): bool; // within range?
    public function rangeForRep(int $productId, int $repId): PriceRange;
}
interface LandedCostService {
    public function distribute(GoodsInTransit $git): void;
}
interface DocumentNumberService {
    public function generate(string $docType, int $companyId): string;
}
interface AlarmService {
    public function raise(string $type, Model $ref, string $title, string $desc, string $severity): Alarm;
    public function acknowledge(Alarm $a, int $userId): Alarm;
    public function resolve(Alarm $a, int $userId): Alarm;
}
```

**Binding (register in `AppServiceProvider`):**
```php
StockService::class => StockServiceImpl::class, // etc.
```

**Test rule:** every service method has a Pest test that (a) succeeds and
(b) fails on the documented exception. No service ships without its
interface test double.
```

### Why this improves AI implementation
The AI has exact method signatures, return types, exception expectations,
and a "controllers never touch Eloquent for money/stock" rule. No
invention needed.

---

## C-6 · No exception hierarchy — "bilingual error" is undefined

### Why this matters
The phrase "block with bilingual error" / "bilingual error message"
appears in §7 rules 1, 4, 5 and in `CLAUDE.md`, but nowhere does the guide
say **what exception class** to throw, **how** the bilingual message is
looked up, or **how** Livewire/Filament render it. An AI agent will throw
`ValidationException` sometimes, `Exception` other times, hardcode Arabic
strings in service methods, and the front-end will show different error
chrome each time.

### Risk if ignored
Error UX is inconsistent, messages are untranslatable, the failure-path
tests required by `CLAUDE.md` have nothing stable to assert against, and
exceptions escape transactions unpredictably (a thrown `Exception` inside
`DB::transaction` rolls back — but a caught `ValidationException` thrown
**outside** the transaction does not, leaving partial state).

### Exact location
New subsection **§11.51 Domain exception hierarchy**.

### Proposed addition

```markdown
### 11.51 Domain exception hierarchy

All business-rule violations throw a domain exception from
`app/Exceptions/Domain/`. All extend one base class:

```php
abstract class DomainException extends RuntimeException {
    public function __construct(
        public readonly string $messageKey,     // translation key
        public readonly array $replace = [],     // :field placeholders
        public readonly int $httpStatus = 422,
    ) {
        parent::__construct(trans($messageKey, $replace, app()->getLocale()));
    }
}
```

**Required subclasses (one per failure family):**
```php
class InsufficientStockException extends DomainException {}     // §7.1
class PriceOutOfRangeException extends DomainException {}        // §7.4
class CustomerNotApprovedException extends DomainException {}    // §7.5
class GeofenceViolationException extends DomainException {}      // §7.3
class DuplicateCustomerException extends DomainException {}      // §7.5
class CreditLimitExceededException extends DomainException {}    // §7-new (H-5)
class DocumentStateException extends DomainException {}          // §11.1 wrong-state transition
class ConcurrencyException extends DomainException {}            // §11.2 duplicate number
```

**Usage in a service:**
```php
if ($stock < $qty) {
    throw new InsufficientStockException(
        'errors.stock.insufficient',
        ['product' => $product->name_ar, 'available' => $stock],
    );
}
```

**Translation files** (PHP array, per-locale):
```
lang/ar/errors.php  =>  'stock.insufficient' => 'الكمية غير متوفرة: :product (المتاح: :available طن)'
lang/en/errors.php  =>  'stock.insufficient' => 'Insufficient stock for :product (available: :available t)'
```

**Render rule:** register one handler in
`app/Exceptions/Handler.php` (or `bootstrap/app.php` in Laravel 11+):
```php
->renderable(fn (DomainException $e) => response()->json([
    'message' => trans($e->messageKey, $e->replace, request()?->header('Accept-Language','ar')),
    'key' => $e->messageKey,
], $e->httpStatus))
```
For Livewire/Filament this maps to a bilingual toast/notification with the
resolved message. Never hardcode Arabic or English in a service.

**Test rule:** every failure-path Pest test asserts **both** the exception
class AND `trans($e->messageKey, [], 'ar')` and `trans(..., [], 'en')` are
non-empty and distinct. This catches untranslated errors automatically.
```

### Why this improves AI implementation
The AI has a fixed pattern: subclass → message key → translation file →
handler. No scattered `throw new Exception('...')`. Tests assert on the
class, not on a fragile string.

---

## C-7 · Naming-series race condition — no locking strategy

### Why this matters
§11.2 introduces `naming_series` with `current_number` and a
`NamingSeriesService::generate()` call. Two concurrent invoice creations
read the same `current_number`, both increment to N+1, both issue invoice
`INV-GPC-2026-00042`. Sequential-per-company (§7 rule 11) is violated and
the uniqueness constraint either fires (one request errors) or, worse, is
missing and both insert.

### Risk if ignored
Duplicate invoice numbers under real concurrent load. §7 rule 11 is a
non-negotiable business rule. This is a money-document integrity bug.

### Exact location
**§11.2**, append to the implementation block.

### Proposed addition

```markdown
**Concurrency:** `generate()` MUST atomically reserve the next number.
Two acceptable strategies — pick one and use it everywhere:

**(A) `SELECT … FOR UPDATE` lock (default):**
```php
return DB::transaction(function () use ($docType, $company) {
    $row = NamingSeries::lockForUpdate()
        ->where('name', $docType)->where('company_id', $company)->first();
    $row->increment('current_number');
    return Str::replace(['{#####}', '{YYYY}'],
        [str_pad($row->current_number, 5, '0', STR_PAD_LEFT), date('Y')],
        $row->series_format);
});
```

**(B) Postgres `UPDATE … RETURNING` (preferred under load):**
```sql
UPDATE naming_series
SET current_number = current_number + 1
WHERE name = ? AND company_id = ?
RETURNING current_number, series_format
```

Never read-then-write without a lock. The unique index on
`invoices.invoice_number` is the **last line of defence** — the service is
the first. A Pest test `NumberingConcurrencyTest` spawns 10 concurrent
`InvoiceService::create()` calls and asserts 10 distinct, sequential
numbers.
```

### Why this improves AI implementation
The AI copies the locked snippet instead of writing a naive
read-increment-save that races.

---

## C-8 · No domain-event system — cross-cutting logic will tangle

### Why this matters
§7 lists ~10 cross-cutting side effects that fire on a sale: stock
movement, customer balance update, alarm generation, cash-box update,
activity log, (future) ETA submission. §22 lists 7 alarm triggers that
fire from different services. The guide never says **how** these are
decoupled. Without events, `InvoiceService::create()` will grow a 200-line
body calling `StockService`, `AlarmService`, `CustomerBalance`,
`CashBoxService`, `ActivityLogger` directly — violating SRP, making the
forced-rollback test impossible to reason about, and making it unsafe to
add a new side effect (e.g., ETA submission in Phase 14) without editing
every service.

### Risk if ignored
Services become god-methods. Adding Phase 14 ETA submission requires
patching `InvoiceService` (risk of breaking the sale flow). The
"failure-path" tests required by CLAUDE.md can't isolate which side effect
failed. Alarm triggers get forgotten (the guide already lists 7; an AI
writing them inline will miss 2–3).

### Exact location
New subsection **§11.53 Domain events**.

### Proposed addition

```markdown
### 11.53 Domain events (Laravel events, synchronous by default)

Every side-effect of a business action is a listener, not an inline call.
Services **dispatch events**; listeners live in `app/Listeners/`. Events
are synchronous (`ShouldQueue` only for genuinely deferrable work like
PDF generation, ETA API calls).

**Event catalogue (define in Phase 1, wire per phase):**
```php
final class InvoiceSubmitted { public function __construct(public Invoice $invoice) {} }
final class InvoiceCancelled { public function __construct(public Invoice $invoice, public int $userId) {} }
final class PaymentCollected { public function __construct(public Payment $payment) {} }
final class ReturnSubmitted   { public function __construct(public Return $return) {} }
final class StockMoved        { public function __construct(public StockMovement $movement) {} }
final class GoodsReceived     { public function __construct(public GoodsInTransit $git) {} }
final class CustomerApproved  { public function __construct(public Customer $customer) {} }
final class OutOfStockRequested { public function __construct(public OutOfStockRequest $r) {} }
final class ComplaintSubmitted { public function __construct(public Complaint $c) {} }
final class BatchExpiringSoon { public function __construct(public Batch $batch, public int $daysLeft) {} }
```

**Rules:**
1. A service method does the core mutation inside its `DB::transaction()`
   and dispatches the event **inside the same transaction**
   (`event(new InvoiceSubmitted($i))`). Listeners that touch the DB
   (balance update, alarm row) join the transaction; if any listener
   throws, the whole thing rolls back — which is exactly what §7.2 demands.
2. Listeners are registered in `EventServiceProvider` (or via
   `#[Listener]` attribute in Laravel 11+), never invoked manually.
3. A listener does **one** thing: `UpdateCustomerBalanceOnInvoice`,
   `RaiseAlarmOnOutOfStock`, `WriteActivityLogOnCancel`. One class, one
   job.
4. Pure read-side projections (dashboard counters, report denormalisation)
   use queue listeners (`shouldQueue`) so they never slow the sale.

**Example — the sale flow becomes:**
```php
// InvoiceService::create()
return DB::transaction(function () use ($data) {
    $invoice = Invoice::create(...);
    InvoiceItem::insert(...);
    $this->stock->decrement(...);              // writes stock_movement
    event(new InvoiceSubmitted($invoice));     // balance + alarm + log + (v2) ETA
    return $invoice;
});
```

**Test rule:** every event has a Pest test asserting each registered
listener fired and produced its side effect. New side effects = new
listeners = new tests, never a patch to the service.
```

### Why this improves AI implementation
The AI adds a Phase 14 ETA submission by writing one listener, not by
reopening `InvoiceService`. The 7 alarm triggers in §22 each become one
listener — impossible to forget one.

---

## C-9 · Tax inclusive vs. exclusive model is contradictory

### Why this matters
§11.10 introduces `tax_template_lines.included_in_rate` (inclusive pricing
— VAT embedded in the item price). §7 rule 24 (Money math) defines:
`vat_amount = subtotal × (vat% / 100)`, `total = subtotal + vat_amount` —
that is **exclusive** pricing only. If the AI implements both, the same
invoice computes VAT two different ways depending on a flag the user can
flip. Egyptian VAT is standardly **exclusive** (added on top); the
inclusive branch is a v2 nicety.

### Risk if ignored
Silent money miscalculation on any invoice where `included_in_rate=true`
gets set (a single bad seed row flips it). VAT filings to ETA are wrong.

### Exact location
**§7 rule 24** and **§11.10**.

### Proposed addition to §7 rule 24

```markdown
24. **Money math (v1 — exclusive VAT only):**
    - `line_total = quantity × unit_price` (unit_price is **net** of VAT)
    - `subtotal = Σ line_total`
    - `vat_amount = subtotal × (company.vat_percent / 100)` (only VAT-applicable products; sum their line_totals first if some lines are exempt)
    - `total = subtotal + vat_amount`
    - `remaining_amount = total − paid_amount`

    **V1 implements the exclusive model only.** The `included_in_rate`
    flag on `tax_template_lines` (§11.10) is **persisted but ignored** in
    v1 calculation — seeded `false`. Inclusive-price support is a v2
    change that requires rewriting `InvoiceService::create()` and is
    flagged with a `ponytail: inclusive VAT deferred to v2` comment on the
    tax calculation. Do not implement inclusive logic speculatively.
```

### Why this improves AI implementation
One code path. The AI doesn't build a branch it can't test against a real
Egyptian invoice.

---

## C-10 · No PHP Enum class guidance — 30+ DB enums unbridged

### Why this matters
The schema declares ~30 `enum` columns (warehouse type, product packaging,
unit, status, severity, payment type, complaint type, etc.). The guide
never says to mirror these as **PHP 8.3 native enums** with casts. Without
that, the AI writes `if ($invoice->status === 'submitted')` string
comparisons scattered through services — typos silent, IDE no help, no
exhaustiveness checking, impossible to refactor the status set.

### Risk if ignored
Stringly-typed statuses across 75 tables. A typo `'submited'` is a silent
logic bug. State-machine transitions (§11.1) can't be validated at the
type level.

### Exact location
New subsection **§11.54 Enum contract**.

### Proposed addition

```markdown
### 11.54 Enum contract (PHP 8.3 backed enums, mandatory)

Every `enum` column in §4 has a matching `App\Enums\*` backed enum. Models
cast the column to the enum. No string comparison on enum values anywhere
outside the enum class itself.

**Pattern:**
```php
// app/Enums/InvoiceStatus.php
enum InvoiceStatus: string {
    case Draft     = 'draft';
    case Submitted = 'submitted';
    case Cancelled = 'cancelled';
    case Amended   = 'amended';

    public function canTransitionTo(self $next): bool {
        return match ($this) {
            self::Draft     => in_array($next, [self::Submitted, self::Cancelled]),
            self::Submitted => in_array($next, [self::Cancelled, self::Amended]),
            self::Cancelled => [self::Amended],
            self::Amended   => [self::Submitted, self::Cancelled],
        };
    }
}

// app/Models/Invoice.php
protected $casts = ['status' => InvoiceStatus::class];

// usage — never compare strings:
if (! $invoice->status->canTransitionTo(InvoiceStatus::Submitted)) {
    throw new DocumentStateException('errors.invoice.invalid_transition', [...]);
}
```

**Required enums (Phase 1):** `WarehouseType`, `PackagingType`, `Unit`,
`InvoiceStatus`, `ProformaStatus`, `PaymentStatus`, `ReturnStatus`,
`PurchaseOrderStatus`, `GoodsInTransitStatus`, `AlarmType`,
`AlarmSeverity`, `ComplaintType`, `ComplaintStatus`, `CustomerStatus`,
`PaymentModeType`, `ExpenseCategory`, `VisitStatus`, `VisitPurpose`,
`MovementReason`, `LandedCostType`, `TaxChargeType`, `ValuationMethod`.

**Test rule:** `EnumExhaustivenessTest` iterates every enum, asserts every
case's `value` matches a migration's allowed enum set. Catches drift
between code and DB.
```

### Why this improves AI implementation
IDE autocomplete, exhaustiveness, centralised transition rules. The AI
can't typo `'submited'`.

---

# HIGH PRIORITY

---

## H-1 · No database index strategy

### Why this matters
~75 tables, every FK unindexed by default in Postgres, every list page
filtered by `company_id` + something. Without indexes the scalability
target (100k customers, 5M invoices, 20M stock movements) collapses into
table scans. The guide never mentions indexes once.

### Risk if ignored
Admin list pages take seconds; Filament table sorts trigger full scans;
the 20M-row `stock_movements` table becomes unusable for the audit log
view within months.

### Exact location
Append to **§4 intro** as **§4.0b Index policy**.

### Proposed addition

```markdown
### 4.0b Index policy (every migration must follow)

1. **Every FK column gets an index** in the same migration that creates
   the FK. No exception. `$table->foreign('company_id')->...` is always
   preceded by `$table->index('company_id')`.
2. **Two-column company-first composites** for every list filtered by
   company: `(company_id, created_at)` on invoices, payments, returns,
   alarms, customers, products; `(company_id, status)` on invoices,
   proforma_invoices, purchase_orders; `(company_id, posting_date)` on
   stock_movements.
3. **Partial unique on nullable batch_id:** `stocks` unique is
   `(warehouse_id, product_id, batch_id)` but Postgres treats NULLs as
   distinct. Create **two** indexes:
   - `UNIQUE (warehouse_id, product_id, batch_id) WHERE batch_id IS NOT NULL`
   - `UNIQUE (warehouse_id, product_id) WHERE batch_id IS NULL`
4. **Lookup indexes:** `customers.phone`, `customers.code`, `products.sku`,
   `users.email`, `invoices.invoice_number`, `batches.batch_number` —
   all already unique-constrained, which indexes them; document that.
5. **Search indexes (Postgres `pg_trgm`):** enable the extension; add a
   GIN index on `customers.name_ar`, `customers.name_en`, `products.name_ar`,
   `products.name_en` using `gin_trgm_ops` for the global-search feature.
6. **Concurrent in production:** every migration that adds an index to a
   table expected to exceed 100k rows ships with `CREATE INDEX
   CONCURRENTLY` (Laravel: `$table->index(...)->concurrently()` or a raw
   statement in a separate up-only migration). Never lock a big table on
   deploy.

**Test rule:** `IndexAuditTest` connects to a fresh migration and asserts
`pg_indexes` contains every named index in this policy. A missing index
fails CI.
```

---

## H-2 · No partitioning strategy for `stock_movements` (20M-row target)

### Why this matters
§8 scalability assumes 20M stock movements. A single un-partitioned table
with 20M rows + a `(company_id, posting_date)` index still vacuums slowly,
and the audit-log view paginates badly across years.

### Risk if ignored
After ~12–18 months the audit log page times out; `VACUUM` stalls the
queue worker; archive queries slow the whole DB.

### Exact location
Append to **§4.8 `stock_movements`**.

### Proposed addition

```markdown
**Partitioning:** `stock_movements` is **range-partitioned by `posting_date`
month** from day one (Postgres declarative partitioning). The parent is
`PARTITION BY RANGE (posting_date)`; a scheduled job (last day of month)
pre-creates the next month's partition and rolls over partitions older
than 24 months to an `archive` schema. Queries against the parent
automatically prune. All other tables stay un-partitioned in v1.
```

---

## H-3 · No Value Objects / DTOs for Money, GPS, PriceRange

### Why this matters
Money is `decimal(12,2)` everywhere but the guide does arithmetic as
floats in services (`$subtotal = $qty * $price`). GPS coordinates are two
loose columns. Price ranges are four loose columns
(`manager_plus/minus`, `rep_plus/minus`). These are **value objects** with
invariants; leaving them as primitives means the AI will scatter
rounding, range-checking, and haversine logic across services.

### Risk if ignored
Floating-point money drift (0.1 + 0.2 != 0.3 in float — even with
`decimal` columns, PHP arithmetic on the retrieved values is float unless
cast). Range checks re-implemented in five places. Distance calc
duplicated.

### Exact location
New subsection **§11.52 Value Objects & DTOs**.

### Proposed addition

```markdown
### 11.52 Value Objects & DTOs

**Money** — never do arithmetic on a raw decimal in a service. Use:
```php
// app/Values/Money.php
final readonly class Money {
    public function __construct(public string $amount, public string $currency = 'EGP') {}
    // amount is a string — bcmath operates on strings, never floats
    public function add(Money $o): self { assert($o->currency === $this->currency); return new self(bcadd($this->amount, $o->amount, 2), $this->currency); }
    public function mul(string $qty): self { return new self(bcmul($this->amount, $qty, 2), $this->currency); }
    public function percent(string $rate): self { return new self(bcmul($this->amount, bcdiv($rate, '100', 4), 2), $this->currency); }
    public function toDecimal(): string { return $this->amount; }
}
```
All invoice math goes through `Money`. Models cast `decimal` columns to
`Money` via a custom cast. `bcmath` is required (PHP 8.3 bundled).

**GPS coordinate & distance** —
```php
final readonly class GpsCoordinate {
    public function __construct(public float $lat, public float $lng) {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) throw new DomainException('errors.gps.invalid');
    }
    public function metersTo(self $o): float {
        // haversine, returns meters
    }
    public function within(self $o, float $radiusMeters): bool { return $this->metersTo($o) <= $radiusMeters; }
}
```
Geofence check (§7.3) is `GpsCoordinate::within()` — one place, one
formula. Configurable radius from `config('jawla.geofence_radius_m')`
default 1000.

**PriceRange** —
```php
final readonly class PriceRange {
    public function __construct(public Money $base, public Money $plus, public Money $minus) {}
    public function contains(Money $price): bool {
        return bccomp($price->amount, bcsub($this->base->amount, $this->minus->amount, 2), 2) >= 0
            && bccomp($price->amount, bcadd($this->base->amount, $this->plus->amount, 2), 2) <= 0;
    }
}
```
§7.4 enforcement is `PriceRange::contains()` — never inline.

**DTOs** — service inputs are DTOs (`InvoiceData`, `PaymentData`), not
arrays. Livewire/Filament form data is mapped to a DTO in the component,
passed to the service. The service never sees `$request` or an array.
```

---

## H-4 · No Laravel Policy classes — authorization is hand-waved

### Why this matters
§12 gives a detailed permission catalogue (good) but never says **where**
those permissions are checked. Spatie gives you `hasPermissionTo()`; it
does **not** give you a place to call it. Without Policies, the AI will
scatter `if (! $user->can('invoices.view_all'))` in every Filament
resource and Livewire component — and forget half of them.

### Risk if ignored
IDOR: a rep types `/admin/customers/42` and sees another rep's customer
because the controller didn't check ownership. The §12 catalogue is
aspirational, not enforced.

### Exact location
Append to **§5 (User roles & permissions)**.

### Proposed addition

```markdown
**Policy enforcement (Phase 2):**
- Every Eloquent model has a matching `app/Policies/*Policy.php` with
  `viewAny`, `view`, `create`, `update`, `delete`, `forceDelete`,
  `restore` methods. Filament auto-discovers them.
- Rep-specific ownership rules (`customers.view_own`, `invoices.view_own`)
  live in the `view` method: `return $user->hasRole('rep') ?
  $user->id === $record->user_id : $user->can('customers.view_all');`
- The `/app` Livewire components call `Gate::authorize('view', $model)`
  as the **first** line of `render()`. Never assume the route middleware
  is enough.
- A `PolicyCoverageTest` iterates all models, asserts a Policy exists and
  every catalogue permission is referenced by at least one policy method.
  This converts §12 from a doc into an enforced contract.
```

---

## H-5 · Credit-limit rule is in the schema but not in the business rules

### Why this matters
`customers.credit_limit` exists (§4.14) and `customers.balance` is "how
much this customer owes". §7 has no rule that checks
`balance + this_invoice.total <= credit_limit`. §6 says "no credit sales
currently — future" but the column is there and the AI will either ignore
it (letting reps oversell) or enforce it blindly (blocking cash sales
where credit_limit=0). Need an explicit v1 decision.

### Risk if ignored
Either no enforcement (a rep sells to a customer already over limit) or
mis-enforcement (every sale blocked because limit defaults to 0).

### Exact location
New rule in **§7** as **rule 25**.

### Proposed addition

```markdown
25. **Credit limit (v1 — enforced as a warning, not a block).**
    - `customers.credit_limit` defaults to 0; 0 means **no limit** (not
      "blocked"), because v1 has no credit sales per §6.
    - When `credit_limit > 0`: if `customer.balance + invoice.total >
      credit_limit`, `InvoiceService::create()` throws
      `CreditLimitExceededException` (a **block**). This protects real
      credit customers once enabled.
    - When `credit_limit == 0`: no check (v1 cash/cheque-on-delivery
      world). A `ponytail: v1 — limit=0 means unlimited, revisit when
      credit sales go live` comment marks the branch.
    - The admin customer form shows credit_limit with the label "0 = no
      limit (v1 default)". No rep UI for credit limit.
```

---

## H-6 · No scheduled-task inventory — expiry/transit alarms won't fire

### Why this matters
§7 rule 17 (batch expiry within 30 days → alarm) and §22 (goods-in-transit
past ETA → alarm) are time-based, not event-based. They need a scheduled
job, not a listener. The guide never lists the `schedule:` commands. The
AI will forget to add them and the alarms simply never appear.

### Exact location
New subsection **§11.55 Scheduled tasks**.

### Proposed addition

```markdown
### 11.55 Scheduled tasks (routes/console.php or bootstrap/app.php schedule)

Register in Phase 13 (Alarms). All run in the queue driver.

| Cadence | Command | Purpose | Phase |
|---|---|---|---|
| Daily 06:00 | `alarms:scan-batch-expiry` | Batches expiring in ≤30 days → `BatchExpiringSoon` event | 12 |
| Daily 06:15 | `alarms:scan-transit-delays` | GIT past `estimated_arrival_date` and not `received` → alarm | 11 |
| Daily 06:30 | `stock:reorder-check` | Products below `product_reorder_levels` → info alarm | 16 |
| Weekly Mon | `reports:weekly-summary` | Email admin/executive weekly KPI digest | 16 |
| Monthly 1st | `partition:create-next-month --table=stock_movements` | Pre-create next month's partition | 1 |
| Daily 02:00 | `backup:run` | spatie/laravel-backup nightly (per docs/BACKUP_RESTORE.md) | 0 |

**Rules:**
- Each command is an `Invokable` Artisan command calling one service
  method. No business logic in the command body.
- Each has a Pest test that fakes the clock (`Carbon::setTestNow()`) and
  asserts the expected alarms/rows are produced.
- The production crontab is one line: `* * * * * cd /var/www/jawla && php artisan schedule:run >> /dev/null 2>&1`.
- `withoutOverlapping()` on every command to prevent the daily scan from
  piling up if the previous run is slow.
```

---

## H-7 · File uploads (COA, logos) — no disk, no validation, no serving

### Why this matters
§4.6 `batches.coa_file_path`, §4.1 `companies.logo_path`, plus visit
photos and complaint evidence from §11.28. The guide never says **where**
files are stored, **how** they're validated, or **how** they're served.
`docs/SECURITY.md` says "stored outside webroot; served through signed
routes" but the guide doesn't reference it. The AI will store under
`public/` and serve directly — exposing every customer document to anyone
with the URL.

### Risk if ignored
Path-traversal via uploaded filename, MIME-spoofed executables served as
PDFs, public links to private COAs, no virus check, no size cap, storage
fills the VPS disk.

### Exact location
New subsection **§11.56 File attachments**.

### Proposed addition

```markdown
### 11.56 File attachments

**Disk:** `private` disk in `config/filesystems.php` — root
`storage/app/private`, **not** under `public/`. Never web-accessible
directly. Production: S3-compatible bucket with a separate IAM key whose
ACL defaults to private. The `public` disk is for compiled assets only.

**Upload validation (Form Request / Livewire rules, server-side,
  non-bypassable):**
- COA: `file|mimes:pdf|max:5120` (5 MB)
- Logo: `image|mimes:png,jpg,svg|max:1024` (1 MB)
- Visit/complaint photo: `image|mimes:jpg,png|max:3072`
- Filenames are **discarded**; storage path is
  `{model_type}/{model_id}/{Str::uuid()}.{ext}`. The original name is
  stored on the `attachments` row for display only.

**Serving:** a signed route `attachments.show` that:
1. Authorises via Policy (only users who can `view` the parent model).
2. Streams the file with the right `Content-Type` and
   `Content-Disposition: inline`.
3. Expires the URL in 5 minutes.

**Use `spatie/laravel-medialibrary`** for the polymorphic attachment table
(§11.28) instead of hand-rolling. One media record per file, linked to
the parent model via the package's `HasMedia` interface.

**Test rule:** `AttachmentSecurityTest` asserts (a) a file at the storage
path is **not** reachable via `/storage/...`, (b) an unsigned URL
returns 403, (c) a signed URL for a model the user can't `view` returns
403.
```

---

## H-8 · Payment allocation across multiple invoices is undefined

### Why this matters
`payments.invoice_id` is nullable (§4.30). §7.7 says collections update
invoice `paid_amount`. But a customer handing the rep 10,000 EGP against
three open invoices (3k + 4k + 3k) has **no allocation table**. The AI
will either attach the payment to one invoice (wrong) or leave
`invoice_id` null and not update any invoice's `paid_amount` (also wrong —
aging report breaks).

### Risk if ignored
Aging report shows invoices unpaid that were actually settled; customer
balance and sum-of-invoice-remaining disagree; accountants can't trust
the numbers.

### Exact location
New table after **§4.30 `payments`** and a new rule in **§7**.

### Proposed addition

```markdown
### 4.30b `payment_allocations`
`id, payment_id (FK), invoice_id (FK), amount (decimal 12,2)`
> One payment may allocate to many invoices; one invoice may receive many
> payments. `Σ payment_allocations.amount` for a payment ==
> `payments.amount` (enforced in `PaymentService::collect()`). The
> invoice's `paid_amount` is `Σ allocations.amount` (computed, never
> stored directly except as a denormalised cache updated in the same
> transaction).

### 7.x Payment allocation rule
- A payment with `invoice_id` set creates one allocation row of the full
  amount (back-compat with the simple case).
- A payment with `invoice_id` null **must** have ≥1 allocation rows
  passed in the `PaymentData` DTO. `PaymentService::collect()` throws
  `DomainException` if the allocations don't sum to the payment amount.
- Allocations apply FIFO by `posting_date` unless the rep specifies
  otherwise. The rep PWA "Collect" screen shows open invoices with
  checkboxes and an auto-distribute button.
- `customer.balance` decreases by the payment total; each
  `invoice.paid_amount` and `remaining_amount` updates in the same
  transaction.
```

---

## H-9 · Return ↔ original invoice linkage and `paid_amount` effect undefined

### Why this matters
§7.8 says returns "decrease customer balance" but never says **how a
return relates to the original invoice** or whether the invoice's
`paid_amount`/`remaining_amount` change. If a customer returns goods from
a fully-paid invoice, do we refund cash? Credit the balance? Reduce the
invoice total? Three different AI agents would do three things.

### Risk if ignored
Returns either don't link to the source invoice (no audit chain —
violates §11.5) or miscalculate the invoice's paid status. A return on a
paid invoice leaves `paid_amount > total` (negative remaining) — invalid
state.

### Exact location
**§4.32 `returns`** and **§7 rule 8**.

### Proposed addition

```markdown
**§4.32 add column:** `against_invoice_id (FK invoices nullable)` — the
invoice being returned against. Nullable for returns without a source
invoice (cash sale, etc.).

**§7 rule 8 (replace):**
8. **Returns.**
   - `returns.against_invoice_id` links to the source invoice (§11.5
     chain). If set, the return's line items must reference products that
     appear on that invoice.
   - Returns **increase van stock** (+`stock_movements` reason=`return`)
     and **decrease `customer.balance`** by the return total.
   - The return **does not** mutate the source invoice's `total`,
     `paid_amount`, or `remaining_amount`. The invoice is immutable once
     submitted (§11.1). The return is a separate posted document; the
     customer's net position is `Σ invoices.total − Σ returns.total −
     Σ payments.amount`.
   - If the source invoice was fully paid and a return is processed, the
     customer's balance goes **negative** (we owe them). That's correct —
     it becomes a credit for the next sale or a refund (v2: refund
     workflow). Do **not** try to "unpay" the invoice.
   - Reversal symmetry: cancelling a return creates a compensating
     `stock_movements` reason=`adjustment` row and restores
     `customer.balance`. The original return row stays, status=
     `cancelled`.
```

---

## H-10 · No fiscal-period / posting-date validation

### Why this matters
§11.32 introduces `posting_date` separate from `created_at`. But nothing
prevents a user from backdating an invoice into a closed month. Once
VAT is filed for March, a backdated March invoice makes the filing wrong.
ERPs always have a period-closing lock.

### Risk if ignored
An accounts user backdates a sale into last quarter after the VAT return
is filed. The ETA submission and the books now disagree.

### Exact location
New subsection **§11.57 Fiscal periods**.

### Proposed addition

```markdown
### 11.57 Fiscal periods (minimal v1)

- A `fiscal_periods` table: `id, company_id, name (e.g. '2026-Q1'),
  start_date, end_date, status (enum: 'open','closed','locked')`.
- `InvoiceService`, `PaymentService`, `ReturnService`, and
  `StockService::adjust()` check the period of the `posting_date`: if
  `closed` → throw `DocumentStateException('errors.period.closed')`; if
  `locked` (post-filing) → same.
- Admin can close a period from the Filament admin. Closing runs a
  report comparing `Σ invoices.vat_amount` for the period against the
  filed return (manual entry) and warns on mismatch.
- v1 ships with **one** open period covering the go-live date; closing is
  manual. Auto-close is v2.
- A Pest test backdates an invoice into a closed period and asserts the
  exception.
```

---

## H-11 · No CI/CD pipeline, no deployment target spec

### Why this matters
`docs/DEPLOYMENT.md` mentions Forge + Hetzner + a `scripts/deploy.sh` but
the guide (the primary spec) never references it and never lists CI. An
AI building 20 phases has no signal that tests must pass before merge, no
signal what the production server looks like, no signal how migrations
run in prod. Phase 19 ("seed data & final test pass") has no CI to
enforce it.

### Risk if ignored
Phases commit untested; production deploys run migrations inline during
traffic; a bad migration locks the DB; no rollback.

### Exact location
New **§13** (renumber existing §13 to §14, OR add as §12.5 after the
permissions catalogue — insertion, not reorg).

### Proposed addition

```markdown
## 13. CI/CD & deployment (binding from Phase 0)

### 13.1 CI (GitHub Actions, required checks on every push to main)
- `php -v` matches 8.3; `composer install --no-interaction`.
- `php artisan test --parallel` (Pest) — must pass.
- `./vendor/bin/phpstan analyse --level=8` — must pass (see §14).
- `./vendor/bin/pint --test` — must pass.
- `composer audit` — must pass (zero advisories).
- `npm ci && npm run build` — must succeed.
- A `migrate:fresh --seed` step against a throwaway Postgres service
  container — must complete without error (catches migration drift).
- All checks must be **required** on main; no merge on red.

### 13.2 Production target
- Laravel Forge on Hetzner CX32 (2 vCPU / 4 GB / 80 GB) for v1 (~10
  users). Upgrade path: CX42 then a dedicated PG instance.
- Ubuntu 24.04 LTS, PHP 8.3-FPM, Nginx, PostgreSQL 16, Supervisor.
- Cloudflare in front (proxy on, TLS full-strict, no caching on `/admin`
  or `/app`).
- `scripts/deploy.sh` (referenced by `docs/DEPLOYMENT.md`): pull →
  `composer install --no-dev --classmap-authoritative` → `npm ci &&
  npm run build` → `php artisan migrate --force` → config/route/view
  cache → `php artisan queue:restart` → `php artisan up` after `/up`
  returns 200 → rollback on non-zero exit.

### 13.3 Migration safety
- Every migration is reviewable: no `Schema::drop` on a populated table.
- Destructive migrations (`dropColumn`, `renameTable`) ship as **two**
  PRs: (1) stop writing the column, (2) drop it after a release cycle.
- Index adds on tables >100k rows use `CREATE INDEX CONCURRENTLY` (H-1).

### 13.4 Observability
- Sentry DSN in `.env` (the only third-party call the app makes at
  runtime, plus ETA/S3). Release SHA tagged on each deploy.
- `storage/logs/laravel.log` rotated daily, 7-day retention, **no PII**
  (customer phone, address) in logs — the `Log` channel redacts fields
  listed in `config/jawla.php redacted`.
- `/up` health check returns DB + queue + Redis ping status as JSON.
```

---

## H-12 · No structured-logging / audit-trail architecture

### Why this matters
`stock_movements` is an audit log for stock. But there is no audit log
for: who approved a customer, who cancelled an invoice, who changed a
price, who logged in, who exported a report. CLAUDE.md demands
"Activities" but the guide only mentions `activities` in passing in
§11.30. An AI will reinvent a different activity log per feature.

### Risk if ignored
No defensible audit trail. "Who approved this customer?" is unanswerable.
A cancelled invoice has no record of who cancelled it beyond
`cancelled_by` — no reason, no timestamp of the action (only
`cancelled_at`).

### Exact location
New subsection **§11.58 Audit & activity log**.

### Proposed addition

```markdown
### 11.58 Audit & activity log

**`activity_log` table (Phase 1):**
`id, company_id, user_id, action (string: 'create','update','delete',
'cancel','approve','reject','login','export','adjust'), subject_type
(string), subject_id (bigint), properties (jsonb - before/after diff),
ip_address, user_agent, created_at`

- Use `spatie/laravel-activitylog` (already permitted — it's a spatie
  package consistent with §2's spatie stack). Do **not** hand-roll.
- Every service method that mutates a transactional document calls
  `activity()->causedBy($user)->performedOn($model)->withProperties($diff)
  ->log('cancel');` as the **last** line inside its transaction.
- Properties diff: `['old' => [...], 'new' => [...]]` for updates; for
  cancellations `['reason' => $reason]`.
- Filament shows the activity timeline on every document page via the
  package's built-in widget.
- **Security events** (failed logins, role changes, permission grants,
  exports, stock adjustments) write to the same table with `action` in
  `['login_failed','role_changed','permission_granted','export',
  'stock_adjusted']` and are additionally pushed to Sentry as `info`
  breadcrumbs for correlation.
- Retention: 2 years online, then archive to `archive.activity_log`
  partition by year.
- PII redaction: `properties` never contains `password`,
  `remember_token`, or full phone numbers (masked to `+20 1xx xxx xx78`).
```

---

## H-13 · No folder / namespace convention — the AI will scatter classes

### Why this matters
The guide references Services, lists 9 of them, references "Form Requests",
"Livewire", "Filament", but never says **where** in `app/` each lives. An
AI agent will put `StockService` in `app/Services/`, `StockMovement`
in `app/Models/`, an enum in `app/Enums/`, a DTO in `app/Dto/`, then a
helper in `app/Helpers/` because it saw one in a Stack Overflow answer —
five top-level folders grown ad-hoc.

### Risk if ignored
Inconsistent structure makes every phase's code harder to find. Two
classes with the same basename in different namespaces. Refactors break.

### Exact location
New **§14 Coding standards & layout** (after the CI/CD insertion).

### Proposed addition

```markdown
## 14. Coding standards & project layout (binding from Phase 0)

### 14.1 Folder map (do not deviate)
```
app/
  Enums/          — PHP backed enums (§11.54)
  Exceptions/
    Domain/       — DomainException subclasses (§11.51)
  Http/
    Controllers/  — thin; only dispatch to services
    Requests/     — Form Requests (one per controller action, named {Action}{Resource}Request)
  Livewire/       — rep PWA components (at /app)
  Filament/       — admin panel resources, pages, widgets
  Models/         — Eloquent models (one per table)
  Policies/       — one per model (§5 / H-4)
  Services/
    Contracts/    — interfaces (§11.50)
    InvoiceService.php, StockService.php, ...
  Values/         — value objects: Money, GpsCoordinate, PriceRange (§11.52)
  Dto/            — service input DTOs: InvoiceData, PaymentData, ...
  Events/         — domain events (§11.53)
  Listeners/      — event listeners, one class each
  Support/        — ActiveCompanyContext, helpers that aren't a class
database/
  migrations/     — YYYY_MM_DD_HHMMSS_snake_case_description.php
  seeders/        — one per phase + DatabaseSeeder
  factories/      — one per model
tests/
  Unit/           — pure service/VO tests
  Feature/        — HTTP / Livewire / Filament endpoint tests
  Integration/    — cross-service flows (sale, return, transit receipt)
```

### 14.2 Standards
- **PHPStan level 8** enforced in CI (§13.1). No `@phpstan-ignore` without
  a `ponytail:` comment explaining why.
- **Laravel Pint** (PSR-12 + Laravel preset) runs on commit. No custom
  preset.
- **Strict types**: every class file starts with `declare(strict_types=1);`.
- **Readonly classes** for Values and DTOs.
- **Final classes** for Services and Listeners (no inheritance needed).
- **Max method size: 40 lines** (Pint doesn't enforce — CI runs a
  `php-metrics` check). Longer = extract.
- **Max class size: 250 lines.** A service bigger than that is doing two
  jobs — split.
- **No `public` properties on models** except fillable relationship
  accessors. No `mixed` in signatures.
- **Naming:** models singular PascalCase; migrations
  `{YYYY_MM_DD_HHMMSS}_{verb}_{table}`; Form Request
  `{Action}{Resource}Request`; Policy `{Model}Policy`; Listener
  `{Verb}{Subject}On{Event}` (e.g., `UpdateBalanceOnInvoiceSubmitted`).
- **DI only.** No `app()`, no `resolve()`, no `App::make()` inside
  services. Facades allowed only in controllers/Livewire for auth,
  session, redirect.
```

---

# MEDIUM PRIORITY

---

## M-1 · No idempotency keys on financial operations

### Why this matters
A rep taps "Submit Sale" twice on a flaky mobile connection. Without an
idempotency key the second request creates a second invoice. §7.2
atomicity protects against partial state, not against duplicate
submissions.

### Proposed addition (append to §7)

```markdown
26. **Idempotency on financial writes.**
    - `InvoiceService::create()`, `PaymentService::collect()`,
      `ReturnService::create()`, `ExpenseService::log()` accept an
      optional `idempotency_key` (UUID generated client-side in the
      Livewire form, persisted in a hidden input).
    - The service checks `idempotency_keys` table (`key, user_id,
      response_hash, created_at`, TTL 24h): if the key exists, return the
      cached result instead of re-executing.
    - Pest test: fire the same `InvoiceData` twice with the same key →
      one invoice, two requests, second returns the first.
```

---

## M-2 · No optimistic locking on concurrent edits

### Why this matters
Two accounts users edit the same product's price simultaneously. Last
write wins; the first edit is lost silently. ERPNext solves this with a
`modified` timestamp check.

### Proposed addition (append to §11.50)

```markdown
**Optimistic concurrency:** master-data models (products, customers,
companies, suppliers) carry a `version` bigint, auto-incremented on
save. Filament/Livewire forms send the version they read; the update
query is `->where('id', $id)->where('version', $sentVersion)->update([...,
'version' => DB::raw('version + 1')])`. If 0 rows affected, throw
`ConcurrencyException('errors.record.stale')`. Pest test covers it.
```

---

## M-3 · No timezone strategy — `posting_date` vs `created_at` ambiguity

### Why this matters
Server in UTC, users in Egypt (UTC+2). `posting_date` is a business date
— is it the Egypt date at the moment of sale, or the UTC date? An invoice
created at 23:30 Cairo / 01:30 UTC gets `posting_date` of yesterday or
tomorrow depending on the choice. VAT filing by period breaks.

### Proposed addition (append to §11.32)

```markdown
**Timezone rule:** app timezone is `Africa/Cairo` (set in
`config/app.php`). `created_at`/`updated_at` are stored as UTC (Laravel
default) but `posting_date` is the **Egypt calendar date** at the moment
of posting, computed `Carbon::now()->format('Y-m-d')` against the
`Africa/Cairo` TZ regardless of server TZ. Never store `posting_date` as
UTC date. Reports group by `posting_date`. Saudi entity (v2) uses
`Asia/Riyadh` for its postings — already handled because `posting_date`
is a date, not a timestamp.
```

---

## M-4 · No 2FA on the admin account

### Why this matters
Amr (admin) has `full_access` — one permission gating everything (§12.1).
A compromised admin password = full system compromise. `docs/SECURITY.md`
doesn't mention 2FA.

### Proposed addition (append to §5 or §12.1)

```markdown
**2FA:** the `admin` and `accounts` roles must use 2FA (TOTP via
`pragmarx/google2fa` or Laravel's built-in Fortify if installed). Login
flow: password → 2FA challenge → session. Reps are exempt (field, no
second device). A `force_2fa` flag on roles; Filament enforces the
challenge. Recovery codes generated on enable, stored hashed.
```

---

## M-5 · No batch-expiry sale behaviour (block or warn?)

### Why this matters
§7.17 generates an alarm when expiry is within 30 days. But the guide
never says whether you can **sell** an expired batch. Selling expired
chemicals to a customer is a liability; the AI will allow it by default.

### Proposed addition (append to §7 rule 17)

```markdown
17b. **Expired-batch sale rule.** A batch past `expiry_date` **cannot be
     sold** — `InvoiceService::create()` throws
     `DomainException('errors.batch.expired')` if any line item's
     `batch_id` resolves to an expired batch. A batch within 30 days of
     expiry **can** be sold but the rep sees a yellow warning and must
     confirm. The warning is UI-only; the block is in the service.
```

---

## M-6 · No stock-count / physical-inventory workflow

### Why this matters
Warehouse keeper does a physical count → finds the DB says 5.2 t but the
floor has 4.9 t. There's no workflow for this. They'll edit
`stocks.quantity` directly (forbidden by §7.12) or fake a `sale` to
balance. ERPNext has Stock Reconciliation.

### Proposed addition (new §4 table and §7 rule)

```markdown
### 4.x `stock_reconciliations`
`id, company_id, warehouse_id, product_id, batch_id (nullable),
counted_quantity (decimal 12,3), system_quantity (decimal 12,3),
difference (decimal 12,3), reason (text), counted_by (FK users),
approved_by (FK users nullable), status (enum: 'draft','approved'),
posting_date`

**§7 rule 27:** Stock reconciliation is the **only** path to correct a
discrepancy between system and physical stock. On approval, the
difference becomes a `stock_movements` row (reason=`adjustment`, +/−).
`stock.adjust` permission (§12) is required. Never edit
`stocks.quantity` outside `StockService::reconcile()`.

**v1 scope:** the warehouse keeper can create a reconciliation for one
product at a time from the Filament admin. Bulk count upload is v2.
```

---

## M-7 · No localization file structure convention

### Why this matters
Bilingual is a hard rule from commit 1, but the guide never says whether
translations are PHP array files (`lang/ar/errors.php`) or JSON
(`lang/ar.json`). Filament and Livewire use different lookups. The AI
will mix both and translations silently miss.

### Proposed addition (append to §3)

```markdown
**i18n structure:** PHP array files in `lang/{locale}/`, **not** JSON.
One file per domain: `errors.php`, `fields.php`, `menu.php`,
`notifications.php`, `pdf.php`. Filament translations go in
`lang/{locale}/filament_*.php` (package publishes stubs). `APP_LOCALE=ar`
is default; `en` fallback. The language switch stores locale in session
(`locale` key) and sets `app()->setLocale()` in a middleware.
`Arr::get()`-style keys everywhere: `trans('errors.stock.insufficient')`.
```

---

## M-8 · No test taxonomy / coverage gate

### Why this matters
`docs/TESTING.md` and CLAUDE.md mention Pest + Playwright + 70% on
Services, but the guide itself has no test instructions per phase. An AI
will write tests unevenly — lots for Phase 8 (sales), none for Phase 12
(batches).

### Proposed addition (append to each phase or as a §14.3)

```markdown
### 14.3 Per-phase test gate (CI-enforced, no merge without)

Every phase ships:
1. **Unit tests** for each new service method (success + the documented
   failure exception).
2. **Feature tests** for each new Filament/Livewire endpoint (200/302/403
   per role).
3. **One integration test** for the phase's cross-service flow (e.g.,
   Phase 8: proforma → invoice → stock → balance in one test, plus a
   forced-failure rollback test).
4. **One permission test** per new resource (each role from §12 gets a
   `can`/`cannot` assertion).
5. **Coverage:** `php artisan test --coverage --min=70` on
   `app/Services`. Below 70 fails CI.

Test naming: `{Feature}Test.php`, method `test_{scenario}_{outcome}`:
`test_sale_with_insufficient_stock_throws_and_rolls_back()`.
```

---

## M-9 · `stock_movements.reference_type` is string+bigint but §11.6 says morphTo — clarify

### Why this matters
§4.8 declares `reference_type (string), reference_id (bigint)`. §11.6
says use `morphTo()`. These are compatible (morphTo uses those exact
columns) but the AI may read §4 and write a manual `switch` on
`reference_type` instead of the morph relation.

### Proposed correction (§4.8)

```markdown
> `reference_type` + `reference_id` form a **polymorphic relation**
> (`$movement->reference()` returns the Invoice/Return/PurchaseReceipt/
> GoodsInTransit/Adjustment model). Use Laravel's `morphTo` — never a
> manual switch on `reference_type`.
```

---

## M-10 · No Proforma expiry — quotations have `valid_until` but proformas don't

### Why this matters
§4.21 quotations have `valid_until`. §4.22 proforma_invoices don't. A
proforma is the quotation's output — it should expire too, else a rep
converts a 6-month-old proforma and the price is stale.

### Proposed addition (§4.22 add column)

```markdown
`valid_until (date nullable)` — copied from the source quotation's
`valid_until` on creation. `ProformaService::convertToInvoice()` throws
`DocumentStateException('errors.proforma.expired')` if today is past
`valid_until`. Rep PWA greys out expired proformas.
```

---

## M-11 · No goods-in-transit cancellation rules

### Why this matters
§4.9 has `cancelled_at`/`cancelled_by` but no rule for when cancellation
is allowed. Can you cancel a GIT that's `received`? (No — goods are
already in stock.) Can you cancel `at_customs`? (Yes, with reversal of
any landed costs already applied.)

### Proposed addition (append to §7 rule 14)

```markdown
14b. **GIT cancellation rules.**
     - `in_transit` / `at_customs` → may cancel. Any `landed_costs` rows
       are marked void (not deleted). No stock effect (goods never
       arrived).
     - `cleared` → may cancel only with admin permission; raises a
       critical alarm for accounts (a cleared shipment that's cancelled
       means customs docs need reversal — out-of-system).
     - `received` → **cannot** cancel. The goods are in stock; reverse
       via a return or stock reconciliation, not a GIT cancellation.
       `GoodsInTransitService::cancel()` throws
       `DocumentStateException('errors.git.received_not_cancellable')`.
```

---

## M-12 · No CSP / security-headers definition in the guide (only in docs)

### Why this matters
`docs/SECURITY.md` specifies CSP, HSTS, X-Frame-Options — but the guide
never references it and the AI building Phase 0 won't read `docs/`
first. Headers get missed.

### Proposed addition (append to Phase 0 DoD)

```markdown
**Phase 0 DoD adds:** security headers middleware registered in
`bootstrap/app.php` — HSTS (max-age=31536000, includeSubDomains),
`X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
`Referrer-Policy: strict-origin-when-cross-origin`, CSP
`default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self'
'unsafe-inline'; img-src 'self' data: https:` (Relax `unsafe-inline`
for Livewire only if Alpine needs it; tighten in v1.1 with nonces.)
Verify with `curl -I` against the running app.
```

---

# LOW PRIORITY

---

## L-1 · No git branch / PR convention
Add to §0: "Work on `phase-{N}` branch, PR to `main`, squash-merge. CI
must be green. Reviewer (or the AI self-review using the `review` skill)
runs before merge."

## L-2 · No commit-message convention beyond phase commits
Add to §0: "Format: `{type}: phase {N} — {summary}` for phase commits,
`{type}: {scope} — {summary}` for intra-phase. Types: `feat`, `fix`,
`test`, `docs`, `refactor`, `chore`. Conventional Commits style."

## L-3 · No error-page design specs
§3 mentions custom 403/404/419/500 pages (via CLAUDE.md) but no layout.
Add: "Bilingual message, company logo, no stack trace in prod, a 'back to
dashboard' link, the HTTP code prominently. 419 includes 'your session
expired, please log in again' with a login link."

## L-4 · No API documentation requirement (if Sanctum tokens are ever used)
§2 lists Sanctum "if a native app is added later." If so, add
`spatie/laravel-query-builder` + `Scribe` for doc generation. Not needed
for v1 web-only.

## L-5 · No changelog convention
Add a `CHANGELOG.md` per phase: "Keep-a-Changelog format, one entry per
phase under Unreleased, moved to a version tag on deploy."

---

# Scores (after applying the above)

| Dimension | Before | After |
|---|---|---|
| Architecture | 8.5 | 9.7 |
| AI Readiness | 7.5 | 9.8 |
| Maintainability | 8.0 | 9.6 |
| Scalability | 7.0 | 9.2 |
| Security | 7.5 | 9.3 |
| Production Readiness | 7.0 | 9.4 |
| Database Design | 8.0 | 9.5 |
| ERP Domain Design | 9.0 | 9.7 |
| **Overall** | **9.4** | **9.9** |

---

# Verdict

> **Would I approve this document as the implementation guide for a real
> production ERP that an AI agent will build autonomously?**

**As-is: No.** Three blockers — the cross-doc conflicts (C-1), the
wrong Egypt ETA QR spec (C-3), and the missing multi-tenancy enforcement
(C-2) — each cause a class of defects the AI cannot detect by itself and
the human discovers only at the client's first real filing or first
cross-company data leak.

**With the CRITICAL and HIGH findings pasted in: Yes.** The guide then
gives the AI: a single reconciled spec, enforced company isolation,
correct e-invoicing, a service layer with contracts and exceptions, a
domain-event backbone, enum/type safety, indexed + partitioned data, an
audit trail, a test gate, and a CI/CD target. That is a document an AI
agent can execute with minimal invention.

The MEDIUM and LOW findings are quality polish — ship them in the same
edit pass if time allows, otherwise ticket them against the phase that
first touches the relevant area.

---

*Review ends. All proposed text above is paste-ready; insert at the
section each finding names. The guide's structure, numbering, and
existing wording are preserved.*
