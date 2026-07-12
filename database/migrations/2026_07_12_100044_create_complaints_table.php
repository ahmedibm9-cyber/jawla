<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('visit_id')->nullable()->constrained('visits')->onDelete('set null');
            $table->enum('complaint_type', [
                'non_conforming_materials', 'delivery_issue', 'quality_issue', 'pricing_issue', 'other',
            ])->default('other');
            $table->text('description');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution')->nullable();
            $table->datetime('resolved_at')->nullable();
            $table->timestamps();
            $table->index('company_id');
            $table->index('customer_id');
            $table->index('user_id');
            $table->index('visit_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};