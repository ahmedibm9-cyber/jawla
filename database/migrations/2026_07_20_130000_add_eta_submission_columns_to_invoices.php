<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('eta_status')->default('not_submitted')->after('zatca_response');
            $table->string('eta_submission_uuid')->nullable()->after('eta_status');
            $table->string('eta_long_id')->nullable()->after('eta_submission_uuid');
            $table->timestamp('eta_submitted_at')->nullable()->after('eta_long_id');
            $table->json('eta_response')->nullable()->after('eta_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn(['eta_status', 'eta_submission_uuid', 'eta_long_id', 'eta_submitted_at', 'eta_response']);
        });
    }
};
