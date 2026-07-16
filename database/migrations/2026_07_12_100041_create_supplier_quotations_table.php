<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_quotations', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests')->onDelete('set null');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->string('currency');
            $table->text('payment_terms')->nullable();
            $table->integer('delivery_time_days')->nullable();
            $table->date('valid_until')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index('purchase_request_id');
            $table->index('company_id');
            $table->index('supplier_id');
            $table->index('product_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_quotations');
    }
};
