<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alarms', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->enum('type', [
                'out_of_stock_request', 'customer_complaint', 'new_customer_pending',
                'price_quotation_requested', 'purchase_request', 'goods_in_transit_delayed',
                'batch_expiring',
            ])->default('out_of_stock_request');
            $table->string('reference_type')->nullable();
            $table->bigInteger('reference_id')->nullable();
            $table->string('title');
            $table->text('description');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->boolean('is_read')->default(false);
            $table->foreignId('read_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('read_at')->nullable();
            $table->timestamps();
            $table->index('company_id');
            $table->index('type');
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'severity']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alarms');
    }
};
