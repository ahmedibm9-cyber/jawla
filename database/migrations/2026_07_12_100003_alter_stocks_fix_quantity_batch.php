<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add batch_id column WITHOUT FK (batches table not yet created)
        Schema::table('stocks', function (Blueprint $table): void {
            $table->unsignedBigInteger('batch_id')->nullable()->after('product_id');
            $table->decimal('quantity', 12, 3)->change();
        });

        // Drop the stock_type enum column (not in guide §4.7)
        if (Schema::hasColumn('stocks', 'stock_type')) {
            // Drop unique constraint first if it includes stock_type
            DB::statement('ALTER TABLE stocks DROP CONSTRAINT IF EXISTS stocks_warehouse_id_product_id_stock_type_unique');
            Schema::table('stocks', function (Blueprint $table): void {
                $table->dropColumn('stock_type');
            });
        }

        // Add proper unique indexes (partial for nullable batch_id)
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS stocks_wh_prod_batch_unique ON stocks (warehouse_id, product_id, batch_id) WHERE batch_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS stocks_wh_prod_unique ON stocks (warehouse_id, product_id) WHERE batch_id IS NULL');

        // CHECK constraint: quantity >= 0
        DB::statement('ALTER TABLE stocks DROP CONSTRAINT IF EXISTS stocks_quantity_check');
        DB::statement('ALTER TABLE stocks ADD CONSTRAINT stocks_quantity_check CHECK (quantity >= 0)');

        // Index on batch_id
        Schema::table('stocks', function (Blueprint $table): void {
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS stocks_wh_prod_batch_unique');
        DB::statement('DROP INDEX IF EXISTS stocks_wh_prod_unique');
        DB::statement('ALTER TABLE stocks DROP CONSTRAINT IF EXISTS stocks_quantity_check');

        Schema::table('stocks', function (Blueprint $table): void {
            $table->dropIndex(['batch_id']);
            $table->dropColumn('batch_id');
            $table->integer('quantity')->default(0)->change();
            $table->enum('stock_type', ['regular', 'returned_damaged'])->default('regular')->after('quantity');
            $table->unique(['warehouse_id', 'product_id', 'stock_type']);
        });
    }
};
