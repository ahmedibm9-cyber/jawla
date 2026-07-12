<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_reports', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('visit_id')->constrained('visits')->onDelete('cascade');
            $table->text('summary');
            $table->text('customer_feedback')->nullable();
            $table->text('action_taken')->nullable();
            $table->boolean('follow_up_needed')->default(false);
            $table->text('follow_up_note')->nullable();
            $table->datetime('submitted_at')->nullable();
            $table->timestamps();
            $table->index('visit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_reports');
    }
};