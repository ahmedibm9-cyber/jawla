<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->string('status')->default('approved')->after('note');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('status');
            $table->dateTime('approved_at')->nullable()->after('approved_by');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'approved_by', 'approved_at']);
        });
    }
};
