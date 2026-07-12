<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_queue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('transaction_type');
            $table->bigInteger('entity_id');
            $table->longText('data_json');
            $table->enum('status', ['pending', 'synced', 'failed', 'rejected'])->default('pending');
            $table->string('idempotency_key')->unique();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('status');
            $table->index('transaction_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_queue');
    }
};
