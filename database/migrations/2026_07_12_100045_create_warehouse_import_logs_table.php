<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_import_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('imported_by')->constrained('users')->onDelete('cascade');
            $table->string('file_name');
            $table->integer('rows_imported')->default(0);
            $table->datetime('imported_at');
            $table->timestamps();
            $table->index('warehouse_id');
            $table->index('imported_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_import_logs');
    }
};