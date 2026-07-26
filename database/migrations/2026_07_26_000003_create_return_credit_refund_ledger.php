<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE warehouses DROP CONSTRAINT IF EXISTS warehouses_type_check');
        DB::statement("ALTER TABLE warehouses ADD CONSTRAINT warehouses_type_check CHECK (type::text = ANY (ARRAY['main','van','quarantine','in_transit']))");

        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('credited_amount', 12, 2)->default(0)->after('paid_amount');
        });
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check');
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status::text = ANY (ARRAY['draft','issued','submitted','partially_paid','paid','credited','voided','cancelled','amended']))");

        Schema::table('return_items', function (Blueprint $table): void {
            $table->foreignId('invoice_item_id')->nullable()->after('return_id')->constrained('invoice_items')->restrictOnDelete();
            $table->string('condition', 24)->default('sellable')->after('batch_id');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('line_total');
            $table->decimal('total', 12, 2)->default(0)->after('tax_amount');
            $table->index('invoice_item_id');
        });

        Schema::create('credit_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->foreignId('return_id')->unique()->constrained('returns')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('credit_number')->unique();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('status', 24)->default('issued');
            $table->text('reason');
            $table->timestampTz('issued_at');
            $table->timestampsTz();
        });

        Schema::create('credit_note_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('credit_note_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('return_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestampsTz();
        });

        Schema::create('customer_credits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->restrictOnDelete();
            $table->foreignId('return_id')->nullable()->unique()->constrained('returns')->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->unique()->constrained('payments')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('credit_number')->unique();
            $table->decimal('amount', 12, 2);
            $table->decimal('remaining_amount', 12, 2);
            $table->string('status', 24)->default('available');
            $table->text('reason');
            $table->timestampsTz();
            $table->index(['company_id', 'customer_id', 'status']);
        });

        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_credit_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('refund_number')->unique();
            $table->string('method', 24);
            $table->decimal('amount', 12, 2);
            $table->string('status', 24)->default('pending_approval');
            $table->text('reason');
            $table->string('external_reference')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE returns DROP CONSTRAINT IF EXISTS returns_against_invoice_id_foreign');
        DB::statement('ALTER TABLE returns ADD CONSTRAINT returns_against_invoice_id_foreign FOREIGN KEY (against_invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT');
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('customer_credits');
        Schema::dropIfExists('credit_note_items');
        Schema::dropIfExists('credit_notes');

        Schema::table('return_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('invoice_item_id');
            $table->dropColumn(['condition', 'tax_amount', 'total']);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('credited_amount');
        });

        DB::statement('ALTER TABLE warehouses DROP CONSTRAINT IF EXISTS warehouses_type_check');
        DB::statement("ALTER TABLE warehouses ADD CONSTRAINT warehouses_type_check CHECK (type::text = ANY (ARRAY['main','van']))");
    }
};
