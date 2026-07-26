<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_import_previews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('staged_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->text('file_path');
            $table->string('file_checksum', 64);
            $table->jsonb('parsed_rows');
            $table->jsonb('errors')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->string('status', 24)->default('staged');
            $table->timestampTz('expires_at');
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampsTz();

            $table->index(['company_id', 'status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_import_previews');
    }
};
