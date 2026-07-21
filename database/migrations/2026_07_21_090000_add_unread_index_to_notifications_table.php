<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The rep/admin shell counts unread notifications on every authenticated page
 * load: WHERE notifiable_type = ? AND notifiable_id = ? AND read_at IS NULL.
 * The morphs() index covers (notifiable_type, notifiable_id) but not read_at, so
 * every check still scans a user's read history. On Postgres a partial index
 * over the unread rows only keeps this hot path tiny and self-maintaining;
 * other drivers get an equivalent composite index.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(
                'CREATE INDEX IF NOT EXISTS notifications_unread_idx '.
                'ON notifications (notifiable_type, notifiable_id) WHERE read_at IS NULL'
            );

            return;
        }

        Schema::table('notifications', function ($table): void {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_unread_idx');
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS notifications_unread_idx');

            return;
        }

        Schema::table('notifications', function ($table): void {
            $table->dropIndex('notifications_unread_idx');
        });
    }
};
