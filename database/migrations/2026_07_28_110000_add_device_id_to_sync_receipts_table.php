<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_receipts', function (Blueprint $table) {
            $table->string('device_id', 190)->nullable()->after('payload_hash');
        });
    }

    public function down(): void
    {
        Schema::table('sync_receipts', function (Blueprint $table) {
            $table->dropColumn('device_id');
        });
    }
};
