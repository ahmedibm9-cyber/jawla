<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mapping = [
            'superadmin' => 'superadmin@jawla.test',
            'admin' => 'admin@jawla.test',
            'rep' => 'rep@jawla.test',
            'rep2' => 'rep2@jawla.test',
            'manager' => 'manager@jawla.test',
            'warehouse' => 'warehouse@jawla.test',
            'accounts' => 'accounts@jawla.test',
            'purchasing' => 'purchasing@jawla.test',
            'executive' => 'executive@jawla.test',
            'hr' => 'hr@jawla.test',
            'viewer' => 'viewer@jawla.test',
            'disabled' => 'disabled@jawla.test',
        ];

        foreach ($mapping as $shortEmail => $fullEmail) {
            DB::table('users')
                ->where('email', $shortEmail)
                ->update(['email' => $fullEmail]);
        }
    }

    public function down(): void
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

        foreach ($mapping as $fullEmail => $shortEmail) {
            DB::table('users')
                ->where('email', $fullEmail)
                ->update(['email' => $shortEmail]);
        }
    }
};
