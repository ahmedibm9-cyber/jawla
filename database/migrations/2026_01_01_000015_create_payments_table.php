<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->foreignId('visit_id')->nullable()->constrained('visits')->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['cash', 'cheque', 'transfer', 'other'])->default('cash');
            $table->dateTime('collected_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('company_id');
            $table->index('customer_id');
            $table->index('user_id');
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
