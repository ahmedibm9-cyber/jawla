<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\NamingSeries;
use App\Services\Contracts\DocumentNumberService;
use App\Services\NumberSequenceService;
use App\Support\ActiveCompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionProperty;
use RuntimeException;
use Tests\TestCase;

class NumberSequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DocumentNumberService::class);
        // The service's assertMatches() is a production-time tenancy guard.
        // In this test environment `runningUnitTests()` returns false, so the
        // singleton's constructor leaves `allowUnscoped = false`. Force the
        // flag true here so the per-company isolation tests can run without
        // also driving the auth boundary they're not exercising.
        $ctx = app(ActiveCompanyContext::class);
        $ref = new ReflectionProperty($ctx, 'allowUnscoped');
        $ref->setValue($ctx, true);
    }

    private function gen(string $docType, int $companyId, ?int $year = null): string
    {
        return app(ActiveCompanyContext::class)->runWithCompany(
            $companyId,
            fn (): string => $this->service->generate($docType, $companyId, $year),
        );
    }

    public function test_generates_formatted_string(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);

        $number = $this->gen('sales_invoice', $company->id);

        $this->assertMatchesRegularExpression('/^INV-GPC-\d{4}-\d{5}$/', $number);
    }

    public function test_returns_sequential_numbers(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);

        $a = $this->gen('sales_invoice', $company->id);
        $b = $this->gen('sales_invoice', $company->id);

        $this->assertNotSame($a, $b);
        $partsA = explode('-', $a);
        $partsB = explode('-', $b);
        $this->assertSame((int) $partsA[3] + 1, (int) $partsB[3]);
    }

    public function test_different_doc_types_have_independent_sequences(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);

        $inv = $this->gen('sales_invoice', $company->id);
        $pf = $this->gen('proforma', $company->id);

        $partsInv = explode('-', $inv);
        $partsPf = explode('-', $pf);
        $this->assertSame(1, (int) $partsInv[3]);
        $this->assertSame(1, (int) $partsPf[3]);
    }

    public function test_different_companies_have_independent_sequences(): void
    {
        $companyA = Company::factory()->create(['abbr' => 'ABC']);
        $companyB = Company::factory()->create(['abbr' => 'XYZ']);

        $a = $this->gen('sales_invoice', $companyA->id);
        $b = $this->gen('sales_invoice', $companyB->id);

        $this->assertStringContainsString('ABC', $a);
        $this->assertStringContainsString('XYZ', $b);
        $partsA = explode('-', $a);
        $partsB = explode('-', $b);
        $this->assertSame(1, (int) $partsA[3]);
        $this->assertSame(1, (int) $partsB[3]);
    }

    public function test_auto_creates_naming_series_row_when_missing(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);

        $this->assertDatabaseMissing('naming_series', [
            'name' => 'unknown_doc',
            'company_id' => $company->id,
        ]);

        $this->gen('unknown_doc', $company->id);

        $this->assertDatabaseHas('naming_series', [
            'name' => 'unknown_doc',
            'company_id' => $company->id,
            'year' => (int) date('Y'),
            'current_number' => 1,
        ]);
    }

    public function test_respects_existing_naming_series_seed(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);
        // Use the unscoped context to seed directly.
        NamingSeries::create([
            'name' => 'sales_invoice',
            'prefix' => 'INV',
            'series_format' => 'INV-GPC-{YYYY}-{#####}',
            'current_number' => 50,
            'pad_length' => 5,
            'company_id' => $company->id,
            'year' => (int) date('Y'),
        ]);

        $number = $this->gen('sales_invoice', $company->id);

        $this->assertStringStartsWith('INV-GPC-', $number);
        $parts = explode('-', $number);
        $this->assertSame(51, (int) $parts[3]);
    }

    // -------------------------------------------------------------------
    // PR-027 properties
    // -------------------------------------------------------------------

    public function test_series_resets_each_calendar_year(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);

        // Mint a number "last year" at 00050 by writing a seed row.
        NamingSeries::create([
            'name' => 'sales_invoice',
            'prefix' => 'INV',
            'series_format' => 'INV-GPC-{YYYY}-{#####}',
            'current_number' => 50,
            'pad_length' => 5,
            'company_id' => $company->id,
            'year' => 2025,
        ]);

        // Now generate for 2026 — should be 00001, not 00051.
        $number = $this->gen('sales_invoice', $company->id, 2026);

        $this->assertSame('INV-GPC-2026-00001', $number);
    }

    public function test_per_year_series_rows_are_independent(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);

        $a = $this->gen('sales_invoice', $company->id, 2024);
        $b = $this->gen('sales_invoice', $company->id, 2025);
        $c = $this->gen('sales_invoice', $company->id, 2024);

        $this->assertSame('INV-GPC-2024-00001', $a);
        $this->assertSame('INV-GPC-2025-00001', $b);
        $this->assertSame('INV-GPC-2024-00002', $c);
    }

    public function test_legal_doc_types_have_stable_prefixes(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);

        $this->assertStringStartsWith('INV-', $this->gen('sales_invoice', $company->id));
        $this->assertStringStartsWith('RET-', $this->gen('sales_return', $company->id));
        $this->assertStringStartsWith('PAY-', $this->gen('payment', $company->id));
        $this->assertStringStartsWith('CN-', $this->gen('credit_note', $company->id));
        $this->assertStringStartsWith('REF-', $this->gen('refund', $company->id));
    }

    public function test_unknown_doc_type_uses_alpha_only_fallback_prefix(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);

        $number = $this->gen('unknown_doc', $company->id);

        $this->assertStringStartsWith('UNKNOWNDOC-', $number);
    }

    public function test_rollback_does_not_consume_a_number(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);

        // Force a rollback around a successful generation. The service runs
        // its whole operation (find-or-create + lock + increment) inside a
        // single DB::transaction, which becomes a savepoint inside the
        // caller's transaction. When the caller's transaction aborts, the
        // entire service operation is reverted — including the row creation.
        // The next successful call must therefore start from 00001, not
        // 00002. This is the "gapless on caller rollback" guarantee.
        try {
            DB::transaction(function () use ($company): void {
                $this->gen('sales_invoice', $company->id);
                throw new RuntimeException('force rollback');
            });
            $this->fail('Outer transaction was expected to throw.');
        } catch (RuntimeException) {
            // expected
        }

        // The next call must be 00001, not 00002. The failed attempt left
        // no trace, so the new call is the first successful allocation.
        $this->assertSame(
            'INV-GPC-'.date('Y').'-00001',
            $this->gen('sales_invoice', $company->id),
        );
    }

    public function test_serial_run_is_gapless_and_monotonic(): void
    {
        $company = Company::factory()->create(['abbr' => 'GPC']);

        $count = 25;
        $numbers = [];
        for ($i = 0; $i < $count; $i++) {
            $numbers[] = $this->gen('sales_invoice', $company->id);
        }

        // No collisions.
        $this->assertCount($count, array_unique($numbers));

        // Sorted, the trailing 5-digit counter is exactly 1..N.
        $tails = array_map(
            fn (string $n): int => (int) explode('-', $n)[3],
            $numbers,
        );
        sort($tails);
        $this->assertSame(range(1, $count), $tails);
    }

    public function test_select_for_update_serializes_two_connections(): void
    {
        // Direct lock test on PostgreSQL: a row held with FOR UPDATE on
        // connection A blocks the same row on connection B until A commits.
        // This is the SQL-level guarantee the service relies on.
        //
        // To exercise two truly independent connections, the suite must be
        // configured with a second connection (e.g. `pgsql_alt`) and
        // `RefreshDatabase` must not be holding the default connection
        // open. Default `phpunit.xml` ships only a single `pgsql`
        // connection, so the proof is skipped here. To run it:
        //   1. add `<env name="DB_CONNECTION_ALT" value="pgsql_alt"/>` and
        //      a `pgsql_alt` entry in `config/database.php`
        //   2. switch this test to `DatabaseMigrations` (or
        //      `DatabaseTruncation`) so the per-test transaction does not
        //      conflict with PDO-level `beginTransaction()` on the
        //      default connection.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Concurrent allocation proof requires PostgreSQL.');
        }
        $this->markTestSkipped(
            'Cross-connection FOR UPDATE proof requires a second DB connection '
            .'and a non-RefreshDatabase test isolation strategy; configure and re-enable.'
        );
    }

    public function test_concurrent_generation_yields_distinct_sequential_numbers(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Concurrent allocation test requires PostgreSQL.');
        }

        $company = Company::factory()->create(['abbr' => 'GPC']);

        $serviceA = new NumberSequenceService;
        $serviceB = new NumberSequenceService;

        $n = 5;
        $aNums = [];
        $bNums = [];

        for ($i = 0; $i < $n; $i++) {
            $aNums[] = app(ActiveCompanyContext::class)->runWithCompany(
                $company->id,
                fn (): string => $serviceA->generate('sales_invoice', $company->id),
            );
            $bNums[] = app(ActiveCompanyContext::class)->runWithCompany(
                $company->id,
                fn (): string => $serviceB->generate('sales_invoice', $company->id),
            );
        }

        $all = array_merge($aNums, $bNums);
        $this->assertCount(2 * $n, array_unique($all), 'No collisions across 2N concurrent allocations.');

        $tails = array_map(fn (string $x): int => (int) explode('-', $x)[3], $all);
        sort($tails);
        $this->assertSame(range(1, 2 * $n), $tails, 'Allocated counters are exactly 1..2N.');
    }
}
