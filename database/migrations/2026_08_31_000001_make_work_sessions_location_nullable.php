<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_sessions', function (Blueprint $table): void {
            $table->decimal('start_latitude', 10, 7)->nullable()->change();
            $table->decimal('start_longitude', 10, 7)->nullable()->change();
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
