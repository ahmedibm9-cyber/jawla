<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('customer_contacts')->nullOnDelete();
            $table->enum('direction', ['inbound', 'outbound'])->default('outbound');
            $table->integer('duration_seconds');
            $table->enum('outcome', ['reached', 'no_answer', 'busy', 'left_voicemail']);
            $table->text('notes')->nullable();
            $table->timestamp('called_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['company_id', 'customer_id']);
            $table->index(['user_id']);
            $table->index(['called_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
