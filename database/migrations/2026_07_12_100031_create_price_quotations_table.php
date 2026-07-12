<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_quotations', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('price_quotation_request_id')->constrained('price_quotation_requests')->onDelete('cascade');
            $table->decimal('base_price', 12, 2);
            $table->decimal('manager_plus', 12, 2)->default(0);
            $table->decimal('manager_minus', 12, 2)->default(0);
            $table->decimal('rep_plus', 12, 2)->default(0);
            $table->decimal('rep_minus', 12, 2)->default(0);
            $table->foreignId('priced_by')->constrained('users')->onDelete('cascade');
            $table->datetime('priced_at');
            $table->date('valid_until')->nullable();
            $table->timestamps();
            $table->index('price_quotation_request_id');
            $table->index('priced_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_quotations');
    }
};