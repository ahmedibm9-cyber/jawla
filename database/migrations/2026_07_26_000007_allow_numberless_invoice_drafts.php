<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE invoices ALTER COLUMN invoice_number DROP NOT NULL');
        DB::statement('ALTER TABLE invoices ALTER COLUMN issued_at DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE invoices ALTER COLUMN invoice_number SET NOT NULL');
        DB::statement('ALTER TABLE invoices ALTER COLUMN issued_at SET NOT NULL');
    }
};
