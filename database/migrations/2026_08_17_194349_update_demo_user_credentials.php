<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mapping = [
            'superadmin@jawla.test' => 'superadmin',
            'admin@jawla.test' => 'admin',
            'rep@jawla.test' => 'rep',
            'rep2@jawla.test' => 'rep2',
            'manager@jawla.test' => 'manager',
            'warehouse@jawla.test' => 'warehouse',
            'accounts@jawla.test' => 'accounts',
            'purchasing@jawla.test' => 'purchasing',
            'executive@jawla.test' => 'executive',
            'hr@jawla.test' => 'hr',
            'viewer@jawla.test' => 'viewer',
            'disabled@jawla.test' => 'disabled',
        ];

        $newPassword = Hash::make('123456789');

        foreach ($mapping as $oldEmail => $newEmail) {
            DB::table('users')
                ->where('email', $oldEmail)
                ->update([
                    'email' => $newEmail,
                    'password' => $newPassword,
                ]);
        }
    }

    public function down(): void
    {
        // No rollback - this is a one-way data migration
    }
};
