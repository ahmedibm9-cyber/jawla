<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_prices', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->boolean('is_customer_override')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['reason', 'is_customer_override']);
        });
    }
};
