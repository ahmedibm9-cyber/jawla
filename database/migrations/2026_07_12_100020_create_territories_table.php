<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('territories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name_ar');
            $table->string('name_en');
            $table->foreignId('parent_id')->nullable()->constrained('territories')->onDelete('set null');
            $table->unsignedInteger('lft')->nullable();
            $table->unsignedInteger('rgt')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('company_id');
            $table->index('parent_id');
            $table->index(['lft', 'rgt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territories');
    }
};