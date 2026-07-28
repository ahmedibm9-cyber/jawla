<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_receipts', function (Blueprint $table) {
            $table->string('payload_hash', 64)->nullable()->after('operation_type');
        });
    }

    public function down(): void
    {
        Schema::table('sync_receipts', function (Blueprint $table) {
            $table->dropColumn('payload_hash');
        });
    }
};
