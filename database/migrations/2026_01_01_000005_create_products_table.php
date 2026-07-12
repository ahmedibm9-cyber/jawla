<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('product_categories')->onDelete('restrict');
            $table->string('sku')->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->enum('unit', ['piece', 'box', 'carton', 'kg', 'liter', 'gallon'])->default('piece');
            $table->decimal('price', 12, 2);
            $table->decimal('cost', 12, 2);
            $table->boolean('vat_applicable')->default(true);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('company_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
