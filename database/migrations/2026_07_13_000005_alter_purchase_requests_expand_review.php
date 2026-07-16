<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE purchase_requests DROP CONSTRAINT IF EXISTS purchase_requests_status_check');
        DB::statement("ALTER TABLE purchase_requests ADD CONSTRAINT purchase_requests_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'sales_approved'::text, 'purchasing_approved'::text, 'rejected_by_sales'::text, 'rejected_by_purchasing'::text]))");

        Schema::table('purchase_requests', function (Blueprint $table): void {
            $table->foreignId('sales_reviewed_by')->nullable()->after('reviewed_by')->constrained('users')->onDelete('set null');
            $table->datetime('sales_reviewed_at')->nullable()->after('sales_reviewed_by');
            $table->foreignId('purchasing_reviewed_by')->nullable()->after('sales_reviewed_at')->constrained('users')->onDelete('set null');
            $table->datetime('purchasing_reviewed_at')->nullable()->after('purchasing_reviewed_by');
            $table->text('sales_review_notes')->nullable()->after('review_notes');
            $table->text('purchasing_review_notes')->nullable()->after('sales_review_notes');

            $table->dropForeign(['reviewed_by']);
            $table->dropColumn('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table): void {
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dropColumn(['sales_reviewed_by', 'sales_reviewed_at', 'purchasing_reviewed_by', 'purchasing_reviewed_at', 'sales_review_notes', 'purchasing_review_notes']);
        });

        DB::statement('ALTER TABLE purchase_requests DROP CONSTRAINT IF EXISTS purchase_requests_status_check');
        DB::statement("ALTER TABLE purchase_requests ADD CONSTRAINT purchase_requests_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'reviewed_by_sales'::text, 'approved'::text, 'rejected'::text]))");
    }
};
