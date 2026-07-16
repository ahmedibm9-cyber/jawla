<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove soft deletes from invoices (§11.44: never delete)
        if (Schema::hasColumn('invoices', 'deleted_at')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->unsignedBigInteger('proforma_invoice_id')->nullable()->after('visit_id');
            $table->text('eta_qr')->nullable()->after('remaining_amount');
            $table->text('zatca_qr')->nullable()->after('eta_qr');
            $table->date('posting_date')->nullable()->after('issued_at');
            $table->datetime('cancelled_at')->nullable()->after('posting_date');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null')->after('cancelled_at');
            $table->foreignId('amended_from')->nullable()->constrained('invoices')->onDelete('set null')->after('cancelled_by');

            $table->index('proforma_invoice_id');
            $table->index('posting_date');
            $table->index(['company_id', 'posting_date']);
            $table->index('amended_from');
        });

        // Drop old enum constraint and add new one matching guide §4.24
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check');
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status::text = ANY (ARRAY['draft'::text, 'submitted'::text, 'cancelled'::text, 'amended'::text]))");

        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_payment_status_check');
        // payment_status is computed, drop it
        if (Schema::hasColumn('invoices', 'payment_status')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropColumn('payment_status');
            });
        }

        // Change status default from 'pending' to 'draft'
        DB::statement("ALTER TABLE invoices ALTER COLUMN status SET DEFAULT 'draft'");
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex(['proforma_invoice_id']);
            $table->dropIndex(['posting_date']);
            $table->dropIndex(['company_id', 'posting_date']);
            $table->dropIndex(['amended_from']);
            $table->dropColumn([
                'proforma_invoice_id', 'eta_qr', 'zatca_qr',
                'posting_date', 'cancelled_at', 'cancelled_by', 'amended_from',
            ]);
            $table->enum('payment_status', ['unpaid', 'partially_paid', 'fully_paid'])->default('unpaid')->after('remaining_amount');
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check');
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'confirmed'::text, 'delivered'::text, 'cancelled'::text]))");
        DB::statement("ALTER TABLE invoices ALTER COLUMN status SET DEFAULT 'pending'");
    }
};
