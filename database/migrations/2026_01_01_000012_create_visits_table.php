<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('route_id')->constrained('routes')->onDelete('restrict');
            $table->foreignId('work_session_id')->constrained('work_sessions')->onDelete('cascade');
            $table->enum('purpose', ['sale', 'collection', 'return', 'survey', 'other'])->default('sale');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->boolean('is_out_of_route')->default(false);
            $table->decimal('checkin_latitude', 10, 7);
            $table->decimal('checkin_longitude', 10, 7);
            $table->dateTime('checkin_at');
            $table->decimal('checkout_latitude', 10, 7)->nullable();
            $table->decimal('checkout_longitude', 10, 7)->nullable();
            $table->dateTime('checkout_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('customer_id');
            $table->index('work_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
