<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modes_of_payment', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['cash', 'cheque', 'bank_transfer', 'lc', 'credit_card', 'other'])->default('cash');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('company_id');
            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modes_of_payment');
    }
};
