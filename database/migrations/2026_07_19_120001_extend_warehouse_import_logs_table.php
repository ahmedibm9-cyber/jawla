<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_import_logs', function (Blueprint $table): void {
            $table->string('status')->default('completed');
            $table->integer('rows_total')->default(0);
            $table->integer('rows_rejected')->default(0);
            $table->json('errors')->nullable();
            $table->string('checksum', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_import_logs', function (Blueprint $table): void {
            $table->dropColumn(['status', 'rows_total', 'rows_rejected', 'errors', 'checksum']);
        });
    }
};
