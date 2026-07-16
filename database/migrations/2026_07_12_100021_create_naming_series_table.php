<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('naming_series', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('prefix');
            $table->string('series_format');
            $table->bigInteger('current_number')->default(0);
            $table->integer('pad_length')->default(5);
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('company_id');
            $table->unique(['name', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('naming_series');
    }
};
