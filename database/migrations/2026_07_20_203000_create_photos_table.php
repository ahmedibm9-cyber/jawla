<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // Polymorphic owner; nullable so a photo can be captured before its
            // parent record exists and attached on save.
            $table->string('photable_type')->nullable();
            $table->unsignedBigInteger('photable_id')->nullable();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index(['photable_type', 'photable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
