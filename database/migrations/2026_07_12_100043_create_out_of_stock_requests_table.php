<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('out_of_stock_requests', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('quantity_requested', 12, 3);
            $table->text('notes')->nullable();
            $table->enum('status', ['open', 'fulfilled', 'cancelled'])->default('open');
            $table->timestamps();
            $table->index('company_id');
            $table->index('user_id');
            $table->index('customer_id');
            $table->index('product_id');
            $table->index('status');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('out_of_stock_requests');
    }
};