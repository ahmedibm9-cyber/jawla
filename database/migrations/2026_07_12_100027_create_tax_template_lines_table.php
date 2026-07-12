<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_template_lines', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('tax_template_id')->constrained('tax_templates')->onDelete('cascade');
            $table->string('description');
            $table->enum('charge_type', ['on_net_total', 'on_previous_row_amount', 'actual_amount'])->default('on_net_total');
            $table->decimal('rate', 5, 2)->default(0);
            $table->boolean('included_in_rate')->default(false);
            $table->integer('row_id')->nullable();
            $table->timestamps();
            $table->index('tax_template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_template_lines');
    }
};