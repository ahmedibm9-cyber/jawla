<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('van_transfers', function (Blueprint $table): void {
            $table->datetime('shipped_at')->nullable()->after('accepted_at');
            $table->datetime('received_at')->nullable()->after('shipped_at');
            $table->foreignId('in_transit_warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null')->after('received_at');

            $table->index('in_transit_warehouse_id');
        });

        // Expand status enum to include shipped/received/cancelled
        DB::statement('ALTER TABLE van_transfers DROP CONSTRAINT IF EXISTS van_transfers_status_check');
        DB::statement("ALTER TABLE van_transfers ADD CONSTRAINT van_transfers_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'accepted'::text, 'shipped'::text, 'received'::text, 'rejected'::text, 'cancelled'::text]))");
    }

    public function down(): void
    {
        Schema::table('van_transfers', function (Blueprint $table): void {
            $table->dropIndex(['in_transit_warehouse_id']);
            $table->dropColumn(['shipped_at', 'received_at', 'in_transit_warehouse_id']);
        });

        DB::statement('ALTER TABLE van_transfers DROP CONSTRAINT IF EXISTS van_transfers_status_check');
        DB::statement("ALTER TABLE van_transfers ADD CONSTRAINT van_transfers_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'accepted'::text, 'rejected'::text]))");
    }
};
