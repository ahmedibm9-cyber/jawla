<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * users.preferences was originally added as `json`. PostgreSQL has no equality
 * operator for `json`, so any `SELECT DISTINCT users.*` (e.g. loading route
 * users on admin resources) throws "could not identify an equality operator
 * for type json" and 500s. `jsonb` supports equality/DISTINCT, so convert to it.
 *
 * Safe in every state: converts an existing json column, is a no-op cast if it
 * is already jsonb, and only runs on PostgreSQL (SQLite/MySQL are unaffected).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN preferences TYPE jsonb USING preferences::jsonb');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN preferences TYPE json USING preferences::json');
        }
    }
};
