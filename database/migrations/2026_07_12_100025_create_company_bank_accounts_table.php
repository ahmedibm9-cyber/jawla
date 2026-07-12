<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_bank_accounts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('iban')->nullable();
            $table->string('swift')->nullable();
            $table->string('currency')->default('EGP');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index('company_id');
            $table->index(['company_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_bank_accounts');
    }
};