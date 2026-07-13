<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alarm_reads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('alarm_id')->constrained('alarms')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('acknowledged')->default(false);
            $table->boolean('resolved')->default(false);
            $table->datetime('read_at')->nullable();
            $table->datetime('acknowledged_at')->nullable();
            $table->datetime('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['alarm_id', 'user_id']);
            $table->index(['user_id', 'acknowledged']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alarm_reads');
    }
};