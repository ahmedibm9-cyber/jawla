<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('van_transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('to_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->dateTime('accepted_at')->nullable();
            $table->timestamps();
            $table->index('company_id');
            $table->index('from_user_id');
            $table->index('to_user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('van_transfers');
    }
};
