<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds FK constraints from stocks.batch_id, stock_movements.batch_id,
 * invoice_items.batch_id, return_items.batch_id, van_transfer_items.batch_id,
 * goods_in_transit_items.batch_id to batches table. Ran AFTER batches table exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table): void {
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('set null');
        });
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('set null');
        });
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('set null');
        });
        Schema::table('return_items', function (Blueprint $table): void {
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('set null');
        });
        Schema::table('van_transfer_items', function (Blueprint $table): void {
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('set null');
        });

        // customers FKs to later-created tables
        Schema::table('customers', function (Blueprint $table): void {
            $table->foreign('customer_group_id')->references('id')->on('customer_groups')->onDelete('set null');
            $table->foreign('territory_id')->references('id')->on('territories')->onDelete('set null');
            $table->foreign('price_list_id')->references('id')->on('price_lists')->onDelete('set null');
        });

        // invoices FK to proforma_invoices
        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreign('proforma_invoice_id')->references('id')->on('proforma_invoices')->onDelete('set null');
        });

        // payments FK to modes_of_payment
        Schema::table('payments', function (Blueprint $table): void {
            $table->foreign('mode_of_payment_id')->references('id')->on('modes_of_payment')->onDelete('set null');
        });

        // visits FK to daily_visit_assignments
        Schema::table('visits', function (Blueprint $table): void {
            $table->foreign('daily_visit_assignment_id')->references('id')->on('daily_visit_assignments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table): void {
            $table->dropForeign(['batch_id']);
        });
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropForeign(['batch_id']);
        });
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropForeign(['batch_id']);
        });
        Schema::table('return_items', function (Blueprint $table): void {
            $table->dropForeign(['batch_id']);
        });
        Schema::table('van_transfer_items', function (Blueprint $table): void {
            $table->dropForeign(['batch_id']);
        });
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropForeign(['customer_group_id']);
            $table->dropForeign(['territory_id']);
            $table->dropForeign(['price_list_id']);
        });
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropForeign(['proforma_invoice_id']);
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['mode_of_payment_id']);
        });
        Schema::table('visits', function (Blueprint $table): void {
            $table->dropForeign(['daily_visit_assignment_id']);
        });
    }
};