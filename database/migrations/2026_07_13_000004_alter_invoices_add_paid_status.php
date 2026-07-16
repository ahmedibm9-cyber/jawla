<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check');
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status::text = ANY (ARRAY['draft'::text, 'submitted'::text, 'partially_paid'::text, 'paid'::text, 'cancelled'::text, 'amended'::text]))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check');
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status::text = ANY (ARRAY['draft'::text, 'submitted'::text, 'cancelled'::text, 'amended'::text]))");
    }
};
