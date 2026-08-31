<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop and recreate columns as nullable (safer than ->change() which requires doctrine/dbal)
        Schema::table('work_sessions', function (Blueprint $table): void {
            $table->dropColumn(['start_latitude', 'start_longitude']);
        });

        Schema::table('work_sessions', function (Blueprint $table): void {
            $table->decimal('start_latitude', 10, 7)->nullable()->after('started_at');
            $table->decimal('start_longitude', 10, 7)->nullable()->after('start_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('work_sessions', function (Blueprint $table): void {
            $table->decimal('start_latitude', 10, 7)->nullable(false)->change();
            $table->decimal('start_longitude', 10, 7)->nullable(false)->change();
        });
    }
};
