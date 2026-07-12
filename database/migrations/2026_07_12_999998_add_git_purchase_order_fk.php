<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_in_transit', function (Blueprint $table): void {
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
        });
        Schema::table('landed_costs', function (Blueprint $table): void {
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('goods_in_transit', function (Blueprint $table): void {
            $table->dropForeign(['purchase_order_id']);
        });
        Schema::table('landed_costs', function (Blueprint $table): void {
            $table->dropForeign(['purchase_order_id']);
        });
    }
};