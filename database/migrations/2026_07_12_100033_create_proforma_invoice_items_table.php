<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proforma_invoice_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('proforma_invoice_id')->constrained('proforma_invoices')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
            $table->index('proforma_invoice_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proforma_invoice_items');
    }
};
