<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PR-027: add a `year` dimension to naming_series so each (company, doc_type)
     * gets a fresh sequence at the start of every calendar year. Required for
     * legally gapless, per-year legal numbering (invoices, returns, payments,
     * credit notes, refunds) that survives fiscal-year boundaries and audits.
     *
     * The composite unique key moves from (name, company_id) to
     * (name, company_id, year) so one row per year per company per doc type.
     */
    public function up(): void
    {
        $currentYear = (int) date('Y');

        // Step 1: add nullable column + index.
        Schema::table('naming_series', function (Blueprint $table): void {
            $table->smallInteger('year')->nullable()->after('company_id');
            $table->index('year');
        });

        // Step 2: backfill. For this codebase there is no production data,
        // so the existing counter's `current_number` is whatever the dev
        // system has minted this calendar year. Stamping the same year
        // preserves that counter. (On a real production cutover with a
        // multi-year history, the operator would split rows by hand first.)
        DB::table('naming_series')
            ->whereNull('year')
            ->update(['year' => $currentYear]);

        // Step 3: enforce NOT NULL via raw SQL (avoids doctrine/dbal).
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE naming_series ALTER COLUMN year SET NOT NULL');
        } else {
            // sqlite (test) and mysql both support this standard form.
            DB::statement('ALTER TABLE naming_series MODIFY year SMALLINT NOT NULL');
        }

        // Step 4: swap the unique key.
        Schema::table('naming_series', function (Blueprint $table): void {
            $table->dropUnique(['name', 'company_id']);
            $table->unique(['name', 'company_id', 'year'], 'naming_series_name_company_year_uq');
        });
    }

    public function down(): void
    {
        Schema::table('naming_series', function (Blueprint $table): void {
            $table->dropUnique('naming_series_name_company_year_uq');
        });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE naming_series ALTER COLUMN year DROP NOT NULL');
        } else {
            DB::statement('ALTER TABLE naming_series MODIFY year SMALLINT NULL');
        }

        Schema::table('naming_series', function (Blueprint $table): void {
            $table->dropIndex(['year']);
            $table->dropColumn('year');
            $table->unique(['name', 'company_id']);
        });
    }
};
