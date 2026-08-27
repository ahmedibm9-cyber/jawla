<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // 1. Drop old CHECK constraint so we can write new enum values
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE daily_visit_assignments MODIFY COLUMN status VARCHAR(255) DEFAULT 'draft'");
        } else {
            DB::statement('ALTER TABLE daily_visit_assignments DROP CONSTRAINT IF EXISTS daily_visit_assignments_status_check');
        }

        // 2. Map old enum values to new ones
        DB::table('daily_visit_assignments')->where('status', 'pending')->update(['status' => 'draft']);
        DB::table('daily_visit_assignments')->where('status', 'missed')->update(['status' => 'rejected']);

        // 3. Add new CHECK constraint / ENUM
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE daily_visit_assignments MODIFY COLUMN status ENUM('draft','pending_approval','approved','rejected','completed') DEFAULT 'draft'");
        } else {
            DB::statement("ALTER TABLE daily_visit_assignments ADD CONSTRAINT daily_visit_assignments_status_check CHECK (status IN ('draft','pending_approval','approved','rejected','completed'))");
        }

        // 3. Add approval columns
        Schema::table('daily_visit_assignments', function (Blueprint $table): void {
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_at');
        });
    }

    public function down(): void
    {
        // Reverse data mapping before restoring old ENUM
        DB::table('daily_visit_assignments')->where('status', 'draft')->update(['status' => 'pending']);
        DB::table('daily_visit_assignments')->where('status', 'rejected')->update(['status' => 'missed']);
        DB::table('daily_visit_assignments')->whereIn('status', ['pending_approval', 'approved'])->update(['status' => 'pending']);

        Schema::table('daily_visit_assignments', function (Blueprint $table): void {
            $table->dropColumn(['submitted_at', 'approved_at']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn('approved_by');
        });

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE daily_visit_assignments MODIFY COLUMN status ENUM('pending','completed','missed') DEFAULT 'pending'");
        } else {
            DB::statement('ALTER TABLE daily_visit_assignments DROP CONSTRAINT IF EXISTS daily_visit_assignments_status_check');
            DB::statement("ALTER TABLE daily_visit_assignments ADD CONSTRAINT daily_visit_assignments_status_check CHECK (status IN ('pending','completed','missed'))");
        }
    }
};
