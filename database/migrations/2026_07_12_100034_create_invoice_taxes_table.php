<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_taxes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('tax_template_line_id')->nullable()->constrained('tax_template_lines')->onDelete('set null');
            $table->string('description');
            $table->decimal('rate', 5, 2);
            $table->decimal('amount', 12, 2);
            $table->boolean('included_in_rate')->default(false);
            $table->timestamps();
            $table->index('invoice_id');
            $table->index('tax_template_line_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_taxes');
    }
};
