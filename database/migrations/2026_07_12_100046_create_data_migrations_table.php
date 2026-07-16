<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_migrations', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('table_name');
            $table->integer('rows_migrated')->default(0);
            $table->foreignId('migrated_by')->constrained('users')->onDelete('cascade');
            $table->enum('source', ['odoo_api', 'excel', 'manual'])->default('manual');
            $table->datetime('migrated_at');
            $table->timestamps();
            $table->index('table_name');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_migrations');
    }
};
