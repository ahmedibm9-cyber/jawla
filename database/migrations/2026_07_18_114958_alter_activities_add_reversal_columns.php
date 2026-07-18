<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->boolean('is_reversed')->default(false)->after('properties');
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete()->after('is_reversed');
            $table->dateTime('reversed_at')->nullable()->after('reversed_by');
            $table->foreignId('reversal_of')->nullable()->constrained('activities')->nullOnDelete()->after('reversed_at');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['reversal_of']);
            $table->dropForeign(['reversed_by']);
            $table->dropColumn(['is_reversed', 'reversed_by', 'reversed_at', 'reversal_of']);
        });
    }
};
