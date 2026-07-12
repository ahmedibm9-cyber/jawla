<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('van_transfer_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('van_transfer_id')->constrained('van_transfers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->integer('quantity');
            $table->timestamps();
            $table->index('van_transfer_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('van_transfer_items');
    }
};
