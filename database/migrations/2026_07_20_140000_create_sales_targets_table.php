<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_targets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // the rep
            $table->date('period_start');
            $table->date('period_end');
            $table->string('metric')->default('sales_amount'); // future: visits, collections
            $table->decimal('target_amount', 14, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('company_id');
            $table->index(['user_id', 'period_start', 'period_end']);
            $table->unique(['user_id', 'period_start', 'period_end', 'metric'], 'sales_targets_rep_period_metric_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
    }
};
