<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->unsignedBigInteger('mode_of_payment_id')->nullable()->after('visit_id');
            $table->decimal('exchange_rate', 12, 6)->nullable()->after('mode_of_payment_id');
            $table->decimal('base_amount', 12, 2)->nullable()->after('exchange_rate');
            $table->date('posting_date')->nullable()->after('collected_at');
            $table->datetime('cancelled_at')->nullable()->after('posting_date');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null')->after('cancelled_at');

            $table->index('mode_of_payment_id');
            $table->index('posting_date');
        });

        // Migrate old `method` to mode_of_payment_id is handled by data migration
        // (for v1 we'll just leave the method column; phase 8+ will remove it)
        // Drop the `method` enum constraint to allow null
        if (Schema::hasColumn('payments', 'method')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->string('method')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['mode_of_payment_id']);
            $table->dropIndex(['posting_date']);
            $table->dropColumn([
                'mode_of_payment_id', 'exchange_rate', 'base_amount',
                'posting_date', 'cancelled_at', 'cancelled_by',
            ]);
        });

        if (Schema::hasColumn('payments', 'method')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->enum('method', ['cash', 'cheque', 'transfer', 'other'])->default('cash')->change();
            });
        }
    }
};