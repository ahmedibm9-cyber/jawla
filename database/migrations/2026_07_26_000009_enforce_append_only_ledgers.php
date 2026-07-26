<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'invoices',
        'invoice_items',
        'payments',
        'returns',
        'return_items',
        'stock_movements',
        'activities',
        'naming_series',
        'sync_receipts',
        'warehouse_import_logs',
        'stock_import_previews',
        'credit_notes',
        'credit_note_items',
        'customer_credits',
        'refunds',
        'reversals',
        'stock_count_sessions',
        'stock_count_items',
        'expenses',
        'cash_reconciliations',
        'van_transfers',
        'van_transfer_items',
    ];

    public function up(): void
    {
        foreach ([
            ['invoices', 'company_id', 'companies'],
            ['invoice_items', 'invoice_id', 'invoices'],
            ['payments', 'company_id', 'companies'],
            ['payments', 'customer_id', 'customers'],
            ['payments', 'user_id', 'users'],
            ['payments', 'invoice_id', 'invoices'],
            ['returns', 'company_id', 'companies'],
            ['returns', 'customer_id', 'customers'],
            ['returns', 'user_id', 'users'],
            ['return_items', 'return_id', 'returns'],
            ['stock_movements', 'warehouse_id', 'warehouses'],
            ['stock_movements', 'product_id', 'products'],
            ['sync_receipts', 'company_id', 'companies'],
            ['sync_receipts', 'user_id', 'users'],
            ['naming_series', 'company_id', 'companies'],
            ['expenses', 'company_id', 'companies'],
            ['expenses', 'user_id', 'users'],
            ['expenses', 'work_session_id', 'work_sessions'],
            ['expenses', 'cancelled_by', 'users'],
            ['cash_reconciliations', 'company_id', 'companies'],
            ['cash_reconciliations', 'user_id', 'users'],
            ['cash_reconciliations', 'work_session_id', 'work_sessions'],
            ['cash_reconciliations', 'reviewed_by', 'users'],
            ['van_transfers', 'company_id', 'companies'],
            ['van_transfers', 'from_user_id', 'users'],
            ['van_transfers', 'to_user_id', 'users'],
            ['van_transfers', 'in_transit_warehouse_id', 'warehouses'],
            ['van_transfer_items', 'van_transfer_id', 'van_transfers'],
            ['van_transfer_items', 'product_id', 'products'],
        ] as [$table, $column, $parent]) {
            $constraint = "{$table}_{$column}_foreign";
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY ({$column}) REFERENCES {$parent}(id) ON DELETE RESTRICT");
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION jawla_prevent_ledger_delete()
RETURNS trigger AS $$
BEGIN
    RAISE EXCEPTION 'Jawla ledger table % is append-only; use a compensating transaction', TG_TABLE_NAME
        USING ERRCODE = 'integrity_constraint_violation';
END;
$$ LANGUAGE plpgsql;
SQL);

        foreach ($this->tables as $table) {
            DB::statement("CREATE TRIGGER {$table}_append_only_delete BEFORE DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION jawla_prevent_ledger_delete()");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            DB::statement("DROP TRIGGER IF EXISTS {$table}_append_only_delete ON {$table}");
        }
        DB::statement('DROP FUNCTION IF EXISTS jawla_prevent_ledger_delete()');
    }
};
