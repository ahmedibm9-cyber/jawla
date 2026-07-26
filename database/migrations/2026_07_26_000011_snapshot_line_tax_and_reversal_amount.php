<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->decimal('tax_amount', 12, 2)->default(0)->after('line_total');
        });
        DB::statement(<<<'SQL'
UPDATE invoice_items AS item
SET tax_amount = ROUND(
    item.line_total * invoice.vat_amount / NULLIF(invoice.subtotal, 0),
    2
)
FROM invoices AS invoice
WHERE invoice.id = item.invoice_id
  AND invoice.vat_amount > 0
  AND invoice.subtotal > 0
SQL);

        Schema::table('reversals', function (Blueprint $table): void {
            $table->decimal('amount', 12, 2)->default(0)->after('status');
        });

        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_visit_id_foreign');
        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_visit_id_foreign FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE RESTRICT');
    }

    public function down(): void
    {
        Schema::table('reversals', function (Blueprint $table): void {
            $table->dropColumn('amount');
        });
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropColumn('tax_amount');
        });
    }
};
