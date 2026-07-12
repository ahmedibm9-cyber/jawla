<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('visit_id')->nullable()->constrained('visits')->onDelete('set null');
            $table->string('invoice_number')->unique();
            $table->enum('status', ['pending', 'confirmed', 'delivered', 'cancelled'])->default('pending');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('vat_amount', 12, 2);
            $table->decimal('total', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2);
            $table->enum('payment_status', ['unpaid', 'partially_paid', 'fully_paid'])->default('unpaid');
            $table->dateTime('issued_at');
            $table->timestamps();
            $table->softDeletes();
            $table->index('company_id');
            $table->index('customer_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
