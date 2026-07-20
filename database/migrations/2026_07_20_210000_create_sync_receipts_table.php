<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // Client-generated idempotency key; unique per company so an operation
            // replayed by an offline client is applied exactly once.
            $table->string('idempotency_key');
            $table->string('operation_type');
            $table->json('response')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'idempotency_key']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_receipts');
    }
};
