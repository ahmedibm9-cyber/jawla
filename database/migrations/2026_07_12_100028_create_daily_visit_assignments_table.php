<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_visit_assignments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->date('visit_date');
            $table->enum('status', ['pending', 'completed', 'missed'])->default('pending');
            $table->integer('sort_order')->default(0);
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->index('company_id');
            $table->index('user_id');
            $table->index('customer_id');
            $table->index('visit_date');
            $table->index(['user_id', 'visit_date']);
            $table->unique(['user_id', 'customer_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_visit_assignments');
    }
};
