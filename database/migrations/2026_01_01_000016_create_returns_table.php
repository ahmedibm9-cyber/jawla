<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('visit_id')->nullable()->constrained('visits')->onDelete('set null');
            $table->string('return_number')->unique();
            $table->decimal('total', 12, 2);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'closed', 'rejected'])->default('pending');
            $table->dateTime('returned_at');
            $table->timestamps();
            $table->softDeletes();
            $table->index('company_id');
            $table->index('customer_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};
