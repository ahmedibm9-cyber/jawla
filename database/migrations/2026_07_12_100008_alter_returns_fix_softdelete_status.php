<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove soft deletes from returns (§11.44)
        if (Schema::hasColumn('returns', 'deleted_at')) {
            Schema::table('returns', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }

        Schema::table('returns', function (Blueprint $table): void {
            $table->foreignId('against_invoice_id')->nullable()->constrained('invoices')->onDelete('set null')->after('visit_id');
            $table->date('posting_date')->nullable()->after('returned_at');
            $table->datetime('cancelled_at')->nullable()->after('posting_date');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null')->after('cancelled_at');

            $table->index('against_invoice_id');
            $table->index('posting_date');
        });

        // Fix status enum: draft/submitted/cancelled per §4.32
        DB::statement('ALTER TABLE returns DROP CONSTRAINT IF EXISTS returns_status_check');
        DB::statement("ALTER TABLE returns ADD CONSTRAINT returns_status_check CHECK (status::text = ANY (ARRAY['draft'::text, 'submitted'::text, 'cancelled'::text]))");
        DB::statement("ALTER TABLE returns ALTER COLUMN status SET DEFAULT 'draft'");
    }

    public function down(): void
    {
        Schema::table('returns', function (Blueprint $table): void {
            $table->dropIndex(['against_invoice_id']);
            $table->dropIndex(['posting_date']);
            $table->dropColumn(['against_invoice_id', 'posting_date', 'cancelled_at', 'cancelled_by']);
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE returns DROP CONSTRAINT IF EXISTS returns_status_check');
        DB::statement("ALTER TABLE returns ADD CONSTRAINT returns_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'confirmed'::text, 'closed'::text, 'rejected'::text]))");
        DB::statement("ALTER TABLE returns ALTER COLUMN status SET DEFAULT 'pending'");
    }
};
