<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table): void {
            $table->foreignId('cash_box_id')->nullable()->after('customer_credit_id')
                ->constrained('cash_boxes')->restrictOnDelete();
            $table->string('intent_id')->after('refund_number');
            $table->unique(['company_id', 'intent_id']);
            $table->unique('external_reference');
        });

        DB::statement('ALTER TABLE refunds ADD CONSTRAINT refunds_amount_positive CHECK (amount > 0)');
        DB::statement("ALTER TABLE refunds ADD CONSTRAINT refunds_method_check CHECK (method IN ('cash', 'bank', 'card'))");
        DB::statement("ALTER TABLE refunds ADD CONSTRAINT refunds_status_check CHECK (status IN ('pending_approval', 'pending_external', 'completed', 'rejected'))");
        DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_reason_check');
        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_reason_check CHECK (reason::text = ANY (ARRAY['sale','return','transfer_in','transfer_out','adjustment','initial','purchase','landed_cost','transit_in','transit_out','inter_company','reversal']))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE refunds DROP CONSTRAINT IF EXISTS refunds_status_check');
        DB::statement('ALTER TABLE refunds DROP CONSTRAINT IF EXISTS refunds_method_check');
        DB::statement('ALTER TABLE refunds DROP CONSTRAINT IF EXISTS refunds_amount_positive');
        DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_reason_check');
        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_reason_check CHECK (reason::text = ANY (ARRAY['sale','return','transfer_in','transfer_out','adjustment','initial','purchase','landed_cost','transit_in','transit_out','inter_company']))");

        Schema::table('refunds', function (Blueprint $table): void {
            $table->dropUnique(['external_reference']);
            $table->dropUnique(['company_id', 'intent_id']);
            $table->dropConstrainedForeignId('cash_box_id');
            $table->dropColumn('intent_id');
        });
    }
};
