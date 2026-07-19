<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A rep may flag a pure stock shortage from the stock-search screen
        // where there is no customer context (B6-02: customer/visit optional).
        Schema::table('out_of_stock_requests', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('out_of_stock_requests', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable(false)->change();
        });
    }
};
