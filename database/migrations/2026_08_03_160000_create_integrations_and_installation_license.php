<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('url');
            $table->text('secret');
            $table->json('events');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('timeout_seconds')->default(10);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('webhook_endpoint_id')->constrained()->cascadeOnDelete();
            $table->uuid('event_id')->unique();
            $table->string('event_type');
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('response_excerpt')->nullable();
            $table->text('last_error')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('next_retry_at')->nullable();
            $table->timestampsTz();
            $table->index(['company_id', 'status', 'next_retry_at']);
        });

        Schema::create('installation_licenses', function (Blueprint $table): void {
            $table->id();
            $table->uuid('license_id')->unique();
            $table->string('licensee');
            $table->string('installation_id')->nullable();
            $table->string('edition', 50);
            $table->unsignedInteger('max_users')->nullable();
            $table->json('features')->nullable();
            $table->date('valid_from');
            $table->date('expires_at');
            $table->string('status', 20);
            $table->text('raw_document');
            $table->text('signature');
            $table->string('document_hash', 64);
            $table->timestampTz('last_verified_at')->nullable();
            $table->foreignId('installed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_licenses');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
    }
};
