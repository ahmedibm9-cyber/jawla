# Test Fixes Summary

## Fixed Issues

### 1. CompanyIsolationTest - Test Isolation (FIXED)

**File:** `tests/Feature/Tenancy/CompanyIsolationTest.php`

**Problem:** Test was seeing 20 customers instead of 2 due to data leaking from other tests.

**Solution:** Added explicit `setUp()` and `tearDown()` methods to truncate Customer and Company tables before and after each test.

```php
protected function setUp(): void
{
    parent::setUp();
    Customer::query()->truncate();
    Company::query()->truncate();
}

protected function tearDown(): void
{
    Customer::query()->truncate();
    Company::query()->truncate();
    parent::tearDown();
}
```

### 2. AMEndToEndTest - Foreign Key Violation (FIXED)

**File:** `tests/Feature/AMEndToEndTest.php`

**Problem:** Test hardcoded `company_bank_account_id => 1` but the seeded bank account has a different auto-increment ID.

**Solution:** Look up the actual bank account for the company:

```php
use App\Models\CompanyBankAccount;

// Before creating ProformaInvoice
$bankAccount = CompanyBankAccount::where('company_id', $rep->company_id)->first();

$proforma = ProformaInvoice::create([
    // ...
    'company_bank_account_id' => $bankAccount?->id,
    // ...
]);
```

### 3. PdfQrCodeTest - Multiple Assertion Fixes (FIXED)

**File:** `tests/Feature/PdfQrCodeTest.php`

Fixed 3 test assertions:

- Saudi Phase 2 company name assertion (factory default is 'شركة اختبار')
- Egypt QR code format assertion (uses invoice_number|total, not company_id)
- Proforma PDF test (removed non-existent bankAccount eager load)

---

## Remaining Issues -Rempending Configuration Issues (Deadlocks)

### PostgreSQL Deadlock with RefreshDatabase

**Affected Tests:**

- `ExpenseServiceTest::test_log_expense_creates_expense_and_decrements_cashbox`
- `ReportsPageTest::test_invoice_factory_creates_valid_invoice`
- `ReportsPageTest::test_invoices_tab_shows_pagination_with_more_than_100`

**Error:**

```
SQLSTATE[40P01]: Deadlock detected
DETAIL: Process X waits for ShareRowExclusiveLock on relation Y; blocked by process Z.
Process Z waits for RowExclusiveLock on relation W; blocked by process X.
SQL: alter table "stock_movements" add constraint "stock_movements_company_id_foreign" foreign key ("company_id") references "companies" ("id") on delete cascade
```

**Root Cause:** PostgreSQL cannot run DDL (ALTER TABLE) inside a transaction. Laravel's `RefreshDatabase` trait wraps tests in transactions, but migrations (which include DDL) must run outside transactions. This causes deadlocks when:

1. Migration tries to add foreign key constraints
2. Another process holds a lock on related tables

**Solutions (choose one):**

#### Option A: Use DatabaseMigrations trait (slower but reliable)

Replace `use RefreshDatabase;` with `use DatabaseMigrations;` in affected test files.

#### Option B: Configure PostgreSQL to avoid deadlocks

Add to `phpunit.xml` or test bootstrap:

```xml
<env name="DB_CONNECTION" value="pgsql"/>
<!-- Ensure only one test process runs at a time -->
```

#### Option C: Use a separate test database with no parallel processes

Ensure tests run sequentially (default for phpunit) and database is clean before test suite:

```bash
php artisan migrate:fresh --env=testing --seed
php artisan test
```

#### Option D: Disable foreign key checks during tests (PostgreSQL)

In `config/database.php` for testing connection:

```php
'pgsql' => [
    // ...
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => true,
    ],
],
```

**Recommended:** Run migrations once before test suite, then use transactional tests:

```bash
# Before running tests
php artisan migrate:fresh --env=testing

# Run tests (they'll use transactions for isolation)
php artisan test
```

---

## Verification

Run individual tests to verify fixes:

```bash
# Test CompanyIsolationTest
php artisan test --filter=CompanyIsolationTest

# Test AMEndToEndTest
php artisan test --filter=AMEndToEndTest

# Test PdfQrCodeTest
php artisan test --filter=PdfQrCodeTest
```

Then run full suite (may need to address deadlock config):

```bash
php artisan test
```
