<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->date('posting_date')->nullable()->after('spent_at');
            $table->index('posting_date');
        });

        // Drop status enum (not in guide §4.34)
        if (Schema::hasColumn('expenses', 'status')) {
            Schema::table('expenses', function (Blueprint $table): void {
                $table->dropColumn('status');
            });
        }
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex(['posting_date']);
            $table->dropColumn('posting_date');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('note');
        });
    }
};
