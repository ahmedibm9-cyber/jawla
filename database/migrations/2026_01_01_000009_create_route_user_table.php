<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('route_id')->constrained('routes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['route_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_user');
    }
};
