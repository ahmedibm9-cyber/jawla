<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name_ar');
            $table->string('name_en');
            $table->enum('type', ['main', 'van'])->default('main');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('company_id');
            $table->index(['type', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
