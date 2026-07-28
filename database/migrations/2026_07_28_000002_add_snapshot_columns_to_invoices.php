<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->json('snapshot_company')->nullable()->after('eta_response');
            $table->json('snapshot_customer')->nullable()->after('snapshot_company');
            $table->json('snapshot_items')->nullable()->after('snapshot_customer');
            $table->json('snapshot_totals')->nullable()->after('snapshot_items');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn(['snapshot_company', 'snapshot_customer', 'snapshot_items', 'snapshot_totals']);
        });
    }
};
