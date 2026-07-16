<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('quantity', 12, 3);
            $table->decimal('offered_price', 12, 2);
            $table->string('currency')->default('EGP');
            $table->text('payment_terms')->nullable();
            $table->enum('status', ['pending', 'reviewed_by_sales', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->index('company_id');
            $table->index('user_id');
            $table->index('supplier_id');
            $table->index('product_id');
            $table->index('status');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
