<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('route_id')->constrained('routes')->onDelete('restrict');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->decimal('start_latitude', 10, 7);
            $table->decimal('start_longitude', 10, 7);
            $table->decimal('end_latitude', 10, 7)->nullable();
            $table->decimal('end_longitude', 10, 7)->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('route_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_sessions');
    }
};
