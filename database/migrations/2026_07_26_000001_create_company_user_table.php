<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_user', function (Blueprint $table): void {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['company_id', 'user_id']);
            $table->index(['user_id', 'company_id']);
        });

        DB::table('company_user')->insertUsing(
            ['company_id', 'user_id', 'created_at', 'updated_at'],
            DB::table('users')
                ->whereNotNull('company_id')
                ->select([
                    'company_id',
                    'id',
                    DB::raw('CURRENT_TIMESTAMP'),
                    DB::raw('CURRENT_TIMESTAMP'),
                ])
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
