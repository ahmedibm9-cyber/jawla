<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unsignedBigInteger('batch_id')->nullable()->after('product_id');
            $table->decimal('valuation_rate', 12, 2)->nullable()->after('quantity_change');
            $table->date('posting_date')->nullable()->after('user_id');
            $table->decimal('quantity_change', 12, 3)->change();
        });

        // Drop old reason check and add new one with all enum values
        DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_reason_check');
        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_reason_check CHECK (reason::text = ANY (ARRAY['sale'::text, 'return'::text, 'transfer_in'::text, 'transfer_out'::text, 'adjustment'::text, 'initial'::text, 'purchase'::text, 'landed_cost'::text, 'transit_in'::text, 'transit_out'::text, 'inter_company'::text]))");

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->index('company_id');
            $table->index('batch_id');
            $table->index('posting_date');
            $table->index(['company_id', 'posting_date']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['batch_id']);
            $table->dropIndex(['posting_date']);
            $table->dropIndex(['company_id', 'posting_date']);
            $table->dropColumn(['company_id', 'batch_id', 'valuation_rate', 'posting_date']);
            $table->integer('quantity_change')->change();
        });

        DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_reason_check');
        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_reason_check CHECK (reason::text = ANY (ARRAY['sale'::text, 'return'::text, 'transfer_in'::text, 'transfer_out'::text, 'adjustment'::text, 'initial'::text]))");
    }
};
