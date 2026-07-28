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
        Schema::table('sync_receipts', function (Blueprint $table) {
            $table->smallInteger('protocol_version')->default(1)->after('operation_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_receipts', function (Blueprint $table) {
            $table->dropColumn('protocol_version');
        });
    }
};
