<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_pings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('work_session_id')->nullable()->constrained('work_sessions')->onDelete('set null');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->dateTime('recorded_at');
            $table->timestamps();

            $table->index(['company_id', 'recorded_at']);
            $table->index(['user_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_pings');
    }
};
