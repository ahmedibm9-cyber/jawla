<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            $table->string('arrival_flag', 20)->nullable();
            $table->unsignedInteger('checkin_distance_m')->nullable();
            $table->unsignedInteger('checkin_accuracy_m')->nullable();

            // Visits are created from daily assignments before arrival, so
            // check-in data cannot be required at insert time.
            $table->decimal('checkin_latitude', 10, 7)->nullable()->change();
            $table->decimal('checkin_longitude', 10, 7)->nullable()->change();
            $table->dateTime('checkin_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            $table->dropColumn(['arrival_flag', 'checkin_distance_m', 'checkin_accuracy_m']);
            $table->decimal('checkin_latitude', 10, 7)->nullable(false)->change();
            $table->decimal('checkin_longitude', 10, 7)->nullable(false)->change();
            $table->dateTime('checkin_at')->nullable(false)->change();
        });
    }
};
