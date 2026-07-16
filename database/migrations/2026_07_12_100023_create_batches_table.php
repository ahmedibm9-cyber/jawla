<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('batch_number');
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('coa_file_path')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->date('received_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('product_id');
            $table->index('supplier_id');
            $table->index('batch_number');
            $table->index('expiry_date');
            $table->unique(['product_id', 'batch_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
