<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landed_costs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('goods_in_transit_id')->nullable()->constrained('goods_in_transit')->onDelete('cascade');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->enum('cost_type', ['shipping', 'customs', 'clearance', 'freight', 'insurance', 'duty', 'port_charges', 'other'])->default('shipping');
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('goods_in_transit_id');
            $table->index('purchase_order_id');
            $table->index('cost_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landed_costs');
    }
};