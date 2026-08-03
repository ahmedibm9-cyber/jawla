<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table): void {
            $table->dropUnique(['order_number']);
            $table->unique(['company_id', 'order_number']);
        });

        Schema::table('return_requests', function (Blueprint $table): void {
            $table->dropUnique(['request_number']);
            $table->unique(['company_id', 'request_number']);
            $table->foreignId('destination_warehouse_id')->nullable()->after('return_record_id')
                ->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('quarantine_warehouse_id')->nullable()->after('destination_warehouse_id')
                ->constrained('warehouses')->restrictOnDelete();
        });

        Schema::table('sync_receipts', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'idempotency_key']);
            $table->unique(['company_id', 'user_id', 'idempotency_key']);
        });

        Schema::table('collection_submissions', function (Blueprint $table): void {
            $table->foreignId('supervisor_reviewed_by')->nullable()->after('captured_at')
                ->constrained('users')->nullOnDelete();
            $table->timestampTz('supervisor_reviewed_at')->nullable()->after('supervisor_reviewed_by');
            $table->foreignId('finance_reviewed_by')->nullable()->after('supervisor_reviewed_at')
                ->constrained('users')->nullOnDelete();
            $table->timestampTz('finance_reviewed_at')->nullable()->after('finance_reviewed_by');
            $table->foreignId('reconciled_by')->nullable()->after('finance_reviewed_at')
                ->constrained('users')->nullOnDelete();
            $table->timestampTz('reconciled_at')->nullable()->after('reconciled_by');
        });

        Schema::table('return_request_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 14, 2)->default(0)->after('condition');
            $table->decimal('tax_amount', 14, 2)->default(0)->after('line_total');
            $table->decimal('total', 14, 2)->default(0)->after('tax_amount');
        });

        Schema::table('returns', function (Blueprint $table): void {
            $table->foreignId('destination_warehouse_id')->nullable()->after('against_invoice_id')
                ->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('quarantine_warehouse_id')->nullable()->after('destination_warehouse_id')
                ->constrained('warehouses')->restrictOnDelete();
        });

        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            $table->uuid('lease_token')->nullable()->after('status');
            $table->timestampTz('leased_at')->nullable()->after('lease_token');
            $table->index(['status', 'leased_at']);
        });

        Schema::table('webhook_endpoints', function (Blueprint $table): void {
            $table->timestampTz('secret_rotated_at')->nullable()->after('secret');
        });

        Schema::table('stock_import_previews', function (Blueprint $table): void {
            $table->string('source_disk')->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('returns', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('quarantine_warehouse_id');
            $table->dropConstrainedForeignId('destination_warehouse_id');
        });

        Schema::table('stock_import_previews', function (Blueprint $table): void {
            $table->dropColumn('source_disk');
        });

        Schema::table('webhook_endpoints', function (Blueprint $table): void {
            $table->dropColumn('secret_rotated_at');
        });

        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            $table->dropIndex(['status', 'leased_at']);
            $table->dropColumn(['lease_token', 'leased_at']);
        });

        Schema::table('return_request_items', function (Blueprint $table): void {
            $table->dropColumn(['unit_price', 'tax_amount', 'total']);
        });

        Schema::table('collection_submissions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('supervisor_reviewed_by');
            $table->dropColumn('supervisor_reviewed_at');
            $table->dropConstrainedForeignId('finance_reviewed_by');
            $table->dropColumn('finance_reviewed_at');
            $table->dropConstrainedForeignId('reconciled_by');
            $table->dropColumn('reconciled_at');
        });

        Schema::table('sync_receipts', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'user_id', 'idempotency_key']);
            $table->unique(['company_id', 'idempotency_key']);
        });

        Schema::table('return_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('quarantine_warehouse_id');
            $table->dropConstrainedForeignId('destination_warehouse_id');
            $table->dropUnique(['company_id', 'request_number']);
            $table->unique('request_number');
        });

        Schema::table('sales_orders', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'order_number']);
            $table->unique('order_number');
        });
    }
};
