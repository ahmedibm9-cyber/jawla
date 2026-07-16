<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('price_list_id')->constrained('price_lists')->onDelete('cascade');
            $table->decimal('price', 12, 2);
            $table->string('uom')->nullable();
            $table->decimal('min_quantity', 12, 3)->default(0);
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->date('valid_from')->nullable();
            $table->date('valid_upto')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('product_id');
            $table->index('price_list_id');
            $table->index('customer_id');
            $table->index(['price_list_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
