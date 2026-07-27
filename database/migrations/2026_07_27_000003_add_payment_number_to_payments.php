<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PR-027: add a `payment_number` column to payments so each payment
     * carries the same gapless, per-(company, year) legal number that
     * invoices, returns, credit notes, and refunds already carry. The
     * column is added nullable so the migration is non-destructive for
     * any historical payments written before the column existed; new
     * payments always receive a number from the numbering service.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('payment_number')->nullable()->unique()->after('intent_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['payment_number']);
            $table->dropColumn('payment_number');
        });
    }
};
