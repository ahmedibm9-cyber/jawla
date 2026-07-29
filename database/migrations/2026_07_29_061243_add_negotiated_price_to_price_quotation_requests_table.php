<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('price_quotation_requests', function (Blueprint $table): void {
            $table->decimal('negotiated_price', 12, 2)->nullable()->after('quantity_requested');
        });
    }

    public function down(): void
    {
        Schema::table('price_quotation_requests', function (Blueprint $table): void {
            $table->dropColumn('negotiated_price');
        });
    }
};
