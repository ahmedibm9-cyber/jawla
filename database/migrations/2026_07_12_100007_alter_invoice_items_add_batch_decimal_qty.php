<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('batch_id')->nullable()->after('product_id');
            $table->decimal('quantity', 12, 3)->change();
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropIndex(['batch_id']);
            $table->dropColumn('batch_id');
            $table->integer('quantity')->change();
        });
    }
};
