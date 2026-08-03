<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'require_approved_devices')) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->boolean('require_approved_devices')->default(false)->after('is_active');
            });
        }

        if (! Schema::hasTable('organization_units')) {
            Schema::create('organization_units', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('organization_units')->restrictOnDelete();
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 20);
                $table->string('code', 50);
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestampsTz();
                $table->unique(['company_id', 'code']);
                $table->index(['company_id', 'type', 'is_active']);
            });
        }

        if (! Schema::hasColumn('users', 'primary_organization_unit_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('primary_organization_unit_id')->nullable()->after('company_id')
                    ->constrained('organization_units')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('organization_unit_user')) {
            Schema::create('organization_unit_user', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('organization_unit_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestampTz('assigned_at')->useCurrent();
                $table->unique(['organization_unit_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('representative_profiles')) {
            Schema::create('representative_profiles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('job_title')->nullable();
                $table->date('hire_date')->nullable();
                $table->string('vehicle_code')->nullable();
                $table->string('national_id')->nullable();
                $table->string('emergency_contact')->nullable();
                $table->json('skills')->nullable();
                $table->timestampsTz();
                $table->index(['company_id', 'supervisor_id']);
            });
        }

        if (! Schema::hasTable('devices')) {
            Schema::create('devices', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->uuid('device_uuid');
                $table->string('name');
                $table->string('platform')->nullable();
                $table->string('fingerprint_hash', 64)->nullable();
                $table->string('status', 20)->default('pending');
                $table->json('metadata')->nullable();
                $table->timestampTz('last_seen_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestampTz('approved_at')->nullable();
                $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestampTz('revoked_at')->nullable();
                $table->timestampsTz();
                $table->unique(['company_id', 'device_uuid']);
                $table->index(['company_id', 'user_id', 'status']);
            });
        }
    }
};
