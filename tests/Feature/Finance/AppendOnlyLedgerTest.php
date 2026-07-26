<?php

namespace Tests\Feature\Finance;

use App\Exceptions\Domain\DomainException;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppendOnlyLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_orm_and_direct_database_deletion_of_ledger_rows_are_blocked(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
        ]);

        try {
            $invoice->delete();
            $this->fail('Eloquent deleted an append-only invoice.');
        } catch (DomainException) {
            $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
        }

        $this->expectException(QueryException::class);
        DB::table('invoices')->where('id', $invoice->id)->delete();
    }

    public function test_parent_deletion_cannot_cascade_financial_history(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);
        Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
        ]);

        $this->expectException(QueryException::class);
        DB::table('companies')->where('id', $company->id)->delete();
    }

    public function test_delete_triggers_cover_all_phase_two_and_existing_operational_ledgers(): void
    {
        $expected = [
            'activities', 'cash_reconciliations', 'credit_note_items', 'credit_notes',
            'customer_credits', 'expenses', 'invoice_items', 'invoices', 'naming_series',
            'payments', 'refunds', 'return_items', 'returns', 'reversals',
            'stock_count_items', 'stock_count_sessions', 'stock_movements',
            'sync_receipts', 'van_transfer_items', 'van_transfers',
        ];
        $actual = collect(DB::select(<<<'SQL'
SELECT c.relname AS table_name
FROM pg_trigger t
JOIN pg_class c ON c.oid = t.tgrelid
WHERE NOT t.tgisinternal
  AND t.tgname LIKE '%_append_only_delete'
SQL))->pluck('table_name')->all();

        foreach ($expected as $table) {
            $this->assertContains($table, $actual, "Missing append-only trigger for {$table}");
        }
    }
}
