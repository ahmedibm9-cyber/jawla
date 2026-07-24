<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unique(['company_id', 'sku']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['company_id', 'employee_code']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'sku']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'employee_code']);
        });
    }
};
