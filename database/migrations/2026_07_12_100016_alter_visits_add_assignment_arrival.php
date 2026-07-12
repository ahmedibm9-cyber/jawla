<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            $table->unsignedBigInteger('daily_visit_assignment_id')->nullable()->after('work_session_id');
            $table->boolean('arrival_confirmed')->default(false)->after('status');
            $table->datetime('arrival_confirmed_at')->nullable()->after('arrival_confirmed');

            $table->index('daily_visit_assignment_id');
        });

        // Expand purpose enum to include custom_visit
        DB::statement('ALTER TABLE visits DROP CONSTRAINT IF EXISTS visits_purpose_check');
        DB::statement("ALTER TABLE visits ADD CONSTRAINT visits_purpose_check CHECK (purpose::text = ANY (ARRAY['sale'::text, 'collection'::text, 'return'::text, 'survey'::text, 'other'::text, 'custom_visit'::text]))");
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            $table->dropIndex(['daily_visit_assignment_id']);
            $table->dropColumn(['daily_visit_assignment_id', 'arrival_confirmed', 'arrival_confirmed_at']);
        });

        DB::statement('ALTER TABLE visits DROP CONSTRAINT IF EXISTS visits_purpose_check');
        DB::statement("ALTER TABLE visits ADD CONSTRAINT visits_purpose_check CHECK (purpose::text = ANY (ARRAY['sale'::text, 'collection'::text, 'return'::text, 'survey'::text, 'other'::text]))");
    }
};