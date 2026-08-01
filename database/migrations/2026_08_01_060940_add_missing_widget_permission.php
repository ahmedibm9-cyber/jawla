<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore([
            ['name' => 'view:low_stock_alert_widget', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Grant to super_admin and admin (they get all perms via RoleSeeder,
        // but those roles were already synced before this widget existed).
        $permId = DB::table('permissions')->where('name', 'view:low_stock_alert_widget')->value('id');
        if ($permId) {
            foreach (['super_admin', 'admin'] as $role) {
                $roleId = DB::table('roles')->where('name', $role)->value('id');
                if ($roleId) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'role_id' => $roleId,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }

        // Set a known staging password for all demo users (the seeder generates
        // random passwords that we cannot recover from the CLI).
        $hash = Hash::make('staging-demo-2026');
        DB::table('users')->where('email', 'like', '%@jawla.test')->update(['password' => $hash]);
    }

    public function down(): void
    {
        DB::table('role_has_permissions')->where('permission_id', function ($q) {
            $q->select('id')->from('permissions')->where('name', 'view:low_stock_alert_widget');
        })->delete();
        DB::table('permissions')->where('name', 'view:low_stock_alert_widget')->delete();
    }
};
