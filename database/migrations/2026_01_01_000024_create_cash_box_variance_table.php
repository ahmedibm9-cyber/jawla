<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_box_variance', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->date('variance_date');
            $table->decimal('expected_balance', 12, 2);
            $table->decimal('actual_balance', 12, 2);
            $table->decimal('variance_amount', 12, 2);
            $table->enum('variance_type', ['overage', 'shortage'])->default('shortage');
            $table->enum('status', ['open', 'reviewed', 'resolved', 'waived'])->default('open');
            $table->text('notes')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('company_id');
            $table->index('status');
            $table->index('variance_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_box_variance');
    }
};
