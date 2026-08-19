<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $emails = [
            'superadmin@jawla.test',
            'admin@jawla.test',
            'rep@jawla.test',
            'rep2@jawla.test',
            'manager@jawla.test',
            'warehouse@jawla.test',
            'accounts@jawla.test',
            'purchasing@jawla.test',
            'executive@jawla.test',
            'hr@jawla.test',
            'viewer@jawla.test',
            'disabled@jawla.test',
        ];

        $newPassword = Hash::make('123456789');

        DB::table('users')
            ->whereIn('email', $emails)
            ->update(['password' => $newPassword]);
    }

    public function down(): void
    {
        // One-way data fix
    }
};
