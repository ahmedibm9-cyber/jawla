<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_in_transit', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('shipment_number')->unique();
            $table->enum('status', ['in_transit', 'at_customs', 'cleared', 'partial_received', 'received'])->default('in_transit');
            $table->date('estimated_arrival_date')->nullable();
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('customs_cost', 12, 2)->default(0);
            $table->decimal('clearance_cost', 12, 2)->default(0);
            $table->decimal('freight_cost', 12, 2)->default(0);
            $table->date('posting_date');
            $table->datetime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index('company_id');
            $table->index('supplier_id');
            $table->index('purchase_order_id');
            $table->index('status');
            $table->index(['company_id', 'status']);
            $table->index('posting_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_in_transit');
    }
};