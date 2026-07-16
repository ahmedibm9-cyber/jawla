<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_in_transit_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('goods_in_transit_id')->constrained('goods_in_transit')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('batch_id')->nullable()->constrained('batches')->onDelete('set null');
            $table->decimal('quantity', 12, 3);
            $table->decimal('received_quantity', 12, 3)->default(0);
            $table->decimal('unit_price', 12, 2);
            $table->enum('currency', ['USD', 'CNY', 'EUR', 'EGP'])->default('USD');
            $table->timestamps();
            $table->index('goods_in_transit_id');
            $table->index('product_id');
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_in_transit_items');
    }
};
