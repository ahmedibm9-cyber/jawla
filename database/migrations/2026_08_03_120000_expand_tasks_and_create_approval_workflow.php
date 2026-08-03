<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_status_check');
        }

        DB::table('tasks')->where('status', 'open')->update(['status' => 'assigned']);
        DB::table('tasks')->where('status', 'done')->update(['status' => 'approved']);

        Schema::table('tasks', function (Blueprint $table): void {
            $table->string('status', 30)->default('draft')->change();
            $table->foreignId('reviewer_id')->nullable()->after('assigned_to')->constrained('users')->restrictOnDelete();
            $table->foreignId('final_approver_id')->nullable()->after('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('priority', 20)->default('normal')->after('status');
            $table->boolean('requires_approval')->default(true)->after('priority');
            $table->json('checklist')->nullable()->after('requires_approval');
            $table->text('completion_notes')->nullable()->after('checklist');
            $table->text('decision_reason')->nullable()->after('completion_notes');
            $table->timestampTz('accepted_at')->nullable()->after('completed_at');
            $table->timestampTz('started_at')->nullable()->after('accepted_at');
            $table->timestampTz('submitted_at')->nullable()->after('started_at');
            $table->timestampTz('approved_at')->nullable()->after('submitted_at');
            $table->timestampTz('rejected_at')->nullable()->after('approved_at');
            $table->timestampTz('reopened_at')->nullable()->after('rejected_at');
            $table->timestampTz('cancelled_at')->nullable()->after('reopened_at');
            $table->index(['company_id', 'status', 'due_date']);
        });

        Schema::create('approval_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->morphs('approvable');
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 30)->default('pending');
            $table->unsignedSmallInteger('current_sequence')->default(1);
            $table->timestampTz('submitted_at');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->index(['company_id', 'status', 'submitted_at']);
        });

        Schema::create('approval_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('approval_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 30)->default('waiting');
            $table->text('decision_reason')->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->timestampsTz();
            $table->unique(['approval_request_id', 'sequence']);
            $table->index(['approver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
        Schema::dropIfExists('approval_requests');

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'status', 'due_date']);
            $table->dropConstrainedForeignId('reviewer_id');
            $table->dropConstrainedForeignId('final_approver_id');
            $table->dropColumn([
                'priority', 'requires_approval', 'checklist', 'completion_notes',
                'decision_reason', 'accepted_at', 'started_at', 'submitted_at',
                'approved_at', 'rejected_at', 'reopened_at', 'cancelled_at',
            ]);
        });

        DB::table('tasks')->where('status', 'assigned')->update(['status' => 'open']);
        DB::table('tasks')->where('status', 'approved')->update(['status' => 'done']);
    }
};
