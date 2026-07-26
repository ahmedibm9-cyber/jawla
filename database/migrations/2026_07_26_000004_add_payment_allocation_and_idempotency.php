<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->decimal('allocated_amount', 12, 2)->default(0)->after('amount');
            $table->decimal('unallocated_amount', 12, 2)->default(0)->after('allocated_amount');
            $table->string('intent_id', 128)->nullable()->after('unallocated_amount');
            $table->unique(['company_id', 'intent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'intent_id']);
            $table->dropColumn(['allocated_amount', 'unallocated_amount', 'intent_id']);
        });
    }
};
