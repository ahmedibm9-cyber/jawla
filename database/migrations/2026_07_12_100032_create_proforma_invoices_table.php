<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proforma_invoices', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('visit_id')->nullable()->constrained('visits')->onDelete('set null');
            $table->foreignId('price_quotation_id')->nullable()->constrained('price_quotations')->onDelete('set null');
            $table->string('proforma_number')->unique();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('vat_amount', 12, 2);
            $table->decimal('total', 12, 2);
            $table->foreignId('company_bank_account_id')->nullable()->constrained('company_bank_accounts')->onDelete('set null');
            $table->enum('status', ['draft', 'sent', 'converted_to_invoice', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->date('valid_until')->nullable();
            $table->date('posting_date');
            $table->datetime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index('company_id');
            $table->index('customer_id');
            $table->index('user_id');
            $table->index('status');
            $table->index(['company_id', 'status']);
            $table->index('posting_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proforma_invoices');
    }
};
