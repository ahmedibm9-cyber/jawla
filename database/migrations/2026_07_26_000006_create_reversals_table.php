<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reversals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('original_type');
            $table->unsignedBigInteger('original_id');
            $table->string('action', 32);
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->string('status', 24)->default('completed');
            $table->string('result_type')->nullable();
            $table->unsignedBigInteger('result_id')->nullable();
            $table->timestampsTz();
            $table->unique(['original_type', 'original_id', 'action']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reversals');
    }
};
