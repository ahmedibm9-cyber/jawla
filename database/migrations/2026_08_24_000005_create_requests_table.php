<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('requests')) {
            Schema::create('requests', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('type', ['discount', 'leave', 'price_override', 'other']);
                $table->string('title');
                $table->text('description');
                $table->enum('status', ['new', 'approved', 'rejected', 'done'])->default('new');
                $table->boolean('is_active')->default(true);
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'status']);
                $table->index(['company_id', 'user_id']);
                $table->index(['reviewed_by']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
