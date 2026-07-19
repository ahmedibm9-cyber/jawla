<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table): void {
            $table->date('expires_at')->nullable()->after('status');
            $table->foreignId('purchase_order_id')->nullable()
                ->constrained('purchase_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('purchase_order_id');
            $table->dropColumn('expires_at');
        });
    }
};
